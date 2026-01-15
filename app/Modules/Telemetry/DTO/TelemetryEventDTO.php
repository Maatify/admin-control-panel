<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 13:07
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Telemetry\DTO;

use App\Modules\Telemetry\Enum\TelemetryEventTypeEnum;
use App\Modules\Telemetry\Enum\TelemetrySeverityEnum;
use DateTimeImmutable;

/**
 * Module-level Telemetry DTO (DB-aligned).
 *
 * RULES:
 * - No RequestContext dependency.
 * - No domain/business logic.
 * - Must mirror telemetry_traces schema exactly.
 *
 * @psalm-type TelemetryMetadata = array<string, mixed>
 */
final readonly class TelemetryEventDTO
{
    /**
     * @param   TelemetryMetadata  $metadata
     */
    public function __construct(
        public string $actorType,
        public ?int $actorId,

        public TelemetryEventTypeEnum $eventType,
        public TelemetrySeverityEnum $severity,

        public ?string $requestId,
        public ?string $routeName,

        public ?string $ipAddress,
        public ?string $userAgent,

        public array $metadata,

        public DateTimeImmutable $occurredAt,
    )
    {
    }
}
