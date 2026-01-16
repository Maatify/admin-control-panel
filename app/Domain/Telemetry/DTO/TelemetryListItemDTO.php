<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-16 13:46
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Telemetry\DTO;

final readonly class TelemetryListItemDTO
{
    public function __construct(
        public int $id,
        public string $event_key,
        public string $severity,
        public string $actor_type,
        public ?int $actor_id,
        public ?string $route_name,
        public ?string $request_id,
        public ?string $ip_address,
        public string $occurred_at,
        public bool $has_metadata
    )
    {
    }
}
