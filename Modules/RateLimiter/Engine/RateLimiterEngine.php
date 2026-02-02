<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Engine;

use Maatify\RateLimiter\Contract\BlockPolicyInterface;
use Maatify\RateLimiter\Contract\DeviceIdentityResolverInterface;
use Maatify\RateLimiter\Contract\FailureSignalEmitterInterface;
use Maatify\RateLimiter\Contract\RateLimiterInterface;
use Maatify\RateLimiter\DTO\FailureSignalDTO;
use Maatify\RateLimiter\DTO\RateLimitContextDTO;
use Maatify\RateLimiter\DTO\RateLimitMetadataDTO;
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
        private readonly FailureSignalEmitterInterface $emitter,
        array $policies
    ) {
        foreach ($policies as $policy) {
            $this->registerPolicy($policy);
        }
    }

    private function registerPolicy(BlockPolicyInterface $policy): void
    {
        if (in_array($policy->getName(), ['login_protection', 'otp_protection'])) {
            $thresholds = $policy->getScoreThresholds();
            if ($thresholds->k4 === null) {
                throw new RateLimiterException("Policy {$policy->getName()} invalid: Must enforce Account (K4) thresholds.");
            }
            if ($policy->getBudgetConfig() === null) {
                throw new RateLimiterException("Policy {$policy->getName()} invalid: Missing required BudgetConfig.");
            }
        }

        $mode = $policy->getFailureMode();
        if (!in_array($mode, ['FAIL_CLOSED', 'FAIL_OPEN'])) {
            throw new RateLimiterException("Policy {$policy->getName()} invalid: Unknown failure mode '$mode'.");
        }

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

            $signal = null;
            if ($mode === 'FAIL_CLOSED' && $this->circuitBreaker->isReEntryGuardViolated($policy->getName())) {
                 $signal = 'CRITICAL_RE_ENTRY_VIOLATION';
                 $this->emitter->emit(new FailureSignalDTO(FailureSignalDTO::TYPE_CB_RE_ENTRY_VIOLATION, $policy->getName()));
            }

            // Local Fallback Check
            if ($mode !== 'FAIL_CLOSED') {
                $normUa = DeviceIdentityResolver::normalizeUserAgent($context->ua);

                if (!LocalFallbackLimiter::check($policy->getName(), $mode, $context->ip, $context->accountId, $normUa)) {
                    $meta = new RateLimitMetadataDTO($signal, 'fallback_limit_exceeded');
                    return new RateLimitResultDTO(RateLimitResultDTO::DECISION_HARD_BLOCK, 2, 60, $mode, $meta);
                }
            }

            $meta = new RateLimitMetadataDTO($signal, null);

            if ($mode === 'FAIL_OPEN') {
                return new RateLimitResultDTO(RateLimitResultDTO::DECISION_ALLOW, 0, 0, $mode, $meta);
            }

            if ($mode === 'DEGRADED_MODE') {
                 return new RateLimitResultDTO(RateLimitResultDTO::DECISION_ALLOW, 0, 0, $mode, $meta);
            }

            // FAIL_CLOSED
            return new RateLimitResultDTO(RateLimitResultDTO::DECISION_HARD_BLOCK, 2, 600, $mode, $meta);
        }
    }
}
