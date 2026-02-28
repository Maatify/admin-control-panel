<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Entity;

final readonly class DocumentTranslation
{
    public function __construct(
        public int $id,
        public int $documentId,
        public int $languageId,
        public string $title,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public string $content,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
    ) {
    }
}
