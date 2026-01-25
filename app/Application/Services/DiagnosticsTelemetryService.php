<?php

declare(strict_types=1);

namespace App\Application\Services;

use Maatify\DiagnosticsTelemetry\Enum\DiagnosticsTelemetryActorTypeInterface;
use Maatify\DiagnosticsTelemetry\Enum\DiagnosticsTelemetrySeverityInterface;
use Maatify\DiagnosticsTelemetry\Recorder\DiagnosticsTelemetryRecorder;
use Psr\Log\LoggerInterface;

class DiagnosticsTelemetryService
{
    public function __construct(
        private readonly DiagnosticsTelemetryRecorder $recorder,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Records a diagnostic telemetry event.
     *
     * This method acts as a project-facing wrapper for the DiagnosticsTelemetryRecorder.
     * It enforces Fail-Open behavior (Best Effort), meaning exceptions during recording
     * are suppressed (logged to fallback) and will NOT crash the application.
     *
     * @param string $eventKey
     * @param DiagnosticsTelemetrySeverityInterface|string $severity
     * @param DiagnosticsTelemetryActorTypeInterface|string $actorType
     * @param int|null $actorId
     * @param string|null $correlationId
     * @param string|null $requestId
     * @param string|null $routeName
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @param int|null $durationMs
     * @param array<mixed>|null $metadata
     */
    public function record(
        string $eventKey,
        DiagnosticsTelemetrySeverityInterface|string $severity,
        DiagnosticsTelemetryActorTypeInterface|string $actorType,
        ?int $actorId = null,
        ?string $correlationId = null,
        ?string $requestId = null,
        ?string $routeName = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?int $durationMs = null,
        ?array $metadata = null
    ): void {
        try {
            $this->recorder->record(
                $eventKey,
                $severity,
                $actorType,
                $actorId,
                $correlationId,
                $requestId,
                $routeName,
                $ipAddress,
                $userAgent,
                $durationMs,
                $metadata
            );
        } catch (\Throwable $e) {
            // Fail-open: suppress all exceptions to prevent application crash
            $this->logger->error('DiagnosticsTelemetryService: Failed to record event', [
                'exception' => $e,
                'event_key' => $eventKey,
            ]);
        }
    }
}
