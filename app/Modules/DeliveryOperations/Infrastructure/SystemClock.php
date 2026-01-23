<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\Infrastructure;

use DateTimeImmutable;
use Maatify\DeliveryOperations\Contract\DeliveryOperationsClockInterface;

class SystemClock implements DeliveryOperationsClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
