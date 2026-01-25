<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Application\Contracts\DiagnosticsTelemetryRecorderInterface;
use Maatify\DiagnosticsTelemetry\Recorder\DiagnosticsTelemetryRecorder;

class DiagnosticsTelemetryMaatifyAdapter implements DiagnosticsTelemetryRecorderInterface
{
    public function __construct(
        private DiagnosticsTelemetryRecorder $recorder
    ) {
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function record(
        string $eventKey,
        string $severity,
        string $actorType,
        ?int $actorId = null,
        ?int $durationMs = null,
        ?array $metadata = null
    ): void {
        $this->recorder->record(
            eventKey: $eventKey,
            severity: $severity,
            actorType: $actorType,
            actorId: $actorId,
            correlationId: null,
            requestId: null,
            routeName: null,
            ipAddress: null,
            userAgent: null,
            durationMs: $durationMs,
            metadata: $metadata
        );
    }
}
