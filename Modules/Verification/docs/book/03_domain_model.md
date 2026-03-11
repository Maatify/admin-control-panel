# Chapter 3: Domain Model

This chapter breaks down the core Data Transfer Objects (DTOs) and Enums that form the vocabulary of the `Maatify\Verification` module.

## Core DTO: `VerificationCode`

The central entity is the `VerificationCode` DTO. It represents the state of a verification challenge at any point in its lifecycle. It is intentionally designed to be fully immutable to ensure predictable state transitions within the application layer.

```php
use DateTimeImmutable;
use Maatify\Verification\Domain\Enum\IdentityTypeEnum;
use Maatify\Verification\Domain\Enum\VerificationCodeStatus;
use Maatify\Verification\Domain\Enum\VerificationPurposeEnum;

readonly class VerificationCode
{
    public function __construct(
        public int $id, // The unique database identifier (0 if unsaved)
        public IdentityTypeEnum $identityType, // E.g., Email, Admin
        public string $identityId, // The actual identifier (e.g., 'user@example.com')
        public VerificationPurposeEnum $purpose, // What the code is authorizing
        public string $codeHash, // The SHA-256 hash of the plain code
        public VerificationCodeStatus $status, // Active, Used, Expired
        public int $attempts, // Number of failed validation attempts
        public int $maxAttempts, // Policy-defined maximum attempts
        public DateTimeImmutable $expiresAt, // Policy-defined TTL
        public DateTimeImmutable $createdAt,
        public ?string $createdIp = null, // Optional IP where code was requested
        public ?string $usedIp = null // Optional IP where code was validated
    ) {}
}
```

**Key Points:**
- `codeHash`: The module *never* stores the plain text code.
- `attempts` / `maxAttempts`: Critical for anti-brute force mechanisms.
- IP Tracking: Included directly in the domain model for complete auditability.

## Output DTO: `GeneratedVerificationCode`

When a new code is created, the system must return both the plain text code (so it can be sent to the user) and the hashed `VerificationCode` entity (so the application knows what was stored).

```php
readonly class GeneratedVerificationCode
{
    public function __construct(
        public VerificationCode $entity,
        public string $plainCode
    ) {}
}
```

## Output DTO: `VerificationResult`

When a code is validated, the `VerificationResult` provides a standardized response. It clearly indicates success or failure and carries context about the identity if successful.

```php
readonly class VerificationResult
{
    public function __construct(
        public bool $success,
        public string $reason = '',
        public ?IdentityTypeEnum $identityType = null,
        public ?string $identityId = null,
        public ?VerificationPurposeEnum $purpose = null
    ) {}
}
```

**Security Consideration:** On failure, `reason` is typically generic (e.g., 'Invalid code.') to prevent leaking information about whether a code exists but is expired vs. doesn't exist at all.

## Policy DTO: `VerificationPolicy`

Defines the rules for a specific `VerificationPurposeEnum`.

```php
readonly class VerificationPolicy
{
    public function __construct(
        public int $ttlSeconds, // How long the code is valid (e.g., 600 for 10 mins)
        public int $maxAttempts, // How many tries before permanent lockout (e.g., 3)
        public int $resendCooldownSeconds // Minimum time between requests (e.g., 60)
    ) {}
}
```

## Core Enums

Strongly typed enumerations are used throughout the module to prevent invalid states.

*   **`VerificationCodeStatus`**: `ACTIVE`, `USED`, `EXPIRED`. These define the strict lifecycle of a code.
*   **`IdentityTypeEnum`**: E.g., `Admin`, `Email`. Defines the *type* of identifier the code is tied to.
*   **`VerificationPurposeEnum`**: E.g., `EmailVerification`, `TelegramChannelLink`. Defines *why* the code was issued. This is heavily tied to the `VerificationPolicyResolver`.