<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use DateTimeImmutable;
use DateTimeZone;

interface ClockInterface
{
    public function now(): DateTimeImmutable;

    public function getTimezone(): DateTimeZone;
}
