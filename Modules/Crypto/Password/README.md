# 🔐 Password Crypto Module

## Overview

This module provides a **secure, irreversible, and infrastructure-agnostic**
password hashing mechanism based on **modern cryptographic best practices**.

It is designed to be:

- Deterministic in behavior
- Fail-closed by default
- Fully testable
- Independent from environment variables
- Compatible with native PHP password APIs

This module is used for **all password-based authentication** in the system
(Admins, Users, Customers).

---

## Core Design (LOCKED)

### Password Processing Flow

```

User Password
→ HMAC-SHA256 with PEPPER
→ Argon2id (memory-hard hashing)
→ Stored as final hash

```

- The output is **NOT reversible**
- No encryption is involved
- The stored value is a native Argon2id hash

---

## Architectural Principles

### 1️⃣ Irreversibility

Passwords are **never encrypted** and **never decrypted**.

The stored value is a verification proof, not a secret.

---

### 2️⃣ Pepper (Defense-in-Depth)

- A **single global pepper** is applied via HMAC **before hashing**
- Pepper is injected via `PasswordPepperProviderInterface`
- The hasher never knows where the pepper comes from

If the database is compromised **without the pepper**, verification is impossible.

---

### 3️⃣ Dependency Injection (Hard Rule)

The password hasher:

- ❌ MUST NOT read environment variables
- ❌ MUST NOT load configuration
- ❌ MUST NOT retrieve secrets
- ❌ MUST NOT depend on infrastructure

All dependencies are injected explicitly.

---

### 4️⃣ Native PHP Compatibility

This design fully supports:

- `password_hash()`
- `password_verify()`
- `password_needs_rehash()`

No wrapper logic or custom parsing is required.

---

## Responsibilities

### PasswordHasher

**Responsible for:**

- Hashing passwords
- Verifying passwords
- Determining rehash necessity

**Explicitly NOT responsible for:**

- Secret storage
- Secret loading
- Configuration access
- Encryption or decryption
- Key rotation

---

### ArgonPolicyDTO

- Defines Argon2id parameters
- Immutable
- Validated on construction
- Safe to change via DI without schema migration

---

### Pepper Provider

- Returns a single pepper value
- Must fail-closed if unavailable
- Can be backed by ENV, Vault, KMS, etc.
- Completely opaque to the hasher

---

## Rehash Strategy (LOCKED)

- Rehashing is **policy-driven**
- Occurs **only after successful verification**
- No background jobs
- No bulk migrations
- No hash inspection or extraction

Example usage pattern:

```

if (verify(password, hash)) {
if (needsRehash(hash)) {
rehash_and_store()
}
}

```

---

## Explicitly Forbidden

This module MUST NOT:

- ❌ Encrypt password hashes (AES, GCM, etc.)
- ❌ Use HKDF
- ❌ Rotate password keys
- ❌ Store multiple peppers
- ❌ Read environment variables
- ❌ Decrypt or inspect stored hashes

Violating any of the above is considered a **security regression**.

---

## Security Properties

- Resistant to database-only compromise
- Resistant to rainbow table attacks
- Resistant to cross-environment reuse
- Memory-hard by default
- Auditor-friendly and standards-compliant

---

## Testing Guarantees

The design enables:

- Deterministic unit tests
- Fake pepper providers
- Pepper rotation simulation
- Static analysis friendliness
- Zero secret leakage in tests

---

## Status

- ✅ Architecture locked
- ✅ Tests complete
- ✅ Production ready

Any change to this module **requires a new ADR**.

---
