<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Contract;

use Closure;

/** Transaction boundary owned by the Category application orchestration. */
interface CategoryTransactionInterface
{
    /**
     * @template TResult
     * @param Closure(): TResult $operation
     * @return TResult
     */
    public function run(Closure $operation): mixed;
}
