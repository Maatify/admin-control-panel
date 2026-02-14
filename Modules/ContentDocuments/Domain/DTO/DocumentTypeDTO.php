<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\DTO;

final readonly class DocumentTypeDTO
{
    public function __construct(
        public int $id,
        public string $key,
        public bool $requiresAcceptanceDefault,
        public bool $isSystem,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
    ) {
    }
}
