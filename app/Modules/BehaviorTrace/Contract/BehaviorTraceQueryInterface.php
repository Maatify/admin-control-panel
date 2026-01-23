<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\Contract;

use Maatify\BehaviorTrace\DTO\BehaviorTraceQueryDTO;

interface BehaviorTraceQueryInterface
{
    /**
     * @param BehaviorTraceQueryDTO|null $cursor
     * @param int $limit
     * @return iterable<\Maatify\BehaviorTrace\DTO\BehaviorTraceViewDTO>
     */
    public function read(?BehaviorTraceQueryDTO $cursor, int $limit = 100): iterable;
}
