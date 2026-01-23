<?php

declare(strict_types=1);

namespace Maatify\SecuritySignals\DTO;

use DateTimeImmutable;

readonly class SecuritySignalRecordDTO
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $eventId,
        public string $actorType,
        public ?int $actorId,
        public string $signalType,
        public string $severity,
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
