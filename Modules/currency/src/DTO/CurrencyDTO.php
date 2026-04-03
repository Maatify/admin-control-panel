<?php

declare(strict_types=1);

namespace Maatify\Currency\DTO;

use InvalidArgumentException;

/**
 * Immutable read-model for a single currency row.
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
 *
 *  To know whether a real translation row exists (not just the fallback),
 *  use CurrencyQueryReaderInterface::findTranslation() explicitly.
 */
final class CurrencyDTO
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $code,
        public readonly string  $name,
        public readonly string  $symbol,
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
            symbol:         self::string($row['symbol']),
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

    /**
     * The effective display name for the current locale.
     *
     * When a languageId was provided at query time this always returns the
     * translated name (real translation or COALESCE fallback — both non-null).
     * When no language context was requested it returns the base name.
     */
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
     *     symbol:          string,
     *     is_active:       bool,
     *     display_order:   int,
     *     created_at:      string,
     *     updated_at:      string|null,
     *     translated_name: string|null,
     *     language_id:     int|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'code'            => $this->code,
            'name'            => $this->name,
            'symbol'          => $this->symbol,
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

    /**
     * Narrows mixed → int.
     * Accepts int or any numeric-string (as returned by MySQL PDO drivers).
     */
    private static function int(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        throw new InvalidArgumentException(
            sprintf('Expected numeric value, got %s.', get_debug_type($value)),
        );
    }

    /** Narrows mixed → string. */
    private static function string(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        throw new InvalidArgumentException(
            sprintf('Expected string value, got %s.', get_debug_type($value)),
        );
    }

    /**
     * Narrows mixed → bool.
     * MySQL TINYINT(1) is returned as '0'/'1' (string) or 0/1 (int) depending
     * on the PDO driver / emulation mode — both are handled here.
     */
    private static function bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_string($value)) {
            return (bool) $value;
        }

        throw new InvalidArgumentException(
            sprintf('Expected bool-castable value, got %s.', get_debug_type($value)),
        );
    }

    /** Narrows mixed → string|null. */
    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return self::string($value);
    }

    /** Narrows mixed → int|null. */
    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return self::int($value);
    }
}
