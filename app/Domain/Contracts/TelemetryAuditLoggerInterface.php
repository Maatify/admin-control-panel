<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\LegacyAuditEventDTO;

/**
 * Telemetry/Legacy Audit Logger.
 * Best-effort. NEVER relied upon for correctness.
 * NEVER part of security logic.
 */
interface TelemetryAuditLoggerInterface
{
    public function log(LegacyAuditEventDTO $event): void;
}
