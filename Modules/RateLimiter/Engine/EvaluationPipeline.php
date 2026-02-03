<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Engine;

use Maatify\RateLimiter\Contract\BlockPolicyInterface;
use Maatify\RateLimiter\Contract\CorrelationStoreInterface;
use Maatify\RateLimiter\Contract\RateLimitStoreInterface;
use Maatify\RateLimiter\Device\EphemeralBucket;
use Maatify\RateLimiter\DTO\DeviceIdentityDTO;
use Maatify\RateLimiter\DTO\Internal\PipelineScoreDTO;
use Maatify\RateLimiter\DTO\RateLimitContextDTO;
use Maatify\RateLimiter\DTO\RateLimitRequestDTO;
use Maatify\RateLimiter\DTO\RateLimitResultDTO;
use Maatify\RateLimiter\DTO\ScoreThresholdsDTO;
use Maatify\RateLimiter\Penalty\AntiEquilibriumGate;
use Maatify\RateLimiter\Penalty\BudgetTracker;
use Maatify\RateLimiter\Penalty\DecayCalculator;
use Maatify\RateLimiter\Penalty\PenaltyLadder;

class EvaluationPipeline
{
    private string $secret;
    private ?string $previousSecret;

    public function __construct(
        private readonly RateLimitStoreInterface $store,
        private readonly CorrelationStoreInterface $correlationStore,
        private readonly BudgetTracker $budgetTracker,
        private readonly AntiEquilibriumGate $antiEquilibriumGate,
        private readonly DecayCalculator $decayCalculator,
        private readonly EphemeralBucket $ephemeralBucket,
        string $keySecret,
        private readonly string $envScope, // e.g. 'prod', 'staging'
        ?string $previousKeySecret = null
    ) {
        $this->secret = $keySecret;
        $this->previousSecret = $previousKeySecret;
    }

    public function process(
        BlockPolicyInterface $policy,
        RateLimitContextDTO $context,
        RateLimitRequestDTO $request,
        DeviceIdentityDTO $device
    ): RateLimitResultDTO {
        // 1. Build Keys for Active Block Check (Original Hash)
        $realKeysV2 = $this->buildKeys($context, $device->normalizedUa, $device->fingerprintHash, $policy->getName(), $this->secret);
        $realKeysV1 = $this->previousSecret
            ? $this->buildKeys($context, $device->normalizedUa, $device->fingerprintHash, $policy->getName(), $this->previousSecret)
            : [];

        // 2. Check Active Blocks (Fail-Fast) on Real Keys
        if ($blocked = $this->checkActiveBlocks($realKeysV2, $realKeysV1)) {
            return $blocked;
        }

        // 3. Check Account Budget (Fail-Fast)
        // Skip check if request is a Success record (Post-Action)
        if (!$request->isSuccess) {
            if ($blocked = $this->checkBudget($policy, $realKeysV2, $device)) {
                return $blocked;
            }
        }

        // 4. Resolve Effective Keys (Ephemeral Logic) for Scoring/Updates
        $effectiveHash = $device->fingerprintHash;
        if ($device->fingerprintHash) {
             // resolveKey returns string (real or ephemeral key)
             $effectiveHash = $this->ephemeralBucket->resolveKey($context, $device->fingerprintHash);
        }
        $effectiveKeysV2 = $this->buildKeys($context, $device->normalizedUa, $effectiveHash, $policy->getName(), $this->secret);
        $effectiveKeysV1 = $this->previousSecret
            ? $this->buildKeys($context, $device->normalizedUa, $effectiveHash, $policy->getName(), $this->previousSecret)
            : [];

        // Check state just for knowing if it IS ephemeral (for key filtering)
        // Since resolveKey already did the counting/check, we can infer from the key string or call check() to get DTO.
        // Calling check() is idempotent for sets.
        $ephemeralState = $device->fingerprintHash
            ? $this->ephemeralBucket->check($context, $device->fingerprintHash)
            : null;
        $isEphemeral = $ephemeralState?->isEphemeral ?? false;

        if ($isEphemeral) {
             unset($effectiveKeysV2['k3'], $effectiveKeysV2['k5']);
             unset($effectiveKeysV1['k3'], $effectiveKeysV1['k5']);
        }

        // 5. Fetch & Decay Scores (Using Effective Keys)
        $rawScores = $this->fetchScores($effectiveKeysV2, $effectiveKeysV1);
        $decayedScores = $this->applyDecay($rawScores, $effectiveKeysV2);

        // 6. Check Thresholds (Soft Blocks)
        if ($blocked = $this->checkThresholds($policy, $decayedScores, $effectiveKeysV2, $device)) {
            return $blocked;
        }

        // 7. Check Correlation Rules
        if ($blocked = $this->checkCorrelationRules($context, $device, $policy->getName(), $isEphemeral)) {
             return $blocked;
        }

        // 8. New Device Flood (5.4)
        if ($ephemeralState && $context->accountId) {
             if ($ephemeralState->accountDeviceCount >= 6) {
                  $floodKey = "flood_stage:acc:{$context->accountId}";
                  $isFloodStage = $this->correlationStore->getWatchFlag($floodKey) > 0;

                  if ($isFloodStage) {
                       $duration = PenaltyLadder::getDuration(2);
                       if ($realKeysV2['k5'] !== null) {
                           $this->store->block($realKeysV2['k5'], 2, $duration);
                       }
                       return $this->createBlockedResult(2, $duration, RateLimitResultDTO::DECISION_HARD_BLOCK);
                  }

                  $duration = PenaltyLadder::getDuration(1);
                  $this->store->block($realKeysV2['k4'], 1, $duration);
                  $this->correlationStore->incrementWatchFlag($floodKey, 900);

                  return $this->createBlockedResult(1, $duration, RateLimitResultDTO::DECISION_SOFT_BLOCK);
             }
        }

        // 9. Pre-Check Only
        if ($request->isPreCheck) {
            return $this->createAllowResult();
        }

        // 10. Process Updates (Failure / Access)
        if ($request->isFailure || $policy->getScoreDeltas()->access > 0) {
             // We write only to V2 (Active Key)
             return $this->processUpdates($policy, $context, $request, $device, $effectiveKeysV2, $rawScores);
        }

        return $this->createAllowResult();
    }

