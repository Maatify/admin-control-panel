# 🔐 Password Crypto Module — HOW TO USE

This document describes the **approved and secure usage** of the Password
Crypto Module.

This is a **security-sensitive component**.
Any deviation from this guide is considered a **security violation**.

---

## 1. Prerequisites

Before using this module, you MUST provide:

- An implementation of `PasswordPepperProviderInterface`
- An `ArgonPolicyDTO` instance
- Dependency Injection wiring

The module MUST NOT:

- Read environment variables
- Load configuration
- Retrieve secrets directly

---

## 2. Creating the PasswordHasher

### Example (Infrastructure Layer)

```php
use Maatify\Crypto\Password\DTO\ArgonPolicyDTO;use Maatify\Crypto\Password\PasswordHasher;

$pepperProvider = new EnvPasswordPepperProvider(); // example
$argonPolicy = new ArgonPolicyDTO(
    memoryCost: 1 << 16, // 64 MB
    timeCost: 3,
    threads: 2
);

$passwordHasher = new PasswordHasher(
    $pepperProvider,
    $argonPolicy
);
```

The hasher does not know where the pepper comes from.

---

## 3. Hashing a Password

```php
$hash = $passwordHasher->hash($plainPassword);

// Store the hash directly in the database
```

Rules:

* Store the hash as-is
* Do NOT encrypt it
* Do NOT modify it
* Do NOT parse it

---

## 4. Verifying a Password

```php
$isValid = $passwordHasher->verify(
    $plainPassword,
    $storedHash
);

if ($isValid === false) {
    // Authentication failed
}
```

Verification automatically applies the pepper.
Timing-safe comparison is handled internally.

---

## 5. Rehashing Strategy (MANDATORY FLOW)

```php
if ($passwordHasher->verify($plainPassword, $storedHash)) {

    if ($passwordHasher->needsRehash($storedHash)) {
        $newHash = $passwordHasher->hash($plainPassword);

        // Persist the new hash
    }

    // Continue authentication
}
```

Rules:

* NEVER call `needsRehash()` before `verify()`
* NEVER inspect or decrypt stored hashes
* NEVER perform bulk or background rehashing

---

## 6. Pepper Rotation Behavior

If the pepper changes:

* All existing password verifications will fail
* Users must reset or recover passwords

This behavior is intentional and required.

The module does NOT support:

* Multiple peppers
* Pepper fallback
* Automatic rotation

---

## 7. Error Handling

Expected exceptions:

* `PepperUnavailableException`
* `HashingFailedException`
* `InvalidArgonPolicyException`

All exceptions are fail-closed.

---

## 8. Strictly Forbidden

This module MUST NOT be used to:

* Encrypt passwords
* Decrypt passwords
* Store reversible secrets
* Use HKDF
* Rotate password keys
* Access environment variables internally

Violating these rules breaks the security model.

---

## 9. Final Notes

* Hash only
* No encryption
* Pepper via DI
* Argon2id as final output
* Rehash only after verification
* Fail-closed behavior

This module is **production-ready and locked**.

Any change requires a new ADR.
