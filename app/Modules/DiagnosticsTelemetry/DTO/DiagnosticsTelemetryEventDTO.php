<?php

declare(strict_types=1);

namespace App\Modules\DiagnosticsTelemetry\DTO;

use App\Modules\DiagnosticsTelemetry\Enum\DiagnosticsTelemetrySeverityInterface;

readonly class DiagnosticsTelemetryEventDTO
{
    /**
     * @param string $eventId UUID
     * @param string $eventKey
     * @param DiagnosticsTelemetrySeverityInterface $severity
     * @param DiagnosticsTelemetryContextDTO $context
     * @param int|null $durationMs
     * @param array<mixed>|null $metadata
     */
    public function __construct(
        public string $eventId,
        public string $eventKey,
        public DiagnosticsTelemetrySeverityInterface $severity,
        public DiagnosticsTelemetryContextDTO $context,
        public ?int $durationMs,
        public ?array $metadata
    ) {
    }
}
