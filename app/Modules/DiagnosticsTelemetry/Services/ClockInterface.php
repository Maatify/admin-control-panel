<?php

declare(strict_types=1);

namespace Maatify\DiagnosticsTelemetry\Services;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
