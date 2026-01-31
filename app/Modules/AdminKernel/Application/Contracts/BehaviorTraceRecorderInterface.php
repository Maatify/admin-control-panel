<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Contracts;

interface BehaviorTraceRecorderInterface
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function record(
        string $action,
        string $actorType,
        ?int $actorId,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null
    ): void;
}
