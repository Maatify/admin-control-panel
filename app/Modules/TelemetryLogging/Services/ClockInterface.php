<?php

declare(strict_types=1);

namespace App\Modules\TelemetryLogging\Services;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
