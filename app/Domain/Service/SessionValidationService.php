<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Context\RequestContext;
use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\DTO\SecurityEventDTO;
use App\Domain\Exception\ExpiredSessionException;
use App\Domain\Exception\InvalidSessionException;
use App\Domain\Exception\RevokedSessionException;
use DateTimeImmutable;
use Exception;

class SessionValidationService
{
    private AdminSessionValidationRepositoryInterface $repository;
    private SecurityEventLoggerInterface $securityLogger;

    public function __construct(
        AdminSessionValidationRepositoryInterface $repository,
        SecurityEventLoggerInterface $securityLogger
    ) {
        $this->repository = $repository;
        $this->securityLogger = $securityLogger;
    }

    /**
     * @throws InvalidSessionException
     * @throws ExpiredSessionException
     * @throws RevokedSessionException
     * @throws Exception
     */
    public function validate(string $token, RequestContext $context): int
    {
        $session = $this->repository->findSession($token);

        if ($session === null) {
            $this->securityLogger->log(new SecurityEventDTO(
                null,
                'session_validation_failed',
                'warning',
                ['reason' => 'invalid_token'],
                $context->ipAddress,
                $context->userAgent,
                new DateTimeImmutable(),
                $context->requestId
            ));
            throw new InvalidSessionException('Session not found or invalid.');
        }

        if ($session['is_revoked'] === 1) {
            $this->securityLogger->log(new SecurityEventDTO(
                $session['admin_id'],
                'session_validation_failed',
                'warning',
                ['reason' => 'revoked'],
                $context->ipAddress,
                $context->userAgent,
                new DateTimeImmutable(),
                $context->requestId
            ));
            throw new RevokedSessionException('Session has been revoked.');
        }

        $expiresAt = new DateTimeImmutable($session['expires_at']);
        if ($expiresAt < new DateTimeImmutable()) {
            $this->securityLogger->log(new SecurityEventDTO(
                $session['admin_id'],
                'session_validation_failed',
                'warning',
                ['reason' => 'expired'],
                $context->ipAddress,
                $context->userAgent,
                new DateTimeImmutable(),
                $context->requestId
            ));
            throw new ExpiredSessionException('Session has expired.');
        }

        return $session['admin_id'];
    }
}
