<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Contracts;

interface EscalationPolicyInterface
{
    public function escalateCategory(
        ErrorCategoryInterface $current,
        ErrorCategoryInterface $previous,
        ErrorPolicyInterface $policy
    ): ErrorCategoryInterface;

    public function escalateHttpStatus(
        int $currentStatus,
        int $previousStatus
    ): int;
}

