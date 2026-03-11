# Chapter 4: Verification Lifecycle

This chapter explains the strict business rules governing the lifecycle of a `VerificationCode` from creation to expiration or usage. The module ensures that codes cannot be reused, brute-forced indefinitely, or valid beyond their intended lifespan.

## State Machine

A verification code always exists in one of three mutually exclusive states defined by `VerificationCodeStatus`:

1.  **`ACTIVE`**: The code has been generated, its TTL (Time-To-Live) has not expired, and its failed attempts are below the maximum allowed.
2.  **`USED`**: The code was successfully validated against its plain text counterpart. It can never be used again.
3.  **`EXPIRED`**: The code is permanently invalidated. This happens due to:
    *   **Time:** The current time surpasses `expiresAt`.
    *   **Brute-Force Protection:** The `attempts` count reaches `maxAttempts`.
    *   **Supersession:** A new code was generated for the same identity and purpose, instantly expiring the older code.

## Lifecycle Events

### Event 1: Generation

When a new code is requested for an identity (e.g., `user@example.com`) and a purpose (e.g., `EmailVerification`):
1.  **Invalidation:** The `VerificationCodeGenerator` immediately queries the repository to find any currently `ACTIVE` codes matching that exact identity and purpose. It marks all of them as `EXPIRED`. This guarantees the "Single Active Code Guarantee".
2.  **Creation:** A new code is generated, its hash stored, and its initial state is `ACTIVE`. The `attempts` counter is `0`.

### Event 2: Validation Failure

When a user submits an incorrect plain text code:
1.  **Attempt Increment:** The validator increments the `attempts` counter in the repository.
2.  **Evaluation:** The validator checks if the new `attempts` total has reached or exceeded `maxAttempts` defined by the code's policy.
3.  **Expiration:** If the limit is reached, the code is immediately and permanently marked as `EXPIRED`. The validation fails.

### Event 3: Validation Success

When a user submits the correct plain text code (matching the hash):
1.  **Check Constraints:** The validator verifies the code is still `ACTIVE`, `expiresAt` is in the future, and `attempts` < `maxAttempts`.
2.  **Usage Marking:** If all checks pass, the code is immediately marked as `USED`. The `usedIp` (if provided) is recorded for auditing. The validation succeeds.

## Anti-Brute Force Protection

The most critical aspect of the lifecycle is the attempt tracking. Unlike simply checking the TTL, tracking attempts prevents attackers from programmatically guessing the 6-digit code. Even if a code is valid for 10 minutes, after 3 failed guesses (or whatever policy dictates), the code is locked out permanently, forcing the user to request a new one and invalidating the attacker's progress.

This mechanism is securely baked directly into the core `VerificationCodeValidator` domain service. It cannot be bypassed by standard integration.