<?php

declare(strict_types=1);

namespace App\Modules\DiagnosticsTelemetry\Contract;

use App\Modules\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryCursorDTO;
use App\Modules\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryEventDTO;

interface DiagnosticsTelemetryQueryInterface
{
    /**
     * @param DiagnosticsTelemetryCursorDTO|null $cursor
     * @param int $limit
     * @return iterable<DiagnosticsTelemetryEventDTO>
     */
    public function read(?DiagnosticsTelemetryCursorDTO $cursor, int $limit = 100): iterable;
}
