<?php

declare(strict_types=1);

namespace App\Domain\Telemetry\Recorder;

use App\Domain\Telemetry\DTO\TelemetryRecordDTO;
use Maatify\DiagnosticsTelemetry\Recorder\DiagnosticsTelemetryRecorder;

final readonly class CanonicalTelemetryRecorder implements TelemetryRecorderInterface
{
    public function __construct(
        private DiagnosticsTelemetryRecorder $recorder
    ) {
    }

    public function record(TelemetryRecordDTO $dto): void
    {
        $this->recorder->record(
            eventKey: $dto->eventType->value,
            severity: $dto->severity->value,
            actorType: $dto->actorType->value,
            actorId: $dto->actorId,
            metadata: $dto->metadata,
            correlationId: null,
            requestId: $dto->requestId,
            routeName: $dto->routeName,
            ipAddress: $dto->ipAddress,
            userAgent: $dto->userAgent
        );
    }
}
