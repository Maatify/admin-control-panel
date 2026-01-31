# 🔁 Key Rotation Module

**Cryptographic Key Lifecycle & Rotation Policy Engine**

This module provides a **strict, fail-closed key rotation layer** responsible for
**managing cryptographic key lifecycle and rotation policy** — without performing
any cryptographic operations.

> ❗ This module does **NOT** encrypt or decrypt data.  
> ❗ It exists to control **which key is active**, **which keys are usable**, and **when rotation occurs**.

---

## 🎯 Purpose

This module exists to solve **key lifecycle management**, including:

- Defining cryptographic keys
- Enforcing **exactly one active key**
- Supporting safe key rotation
- Preserving backward-compatible decryption
- Preparing data for cryptographic services

Typical use cases:
- Email queues
- Webhook payloads
- Deferred jobs
- Long-lived encrypted storage

---

## 🧠 Core Principle

> **Key rotation is a policy problem, not a cryptographic primitive.**

This module:
- Owns **key lifecycle and status**
- Owns **rotation decisions**
- Supplies keys to cryptographic services

It intentionally does **NOT**:
- Perform encryption
- Perform decryption
- Know about ciphers or algorithms
- Call OpenSSL or crypto libraries

---

## 🧱 Architectural Overview

```text
KeyRotationService
        ↓
KeyRotationPolicy
        ↓
KeyProviderInterface
        ↓
Key Storage (InMemory / DB / Vault)
````

### Separation of Responsibilities

| Layer         | Responsibility               |
|---------------|------------------------------|
| Policy        | Enforces rotation rules      |
| Provider      | Stores keys & mutates state  |
| Service       | Orchestration & export       |
| Crypto Module | Encryption / Decryption only |

**No circular dependency is allowed.**

---

## 🔐 Key Identity Model

Each cryptographic key is represented by:

* `key_id` (immutable identifier)
* `status` (ACTIVE / INACTIVE / RETIRED)
* `created_at`
* raw key material (binary)

### Key Status Semantics

| Status   | Encrypt | Decrypt | Notes                   |
|----------|---------|---------|-------------------------|
| ACTIVE   | ✔️      | ✔️      | Used for new encryption |
| INACTIVE | ❌       | ✔️      | Old data only           |
| RETIRED  | ❌       | ✔️      | Legacy / grace period   |

Encryption **MUST NEVER** use non-ACTIVE keys.

---

## 🔒 Invariants (Fail-Closed)

This module strictly enforces:

* ❗ Exactly **ONE** ACTIVE key must exist
* ❗ Zero ACTIVE keys → hard failure
* ❗ Multiple ACTIVE keys → hard failure
* ❗ Unknown key_id → hard failure
* ❗ Invalid state transition → hard failure

No silent fallback is allowed.

---

## 📁 Module Structure

```text
KeyRotation/
├── KeyRotationService.php
├── KeyProviderInterface.php
├── CryptoKeyInterface.php
├── KeyStatusEnum.php
├── Policy/
│   └── StrictSingleActiveKeyPolicy.php
├── Providers/
│   └── InMemoryKeyProvider.php
├── DTO/
│   ├── CryptoKeyDTO.php
│   ├── KeyRotationStateDTO.php
│   ├── KeyRotationDecisionDTO.php
│   └── KeyRotationValidationResultDTO.php
└── Exceptions/
    ├── KeyRotationException.php
    ├── NoActiveKeyException.php
    ├── MultipleActiveKeysException.php
    ├── KeyNotFoundException.php
    └── DecryptionKeyNotAllowedException.php
```

---

## 🔁 Rotation Flow (v1)

1. A new key is added as **INACTIVE**
2. Policy validates invariant (one ACTIVE key)
3. New key is promoted to **ACTIVE**
4. Previous ACTIVE key becomes **INACTIVE**
5. New data uses new key
6. Old data remains decryptable

❌ No automatic re-encryption
❌ No background mutation

---

## 🧩 Example Usage (Bootstrap)

```php
use Maatify\Crypto\KeyRotation\DTO\CryptoKeyDTO;use Maatify\Crypto\KeyRotation\KeyRotationService;use Maatify\Crypto\KeyRotation\KeyStatusEnum;use Maatify\Crypto\KeyRotation\Policy\StrictSingleActiveKeyPolicy;use Maatify\Crypto\KeyRotation\Providers\InMemoryKeyProvider;

$keys = [
    new CryptoKeyDTO('v1', $key1, KeyStatusEnum::ACTIVE, new DateTimeImmutable()),
    new CryptoKeyDTO('v2', $key2, KeyStatusEnum::INACTIVE, new DateTimeImmutable()),
];

$provider = new InMemoryKeyProvider($keys);

$rotation = new KeyRotationService(
    provider: $provider,
    policy: new StrictSingleActiveKeyPolicy()
);
```

---

## 🔗 Integration with ReversibleCrypto

```php
$config = $rotation->exportForCrypto();

$crypto = new ReversibleCryptoService(
    registry: $registry,
    keys: $config['keys'],
    activeKeyId: $config['active_key_id'],
    activeAlgorithm: ReversibleCryptoAlgorithmEnum::AES_256_GCM
);
```

---

## 🧪 Testing Philosophy

* InMemory provider for deterministic tests
* Policy tested independently
* Service tested as orchestration
* No environment or DB dependency

All invariants are validated at runtime.

---

## 🚫 What This Module Does NOT Do

* ❌ Encrypt or decrypt data
* ❌ Load keys from environment
* ❌ Persist keys (DB/Vault is external)
* ❌ Perform hashing or signing
* ❌ Auto re-encrypt data
* ❌ Guess or fallback

---

## 📦 Library-Ready Design

This module is:

* Stateless at service level
* Provider-driven
* Policy-explicit
* Fail-closed
* Extractable as a standalone library

It can be moved to its own repository without refactoring.

---

## 🏁 Summary

✔️ Explicit key lifecycle
✔️ Strict rotation policy
✔️ Fail-closed security
✔️ Backward-compatible decryption
✔️ Clean separation from crypto primitives
✔️ Production-grade design

---

**Key rotation must be explicit, auditable, and boring.
Anything implicit is a security risk.**
