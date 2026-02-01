<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Engine;

use Maatify\RateLimiter\Contract\DeviceIdentityResolverInterface;
use Maatify\RateLimiter\Contract\RateLimiterInterface;
use Maatify\RateLimiter\DTO\RateLimitContextDTO;
use Maatify\RateLimiter\DTO\RateLimitRequestDTO;
use Maatify\RateLimiter\DTO\RateLimitResultDTO;
use Maatify\RateLimiter\Exception\RateLimiterException;

class RateLimiterEngine implements RateLimiterInterface
{
    private array $policies;

    public function __construct(
        private readonly DeviceIdentityResolverInterface $deviceResolver,
        private readonly EvaluationPipeline $pipeline,
        private readonly CircuitBreaker $circuitBreaker,
        private readonly FailureModeResolver $failureResolver,
        array $policies
    ) {
        foreach ($policies as $policy) {
            $this->policies[$policy->getName()] = $policy;
        }
    }

    public function limit(RateLimitContextDTO $context, RateLimitRequestDTO $request): RateLimitResultDTO
    {
        $policy = $this->policies[$request->policyName] ?? null;
        if (!$policy) {
            throw new RateLimiterException("Policy not found: {$request->policyName}");
        }

        try {
            $device = $this->deviceResolver->resolve($context);

            $result = $this->pipeline->process($policy, $context, $request, $device);

            $this->circuitBreaker->reportSuccess($policy->getName());

            return $result;
        } catch (\Throwable $e) {
            $this->circuitBreaker->reportFailure($policy->getName());

            $mode = $this->failureResolver->resolve($policy, $this->circuitBreaker);

            // Check Re-Entry Guard Violation
            // FailureModeResolver might return DEGRADED, FAIL_OPEN, FAIL_CLOSED.
            // If it returns FAIL_CLOSED due to Re-Entry, we need to add Metadata.
            // Wait, FailureModeResolver logic calls `cb->isReEntryGuardViolated`.
            // But FailureModeResolver returns string.
            // We need to know if it was Re-Entry.

            $metadata = [];
            if ($mode === 'FAIL_CLOSED' && $this->circuitBreaker->isReEntryGuardViolated($policy->getName())) {
                 $metadata['signal'] = 'CRITICAL_RE_ENTRY_VIOLATION';
            }

            // Local Fallback Check
            // Applies to DEGRADED and FAIL_OPEN
            if ($mode !== 'FAIL_CLOSED') {
                if (!LocalFallbackLimiter::check($policy->getName(), $mode, $context->ip, $context->accountId)) {
                    // Local limit exceeded. Fallback to safe block.
                    // Max Level L2.
                    return new RateLimitResultDTO(RateLimitResultDTO::DECISION_HARD_BLOCK, 2, 60, $mode, $metadata);
                }
            }

            if ($mode === 'FAIL_OPEN') {
                return new RateLimitResultDTO(RateLimitResultDTO::DECISION_ALLOW, 0, 0, $mode, $metadata);
            }

            // DEGRADED_MODE default is Block L2 if not allowed by local limiter?
            // LocalLimiter::check returned true -> Allow?
            // "DEGRADED_MODE ... Apply coarse, local, in-memory limits".
            // If limits NOT exceeded, we ALLOW.
            // If exceeded, we BLOCK.
            // My logic above: if (!check) return Block.
            // So if (check) return Allow?
            // Yes.
            if ($mode === 'DEGRADED_MODE') {
                 return new RateLimitResultDTO(RateLimitResultDTO::DECISION_ALLOW, 0, 0, $mode, $metadata);
            }

            // FAIL_CLOSED
            return new RateLimitResultDTO(RateLimitResultDTO::DECISION_HARD_BLOCK, 2, 600, $mode, $metadata);
        }
    }
}
