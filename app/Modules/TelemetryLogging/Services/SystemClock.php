<?php

declare(strict_types=1);

namespace App\Modules\TelemetryLogging\Services;

use DateTimeImmutable;

class SystemClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
