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

namespace App\Domain\Telemetry\DTO;

use App\Domain\Telemetry\Enum\TelemetryActorTypeEnum;
use App\Modules\Telemetry\Enum\TelemetryEventTypeEnum;
use App\Modules\Telemetry\Enum\TelemetrySeverityEnum;

/**
 * Domain-level telemetry intent DTO.
 *
 * RULES:
 * - No RequestContext dependency
 * - No storage concerns
 *
 * @psalm-type TelemetryMetadata = array<string, mixed>
 */
final readonly class TelemetryRecordDTO
{
    /**
     * @param   TelemetryMetadata  $metadata
     */
    public function __construct(
        public TelemetryActorTypeEnum $actorType,
        public ?int $actorId,

        public TelemetryEventTypeEnum $eventType,
        public TelemetrySeverityEnum $severity,

        public ?string $requestId = null,
        public ?string $routeName = null,

        public ?string $ipAddress = null,
        public ?string $userAgent = null,

        public array $metadata = [],
    )
    {
    }
}
