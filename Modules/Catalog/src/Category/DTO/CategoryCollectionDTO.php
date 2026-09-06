<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\DTO;

use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * Typed Category query result collection.
 *
 * @implements IteratorAggregate<int, CategoryDTO>
 */
final readonly class CategoryCollectionDTO implements IteratorAggregate, Countable
{
    /**
     * @param list<CategoryDTO> $items
     */
    public function __construct(private array $items) {}

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return ArrayIterator<int, CategoryDTO>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}
