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
                       if (isset($realKeysV2['k5'])) {
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

    private function checkBudget(BlockPolicyInterface $policy, array $keys, DeviceIdentityDTO $device): ?RateLimitResultDTO
    {
        $config = $policy->getBudgetConfig();
        if ($config && isset($keys['k4'])) {
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

    // ... checkThresholds, checkCorrelationRules ... (Same as before)
    private function checkThresholds(BlockPolicyInterface $policy, array $scores, array $keys, DeviceIdentityDTO $device): ?RateLimitResultDTO
    {
        $highestLevel = 0;
        foreach ($scores as $keyType => $score) {
            $level = $this->determineLevel($score, $keyType, $policy);
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
        $k2 = $this->hashKey("{$policyName}:k2:{$this->getIpPrefix($context->ip)}:{$device->normalizedUa}", $this->secret);
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
                    // Medium+ Confidence requires 2-window confirmation
                    $warnKey = "dilution_warn:{$device->fingerprintHash}";
                    $warn = $this->correlationStore->getWatchFlag($warnKey);
                    if ($warn > 0) {
                        $targetKey = $this->hashKey("{$policyName}:k3:{$this->getIpPrefix($context->ip)}:{$device->fingerprintHash}", $this->secret);
                        $shouldBlock = true;
                    } else {
                        $this->correlationStore->incrementWatchFlag($warnKey, 1800);
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

                $thresholds = $this->getScopedThresholds($keyType, $policy);
                foreach ($thresholds as $t => $l) {
                    if ($newScore == $t - 1) {
                         $wKey = "watch:{$key}";
                         $flags = $this->correlationStore->incrementWatchFlag($wKey, 1800);
                         if ($flags >= 2) $level = max($level, $l);
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

            if ($shouldCount) {
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
    private function buildKeys(RateLimitContextDTO $context, string $ua, ?string $fpHash, string $policyName, string $secret): array {
        $k1 = $this->hashKey("{$policyName}:k1:{$this->getIpPrefix($context->ip)}", $secret);
        $k2 = $this->hashKey("{$policyName}:k2:{$this->getIpPrefix($context->ip)}:{$ua}", $secret);
        $k3 = $fpHash ? $this->hashKey("{$policyName}:k3:{$this->getIpPrefix($context->ip)}:{$fpHash}", $secret) : null;
        $k4 = $context->accountId ? $this->hashKey("{$policyName}:k4:{$context->accountId}", $secret) : null;
        $k5 = $context->accountId && $fpHash ? $this->hashKey("{$policyName}:k5:{$context->accountId}:{$fpHash}", $secret) : null;
        $keys = ['k1' => $k1, 'k2' => $k2, 'k3' => $k3, 'k4' => $k4, 'k5' => $k5];
        if (filter_var($context->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
             $keys['k1_48'] = $this->hashKey("{$policyName}:k1:{$this->getIpPrefix($context->ip, 48)}", $secret);
             $keys['k1_40'] = $this->hashKey("{$policyName}:k1:{$this->getIpPrefix($context->ip, 40)}", $secret);
             $keys['k1_32'] = $this->hashKey("{$policyName}:k1:{$this->getIpPrefix($context->ip, 32)}", $secret);
        }
        return $keys;
    }
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
    private function applyDecay(array $rawScores, array $keys): array {
        $decayed = [];
        foreach ($keys as $keyType => $key) {
            $dto = $rawScores[$keyType] ?? null;
            if (!$key || !$dto) { $decayed[$keyType] = 0; continue; }
            $decayed[$keyType] = $this->calculateDecayedScore($dto->value, $dto->updatedAt, $keyType, $key);
        }
        return $decayed;
    }
    private function calculateDeltas(BlockPolicyInterface $policy, RateLimitContextDTO $context, DeviceIdentityDTO $device, RateLimitRequestDTO $request): array {
        $deltasDto = $policy->getScoreDeltas();
        $result = [];
        if ($deltasDto->access > 0) {
            $cost = $deltasDto->access * $request->cost;
            $result['k1'] = ($result['k1'] ?? 0) + $cost;
            $result['k2'] = ($result['k2'] ?? 0) + $cost;
            $result['k3'] = ($result['k3'] ?? 0) + $cost;
        }
        if ($request->isFailure) {
            if ($deltasDto->k5_failure > 0) $result['k5'] = $deltasDto->k5_failure;
            if ($deltasDto->k4_failure > 0) $result['k4'] = $deltasDto->k4_failure;
            if ($deltasDto->k2_missing_fp > 0 && empty($device->fingerprintHash)) $result['k2'] = $deltasDto->k2_missing_fp;
            if ($deltasDto->k1_spray > 0) $result['k1'] = $deltasDto->k1_spray;
        }
        return $result;
    }
    private function getScopedThresholds(string $keyType, BlockPolicyInterface $policy): array {
        $thresholdsDto = $policy->getScoreThresholds();
        $thresholds = $thresholdsDto->thresholds;

        $scoped = $thresholds[$keyType] ?? $thresholds;
        if (!is_array(reset($scoped))) {
            if (isset($thresholds[$keyType])) $scoped = $thresholds[$keyType];
            elseif (isset($thresholds['default'])) $scoped = $thresholds['default'];
            else { if (str_starts_with($keyType, 'k1_')) $scoped = $thresholds['k1'] ?? $thresholds['default'] ?? []; }
        }
        return is_array($scoped) ? $scoped : [];
    }
    private function determineLevel(int $score, string $keyType, BlockPolicyInterface $policy): int {
        $scoped = $this->getScopedThresholds($keyType, $policy);
        krsort($scoped);
        foreach ($scoped as $thresh => $lvl) { if ($score >= $thresh) return $lvl; }
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
        return new RateLimitResultDTO($decision, $level, $retryAfter, 'NORMAL');
    }
    private function createAllowResult(): RateLimitResultDTO {
        return new RateLimitResultDTO(RateLimitResultDTO::DECISION_ALLOW, 0, 0, 'NORMAL');
    }
}
