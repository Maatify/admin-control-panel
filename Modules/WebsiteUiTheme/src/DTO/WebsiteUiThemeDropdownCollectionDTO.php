<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\DTO;

use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;

/** @implements IteratorAggregate<int, WebsiteUiThemeDropdownItemDTO> */
final readonly class WebsiteUiThemeDropdownCollectionDTO implements IteratorAggregate, JsonSerializable
{
    /** @param list<WebsiteUiThemeDropdownItemDTO> $items */
    public function __construct(public array $items) {}

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /** @return ArrayIterator<int, WebsiteUiThemeDropdownItemDTO> */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /** @return list<array{id:int,entity_type:string,theme_file:string,display_name:string}> */
    public function jsonSerialize(): array
    {
        return array_map(
            static fn (WebsiteUiThemeDropdownItemDTO $item): array => $item->jsonSerialize(),
            $this->items,
        );
    }
}
