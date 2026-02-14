<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Contract\Service;

use Maatify\ContentDocuments\Domain\DTO\EnforcementResultDTO;
use Maatify\ContentDocuments\Domain\ValueObject\ActorIdentity;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;

interface DocumentEnforcementServiceInterface
{
    /**
     * Returns true if actor must accept active document version for a specific type.
     */
    public function requiresAcceptance(
        ActorIdentity $actor,
        DocumentTypeKey $typeKey
    ): bool;

    /**
     * Returns required acceptances across all document types.
     */
    public function enforcementResult(ActorIdentity $actor): EnforcementResultDTO;
}
