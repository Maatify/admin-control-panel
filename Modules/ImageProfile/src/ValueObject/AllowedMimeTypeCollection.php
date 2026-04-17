<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-16
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\ValueObject;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Immutable collection of normalized MIME types.
 *
 * Rules:
 *   - values are stored lowercase
 *   - whitespace is trimmed
 *   - duplicates are removed (stable order)
 *   - empty strings are ignored
 *
 * No array-based representation is exposed on the public API.
 *
 * @implements IteratorAggregate<int, string>
 */
final class AllowedMimeTypeCollection implements IteratorAggregate, Countable, JsonSerializable
{
    /**
     * @var list<string>
     */
    private readonly array $values;

    public function __construct(string ...$mimeTypes)
    {
        $normalized = [];
        $seen       = [];

        foreach ($mimeTypes as $raw) {
            $value = self::normalize($raw);
            if ($value === '') {
                continue;
            }
            if (isset($seen[$value])) {
                continue;
            }
            $seen[$value] = true;
            $normalized[] = $value;
        }

        $this->values = $normalized;
    }

    /**
     * Build from a comma/semicolon-separated string (as stored in DB).
     */
    public static function fromDelimitedString(?string $raw, string $delimiters = ",;|"): self
    {
        if ($raw === null) {
            return new self();
        }
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return new self();
        }

        $pattern = '/[' . preg_quote($delimiters, '/') . ']+/';
        $parts   = preg_split($pattern, $trimmed) ?: [];

        return new self(...array_values($parts));
    }

    public function has(string $mimeType): bool
    {
        $needle = self::normalize($mimeType);
        if ($needle === '') {
            return false;
        }
        foreach ($this->values as $value) {
            if ($value === $needle) {
                return true;
            }
        }
        return false;
    }

    public function isEmpty(): bool
    {
        return $this->values === [];
    }

    public function count(): int
    {
        return count($this->values);
    }

    /**
     * @return Traversable<int, string>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->values);
    }

    /**
     * @return list<string>
     */
    public function jsonSerialize(): array
    {
        return $this->values;
    }

    private static function normalize(string $value): string
    {
        return strtolower(trim($value));
    }
}
