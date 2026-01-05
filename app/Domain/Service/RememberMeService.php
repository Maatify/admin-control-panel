<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Domain\Contracts\AuditLoggerInterface;
use App\Domain\Contracts\ClientInfoProviderInterface;
use App\Domain\Contracts\RememberMeRepositoryInterface;
use App\Domain\DTO\AuditEventDTO;
use DateTimeImmutable;
use Exception;

readonly class RememberMeService
{
    private const SELECTOR_LENGTH = 12; // 24 hex chars
    private const VALIDATOR_LENGTH = 32; // 64 hex chars
    private const EXPIRATION_DAYS = 30;

    public function __construct(
        private RememberMeRepositoryInterface $repository,
        private AdminSessionRepositoryInterface $sessionRepository,
        private AuditLoggerInterface $auditLogger,
        private ClientInfoProviderInterface $clientInfoProvider
    ) {
    }

    /**
     * Issues a new Remember-Me token for an admin.
     * Returns the cookie value (selector:validator).
     */
    public function issue(int $adminId): string
    {
        $selector = bin2hex(random_bytes(self::SELECTOR_LENGTH));
        $validator = bin2hex(random_bytes(self::VALIDATOR_LENGTH));
        $hashedValidator = hash('sha256', $validator);

        $userAgent = $this->clientInfoProvider->getUserAgent() ?? 'unknown';
        $userAgentHash = hash('sha256', $userAgent);

        $expiresAt = (new DateTimeImmutable())->modify('+' . self::EXPIRATION_DAYS . ' days');

        $this->repository->save($adminId, $selector, $hashedValidator, $userAgentHash, $expiresAt);

        $this->auditLogger->log(new AuditEventDTO(
            $adminId,
            'admin',
            $adminId,
            'remember_me_issued',
            ['expires_at' => $expiresAt->format('Y-m-d H:i:s')],
            $this->clientInfoProvider->getIpAddress(),
            $userAgent,
            new DateTimeImmutable()
        ));

        return $selector . ':' . $validator;
    }

    /**
     * Processes an auto-login attempt using the cookie value.
     * On success, rotates the token and returns [newSessionToken, newCookieValue, adminId].
     * On failure, returns null.
     *
     * @param string $cookieValue
     * @return array{session_token: string, cookie_value: string, admin_id: int}|null
     */
    public function processAutoLogin(string $cookieValue): ?array
    {
        $parts = explode(':', $cookieValue);
        if (count($parts) !== 2) {
            return null;
        }

        [$selector, $validator] = $parts;

        $record = $this->repository->findBySelector($selector);
        if ($record === null) {
            return null;
        }

        // Validate Validator
        if (!hash_equals($record['hashed_validator'], hash('sha256', $validator))) {
            // Potential theft: Selector valid, validator invalid. Revoke!
            $this->repository->deleteBySelector($selector);
            $this->logFailure($record['admin_id'], 'invalid_validator');
            return null;
        }

        // Validate User Agent
        $currentUserAgent = $this->clientInfoProvider->getUserAgent() ?? 'unknown';
        if (!hash_equals($record['user_agent_hash'], hash('sha256', $currentUserAgent))) {
            $this->repository->deleteBySelector($selector);
            $this->logFailure($record['admin_id'], 'user_agent_mismatch');
            return null;
        }

        // Validate Expiration
        if (new DateTimeImmutable($record['expires_at']) < new DateTimeImmutable()) {
            $this->repository->deleteBySelector($selector);
            return null; // Expired, just fail silently/redirect to login
        }

        // Success: Rotate Token
        $this->repository->deleteBySelector($selector); // Revoke old

        // Mint Session
        $sessionToken = $this->sessionRepository->createSession($record['admin_id']);

        // Issue New Remember-Me Token
        $newCookieValue = $this->issue($record['admin_id']);

        $this->auditLogger->log(new AuditEventDTO(
            $record['admin_id'],
            'admin',
            $record['admin_id'],
            'remember_me_rotated',
            [],
            $this->clientInfoProvider->getIpAddress(),
            $currentUserAgent,
            new DateTimeImmutable()
        ));

        return [
            'session_token' => $sessionToken,
            'cookie_value' => $newCookieValue,
            'admin_id' => $record['admin_id']
        ];
    }

    public function revoke(string $cookieValue): void
    {
        $parts = explode(':', $cookieValue);
        if (count($parts) === 2) {
            $selector = $parts[0];
            $record = $this->repository->findBySelector($selector);

            if ($record !== null) {
                $this->auditLogger->log(new AuditEventDTO(
                    $record['admin_id'],
                    'admin',
                    $record['admin_id'],
                    'remember_me_revoked',
                    [],
                    $this->clientInfoProvider->getIpAddress(),
                    $this->clientInfoProvider->getUserAgent(),
                    new DateTimeImmutable()
                ));
                $this->repository->deleteBySelector($selector);
            }
        }
    }

    private function logFailure(int $adminId, string $reason): void
    {
        $this->auditLogger->log(new AuditEventDTO(
            $adminId,
            'admin',
            $adminId,
            'remember_me_failed',
            ['reason' => $reason],
            $this->clientInfoProvider->getIpAddress(),
            $this->clientInfoProvider->getUserAgent(),
            new DateTimeImmutable()
        ));
    }
}
