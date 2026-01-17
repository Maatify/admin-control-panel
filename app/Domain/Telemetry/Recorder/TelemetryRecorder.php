<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 13:10
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Telemetry\Recorder;

use App\Domain\Telemetry\DTO\TelemetryRecordDTO;
use App\Modules\Telemetry\Contracts\TelemetryLoggerInterface;
use App\Modules\Telemetry\DTO\TelemetryEventDTO;
use App\Modules\Telemetry\Exceptions\TelemetryStorageException;

/**
 * Telemetry Recorder (Observability ONLY)
 *
 * HARD RULES:
 * - Telemetry MUST NOT be used for:
 *   - Audit logging
 *   - Security events
 *   - Activity logging
 *   - Authority decisions
 *
 * - Telemetry is BEST-EFFORT and NON-AUTHORITATIVE.
 * - Telemetry failures MUST NEVER affect application flow.
 *
 * This class intentionally swallows TelemetryStorageException.
 */

final readonly class TelemetryRecorder implements TelemetryRecorderInterface
{
    public function __construct(
        private TelemetryLoggerInterface $logger
    )
    {
    }

    public function record(TelemetryRecordDTO $dto): void
    {
        try {
            $moduleDto = new TelemetryEventDTO(
                actorType : $dto->actorType->value,
                actorId   : $dto->actorId,

                eventType : $dto->eventType,
                severity  : $dto->severity,

                requestId : $dto->requestId,
                routeName : $dto->routeName,

                ipAddress : $dto->ipAddress,
                userAgent : $dto->userAgent,

                metadata  : $dto->metadata,
                occurredAt: new \DateTimeImmutable('now')
            );

            $this->logger->log($moduleDto);
        } catch (TelemetryStorageException) {
            // Best-effort: swallow.
            // No PSR-3 logging here (diagnostics may be added later in an approved phase).
            return;
        }
    }
}
