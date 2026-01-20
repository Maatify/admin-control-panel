<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Context\RequestContext;
use App\Domain\Contracts\StepUpGrantRepositoryInterface;
use App\Domain\Contracts\AdminTotpSecretStoreInterface;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\DTO\StepUpGrant;
use App\Domain\DTO\TotpVerificationResultDTO;
use App\Domain\Enum\Scope;
use App\Domain\Enum\SessionState;
use App\Domain\SecurityEvents\DTO\SecurityEventRecordDTO;
use App\Domain\SecurityEvents\Enum\SecurityEventActorTypeEnum;
use App\Domain\SecurityEvents\Recorder\SecurityEventRecorderInterface;
use App\Modules\SecurityEvents\Enum\SecurityEventSeverityEnum;
use App\Modules\SecurityEvents\Enum\SecurityEventTypeEnum;
use DateTimeImmutable;
use PDO;

readonly class StepUpService
{
    public function __construct(
        private StepUpGrantRepositoryInterface $grantRepository,
        private AdminTotpSecretStoreInterface $totpSecretStore,
        private TotpServiceInterface $totpService,
        private AuthoritativeSecurityAuditWriterInterface $outboxWriter,
        private SecurityEventRecorderInterface $securityEventRecorder,
        private RecoveryStateService $recoveryState,
        private PDO $pdo
    ) {
    }

    public function verifyTotp(
        int $adminId,
        string $token,
        string $code,
        RequestContext $context,
        ?Scope $requestedScope = null
    ): TotpVerificationResultDTO {
        $this->recoveryState->enforce(RecoveryStateService::ACTION_OTP_VERIFY, $adminId, $context);

        $sessionId = hash('sha256', $token);

        $secret = $this->totpSecretStore->retrieve($adminId);
        if ($secret === null) {
            $this->securityEventRecorder->record(
                new SecurityEventRecordDTO(
                    actorType: SecurityEventActorTypeEnum::ADMIN,
                    actorId: $adminId,
                    eventType: SecurityEventTypeEnum::STEP_UP_NOT_ENROLLED,
                    severity: SecurityEventSeverityEnum::ERROR,
                    requestId: $context->requestId,
                    routeName: $context->routeName ?? null,
                    ipAddress: $context->ipAddress,
                    userAgent: $context->userAgent,
                    metadata: [
                        'reason' => 'no_totp_enrolled',
                        'session_id' => $sessionId,
                        'scope' => Scope::LOGIN->value,
                    ]
                )
            );

            return new TotpVerificationResultDTO(false, 'TOTP not enrolled');
        }

        if (!$this->totpService->verify($secret, $code)) {
            $this->securityEventRecorder->record(
                new SecurityEventRecordDTO(
                    actorType: SecurityEventActorTypeEnum::ADMIN,
                    actorId: $adminId,

                    eventType: SecurityEventTypeEnum::STEP_UP_INVALID_CODE,
                    severity: SecurityEventSeverityEnum::ERROR,

                    requestId: $context->requestId,
                    routeName: $context->routeName,
                    ipAddress: $context->ipAddress,
                    userAgent: $context->userAgent,

                    metadata: [
                        'session_id' => $sessionId,
                        'scope' => Scope::LOGIN->value,
                    ]
                )
            );

            return new TotpVerificationResultDTO(false, 'Invalid code');
        }

        $this->pdo->beginTransaction();
        try {
            if ($requestedScope !== null && $requestedScope !== Scope::LOGIN) {
                $this->issueScopedGrant($adminId, $token, $requestedScope, $context);
            } else {
                $this->issuePrimaryGrant($adminId, $token, $context);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return new TotpVerificationResultDTO(true);
    }

    public function enableTotp(
        int $adminId,
        string $token,
        string $secret,
        string $code,
        RequestContext $context
    ): bool {
        $this->recoveryState->enforce(RecoveryStateService::ACTION_OTP_VERIFY, $adminId, $context);

        $sessionId = hash('sha256', $token);

        if (!$this->totpService->verify($secret, $code)) {
            $this->securityEventRecorder->record(
                new SecurityEventRecordDTO(
                    actorType: SecurityEventActorTypeEnum::ADMIN,
                    actorId: $adminId,

                    eventType: SecurityEventTypeEnum::STEP_UP_ENROLL_FAILED,
                    severity: SecurityEventSeverityEnum::ERROR,

                    requestId: $context->requestId,
                    routeName: $context->routeName,
                    ipAddress: $context->ipAddress,
                    userAgent: $context->userAgent,

                    metadata: [
                        'session_id' => $sessionId,
                        'scope' => Scope::LOGIN->value,
                        'reason' => 'invalid_code',
                    ]
                )
            );

            return false;
        }

        $this->pdo->beginTransaction();
        try {
            $this->totpSecretStore->store($adminId, $secret);

            $this->issuePrimaryGrant($adminId, $token, $context);

            // TODO[AUDIT][NOTE]:
            // audit_outbox
            // stepup_enrolled was previously double-written:
            // 1) Authoritative audit (outbox)
            // 2) TelemetryAuditLogger -> audit_logs
            // Telemetry audit must be removed permanently.
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
            Scope::LOGIN,
            $this->getRiskHash($context),
            new DateTimeImmutable(),
            new DateTimeImmutable('+2 hours'),
            false
        );

        $this->grantRepository->save($grant);

        // TODO[AUDIT][NOTE]:
        // audit_outbox
        // stepup_primary_issued was previously double-written
        // (authoritative outbox + telemetry audit).
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
            $this->getScopeTtl($scope),
            false
        );

        $this->grantRepository->save($grant);

        // TODO[AUDIT][NOTE]:
        // audit_outbox
        // stepup_scoped_issued was previously double-written
        // (authoritative outbox + telemetry audit).
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

        // TODO[AUDIT][BLOCKER]:
        // audit_outbox
        // stepup_denied was previously logged via TelemetryAuditLogger.
        // This MUST use Authoritative Audit only.
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

        if (!hash_equals($grant->riskContextHash, $this->getRiskHash($context))) {
            $this->securityEventRecorder->record(
                new SecurityEventRecordDTO(
                    actorType: SecurityEventActorTypeEnum::ADMIN,
                    actorId: $adminId,

                    eventType: SecurityEventTypeEnum::STEP_UP_RISK_MISMATCH,
                    severity: SecurityEventSeverityEnum::CRITICAL,

                    requestId: $context->requestId,
                    routeName: $context->routeName,
                    ipAddress: $context->ipAddress,
                    userAgent: $context->userAgent,

                    metadata: [
                        'session_id' => $sessionId,
                        'scope' => $scope->value,
                        'expected_risk' => $grant->riskContextHash,
                        'actual_risk' => $this->getRiskHash($context),
                    ]
                )
            );

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

        if ($grant->singleUse) {
            $this->pdo->beginTransaction();
            try {
                $this->grantRepository->revoke($adminId, $sessionId, $scope);

                // TODO[AUDIT][NOTE]:
                // audit_outbox
                // stepup_grant_consumed was previously double-written
                // (authoritative outbox + telemetry audit).
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

        $primaryGrant = $this->grantRepository->find($adminId, $sessionId, Scope::LOGIN);

        if ($primaryGrant !== null && $primaryGrant->expiresAt > new DateTimeImmutable()) {
            if (hash_equals($primaryGrant->riskContextHash, $this->getRiskHash($context))) {
                return SessionState::ACTIVE;
            }
        }

        return SessionState::PENDING_STEP_UP;
    }

    private function getRiskHash(RequestContext $context): string
    {
        return hash('sha256', $context->ipAddress . '|' . $context->userAgent);
    }

    private function getScopeTtl(Scope $scope): DateTimeImmutable
    {
        return match ($scope) {
            Scope::ADMIN_CREATE => new DateTimeImmutable('+5 minutes'),
            default => new DateTimeImmutable('+15 minutes'),
        };
    }
}
