<?php

declare(strict_types=1);

namespace Maatify\AuditTrail\DTO;

use DateTimeImmutable;

readonly class AuditTrailRecordDTO
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $eventId,
        public string $actorType,
        public ?int $actorId,
        public string $eventKey,
        public string $entityType,
        public ?int $entityId,
        public ?string $subjectType,
        public ?int $subjectId,
        public ?string $referrerRouteName,
        public ?string $referrerPath,
        public ?string $referrerHost,
        public ?string $correlationId,
        public ?string $requestId,
        public ?string $routeName,
        public ?string $ipAddress,
        public ?string $userAgent,
        public array $metadata,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
