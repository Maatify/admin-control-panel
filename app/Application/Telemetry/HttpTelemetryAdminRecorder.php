<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 13:11
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Application\Telemetry;

use App\Context\RequestContext;
use App\Domain\Telemetry\DTO\TelemetryRecordDTO;
use App\Domain\Telemetry\Enum\TelemetryActorTypeEnum;
use App\Domain\Telemetry\Recorder\TelemetryRecorderInterface;
use App\Modules\Telemetry\Enum\TelemetryEventTypeEnum;
use App\Modules\Telemetry\Enum\TelemetrySeverityEnum;

/**
 * HTTP-layer Telemetry Recorder.
 *
 * Responsibilities:
 * - Enrich domain TelemetryRecordDTO with RequestContext data
 * - Delegate to Domain TelemetryRecorder
 *
 * This class MAY:
 * - Depend on RequestContext
 *
 * This class MUST NOT:
 * - Contain persistence logic
 */
final readonly class HttpTelemetryAdminRecorder
{
    public function __construct(
        private TelemetryRecorderInterface $recorder,
        private RequestContext $context
    )
    {
    }

    /**
     * @param   array<string, mixed>  $metadata
     */
    public function record(
        ?int $actorId,
        TelemetryEventTypeEnum $eventType,
        TelemetrySeverityEnum $severity,
        array $metadata = []
    ): void
    {
        $dto = new TelemetryRecordDTO(
            actorType: TelemetryActorTypeEnum::ADMIN,
            actorId  : $actorId,

            eventType: $eventType,
            severity : $severity,

            requestId: $this->context->requestId,
            routeName: $this->context->routeName,
            ipAddress: $this->context->ipAddress,
            userAgent: $this->context->userAgent,

            metadata : $metadata
        );

        $this->recorder->record($dto);
    }
}
