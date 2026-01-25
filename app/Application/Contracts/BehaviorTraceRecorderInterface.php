<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface BehaviorTraceRecorderInterface
{
    public function record(
        string $action,
        string $actorType,
        ?int $actorId,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null
    ): void;
}
