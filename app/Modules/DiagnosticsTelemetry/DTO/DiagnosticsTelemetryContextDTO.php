<?php

declare(strict_types=1);

namespace App\Modules\DiagnosticsTelemetry\DTO;

use App\Modules\DiagnosticsTelemetry\Enum\DiagnosticsTelemetryActorTypeInterface;
use DateTimeImmutable;

readonly class DiagnosticsTelemetryContextDTO
{
    public function __construct(
        public DiagnosticsTelemetryActorTypeInterface $actorType,
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
