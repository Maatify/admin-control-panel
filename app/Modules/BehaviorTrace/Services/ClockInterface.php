<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\Services;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
