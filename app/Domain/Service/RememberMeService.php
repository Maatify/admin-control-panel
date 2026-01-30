<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Context\RequestContext;
use App\Domain\Contracts\ClockInterface;
use App\Domain\Contracts\RememberMeRepositoryInterface;
use App\Domain\DTO\RememberMeTokenDTO;
use App\Domain\Exception\InvalidCredentialsException;
use DateTimeImmutable;
use PDO;
use Random\RandomException;

class RememberMeService
{
    private const TOKEN_EXPIRY_DAYS = 30;

    public function __construct(
        private RememberMeRepositoryInterface $rememberMeRepository,
        private AdminSessionRepositoryInterface $sessionRepository,
        private PDO $pdo,
        private ClockInterface $clock
    ) {
    }

    /**
     * Issues a new Remember-Me token for an admin.
     *
     * @param int $adminId
     * @return string The raw token in format "selector:validator"
     */
    public function issue(int $adminId, RequestContext $context): string
    {
        try {
            $selector = bin2hex(random_bytes(16)); // 32 chars
            $validator = bin2hex(random_bytes(32)); // 64 chars
        } catch (RandomException $e) {
            throw new \RuntimeException('Could not generate random bytes.', 0, $e);
        }

        $hashedValidator = hash('sha256', $validator);
        $userAgentHash = hash('sha256', $context->userAgent);
        $expiresAt = $this->clock->now()->modify('+' . self::TOKEN_EXPIRY_DAYS . ' days');

        $tokenDto = new RememberMeTokenDTO(
            $selector,
            $hashedValidator,
            $adminId,
            $expiresAt,
            $userAgentHash
        );

        $this->pdo->beginTransaction();
        try {
            $this->rememberMeRepository->save($tokenDto);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $selector . ':' . $validator;
    }

    /**
     * Processes an auto-login attempt using a Remember-Me token.
     *
     * @param string $rawToken The raw token from cookie.
     * @return array{session_token: string, remember_me_token: string}
     * @throws InvalidCredentialsException If token is invalid.
     */
    public function processAutoLogin(string $rawToken, RequestContext $context): array
    {
        $parts = explode(':', $rawToken);
        if (count($parts) !== 2) {
            throw new InvalidCredentialsException('Invalid remember-me token format.');
        }

        [$selector, $validator] = $parts;

        $this->pdo->beginTransaction();
        try {
            $tokenDto = $this->rememberMeRepository->findBySelector($selector);

            if ($tokenDto === null) {
                // Not found - just throw, rollback effectively does nothing read-only
                throw new InvalidCredentialsException('Remember-me token not found.');
            }

            // Validate Validator
            if (!hash_equals($tokenDto->hashedValidator, hash('sha256', $validator))) {
                // Suspected theft: Delete this selector immediately
                $this->rememberMeRepository->deleteBySelector($selector);

                $this->pdo->commit();
                throw new InvalidCredentialsException('Invalid remember-me validator.');
            }

            // Validate Expiry
            if ($tokenDto->expiresAt < $this->clock->now()) {
                $this->rememberMeRepository->deleteBySelector($selector);
                $this->pdo->commit();
                throw new InvalidCredentialsException('Remember-me token expired.');
            }

            // Rotate: Delete old
            $this->rememberMeRepository->deleteBySelector($selector);

            // Generate new token details inline to allow single transaction
            try {
                $newSelector = bin2hex(random_bytes(16));
                $newValidator = bin2hex(random_bytes(32));
            } catch (RandomException $e) {
                throw new \RuntimeException('Random failure', 0, $e);
            }
            $newHashedValidator = hash('sha256', $newValidator);
            $newUserAgentHash = hash('sha256', $context->userAgent);
            $expiresAt = $this->clock->now()->modify('+' . self::TOKEN_EXPIRY_DAYS . ' days');

            $newTokenDto = new RememberMeTokenDTO(
                $newSelector,
                $newHashedValidator,
                $tokenDto->adminId,
                $expiresAt,
                $newUserAgentHash
            );
            $this->rememberMeRepository->save($newTokenDto);

            // Create Session
            $sessionToken = $this->sessionRepository->createSession($tokenDto->adminId);

            $this->pdo->commit();

            return [
                'session_token' => $sessionToken,
                'remember_me_token' => $newSelector . ':' . $newValidator
            ];

        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }


    public function revoke(int $adminId, RequestContext $context): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->rememberMeRepository->deleteByAdminId($adminId);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function revokeBySelector(string $selector, RequestContext $context): void
    {
        $this->pdo->beginTransaction();
        try {
            $tokenDto = $this->rememberMeRepository->findBySelector($selector);
            if ($tokenDto !== null) {
                $this->rememberMeRepository->deleteBySelector($selector);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
