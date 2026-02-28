<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\DTO;

final readonly class ActivateDocumentDTO
{
    public function __construct(
        public int $documentId,
    ) {
    }
}
