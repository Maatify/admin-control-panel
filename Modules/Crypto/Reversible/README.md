# 🔐 Reversible Crypto Module

**Reversible / Symmetric Cryptography Engine**

This module provides a **strict, fail-closed, reversible cryptography layer** designed for
systems that need to **encrypt data and later decrypt it back to its original form**.

> ❗ This module is **NOT** for password hashing or one-way secrets.

---

## 🎯 Purpose

This module is intended for use cases such as:

- Encrypted queues (Email, Webhooks, Jobs)
- Secure payload storage
- Deferred delivery systems
- Any data that **must be recovered later**

❌ **Out of scope**:
- Password hashing
- One-way hashing
- Token signing
- JWT / HMAC / integrity-only use cases

---

## 🧠 Core Principle

> **Reversible cryptography is NOT hashing.**

This module explicitly supports:
- Encryption **and**
- Decryption back to the original plaintext

Every class, interface, and name reflects this intent clearly.

---

## 🧱 Architecture Overview

```text
ReversibleCryptoService
        ↓
ReversibleCryptoAlgorithmRegistry
        ↓
ReversibleCryptoAlgorithmInterface
        ↓
Concrete Algorithm Implementations
````

### Separation of Responsibilities

| Layer           | Responsibility                |
|-----------------|-------------------------------|
| Enum            | Security whitelist & metadata |
| Algorithm Class | Cryptographic execution       |
| Registry        | Controlled algorithm binding  |
| Service         | Orchestration only            |
| Bootstrap       | Key loading & rotation policy |

---

## 📁 Module Structure

```text
Reversible/
├── ReversibleCryptoService.php
├── ReversibleCryptoAlgorithmEnum.php
├── ReversibleCryptoAlgorithmInterface.php
├── Registry/
│   └── ReversibleCryptoAlgorithmRegistry.php
├── Algorithms/
│   └── Aes256GcmAlgorithm.php
├── DTO/
│   ├── ReversibleCryptoEncryptionResultDTO.php
│   └── ReversibleCryptoMetadataDTO.php
└── Exceptions/
    ├── CryptoAlgorithmNotSupportedException.php
    ├── CryptoKeyNotFoundException.php
    └── CryptoDecryptionFailedException.php
```

---

## 🔐 Supported Algorithms (Whitelist)

| Algorithm         | AEAD | IV | Tag | Status     |
|-------------------|------|----|-----|------------|
| AES-256-GCM       | ✔️   | ✔️ | ✔️  | Default    |
| AES-128-GCM       | ✔️   | ✔️ | ✔️  | Allowed    |
| ChaCha20-Poly1305 | ✔️   | ✔️ | ✔️  | Allowed    |
| AES-256-CBC       | ❌    | ✔️ | ❌   | Restricted |

❌ ECB is **forbidden**
❌ Custom crypto is **forbidden**

---

## 🔁 Key Rotation Model

This module **does NOT manage key rotation**.

It only:

* Accepts a key set
* Uses the active key for encryption
* Uses stored key identifiers for decryption

### Why?

Key rotation is a **key-management policy**, not a cryptographic primitive.

Rotation MUST be handled by:

* Environment configuration
* Secret managers
* Bootstrap or infrastructure layer

---

## 🗄️ Required Storage Fields

Any encrypted record **MUST store**:

| Field     | Required |
|-----------|----------|
| cipher    | ✔️       |
| algorithm | ✔️       |
| key_id    | ✔️       |
| iv        | nullable |
| tag       | nullable |

This guarantees:

* Safe decryption
* Future algorithm changes
* Seamless key rotation

---

## 🔥 Failure Semantics (Fail-Closed)

This module **never fails silently**.

Any failure results in an exception:

* Unsupported algorithm
* Missing key
* Invalid authentication tag
* Corrupted ciphertext
* Decryption failure

✔️ No fallbacks
✔️ No empty returns
✔️ No partial success

---

## 🧪 Testing Philosophy

The module is fully covered by unit tests:

* Algorithm correctness
* Registry security
* Service orchestration
* Integrity failure scenarios

Tests are:

* Deterministic
* Stateless
* Independent of environment or database

---

## 🚫 What This Module Does NOT Do

* ❌ Load keys from env
* ❌ Manage key lifecycle
* ❌ Handle storage
* ❌ Perform hashing
* ❌ Implement password security
* ❌ Provide automatic rotation

---

## 📦 Library-Ready Design

This module is designed to be:

* Stateless
* Injectable
* Environment-agnostic
* Extractable as a standalone library

It can be moved to its own repository **without renaming or refactoring**.

---

## 🏁 Summary

✔️ Clear reversible crypto intent
✔️ Strong separation of concerns
✔️ Fail-closed security model
✔️ Algorithm-agnostic design
✔️ Ready for queues, jobs, and secure storage
✔️ Library-grade architecture

---

**Use responsibly. Cryptography is not forgiving.**
