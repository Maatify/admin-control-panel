<?php

declare(strict_types=1);

namespace Maatify\Category\DTO;

use JsonSerializable;
use Maatify\Category\Exception\CategoryInvalidArgumentException;

/**
 * Immutable read-model for a single category_settings row.
 *
 * Implements JsonSerializable — works directly with json_encode().
 */
final class CategorySettingDTO implements JsonSerializable
{
    public function __construct(
        public readonly int     $id,
        public readonly int     $categoryId,
        public readonly string  $key,
        public readonly string  $value,
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
            key:        self::string($row['key']),
            value:      self::string($row['value']),
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
     *     key:         string,
     *     value:       string,
     *     created_at:  string,
     *     updated_at:  string|null
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id'          => $this->id,
            'category_id' => $this->categoryId,
            'key'         => $this->key,
            'value'       => $this->value,
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

