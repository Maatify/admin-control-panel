<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\DTO;

use JsonSerializable;

/**
 * @phpstan-type ContentDocumentTranslationArray array{
 *     id: int,
 *     document_id: int,
 *     language_id: int,
 *     title: string,
 *     meta_title: string|null,
 *     meta_description: string|null,
 *     content: string,
 *     created_at: string,
 *     updated_at: string|null
 * }
 */
final class ContentDocumentTranslationDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public int $document_id,
        public int $language_id,
        public string $title,
        public ?string $meta_title,
        public ?string $meta_description,
        public string $content,
        public string $created_at,
        public ?string $updated_at,
    ) {
    }

    /**
     * @return ContentDocumentTranslationArray
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->document_id,
            'language_id' => $this->language_id,
            'title' => $this->title,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'content' => $this->content,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
