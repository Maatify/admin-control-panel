<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\DTO;

use DateTimeImmutable;

final readonly class PublishDocumentDTO
{
    public function __construct(
        public int $documentId,
        public DateTimeImmutable $publishedAt,
    ) {
    }
}
