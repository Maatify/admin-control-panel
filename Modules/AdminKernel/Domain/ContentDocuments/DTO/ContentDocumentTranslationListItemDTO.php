<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\DTO;

use JsonSerializable;

/**
 * @phpstan-type ContentDocumentTranslationListItemArray array{
 *     document_id: int,
 *     language_id: int,
 *     language_code: string,
 *     language_name: string,
 *     language_icon: string|null,
 *     language_direction: string|null,
 *     has_translation: bool,
 *     translation_id: int|null,
 *     updated_at: string|null
 * }
 */
final class ContentDocumentTranslationListItemDTO implements JsonSerializable
{
    public function __construct(
        public int $document_id,
        public int $language_id,
        public string $language_code,
        public string $language_name,
        public ?string $language_icon,
        public ?string $language_direction,
        public bool $has_translation,
        public ?int $translation_id,
        public ?string $updated_at,
    ) {
    }

    /**
     * @return ContentDocumentTranslationListItemArray
     */
    public function jsonSerialize(): array
    {
        return [
            'document_id'        => $this->document_id,
            'language_id'        => $this->language_id,
            'language_code'      => $this->language_code,
            'language_name'      => $this->language_name,
            'language_icon'      => $this->language_icon,
            'language_direction' => $this->language_direction,
            'has_translation'    => $this->has_translation,
            'translation_id'     => $this->translation_id,
            'updated_at'         => $this->updated_at,
        ];
    }
}