<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Contracts;

interface SecuritySignalsRecorderInterface
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function record(
        string $signalType,
        string $severity,
        string $actorType,
        ?int $actorId,
        ?string $ipAddress,
        ?string $userAgent,
        ?array $metadata = null
    ): void;
}
