<?php

declare(strict_types=1);

namespace App\Modules\TelemetryLogging\Recorder;

use App\Modules\TelemetryLogging\Contract\TelemetryWriterInterface;
use App\Modules\TelemetryLogging\DTO\TelemetryContextDTO;
use App\Modules\TelemetryLogging\DTO\TelemetryEventDTO;
use App\Modules\TelemetryLogging\Enum\TelemetryLevelEnum;
use App\Modules\TelemetryLogging\Exception\TelemetryStorageException;
use App\Modules\TelemetryLogging\Services\ClockInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use JsonException;

class TelemetryRecorder
{
    private const ALLOWED_ACTOR_TYPES = [
        'SYSTEM',
        'ADMIN',
        'USER',
        'SERVICE',
        'API_CLIENT',
        'ANONYMOUS',
    ];

    private const MAX_METADATA_SIZE = 65536; // 64KB

    public function __construct(
        private readonly TelemetryWriterInterface $writer,
        private readonly ClockInterface $clock,
        private readonly ?LoggerInterface $fallbackLogger = null
    ) {
    }

    public function record(
        string $eventKey,
        TelemetryLevelEnum $severity,
        TelemetryContextDTO $context,
        ?int $durationMs = null,
        ?array $metadata = null
    ): void {
        // Validate Actor Type
        if (!in_array($context->actorType, self::ALLOWED_ACTOR_TYPES, true)) {
            // Depending on strictness, we might throw or correct it.
            // Design says "Any new value requires an explicit documented architectural decision."
            // "The Recorder MUST validate actor_type"
            // We'll throw an exception or handle it. Since this is "Best Effort", maybe log error to fallback and return?
            // But if the call is invalid, it's a developer error.
            // I'll throw an InvalidArgumentException.
            throw new \InvalidArgumentException("Invalid actor_type: {$context->actorType}");
        }

        // Validate Metadata Size
        if ($metadata !== null) {
            try {
                $json = json_encode($metadata, JSON_THROW_ON_ERROR);
                if (strlen($json) > self::MAX_METADATA_SIZE) {
                    // "Violations MUST result in: trimming ... or best-effort drop with PSR-3 warning"
                    if ($this->fallbackLogger) {
                        $this->fallbackLogger->warning('Telemetry metadata exceeded 64KB limit. Dropping metadata.', [
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

        $dto = new TelemetryEventDTO(
            eventId: Uuid::uuid4()->toString(),
            eventKey: $eventKey,
            severity: $severity,
            context: $context,
            durationMs: $durationMs,
            metadata: $metadata
        );

        try {
            $this->writer->write($dto);
        } catch (TelemetryStorageException $e) {
            // "Non-authoritative recorders MAY swallow storage exceptions"
            // "failure SHOULD be surfaced via Diagnostics Telemetry ... without creating recursive failures"
            // Since we ARE Telemetry, we rely on PSR-3 fallback.
            if ($this->fallbackLogger) {
                $this->fallbackLogger->error('Telemetry logging failed', [
                    'exception' => $e->getMessage(),
                    'event_key' => $eventKey,
                ]);
            }
            // Swallow exception
        }
    }
}
