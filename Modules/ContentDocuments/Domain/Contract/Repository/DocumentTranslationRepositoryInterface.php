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

    public function save(DocumentTranslation $translation): void;
}
