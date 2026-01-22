<?php

declare(strict_types=1);

namespace App\Modules\TelemetryLogging\DTO;

use DateTimeImmutable;

readonly class TelemetryContextDTO
{
    public function __construct(
        public string $actorType,
        public ?int $actorId,
        public ?string $correlationId,
        public ?string $requestId,
        public ?string $routeName,
        public ?string $ipAddress,
        public ?string $userAgent,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
