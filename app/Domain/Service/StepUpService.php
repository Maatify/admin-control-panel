<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AuditLoggerInterface;
use App\Domain\Contracts\StepUpGrantRepositoryInterface;
use App\Domain\Contracts\TotpSecretRepositoryInterface;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\DTO\SecurityEventDTO;
use App\Domain\DTO\StepUpGrant;
use App\Domain\DTO\TotpVerificationResultDTO;
use App\Domain\Enum\Scope;
use App\Domain\Enum\SessionState;
use DateTimeImmutable;

readonly class StepUpService
{
    public function __construct(
        private StepUpGrantRepositoryInterface $grantRepository,
        private TotpSecretRepositoryInterface $totpSecretRepository,
        private TotpServiceInterface $totpService,
        private AuditLoggerInterface $auditLogger
    ) {
    }

    public function verifyTotp(int $adminId, string $sessionId, string $code, ?Scope $requestedScope = null): TotpVerificationResultDTO
    {
        $secret = $this->totpSecretRepository->get($adminId);
        if ($secret === null) {
             $this->logSecurityEvent($adminId, $sessionId, 'stepup_primary_failed', ['reason' => 'no_totp_enrolled']);
             return new TotpVerificationResultDTO(false, 'TOTP not enrolled');
        }

        if (!$this->totpService->verify($secret, $code)) {
            $this->logSecurityEvent($adminId, $sessionId, 'stepup_primary_failed', ['reason' => 'invalid_code']);
            return new TotpVerificationResultDTO(false, 'Invalid code');
        }

        if ($requestedScope !== null && $requestedScope !== Scope::LOGIN) {
            $this->issueScopedGrant($adminId, $sessionId, $requestedScope);
        } else {
            // Issue Primary Grant
            $this->issuePrimaryGrant($adminId, $sessionId);
        }

        return new TotpVerificationResultDTO(true);
    }

    public function enableTotp(int $adminId, string $sessionId, string $secret, string $code): bool
    {
        if (!$this->totpService->verify($secret, $code)) {
            $this->logSecurityEvent($adminId, $sessionId, 'stepup_enroll_failed', ['reason' => 'invalid_code']);
            return false;
        }

        $this->totpSecretRepository->save($adminId, $secret);
        $this->issuePrimaryGrant($adminId, $sessionId);

        $this->auditLogger->log(new AuditEventDTO(
            $adminId,
            'system',
            $adminId,
            'stepup_enrolled',
            ['session_id' => $sessionId],
            '0.0.0.0',
            'system',
            new DateTimeImmutable()
        ));

        return true;
    }

    public function issuePrimaryGrant(int $adminId, string $sessionId): void
    {
        $grant = new StepUpGrant(
            $adminId,
            $sessionId,
            Scope::LOGIN, // Primary Scope
            new DateTimeImmutable(),
            new DateTimeImmutable('+2 hours'), // Match session expiry usually
            false
        );

        $this->grantRepository->save($grant);

        $this->auditLogger->log(new AuditEventDTO(
            $adminId,
            'system',
            $adminId,
            'stepup_primary_issued',
            ['session_id' => $sessionId],
            '0.0.0.0', // Context not available here easily without request stack
            'system',
            new DateTimeImmutable()
        ));
    }

    public function issueScopedGrant(int $adminId, string $sessionId, Scope $scope): void
    {
        $grant = new StepUpGrant(
            $adminId,
            $sessionId,
            $scope,
            new DateTimeImmutable(),
            new DateTimeImmutable('+15 minutes'), // Scoped grants are short-lived
            false
        );

        $this->grantRepository->save($grant);

        $this->auditLogger->log(new AuditEventDTO(
            $adminId,
            'system',
            $adminId,
            'stepup_scoped_issued',
            ['session_id' => $sessionId, 'scope' => $scope->value],
            '0.0.0.0',
            'system',
            new DateTimeImmutable()
        ));
    }

    public function logDenial(int $adminId, string $sessionId, Scope $requiredScope): void
    {
        $this->auditLogger->log(new AuditEventDTO(
            $adminId,
            'system',
            $adminId,
            'stepup_denied',
            [
                'session_id' => $sessionId,
                'required_scope' => $requiredScope->value,
                'severity' => 'warning'
            ],
            '0.0.0.0',
            'system',
            new DateTimeImmutable()
        ));
    }

    public function hasGrant(int $adminId, string $sessionId, Scope $scope): bool
    {
        $grant = $this->grantRepository->find($adminId, $sessionId, $scope);
        if ($grant === null) {
            return false;
        }

        if ($grant->expiresAt < new DateTimeImmutable()) {
            return false;
        }

        // Check single use?
        if ($grant->singleUse) {
            // Consume grant
            $this->grantRepository->revoke($adminId, $sessionId, $scope);
             $this->auditLogger->log(new AuditEventDTO(
                $adminId,
                'system',
                $adminId,
                'stepup_grant_consumed',
                ['scope' => $scope->value],
                '0.0.0.0',
                'system',
                new DateTimeImmutable()
            ));
        }

        return true;
    }

    public function getSessionState(int $adminId, string $sessionId): SessionState
    {
        // Session existence check is assumed to be done by SessionGuard (database check).
        // If we are here, DB session is valid.

        // Check for Primary Grant (Scope::LOGIN)
        // We reuse logic but avoid consuming if it was single use (Primary is likely not single use, but checking finds it)
        // Actually, Primary Grant is NOT single use.
        $primaryGrant = $this->grantRepository->find($adminId, $sessionId, Scope::LOGIN);

        if ($primaryGrant !== null && $primaryGrant->expiresAt > new DateTimeImmutable()) {
            return SessionState::ACTIVE;
        }

        return SessionState::PENDING_STEP_UP;
    }

    /**
     * @param array<string, mixed> $details
     */
    private function logSecurityEvent(int $adminId, string $sessionId, string $event, array $details): void
    {
        $details['session_id'] = $sessionId;
        $details['scope'] = Scope::LOGIN->value;
        $details['severity'] = 'error';

        /** @var array<string, scalar> $context */
        $context = $details;

        $this->auditLogger->log(new AuditEventDTO(
            $adminId,
            'security',
            $adminId,
            $event,
            $context,
            '0.0.0.0',
            'system',
            new DateTimeImmutable()
        ));
    }
}
