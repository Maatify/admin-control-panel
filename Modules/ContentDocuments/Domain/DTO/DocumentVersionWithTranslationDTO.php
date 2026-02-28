<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\DTO;

final readonly class DocumentVersionWithTranslationDTO
{
    public function __construct(
        public DocumentDTO $document,
        public ?DocumentTranslationDTO $translation,
    ) {
    }
}
