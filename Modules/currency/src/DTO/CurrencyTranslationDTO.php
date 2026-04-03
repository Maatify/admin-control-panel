<?php

declare(strict_types=1);

namespace Maatify\Currency\DTO;

use InvalidArgumentException;

/**
 * Immutable read-model for a single currency_translations row.
 * Used when managing translations explicitly (admin screens).
 */
final class CurrencyTranslationDTO
{
    public function __construct(
        public readonly int     $id,
        public readonly int     $currencyId,
        public readonly int     $languageId,
        public readonly string  $translatedName,
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
            id:             self::int($row['id']),
            currencyId:     self::int($row['currency_id']),
            languageId:     self::int($row['language_id']),
            translatedName: self::string($row['name']),
            createdAt:      self::string($row['created_at']),
            updatedAt:      self::nullableString($row['updated_at'] ?? null),
        );
    }

    // ------------------------------------------------------------------ //
    //  Serialisation
    // ------------------------------------------------------------------ //

    /**
     * @return array{
     *     id:              int,
     *     currency_id:     int,
     *     language_id:     int,
     *     translated_name: string,
     *     created_at:      string,
     *     updated_at:      string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'currency_id'     => $this->currencyId,
            'language_id'     => $this->languageId,
            'translated_name' => $this->translatedName,
            'created_at'      => $this->createdAt,
            'updated_at'      => $this->updatedAt,
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

    /** Narrows mixed → string|null. */
    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return self::string($value);
    }
}
