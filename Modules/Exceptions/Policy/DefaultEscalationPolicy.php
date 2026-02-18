<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Policy;


use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Contracts\EscalationPolicyInterface;
use Maatify\Exceptions\Contracts\ErrorPolicyInterface;

final class DefaultEscalationPolicy implements EscalationPolicyInterface
{
    private function __construct()
    {
    }

    public static function default(): self
    {
        return new self();
    }

    public function escalateCategory(
        ErrorCategoryInterface $current,
        ErrorCategoryInterface $previous,
        ErrorPolicyInterface $policy
    ): ErrorCategoryInterface {
        $currentSeverity = $policy->severity($current);
        $previousSeverity = $policy->severity($previous);

        return $previousSeverity > $currentSeverity
            ? $previous
            : $current;
    }

    public function escalateHttpStatus(
        int $currentStatus,
        int $previousStatus
    ): int {
        return max($previousStatus, $currentStatus);
    }
}
