<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-26 20:21
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\DTO\Roles;

use JsonSerializable;

class RolesListItemDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public string $group,
        public ?string $display_name,
        public ?string $description,
        public int $is_active,
    )
    {
    }

    /**
     * @return array{id: int, name: string, group: string, display_name: string|null, description: string|null, is_active: int}
     */
    public function jsonSerialize(): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'group'        => $this->group,
            'display_name' => $this->display_name,
            'description'  => $this->description,
            'is_active' => $this->is_active,
        ];
    }
}
