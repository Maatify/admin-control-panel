<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\Contract;

use DateTimeImmutable;

interface DeliveryOperationsClockInterface
{
    public function now(): DateTimeImmutable;
}
