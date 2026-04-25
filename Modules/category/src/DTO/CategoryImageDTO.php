<?php

declare(strict_types=1);

namespace Maatify\Category\DTO;

use JsonSerializable;
use Maatify\Category\Exception\CategoryInvalidArgumentException;

/**
 * Immutable read-model for a single category_images row.
 *
 * Implements JsonSerializable — works directly with json_encode().
 */
final class CategoryImageDTO implements JsonSerializable
{
    public function __construct(
        public readonly int     $id,
        public readonly int     $categoryId,
        public readonly string  $imageType,
        public readonly int     $languageId,
        public readonly string  $path,
        public readonly string  $createdAt,
        public readonly ?string $updatedAt,
    ) {}

    // ------------------------------------------------------------------ //
    //  Factory
    // ------------------------------------------------------------------ //

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:         self::int($row['id']),
            categoryId: self::int($row['category_id']),
            imageType:  self::string($row['image_type']),
            languageId: self::int($row['language_id']),
            path:       self::string($row['path']),
            createdAt:  self::string($row['created_at']),
            updatedAt:  self::nullableString($row['updated_at'] ?? null),
        );
    }

    // ------------------------------------------------------------------ //
    //  Serialisation
    // ------------------------------------------------------------------ //

    /**
     * @return array{
     *     id:          int,
     *     category_id: int,
     *     image_type:  string,
     *     language_id: int,
     *     path:        string,
     *     created_at:  string,
     *     updated_at:  string|null
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id'          => $this->id,
            'category_id' => $this->categoryId,
            'image_type'  => $this->imageType,
            'language_id' => $this->languageId,
            'path'        => $this->path,
            'created_at'  => $this->createdAt,
            'updated_at'  => $this->updatedAt,
        ];
    }

    // ------------------------------------------------------------------ //
    //  Private — type-safe row casting helpers
    // ------------------------------------------------------------------ //

    private static function int(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        throw CategoryInvalidArgumentException::unexpectedType('int field', $value);
    }

    private static function string(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        throw CategoryInvalidArgumentException::unexpectedType('string field', $value);
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return self::string($value);
    }
}

