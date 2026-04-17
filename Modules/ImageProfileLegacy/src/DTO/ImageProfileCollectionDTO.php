<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 * @see         https://www.maatify.dev Maatify.dev
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\ImageProfileLegacy\DTO;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Maatify\ImageProfileLegacy\Entity\ImageProfileEntity;
use Traversable;

/**
 * Immutable, ordered collection of {@see ImageProfileEntity} entities.
 *
 * Only this collection type is returned when listing profiles — no raw
 * arrays of entities are exposed on the public API.
 *
 * @implements IteratorAggregate<int, ImageProfileEntity>
 */
final class ImageProfileCollectionDTO implements IteratorAggregate, Countable, JsonSerializable
{
    /**
     * @var list<ImageProfileEntity>
     */
    private readonly array $profiles;

    public function __construct(ImageProfileEntity ...$profiles)
    {
        $this->profiles = array_values($profiles);
    }

    public static function empty(): self
    {
        return new self();
    }

    /**
     * Return a new collection containing only active profiles.
     */
    public function filterActive(): self
    {
        $active = array_values(
            array_filter($this->profiles, static fn(ImageProfileEntity $p) => $p->isActive())
        );

        return new self(...$active);
    }

    public function with(ImageProfileEntity $profile): self
    {
        return new self(...$this->profiles, ...[$profile]);
    }

    public function isEmpty(): bool
    {
        return $this->profiles === [];
    }

    public function count(): int
    {
        return count($this->profiles);
    }

    public function first(): ?ImageProfileEntity
    {
        return $this->profiles[0] ?? null;
    }

    /**
     * @return Traversable<int, ImageProfileEntity>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->profiles);
    }

    /**
     * @return list<ImageProfileEntity>
     */
    public function jsonSerialize(): array
    {
        return $this->profiles;
    }
}
