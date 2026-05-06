<?php

declare(strict_types=1);

namespace Maatify\Geo\DTO;

use JsonSerializable;
use Maatify\Geo\Exception\GeoInvalidArgumentException;

/**
 * Immutable read-model for a single country row.
 *
 * Implements JsonSerializable — Json_encode($dto) works directly.
 *
 * ── Translation fields ──────────────────────────────────────────────────
 *
 *  $languageId      null  → query was executed without a language context;
 *                           $translatedName is also null.
 *
 *  $languageId      int   → query used LEFT JOIN + COALESCE:
 *                           $translatedName is ALWAYS a non-null string —
 *                           either the actual translation or the base name
 *                           as fallback. No null-check required by the caller.
 */
final class  CountryDTO implements JsonSerializable
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $code,
        public readonly string  $name,
        public readonly ?string $icon,
        public readonly bool    $isActive,
        public readonly int     $displayOrder,
        public readonly string  $createdAt,
        public readonly ?string $updatedAt,
        public readonly ?string $translatedName,
        public readonly ?int    $languageId,
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
            id:             self::int($row['id']),
            code:           self::string($row['code']),
            name:           self::string($row['name']),
            icon:           self::nullableString($row['icon'] ?? null),
            isActive:       self::bool($row['is_active']),
            displayOrder:   self::int($row['display_order']),
            createdAt:      self::string($row['created_at']),
            updatedAt:      self::nullableString($row['updated_at'] ?? null),
            translatedName: self::nullableString($row['translated_name'] ?? null),
            languageId:     self::nullableInt($row['translation_language_id'] ?? null),
        );
    }

    // ------------------------------------------------------------------ //
    //  Convenience
    // ------------------------------------------------------------------ //

    public function displayName(): string
    {
        return $this->translatedName ?? $this->name;
    }

    // ------------------------------------------------------------------ //
    //  Serialisation
    // ------------------------------------------------------------------ //

    /**
     * @return array{
     *     id:              int,
     *     code:            string,
     *     name:            string,
     *     icon:            string|null,
     *     is_active:       bool,
     *     display_order:   int,
     *     created_at:      string,
     *     updated_at:      string|null,
     *     translated_name: string|null,
     *     language_id:     int|null
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id'              => $this->id,
            'code'            => $this->code,
            'name'            => $this->name,
            'icon'            => $this->icon,
            'is_active'       => $this->isActive,
            'display_order'   => $this->displayOrder,
            'created_at'      => $this->createdAt,
            'updated_at'      => $this->updatedAt,
            'translated_name' => $this->translatedName,
            'language_id'     => $this->languageId,
        ];
    }

    // ------------------------------------------------------------------ //
    //  Private — type-safe row casting helpers
    // ------------------------------------------------------------------ //

    private static function int(mixed $value): int
    {
        if (is_int($value)) { return $value; }
        if (is_numeric($value)) { return (int) $value; }
        throw GeoInvalidArgumentException::unexpectedType('int field', $value);
    }

    private static function string(mixed $value): string
    {
        if (is_string($value)) { return $value; }
        throw GeoInvalidArgumentException::unexpectedType('string field', $value);
    }

    private static function bool(mixed $value): bool
    {
        if (is_bool($value)) { return $value; }
        if (is_int($value) || is_string($value)) { return (bool) $value; }
        throw GeoInvalidArgumentException::unexpectedType('bool field', $value);
    }

    private static function nullableString(mixed $value): ?string
    {
        return $value === null ? null : self::string($value);
    }

    private static function nullableInt(mixed $value): ?int
    {
        return $value === null ? null : self::int($value);
    }
}

