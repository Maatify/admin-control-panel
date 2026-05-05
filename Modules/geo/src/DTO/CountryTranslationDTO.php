<?php

declare(strict_types=1);

namespace Maatify\Geo\DTO;

use JsonSerializable;
use Maatify\Geo\Exception\GeoInvalidArgumentException;

/**
 * Immutable read-model for a single row in geo_country_translations.
 *
 * This DTO contains ONLY data owned by the geo module.
 * It has NO knowledge of the languages table.
 *
 * language_id is stored as a plain INT — the admin execution layer
 * is responsible for joining language metadata (code, name, etc.)
 * independently when it needs to display language details alongside
 * the translation.
 */
final readonly class CountryTranslationDTO implements JsonSerializable
{
    public function __construct(
        public int     $id,
        public int     $countryId,
        public int     $languageId,
        public string  $name,
        public string  $createdAt,
        public ?string $updatedAt,
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
            countryId:  self::int($row['country_id']),
            languageId: self::int($row['language_id']),
            name:       self::string($row['name']),
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
     *     country_id:  int,
     *     language_id: int,
     *     name:        string,
     *     created_at:  string,
     *     updated_at:  string|null
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id'          => $this->id,
            'country_id'  => $this->countryId,
            'language_id' => $this->languageId,
            'name'        => $this->name,
            'created_at'  => $this->createdAt,
            'updated_at'  => $this->updatedAt,
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

    private static function nullableString(mixed $value): ?string
    {
        return $value === null ? null : self::string($value);
    }
}

