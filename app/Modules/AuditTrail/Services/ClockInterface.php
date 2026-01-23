<?php

declare(strict_types=1);

namespace Maatify\AuditTrail\Services;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
