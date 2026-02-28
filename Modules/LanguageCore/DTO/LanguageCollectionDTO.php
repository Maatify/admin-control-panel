<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\DTO;

use ArrayIterator;
use IteratorAggregate;

/**
 * @phpstan-type LanguageList list<LanguageDTO>
 * @implements IteratorAggregate<int, LanguageDTO>
 */
final readonly class LanguageCollectionDTO implements IteratorAggregate
{
    /**
     * @param list<LanguageDTO> $items
     */
    public function __construct(
        public array $items,
    ) {}

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * @return ArrayIterator<int, LanguageDTO>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
}
