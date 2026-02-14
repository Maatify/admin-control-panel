<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\DTO;

final readonly class RequiredAcceptanceDTO
{
    public function __construct(
        public int $documentId,
        public string $typeKey,
        public string $version,
    ) {
    }
}
