<?php

declare(strict_types=1);

namespace App\Infrastructure\Clock;

use App\Domain\Contracts\ClockInterface;
use DateTimeImmutable;
use DateTimeZone;

final class SystemClock implements ClockInterface
{
    private DateTimeZone $timezone;

    public function __construct(DateTimeZone $timezone)
    {
        $this->timezone = $timezone;
    }

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->timezone);
    }

    public function getTimezone(): DateTimeZone
    {
        return $this->timezone;
    }
}
