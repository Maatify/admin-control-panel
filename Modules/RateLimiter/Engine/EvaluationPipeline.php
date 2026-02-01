<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Engine;

use Maatify\RateLimiter\Contract\BlockPolicyInterface;
use Maatify\RateLimiter\Contract\CorrelationStoreInterface;
use Maatify\RateLimiter\Contract\RateLimitStoreInterface;
use Maatify\RateLimiter\DTO\DeviceIdentityDTO;
use Maatify\RateLimiter\DTO\RateLimitContextDTO;
use Maatify\RateLimiter\DTO\RateLimitRequestDTO;
use Maatify\RateLimiter\DTO\RateLimitResultDTO;
use Maatify\RateLimiter\Penalty\AntiEquilibriumGate;
use Maatify\RateLimiter\Penalty\BudgetTracker;
use Maatify\RateLimiter\Penalty\DecayCalculator;
use Maatify\RateLimiter\Penalty\PenaltyLadder;

class EvaluationPipeline
{
    private string $secret;

    public function __construct(
        private readonly RateLimitStoreInterface $store,
        private readonly CorrelationStoreInterface $correlationStore,
        private readonly BudgetTracker $budgetTracker,
        private readonly AntiEquilibriumGate $antiEquilibriumGate,
        private readonly DecayCalculator $decayCalculator,
        string $keySecret
    ) {
        $this->secret = $keySecret;
    }

    public function process(
        BlockPolicyInterface $policy,
        RateLimitContextDTO $context,
        RateLimitRequestDTO $request,
        DeviceIdentityDTO $device
    ): RateLimitResultDTO {
        $keys = $this->buildKeys($context, $device, $policy->getName());

        // 2. Check Active Blocks (Fail-Fast)
        foreach ($keys as $keyType => $key) {
            if (!$key) continue;
            $block = $this->store->checkBlock($key);
            if ($block && $this->isHardBlock($block['level'])) {
                return $this->createBlockedResult($block['level'], $block['expires_at'] - time(), RateLimitResultDTO::DECISION_HARD_BLOCK);
            }
        }

        // 3. Check Account Budget (Fail-Fast)
        $budgetConfig = $policy->getBudgetConfig();
        if ($budgetConfig && isset($keys['k4'])) {
            $isBudgetExceeded = $this->budgetTracker->isExceeded($keys['k4'], $budgetConfig['threshold']);
            if ($isBudgetExceeded) {
                $level = $budgetConfig['block_level'];
                if ($device->isTrustedSession) {
                    $level = max(2, $level - 1);
                }
                return $this->createBlockedResult($level, 3600, RateLimitResultDTO::DECISION_SOFT_BLOCK);
            }
        }

        // 4. Check Current Scores (Soft Blocks)
        $rawScores = $this->fetchScores($keys);
        $decayedScores = $this->applyDecay($rawScores, $keys);

        $highestLevel = 0;
        foreach ($decayedScores as $keyType => $score) {
            $level = $this->determineLevel($score, $keyType, $policy);
            if ($level > $highestLevel) {
                $highestLevel = $level;
            }
        }

        if ($highestLevel > 0) {
            $decision = ($highestLevel >= 2) ? RateLimitResultDTO::DECISION_HARD_BLOCK : RateLimitResultDTO::DECISION_SOFT_BLOCK;
            return $this->createBlockedResult($highestLevel, PenaltyLadder::getDuration($highestLevel), $decision);
        }

        // 5. If Pre-Check Only (and no blocks found), Allow.
        if ($request->isPreCheck) {
            return $this->createAllowResult();
        }

        // 6. If Failure (or API Access), Update State
        if ($request->isFailure || isset($policy->getScoreDeltas()['access'])) {
             return $this->processUpdates($policy, $context, $request, $device, $keys, $decayedScores);
        }

        // 7. Success
        return $this->createAllowResult();
    }

