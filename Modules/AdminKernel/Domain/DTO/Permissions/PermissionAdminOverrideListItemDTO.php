<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Permissions;

use JsonSerializable;

final readonly class PermissionAdminOverrideListItemDTO implements JsonSerializable
{
    public function __construct(
        private int $admin_id,
        private string $admin_display_name,
        private bool $is_allowed,
        private ?string $expires_at,
        private string $granted_at
    ) {}

    /**
     * @return array{
     *   admin_id:int,
     *   admin_display_name:string,
     *   is_allowed:bool,
     *   expires_at:string|null,
     *   granted_at:string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'admin_id'   => $this->admin_id,
            'admin_display_name' => $this->admin_display_name,
            'is_allowed' => $this->is_allowed,
            'expires_at' => $this->expires_at,
            'granted_at' => $this->granted_at,
        ];
    }
}
