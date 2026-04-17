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
use Maatify\ImageProfile\Enum\ImageFormatEnum;
use Maatify\ImageProfile\Enum\ResizeModeEnum;
use Traversable;

/**
 * Immutable, typed collection of VariantDefinitionDTO objects.
 *
 * @implements IteratorAggregate<int, VariantDefinitionDTO>
 */
final class VariantDefinitionCollectionDTO implements IteratorAggregate, Countable, JsonSerializable
{
    /** @var list<VariantDefinitionDTO> */
    private array $items;

    public function __construct(VariantDefinitionDTO ...$variants)
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
     * Deserialise from a JSON-decoded array (as stored in the database).
     *
     * Expected per-element shape:
     * {
     *   "name": string,
     *   "width": int,
     *   "height": int,
     *   "mode": string  (fit|fill|stretch),
     *   "quality": int,
     *   "outputFormat": string|null
     * }
     *
     * Malformed or missing elements are silently skipped to prevent a bad DB
     * row from taking down the entire provider.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public static function fromJsonArray(array $rows): self
    {
        $variants = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name   = isset($row['name'])   && is_string($row['name'])   ? $row['name']            : null;
            $width  = isset($row['width'])  && is_numeric($row['width']) ? (int) $row['width']      : null;
            $height = isset($row['height']) && is_numeric($row['height'])? (int) $row['height']     : null;

            if ($name === null || $name === '' || $width === null || $height === null) {
                continue;
            }

            $modeValue    = isset($row['mode']) && is_string($row['mode']) ? $row['mode'] : ResizeModeEnum::Fit->value;
            $mode         = ResizeModeEnum::tryFrom($modeValue) ?? ResizeModeEnum::Fit;
            $quality      = isset($row['quality']) && is_numeric($row['quality']) ? (int) $row['quality'] : 85;
            $formatValue  = isset($row['outputFormat']) && is_string($row['outputFormat']) ? $row['outputFormat'] : null;
            $outputFormat = $formatValue !== null ? ImageFormatEnum::tryFrom($formatValue) : null;

            try {
                $options    = new ResizeOptionsDTO($width, $height, $mode, $quality, $outputFormat);
                $variants[] = new VariantDefinitionDTO($name, $options);
            } catch (\Throwable) {
                // Skip invalid entries
            }
        }

        return new self(...$variants);
    }

    /**
     * Deserialise from a JSON string (as read directly from a database TEXT column).
     * Returns an empty collection on null, empty string, or invalid JSON.
     */
    public static function fromJsonString(?string $json): self
    {
        if ($json === null || $json === '') {
            return self::empty();
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return self::empty();
        }

        return self::fromJsonArray($decoded);
    }

    /**
     * Returns a new instance with $variant appended.
     * The receiver is not mutated.
     */
    public function with(VariantDefinitionDTO $variant): self
    {
        return new self(...$this->items, ...[$variant]);
    }

    // -------------------------------------------------------------------------
    // Lookups
    // -------------------------------------------------------------------------

    public function findByName(string $name): ?VariantDefinitionDTO
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

    // -------------------------------------------------------------------------
    // IteratorAggregate
    // -------------------------------------------------------------------------

    /** @return Traversable<int, VariantDefinitionDTO> */
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
            static fn(VariantDefinitionDTO $v): array => $v->jsonSerialize(),
            $this->items
        );
    }
}