    /**
     * @param array<string, string|null> $keysV2
     * @param array<string, string|null> $keysV1
     */
    private function checkActiveBlocks(array $keysV2, array $keysV1): ?RateLimitResultDTO
    {
        foreach ([$keysV2, $keysV1] as $keys) {
            foreach ($keys as $keyType => $key) {
                if (!$key) continue;
                $block = $this->store->checkBlock($key);
                if ($block && $block->level >= 2) {
                    return $this->createBlockedResult($block->level, $block->expiresAt - time(), RateLimitResultDTO::DECISION_HARD_BLOCK);
                }
            }
        }
        return null;
    }

    /**
     * @param array<string, string|null> $keys
     */
    /**
     * @param array<string, string|null> $keys
     */
    private function checkBudget(BlockPolicyInterface $policy, array $keys, DeviceIdentityDTO $device): ?RateLimitResultDTO
    {
        $config = $policy->getBudgetConfig();
        // Fix Error 2: isset check on known offset is redundant, just check for null value
        if ($config && $keys['k4'] !== null) {
            if ($this->budgetTracker->isExceeded($keys['k4'], $config->threshold)) {
                $level = $config->block_level;
                if ($device->isTrustedSession) {
                    $level = max(2, $level - 1);
                }

                // Calculate Retry-After
                $status = $this->budgetTracker->getStatus($keys['k4']);
                $retryAfter = max(0, ($status->epochStart + 86400) - time());

                return $this->createBlockedResult($level, $retryAfter, RateLimitResultDTO::DECISION_SOFT_BLOCK);
            }
        }
        return null;
    }

