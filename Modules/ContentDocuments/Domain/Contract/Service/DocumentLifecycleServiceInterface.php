<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Contract\Service;

use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;

interface DocumentLifecycleServiceInterface
{
    public function publish(int $documentId, \DateTimeImmutable $publishedAt): void;

    public function activate(int $documentId): void;

    public function deactivate(int $documentId): void;

    public function createVersion(
        DocumentTypeKey $typeKey,
        DocumentVersion $version,
        bool $requiresAcceptance
    ): int; // returns new document id
}
