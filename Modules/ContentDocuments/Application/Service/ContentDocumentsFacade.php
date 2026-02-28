<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Application\Service;

use DateTimeImmutable;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\ContentDocumentsFacadeInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentAcceptanceServiceInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentEnforcementServiceInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentLifecycleServiceInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentQueryServiceInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentTranslationServiceInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentTypeServiceInterface;
use Maatify\ContentDocuments\Domain\DTO\AcceptanceReceiptDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentTranslationDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentTypeDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentVersionItemDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentViewDTO;
use Maatify\ContentDocuments\Domain\DTO\EnforcementResultDTO;
use Maatify\ContentDocuments\Domain\Exception\DocumentNotFoundException;
use Maatify\ContentDocuments\Domain\ValueObject\ActorIdentity;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;

final readonly class ContentDocumentsFacade implements ContentDocumentsFacadeInterface
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepository,
        private DocumentQueryServiceInterface $queryService,
        private DocumentAcceptanceServiceInterface $acceptanceService,
        private DocumentLifecycleServiceInterface $lifecycleService,
        private DocumentEnforcementServiceInterface $enforcementService,
        private DocumentTypeServiceInterface $documentTypeService,
        private DocumentTranslationServiceInterface $translationService,
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
            $translation = $this->queryService->getTranslationByDocumentId($doc->id, $languageId);
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
        return $this->queryService->getVersionItems($typeKey);
    }

    public function getDocumentById(int $documentId): ?DocumentDTO
    {
        return $this->queryService->getById($documentId);
    }

    public function createVersion(
        DocumentTypeKey $typeKey,
        DocumentVersion $version,
        bool $requiresAcceptance
    ): int {
        return $this->lifecycleService->createVersion($typeKey, $version, $requiresAcceptance);
    }

    public function publish(int $documentId, DateTimeImmutable $publishedAt): void
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
        return $this->queryService->getTranslationByDocumentId($documentId, $languageId);
    }

    public function saveTranslation(DocumentTranslationDTO $translation): void
    {
        $this->translationService->save($translation);
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

    public function archive(int $documentId, DateTimeImmutable $archivedAt): void
    {
        $this->lifecycleService->archive($documentId, $archivedAt);
    }

    /**
     * @return list<DocumentTypeDTO>
     */
    public function listDocumentTypes(): array
    {
        return $this->documentTypeService->list();
    }

    /**
     * @return list<DocumentTypeKey>
     */
    public function listRegisteredDocumentTypeKeys(): array
    {
        return $this->documentTypeService->listRegisteredKeys();
    }

    public function getDocumentTypeById(int $typeId): ?DocumentTypeDTO
    {
        return $this->documentTypeService->getById($typeId);
    }

    public function getDocumentTypeByKey(DocumentTypeKey $key): ?DocumentTypeDTO
    {
        return $this->documentTypeService->getByKey($key);
    }

    public function createDocumentType(
        DocumentTypeKey $key,
        bool $requiresAcceptanceDefault,
        bool $isSystem
    ): int {
        return $this->documentTypeService->create($key, $requiresAcceptanceDefault, $isSystem);
    }

    public function updateDocumentType(
        int $typeId,
        bool $requiresAcceptanceDefault,
        bool $isSystem
    ): void {
        $this->documentTypeService->update($typeId, $requiresAcceptanceDefault, $isSystem);
    }
}
