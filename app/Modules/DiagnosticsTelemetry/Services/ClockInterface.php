<?php

declare(strict_types=1);

namespace App\Modules\DiagnosticsTelemetry\Services;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
