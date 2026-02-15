<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Application\Service;

use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentAcceptanceRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentTypeRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\DocumentEnforcementServiceInterface;
use Maatify\ContentDocuments\Domain\DTO\EnforcementResultDTO;
use Maatify\ContentDocuments\Domain\DTO\RequiredAcceptanceDTO;
use Maatify\ContentDocuments\Domain\ValueObject\ActorIdentity;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;

final readonly class DocumentEnforcementService implements DocumentEnforcementServiceInterface
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepository,
        private DocumentAcceptanceRepositoryInterface $acceptanceRepository,
    ) {
    }

    public function requiresAcceptance(
        ActorIdentity $actor,
        DocumentTypeKey $typeKey
    ): bool {
        $document = $this->documentRepository->findActiveByType($typeKey);

        if ($document === null) {
            return false;
        }

        if (! $document->isPublished()) {
            return false;
        }

        if (! $document->requiresAcceptance) {
            return false;
        }

        return ! $this->acceptanceRepository->hasAccepted(
            $actor,
            $document->id,
            $document->version
        );
    }

    public function enforcementResult(ActorIdentity $actor): EnforcementResultDTO
    {
        // 1️⃣ Get all active + published + requires_acceptance documents
        $documents = $this->documentRepository->findActivePublishedRequiringAcceptance();

        if ($documents === []) {
            return new EnforcementResultDTO(
                requiresAcceptance: false,
                requiredDocuments: []
            );
        }

        // 2️⃣ Get all accepted document (id + version) pairs for actor
        $accepted = $this->acceptanceRepository
            ->findAcceptedDocumentVersions($actor);

        // Build O(1) lookup: document_id|version
        $acceptedLookup = [];

        foreach ($accepted as $row) {
            $key = $row['document_id'] . '|' . $row['version'];
            $acceptedLookup[$key] = true;
        }

        $required = [];

        foreach ($documents as $doc) {

            $key = $doc->id . '|' . (string) $doc->version;

            // If this exact version was accepted → skip
            if (isset($acceptedLookup[$key])) {
                continue;
            }

            $required[] = new RequiredAcceptanceDTO(
                documentId: $doc->id,
                typeKey: (string) $doc->typeKey,
                version: (string) $doc->version
            );
        }

        return new EnforcementResultDTO(
            requiresAcceptance: $required !== [],
            requiredDocuments: $required
        );
    }

}
