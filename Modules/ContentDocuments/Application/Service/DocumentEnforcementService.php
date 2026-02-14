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
        private DocumentTypeRepositoryInterface $documentTypeRepository,
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
        $types = $this->documentTypeRepository->findAll();

        $required = [];

        foreach ($types as $type) {
            $typeKey = $type->key;

            $doc = $this->documentRepository->findActiveByType($typeKey);

            if ($doc === null) {
                continue;
            }

            if (! $doc->isPublished()) {
                continue;
            }

            if (! $doc->requiresAcceptance) {
                continue;
            }

            $accepted = $this->acceptanceRepository->hasAccepted(
                $actor,
                $doc->id,
                $doc->version
            );

            if ($accepted) {
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
