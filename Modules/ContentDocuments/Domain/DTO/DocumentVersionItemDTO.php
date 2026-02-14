<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\DTO;

final readonly class DocumentVersionItemDTO
{
    public function __construct(
        public int $documentId,
        public string $version,
        public bool $isActive,
        public bool $requiresAcceptance,
        public ?\DateTimeImmutable $publishedAt,
        public \DateTimeImmutable $createdAt,
    ) {}
}
