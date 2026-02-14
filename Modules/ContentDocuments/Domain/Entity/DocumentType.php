<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Entity;

use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;

final readonly class DocumentType
{
    public function __construct(
        public int $id,
        public DocumentTypeKey $key,
        public bool $requiresAcceptanceDefault,
        public bool $isSystem,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
    ) {
    }
}
