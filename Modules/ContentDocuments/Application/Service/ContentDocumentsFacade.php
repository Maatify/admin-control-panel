<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Application\Service;

use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTranslationRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\ContentDocumentsFacadeInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentAcceptanceServiceInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentEnforcementServiceInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentLifecycleServiceInterface;
use Maatify\ContentDocuments\Domain\DTO\AcceptanceReceiptDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentTranslationDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentViewDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentVersionItemDTO;
use Maatify\ContentDocuments\Domain\DTO\EnforcementResultDTO;
use Maatify\ContentDocuments\Domain\Entity\DocumentAcceptance;
use Maatify\ContentDocuments\Domain\Exception\DocumentNotFoundException;
use Maatify\ContentDocuments\Domain\ValueObject\ActorIdentity;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;
use Maatify\SharedCommon\Contracts\ClockInterface;

final readonly class ContentDocumentsFacade implements ContentDocumentsFacadeInterface
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepository,
        private DocumentTranslationRepositoryInterface $translationRepository,
        private DocumentAcceptanceServiceInterface $acceptanceService,
        private DocumentLifecycleServiceInterface $lifecycleService,
        private DocumentEnforcementServiceInterface $enforcementService,
        private ClockInterface $clock,
    ) {}

    public function getActiveDocument(
        DocumentTypeKey $typeKey,
        ?int $languageId
    ): ?DocumentViewDTO {
        $doc = $this->documentRepository->findActiveByType($typeKey);

        if ($doc === null) {
            return null;
        }

        $translation = null;

        if ($languageId !== null) {
            $t = $this->translationRepository->findByDocumentAndLanguage($doc->id, $languageId);

            if ($t !== null) {
                $translation = new DocumentTranslationDTO(
                    documentId: $t->documentId,
                    languageId: $t->languageId,
                    title: $t->title,
                    metaTitle: $t->metaTitle,
                    metaDescription: $t->metaDescription,
                    content: $t->content
                );
            }
        }

        return new DocumentViewDTO(
            documentId: $doc->id,
            documentTypeId: $doc->documentTypeId,
            typeKey: (string) $doc->typeKey,
            version: (string) $doc->version,
            isActive: $doc->isActive,
            requiresAcceptance: $doc->requiresAcceptance,
            publishedAt: $doc->publishedAt,
            translation: $translation
        );
    }

    /**
     * @return list<DocumentVersionItemDTO>
     */
    public function listVersions(DocumentTypeKey $typeKey): array
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

    public function getDocumentById(int $documentId): ?DocumentDTO
    {
        $doc = $this->documentRepository->findById($documentId);

        if ($doc === null) {
            return null;
        }

        return new DocumentDTO(
            id: $doc->id,
            documentTypeId: $doc->documentTypeId,
            typeKey: (string) $doc->typeKey,
            version: (string) $doc->version,
            isActive: $doc->isActive,
            requiresAcceptance: $doc->requiresAcceptance,
            publishedAt: $doc->publishedAt,
            createdAt: $doc->createdAt,
            updatedAt: $doc->updatedAt
        );
    }

    public function createVersion(
        DocumentTypeKey $typeKey,
        DocumentVersion $version,
        bool $requiresAcceptance
    ): int {
        return $this->lifecycleService->createVersion($typeKey, $version, $requiresAcceptance);
    }

    public function publish(int $documentId, \DateTimeImmutable $publishedAt): void
    {
        $this->lifecycleService->publish($documentId, $publishedAt);
    }

    public function activate(int $documentId): void
    {
        $this->lifecycleService->activate($documentId);
    }

    public function deactivate(int $documentId): void
    {
        $this->lifecycleService->deactivate($documentId);
    }

    public function getTranslation(
        int $documentId,
        int $languageId
    ): ?DocumentTranslationDTO {
        $t = $this->translationRepository->findByDocumentAndLanguage($documentId, $languageId);

        if ($t === null) {
            return null;
        }

        return new DocumentTranslationDTO(
            documentId: $t->documentId,
            languageId: $t->languageId,
            title: $t->title,
            metaTitle: $t->metaTitle,
            metaDescription: $t->metaDescription,
            content: $t->content
        );
    }

    public function saveTranslation(DocumentTranslationDTO $translation): void
    {
        // assumes DocumentTranslation entity exists and repo->save() handles upsert
        $this->translationRepository->save(
            new \Maatify\ContentDocuments\Domain\Entity\DocumentTranslation(
                id: 0,
                documentId: $translation->documentId,
                languageId: $translation->languageId,
                title: $translation->title,
                metaTitle: $translation->metaTitle,
                metaDescription: $translation->metaDescription,
                content: $translation->content,
                createdAt: $this->clock->now(),
                updatedAt: null
            )
        );
    }

    public function acceptActive(
        ActorIdentity $actor,
        DocumentTypeKey $typeKey,
        ?string $ipAddress,
        ?string $userAgent
    ): AcceptanceReceiptDTO {

        $doc = $this->documentRepository->findActiveByType($typeKey);

        if ($doc === null || ! $doc->isPublished()) {
            throw new DocumentNotFoundException();
        }

        $acceptedAt = $this->acceptanceService->accept(
            $actor,
            $doc->id,
            $ipAddress,
            $userAgent
        );

        return new AcceptanceReceiptDTO(
            documentId: $doc->id,
            typeKey: (string) $doc->typeKey,
            version: (string) $doc->version,
            acceptedAt: $acceptedAt
        );
    }

    public function enforcementResult(ActorIdentity $actor): EnforcementResultDTO
    {
        return $this->enforcementService->enforcementResult($actor);
    }
}
