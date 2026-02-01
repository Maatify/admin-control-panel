<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Contract;

use Maatify\RateLimiter\DTO\FailureSignalDTO;

interface FailureSignalEmitterInterface
{
    public function emit(FailureSignalDTO $signal): void;
}
