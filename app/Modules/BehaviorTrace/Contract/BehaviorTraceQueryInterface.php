<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\Contract;

use Maatify\BehaviorTrace\DTO\BehaviorTraceCursorDTO;
use Maatify\BehaviorTrace\DTO\BehaviorTraceEventDTO;

interface BehaviorTraceQueryInterface
{
    /**
     * @param BehaviorTraceCursorDTO|null $cursor
     * @param int $limit
     * @return iterable<BehaviorTraceEventDTO>
     */
    public function read(?BehaviorTraceCursorDTO $cursor, int $limit = 100): iterable;
}
