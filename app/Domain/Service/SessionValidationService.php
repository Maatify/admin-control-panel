<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Context\RequestContext;
use App\Domain\Exception\ExpiredSessionException;
use App\Domain\SecurityEvents\DTO\SecurityEventRecordDTO;
use App\Domain\SecurityEvents\Enum\SecurityEventActorTypeEnum;
use App\Domain\SecurityEvents\Recorder\SecurityEventRecorderInterface;
use App\Modules\SecurityEvents\Enum\SecurityEventSeverityEnum;
use App\Modules\SecurityEvents\Enum\SecurityEventTypeEnum;
use App\Domain\Exception\InvalidSessionException;
use App\Domain\Exception\RevokedSessionException;
use DateTimeImmutable;
use Exception;

class SessionValidationService
{
    private AdminSessionValidationRepositoryInterface $repository;
    private SecurityEventRecorderInterface $securityLogger;

    public function __construct(
        AdminSessionValidationRepositoryInterface $repository,
        SecurityEventRecorderInterface $securityLogger
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
            $this->securityLogger->record(new SecurityEventRecordDTO(
                SecurityEventActorTypeEnum::ANONYMOUS,
                null,
                SecurityEventTypeEnum::SESSION_INVALID,
                SecurityEventSeverityEnum::WARNING,
                $context->requestId,
                null,
                $context->ipAddress,
                $context->userAgent,
                ['reason' => 'invalid_token']
            ));
            throw new InvalidSessionException('Session not found or invalid.');
        }

        if ($session['is_revoked'] === 1) {
            $this->securityLogger->record(new SecurityEventRecordDTO(
                SecurityEventActorTypeEnum::ADMIN,
                $session['admin_id'],
                SecurityEventTypeEnum::SESSION_INVALID,
                SecurityEventSeverityEnum::WARNING,
                $context->requestId,
                null,
                $context->ipAddress,
                $context->userAgent,
                ['reason' => 'revoked']
            ));
            throw new RevokedSessionException('Session has been revoked.');
        }

        $expiresAt = new DateTimeImmutable($session['expires_at']);
        if ($expiresAt < new DateTimeImmutable()) {
            $this->securityLogger->record(new SecurityEventRecordDTO(
                SecurityEventActorTypeEnum::ADMIN,
                $session['admin_id'],
                SecurityEventTypeEnum::SESSION_EXPIRED,
                SecurityEventSeverityEnum::WARNING,
                $context->requestId,
                null,
                $context->ipAddress,
                $context->userAgent
            ));
            throw new ExpiredSessionException('Session has expired.');
        }

        return $session['admin_id'];
    }
}
