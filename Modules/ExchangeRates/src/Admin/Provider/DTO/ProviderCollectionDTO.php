<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Provider\DTO;

/** @implements \IteratorAggregate<int, ProviderListItemDTO> */
final readonly class ProviderCollectionDTO implements \IteratorAggregate, \JsonSerializable
{
    /** @var list<ProviderListItemDTO> */
    private array $items;

    /** @param list<ProviderListItemDTO> $items */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /** @return \ArrayIterator<int, ProviderListItemDTO> */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @return list<ProviderListItemDTO>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
