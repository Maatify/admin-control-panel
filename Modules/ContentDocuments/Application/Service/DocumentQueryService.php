<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Application\Service;

use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTranslationRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentQueryServiceInterface;
use Maatify\ContentDocuments\Domain\DTO\DocumentDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentTranslationDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentVersionItemDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentVersionWithTranslationDTO;
use Maatify\ContentDocuments\Domain\Entity\Document;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;

final readonly class DocumentQueryService implements DocumentQueryServiceInterface
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepository,
        private DocumentTranslationRepositoryInterface $translationRepository,
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

    /**
     * @return list<DocumentVersionItemDTO>
     */
    public function getVersionItems(DocumentTypeKey $typeKey): array
    {
        $versions = $this->documentRepository->findVersionsByType($typeKey);

        $out = [];

        foreach ($versions as $doc) {
            $out[] = new DocumentVersionItemDTO(
                documentId: $doc->id,
                version: (string) $doc->version,
                isActive: $doc->isActive,
                requiresAcceptance: $doc->requiresAcceptance,
                publishedAt: $doc->publishedAt,
                createdAt: $doc->createdAt
            );
        }

        return $out;
    }


    public function getById(int $documentId): ?DocumentDTO
    {
        $document = $this->documentRepository->findByIdNonArchived($documentId);

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
            createdAt: $translation->createdAt,
            updatedAt: $translation->updatedAt
        );
    }

    /**
     * @return list<DocumentVersionWithTranslationDTO>
     */
    public function getVersionsWithLanguage(
        DocumentTypeKey $typeKey,
        int $languageId
    ): array {
        // Load versions once
        $versions = $this->documentRepository->findVersionsByType($typeKey);

        $out = [];

        $ids = array_map(static fn (Document $d): int => $d->id, $versions);
        $translationsMap = $this->translationRepository->findByDocumentIdsAndLanguage($ids, $languageId);

        foreach ($versions as $document) {
            $docDto = $this->mapDocument($document);  // هنا نقوم بتمرير كائن Document بدلاً من مصفوفة

            $trEntity = $translationsMap[$document->id] ?? null;
            $translation = $trEntity === null
                ? null
                : new DocumentTranslationDTO(
                    documentId: $trEntity->documentId,
                    languageId: $trEntity->languageId,
                    title: $trEntity->title,
                    metaTitle: $trEntity->metaTitle,
                    metaDescription: $trEntity->metaDescription,
                    content: $trEntity->content,
                    createdAt: $trEntity->createdAt,
                    updatedAt: $trEntity->updatedAt
                );

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
