<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Contract\Service;

use Maatify\ContentDocuments\Domain\DTO\DocumentDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentTranslationDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentVersionWithTranslationDTO;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;

interface DocumentQueryServiceInterface
{
    public function getActive(DocumentTypeKey $typeKey): ?DocumentDTO;

    public function getActiveTranslation(
        DocumentTypeKey $typeKey,
        int $languageId
    ): ?DocumentTranslationDTO;

    /**
     * @return list<DocumentDTO>
     */
    public function getVersions(DocumentTypeKey $typeKey): array;

    public function getById(int $documentId): ?DocumentDTO;

    public function getTranslationByDocumentId(
        int $documentId,
        int $languageId
    ): ?DocumentTranslationDTO;

    /**
     * @return list<DocumentVersionWithTranslationDTO>
     */
    public function getVersionsWithLanguage(
        DocumentTypeKey $typeKey,
        int $languageId
    ): array;
}
