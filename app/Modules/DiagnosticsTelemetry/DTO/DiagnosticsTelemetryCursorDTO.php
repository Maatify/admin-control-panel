<?php

declare(strict_types=1);

namespace App\Modules\DiagnosticsTelemetry\DTO;

use DateTimeImmutable;

readonly class DiagnosticsTelemetryCursorDTO
{
    public function __construct(
        public DateTimeImmutable $lastOccurredAt,
        public int $lastId
    ) {
    }
}
