# Maatify RateLimiter Module

A standalone, contract-compliant Rate Limiting library for the Admin Control Panel.

## Features

- **Device Fingerprinting**: Passive identification using UA, Accept-Language, and OS hints.
- **Micro-cap Budgets**: Limits repeated failures on specific devices without global lockouts.
- **Fail-Safe Architecture**: Circuit breaker with "Fail-Closed" (Security) and "Fail-Open" (Availability) modes.
- **IPv6 Hierarchy**: Automatic aggregation of IPv6 addresses (/64, /48, /40).
- **Strict Contracts**: DTO-first API for type safety and determinism.

## Installation

This module is part of the `Maatify` monorepo. Ensure it is autoloaded via `composer.json`.

```json
"autoload": {
    "psr-4": {
        "Maatify\\RateLimiter\\": "Modules/RateLimiter/"
    }
}
```

## Usage

### 1. Bootstrap the Engine

Construct the engine with required infrastructure dependencies.

```php
use Maatify\RateLimiter\Engine\RateLimiterEngine;
use Maatify\RateLimiter\Engine\EvaluationPipeline;
use Maatify\RateLimiter\Device\DeviceIdentityResolver;
// ... (Inject implementations of Stores)

$engine = new RateLimiterEngine(
    new DeviceIdentityResolver(),
    new EvaluationPipeline(
        $rateLimitStore,
        $correlationStore,
        $budgetTracker,
        $antiGate,
        $decayCalc,
        $ephemeralBucket,
        'secret_v2', // Active Key
        'prod',      // Env Scope
        'secret_v1'  // Previous Key
    ),
    $circuitBreaker,
    $failureResolver,
    [
        new LoginProtectionPolicy(),
        new OtpProtectionPolicy()
    ]
);
```

### 2. Limit a Request

```php
$context = new RateLimitContextDTO(
    ip: '203.0.113.1',
    ua: 'Mozilla/5.0...',
    accountId: 'user_123'
);

$request = new RateLimitRequestDTO(
    policyName: 'login_protection',
    cost: 1,
    isFailure: false // Set true after a failed attempt
);

$result = $engine->limit($context, $request);

if ($result->decision !== RateLimitResultDTO::DECISION_ALLOW) {
    // Block the request
    http_response_code(429);
    header('Retry-After: ' . $result->retryAfter);
    exit;
}
```

## Contracts

- `RateLimitStoreInterface`: Storage for counters and blocks.
- `CorrelationStoreInterface`: Storage for cardinality and watch flags.
- `CircuitBreakerStoreInterface`: Persistence for circuit breaker state.
- `BlockPolicyInterface`: Definition of rules (Thresholds, Budgets).

## Security Notes

1.  **Do NOT weaken policies**: Default thresholds are security constants.
2.  **Deterministic Inputs**: Ensure IP and UA inputs are raw; normalization is handled internally.
3.  **Failure Signals**: Monitor the `FailureSignalEmitterInterface` for critical alerts (e.g., Re-entry Violations).
