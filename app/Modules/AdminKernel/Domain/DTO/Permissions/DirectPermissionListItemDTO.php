<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Permissions;

use JsonSerializable;

final readonly class DirectPermissionListItemDTO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $name,
        private string $group,
        private ?string $display_name,
        private ?string $description,

        /**
         * Direct decision only
         * true  => allowed
         * false => denied
         */
        private bool $is_allowed,

        /**
         * Expiry timestamp (nullable)
         */
        private ?string $expires_at,

        /**
         * Grant timestamp
         */
        private string $granted_at
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @phpstan-return array{
     *   id:int,
     *   name:string,
     *   group:string,
     *   display_name:string|null,
     *   description:string|null,
     *   is_allowed:bool,
     *   expires_at:string|null,
     *   granted_at:string
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
            'is_allowed'   => $this->is_allowed,
            'expires_at'   => $this->expires_at,
            'granted_at'   => $this->granted_at,
        ];
    }
}
