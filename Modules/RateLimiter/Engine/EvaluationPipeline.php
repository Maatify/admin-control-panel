<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Engine;

use Maatify\RateLimiter\Contract\BlockPolicyInterface;
use Maatify\RateLimiter\Contract\CorrelationStoreInterface;
use Maatify\RateLimiter\Contract\RateLimitStoreInterface;
use Maatify\RateLimiter\Device\EphemeralBucket;
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
        private readonly EphemeralBucket $ephemeralBucket,
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
        // 1. Build Keys for Active Block Check (Original Hash)
        // Use normalized UA for K2 from DeviceDTO
        $realKeys = $this->buildKeys($context, $device->normalizedUa, $device->fingerprintHash, $policy->getName());

        // 2. Check Active Blocks (Fail-Fast) on Real Keys
        if ($blocked = $this->checkActiveBlocks($realKeys)) {
            return $blocked;
        }

        // 3. Check Account Budget (Fail-Fast)
        if ($blocked = $this->checkBudget($policy, $realKeys, $device)) {
            return $blocked;
        }

        // 4. Resolve Ephemeral Status
        // Do NOT create new K3/K5 if ephemeral.
        $ephemeralState = $device->fingerprintHash
            ? $this->ephemeralBucket->check($context, $device->fingerprintHash)
            : null;

        $isEphemeral = $ephemeralState?->isEphemeral ?? false;

        $effectiveKeys = $realKeys;
        if ($isEphemeral) {
             unset($effectiveKeys['k3'], $effectiveKeys['k5']);
        }

        // 5. Fetch & Decay Scores (Using Effective Keys)
        $rawScores = $this->fetchScores($effectiveKeys);
        $decayedScores = $this->applyDecay($rawScores, $effectiveKeys);

        // 6. Check Thresholds (Soft Blocks)
        if ($blocked = $this->checkThresholds($policy, $decayedScores, $effectiveKeys, $device)) {
            return $blocked;
        }

        // 7. Check Correlation Rules
        if ($blocked = $this->checkCorrelationRules($context, $device, $policy->getName(), $isEphemeral)) {
             return $blocked;
        }

        // 8. New Device Flood (5.4)
        if ($ephemeralState && $context->accountId) {
             if ($ephemeralState->accountDeviceCount >= 6) {
                  // Check for specific "Flood Stage" marker (Rule 5.4 marker)
                  // "Continued flood trigger must be tied to the 5.4 soft-block marker/window"
                  $floodKey = "flood_stage:acc:{$context->accountId}";
                  $isFloodStage = $this->correlationStore->getWatchFlag($floodKey) > 0;

                  if ($isFloodStage) {
                       // Continued flood -> HARD_BLOCK (each new K5).
                       // Block durations MUST follow Penalty Ladder exactly (L2 = 60s)
                       $duration = PenaltyLadder::getDuration(2);
                       if (isset($realKeys['k5'])) {
                           $this->store->block($realKeys['k5'], 2, $duration);
                       }
                       return $this->createBlockedResult(2, $duration, RateLimitResultDTO::DECISION_HARD_BLOCK);
                  }

                  // Apply Soft Block Account (L1 = 15s)
                  // Set marker for 15 minutes (900s) to define the flood window
                  $duration = PenaltyLadder::getDuration(1);
                  $this->store->block($realKeys['k4'], 1, $duration);
                  $this->correlationStore->incrementWatchFlag($floodKey, 900);

                  return $this->createBlockedResult(1, $duration, RateLimitResultDTO::DECISION_SOFT_BLOCK);
             }
        }

        // 9. Pre-Check Only
        if ($request->isPreCheck) {
            return $this->createAllowResult();
        }

        // 10. Process Updates (Failure / Access)
        if ($request->isFailure || isset($policy->getScoreDeltas()['access'])) {
             return $this->processUpdates($policy, $context, $request, $device, $effectiveKeys, $decayedScores);
        }

        return $this->createAllowResult();
    }

    // --- Steps ---

    private function checkActiveBlocks(array $keys): ?RateLimitResultDTO
    {
        foreach ($keys as $keyType => $key) {
            if (!$key) continue;
            $block = $this->store->checkBlock($key);
            if ($block && $block['level'] >= 2) {
                return $this->createBlockedResult($block['level'], $block['expires_at'] - time(), RateLimitResultDTO::DECISION_HARD_BLOCK);
            }
        }
        return null;
    }

    private function checkBudget(BlockPolicyInterface $policy, array $keys, DeviceIdentityDTO $device): ?RateLimitResultDTO
    {
        $config = $policy->getBudgetConfig();
        if ($config && isset($keys['k4'])) {
            if ($this->budgetTracker->isExceeded($keys['k4'], $config['threshold'])) {
                $level = $config['block_level'];
                if ($device->isTrustedSession) {
                    $level = max(2, $level - 1);
                }
                return $this->createBlockedResult($level, 3600, RateLimitResultDTO::DECISION_SOFT_BLOCK);
            }
        }
        return null;
    }

    private function checkThresholds(BlockPolicyInterface $policy, array $scores, array $keys, DeviceIdentityDTO $device): ?RateLimitResultDTO
    {
        $highestLevel = 0;
        foreach ($scores as $keyType => $score) {
            $level = $this->determineLevel($score, $keyType, $policy);

            // Confidence Constraint (K3)
            if ($keyType === 'k3' && $device->confidence === 'LOW' && $level >= 2) {
                // Passive-only fingerprints MUST NOT trigger HARD_BLOCK.
                $level = 1;
            }

            if ($level > $highestLevel) {
                $highestLevel = $level;
            }
        }

        if ($highestLevel > 0) {
            $decision = ($highestLevel >= 2) ? RateLimitResultDTO::DECISION_HARD_BLOCK : RateLimitResultDTO::DECISION_SOFT_BLOCK;
            return $this->createBlockedResult($highestLevel, PenaltyLadder::getDuration($highestLevel), $decision);
        }
        return null;
    }

    private function checkCorrelationRules(RateLimitContextDTO $context, DeviceIdentityDTO $device, string $policyName, bool $isEphemeral): ?RateLimitResultDTO
    {
        $k2 = $this->hashKey("{$policyName}:k2:{$this->getIpPrefix($context->ip)}:{$device->normalizedUa}");
        if ($device->fingerprintHash) {
             $count = $this->correlationStore->addDistinct("churn:{$k2}", $device->fingerprintHash, 600);
             if ($count >= 3) {
                 $this->store->block($k2, 2, 60); // L2 = 60s
                 return $this->createBlockedResult(2, 60, RateLimitResultDTO::DECISION_HARD_BLOCK);
             }
        }

        if ($device->fingerprintHash) {
            $k3_raw = "dilution:{$device->fingerprintHash}";
            // Dilution Rule: "distinct(IP) >= 6 within 10m"
            // "Two consecutive 10-minute windows for MEDIUM+ confidence"
            $count = $this->correlationStore->addDistinct($k3_raw, $context->ip, 600);

            if ($count >= 6) {
                $shouldBlock = false;
                $targetKey = null;

                if ($device->confidence === 'LOW') {
                    // Downgrade to HARD_BLOCK(IP+UA) (K2)
                    $targetKey = $k2;
                    $shouldBlock = true;
                } else {
                    // MEDIUM+ -> Check 2 consecutive windows
                    // We use a flag "dilution_warn" set in previous window?
                    // Window logic is tricky with sliding distinct count.
                    // But if we use "flag", we can approximate "Two consecutive windows".
                    // Logic:
                    // 1. If "dilution_warn" exists AND we hit threshold again -> Block.
                    // 2. Set "dilution_warn" (TTL 10m).
                    $warnKey = "dilution_warn:{$device->fingerprintHash}";
                    $warn = $this->correlationStore->getWatchFlag($warnKey);

                    if ($warn > 0) {
                        // Confirmed in second window
                        $targetKey = $this->hashKey("{$policyName}:k3:{$this->getIpPrefix($context->ip)}:{$device->fingerprintHash}");
                        $shouldBlock = true;
                    } else {
                        // First detection, set warning
                        $this->correlationStore->incrementWatchFlag($warnKey, 600);
                    }
                }

                if ($shouldBlock && $targetKey) {
                    // If targetKey is K3 and we are Ephemeral, we cannot block K3 (it doesn't exist/we shouldn't create it).
                    // But we can return Block Result.
                    // If K3 is ephemeral, we should probably block K2 or IP?
                    // Or just return Block.
                    if ($isEphemeral && strpos($targetKey, ':k3:') !== false) {
                        // Fallback to K2 block if K3 is ephemeral/unstable?
                        // Or just return Block result without storing Block on K3?
                        // "Ephemeral routing MUST NOT create or update K3/K5".
                        // Blocking K3 creates a key.
                        // So we block K2.
                        $targetKey = $k2;
                    }

                    $this->store->block($targetKey, 2, 60);
                    return $this->createBlockedResult(2, 60, RateLimitResultDTO::DECISION_HARD_BLOCK);
                }
            }
        }

        return null;
    }

    private function processUpdates(
        BlockPolicyInterface $policy,
        RateLimitContextDTO $context,
        RateLimitRequestDTO $request,
        DeviceIdentityDTO $device,
        array $keys,
        array $currentScores
    ): RateLimitResultDTO {
        $deltas = $this->calculateDeltas($policy, $context, $device, $request);

        // Repeated Missing FP Logic
        if ($request->isFailure && empty($device->fingerprintHash) && $context->accountId) {
             $key = "last_missing_fp:acc:{$context->accountId}";
             $last = $this->store->get($key);
             if ($last && (time() - $last['value']) <= 1800) {
                 if (isset($policy->getScoreDeltas()['k4_repeated_missing_fp'])) {
                      $deltas['k4'] = ($deltas['k4'] ?? 0) + $policy->getScoreDeltas()['k4_repeated_missing_fp'];
                 }
             }
             $this->store->set($key, time(), 3600);
        }

        $newMaxLevel = 0;
        $triggeredKey = null;

        foreach ($keys as $keyType => $key) {
            if (!$key) continue;

            $deltaKey = $keyType;
            if (str_starts_with($keyType, 'k1_')) $deltaKey = 'k1';

            $delta = $deltas[$deltaKey] ?? 0;
            if ($delta > 0) {
                $raw = $this->fetchRawScore($key);
                $decayed = $currentScores[$keyType] ?? 0;
                $net = ($decayed + $delta) - $raw;

                $newScore = $this->store->increment($key, 86400, (int)$net);

                $level = $this->determineLevel($newScore, $keyType, $policy);

                // N-1 Watch Logic
                $thresholds = $this->getScopedThresholds($keyType, $policy);
                foreach ($thresholds as $t => $l) {
                    if ($newScore == $t - 1) {
                         $wKey = "watch:{$key}";
                         $flags = $this->correlationStore->incrementWatchFlag($wKey, 1800);
                         if ($flags >= 2) {
                             $level = max($level, $l);
                         }
                    }
                }

                if ($level > $newMaxLevel) {
                    $newMaxLevel = $level;
                    $triggeredKey = $key;
                }
            }
        }

        // Budget Updates (K4)
        if (isset($keys['k4']) && $policy->getBudgetConfig()) {
            $this->budgetTracker->increment($keys['k4']);
            if ($this->budgetTracker->isExceeded($keys['k4'], $policy->getBudgetConfig()['threshold'])) {
                $newMaxLevel = max($newMaxLevel, $policy->getBudgetConfig()['block_level']);
            }
        }

        if ($newMaxLevel > 0) {
            $decision = ($newMaxLevel >= 2) ? RateLimitResultDTO::DECISION_HARD_BLOCK : RateLimitResultDTO::DECISION_SOFT_BLOCK;

            // Anti-Equilibrium
            if ($decision === RateLimitResultDTO::DECISION_SOFT_BLOCK && isset($keys['k4']) && $context->accountId) {
                $this->antiEquilibriumGate->recordSoftBlock($context->accountId);
                if ($this->antiEquilibriumGate->shouldEscalate($context->accountId)) {
                     $newMaxLevel = max($newMaxLevel, 2);
                     $decision = RateLimitResultDTO::DECISION_HARD_BLOCK;
                }
            }

            $duration = PenaltyLadder::getDuration($newMaxLevel);

            // Apply Blocks
            if ($context->accountId && isset($keys['k4'])) {
                $this->store->block($keys['k4'], $newMaxLevel, $duration);
            }
            if ($policy->getName() === 'api_heavy_protection') {
                 if (isset($keys['k1'])) $this->store->block($keys['k1'], $newMaxLevel, $duration);
                 if (isset($keys['k2'])) $this->store->block($keys['k2'], $newMaxLevel, $duration);
                 if (isset($keys['k3']) && $device->confidence !== 'LOW') {
                     $this->store->block($keys['k3'], $newMaxLevel, $duration);
                 } elseif (isset($keys['k3']) && $device->confidence === 'LOW') {
                     if (isset($keys['k2'])) $this->store->block($keys['k2'], $newMaxLevel, $duration);
                 }
            }

            return $this->createBlockedResult($newMaxLevel, $duration, $decision);
        }

        return $this->createAllowResult();
    }

    // --- Helpers ---

    private function buildKeys(RateLimitContextDTO $context, string $ua, ?string $fpHash, string $policyName): array
    {
        $k1 = $this->hashKey("{$policyName}:k1:{$this->getIpPrefix($context->ip)}");
        // Use normalized UA
        $k2 = $this->hashKey("{$policyName}:k2:{$this->getIpPrefix($context->ip)}:{$ua}");
        $k3 = $fpHash ? $this->hashKey("{$policyName}:k3:{$this->getIpPrefix($context->ip)}:{$fpHash}") : null;
        $k4 = $context->accountId ? $this->hashKey("{$policyName}:k4:{$context->accountId}") : null;
        $k5 = $context->accountId && $fpHash ? $this->hashKey("{$policyName}:k5:{$context->accountId}:{$fpHash}") : null;

        $keys = [
            'k1' => $k1,
            'k2' => $k2,
            'k3' => $k3,
            'k4' => $k4,
            'k5' => $k5,
        ];

        if (filter_var($context->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
             $keys['k1_48'] = $this->hashKey("{$policyName}:k1:{$this->getIpPrefix($context->ip, 48)}");
             $keys['k1_40'] = $this->hashKey("{$policyName}:k1:{$this->getIpPrefix($context->ip, 40)}");
             $keys['k1_32'] = $this->hashKey("{$policyName}:k1:{$this->getIpPrefix($context->ip, 32)}");
        }

        return $keys;
    }

    private function getScopedThresholds(string $keyType, BlockPolicyInterface $policy): array
    {
        $thresholds = $policy->getScoreThresholds();
        $scoped = $thresholds[$keyType] ?? $thresholds;
        if (!is_array(reset($scoped))) {
            if (isset($thresholds[$keyType])) {
                 $scoped = $thresholds[$keyType];
             } elseif (isset($thresholds['default'])) {
                 $scoped = $thresholds['default'];
             } else {
                 if (str_starts_with($keyType, 'k1_')) {
                     $scoped = $thresholds['k1'] ?? $thresholds['default'] ?? [];
                 }
             }
        }
        return is_array($scoped) ? $scoped : [];
    }

    private function determineLevel(int $score, string $keyType, BlockPolicyInterface $policy): int
    {
        $scoped = $this->getScopedThresholds($keyType, $policy);
        krsort($scoped);
        foreach ($scoped as $thresh => $lvl) {
            if ($score >= $thresh) return $lvl;
        }
        return 0;
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
            $value = $data['value'] ?? 0;
            $updatedAt = $data['updated_at'] ?? time();

            $scope = match($keyType) {
                'k4' => 'account',
                'k3', 'k5' => 'device',
                default => 'ip'
            };

            $block = $this->store->checkBlock($key);
            $level = $block ? $block['level'] : 0;

            $decayAmount = $this->decayCalculator->calculateDecay($value, $updatedAt, $level, $scope);
            $decayed[$keyType] = max(0, $value - $decayAmount);
        }
        return $decayed;
    }

    private function calculateDeltas(BlockPolicyInterface $policy, RateLimitContextDTO $context, DeviceIdentityDTO $device, RateLimitRequestDTO $request): array
    {
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

    private function hashKey(string $input): string { return hash_hmac('sha256', $input, $this->secret); }

    private function getIpPrefix(string $ip, int $cidr = 64): string {
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

    private function createBlockedResult(int $level, int $retryAfter, string $decision): RateLimitResultDTO {
        return new RateLimitResultDTO($decision, $level, $retryAfter, 'NORMAL');
    }

    private function createAllowResult(): RateLimitResultDTO {
        return new RateLimitResultDTO(RateLimitResultDTO::DECISION_ALLOW, 0, 0, 'NORMAL');
    }
}
