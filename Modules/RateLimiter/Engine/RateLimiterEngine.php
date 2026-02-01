<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Engine;

use Maatify\RateLimiter\Contract\DeviceIdentityResolverInterface;
use Maatify\RateLimiter\Contract\RateLimiterInterface;
use Maatify\RateLimiter\DTO\RateLimitContextDTO;
use Maatify\RateLimiter\DTO\RateLimitRequestDTO;
use Maatify\RateLimiter\DTO\RateLimitResultDTO;
use Maatify\RateLimiter\Exception\RateLimiterException;
use Maatify\RateLimiter\Device\DeviceIdentityResolver;

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

            $metadata = [];
            if ($mode === 'FAIL_CLOSED' && $this->circuitBreaker->isReEntryGuardViolated($policy->getName())) {
                 $metadata['signal'] = 'CRITICAL_RE_ENTRY_VIOLATION';
            }

            // Local Fallback Check
            if ($mode !== 'FAIL_CLOSED') {
                // Must pass normalized UA for K2 check
                // We access the static normalizer directly for robustness in fallback
                $normUa = DeviceIdentityResolver::normalizeUserAgent($context->ua);

                if (!LocalFallbackLimiter::check($policy->getName(), $mode, $context->ip, $context->accountId, $normUa)) {
                    return new RateLimitResultDTO(RateLimitResultDTO::DECISION_HARD_BLOCK, 2, 60, $mode, $metadata);
                }
            }

            if ($mode === 'FAIL_OPEN') {
                return new RateLimitResultDTO(RateLimitResultDTO::DECISION_ALLOW, 0, 0, $mode, $metadata);
            }

            if ($mode === 'DEGRADED_MODE') {
                 return new RateLimitResultDTO(RateLimitResultDTO::DECISION_ALLOW, 0, 0, $mode, $metadata);
            }

            // FAIL_CLOSED
            return new RateLimitResultDTO(RateLimitResultDTO::DECISION_HARD_BLOCK, 2, 600, $mode, $metadata);
        }
    }
}
