<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Contract\Service;

use Maatify\ContentDocuments\Domain\ValueObject\ActorIdentity;

interface DocumentAcceptanceServiceInterface
{
    public function accept(
        ActorIdentity $actor,
        int $documentId,
        ?string $ipAddress,
        ?string $userAgent
    ): \DateTimeImmutable;

    public function hasAccepted(
        ActorIdentity $actor,
        int $documentId
    ): bool;
}
