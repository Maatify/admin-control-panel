<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Contract\Repository;

use Maatify\ContentDocuments\Domain\Entity\DocumentAcceptance;
use Maatify\ContentDocuments\Domain\ValueObject\ActorIdentity;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;

interface DocumentAcceptanceRepositoryInterface
{
    public function hasAccepted(
        ActorIdentity $actor,
        int $documentId,
        DocumentVersion $version
    ): bool;

    public function save(DocumentAcceptance $acceptance): void;

    /**
     * @return list<DocumentAcceptance>
     */
    public function findByActor(ActorIdentity $actor): array;
}
