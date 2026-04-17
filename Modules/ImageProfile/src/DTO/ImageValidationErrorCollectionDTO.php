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

namespace Maatify\ImageProfile\DTO;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Maatify\ImageProfile\Enum\ValidationErrorCodeEnum;
use Traversable;

/**
 * Immutable, ordered collection of {@see ImageValidationErrorDTO}.
 *
 * Only this collection type appears on the public contract — no raw
 * arrays of errors are exposed by the package.
 *
 * @implements IteratorAggregate<int, ImageValidationErrorDTO>
 */
final class ImageValidationErrorCollectionDTO implements IteratorAggregate, Countable, JsonSerializable
{
    /**
     * @var list<ImageValidationErrorDTO>
     */
    private readonly array $errors;

    public function __construct(ImageValidationErrorDTO ...$errors)
    {
        $this->errors = array_values($errors);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function with(ImageValidationErrorDTO $error): self
    {
        return new self(...$this->errors, ...[$error]);
    }

    public function isEmpty(): bool
    {
        return $this->errors === [];
    }

    public function count(): int
    {
        return count($this->errors);
    }

    public function hasCode(ValidationErrorCodeEnum $code): bool
    {
        foreach ($this->errors as $error) {
            if ($error->code === $code) {
                return true;
            }
        }
        return false;
    }

    public function first(): ?ImageValidationErrorDTO
    {
        return $this->errors[0] ?? null;
    }

    /**
     * @return Traversable<int, ImageValidationErrorDTO>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->errors);
    }

    /**
     * @return list<ImageValidationErrorDTO>
     */
    public function jsonSerialize(): array
    {
        return $this->errors;
    }
}
