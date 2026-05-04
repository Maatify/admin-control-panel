<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Provider\DTO;

/** Lightweight DTO for paginated list rows. */
final readonly class ProviderListItemDTO implements \JsonSerializable
{
    public function __construct(
        public int    $id,
        public string $name,
        public string $code,
        public bool   $isActive,
        public int    $displayOrder,
        public string $createdAt,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     code: string,
     *     is_active: bool,
     *     display_order: int,
     *     created_at: string,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'code'          => $this->code,
            'is_active'     => $this->isActive,
            'display_order' => $this->displayOrder,
            'created_at'    => $this->createdAt,
        ];
    }
}
