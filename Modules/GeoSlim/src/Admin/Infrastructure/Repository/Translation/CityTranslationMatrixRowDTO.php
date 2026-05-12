<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Infrastructure\Repository\Translation;

use JsonSerializable;

final readonly class CityTranslationMatrixRowDTO implements JsonSerializable
{
    public function __construct(
        public int $cityId,
        public int $languageId,
        public string $languageCode,
        public string $languageName,

        public ?int $id,
        public ?string $name,
        public ?string $createdAt,
        public ?string $updatedAt,

        public string $baseCityName,
    ) {
    }

    public function hasTranslation(): bool
    {
        return $this->id !== null;
    }

    /**
     * @return array{
     *     city_id: int,
     *     language_id: int,
     *     language_code: string,
     *     language_name: string,
     *     id: int|null,
     *     name: string|null,
     *     created_at: string|null,
     *     updated_at: string|null,
     *     base_city_name: string,
     *     has_translation: bool
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'city_id' => $this->cityId,
            'language_id' => $this->languageId,
            'language_code' => $this->languageCode,
            'language_name' => $this->languageName,
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'base_city_name' => $this->baseCityName,
            'has_translation' => $this->hasTranslation(),
        ];
    }
}
