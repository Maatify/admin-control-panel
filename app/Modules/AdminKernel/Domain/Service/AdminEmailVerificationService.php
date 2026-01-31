<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Service;

use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use Maatify\AdminKernel\Domain\Enum\VerificationStatus;
use Maatify\AdminKernel\Domain\Exception\InvalidIdentifierStateException;
use Maatify\SharedCommon\Contracts\ClockInterface;
use PDO;

readonly class AdminEmailVerificationService
{
    public function __construct(
        private AdminEmailVerificationRepositoryInterface $repository,
        private PDO $pdo,
        private ClockInterface $clock
    ) {
    }

    /* ===============================
     * SELF VERIFY
     * PENDING → VERIFIED
     *
     * Performed by the email owner (admin side).
     * This action represents a successful verification
     * initiated by the admin for their own email.
     *
     * Rules:
     * - Allowed only if status is PENDING
     * - FAILED or REPLACED identifiers are forbidden
     * - Emits authoritative audit event
     * =============================== */

    public function selfVerify(int $emailId, RequestContext $context): void
    {
        $adminEmailIdentifierDTO = $this->repository->getEmailIdentity($emailId);

        $currentStatus = $adminEmailIdentifierDTO->verificationStatus;
        $adminId = $adminEmailIdentifierDTO->adminId;

        if ($currentStatus === VerificationStatus::VERIFIED) {
            throw new InvalidIdentifierStateException("Identifier is already verified.");
        }

        if ($currentStatus === VerificationStatus::FAILED) {
            throw new InvalidIdentifierStateException("Cannot verify a failed identifier.");
        }

        if ($currentStatus === VerificationStatus::REPLACED) {
            throw new InvalidIdentifierStateException("Cannot verify a replaced identifier.");
        }

        $this->pdo->beginTransaction();
        try {
            $this->repository->markVerified($emailId, $this->clock->now()->format('Y-m-d H:i:s'));

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }


    /* ===============================
     * VERIFY
     * PENDING → VERIFIED
     * =============================== */
    public function verify(int $emailId, RequestContext $context): void
    {
        $identity = $this->repository->getEmailIdentity($emailId);

        if ($identity->verificationStatus !== VerificationStatus::PENDING) {
            throw new InvalidIdentifierStateException(
                "Cannot verify email in state {$identity->verificationStatus->value}"
            );
        }

        $this->transition(
            emailId: $emailId,
            adminId: $identity->adminId,
            from: $identity->verificationStatus,
            to: VerificationStatus::VERIFIED,
            auditAction: 'admin_email_verified',
            severity: 'CRITICAL',
            context: $context
        );
    }

    /* ===============================
     * FAIL
     * PENDING → FAILED
     * =============================== */
    public function fail(int $emailId, RequestContext $context): void
    {
        $identity = $this->repository->getEmailIdentity($emailId);

        if ($identity->verificationStatus !== VerificationStatus::PENDING) {
            throw new InvalidIdentifierStateException(
                "Cannot fail email in state {$identity->verificationStatus->value}"
            );
        }

        $this->transition(
            emailId: $emailId,
            adminId: $identity->adminId,
            from: $identity->verificationStatus,
            to: VerificationStatus::FAILED,
            auditAction: 'admin_email_failed',
            severity: 'HIGH',
            context: $context
        );
    }

    /* ===============================
     * REPLACE
     * PENDING | VERIFIED → REPLACED
     * =============================== */
    public function replace(int $emailId, RequestContext $context): void
    {
        $identity = $this->repository->getEmailIdentity($emailId);

        if (!in_array(
            $identity->verificationStatus,
            [VerificationStatus::PENDING, VerificationStatus::VERIFIED],
            true
        )) {
            throw new InvalidIdentifierStateException(
                "Cannot replace email in state {$identity->verificationStatus->value}"
            );
        }

        $this->transition(
            emailId: $emailId,
            adminId: $identity->adminId,
            from: $identity->verificationStatus,
            to: VerificationStatus::REPLACED,
            auditAction: 'admin_email_replaced',
            severity: 'HIGH',
            context: $context
        );
    }

    /* ===============================
     * RESTART VERIFICATION
     * FAILED | REPLACED → PENDING
     * =============================== */
    public function restart(int $emailId, RequestContext $context): void
    {
        $identity = $this->repository->getEmailIdentity($emailId);

        if (!in_array(
            $identity->verificationStatus,
            [VerificationStatus::FAILED, VerificationStatus::REPLACED],
            true
        )) {
            throw new InvalidIdentifierStateException(
                "Cannot restart verification from state {$identity->verificationStatus->value}"
            );
        }

        $this->transition(
            emailId: $emailId,
            adminId: $identity->adminId,
            from: $identity->verificationStatus,
            to: VerificationStatus::PENDING,
            auditAction: 'admin_email_verification_restarted',
            severity: 'MEDIUM',
            context: $context
        );
    }

    /* ===============================
     * INTERNAL STATE TRANSITION
     * =============================== */
    private function transition(
        int $emailId,
        int $adminId,
        VerificationStatus $from,
        VerificationStatus $to,
        string $auditAction,
        string $severity,
        RequestContext $context
    ): void {
        $this->pdo->beginTransaction();

        try {
            match ($to) {
                VerificationStatus::VERIFIED =>
                $this->repository->markVerified(
                    $emailId,
                    $this->clock->now()->format('Y-m-d H:i:s')
                ),

                VerificationStatus::FAILED =>
                $this->repository->markFailed($emailId),

                VerificationStatus::REPLACED =>
                $this->repository->markReplaced($emailId),

                VerificationStatus::PENDING =>
                $this->repository->markPending($emailId),
            };

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
