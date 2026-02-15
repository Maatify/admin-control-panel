<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Application\Service;

use DateTimeImmutable;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTypeRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentLifecycleServiceInterface;
use Maatify\ContentDocuments\Domain\Contract\Transaction\TransactionManagerInterface;
use Maatify\ContentDocuments\Domain\Exception\DocumentNotFoundException;
use Maatify\ContentDocuments\Domain\Exception\DocumentTypeNotFoundException;
use Maatify\ContentDocuments\Domain\Exception\InvalidDocumentStateException;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;

final readonly class DocumentLifecycleService implements DocumentLifecycleServiceInterface
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepository,
        private DocumentTypeRepositoryInterface $documentTypeRepository,
        private TransactionManagerInterface $transactionManager,
    ) {}

    public function createVersion(
        DocumentTypeKey $typeKey,
        DocumentVersion $version,
        bool $requiresAcceptance
    ): int {
        $documentType = $this->documentTypeRepository->findByKey($typeKey);

        if ($documentType === null) {
            throw new DocumentTypeNotFoundException('Document type not found.');
        }

        return $this->documentRepository->create(
            $documentType->id,
            $typeKey,
            $version,
            $requiresAcceptance
        );
    }

    public function publish(int $documentId, \DateTimeImmutable $publishedAt): void
    {
        $document = $this->documentRepository->findByIdNonArchived($documentId);

        if ($document === null) {
            $maybeArchived = $this->documentRepository->findById($documentId);
            if ($maybeArchived !== null) {
                throw new InvalidDocumentStateException('Cannot publish archived document.');
            }
            throw new DocumentNotFoundException();
        }

        if ($document->isPublished()) {
            return;
        }

        $this->documentRepository->publish($documentId, $publishedAt);
    }

    public function activate(int $documentId): void
    {
        $document = $this->documentRepository->findByIdNonArchived($documentId);

        if ($document === null) {
            $maybeArchived = $this->documentRepository->findById($documentId);
            if ($maybeArchived !== null) {
                throw new InvalidDocumentStateException('Cannot activate archived document.');
            }
            throw new DocumentNotFoundException();
        }

        if (!$document->isPublished()) {
            throw new InvalidDocumentStateException('Cannot activate unpublished document.');
        }

        if ($document->isActive) {
            return; // already active → nothing to do
        }

        $owned = false;

        if (!$this->transactionManager->inTransaction()) {
            $this->transactionManager->begin();
            $owned = true;
        }

        try {
            $this->documentRepository->deactivateAllByTypeId($document->documentTypeId);
            $this->documentRepository->activate($documentId);

            if ($owned) {
                $this->transactionManager->commit();
            }
        } catch (\Throwable $e) {
            if ($owned) {
                $this->transactionManager->rollback();
            }
            throw $e;
        }
    }

    public function deactivate(int $documentId): void
    {
        $document = $this->documentRepository->findByIdNonArchived($documentId);

        if ($document === null) {
            $maybeArchived = $this->documentRepository->findById($documentId);
            if ($maybeArchived !== null) {
                throw new InvalidDocumentStateException('Cannot deactivate archived document.');
            }
            throw new DocumentNotFoundException();
        }

        $this->documentRepository->deactivate($documentId);
    }

    public function archive(int $documentId, DateTimeImmutable $archivedAt): void
    {
        // Idempotent archive:
        // - if already archived => no-op
        // - if missing => NotFound
        $document = $this->documentRepository->findByIdNonArchived($documentId);

        if ($document === null) {
            $maybeArchived = $this->documentRepository->findById($documentId);
            if ($maybeArchived !== null) {
                return; // already archived => no-op
            }
            throw new DocumentNotFoundException();
        }

        $owned = false;

        if (!$this->transactionManager->inTransaction()) {
            $this->transactionManager->begin();
            $owned = true;
        }

        try {
            // Safety: ensure it can’t remain active (even if repo already enforces it)
            if ($document->isActive) {
                $this->documentRepository->deactivate($documentId);
            }

            $this->documentRepository->archive($documentId, $archivedAt);

            if ($owned) {
                $this->transactionManager->commit();
            }
        } catch (\Throwable $e) {
            if ($owned) {
                $this->transactionManager->rollback();
            }
            throw $e;
        }
    }
}
