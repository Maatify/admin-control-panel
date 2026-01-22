<?php

declare(strict_types=1);

namespace App\Modules\TelemetryLogging\DTO;

use DateTimeImmutable;

readonly class TelemetryCursorDTO
{
    public function __construct(
        public DateTimeImmutable $lastOccurredAt,
        public int $lastId
    ) {
    }
}
