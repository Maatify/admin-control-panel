<?php

declare(strict_types=1);

namespace Maatify\Catalog\Tests\Integration\Support;

use DateTimeImmutable;
use DateTimeZone;
use Maatify\SharedCommon\Contracts\ClockInterface;

final class FixedCatalogClock implements ClockInterface
{
    private DateTimeImmutable $now;

    public function __construct(string $value = '2026-01-03 00:00:00 UTC')
    {
        $this->now = new DateTimeImmutable($value);
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function getTimezone(): DateTimeZone
    {
        return $this->now->getTimezone();
    }
}
