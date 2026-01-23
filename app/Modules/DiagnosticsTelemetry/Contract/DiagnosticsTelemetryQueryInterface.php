<?php

declare(strict_types=1);

namespace Maatify\DiagnosticsTelemetry\Contract;

use Maatify\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryCursorDTO;
use Maatify\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryEventDTO;

interface DiagnosticsTelemetryQueryInterface
{
    /**
     * @param DiagnosticsTelemetryCursorDTO|null $cursor
     * @param int $limit
     * @return iterable<DiagnosticsTelemetryEventDTO>
     */
    public function read(?DiagnosticsTelemetryCursorDTO $cursor, int $limit = 100): iterable;
}
