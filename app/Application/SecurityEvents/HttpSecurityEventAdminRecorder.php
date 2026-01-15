<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 12:16
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Application\SecurityEvents;

use App\Context\RequestContext;
use App\Domain\SecurityEvents\Enum\SecurityEventActorTypeEnum;
use App\Domain\SecurityEvents\Recorder\SecurityEventRecorderInterface;
use App\Domain\SecurityEvents\DTO\SecurityEventRecordDTO;
use App\Modules\SecurityEvents\Enum\SecurityEventSeverityEnum;
use App\Modules\SecurityEvents\Enum\SecurityEventTypeEnum;

/**
 * HTTP-layer Security Event Recorder.
 *
 * Responsibilities:
 * - Enrich domain SecurityEventRecordDTO with RequestContext data
 * - Delegate to Domain SecurityEventRecorder
 *
 * This class MAY:
 * - Depend on RequestContext
 *
 * This class MUST NOT:
 * - Contain persistence logic
 *
 * Admin-scoped HTTP security recorder.
 * Use only for admin-authenticated flows.
 */
final readonly class HttpSecurityEventAdminRecorder
{
    public function __construct(
        private SecurityEventRecorderInterface $recorder,
        private RequestContext $context
    )
    {
    }

    /**
     * Record a security event with automatic request context enrichment.
     *
     * @param   array<string, mixed>  $metadata
     */
    public function record(
        ?int $actorId,
        SecurityEventTypeEnum $eventType,
        SecurityEventSeverityEnum $severity,
        array $metadata = []
    ): void
    {
        $dto = new SecurityEventRecordDTO(
            actorType : SecurityEventActorTypeEnum::ADMIN,
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
