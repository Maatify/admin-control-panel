<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Ui;

use IteratorAggregate;
use Traversable;

/**
 * NavigationItemDTO
 *
 * Rules:
 * - If $children !== null → this is a Group (container)
 * - If $children === null → this is a Link item
 * - Group MUST have null $path
 * - Link MUST have non-null $path
 *
 * @implements IteratorAggregate<int, NavigationItemDTO>
 */
readonly class NavigationItemDTO implements IteratorAggregate
{
    /**
     * @param NavigationItemDTO[]|null $children
     */
    public function __construct(
        public string $title,
        public ?string $path,
        public string $icon,
        public ?array $children = null
    ) {
    }

    public function isGroup(): bool
    {
        return $this->children !== null;
    }

    public function isLink(): bool
    {
        return $this->children === null;
    }

    /**
     * Allows direct iteration over children (if group)
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->children ?? []);
    }
}
