<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface SecuritySignalsRecorderInterface
{
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
