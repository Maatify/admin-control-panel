<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Contracts;

interface AuditTrailRecorderInterface
{
    /**
     * @param array<string, mixed>|null $metadata
     */
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
