<?php

declare(strict_types=1);

namespace Maatify\Currency\DTO;

use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;

/**
 * @phpstan-type CurrencyDropdownItemList list<CurrencyDropdownItemDTO>
 * @implements IteratorAggregate<int, CurrencyDropdownItemDTO>
 */
final readonly class CurrencyDropdownCollectionDTO implements IteratorAggregate, JsonSerializable
{
    /**
     * @param list<CurrencyDropdownItemDTO> $items
     */
    public function __construct(
        public array $items,
    ) {}

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * @return ArrayIterator<int, CurrencyDropdownItemDTO>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return list<array{id: int, name: string, symbol: string, is_active: int}>
     */
    public function jsonSerialize(): array
    {
        return array_map(
            static fn(CurrencyDropdownItemDTO $item): array => $item->jsonSerialize(),
            $this->items
        );
    }
}
