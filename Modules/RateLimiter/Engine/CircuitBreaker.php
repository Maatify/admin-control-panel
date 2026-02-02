<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Engine;

use Maatify\RateLimiter\Contract\CircuitBreakerStoreInterface;
use Maatify\RateLimiter\Contract\FailureSignalEmitterInterface;
use Maatify\RateLimiter\DTO\FailureSignalDTO;
use Maatify\RateLimiter\DTO\FailureStateDTO;
use Maatify\RateLimiter\DTO\Store\CircuitBreakerStateDTO;

class CircuitBreaker
{
    private const TRIP_THRESHOLD = 3;
    private const TRIP_WINDOW = 10;
    private const MIN_DEGRADED_DURATION = 300; // 5 min
    private const MIN_HEALTHY_INTERVAL = 120; // 2 min
    private const RE_ENTRY_LIMIT = 2;
    private const RE_ENTRY_WINDOW = 1800; // 30 min
    private const FAIL_CLOSED_DURATION = 600; // 10 min

    public function __construct(
        private readonly CircuitBreakerStoreInterface $store,
        private readonly FailureSignalEmitterInterface $emitter
    ) {}

    public function reportFailure(string $policyName): void
    {
        $state = $this->loadState($policyName);
        $now = time();

        $failures = $state->failures;
        $failures[] = $now;
        $failures = array_filter($failures, fn($t) => $t >= $now - self::TRIP_WINDOW);

        $status = $state->status;
        $openSince = $state->openSince;
        $reEntries = $state->reEntries;
        $failClosedUntil = $state->failClosedUntil;

        if (count($failures) >= self::TRIP_THRESHOLD) {
            if ($status !== FailureStateDTO::STATE_OPEN) {
                // Trip!
                $status = FailureStateDTO::STATE_OPEN;
                $openSince = $now;

                $reEntries[] = $now;
                $reEntries = array_filter($reEntries, fn($t) => $t >= $now - self::RE_ENTRY_WINDOW);

                $this->emitter->emit(new FailureSignalDTO(FailureSignalDTO::TYPE_CB_OPENED, $policyName));

                if (count($reEntries) > self::RE_ENTRY_LIMIT) {
                    $this->emitter->emit(new FailureSignalDTO(FailureSignalDTO::TYPE_CB_RE_ENTRY_VIOLATION, $policyName));
                    $failClosedUntil = $now + self::FAIL_CLOSED_DURATION;
                }
            }
        }

        $this->saveState($policyName, new CircuitBreakerStateDTO(
            $status,
            array_values($failures),
            $now,
            $openSince,
            $state->lastSuccess,
            array_values($reEntries),
            $failClosedUntil
        ));
    }

    public function reportSuccess(string $policyName): void
    {
        $state = $this->loadState($policyName);
        if ($state->status === FailureStateDTO::STATE_CLOSED) {
            return;
        }

        $now = time();
        $status = $state->status;
        $failures = $state->failures;

        if ($status === FailureStateDTO::STATE_OPEN) {
            if ($now - $state->openSince >= self::MIN_DEGRADED_DURATION) {
                if ($now - $state->lastFailure >= self::MIN_HEALTHY_INTERVAL) {
                    $status = FailureStateDTO::STATE_CLOSED;
                    $failures = [];
                    $this->emitter->emit(new FailureSignalDTO(FailureSignalDTO::TYPE_CB_RECOVERED, $policyName));
                }
            }
        }

        $this->saveState($policyName, new CircuitBreakerStateDTO(
            $status,
            $failures,
            $state->lastFailure,
            $state->openSince,
            $now,
            $state->reEntries,
            $state->failClosedUntil
        ));
    }

    public function getState(string $policyName): FailureStateDTO
    {
        $data = $this->loadState($policyName);
        return new FailureStateDTO(
            $data->status,
            count($data->failures),
            $data->lastFailure,
            $data->status === FailureStateDTO::STATE_OPEN
        );
    }

    public function isReEntryGuardViolated(string $policyName): bool
    {
        $data = $this->loadState($policyName);
        return $data->failClosedUntil > time();
    }

    private function loadState(string $policyName): CircuitBreakerStateDTO
    {
        $data = $this->store->load($policyName);
        return $data ?? new CircuitBreakerStateDTO(
            FailureStateDTO::STATE_CLOSED,
            [],
            0,
            0,
            0,
            [],
            0
        );
    }

    private function saveState(string $policyName, CircuitBreakerStateDTO $state): void
    {
        $this->store->save($policyName, $state);
    }
}
