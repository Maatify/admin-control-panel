<?php

declare(strict_types=1);

namespace Maatify\WebsiteUiTheme\DTO;

use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;

/** @implements IteratorAggregate<int, WebsiteUiThemeDTO> */
final readonly class WebsiteUiThemeCollectionDTO implements IteratorAggregate, JsonSerializable
{
    /** @param list<WebsiteUiThemeDTO> $items */
    public function __construct(public array $items) {}

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /** @return ArrayIterator<int, WebsiteUiThemeDTO> */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /** @return list<array{id:int,entity_type:string,theme_file:string,display_name:string}> */
    public function jsonSerialize(): array
    {
        return array_map(
            static fn (WebsiteUiThemeDTO $item): array => $item->jsonSerialize(),
            $this->items,
        );
    }
}
