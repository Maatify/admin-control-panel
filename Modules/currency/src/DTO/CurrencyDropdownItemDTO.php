<?php

declare(strict_types=1);

namespace Maatify\Currency\DTO;

use JsonSerializable;

final readonly class CurrencyDropdownItemDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public string $symbol,
        public int $isActive,
    ) {}

    /**
     * @return array{id: int, name: string, symbol: string, is_active: int}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'is_active' => $this->isActive,
        ];
    }
}
