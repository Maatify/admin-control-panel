<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Provider\DTO;

/** Full detail DTO — used in findById and admin edit screens. */
final readonly class ProviderDTO implements \JsonSerializable
{
    public function __construct(
        public int     $id,
        public string  $name,
        public string  $code,
        public ?string $description,
        public bool    $isActive,
        public int     $displayOrder,
        public string  $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     code: string,
     *     description: string|null,
     *     is_active: bool,
     *     display_order: int,
     *     created_at: string,
     *     updated_at: string|null,
     *     deleted_at: string|null,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'code'          => $this->code,
            'description'   => $this->description,
            'is_active'     => $this->isActive,
            'display_order' => $this->displayOrder,
            'created_at'    => $this->createdAt,
            'updated_at'    => $this->updatedAt,
            'deleted_at'    => $this->deletedAt,
        ];
    }
}
