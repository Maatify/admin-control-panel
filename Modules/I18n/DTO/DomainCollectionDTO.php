<?php

declare(strict_types=1);

namespace Maatify\I18n\DTO;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, DomainDTO>
 */
final readonly class DomainCollectionDTO implements IteratorAggregate
{
    /**
     * @param DomainDTO[] $items
     */
    public function __construct(
        public array $items
    ) {
    }

    /**
     * @return Traversable<int, DomainDTO>
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
