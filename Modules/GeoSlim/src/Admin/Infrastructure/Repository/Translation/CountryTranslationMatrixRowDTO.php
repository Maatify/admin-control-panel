<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Infrastructure\Repository\Translation;

use JsonSerializable;

final readonly class CountryTranslationMatrixRowDTO implements JsonSerializable
{
    public function __construct(
        public int $countryId,
        public int $languageId,
        public string $languageCode,
        public string $languageName,

        public ?int $id,
        public ?string $name,
        public ?string $createdAt,
        public ?string $updatedAt,

        public string $baseCountryName,
    ) {
    }

    public function hasTranslation(): bool
    {
        return $this->id !== null;
    }

    /**
     * @return array{
     *     country_id: int,
     *     language_id: int,
     *     language_code: string,
     *     language_name: string,
     *     id: int|null,
     *     name: string|null,
     *     created_at: string|null,
     *     updated_at: string|null,
     *     base_country_name: string,
     *     has_translation: bool
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'country_id' => $this->countryId,
            'language_id' => $this->languageId,
            'language_code' => $this->languageCode,
            'language_name' => $this->languageName,
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'base_country_name' => $this->baseCountryName,
            'has_translation' => $this->hasTranslation(),
        ];
    }
}
