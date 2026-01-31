<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-11 22:25
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\ActivityLog;

use JsonSerializable;

final class ActivityLogListItemDTO implements JsonSerializable
{
    /**
     * @param   array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $id,

        public string $action,

        public string $actor_type,
        public ?int $actor_id,

        public ?string $entity_type,
        public ?int $entity_id,

        public ?array $metadata,

        public ?string $ip_address,
        public ?string $user_agent,

        public ?string $request_id,

        public string $occurred_at
    )
    {
    }

    /**
     * @return array{
     *   id: int,
     *   action: string,
     *   actor_type: string,
     *   actor_id: int|null,
     *   entity_type: string|null,
     *   entity_id: int|null,
     *   metadata: array<string,mixed>|null,
     *   ip_address: string|null,
     *   user_agent: string|null,
     *   request_id: string|null,
     *   occurred_at: string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id'     => $this->id,
            'action' => $this->action,

            'actor_type' => $this->actor_type,
            'actor_id'   => $this->actor_id,

            'entity_type' => $this->entity_type,
            'entity_id'   => $this->entity_id,

            'metadata' => $this->metadata,

            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,

            'request_id' => $this->request_id,

            'occurred_at' => $this->occurred_at,
        ];
    }
}
