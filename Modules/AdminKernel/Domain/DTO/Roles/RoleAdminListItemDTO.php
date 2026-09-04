<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Roles;

use JsonSerializable;

readonly class RoleAdminListItemDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public ?string $display_name,
        public string $status,
        public bool $assigned,
    ) {}

    /**
     * @return array{
     *   id:int,
     *   display_name:string|null,
     *   status:string,
     *   assigned:bool
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id'           => $this->id,
            'display_name' => $this->display_name,
            'status'       => $this->status,
            'assigned'     => $this->assigned,
        ];
    }
}

