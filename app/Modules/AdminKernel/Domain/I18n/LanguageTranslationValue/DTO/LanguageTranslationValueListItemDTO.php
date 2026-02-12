<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\LanguageTranslationValue\DTO;

use JsonSerializable;

/**
 * @phpstan-type TranslationValueListItemArray array{
 *   key_id: int,
 *   scope: string,
 *   domain: string,
 *   key_part: string,
 *   translation_id: int|null,
 *   language_id: int,
 *   value: string|null,
 *   created_at: string,
 *   updated_at: string|null
 * }
 */
final readonly class LanguageTranslationValueListItemDTO implements JsonSerializable
{
    public function __construct(
        public int $keyId,
        public string $scope,
        public string $domain,
        public string $keyPart,
        public ?int $translationId,
        public int $languageId,
        public ?string $value,
        public string $createdAt,
        public ?string $updatedAt,
    ) {}

    public function fullKey(): string
    {
        return $this->scope . '.' . $this->domain . '.' . $this->keyPart;
    }

    /**
     * @return TranslationValueListItemArray
     */
    public function jsonSerialize(): array
    {
        return [
            'key_id' => $this->keyId,
            'scope' => $this->scope,
            'domain' => $this->domain,
            'key_part' => $this->keyPart,
            'translation_id' => $this->translationId,
            'language_id' => $this->languageId,
            'value' => $this->value,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
