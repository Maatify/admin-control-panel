<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\DTO;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Immutable, typed collection of GeneratedVariantDTO objects.
 *
 * @implements IteratorAggregate<int, GeneratedVariantDTO>
 */
final class GeneratedVariantCollectionDTO implements IteratorAggregate, Countable, JsonSerializable
{
    /** @var list<GeneratedVariantDTO> */
    private array $items;

    public function __construct(GeneratedVariantDTO ...$variants)
    {
        $this->items = array_values($variants);
    }

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    public static function empty(): self
    {
        return new self();
    }

    /**
     * Returns a new instance with $variant appended.
     * The receiver is not mutated.
     */
    public function with(GeneratedVariantDTO $variant): self
    {
        return new self(...$this->items, ...[$variant]);
    }

    // -------------------------------------------------------------------------
    // Lookups
    // -------------------------------------------------------------------------

    public function findByName(string $name): ?GeneratedVariantDTO
    {
        foreach ($this->items as $item) {
            if ($item->name === $name) {
                return $item;
            }
        }

        return null;
    }

    public function hasName(string $name): bool
    {
        return $this->findByName($name) !== null;
    }

    /** Returns the total size in bytes of all generated variants. */
    public function totalSizeBytes(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->result->sizeBytes;
        }

        return $total;
    }

    // -------------------------------------------------------------------------
    // IteratorAggregate
    // -------------------------------------------------------------------------

    /** @return Traversable<int, GeneratedVariantDTO> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    // -------------------------------------------------------------------------
    // Countable
    // -------------------------------------------------------------------------

    public function count(): int
    {
        return count($this->items);
    }

    // -------------------------------------------------------------------------
    // JsonSerializable
    // -------------------------------------------------------------------------

    /** @return list<array<string, mixed>> */
    public function jsonSerialize(): array
    {
        return array_map(
            static fn(GeneratedVariantDTO $v): array => $v->jsonSerialize(),
            $this->items
        );
    }
}
