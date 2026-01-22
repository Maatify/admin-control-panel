<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-11 19:53
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\ActivityLog\DTO;

use DateTimeImmutable;

final readonly class ActivityLogDTO
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public string $action,

        public string $actorType,
        public ?int $actorId,

        public ?string $entityType,
        public ?int $entityId,

        public ?array $metadata,

        public ?string $ipAddress,
        public ?string $userAgent,

        public ?string $correlationId,
        public ?string $requestId,
        public ?string $routeName,

        public DateTimeImmutable $occurredAt,
    )
    {
    }
}
