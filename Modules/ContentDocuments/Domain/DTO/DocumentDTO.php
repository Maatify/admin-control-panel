<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\DTO;

use DateTimeImmutable;

final readonly class DocumentDTO
{
    public function __construct(
        public int $id,
        public int $documentTypeId,
        public string $typeKey,
        public string $version,
        public bool $isActive,
        public bool $requiresAcceptance,
        public ?DateTimeImmutable $publishedAt,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
    ) {
    }

    public function isPublished(): bool
    {
        return $this->publishedAt !== null;
    }
}
