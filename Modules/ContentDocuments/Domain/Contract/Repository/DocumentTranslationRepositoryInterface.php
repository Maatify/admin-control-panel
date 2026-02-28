<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Contract\Repository;

use Maatify\ContentDocuments\Domain\Entity\DocumentTranslation;

interface DocumentTranslationRepositoryInterface
{
    public function findByDocumentAndLanguage(
        int $documentId,
        int $languageId
    ): ?DocumentTranslation;

    /**
     * @return list<DocumentTranslation>
     */
    public function findByDocument(int $documentId): array;
    public function create(DocumentTranslation $translation): int;
    public function update(DocumentTranslation $translation): void;

    /**
     * Bulk load translations for many documents in one query.
     *
     * @param list<int> $documentIds
     * @return array<int, DocumentTranslation> Map keyed by document_id
     */
    public function findByDocumentIdsAndLanguage(array $documentIds, int $languageId): array;
}
