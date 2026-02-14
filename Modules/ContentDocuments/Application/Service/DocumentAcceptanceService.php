<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Application\Service;

use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentAcceptanceRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentAcceptanceServiceInterface;
use Maatify\ContentDocuments\Domain\Contract\Transaction\TransactionManagerInterface;
use Maatify\ContentDocuments\Domain\Entity\DocumentAcceptance;
use Maatify\ContentDocuments\Domain\Exception\DocumentAlreadyAcceptedException;
use Maatify\ContentDocuments\Domain\Exception\DocumentNotFoundException;
use Maatify\ContentDocuments\Domain\Exception\InvalidDocumentStateException;
use Maatify\ContentDocuments\Domain\ValueObject\ActorIdentity;
use Maatify\SharedCommon\Contracts\ClockInterface;

final readonly class DocumentAcceptanceService implements DocumentAcceptanceServiceInterface
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepository,
        private DocumentAcceptanceRepositoryInterface $acceptanceRepository,
        private TransactionManagerInterface $transactionManager,
        private ClockInterface $clock,
    ) {
    }

    public function accept(
        ActorIdentity $actor,
        int $documentId,
        ?string $ipAddress,
        ?string $userAgent
    ): void {
        $document = $this->documentRepository->findById($documentId);

        if ($document === null) {
            throw new DocumentNotFoundException();
        }

        if (!$document->isPublished()) {
            throw new InvalidDocumentStateException('Document is not published.');
        }

        if (!$document->isActive) {
            throw new InvalidDocumentStateException('Document is not active.');
        }

        if (!$document->requiresAcceptance) {
            throw new InvalidDocumentStateException('Document does not require acceptance.');
        }

        $owned = false;

        if (!$this->transactionManager->inTransaction()) {
            $this->transactionManager->begin();
            $owned = true;
        }

        try {
            $acceptance = new DocumentAcceptance(
                id: 0,
                actor: $actor,
                documentId: $documentId,
                version: $document->version,
                acceptedAt: $this->clock->now(),
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            try {
                $this->acceptanceRepository->save($acceptance);
            } catch (DocumentAlreadyAcceptedException) {
                // Idempotent: already accepted → treat as success
            }

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

    public function hasAccepted(
        ActorIdentity $actor,
        int $documentId
    ): bool {
        $document = $this->documentRepository->findById($documentId);

        if ($document === null) {
            throw new DocumentNotFoundException();
        }

        return $this->acceptanceRepository->hasAccepted(
            $actor,
            $documentId,
            $document->version
        );
    }
}
