<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface AuditTrailRecorderInterface
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function record(
        string $eventKey,
        string $actorType,
        ?int $actorId,
        string $entityType, // Made non-nullable to match Recorder requirement and Service usage
        ?int $entityId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $metadata = null
    ): void;
}
