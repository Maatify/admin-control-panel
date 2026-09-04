<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Permissions;

use JsonSerializable;

final readonly class PermissionRoleListItemDTO implements JsonSerializable
{
    public function __construct(
        private int $role_id,
        private string $role_name,
        private string $group,
        private ?string $display_name,
        private bool $is_active
    ) {}

    /**
     * @return array{
     *   role_id:int,
     *   role_name:string,
     *     group:string,
     *   display_name:string|null,
     *   is_active:bool
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'role_id'      => $this->role_id,
            'role_name'    => $this->role_name,
            'group'        => $this->group,
            'display_name' => $this->display_name,
            'is_active'    => $this->is_active,
        ];
    }
}
