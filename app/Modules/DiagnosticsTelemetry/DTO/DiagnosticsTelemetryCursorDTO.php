<?php

declare(strict_types=1);

namespace Maatify\DiagnosticsTelemetry\DTO;

use DateTimeImmutable;

readonly class DiagnosticsTelemetryCursorDTO
{
    public function __construct(
        public DateTimeImmutable $lastOccurredAt,
        public int $lastId
    ) {
    }
}
