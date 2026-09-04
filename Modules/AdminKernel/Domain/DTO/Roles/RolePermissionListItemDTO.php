<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Roles;

use JsonSerializable;

readonly class RolePermissionListItemDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $group,
        public ?string $display_name,
        public ?string $description,
        public bool $assigned,
    ) {
    }

    /**
     * @return array{
     *   id:int,
     *   name:string,
     *   group:string|null,
     *   display_name:string|null,
     *   description:string|null,
     *   assigned:bool
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
        ];
    }
}