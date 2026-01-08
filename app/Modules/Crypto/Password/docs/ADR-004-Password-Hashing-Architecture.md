# 🔒 ADR-004 — Password Hashing Architecture (Keyless, DI-based)

**Status:** ✅ ACCEPTED / LOCKED
**Date:** 2026-01-08
**Decision Owner:** Security Architecture
**Scope:** All password-based authentication (Admins, Users, Customers)

---

## 🎯 Context

The system requires a **high-assurance password storage and verification mechanism** that:

* Remains secure if the database is exposed
* Does not rely on reversible encryption
* Avoids introducing new single points of failure
* Is testable, reviewable, and infrastructure-agnostic
* Complies with modern security standards (OWASP, NIST guidance)

Previous discussions evaluated adding AES-GCM encryption on top of password hashes to “hide” stored values in the database. This approach was rejected due to architectural and threat-model violations.

---

## 🧠 Decision

### ✅ Passwords SHALL be stored using:

```
User Password
   → HMAC-SHA256 with PEPPER
   → Argon2id (memory-hard hashing)
   → Stored as final hash (no encryption)
```

### ❌ Password hashes SHALL NOT be encrypted (e.g., AES-GCM).

---

## 🔑 Dependency Injection Rule (HARD)

* The password hashing component:

    * ❌ MUST NOT read environment variables
    * ❌ MUST NOT know where secrets come from
    * ❌ MUST NOT load configuration directly

* All secrets (e.g., PEPPER) and policies MUST be injected via interfaces.

This enforces:

* Clean architecture
* Testability
* Infrastructure independence
* Zero hidden dependencies

---

## 🧱 Architectural Components

### 1️⃣ PasswordHasher

**Responsibilities:**

* Hash passwords
* Verify passwords
* Determine rehash necessity

**Explicitly NOT responsible for:**

* Secret storage
* Secret retrieval
* Configuration loading
* Encryption or decryption

---

### 2️⃣ Pepper Provider (Injected)

```text
PasswordHasher
   ← PasswordPepperProviderInterface
```

* Returns a single, constant pepper value
* Throws on failure (fail-closed)
* Source may be ENV, Vault, KMS, etc.
* PasswordHasher is agnostic to the source

---

### 3️⃣ Argon Policy (Injected)

* Defines Argon2id parameters
* Immutable
* Versioned via DI
* Changeable without schema migration

---

## 🔐 Security Rationale

### Why HMAC → Argon2id

* Pepper is applied **before hashing**, increasing effective entropy of the password input
* Argon2id remains the **final stored format**, fully compatible with:

    * `password_verify()`
    * `password_needs_rehash()`
* No cryptographic assumptions are violated
* Rehash lifecycle is clean, explicit, and standards-compliant

---

### Why hashing is correct

* Password hashes are **verification proofs**, not secrets
* They are designed to be safe even if exposed
* Argon2id ensures high computational cost
* HMAC with pepper adds protection against:

    * Database-only compromise
    * Precomputed attacks
    * Cross-environment reuse

---

### Why encryption was rejected

Encrypting password hashes:

* Introduces a **reversible dependency** (encryption key)
* Creates a **single point of failure**
* Weakens security if DB + key are compromised
* Adds operational risk (rotation, outages, misconfiguration)
* Breaks the “keyless safety” property of password hashing

This was deemed a **security regression**, not a defense-in-depth improvement.

---

## 🔄 Rehash Strategy (LOCKED)

* Rehashing occurs **only after successful verification**
* Stored hashes remain native Argon2id format
* No stored hash is ever decrypted
* No background migrations
* No bulk operations
* Transparent to users

---

## 🚫 Explicitly Forbidden

* ❌ AES / GCM encryption for passwords
* ❌ HKDF usage in password storage
* ❌ Key rotation for password hashes
* ❌ Multiple peppers per environment
* ❌ Reading ENV or config inside hasher
* ❌ Any form of password hash decryption

---

## 🧪 Testing Guarantees

This design enables:

* Deterministic unit tests (via fake pepper providers)
* Pepper rotation simulation
* Native rehash verification
* Static analysis friendliness
* No secret leakage in logs or tests

---

## 🏁 Outcome

* Password storage is **keyless, robust, and future-proof**
* Architecture aligns with global best practices
* No hidden coupling to infrastructure
* Clear separation of responsibilities
* Security review–friendly and auditor-proof

---

## 🔒 Final Note

This decision is **frozen**.

Any future change to password storage **requires a new ADR** and explicit threat-model justification.

---
