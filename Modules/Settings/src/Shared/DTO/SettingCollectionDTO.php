<?php

declare(strict_types=1);

namespace Maatify\Settings\Shared\DTO;

/** @implements \IteratorAggregate<int, SettingListItemDTO> */
final readonly class SettingCollectionDTO implements \IteratorAggregate, \JsonSerializable
{
    /** @var list<SettingListItemDTO> */
    private array $items;

    /** @param list<SettingListItemDTO> $items */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /** @return \ArrayIterator<int, SettingListItemDTO> */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function jsonSerialize(): mixed
    {
        return $this->items;
    }
}
