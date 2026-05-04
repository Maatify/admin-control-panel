<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Rate\DTO;

/** @implements \IteratorAggregate<int, RateListItemDTO> */
final readonly class RateCollectionDTO implements \IteratorAggregate, \JsonSerializable
{
    /** @var list<RateListItemDTO> */
    private array $items;

    /** @param list<RateListItemDTO> $items */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /** @return \ArrayIterator<int, RateListItemDTO> */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @return list<RateListItemDTO>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
