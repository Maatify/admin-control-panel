<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\DTO;

final readonly class CreateDocumentVersionDTO
{
    public function __construct(
        public string $typeKey,
        public string $version,
        public bool $requiresAcceptance,
    ) {
    }
}
