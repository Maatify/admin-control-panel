<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface AuditTrailRecorderInterface
{
    public function record(
        string $eventKey,
        string $actorType,
        ?int $actorId,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $metadata = null
    ): void;
}
