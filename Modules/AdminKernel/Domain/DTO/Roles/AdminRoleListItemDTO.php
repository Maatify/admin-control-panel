<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Roles;

use JsonSerializable;

final readonly class AdminRoleListItemDTO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $name,
        private string $group,
        private ?string $display_name,
        private ?string $description,
        private bool $is_active
    ) {
    }

    /**
     * @return array{
     *   id:int,
     *   name:string,
     *     group:string,
     *   display_name:string|null,
     *   description:string|null,
     *   is_active:bool
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
            'is_active'    => $this->is_active,
        ];
    }
}
