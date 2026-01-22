<?php

declare(strict_types=1);

namespace App\Modules\DiagnosticsTelemetry\Recorder;

use App\Modules\DiagnosticsTelemetry\Contract\DiagnosticsTelemetryPolicyInterface;
use App\Modules\DiagnosticsTelemetry\Contract\DiagnosticsTelemetryLoggerInterface;
use App\Modules\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryContextDTO;
use App\Modules\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryEventDTO;
use App\Modules\DiagnosticsTelemetry\Enum\DiagnosticsTelemetryActorTypeInterface;
use App\Modules\DiagnosticsTelemetry\Enum\DiagnosticsTelemetrySeverityInterface;
use App\Modules\DiagnosticsTelemetry\Exception\DiagnosticsTelemetryStorageException;
use App\Modules\DiagnosticsTelemetry\Services\ClockInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use JsonException;

class DiagnosticsTelemetryRecorder
{
    private readonly DiagnosticsTelemetryPolicyInterface $policy;

    public function __construct(
        private readonly DiagnosticsTelemetryLoggerInterface $writer,
        private readonly ClockInterface $clock,
        private readonly ?LoggerInterface $fallbackLogger = null,
        ?DiagnosticsTelemetryPolicyInterface $policy = null
    ) {
        $this->policy = $policy ?? new DiagnosticsTelemetryDefaultPolicy();
    }

    /**
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
        // Normalize Severity via Policy
        $normalizedSeverity = $this->policy->normalizeSeverity($severity);

        // Normalize Actor Type via Policy
        $normalizedActorType = $this->policy->normalizeActorType($actorType);

        // Validate Metadata Size and Encoding
        if ($metadata !== null) {
            try {
                $json = json_encode($metadata, JSON_THROW_ON_ERROR);
                if (!$this->policy->validateMetadataSize($json)) {
                    if ($this->fallbackLogger) {
                        $this->fallbackLogger->warning('Telemetry metadata exceeded limit. Dropping metadata.', [
                            'event_key' => $eventKey,
                            'size' => strlen($json)
                        ]);
                    }
                    $metadata = ['error' => 'Metadata dropped due to size limit'];
                }
            } catch (JsonException $e) {
                 if ($this->fallbackLogger) {
                        $this->fallbackLogger->warning('Telemetry metadata JSON encoding failed.', [
                            'event_key' => $eventKey,
                            'error' => $e->getMessage()
                        ]);
                    }
                 $metadata = ['error' => 'Metadata dropped due to encoding error'];
            }
        }

        // Construct Context DTO
        $context = new DiagnosticsTelemetryContextDTO(
            actorType: $normalizedActorType,
            actorId: $actorId,
            correlationId: $correlationId,
            requestId: $requestId,
            routeName: $routeName,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            occurredAt: $this->clock->now()
        );

        // Construct Event DTO
        $dto = new DiagnosticsTelemetryEventDTO(
            eventId: Uuid::uuid4()->toString(),
            eventKey: $eventKey,
            severity: $normalizedSeverity,
            context: $context,
            durationMs: $durationMs,
            metadata: $metadata
        );

        try {
            $this->writer->write($dto);
        } catch (DiagnosticsTelemetryStorageException $e) {
            // Best-effort: swallow exception but log to fallback
            if ($this->fallbackLogger) {
                $this->fallbackLogger->error('Telemetry logging failed', [
                    'exception' => $e->getMessage(),
                    'event_key' => $eventKey,
                ]);
            }
        }
    }
}
