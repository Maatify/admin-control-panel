<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\Contract;

use Maatify\BehaviorTrace\DTO\BehaviorTraceRecordDTO;

interface BehaviorTraceLoggerInterface
{
    public function write(BehaviorTraceRecordDTO $dto): void;
}
