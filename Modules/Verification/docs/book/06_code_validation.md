# Chapter 6: Code Validation

The validation of verification codes is handled by the `VerificationCodeValidatorInterface` and its default implementation, `VerificationCodeValidator`. This process ensures strict adherence to lifecycle rules, constant-time comparison, and robust anti-brute force mechanisms.

## The Validation Process

When `validate()` or `validateByCode()` is called:

```php
public function validate(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose, string $plainCode, ?string $usedIp = null): VerificationResult
```

The service executes the following sequence:

1.  **Lookup Active Code:**
    *   The validator queries the `VerificationCodeRepositoryInterface->findActive(...)` using the exact `$identityType`, `$identityId`, and `$purpose`.
    *   If no matching active code is found, it immediately returns a `VerificationResult::failure('Invalid code.')`. This prevents an attacker from determining if a code existed previously.

2.  **Evaluate Expiry (TTL):**
    *   It checks the `expiresAt` property of the `VerificationCode` DTO against the current time (`ClockInterface->now()`).
    *   If the code is expired, the validator calls `$this->repository->expire($code->id)` to ensure the state is consistent in the database and returns a `VerificationResult::failure('Invalid code.')`.

3.  **Evaluate Attempts (Anti-Brute Force):**
    *   It checks the `attempts` count against the `maxAttempts` allowed by the policy.
    *   If `attempts` is greater than or equal to `maxAttempts`, the code is locked out permanently, even if the user correctly guessed it this time.
    *   The validator calls `$this->repository->expire($code->id)` to explicitly mark it `EXPIRED` and returns `VerificationResult::failure('Invalid code.')`.

4.  **Constant-Time Comparison:**
    *   The incoming `$plainCode` is hashed: `$inputHash = hash('sha256', $plainCode)`.
    *   The validator uses PHP's `hash_equals($code->codeHash, $inputHash)` to compare the input against the stored hash in constant time, mitigating timing attacks.
    *   If the hashes do **not** match:
        *   It immediately increments the failed attempts counter: `$this->repository->incrementAttempts($code->id)`.
        *   It then re-evaluates the attempts. If the newly incremented total reaches the `maxAttempts`, the code is expired: `$this->repository->expire($code->id)`.
        *   It returns a `VerificationResult::failure('Invalid code.')`.

5.  **Usage Marking:**
    *   If the hashes match and all checks pass, the code is valid.
    *   The validator explicitly marks the code as used and records the IP address (if provided) for auditing: `$this->repository->markUsed($code->id, $usedIp)`.
    *   It returns a `VerificationResult::success(...)` containing the matched identity and purpose.

## `validateByCode` Alternative

The `validateByCode()` method allows checking a code without knowing the identity beforehand. It follows a similar, but reversed, logic:

1.  It hashes the input `$plainCode`.
2.  It queries the repository using `$this->repository->findByCodeHash($codeHash)`.
3.  If found, it then performs the exact same status (`VerificationCodeStatus::ACTIVE`), expiry (`expiresAt`), and attempts (`maxAttempts`) checks as the standard validation.
4.  If successful, it marks the code as used and returns the embedded identity details.

## Key Security Principles Enforced During Validation

*   **Generic Failure Messages:** The generic `'Invalid code.'` message prevents leaking whether the failure was due to a non-existent code, an expired code, an incorrect guess, or a brute-force lockout.
*   **Constant-Time Validation:** Using `hash_equals()` is crucial to prevent attackers from inferring the correct hash by measuring response times.
*   **Strict Brute-Force Limits:** Incrementing the attempt counter and expiring the code upon reaching the limit is hard-coded into the domain service. It cannot be bypassed, ensuring robust protection against automated guessing.