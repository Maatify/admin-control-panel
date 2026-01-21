<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\TelemetryDTO;
use App\Domain\Exception\Telemetry\TelemetryStorageException;

/**
 * Telemetry Logger.
 * Best-effort. NEVER relied upon for correctness.
 * NEVER part of security logic.
 */
interface TelemetryLoggerInterface
{
    /**
     * @throws TelemetryStorageException
     */
    public function log(TelemetryDTO $event): void;
}
