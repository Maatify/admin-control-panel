<?php

declare(strict_types=1);

namespace Maatify\AuthoritativeAudit\Services;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
