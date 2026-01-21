<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Admin\Enum\AdminStatusEnum;
use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Context\RequestContext;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\DTO\AdminLoginResultDTO;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\DTO\SecurityEventDTO;
use App\Domain\Enum\VerificationStatus;
use App\Domain\Exception\AuthStateException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Exception\MustChangePasswordException;
use App\Infrastructure\Repository\AdminRepository;
use DateTimeImmutable;
use PDO;

readonly class AdminAuthenticationService
{
    public function __construct(
        private AdminIdentifierLookupInterface $lookupRepository,
        private AdminEmailVerificationRepositoryInterface $verificationRepository,
        private AdminPasswordRepositoryInterface $passwordRepository,
        private AdminSessionRepositoryInterface $sessionRepository,

        private SecurityEventLoggerInterface $securityLogger,
        private AuthoritativeSecurityAuditWriterInterface $outboxWriter,
        private RecoveryStateService $recoveryState,
        private PDO $pdo,
        private PasswordService $passwordService,
        private AdminRepository $adminRepository,
    ) {
    }

    public function login(string $blindIndex, string $password, RequestContext $context): AdminLoginResultDTO
    {
        $this->recoveryState->enforce(RecoveryStateService::ACTION_LOGIN, null, $context);

        // 1. Look up Admin ID by Blind Index
        $adminId = $this->lookupRepository->findByBlindIndex($blindIndex);
        if ($adminId === null) {
            $this->securityLogger->log(new SecurityEventDTO(
                null,
                'login_failed',
                'warning',
                ['reason' => 'user_not_found', 'blind_index' => $blindIndex],
                $context->ipAddress,
                $context->userAgent,
                new DateTimeImmutable(),
                $context->requestId
            ));
            throw new InvalidCredentialsException("Invalid credentials.");
        }

        // 2. Verify Password
        $record = $this->passwordRepository->getPasswordRecord($adminId);
        if ($record === null || !$this->passwordService->verify($password, $record->hash, $record->pepperId)) {
            $this->securityLogger->log(new SecurityEventDTO(
                $adminId,
                'login_failed',
                'warning',
                ['reason' => 'invalid_password'],
                $context->ipAddress,
                $context->userAgent,
                new DateTimeImmutable(),
                $context->requestId
            ));
            throw new InvalidCredentialsException("Invalid credentials.");
        }

        // 🔒 3 Enforce Admin Lifecycle Status
        $status = $this->adminRepository->getStatus($adminId);
        if (! $status->canAuthenticate()) {

            $this->securityLogger->log(new SecurityEventDTO(
                $adminId,
                'login_blocked',
                'warning',
                ['reason' => 'admin_' . strtolower($status->name)],
                $context->ipAddress,
                $context->userAgent,
                new DateTimeImmutable(),
                $context->requestId
            ));

            throw new AuthStateException(
                match ($status) {
                    AdminStatusEnum::SUSPENDED =>
                    AuthStateException::REASON_SUSPENDED,

                    AdminStatusEnum::DISABLED =>
                    AuthStateException::REASON_DISABLED,

                    default =>
                    AuthStateException::REASON_DISABLED, // fail-closed
                },
                match ($status) {
                    AdminStatusEnum::SUSPENDED =>
                    'Your account is temporarily suspended. Please contact the system administrator.',

                    AdminStatusEnum::DISABLED =>
                    'Your account has been disabled and is no longer active.',

                    default =>
                    'Authentication failed.',
                }
            );

        }

        // 4. Check Verification Status
        $status = $this->verificationRepository->getVerificationStatus($adminId);
        if ($status !== VerificationStatus::VERIFIED) {
            $this->securityLogger->log(new SecurityEventDTO(
                $adminId,
                'login_failed',
                'warning',
                ['reason' => 'not_verified'],
                $context->ipAddress,
                $context->userAgent,
                new DateTimeImmutable(),
                $context->requestId
            ));
            throw new AuthStateException(
                AuthStateException::REASON_NOT_VERIFIED,
                'Identifier is not verified.'
            );
        }

        // 🔒 5. Enforce Must-Change-Password
        if ($record->mustChangePassword) {
            throw new MustChangePasswordException('Password change required.');
        }

        // 6. Transactional Login (Upgrade + Session)
        $this->pdo->beginTransaction();
        try {
            // 6.1 Upgrade-on-Login
            if ($this->passwordService->needsRehash($record->hash, $record->pepperId)) {
                $newHash = $this->passwordService->hash($password);
                $this->passwordRepository->savePassword(
                    $adminId,
                    $newHash['hash'],
                    $newHash['pepper_id'],
                    false // 🔒 explicit: this is NOT a temporary password
                );
            }

            // 6.2 Create Session
            $token = $this->sessionRepository->createSession($adminId);
            $sessionId = hash('sha256', $token);

            // TODO[AUDIT][NOTE]:
            // audit_outbox
            // login_credentials_verified was previously double-written:
            // 1) TelemetryAuditLogger -> audit_logs (non-authoritative, best-effort)
            // 2) Authoritative audit outbox (correct source of truth)
            // Telemetry audit MUST NOT be reintroduced.
            $this->outboxWriter->write(new AuditEventDTO(
                $adminId,
                'login_credentials_verified',
                'admin',
                $adminId,
                'LOW',
                [
                    'ip_address' => $context->ipAddress,
                    'user_agent' => $context->userAgent,
                    'session_id_prefix' => substr($sessionId, 0, 8) . '...',
                ],
                bin2hex(random_bytes(16)),
                $context->requestId,
                new DateTimeImmutable()
            ));

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return new AdminLoginResultDTO(
            adminId: $adminId,
            token: $token
        );
    }

    public function logoutSession(int $adminId, string $token, RequestContext $context): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->sessionRepository->revokeSession($token);
            $sessionId = hash('sha256', $token);

            $this->outboxWriter->write(new AuditEventDTO(
                $adminId,
                'session_revoked',
                'admin',
                $adminId,
                'LOW',
                [
                    'session_id_prefix' => substr($sessionId, 0, 8) . '...',
                    'ip_address' => $context->ipAddress,
                    'user_agent' => $context->userAgent,
                ],
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
}
