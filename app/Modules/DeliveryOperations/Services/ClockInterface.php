<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\Services;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