    /**
     * @param array<string, int> $scores
     * @param array<string, string|null> $keys
     */
    /**
     * @param array<string, int> $scores
     * @param array<string, string|null> $keys
     */
    private function checkThresholds(BlockPolicyInterface $policy, array $scores, array $keys, DeviceIdentityDTO $device): ?RateLimitResultDTO
    {
        $highestLevel = 0;
        foreach ($scores as $keyType => $score) {
            $level = $this->determineLevel($score, $keyType, $policy);

            // Fix redundant isset/offset checks by trusting the loop and explicit checks
            if ($keyType === 'k3' && $device->confidence === 'LOW' && $level >= 2) {
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
        $base = "{$policyName}:rate_limiter";
        $ver = "v2";
        $env = $this->envScope;

        $k2 = $this->hashKey("{$base}:k2:{$ver}:{$env}:{$this->getIpPrefix($context->ip)}:{$device->normalizedUa}", $this->secret);

        if ($device->fingerprintHash) {
             $count = $this->correlationStore->addDistinct("churn:{$k2}", $device->fingerprintHash, 600);
             if ($count >= 3) {
                 $this->store->block($k2, 2, 60);
                 return $this->createBlockedResult(2, 60, RateLimitResultDTO::DECISION_HARD_BLOCK);
             }
        }
        if ($device->fingerprintHash) {
            $k3_raw = "dilution:{$device->fingerprintHash}";
            $count = $this->correlationStore->addDistinct($k3_raw, $context->ip, 600);

            $thresholdMet = false;
            if ($count >= 6) {
                $thresholdMet = true;
            } elseif ($count === 5) {
                // Dilution N-1 Watch
                $wKey = "watch_dilution:{$device->fingerprintHash}";
                $flags = $this->correlationStore->incrementWatchFlag($wKey, 1800);
                if ($flags >= 2) {
                    $thresholdMet = true;
                }
            }

            if ($thresholdMet) {
                $targetKey = null;
                $shouldBlock = false;
                if ($device->confidence === 'LOW') {
                    $targetKey = $k2;
                    $shouldBlock = true;
                } else {
                    // Medium+ Confidence requires 2-window confirmation (consecutive 10-minute windows)
                    // We use a window-based key to track presence
                    $windowId = (int) floor(time() / 600);
                    $prevWindowId = $windowId - 1;

                    $wKey = "dilution_warn:{$device->fingerprintHash}:{$windowId}";
                    $this->correlationStore->incrementWatchFlag($wKey, 1200); // 20 min retention

                    $prevWKey = "dilution_warn:{$device->fingerprintHash}:{$prevWindowId}";
                    $prevCount = $this->correlationStore->getWatchFlag($prevWKey);

                    if ($prevCount > 0) {
                        $targetKey = $this->hashKey("{$base}:k3:{$ver}:{$env}:{$this->getIpPrefix($context->ip)}:{$device->fingerprintHash}", $this->secret);
                        $shouldBlock = true;
                    }
                }

                if ($shouldBlock && $targetKey) {
                    if ($isEphemeral && strpos($targetKey, ':k3:') !== false) {
                        $targetKey = $k2;
                    }
                    $this->store->block($targetKey, 2, 60);
                    return $this->createBlockedResult(2, 60, RateLimitResultDTO::DECISION_HARD_BLOCK);
                }
            }
        }
        return null;
    }

    /**
     * @param array<string, string|null> $keys
     * @param array<string, ?PipelineScoreDTO> $rawScores
     */
    private function processUpdates(
        BlockPolicyInterface $policy,
        RateLimitContextDTO $context,
        RateLimitRequestDTO $request,
        DeviceIdentityDTO $device,
        array $keys,
        array $rawScores
    ): RateLimitResultDTO {
        $deltas = $this->calculateDeltas($policy, $context, $device, $request);

        if ($request->isFailure && empty($device->fingerprintHash) && $context->accountId) {
             $key = "last_missing_fp:acc:{$context->accountId}";
             $last = $this->store->get($key);
             if ($last && (time() - $last->value) <= 1800) {
                 $k4Repeated = $policy->getScoreDeltas()->k4_repeated_missing_fp;
                 if ($k4Repeated > 0) {
                      $deltas['k4'] = ($deltas['k4'] ?? 0) + $k4Repeated;
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
                $scoreDto = $rawScores[$keyType] ?? null;
                $rawVal = $scoreDto ? $scoreDto->value : 0;
                $updatedAt = $scoreDto ? $scoreDto->updatedAt : time();

                $decayed = $this->calculateDecayedScore($rawVal, $updatedAt, $keyType, $key);
                $baseValue = ($scoreDto && !$scoreDto->isFromV1) ? $rawVal : 0;
                $netChange = ($decayed + $delta) - $baseValue;

                $newScore = $this->store->increment($key, 86400, (int)$netChange);
                $level = $this->determineLevel($newScore, $keyType, $policy);

                $thresholdsDto = $this->getScopedThresholds($keyType, $policy);
                if ($thresholdsDto) {
                    // Check if approaching any threshold (N-1)
                    foreach ([$thresholdsDto->l1, $thresholdsDto->l2, $thresholdsDto->l3] as $thresh) {
                         if ($newScore == $thresh - 1) {
                             $wKey = "watch:{$key}";
                             $flags = $this->correlationStore->incrementWatchFlag($wKey, 1800);
                             // If watched twice, upgrade level effectively
                             if ($flags >= 2) {
                                 // Determine implied level
                                 $impliedLevel = 0;
                                 if ($thresh == $thresholdsDto->l3) $impliedLevel = 3;
                                 elseif ($thresh == $thresholdsDto->l2) $impliedLevel = 2;
                                 elseif ($thresh == $thresholdsDto->l1) $impliedLevel = 1;

                                 $level = max($level, $impliedLevel);
                             }
                         }
                    }
                }

                if ($level > $newMaxLevel) {
                    $newMaxLevel = $level;
                    $triggeredKey = $key;
                }
            }
        }

        if (isset($keys['k4']) && $policy->getBudgetConfig()) {
            $config = $policy->getBudgetConfig();
            $shouldCount = false;
            // Case 1: Increments K4 directly (New Device, Repeated Missing FP)
            if (isset($deltas['k4']) && $deltas['k4'] > 0) $shouldCount = true;
            // Case 2: Missing FP
            if (empty($device->fingerprintHash) && $request->isFailure) $shouldCount = true;
            // Case 3: Same Known Device (K5) > Micro-cap
            // Must use fixed 24h epoch counter, not decayed score.
            if (isset($deltas['k5']) && $deltas['k5'] > 0 && $context->accountId && $device->fingerprintHash) {
                $microRaw = "{$policy->getName()}:rate_limiter:microcap:k5:v1:{$context->accountId}:{$device->fingerprintHash}";

                // Write to V2 (Active Key)
                $microKeyV2 = $this->hashKey($microRaw, $this->secret);
                $this->budgetTracker->increment($microKeyV2);

                // Read from V2
                $statusV2 = $this->budgetTracker->getStatus($microKeyV2);
                $maxCount = $statusV2->count;

                // Read from V1 (Rotation Fallback) - Read Only
                if ($this->previousSecret) {
                    $microKeyV1 = $this->hashKey($microRaw, $this->previousSecret);
                    $statusV1 = $this->budgetTracker->getStatus($microKeyV1);
                    $maxCount = max($maxCount, $statusV1->count);
                }

                if ($maxCount >= 8) {
                    $shouldCount = true;
                }
            }

            if ($shouldCount && isset($keys['k4'])) {
                $this->budgetTracker->increment($keys['k4']);
                if ($this->budgetTracker->isExceeded($keys['k4'], $config->threshold)) {
                    $newMaxLevel = max($newMaxLevel, $config->block_level);
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

    // --- Helpers (Same as before) ---
    private function calculateDecayedScore(int $value, int $updatedAt, string $keyType, string $key): int {
        $scope = match($keyType) { 'k4' => 'account', 'k3', 'k5' => 'device', default => 'ip' };
        $block = $this->store->checkBlock($key);
        $level = $block ? $block->level : 0;
        $decayAmount = $this->decayCalculator->calculateDecay($value, $updatedAt, $level, $scope);
        return max(0, $value - $decayAmount);
    }
    // ... other helpers identical to previous turn ...
    /**
     * @return array<string, string|null>
     */
    private function buildKeys(RateLimitContextDTO $context, string $ua, ?string $fpHash, string $policyName, string $secret): array {
        // Enforce strict Key Strategy namespacing:
        // {policy}:rate_limiter:{type}:{algo_ver}:{env}:{scope_val}
        $base = "{$policyName}:rate_limiter";
        $ver = "v2"; // Current algo version
        $env = $this->envScope;

        $k1 = $this->hashKey("{$base}:k1:{$ver}:{$env}:{$this->getIpPrefix($context->ip)}", $secret);
        $k2 = $this->hashKey("{$base}:k2:{$ver}:{$env}:{$this->getIpPrefix($context->ip)}:{$ua}", $secret);
        $k3 = $fpHash ? $this->hashKey("{$base}:k3:{$ver}:{$env}:{$this->getIpPrefix($context->ip)}:{$fpHash}", $secret) : null;
        $k4 = $context->accountId ? $this->hashKey("{$base}:k4:{$ver}:{$env}:{$context->accountId}", $secret) : null;
        $k5 = $context->accountId && $fpHash ? $this->hashKey("{$base}:k5:{$ver}:{$env}:{$context->accountId}:{$fpHash}", $secret) : null;

        $keys = ['k1' => $k1, 'k2' => $k2, 'k3' => $k3, 'k4' => $k4, 'k5' => $k5];
        if (filter_var($context->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
             $keys['k1_48'] = $this->hashKey("{$base}:k1:{$ver}:{$env}:{$this->getIpPrefix($context->ip, 48)}", $secret);
             $keys['k1_40'] = $this->hashKey("{$base}:k1:{$ver}:{$env}:{$this->getIpPrefix($context->ip, 40)}", $secret);
             $keys['k1_32'] = $this->hashKey("{$base}:k1:{$ver}:{$env}:{$this->getIpPrefix($context->ip, 32)}", $secret);
        }
        return $keys;
    }

    /**
     * @param array<string, string|null> $keysV2
     * @param array<string, string|null> $keysV1
     * @return array<string, ?PipelineScoreDTO>
     */
    private function fetchScores(array $keysV2, array $keysV1): array {
        $scores = [];
        foreach ($keysV2 as $keyType => $key) {
            $data = $key ? $this->store->get($key) : null;
            $isFromV1 = false;
            if (!$data && isset($keysV1[$keyType]) && $keysV1[$keyType]) {
                $data = $this->store->get($keysV1[$keyType]);
                if ($data) $isFromV1 = true;
            }
            $scores[$keyType] = $data ? new PipelineScoreDTO($data->value, $data->updatedAt, $isFromV1) : null;
        }
        return $scores;
    }

    /**
     * @param array<string, ?PipelineScoreDTO> $rawScores
     * @param array<string, string|null> $keys
     * @return array<string, int>
     */
    private function applyDecay(array $rawScores, array $keys): array {
        $decayed = [];
        foreach ($keys as $keyType => $key) {
            $dto = $rawScores[$keyType] ?? null;
            if (!$key || !$dto) { $decayed[$keyType] = 0; continue; }
            $decayed[$keyType] = $this->calculateDecayedScore($dto->value, $dto->updatedAt, $keyType, $key);
        }
        return $decayed;
    }

    /**
     * @return array{k1: int, k2: int, k3: int, k4: int, k5: int}
     */
    private function calculateDeltas(BlockPolicyInterface $policy, RateLimitContextDTO $context, DeviceIdentityDTO $device, RateLimitRequestDTO $request): array {
        $deltasDto = $policy->getScoreDeltas();
        // Fix Error 3: Initialize with stable shape
        $result = ['k1' => 0, 'k2' => 0, 'k3' => 0, 'k4' => 0, 'k5' => 0];

        if ($deltasDto->access > 0) {
            $cost = $deltasDto->access * $request->cost;
            $result['k1'] += $cost;
            $result['k2'] += $cost;
            $result['k3'] += $cost;
        }
        if ($request->isFailure) {
            if ($deltasDto->k5_failure > 0) $result['k5'] = $deltasDto->k5_failure;
            if ($deltasDto->k4_failure > 0) $result['k4'] = $deltasDto->k4_failure;
            if ($deltasDto->k2_missing_fp > 0 && empty($device->fingerprintHash)) $result['k2'] = $deltasDto->k2_missing_fp;
            if ($deltasDto->k1_spray > 0) $result['k1'] = $deltasDto->k1_spray;
        }
        return $result;
    }
    private function getScopedThresholds(string $keyType, BlockPolicyInterface $policy): ?ScoreThresholdsDTO {
        $thresholds = $policy->getScoreThresholds();

        if (str_starts_with($keyType, 'k1_')) return $thresholds->k1 ?? $thresholds->default;

        return match($keyType) {
            'k1' => $thresholds->k1,
            'k2' => $thresholds->k2,
            'k3' => $thresholds->k3,
            'k4' => $thresholds->k4,
            'k5' => $thresholds->k5,
            default => $thresholds->default
        };
    }

    private function determineLevel(int $score, string $keyType, BlockPolicyInterface $policy): int {
        $dto = $this->getScopedThresholds($keyType, $policy);
        if (!$dto) return 0;

        if ($score >= $dto->l3) return 3;
        if ($score >= $dto->l2) return 2;
        if ($score >= $dto->l1) return 1;

        return 0;
    }
    private function hashKey(string $input, string $secret): string { return hash_hmac('sha256', $input, $secret); }
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
        return new RateLimitResultDTO($decision, $level, $retryAfter, 'NORMAL', null);
    }
    private function createAllowResult(): RateLimitResultDTO {
        return new RateLimitResultDTO(RateLimitResultDTO::DECISION_ALLOW, 0, 0, 'NORMAL', null);
    }
}
