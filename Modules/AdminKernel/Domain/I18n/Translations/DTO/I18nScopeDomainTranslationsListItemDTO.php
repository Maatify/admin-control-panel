<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Translations\DTO;

use JsonSerializable;

/**
 * @phpstan-type I18nScopeDomainTranslationsListItemArray array{
 *   id: int|null,
 *   key_id: int,
 *   key_part: string,
 *   description: string|null,
 *   language_id: int|null,
 *   language_code: string|null,
 *   language_name: string|null,
 *   language_icon: string|null,
 *   language_direction: string|null,
 *   value: string|null
 * }
 */
class I18nScopeDomainTranslationsListItemDTO implements JsonSerializable
{
    public function __construct(
        public ?int $id, // translation_id
        public int $keyId,
        public string $keyPart,
        public ?string $description,
        public ?int $languageId,
        public ?string $languageCode,
        public ?string $languageName,
        public ?string $languageIcon,
        public ?string $languageDirection,
        public ?string $value,
    ) {}

    /**
     * @return I18nScopeDomainTranslationsListItemArray
     */

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'key_id' => $this->keyId,
            'key_part' => $this->keyPart,
            'description' => $this->description,
            'language_id' => $this->languageId,
            'language_code' => $this->languageCode,
            'language_name' => $this->languageName,
            'language_icon' => $this->languageIcon,
            'language_direction' => $this->languageDirection,
            'value' => $this->value,
        ];
    }
}
