<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\RateHistory\DTO;

/** @implements \IteratorAggregate<int, RateHistoryListItemDTO> */
final readonly class RateHistoryCollectionDTO implements \IteratorAggregate, \JsonSerializable
{
    /** @var list<RateHistoryListItemDTO> */
    private array $items;

    /** @param list<RateHistoryListItemDTO> $items */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /** @return \ArrayIterator<int, RateHistoryListItemDTO> */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @return list<RateHistoryListItemDTO>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
