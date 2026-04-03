<?php

declare(strict_types=1);

namespace Maatify\Currency\DTO;

use Maatify\Currency\Exception\CurrencyInvalidArgumentException;

/**
 * Immutable read-model for a currency translation row, always enriched
 * with language identity data from the `languages` table.
 *
 * ── Listing behaviour ───────────────────────────────────────────────────
 *
 *  listTranslationsForCurrency() performs a LEFT JOIN on `languages` so
 *  every active language is represented — including those without a
 *  translation row yet.
 *
 *  When no translation exists for a language:
 *    $id             → null
 *    $translatedName → null
 *    $createdAt      → null
 *    $updatedAt      → null
 *
 *  The caller can use ($dto->translatedName === null) to detect
 *  untranslated languages and render an "Add translation" prompt.
 *
 * ── Single-record lookups ────────────────────────────────────────────────
 *
 *  findTranslation() performs an INNER JOIN so it only returns a DTO
 *  when the translation row actually exists (non-null fields guaranteed).
 */
final class CurrencyTranslationDTO
{
    public function __construct(
        public readonly ?int    $id,              // null = no translation row yet
        public readonly int     $languageId,
        public readonly string  $languageCode,
        public readonly string  $languageName,
        public readonly ?string $translatedName,  // null = no translation row yet
        public readonly ?string $createdAt,       // null = no translation row yet
        public readonly ?string $updatedAt,       // null = no translation row yet
    ) {}

    // ------------------------------------------------------------------ //
    //  Convenience
    // ------------------------------------------------------------------ //

    /** Returns true when a real translation row exists for this language. */
    public function hasTranslation(): bool
    {
        return $this->translatedName !== null;
    }

    // ------------------------------------------------------------------ //
    //  Factory
    // ------------------------------------------------------------------ //

    /**
     * Hydrates from a row produced by either:
     *   • LEFT JOIN languages → currency_translations  (listing — nulls possible)
     *   • INNER JOIN languages → currency_translations (single lookup — no nulls)
     *
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:             self::nullableInt($row['id'] ?? null),
            languageId:     self::int($row['language_id']),
            languageCode:   self::string($row['language_code']),
            languageName:   self::string($row['language_name']),
            translatedName: self::nullableString($row['name'] ?? null),
            createdAt:      self::nullableString($row['created_at'] ?? null),
            updatedAt:      self::nullableString($row['updated_at'] ?? null),
        );
    }

    // ------------------------------------------------------------------ //
    //  Serialisation
    // ------------------------------------------------------------------ //

    /**
     * @return array{
     *     id:              int|null,
     *     language_id:     int,
     *     language_code:   string,
     *     language_name:   string,
     *     translated_name: string|null,
     *     has_translation: bool,
     *     created_at:      string|null,
     *     updated_at:      string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'language_id'     => $this->languageId,
            'language_code'   => $this->languageCode,
            'language_name'   => $this->languageName,
            'translated_name' => $this->translatedName,
            'has_translation' => $this->hasTranslation(),
            'created_at'      => $this->createdAt,
            'updated_at'      => $this->updatedAt,
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
        throw CurrencyInvalidArgumentException::unexpectedType('int field', $value);
    }

    private static function string(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        throw CurrencyInvalidArgumentException::unexpectedType('string field', $value);
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
