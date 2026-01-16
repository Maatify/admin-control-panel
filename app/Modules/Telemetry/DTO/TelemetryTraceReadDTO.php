<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-16 11:33
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Telemetry\DTO;

final readonly class TelemetryTraceReadDTO
{
    /**
     * @param   array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $id,
        public string $eventKey,
        public string $severity,
        public ?string $routeName,
        public ?string $requestId,
        public ?int $actorAdminId,
        public ?string $ipAddress,
        public ?string $userAgent,
        public ?array $metadata,
        public \DateTimeImmutable $occurredAt,
    )
    {
    }
}
