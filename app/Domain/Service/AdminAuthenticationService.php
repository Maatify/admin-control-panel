<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Domain\Contracts\TelemetryAuditLoggerInterface;
use App\Domain\Contracts\ClientInfoProviderInterface;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
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
        private PDO $pdo
    ) {
    }

    public function login(string $blindIndex, string $password): string
    {
        $this->recoveryState->enforce(RecoveryStateService::ACTION_LOGIN);

        // 1. Look up Admin ID by Blind Index
        $adminId = $this->lookupRepository->findByBlindIndex($blindIndex);
        if ($adminId === null) {
            $this->securityLogger->log(new SecurityEventDTO(
                null,
                'login_failed',
                'warning',
                ['reason' => 'user_not_found', 'blind_index' => $blindIndex],
                $this->clientInfoProvider->getIpAddress(),
                $this->clientInfoProvider->getUserAgent(),
                new DateTimeImmutable()
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
                $this->clientInfoProvider->getIpAddress(),
                $this->clientInfoProvider->getUserAgent(),
                new DateTimeImmutable()
            ));
            throw new AuthStateException("Identifier is not verified.");
        }

        // 3. Verify Password
        $hash = $this->passwordRepository->getPasswordHash($adminId);
        if ($hash === null || !password_verify($password, $hash)) {
            $this->securityLogger->log(new SecurityEventDTO(
                $adminId,
                'login_failed',
                'warning',
                ['reason' => 'invalid_password'],
                $this->clientInfoProvider->getIpAddress(),
                $this->clientInfoProvider->getUserAgent(),
                new DateTimeImmutable()
            ));
            throw new InvalidCredentialsException("Invalid credentials.");
        }

        // 4. Create Session (Transactional)
        $this->pdo->beginTransaction();
        try {
            $token = $this->sessionRepository->createSession($adminId);

            $this->auditLogger->log(new LegacyAuditEventDTO(
                $adminId, // Actor
                'admin', // Target Type
                $adminId, // Target ID
                'login_credentials_verified', // Action
                [], // Changes
                $this->clientInfoProvider->getIpAddress(),
                $this->clientInfoProvider->getUserAgent(),
                new DateTimeImmutable()
            ));

            $this->outboxWriter->write(new AuditEventDTO(
                $adminId,
                'login_credentials_verified',
                'admin',
                $adminId,
                'LOW',
                [
                    'ip_address' => $this->clientInfoProvider->getIpAddress(),
                    'user_agent' => $this->clientInfoProvider->getUserAgent(),
                ],
                bin2hex(random_bytes(16)),
                new DateTimeImmutable()
            ));

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $token;
    }

    public function logoutSession(int $adminId, string $token): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->sessionRepository->revokeSession($token);

            $this->outboxWriter->write(new AuditEventDTO(
                $adminId,
                'session_revoked',
                'admin',
                $adminId,
                'LOW',
                [
                    'session_token_prefix' => substr($token, 0, 8) . '...',
                    'ip_address' => $this->clientInfoProvider->getIpAddress(),
                    'user_agent' => $this->clientInfoProvider->getUserAgent(),
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
