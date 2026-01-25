<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface DiagnosticsTelemetryRecorderInterface
{
    public function record(
        string $eventKey,
        string $severity,
        string $actorType,
        ?int $actorId = null,
        ?int $durationMs = null,
        ?array $metadata = null
    ): void;
}
