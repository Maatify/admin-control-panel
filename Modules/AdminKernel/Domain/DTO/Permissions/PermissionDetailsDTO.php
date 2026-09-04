<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Permissions;

use JsonSerializable;

final readonly class PermissionDetailsDTO implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $name,
        private string $group,
        private ?string $display_name,
        private ?string $description,
        private string $created_at
    ) {}

    /**
     * @return array{
     *   id:int,
     *   name:string,
     *   group:string,
     *   display_name:string|null,
     *   description:string|null,
     *   created_at:string
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
            'created_at'   => $this->created_at,
        ];
    }
}
