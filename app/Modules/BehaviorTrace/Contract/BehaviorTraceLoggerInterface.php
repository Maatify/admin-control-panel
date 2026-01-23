<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\Contract;

use Maatify\BehaviorTrace\DTO\BehaviorTraceEventDTO;

interface BehaviorTraceLoggerInterface
{
    public function write(BehaviorTraceEventDTO $dto): void;
}
