<?php

declare(strict_types=1);

namespace Maatify\AuditTrail\DTO;

use DateTimeImmutable;

readonly class AuditTrailQueryDTO
{
    public function __construct(
        public ?string $actorType = null,
        public ?int $actorId = null,
        public ?string $eventKey = null,
        public ?string $correlationId = null,
        public ?DateTimeImmutable $after = null,
        public ?DateTimeImmutable $before = null,
        public ?DateTimeImmutable $cursorOccurredAt = null,
        public ?int $cursorId = null,
        public int $limit = 50
    ) {
    }
}
