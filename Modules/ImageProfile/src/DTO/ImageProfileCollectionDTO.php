<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\DTO;

use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;

/** @implements IteratorAggregate<int, ImageProfileDTO> */
final readonly class ImageProfileCollectionDTO implements IteratorAggregate, JsonSerializable
{
    /** @param list<ImageProfileDTO> $items */
    public function __construct(public array $items) {}

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /** @return ArrayIterator<int, ImageProfileDTO> */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /** @return list<array<string,mixed>> */
    public function jsonSerialize(): array
    {
        return array_map(
            static fn (ImageProfileDTO $item): array => $item->jsonSerialize(),
            $this->items,
        );
    }
}
