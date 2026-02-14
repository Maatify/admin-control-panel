<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\DTO;

final readonly class AcceptanceDTO
{
    public function __construct(
        public string $actorType,
        public int $actorId,
        public int $documentId,
        public string $version,
        public \DateTimeImmutable $acceptedAt,
        public ?string $ipAddress,
        public ?string $userAgent,
    ) {
    }
}
