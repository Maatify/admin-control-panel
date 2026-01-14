<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Domain\Contracts\TelemetryAuditLoggerInterface;
use App\Context\RequestContext;
use App\Domain\Contracts\ClientInfoProviderInterface;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\DTO\AdminLoginResultDTO;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\DTO\LegacyAuditEventDTO;
use App\Domain\DTO\SecurityEventDTO;
use App\Domain\Enum\VerificationStatus;
use App\Domain\Exception\AuthStateException;
use App\Domain\Exception\InvalidCredentialsException;
use DateTimeImmutable;
use PDO;

readonly class AdminAuthenticationService
{
    public function __construct(
        private AdminIdentifierLookupInterface $lookupRepository,
        private AdminEmailVerificationRepositoryInterface $verificationRepository,
        private AdminPasswordRepositoryInterface $passwordRepository,
        private AdminSessionRepositoryInterface $sessionRepository,
        private TelemetryAuditLoggerInterface $auditLogger,
        private SecurityEventLoggerInterface $securityLogger,
        private ClientInfoProviderInterface $clientInfoProvider,
        private AuthoritativeSecurityAuditWriterInterface $outboxWriter,
        private RecoveryStateService $recoveryState,
        private PDO $pdo,
        private PasswordService $passwordService
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
            // Defensive: Do not reveal user existence
            throw new InvalidCredentialsException("Invalid credentials.");
        }

        // 2. Check Verification Status
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
            throw new AuthStateException("Identifier is not verified.");
        }

        // 3. Verify Password
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

        // 4. Transactional Login (Upgrade + Session)
        $this->pdo->beginTransaction();
        try {
            // 4.1 Upgrade-on-Login (Rehash if pepper changed OR Argon2 params changed)
            if ($this->passwordService->needsRehash($record->hash, $record->pepperId)) {
                $newHash = $this->passwordService->hash($password);
                $this->passwordRepository->savePassword($adminId, $newHash['hash'], $newHash['pepper_id']);
            }

            // 4.2 Create Session
            $token = $this->sessionRepository->createSession($adminId);
            // Hash token to get Session ID (for audit logging)
            $sessionId = hash('sha256', $token);

            $this->auditLogger->log(new LegacyAuditEventDTO(
                $adminId, // Actor
                'admin', // Target Type
                $adminId, // Target ID
                'login_credentials_verified', // Action
                [], // Changes
                $context->ipAddress,
                $context->userAgent,
                $context->requestId,
                new DateTimeImmutable()
            ));

            $this->outboxWriter->write(new AuditEventDTO(
                $adminId,
                'login_credentials_verified',
                'admin',
                $adminId,
                'LOW',
                [
                    'ip_address' => $context->ipAddress,
                    'user_agent' => $context->userAgent,
                    // Log the Session ID prefix (Safe), NOT the Token prefix
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
                    // Log the Session ID prefix (Safe), NOT the Token prefix
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
