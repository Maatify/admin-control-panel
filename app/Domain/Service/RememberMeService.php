<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Domain\Contracts\ClientInfoProviderInterface;
use App\Domain\Contracts\RememberMeRepositoryInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\DTO\RememberMeTokenDTO;
use App\Domain\DTO\SecurityEventDTO;
use App\Domain\Exception\IdentifierNotFoundException;
use App\Domain\Exception\InvalidCredentialsException;
use DateTimeImmutable;
use Random\RandomException;

class RememberMeService
{
    private const TOKEN_EXPIRY_DAYS = 30;

    public function __construct(
        private RememberMeRepositoryInterface $rememberMeRepository,
        private AdminSessionRepositoryInterface $sessionRepository,
        private SecurityEventLoggerInterface $securityEventLogger,
        private ClientInfoProviderInterface $clientInfoProvider
    ) {
    }

    /**
     * Issues a new Remember-Me token for an admin.
     *
     * @param int $adminId
     * @return string The raw token in format "selector:validator"
     */
    public function issue(int $adminId): string
    {
        try {
            $selector = bin2hex(random_bytes(16)); // 32 chars
            $validator = bin2hex(random_bytes(32)); // 64 chars
        } catch (RandomException $e) {
            throw new \RuntimeException('Could not generate random bytes.', 0, $e);
        }

        $hashedValidator = hash('sha256', $validator);
        $userAgentHash = hash('sha256', $this->clientInfoProvider->getUserAgent() ?? '');
        $expiresAt = (new DateTimeImmutable())->modify('+' . self::TOKEN_EXPIRY_DAYS . ' days');

        $tokenDto = new RememberMeTokenDTO(
            $selector,
            $hashedValidator,
            $adminId,
            $expiresAt,
            $userAgentHash
        );

        $this->rememberMeRepository->save($tokenDto);

        $this->logEvent($adminId, 'remember_me_issued');

        return $selector . ':' . $validator;
    }

    /**
     * Processes an auto-login attempt using a Remember-Me token.
     *
     * @param string $rawToken The raw token from cookie.
     * @return array{session_token: string, remember_me_token: string}
     * @throws InvalidCredentialsException If token is invalid.
     */
    public function processAutoLogin(string $rawToken): array
    {
        $parts = explode(':', $rawToken);
        if (count($parts) !== 2) {
            throw new InvalidCredentialsException('Invalid remember-me token format.');
        }

        [$selector, $validator] = $parts;

        $tokenDto = $this->rememberMeRepository->findBySelector($selector);

        if ($tokenDto === null) {
            throw new InvalidCredentialsException('Remember-me token not found.');
        }

        // Validate Validator
        if (!hash_equals($tokenDto->hashedValidator, hash('sha256', $validator))) {
            $this->rememberMeRepository->deleteBySelector($selector); // Theft assumption
            $this->logEvent($tokenDto->adminId, 'remember_me_theft_suspected', ['selector' => $selector]);
            throw new InvalidCredentialsException('Invalid remember-me validator.');
        }

        // Validate Expiry
        if ($tokenDto->expiresAt < new DateTimeImmutable()) {
            $this->rememberMeRepository->deleteBySelector($selector);
            throw new InvalidCredentialsException('Remember-me token expired.');
        }

        // Rotate Token (Issue new one, delete old one)
        $this->rememberMeRepository->deleteBySelector($selector);
        $newRememberMeToken = $this->issue($tokenDto->adminId);

        // Create new session
        $sessionToken = $this->sessionRepository->createSession($tokenDto->adminId);

        $this->logEvent($tokenDto->adminId, 'remember_me_rotated');

        return [
            'session_token' => $sessionToken,
            'remember_me_token' => $newRememberMeToken
        ];
    }


    public function revoke(int $adminId): void
    {
        $this->rememberMeRepository->deleteByAdminId($adminId);
        $this->logEvent($adminId, 'remember_me_revoked');
    }

    public function revokeBySelector(string $selector): void
    {
        $tokenDto = $this->rememberMeRepository->findBySelector($selector);
        if ($tokenDto !== null) {
            $this->rememberMeRepository->deleteBySelector($selector);
            $this->logEvent($tokenDto->adminId, 'remember_me_revoked', ['selector' => $selector]);
        }
    }

    private function logEvent(int $adminId, string $eventName, array $context = []): void
    {
        $this->securityEventLogger->log(new SecurityEventDTO(
            $adminId,
            $eventName,
            'info',
            $context,
            $this->clientInfoProvider->getIpAddress(),
            $this->clientInfoProvider->getUserAgent(),
            new DateTimeImmutable()
        ));
    }
}
