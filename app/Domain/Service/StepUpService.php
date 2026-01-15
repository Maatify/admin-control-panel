<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\TelemetryAuditLoggerInterface;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Context\RequestContext;
use App\Domain\Contracts\StepUpGrantRepositoryInterface;
use App\Domain\Contracts\TotpSecretRepositoryInterface;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\DTO\LegacyAuditEventDTO;
use App\Domain\DTO\SecurityEventDTO;
use App\Domain\DTO\StepUpGrant;
use App\Domain\DTO\TotpVerificationResultDTO;
use App\Domain\Enum\Scope;
use App\Domain\Enum\SessionState;
use DateTimeImmutable;
use PDO;

readonly class StepUpService
{
    public function __construct(
        private StepUpGrantRepositoryInterface $grantRepository,
        private TotpSecretRepositoryInterface $totpSecretRepository,
        private TotpServiceInterface $totpService,
        private TelemetryAuditLoggerInterface $auditLogger,
        private AuthoritativeSecurityAuditWriterInterface $outboxWriter,
        private RecoveryStateService $recoveryState,
        private PDO $pdo
    ) {
    }

    public function verifyTotp(int $adminId, string $token, string $code, RequestContext $context, ?Scope $requestedScope = null): TotpVerificationResultDTO
    {
        $this->recoveryState->enforce(RecoveryStateService::ACTION_OTP_VERIFY, $adminId, $context);

        $sessionId = hash('sha256', $token);

        $secret = $this->totpSecretRepository->get($adminId);
        if ($secret === null) {
             $this->logSecurityEvent($adminId, $sessionId, 'stepup_primary_failed', ['reason' => 'no_totp_enrolled'], $context);
             return new TotpVerificationResultDTO(false, 'TOTP not enrolled');
        }

        if (!$this->totpService->verify($secret, $code)) {
            $this->logSecurityEvent($adminId, $sessionId, 'stepup_primary_failed', ['reason' => 'invalid_code'], $context);
            return new TotpVerificationResultDTO(false, 'Invalid code');
        }

        $this->pdo->beginTransaction();
        try {
            if ($requestedScope !== null && $requestedScope !== Scope::LOGIN) {
                $this->issueScopedGrant($adminId, $token, $requestedScope, $context);
            } else {
                // Issue Primary Grant
                $this->issuePrimaryGrant($adminId, $token, $context);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return new TotpVerificationResultDTO(true);
    }

    public function enableTotp(int $adminId, string $token, string $secret, string $code, RequestContext $context): bool
    {
        $this->recoveryState->enforce(RecoveryStateService::ACTION_OTP_VERIFY, $adminId, $context);

        $sessionId = hash('sha256', $token);

        if (!$this->totpService->verify($secret, $code)) {
            $this->logSecurityEvent($adminId, $sessionId, 'stepup_enroll_failed', ['reason' => 'invalid_code'], $context);
            return false;
        }

        $this->pdo->beginTransaction();
        try {
            $this->totpSecretRepository->save($adminId, $secret);

            $this->issuePrimaryGrant($adminId, $token, $context);

            $this->auditLogger->log(new LegacyAuditEventDTO(
                $adminId,
                'system',
                $adminId,
                'stepup_enrolled',
                ['session_id' => $sessionId],
                $context->ipAddress,
                $context->userAgent,
                $context->requestId,
                new DateTimeImmutable()
            ));

            $this->outboxWriter->write(new AuditEventDTO(
                $adminId,
                'stepup_enrolled',
                'admin',
                $adminId,
                'HIGH',
                ['session_id' => $sessionId],
                bin2hex(random_bytes(16)),
                $context->requestId,
                new DateTimeImmutable()
            ));

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return true;
    }

    public function issuePrimaryGrant(int $adminId, string $token, RequestContext $context): void
    {
        $sessionId = hash('sha256', $token);

        $grant = new StepUpGrant(
            $adminId,
            $sessionId,
            Scope::LOGIN, // Primary Scope
            $this->getRiskHash($context),
            new DateTimeImmutable(),
            new DateTimeImmutable('+2 hours'), // Match session expiry usually
            false
        );

        $this->grantRepository->save($grant);

        $this->auditLogger->log(new LegacyAuditEventDTO(
            $adminId,
            'system',
            $adminId,
            'stepup_primary_issued',
            ['session_id' => $sessionId],
            $context->ipAddress,
            $context->userAgent,
            $context->requestId,
            new DateTimeImmutable()
        ));

        $this->outboxWriter->write(new AuditEventDTO(
            $adminId,
            'stepup_primary_issued',
            'grant',
            $adminId,
            'MEDIUM',
            ['session_id' => $sessionId, 'scope' => Scope::LOGIN->value],
            bin2hex(random_bytes(16)),
            $context->requestId,
            new DateTimeImmutable()
        ));
    }

    public function issueScopedGrant(int $adminId, string $token, Scope $scope, RequestContext $context): void
    {
        $sessionId = hash('sha256', $token);

        $grant = new StepUpGrant(
            $adminId,
            $sessionId,
            $scope,
            $this->getRiskHash($context),
            new DateTimeImmutable(),
            new DateTimeImmutable('+15 minutes'), // Scoped grants are short-lived
            false
        );

        $this->grantRepository->save($grant);

        $this->auditLogger->log(new LegacyAuditEventDTO(
            $adminId,
            'system',
            $adminId,
            'stepup_scoped_issued',
            ['session_id' => $sessionId, 'scope' => $scope->value],
            $context->ipAddress,
            $context->userAgent,
            $context->requestId,
            new DateTimeImmutable()
        ));

        $this->outboxWriter->write(new AuditEventDTO(
            $adminId,
            'stepup_scoped_issued',
            'grant',
            $adminId,
            'MEDIUM',
            ['session_id' => $sessionId, 'scope' => $scope->value],
            bin2hex(random_bytes(16)),
            $context->requestId,
            new DateTimeImmutable()
        ));
    }

    public function logDenial(int $adminId, string $token, Scope $requiredScope, RequestContext $context): void
    {
        $sessionId = hash('sha256', $token);

        $this->auditLogger->log(new LegacyAuditEventDTO(
            $adminId,
            'system',
            $adminId,
            'stepup_denied',
            [
                'session_id' => $sessionId,
                'required_scope' => $requiredScope->value,
                'severity' => 'warning'
            ],
            $context->ipAddress,
            $context->userAgent,
            $context->requestId,
            new DateTimeImmutable()
        ));

        $this->pdo->beginTransaction();
        try {
            $this->outboxWriter->write(new AuditEventDTO(
                $adminId,
                'stepup_denied',
                'grant',
                $adminId,
                'LOW',
                ['session_id' => $sessionId, 'required_scope' => $requiredScope->value],
                bin2hex(random_bytes(16)),
                $context->requestId,
                new DateTimeImmutable()
            ));
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function hasGrant(int $adminId, string $token, Scope $scope, RequestContext $context): bool
    {
        $sessionId = hash('sha256', $token);

        $grant = $this->grantRepository->find($adminId, $sessionId, $scope);
        if ($grant === null) {
            return false;
        }

        // Verify Risk Context
        if (!hash_equals($grant->riskContextHash, $this->getRiskHash($context))) {
            $this->logSecurityEvent($adminId, $sessionId, 'stepup_risk_mismatch', ['reason' => 'context_changed'], $context);
            // Invalidate strictly
            $this->pdo->beginTransaction();
            try {
                $this->grantRepository->revoke($adminId, $sessionId, $scope);
                $this->outboxWriter->write(new AuditEventDTO(
                    $adminId,
                    'stepup_revoked_risk',
                    'grant',
                    $adminId,
                    'HIGH',
                    ['session_id' => $sessionId, 'scope' => $scope->value],
                    bin2hex(random_bytes(16)),
                    $context->requestId,
                    new DateTimeImmutable()
                ));
                $this->pdo->commit();
            } catch (\Throwable $e) {
                $this->pdo->rollBack();
            }
            return false;
        }

        if ($grant->expiresAt < new DateTimeImmutable()) {
            return false;
        }

        // Check single use?
        if ($grant->singleUse) {
            $this->pdo->beginTransaction();
            try {
                // Consume grant
                $this->grantRepository->revoke($adminId, $sessionId, $scope);

                 $this->auditLogger->log(new LegacyAuditEventDTO(
                    $adminId,
                    'system',
                    $adminId,
                    'stepup_grant_consumed',
                    ['scope' => $scope->value],
                    $context->ipAddress,
                    $context->userAgent,
                    $context->requestId,
                    new DateTimeImmutable()
                ));

                $this->outboxWriter->write(new AuditEventDTO(
                    $adminId,
                    'stepup_grant_consumed',
                    'grant',
                    $adminId,
                    'MEDIUM',
                    ['session_id' => $sessionId, 'scope' => $scope->value],
                    bin2hex(random_bytes(16)),
                    $context->requestId,
                    new DateTimeImmutable()
                ));

                $this->pdo->commit();
            } catch (\Throwable $e) {
                $this->pdo->rollBack();
                return false;
            }
        }

        return true;
    }

    public function getSessionState(int $adminId, string $token, RequestContext $context): SessionState
    {
        $sessionId = hash('sha256', $token);

        // Check for Primary Grant (Scope::LOGIN)
        $primaryGrant = $this->grantRepository->find($adminId, $sessionId, Scope::LOGIN);

        if ($primaryGrant !== null && $primaryGrant->expiresAt > new DateTimeImmutable()) {
            // Verify Risk Context for Primary Grant too
            if (hash_equals($primaryGrant->riskContextHash, $this->getRiskHash($context))) {
                return SessionState::ACTIVE;
            }
        }

        return SessionState::PENDING_STEP_UP;
    }

    /**
     * @param array<string, scalar> $details
     */
    private function logSecurityEvent(int $adminId, string $sessionId, string $event, array $details, RequestContext $context): void
    {
        $details['session_id'] = $sessionId;
        $details['scope'] = Scope::LOGIN->value;
        $details['severity'] = 'error';

        /** @var array<string, scalar> $logContext */
        $logContext = $details;

        $this->auditLogger->log(new LegacyAuditEventDTO(
            $adminId,
            'security',
            $adminId,
            $event,
            $logContext,
            $context->ipAddress,
            $context->userAgent,
            $context->requestId,
            new DateTimeImmutable()
        ));
    }

    private function getRiskHash(RequestContext $context): string
    {
        $ip = $context->ipAddress;
        $ua = $context->userAgent;
        return hash('sha256', $ip . '|' . $ua);
    }
}
