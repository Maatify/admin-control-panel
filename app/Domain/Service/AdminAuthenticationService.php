<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Domain\Contracts\AuditLoggerInterface;
use App\Domain\Contracts\ClientInfoProviderInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\DTO\SecurityEventDTO;
use App\Domain\Enum\VerificationStatus;
use App\Domain\Exception\AuthStateException;
use App\Domain\Exception\InvalidCredentialsException;
use DateTimeImmutable;

readonly class AdminAuthenticationService
{
    public function __construct(
        private AdminIdentifierLookupInterface $lookupRepository,
        private AdminEmailVerificationRepositoryInterface $verificationRepository,
        private AdminPasswordRepositoryInterface $passwordRepository,
        private AdminSessionRepositoryInterface $sessionRepository,
        private AuditLoggerInterface $auditLogger,
        private SecurityEventLoggerInterface $securityLogger,
        private ClientInfoProviderInterface $clientInfoProvider
    ) {
    }

    public function login(string $blindIndex, string $password): string
    {
        // 1. Look up Admin ID by Blind Index
        $adminId = $this->lookupRepository->findByBlindIndex($blindIndex);
        if ($adminId === null) {
            $this->securityLogger->log(new SecurityEventDTO(
                null,
                'login_failed',
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
                ['reason' => 'invalid_password'],
                $this->clientInfoProvider->getIpAddress(),
                $this->clientInfoProvider->getUserAgent(),
                new DateTimeImmutable()
            ));
            throw new InvalidCredentialsException("Invalid credentials.");
        }

        // 4. Create Session
        $token = $this->sessionRepository->createSession($adminId);

        $this->auditLogger->log(new AuditEventDTO(
            $adminId, // Actor
            'admin', // Target Type
            $adminId, // Target ID
            'login', // Action
            [], // Changes
            $this->clientInfoProvider->getIpAddress(),
            $this->clientInfoProvider->getUserAgent(),
            new DateTimeImmutable()
        ));

        return $token;
    }
}
