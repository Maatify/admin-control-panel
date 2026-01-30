<?php

declare(strict_types=1);

namespace Maatify\SharedCommon\Contracts;

use DateTimeImmutable;
use DateTimeZone;

interface ClockInterface
{
    public function now(): DateTimeImmutable;

    public function getTimezone(): DateTimeZone;
}
