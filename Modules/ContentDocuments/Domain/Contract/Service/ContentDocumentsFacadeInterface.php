<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Contract\Service;

use DateTimeImmutable;
use Maatify\ContentDocuments\Domain\DTO\AcceptanceReceiptDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentTranslationDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentTypeDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentViewDTO;
use Maatify\ContentDocuments\Domain\DTO\DocumentVersionItemDTO;
use Maatify\ContentDocuments\Domain\DTO\EnforcementResultDTO;
use Maatify\ContentDocuments\Domain\ValueObject\ActorIdentity;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;

interface ContentDocumentsFacadeInterface
{
    public function getActiveDocument(
        DocumentTypeKey $typeKey,
        ?int $languageId
    ): ?DocumentViewDTO;

    /**
     * @return list<DocumentVersionItemDTO>
     */
    public function listVersions(DocumentTypeKey $typeKey): array;

    public function getDocumentById(int $documentId): ?DocumentDTO;

    public function createVersion(
        DocumentTypeKey $typeKey,
        DocumentVersion $version,
        bool $requiresAcceptance
    ): int;

    public function publish(int $documentId, \DateTimeImmutable $publishedAt): void;

    public function activate(int $documentId): void;

    public function deactivate(int $documentId): void;

    public function getTranslation(
        int $documentId,
        int $languageId
    ): ?DocumentTranslationDTO;

    public function saveTranslation(DocumentTranslationDTO $translation): void;

    public function acceptActive(
        ActorIdentity $actor,
        DocumentTypeKey $typeKey,
        ?string $ipAddress,
        ?string $userAgent
    ): AcceptanceReceiptDTO;

    public function enforcementResult(ActorIdentity $actor): EnforcementResultDTO;

    public function archive(int $documentId, \DateTimeImmutable $archivedAt): void;

    /**
     * @return list<DocumentTypeDTO>
     */
    public function listTypes(): array;

    public function getTypeById(int $typeId): ?DocumentTypeDTO;

    public function getTypeByKey(DocumentTypeKey $key): ?DocumentTypeDTO;

    public function createType(
        DocumentTypeKey $key,
        bool $requiresAcceptanceDefault,
        bool $isSystem
    ): int;

    public function updateType(
        int $typeId,
        bool $requiresAcceptanceDefault,
        bool $isSystem
    ): void;

}
