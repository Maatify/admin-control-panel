<?php

declare(strict_types=1);

namespace Maatify\Category\DTO;

use JsonSerializable;
use Maatify\Category\Exception\CategoryInvalidArgumentException;

/**
 * Immutable read-model for a category translation row, always enriched
 * with language identity data from the `languages` table.
 *
 * Implements JsonSerializable — json_encode($dto) works directly.
 *
 * ── Listing behaviour ───────────────────────────────────────────────────
 *
 *  listTranslationsForCategoryPaginated() performs a LEFT JOIN on `languages`
 *  so every active language is represented — including those without a
 *  translation row yet.
 *
 *  When no translation exists for a language:
 *    $id             → null
 *    $translatedName → null
 *    $createdAt      → null
 *    $updatedAt      → null
 *
 *  The caller can use ($dto->translatedName === null) or hasTranslation()
 *  to detect untranslated languages and render an "Add translation" prompt.
 */
final class CategoryTranslationDTO implements JsonSerializable
{
    public function __construct(
        public readonly ?int    $id,                      // null = no translation row yet
        public readonly int     $languageId,
        public readonly string  $languageCode,
        public readonly string  $languageName,
        public readonly ?string $translatedName,          // null = no translation row yet
        public readonly ?string $translatedDescription,   // null = not translated yet
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}

    public function hasTranslation(): bool
    {
        return $this->translatedName !== null;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:                    self::nullableInt($row['id'] ?? null),
            languageId:            self::int($row['language_id']),
            languageCode:          self::string($row['language_code']),
            languageName:          self::string($row['language_name']),
            translatedName:        self::nullableString($row['name'] ?? null),
            translatedDescription: self::nullableString($row['description'] ?? null),
            createdAt:             self::nullableString($row['created_at'] ?? null),
            updatedAt:             self::nullableString($row['updated_at'] ?? null),
        );
    }

    /**
     * @return array{
     *     id:                     int|null,
     *     language_id:            int,
     *     language_code:          string,
     *     language_name:          string,
     *     translated_name:        string|null,
     *     translated_description: string|null,
     *     has_translation:        bool,
     *     created_at:             string|null,
     *     updated_at:             string|null
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id'                     => $this->id,
            'language_id'            => $this->languageId,
            'language_code'          => $this->languageCode,
            'language_name'          => $this->languageName,
            'translated_name'        => $this->translatedName,
            'translated_description' => $this->translatedDescription,
            'has_translation'        => $this->hasTranslation(),
            'created_at'             => $this->createdAt,
            'updated_at'             => $this->updatedAt,
        ];
    }

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

