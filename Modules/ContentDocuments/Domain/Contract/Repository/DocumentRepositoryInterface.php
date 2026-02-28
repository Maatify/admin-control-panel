<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Contract\Repository;

use DateTimeImmutable;
use Maatify\ContentDocuments\Domain\Entity\Document;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;

interface DocumentRepositoryInterface
{
    public function create(
        int $documentTypeId,
        DocumentTypeKey $typeKey,
        DocumentVersion $version,
        bool $requiresAcceptance
    ): int;

    public function findById(int $id): ?Document;

    public function findByTypeAndVersion(
        DocumentTypeKey $typeKey,
        DocumentVersion $version
    ): ?Document;

    public function findByIdNonArchived(int $id): ?Document;

    public function findActiveByType(DocumentTypeKey $typeKey): ?Document;

    /**
     * @return list<Document>
     */
    public function findVersionsByType(DocumentTypeKey $typeKey): array;

    public function archive(int $documentId, DateTimeImmutable $archivedAt): void;

    public function publish(int $documentId, \DateTimeImmutable $publishedAt): void;

    public function activate(int $documentId): void;

    public function deactivate(int $documentId): void;

    public function deactivateAllByTypeId(int $documentTypeId): void;

    /**
     * @return list<Document>
     */
    public function findActivePublishedRequiringAcceptance(): array;

}
