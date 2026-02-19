<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\DTO;

use JsonSerializable;

/**
 * @phpstan-type ContentDocumentListItemArray array{
 *     id: int,
 *     key: string,
 *     requires_acceptance_default: int,
 *     is_system: int,
 *     created_at: string,
 *     updated_at: string
 * }
 */
final class ContentDocumentListItemDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $key,
        public int $requires_acceptance_default,
        public int $is_system,
        public string $created_at,
        public string $updated_at
    )
    {
    }

    /**
     * @return ContentDocumentListItemArray
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'requires_acceptance_default' => $this->requires_acceptance_default,
            'is_system' => $this->is_system,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
