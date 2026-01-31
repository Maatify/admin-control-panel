<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Contracts;

interface DiagnosticsTelemetryRecorderInterface
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function record(
        string $eventKey,
        string $severity,
        string $actorType,
        ?int $actorId = null,
        ?int $durationMs = null,
        ?array $metadata = null
    ): void;
}
