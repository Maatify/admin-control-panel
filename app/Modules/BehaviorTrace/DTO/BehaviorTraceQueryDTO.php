<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\DTO;

use DateTimeImmutable;

readonly class BehaviorTraceQueryDTO
{
    public function __construct(
        public DateTimeImmutable $lastOccurredAt,
        public int $lastId
    ) {
    }
}
