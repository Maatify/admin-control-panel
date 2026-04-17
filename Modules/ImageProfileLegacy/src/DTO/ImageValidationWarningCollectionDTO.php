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
use Traversable;

/**
 * Immutable, ordered collection of {@see ImageValidationWarningDTO}.
 *
 * @implements IteratorAggregate<int, ImageValidationWarningDTO>
 */
final class ImageValidationWarningCollectionDTO implements IteratorAggregate, Countable, JsonSerializable
{
    /**
     * @var list<ImageValidationWarningDTO>
     */
    private readonly array $warnings;

    public function __construct(ImageValidationWarningDTO ...$warnings)
    {
        $this->warnings = array_values($warnings);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function with(ImageValidationWarningDTO $warning): self
    {
        return new self(...$this->warnings, ...[$warning]);
    }

    public function isEmpty(): bool
    {
        return $this->warnings === [];
    }

    public function count(): int
    {
        return count($this->warnings);
    }

    /**
     * @return Traversable<int, ImageValidationWarningDTO>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->warnings);
    }

    /**
     * @return list<ImageValidationWarningDTO>
     */
    public function jsonSerialize(): array
    {
        return $this->warnings;
    }
}
