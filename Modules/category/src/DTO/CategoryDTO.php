<?php

declare(strict_types=1);

namespace Maatify\Category\DTO;

use JsonSerializable;
use Maatify\Category\Exception\CategoryInvalidArgumentException;

/**
 * Immutable read-model for a single category row.
 *
 * Implements JsonSerializable — json_encode($dto) and json_encode($list)
 * both work directly without a manual toArray() call in the controller.
 *
 * ── Hierarchy fields ────────────────────────────────────────────────────
 *
 *  $parentId = null  → this is a ROOT category (depth 0)
 *  $parentId = int   → this is a SUB-CATEGORY of that parent (depth 1)
 *
 * ── childCount ──────────────────────────────────────────────────────────
 *
 *  When the query reader computes child_count (via subquery), this field
 *  is a non-null int.
 *
 *  When it is not requested (e.g. in a sub-category list where children
 *  are impossible), it is null.
 *
 *  Callers use this to decide whether to show a "view sub-categories"
 *  button without a second round-trip.
 */
final class CategoryDTO implements JsonSerializable
{
    public function __construct(
        public readonly int     $id,
        public readonly ?int    $parentId,
        public readonly string  $name,
        public readonly string  $slug,
        public readonly ?string $description  = null,
        public readonly bool    $isActive     = true,
        public readonly int     $displayOrder = 0,
        public readonly string  $createdAt    = '',
        public readonly ?string $updatedAt    = null,
        public readonly ?int    $childCount   = null,
        public readonly ?string $notes        = null,
    ) {}

    // ------------------------------------------------------------------ //
    //  Convenience
    // ------------------------------------------------------------------ //

    /** Returns true when this is a root-level category. */
    public function isRoot(): bool
    {
        return $this->parentId === null;
    }

    /** Returns true when this is a sub-category. */
    public function isSubCategory(): bool
    {
        return $this->parentId !== null;
    }

    // ------------------------------------------------------------------ //
    //  Factory
    // ------------------------------------------------------------------ //

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:           self::int($row['id']),
            parentId:     self::nullableInt($row['parent_id'] ?? null),
            name:         self::string($row['name']),
            slug:         self::string($row['slug']),
            description:  self::nullableString($row['description'] ?? null),
            isActive:     self::bool($row['is_active']),
            displayOrder: self::int($row['display_order']),
            createdAt:    self::string($row['created_at']),
            updatedAt:    self::nullableString($row['updated_at'] ?? null),
            childCount:   self::nullableInt($row['child_count'] ?? null),
            notes:        self::nullableString($row['notes'] ?? null),
        );
    }

    // ------------------------------------------------------------------ //
    //  Serialisation — single source of truth for the JSON shape
    // ------------------------------------------------------------------ //

    /**
     * @return array{
     *     id:            int,
     *     parent_id:     int|null,
     *     name:          string,
     *     slug:          string,
     *     is_active:     bool,
     *     display_order: int,
     *     created_at:    string,
     *     updated_at:    string|null,
     *     child_count:   int|null,
     *     is_root:       bool,
     *     notes:         string|null
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id'            => $this->id,
            'parent_id'     => $this->parentId,
            'name'          => $this->name,
            'slug'          => $this->slug,
            'description'   => $this->description,
            'is_active'     => $this->isActive,
            'display_order' => $this->displayOrder,
            'created_at'    => $this->createdAt,
            'updated_at'    => $this->updatedAt,
            'child_count'   => $this->childCount,
            'is_root'       => $this->isRoot(),
            'notes'         => $this->notes,
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

    private static function bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_string($value)) {
            return (bool) $value;
        }
        throw CategoryInvalidArgumentException::unexpectedType('bool field', $value);
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }
        return self::int($value);
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return self::string($value);
    }
}

