<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\DTO;

use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * Typed Category Translation query result collection.
 *
 * @implements IteratorAggregate<int, CategoryTranslationDTO>
 */
final readonly class CategoryTranslationCollectionDTO implements IteratorAggregate, Countable
{
    /**
     * @param list<CategoryTranslationDTO> $items
     */
    public function __construct(private array $items) {}

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return ArrayIterator<int, CategoryTranslationDTO>
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
