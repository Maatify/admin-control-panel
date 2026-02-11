<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\TranslationValue\DTO;

use JsonSerializable;

/**
 * @phpstan-type TranslationValueListItemArray array{
 *   key_id: int,
 *   key_name: string,
 *   translation_id: int|null,
 *   language_id: int,
 *   value: string|null,
 *   created_at: string,
 *   updated_at: string|null
 * }
 */
final readonly class TranslationValueListItemDTO implements JsonSerializable
{
    public function __construct(
        public int $keyId,
        public string $keyName,
        public ?int $translationId,
        public int $languageId,
        public ?string $value,
        public string $createdAt,
        public ?string $updatedAt,
    ) {}

    /**
     * @return TranslationValueListItemArray
     */
    public function jsonSerialize(): array
    {
        return [
            'key_id' => $this->keyId,
            'key_name' => $this->keyName,
            'translation_id' => $this->translationId,
            'language_id' => $this->languageId,
            'value' => $this->value,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}