    private function processUpdates(
        BlockPolicyInterface $policy,
        RateLimitContextDTO $context,
        RateLimitRequestDTO $request,
        DeviceIdentityDTO $device,
        array $keys,
        array $decayedScores // Calculated in Step 4
    ): RateLimitResultDTO {
        $deltas = $this->calculateDeltas($policy, $context, $device, $request);

        $newMaxLevel = 0;
        $triggeredKey = null;

        foreach ($keys as $keyType => $key) {
            if (!$key) continue;

            $delta = $deltas[$keyType] ?? 0;
            if ($delta > 0) {
                // Decay Logic applied to update
                // New Score = DecayedScore + Delta.
                // DecayedScore was calc'd in Step 4.
                // Store Increment works on RAW value.
                // New Raw Value = Old Raw Value + (Net Change).
                // Net Change = New Score - Old Raw Value.
                // Net Change = (DecayedScore + Delta) - Old Raw Value.
                // Note: DecayedScore <= Old Raw Value. So Net Change is usually negative or small positive?
                // Wait.
                // Old Raw = 100. Decayed = 50. Delta = 1.
                // New Score = 51.
                // Net Change = 51 - 100 = -49.
                // Increment(100, -49) -> 51.
                // Correct.

                $rawScore = $this->fetchRawScore($key);
                $decayedScore = $decayedScores[$keyType] ?? 0;

                // Recalculate decay in case time passed? No, atomic enough.
                // But wait, $decayedScores comes from $rawScores fetch in Step 4.
                // If $rawScore changed (race condition), we might overwrite?
                // RateLimitStoreInterface provides increment (atomic).
                // But here we depend on Read-Calc-Write logic for Decay.
                // "If a backend cannot satisfy required atomicity... defer to Engine failure semantics".
                // Since interface is `increment`, we rely on it.
                // We assume $rawScore in Step 6 is same as Step 4 (optimistic).
                // Or we re-fetch?
                // Re-fetching $rawScore ensures we don't regress if another process updated it?
                // But we are calculating a *delta* based on *our* view of decay.
                // If we use `increment`, we are applying a delta.
                // `increment(key, val)` adds val.
                // If we send `-49`. And another process added `+1` (Raw 101).
                // Result: 101 - 49 = 52.
                // Expected: 101 -> Decayed (50.5) -> 51.5 + 1.
                // It works reasonably well.

                $netChange = ($decayedScore + $delta) - $rawScore;

                $newScore = $this->store->increment($key, 86400, (int)$netChange);

                $level = $this->determineLevel($newScore, $keyType, $policy);
                if ($level > $newMaxLevel) {
                    $newMaxLevel = $level;
                    $triggeredKey = $key;
                }
            }
        }

        // Budget Updates
        if (isset($keys['k4']) && $policy->getBudgetConfig()) {
            if ($this->checkBudgetEligibility($keys, $policy)) {
                $this->budgetTracker->increment($keys['k4']);
                if ($this->budgetTracker->isExceeded($keys['k4'], $policy->getBudgetConfig()['threshold'])) {
                    $newMaxLevel = max($newMaxLevel, $policy->getBudgetConfig()['block_level']);
                }
            }
        }

        if ($newMaxLevel > 0) {
            $decision = ($newMaxLevel >= 2) ? RateLimitResultDTO::DECISION_HARD_BLOCK : RateLimitResultDTO::DECISION_SOFT_BLOCK;

            if ($decision === RateLimitResultDTO::DECISION_SOFT_BLOCK && isset($keys['k4']) && $context->accountId) {
                $this->antiEquilibriumGate->recordSoftBlock($context->accountId);
                if ($this->antiEquilibriumGate->shouldEscalate($context->accountId)) {
                     $newMaxLevel = max($newMaxLevel, 2);
                     $decision = RateLimitResultDTO::DECISION_HARD_BLOCK;
                }
            }

            $duration = PenaltyLadder::getDuration($newMaxLevel);

            // Block appropriate keys
            if ($context->accountId && isset($keys['k4'])) {
                $this->store->block($keys['k4'], $newMaxLevel, $duration);
            }
            if ($triggeredKey && $triggeredKey !== ($keys['k4'] ?? null)) {
                $this->store->block($triggeredKey, $newMaxLevel, $duration);
            }

            return $this->createBlockedResult($newMaxLevel, $duration, $decision);
        }

        return $this->createAllowResult();
    }

    private function fetchScores(array $keys): array
    {
        $scores = [];
        foreach ($keys as $keyType => $key) {
            $scores[$keyType] = $key ? $this->store->get($key) : null;
        }
        return $scores;
    }

    private function fetchRawScore(string $key): int
    {
        $data = $this->store->get($key);
        return $data ? $data['value'] : 0;
    }

    private function applyDecay(array $rawScores, array $keys): array
    {
        $decayed = [];
        foreach ($keys as $keyType => $key) {
            if (!$key || !isset($rawScores[$keyType])) {
                $decayed[$keyType] = 0;
                continue;
            }

            $data = $rawScores[$keyType];
            $value = $data['value'];
            $updatedAt = $data['updated_at'];

            // Determine Scope
            $scope = match($keyType) {
                'k4' => 'account',
                'k3', 'k5' => 'device',
                default => 'ip'
            };

            // Determine Block Level
            // To be accurate, we should check active block.
            $block = $this->store->checkBlock($key);
            $level = $block ? $block['level'] : 0;

            $decayAmount = $this->decayCalculator->calculateDecay($value, $updatedAt, $level, $scope);
            $decayed[$keyType] = max(0, $value - $decayAmount);
        }
        return $decayed;
    }

