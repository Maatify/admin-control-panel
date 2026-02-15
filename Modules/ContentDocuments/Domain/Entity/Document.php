<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Entity;

use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;

final readonly class Document
{
    public function __construct(
        public int $id,
        public int $documentTypeId,
        public DocumentTypeKey $typeKey,
        public DocumentVersion $version,
        public bool $isActive,
        public bool $requiresAcceptance,
        public ?\DateTimeImmutable $publishedAt,
        public ?\DateTimeImmutable $archivedAt,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
    ) {
    }

    public function isPublished(): bool
    {
        return $this->publishedAt !== null;
    }

    public function isArchived(): bool
    {
        return $this->archivedAt !== null;
    }
}
