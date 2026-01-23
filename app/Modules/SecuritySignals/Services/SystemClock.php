<?php

declare(strict_types=1);

namespace Maatify\SecuritySignals\Services;

use DateTimeImmutable;
use DateTimeZone;

class SystemClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
