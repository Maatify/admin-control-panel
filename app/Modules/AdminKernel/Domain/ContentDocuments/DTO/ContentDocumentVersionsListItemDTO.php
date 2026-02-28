<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\DTO;

use JsonSerializable;

/**
 * @phpstan-type ContentDocumentVersionsListItemArray array{
 *     id: int,
 *     document_type_id: int,
 *     type_key: string,
 *     version: string,
 *     is_active: int,
 *     requires_acceptance: int,
 *     published_at: string|null,
 *     archived_at: string|null,
 *     created_at: string,
 *     updated_at: string|null
 * }
 */
final class ContentDocumentVersionsListItemDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public int $document_type_id,
        public string $type_key,
        public string $version,
        public int $is_active,
        public int $requires_acceptance,
        public ?string $published_at,
        public ?string $archived_at,
        public string $created_at,
        public ?string $updated_at,
    ) {
    }

    /**
     * @return ContentDocumentVersionsListItemArray
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'document_type_id' => $this->document_type_id,
            'type_key' => $this->type_key,
            'version' => $this->version,
            'is_active' => $this->is_active,
            'requires_acceptance' => $this->requires_acceptance,
            'published_at' => $this->published_at,
            'archived_at' => $this->archived_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}