<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Entity;

use Maatify\ContentDocuments\Domain\ValueObject\ActorIdentity;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;

final readonly class DocumentAcceptance
{
    public function __construct(
        public int $id,
        public ActorIdentity $actor,
        public int $documentId,
        public DocumentVersion $version,
        public \DateTimeImmutable $acceptedAt,
        public ?string $ipAddress,
        public ?string $userAgent,
    ) {
    }
}
