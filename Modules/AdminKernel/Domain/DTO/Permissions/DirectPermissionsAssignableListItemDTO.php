<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Permissions;

use JsonSerializable;

final readonly class DirectPermissionsAssignableListItemDTO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $name,
        private string $group,
        private ?string $display_name,
        private ?string $description,

        /**
         * Whether this permission currently has a direct assignment
         */
        private bool $assigned,

        /**
         * Direct decision if assigned
         * true  => allowed
         * false => denied
         * null  => not assigned
         */
        private ?bool $is_allowed,

        /**
         * Expiry timestamp (only if assigned)
         */
        private ?string $expires_at
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @phpstan-return array{
     *   id:int,
     *   name:string,
     *   group:string,
     *   display_name:string|null,
     *   description:string|null,
     *   assigned:bool,
     *   is_allowed:bool|null,
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
            'assigned'     => $this->assigned,
            'is_allowed'   => $this->is_allowed,
            'expires_at'   => $this->expires_at,
        ];
    }
}
