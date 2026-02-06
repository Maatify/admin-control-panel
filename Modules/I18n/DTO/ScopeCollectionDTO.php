<?php

declare(strict_types=1);

namespace Maatify\I18n\DTO;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, ScopeDTO>
 */
final readonly class ScopeCollectionDTO implements IteratorAggregate
{
    /**
     * @param ScopeDTO[] $items
     */
    public function __construct(
        public array $items
    ) {
    }

    /**
     * @return Traversable<int, ScopeDTO>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}
