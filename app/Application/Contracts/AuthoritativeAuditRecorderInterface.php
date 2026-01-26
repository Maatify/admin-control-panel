<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface AuthoritativeAuditRecorderInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function record(
        string $action,
        string $targetType,
        ?int $targetId,
        string $riskLevel,
        string $actorType,
        ?int $actorId,
        array $payload
    ): void;
}
