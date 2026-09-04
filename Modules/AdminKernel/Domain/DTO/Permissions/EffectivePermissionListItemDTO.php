<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Permissions;

use JsonSerializable;

final readonly class EffectivePermissionListItemDTO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $name,
        private string $group,
        private ?string $display_name,
        private ?string $description,

        /**
         * Source of permission decision:
         * - role
         * - direct_allow
         * - direct_deny
         */
        private string $source,

        /**
         * Role technical name if source = role
         */
        private ?string $role_name,

        /**
         * Final RBAC decision
         */
        private bool $is_allowed,

        /**
         * Expiry for direct permissions only
         */
        private ?string $expires_at
    ) {
    }

    /**
     * @return array{
     *   id:int,
     *   name:string,
     *   group:string,
     *   display_name:string|null,
     *   description:string|null,
     *   source:string,
     *   role_name:string|null,
     *   is_allowed:bool,
     *   expires_at:string|null
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'group'        => $this->group,
            'display_name' => $this->display_name,
            'description'  => $this->description,
            'source'       => $this->source,
            'role_name'    => $this->role_name,
            'is_allowed'   => $this->is_allowed,
            'expires_at'   => $this->expires_at,
        ];
    }
}
