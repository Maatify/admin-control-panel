<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Contract;

interface CircuitBreakerStoreInterface
{
    /**
     * Load circuit breaker state for a policy.
     * Returns associative array compatible with CircuitBreaker logic.
     * @param string $policyName
     * @return array|null
     */
    public function load(string $policyName): ?array;

    /**
     * Save circuit breaker state.
     * @param string $policyName
     * @param array $state
     * @return void
     */
    public function save(string $policyName, array $state): void;
}
