<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 10:41
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\SecurityEvents\DTO;

use App\Domain\SecurityEvents\Enum\SecurityEventActorTypeEnum;
use App\Modules\SecurityEvents\Enum\SecurityEventSeverityEnum;
use App\Modules\SecurityEvents\Enum\SecurityEventTypeEnum;

/**
 * Domain-level DTO used to record a security event.
 *
 * Notes:
 * - This DTO is RequestContext-agnostic.
 * - Request-related data is OPTIONAL and must be provided explicitly by the caller.
 * - No infrastructure or HTTP dependencies are allowed here.
 */
final readonly class SecurityEventRecordDTO
{
    /**
     * @param   array<string, mixed>  $metadata
     */
    public function __construct(
        public SecurityEventActorTypeEnum $actorType,
        public ?int $actorId,

        public SecurityEventTypeEnum $eventType,
        public SecurityEventSeverityEnum $severity,

        // Optional request correlation data
        public ?string $requestId = null,
        public ?string $routeName = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,

        // Pure business / security metadata
        public array $metadata = []
    ) {
    }
}
