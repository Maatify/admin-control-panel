<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Engine;

use Maatify\RateLimiter\Contract\CircuitBreakerStoreInterface;
use Maatify\RateLimiter\DTO\FailureStateDTO;

class CircuitBreaker
{
    private const TRIP_THRESHOLD = 3;
    private const TRIP_WINDOW = 10;
    private const MIN_DEGRADED_DURATION = 300; // 5 min
    private const MIN_HEALTHY_INTERVAL = 120; // 2 min
    private const RE_ENTRY_LIMIT = 2;
    private const RE_ENTRY_WINDOW = 1800; // 30 min

    public function __construct(
        private readonly CircuitBreakerStoreInterface $store
    ) {}

    public function reportFailure(string $policyName): void
    {
        $state = $this->loadState($policyName);
        $now = time();

        // Add failure
        $state['failures'][] = $now;
        // Prune old failures
        $state['failures'] = array_filter($state['failures'], fn($t) => $t >= $now - self::TRIP_WINDOW);

        $state['last_failure'] = $now;

        // Check Trip
        if (count($state['failures']) >= self::TRIP_THRESHOLD) {
            if ($state['status'] !== FailureStateDTO::STATE_OPEN) {
                // Trip!
                $state['status'] = FailureStateDTO::STATE_OPEN;
                $state['open_since'] = $now;

                // Track re-entries
                $state['re_entries'][] = $now;
                $state['re_entries'] = array_filter($state['re_entries'], fn($t) => $t >= $now - self::RE_ENTRY_WINDOW);
            }
        }

        $this->saveState($policyName, $state);
    }

    public function reportSuccess(string $policyName): void
    {
        $state = $this->loadState($policyName);
        if ($state['status'] === FailureStateDTO::STATE_CLOSED) {
            return;
        }

        $now = time();
        $state['last_success'] = $now;

        // Check recovery
        if ($state['status'] === FailureStateDTO::STATE_OPEN) {
            if ($now - $state['open_since'] >= self::MIN_DEGRADED_DURATION) {
                if ($now - $state['last_failure'] >= self::MIN_HEALTHY_INTERVAL) {
                    $state['status'] = FailureStateDTO::STATE_CLOSED;
                    $state['failures'] = [];
                }
            }
        }

        $this->saveState($policyName, $state);
    }

    public function getState(string $policyName): FailureStateDTO
    {
        $data = $this->loadState($policyName);
        $status = $data['status'];

        return new FailureStateDTO(
            $status,
            count($data['failures']),
            $data['last_failure'] ?? 0,
            $status === FailureStateDTO::STATE_OPEN
        );
    }

    public function isReEntryGuardViolated(string $policyName): bool
    {
        $data = $this->loadState($policyName);
        return count($data['re_entries']) > self::RE_ENTRY_LIMIT;
    }

    private function loadState(string $policyName): array
    {
        $data = $this->store->load($policyName);
        if (is_array($data)) {
            return array_merge($this->getDefaultState(), $data);
        }
        return $this->getDefaultState();
    }

    private function saveState(string $policyName, array $state): void
    {
        $this->store->save($policyName, $state);
    }

    private function getDefaultState(): array
    {
        return [
            'status' => FailureStateDTO::STATE_CLOSED,
            'failures' => [],
            'last_failure' => 0,
            'open_since' => 0,
            'last_success' => 0,
            're_entries' => [],
        ];
    }
}
