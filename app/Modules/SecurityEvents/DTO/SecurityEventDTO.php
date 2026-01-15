<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 09:28
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\SecurityEvents\DTO;

use App\Modules\SecurityEvents\Enum\SecurityEventTypeEnum;
use App\Modules\SecurityEvents\Enum\SecurityEventSeverityEnum;

/**
 * @note
 * Immutable Data Transfer Object representing a single security event.
 *
 * This DTO is framework-agnostic and MUST NOT contain
 * any HTTP or infrastructure-specific dependencies.
 */
final readonly class SecurityEventDTO
{
    public function __construct(

        /**
         * Actor identifier related to the event.
         * Null when the actor is anonymous or unknown.
         */
        public string $actorType,
        public ?int $actorId,

        /**
         * Type of the security event (e.g. LOGIN_FAILED, STEP_UP_FAILED).
         */
        public SecurityEventTypeEnum $eventType,

        /**
         * Severity level of the event.
         */
        public SecurityEventSeverityEnum $severity,

        /**
         * Correlation identifier for the request lifecycle.
         */
        public ?string $requestId,

        /**
         * Client IP address, if available.
         */
        public ?string $ipAddress,

        /**
         * Client User-Agent string, if available.
         */
        public ?string $userAgent,

        /**
         * Logical route or permission name, if available.
         */
        public ?string $routeName,

        /**
         * Arbitrary structured metadata related to the event.
         * Must be JSON-serializable.
         *
         * @var array<string, mixed>
         */
        public array $metadata = [],

        /**
         * Event occurrence timestamp (UTC or system default).
         * If null, the infrastructure layer may set it.
         */
        public ?\DateTimeImmutable $occurredAt = null,
    )
    {
    }
}
