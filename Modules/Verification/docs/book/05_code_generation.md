# Chapter 5: Code Generation

The generation of verification codes is handled by the `VerificationCodeGeneratorInterface` and its default implementation, `VerificationCodeGenerator`. This process ensures secure randomness, strict policy adherence, and immediate invalidation of prior active codes.

## The Generation Process

When `generate()` is called:

```php
public function generate(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose, ?string $createdIp = null): GeneratedVerificationCode
```

The service executes the following sequence:

1.  **Policy Resolution:**
    *   It queries the `VerificationCodePolicyResolverInterface` using the provided `$purpose` (e.g., `EmailVerification`).
    *   The resolver returns a `VerificationPolicy` defining `ttlSeconds` (Time-To-Live) and `maxAttempts` (brute-force limit).

2.  **Invalidate Prior Active Codes (Lifecycle Rule):**
    *   Before creating a new code, the generator explicitly calls `$this->repository->expireAllFor(...)`.
    *   It passes the exact `$identityType`, `$identityId`, and `$purpose`.
    *   This guarantees that only **one** active code exists per user/purpose combination at any given time, significantly shrinking the attack window.

3.  **Generate Random Numeric OTP:**
    *   The service uses PHP's cryptographically secure pseudo-random number generator (CSPRNG), specifically `random_int(100000, 999999)`.
    *   This ensures the generated 6-digit code is unpredictable.
    *   If `random_int()` fails (due to a system issue), a `RuntimeException` is thrown, aborting the process rather than generating insecure codes.

4.  **Secure Hashing:**
    *   The plain text code is immediately hashed: `$codeHash = hash('sha256', $plainCode)`.
    *   This hash is what will be stored in the repository. The plain text code is **never** saved to disk or database.

5.  **Entity Creation:**
    *   A new `VerificationCode` DTO is instantiated.
    *   It is populated with the `$codeHash`, `VerificationCodeStatus::ACTIVE`, the initial `attempts` count (0), the calculated `expiresAt` based on the policy's TTL, the current time (`createdAt`), and the optional `$createdIp`.

6.  **Storage:**
    *   The new `VerificationCode` DTO is passed to the `VerificationCodeRepositoryInterface->store()` method for persistence.

7.  **Return Data:**
    *   The service returns a `GeneratedVerificationCode` composite object.
    *   This object contains both the fully populated `VerificationCode` entity (for reference) and the `$plainCode` string.
    *   This is the **only** time the application has access to the plain text code. It must be immediately transmitted to the user (e.g., via email or SMS).

## Key Security Principles Enforced During Generation

*   **No Plain Text Storage:** The use of `sha256` ensures that even if the repository is compromised, the actual verification codes cannot be easily retrieved.
*   **Single Active Guarantee:** By explicitly expiring old codes *before* creating a new one, attackers cannot hoard multiple valid codes to brute-force later.
*   **Cryptographic Randomness:** The strict reliance on `random_int()` prevents predictable code sequences.