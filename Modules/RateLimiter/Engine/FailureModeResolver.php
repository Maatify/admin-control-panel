<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Engine;

use Maatify\RateLimiter\Contract\BlockPolicyInterface;
use Maatify\RateLimiter\DTO\FailureStateDTO;

class FailureModeResolver
{
    public function resolve(BlockPolicyInterface $policy, CircuitBreaker $cb): string
    {
        $state = $cb->getState($policy->getName());

        if ($state->state === FailureStateDTO::STATE_OPEN) {
            if ($cb->isReEntryGuardViolated($policy->getName())) {
                return 'FAIL_CLOSED';
            }
            // If policy prefers FAIL_OPEN (API), respect it (with guardrails)
            if ($policy->getFailureMode() === 'FAIL_OPEN') {
                return 'FAIL_OPEN';
            }
            return 'DEGRADED_MODE';
        }

        return $policy->getFailureMode();
    }
}