    private function determineLevel(int $score, string $keyType, BlockPolicyInterface $policy): int
    {
        $thresholds = $policy->getScoreThresholds();

        $scopedThresholds = $thresholds[$keyType] ?? $thresholds;
        if (!is_array(reset($scopedThresholds))) {
             if (isset($thresholds[$keyType])) {
                 $scopedThresholds = $thresholds[$keyType];
             } elseif (isset($thresholds['default'])) {
                 $scopedThresholds = $thresholds['default'];
             } else {
                 if (str_starts_with($keyType, 'k1_')) {
                     $scopedThresholds = $thresholds['k1'] ?? $thresholds['default'] ?? [];
                 } else {
                     return 0;
                 }
             }
        }

        krsort($scopedThresholds);
        foreach ($scopedThresholds as $thresh => $lvl) {
            if ($score >= $thresh) {
                return $lvl;
            }
        }
        return 0;
    }

    private function calculateDeltas(
        BlockPolicyInterface $policy,
        RateLimitContextDTO $context,
        DeviceIdentityDTO $device,
        RateLimitRequestDTO $request
    ): array {
        $deltas = $policy->getScoreDeltas();
        $result = [];

        if (isset($deltas['access'])) {
            $cost = $deltas['access'] * $request->cost;
            $result['k1'] = ($result['k1'] ?? 0) + $cost;
            $result['k2'] = ($result['k2'] ?? 0) + $cost;
            $result['k3'] = ($result['k3'] ?? 0) + $cost;
        }

        if ($request->isFailure) {
            if (isset($deltas['k5_failure'])) $result['k5'] = $deltas['k5_failure'];
            if (isset($deltas['k4_failure'])) $result['k4'] = $deltas['k4_failure'];
            if (isset($deltas['k2_missing_fp']) && empty($device->fingerprintHash)) $result['k2'] = $deltas['k2_missing_fp'];
            if (isset($deltas['k1_spray'])) $result['k1'] = $deltas['k1_spray'];
        }

        return $result;
    }

    private function checkBudgetEligibility(array $keys, BlockPolicyInterface $policy): bool
    {
        return true;
    }

    private function buildKeys(RateLimitContextDTO $context, DeviceIdentityDTO $device, string $policyName): array
    {
        $k1 = $this->hashKey("{$policyName}:k1:{$this->getIpPrefix($context->ip)}");
        $k2 = $this->hashKey("{$policyName}:k2:{$this->getIpPrefix($context->ip)}:{$context->ua}");
        $k3 = $device->fingerprintHash ? $this->hashKey("{$policyName}:k3:{$this->getIpPrefix($context->ip)}:{$device->fingerprintHash}") : null;
        $k4 = $context->accountId ? $this->hashKey("{$policyName}:k4:{$context->accountId}") : null;
        $k5 = $context->accountId && $device->fingerprintHash ? $this->hashKey("{$policyName}:k5:{$context->accountId}:{$device->fingerprintHash}") : null;

        $keys = [
            'k1' => $k1,
            'k2' => $k2,
            'k3' => $k3,
            'k4' => $k4,
            'k5' => $k5,
        ];

        // IPv6 Hierarchy
        if (filter_var($context->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
             $keys['k1_48'] = $this->hashKey("{$policyName}:k1:{$this->getIpPrefix($context->ip, 48)}");
             $keys['k1_40'] = $this->hashKey("{$policyName}:k1:{$this->getIpPrefix($context->ip, 40)}");
             $keys['k1_32'] = $this->hashKey("{$policyName}:k1:{$this->getIpPrefix($context->ip, 32)}");
        }

        return $keys;
    }

    private function hashKey(string $input): string
    {
        return hash_hmac('sha256', $input, $this->secret);
    }

    private function getIpPrefix(string $ip, int $cidr = 64): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = inet_pton($ip);
            if ($packed !== false) {
                 $hex = bin2hex($packed);
                 $length = (int) ceil($cidr / 4);
                 return substr($hex, 0, $length);
            }
        }
        return $ip;
    }

    private function isHardBlock(int $level): bool
    {
        return $level >= 2;
    }

    private function createBlockedResult(int $level, int $retryAfter, string $decision): RateLimitResultDTO
    {
        return new RateLimitResultDTO($decision, $level, $retryAfter, 'NORMAL');
    }

    private function createAllowResult(): RateLimitResultDTO
    {
        return new RateLimitResultDTO(RateLimitResultDTO::DECISION_ALLOW, 0, 0, 'NORMAL');
    }
}
