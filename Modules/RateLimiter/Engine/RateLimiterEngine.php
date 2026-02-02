<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Engine;

use Maatify\RateLimiter\Contract\BlockPolicyInterface;
use Maatify\RateLimiter\Contract\DeviceIdentityResolverInterface;
use Maatify\RateLimiter\Contract\RateLimiterInterface;
use Maatify\RateLimiter\DTO\RateLimitContextDTO;
use Maatify\RateLimiter\DTO\RateLimitRequestDTO;
use Maatify\RateLimiter\DTO\RateLimitResultDTO;
use Maatify\RateLimiter\Exception\RateLimiterException;
use Maatify\RateLimiter\Device\DeviceIdentityResolver;

class RateLimiterEngine implements RateLimiterInterface
{
    /** @var array<string, BlockPolicyInterface> */
    private array $policies = [];

    public function __construct(
        private readonly DeviceIdentityResolverInterface $deviceResolver,
        private readonly EvaluationPipeline $pipeline,
        private readonly CircuitBreaker $circuitBreaker,
        private readonly FailureModeResolver $failureResolver,
        array $policies
    ) {
        foreach ($policies as $policy) {
            $this->registerPolicy($policy);
        }
    }

    private function registerPolicy(BlockPolicyInterface $policy): void
    {
        // Task F: Policy Validation

        // 1. Auth-related policies must enforce account-level protection
        if (in_array($policy->getName(), ['login_protection', 'otp_protection'])) {
            $thresholds = $policy->getScoreThresholds();
            if ($thresholds->k4 === null) {
                throw new RateLimiterException("Policy {$policy->getName()} invalid: Must enforce Account (K4) thresholds.");
            }
        }

        // 2. Budgets required where mandated
        if (in_array($policy->getName(), ['login_protection', 'otp_protection'])) {
            if ($policy->getBudgetConfig() === null) {
                throw new RateLimiterException("Policy {$policy->getName()} invalid: Missing required BudgetConfig.");
            }
        }

        // 3. Explicit Failure Semantics
        $mode = $policy->getFailureMode();
        if (!in_array($mode, ['FAIL_CLOSED', 'FAIL_OPEN'])) {
            throw new RateLimiterException("Policy {$policy->getName()} invalid: Unknown failure mode '$mode'.");
        }

        // 4. Cannot weaken defaults (Check existence of thresholds)
        // Strictly enforcing presence of critical scope thresholds based on policy type
        if ($policy->getName() === 'api_heavy_protection') {
             $thresholds = $policy->getScoreThresholds();
             if ($thresholds->k1 === null || $thresholds->k2 === null) {
                 throw new RateLimiterException("Policy {$policy->getName()} invalid: Must enforce K1 and K2.");
             }
        }

        $this->policies[$policy->getName()] = $policy;
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

            // Metadata for output DTO - strictly DTO based metadata handling if possible, but DTO allows array for metadata
            // as generic payload.
            $metadata = [];
            if ($mode === 'FAIL_CLOSED' && $this->circuitBreaker->isReEntryGuardViolated($policy->getName())) {
                 $metadata['signal'] = 'CRITICAL_RE_ENTRY_VIOLATION';
            }

            // Local Fallback Check
            if ($mode !== 'FAIL_CLOSED') {
                // Must pass normalized UA for K2 check
                // We access the static normalizer directly for robustness in fallback
                // Assuming DeviceIdentityResolver has public static normalizeUserAgent as implied by trace context or need to add it?
                // The current file content showed `DeviceIdentityResolver::normalizeUserAgent` usage in the trace so it likely exists or I should add it.
                // Wait, DeviceIdentityResolver is in Device/DeviceIdentityResolver.php.
                // Let's verify if normalizeUserAgent is static public.
                // In a previous step I modified `LocalFallbackLimiter` to have its own `normalizeUa`.
                // So I can use `LocalFallbackLimiter`'s internal normalization or pass raw?
                // `LocalFallbackLimiter::check` accepts raw `ua` and normalizes it internally now.

                if (!LocalFallbackLimiter::check($policy->getName(), $mode, $context->ip, $context->accountId, $context->ua)) {
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
