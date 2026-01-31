# How To Use: Crypto DX Layer

The `CryptoProvider` is the primary entry point for all cryptographic operations in the application layer.
It simplifies the usage of the underlying strictly separated crypto modules while preserving all security boundaries.

---

## 1. Injection

Inject `App\Modules\Crypto\DX\CryptoProvider` into your service or controller.

```php
use Maatify\Crypto\DX\CryptoProvider;

class MyService
{
    public function __construct(
        private CryptoProvider $crypto
    ) {}
}
```

---

## 2. Context-Based Encryption (Recommended)

Use this pipeline for **domain-separated data** (e.g., PII, tokens, notification payloads).
It automatically derives unique keys for the given context from the active root keys using HKDF.

**Pipeline:**
`KeyRotation` → `HKDF` → `Reversible`

> ⚠️ Note
> The following example is **conceptual pseudo-code**.
> The concrete encryption return type and decryption signature are defined by the underlying
> `ReversibleCryptoService` and must not be assumed by consumers.

```php
// 1. Get the encrypter for a specific context
// Context strings MUST be explicit and versioned.
$encrypter = $this->crypto->context('notification:email:v1');

// 2. Encrypt (returns an opaque encrypted payload object)
$encryptedPayload = $encrypter->encrypt('secret payload');

// 3. Decrypt using the same encrypter instance
$plaintext = $encrypter->decrypt($encryptedPayload);
```

---

## 3. Direct Encryption (Use with Caution)

Use this pipeline **only** when HKDF derivation is explicitly not required
(e.g., legacy data or tightly scoped system internals).

**Pipeline:**
`KeyRotation` → `Reversible`

> ⚠️ Warning
> This pipeline does **not** provide domain separation.

```php
$encrypter = $this->crypto->direct();

// Same conceptual API as context-based encryption
$encryptedPayload = $encrypter->encrypt('raw secret');
$plaintext = $encrypter->decrypt($encryptedPayload);
```

---

## 4. Password Hashing

Provides direct access to the `PasswordService` for hashing and verification.
This pipeline is **fully isolated** from encryption keys and reversible cryptography.

**Pipeline:**
`HMAC(Pepper)` → `Argon2id`

```php
$passwordService = $this->crypto->password();

// Hash
$hash = $passwordService->hash('user-password');

// Verify
$isValid = $passwordService->verify('user-password', $hash);
```

---

## Summary of Methods

| Method                 | Returns                   | Use Case                                         |
|------------------------|---------------------------|--------------------------------------------------|
| `context(string $ctx)` | `ReversibleCryptoService` | **Default**. Domain-separated encryption (HKDF). |
| `direct()`             | `ReversibleCryptoService` | **Advanced**. Raw root-key encryption.           |
| `password()`           | `PasswordService`         | Password hashing and verification.               |

---

## Final Notes

* The Crypto DX layer is **orchestration only**.
* It does **not** define cryptographic formats or payload structures.
* Consumers must treat encryption results as **opaque objects**.
* All cryptographic correctness is enforced by the underlying frozen modules.

---
