<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\Contracts\ClientInfoProviderInterface;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\Enum\VerificationStatus;
use App\Domain\Exception\InvalidIdentifierStateException;
use DateTimeImmutable;
use PDO;

readonly class AdminEmailVerificationService
{
    public function __construct(
        private AdminEmailVerificationRepositoryInterface $repository,
        private AuthoritativeSecurityAuditWriterInterface $auditWriter,
        private ClientInfoProviderInterface $clientInfoProvider,
        private PDO $pdo
    ) {
    }

    public function verify(int $adminId): void
    {
        $currentStatus = $this->repository->getVerificationStatus($adminId);

        if ($currentStatus === VerificationStatus::VERIFIED) {
            throw new InvalidIdentifierStateException("Identifier is already verified.");
        }

        if ($currentStatus === VerificationStatus::FAILED) {
            throw new InvalidIdentifierStateException("Cannot verify a failed identifier.");
        }

        $this->pdo->beginTransaction();
        try {
            $this->repository->markVerified($adminId, (new DateTimeImmutable())->format('Y-m-d H:i:s'));

            $this->auditWriter->write(new AuditEventDTO(
                $adminId,
                'identity_verified',
                'admin',
                $adminId,
                'CRITICAL',
                [
                    'previous_status' => $currentStatus->value,
                    'new_status' => VerificationStatus::VERIFIED->value,
                    'ip_address' => $this->clientInfoProvider->getIpAddress(),
                    'user_agent' => $this->clientInfoProvider->getUserAgent()
                ],
                bin2hex(random_bytes(16)),
                new DateTimeImmutable()
            ));

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function startVerification(int $adminId): void
    {
        $currentStatus = $this->repository->getVerificationStatus($adminId);

        if ($currentStatus === VerificationStatus::VERIFIED) {
            throw new InvalidIdentifierStateException("Cannot reset a verified identifier.");
        }

        $this->pdo->beginTransaction();
        try {
            $this->repository->markPending($adminId);

            $this->auditWriter->write(new AuditEventDTO(
                $adminId,
                'identity_verification_started',
                'admin',
                $adminId,
                'MEDIUM',
                [
                    'previous_status' => $currentStatus->value,
                    'ip_address' => $this->clientInfoProvider->getIpAddress()
                ],
                bin2hex(random_bytes(16)),
                new DateTimeImmutable()
            ));

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function failVerification(int $adminId): void
    {
        $currentStatus = $this->repository->getVerificationStatus($adminId);

        if ($currentStatus === VerificationStatus::VERIFIED) {
            throw new InvalidIdentifierStateException("Cannot fail a verified identifier.");
        }

        $this->pdo->beginTransaction();
        try {
            $this->repository->markFailed($adminId);

            $this->auditWriter->write(new AuditEventDTO(
                $adminId,
                'identity_verification_failed',
                'admin',
                $adminId,
                'HIGH',
                [
                    'previous_status' => $currentStatus->value,
                    'ip_address' => $this->clientInfoProvider->getIpAddress()
                ],
                bin2hex(random_bytes(16)),
                new DateTimeImmutable()
            ));

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
