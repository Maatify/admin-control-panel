<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Application\Service;

use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTranslationRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentTranslationServiceInterface;
use Maatify\ContentDocuments\Domain\DTO\DocumentTranslationDTO;
use Maatify\ContentDocuments\Domain\Entity\DocumentTranslation;
use Maatify\ContentDocuments\Domain\Exception\DocumentNotFoundException;
use Maatify\ContentDocuments\Domain\Exception\DocumentVersionImmutableException;
use Maatify\SharedCommon\Contracts\ClockInterface;

final readonly class DocumentTranslationService implements DocumentTranslationServiceInterface
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepository,
        private DocumentTranslationRepositoryInterface $translationRepository,
        private ClockInterface $clock,
    ) {
    }

    public function save(DocumentTranslationDTO $translation): void
    {
        $document = $this->documentRepository->findById($translation->documentId);

        if ($document === null) {
            throw new DocumentNotFoundException();
        }

        // Enforce immutability
        if (
            $document->isActive
            || $document->isPublished()
            || $document->isArchived()
        ) {
            throw new DocumentVersionImmutableException();
        }

        $existing = $this->translationRepository->findByDocumentAndLanguage(
            $translation->documentId,
            $translation->languageId
        );

        $now = $this->clock->now();

        if ($existing === null) {
            // CREATE
            $this->translationRepository->create(
                new DocumentTranslation(
                    id: 0,
                    documentId: $translation->documentId,
                    languageId: $translation->languageId,
                    title: $translation->title,
                    metaTitle: $translation->metaTitle,
                    metaDescription: $translation->metaDescription,
                    content: $translation->content,
                    createdAt: $now,
                    updatedAt: null
                )
            );

            return;
        }

        // UPDATE
        $this->translationRepository->update(
            new DocumentTranslation(
                id: $existing->id,
                documentId: $existing->documentId,
                languageId: $existing->languageId,
                title: $translation->title,
                metaTitle: $translation->metaTitle,
                metaDescription: $translation->metaDescription,
                content: $translation->content,
                createdAt: $existing->createdAt,
                updatedAt: $now
            )
        );
    }
}
