<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\DTO;

use DateTimeImmutable;

final readonly class DocumentViewDTO
{
    public function __construct(
        public int $documentId,
        public int $documentTypeId,
        public string $typeKey,
        public string $version,
        public bool $isActive,
        public bool $requiresAcceptance,
        public ?DateTimeImmutable $publishedAt,
        public ?DocumentTranslationDTO $translation,
    ) {}
}
