<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\Contract;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
