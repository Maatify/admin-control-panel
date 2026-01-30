<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Context\RequestContext;
use App\Domain\Contracts\ClockInterface;
use App\Domain\Exception\ExpiredSessionException;
use App\Domain\Exception\InvalidSessionException;
use App\Domain\Exception\RevokedSessionException;
use DateTimeImmutable;
use Exception;

class SessionValidationService
{
    private AdminSessionValidationRepositoryInterface $repository;

    public function __construct(
        AdminSessionValidationRepositoryInterface $repository,
        private ClockInterface $clock
    ) {
        $this->repository = $repository;
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
            throw new InvalidSessionException('Session not found or invalid.');
        }

        if ($session['is_revoked'] === 1) {
            throw new RevokedSessionException('Session has been revoked.');
        }

        $expiresAt = new DateTimeImmutable($session['expires_at'], $this->clock->getTimezone());
        if ($expiresAt < $this->clock->now()) {
            throw new ExpiredSessionException('Session has expired.');
        }

        return $session['admin_id'];
    }
}
