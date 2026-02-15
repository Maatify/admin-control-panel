<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Application\Service;

use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTranslationRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentQueryServiceInterface;
use Maatify\ContentDocuments\Domain\DTO\DocumentDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentTranslationDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentVersionWithTranslationDTO;
use Maatify\ContentDocuments\Domain\Entity\Document;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;

final class DocumentQueryService implements DocumentQueryServiceInterface
{
    public function __construct(
        private readonly DocumentRepositoryInterface $documentRepository,
        private readonly DocumentTranslationRepositoryInterface $translationRepository,
    ) {
    }

    public function getActive(DocumentTypeKey $typeKey): ?DocumentDTO
    {
        $document = $this->documentRepository->findActiveByType($typeKey);

        if ($document === null) {
            return null;
        }

        return $this->mapDocument($document);
    }

    public function getActiveTranslation(
        DocumentTypeKey $typeKey,
        int $languageId
    ): ?DocumentTranslationDTO {
        $document = $this->documentRepository->findActiveByType($typeKey);

        if ($document === null) {
            return null;
        }

        return $this->getTranslationByDocumentId($document->id, $languageId);
    }

    /**
     * @return list<DocumentDTO>
     */
    public function getVersions(DocumentTypeKey $typeKey): array
    {
        return array_map(
            fn (Document $document) => $this->mapDocument($document),
            $this->documentRepository->findVersionsByType($typeKey)
        );
    }

    public function getById(int $documentId): ?DocumentDTO
    {
        $document = $this->documentRepository->findById($documentId);

        if ($document === null) {
            return null;
        }

        return $this->mapDocument($document);
    }

    public function getTranslationByDocumentId(
        int $documentId,
        int $languageId
    ): ?DocumentTranslationDTO {
        $translation = $this->translationRepository
            ->findByDocumentAndLanguage($documentId, $languageId);

        if ($translation === null) {
            return null;
        }

        return new DocumentTranslationDTO(
            documentId: $translation->documentId,
            languageId: $translation->languageId,
            title: $translation->title,
            metaTitle: $translation->metaTitle,
            metaDescription: $translation->metaDescription,
            content: $translation->content,
        );
    }

    /**
     * @return list<DocumentVersionWithTranslationDTO>
     */
    public function getVersionsWithLanguage(
        DocumentTypeKey $typeKey,
        int $languageId
    ): array {
        $versions = $this->documentRepository->findVersionsWithTranslationsByTypeAndLanguage($typeKey, $languageId);

        $out = [];

        foreach ($versions as $document) {
            $docDto = $this->mapDocument($document);  // هنا نقوم بتمرير كائن Document بدلاً من مصفوفة
            $translation = $this->getTranslationByDocumentId($document->id, $languageId);

            $out[] = new DocumentVersionWithTranslationDTO(
                document: $docDto,
                translation: $translation
            );
        }

        return $out;
    }

    private function mapDocument(Document $document): DocumentDTO
    {
        return new DocumentDTO(
            id: $document->id,
            documentTypeId: $document->documentTypeId,
            typeKey: (string) $document->typeKey,
            version: (string) $document->version,
            isActive: $document->isActive,
            requiresAcceptance: $document->requiresAcceptance,
            publishedAt: $document->publishedAt,
            createdAt: $document->createdAt,
            updatedAt: $document->updatedAt,
        );
    }
}
