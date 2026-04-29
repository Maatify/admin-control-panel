<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Customer\Rate\DTO;

/** @implements \IteratorAggregate<int, CustomerRateDTO> */
final readonly class CustomerRateCollectionDTO implements \IteratorAggregate, \JsonSerializable
{
    /** @var list<CustomerRateDTO> */
    private array $items;

    /** @param list<CustomerRateDTO> $items */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /** @return \ArrayIterator<int, CustomerRateDTO> */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @return list<CustomerRateDTO>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
