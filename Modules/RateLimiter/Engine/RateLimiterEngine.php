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

            if ($mode === 'FAIL_OPEN') {
                return new RateLimitResultDTO(RateLimitResultDTO::DECISION_ALLOW, 0, 0, $mode);
            }

            // DEGRADED_MODE or FAIL_CLOSED
            // Without a working store, we default to blocking to ensure security (Fail Closed).
            // Degraded mode implies we use a secondary store (memory).
            // Since strict memory store implementation is outside scope/not injected, we fallback to safe block.
            return new RateLimitResultDTO(RateLimitResultDTO::DECISION_HARD_BLOCK, 2, 60, $mode);
        }
    }
}
