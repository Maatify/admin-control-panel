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

final readonly class TelemetryTraceReadQueryDTO
{
    public function __construct(
        public ?string $eventKey,
        public ?string $severity,
        public ?string $routeName,
        public ?string $requestId,
        public ?int $actorAdminId,
        public ?\DateTimeImmutable $occurredFrom,
        public ?\DateTimeImmutable $occurredTo,
        public int $page,
        public int $perPage,
    )
    {
    }
}
