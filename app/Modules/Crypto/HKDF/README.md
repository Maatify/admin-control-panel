# HKDF Module

## Overview

The HKDF module provides a **strict, stateless, RFC-5869 compliant**
key derivation layer for the Admin Control Panel cryptographic architecture.

It is designed to support **multi-channel, multi-consumer encryption**
while maintaining strong **domain separation** and **blast-radius reduction**.

This module is intentionally minimal and does **not** perform encryption,
key rotation, or secret generation by itself.

---

## Architectural Position

The HKDF module is an **optional upper layer** that sits **above key rotation**
and **below cryptographic primitives**.

```text
KeyRotation (ACTIVE_ROOT_KEY)
        ↓
      HKDF
        ↓
 Context-Derived Keys
        ↓
Crypto Primitives (AES-GCM, HMAC, etc.)
````

It complements — but does not replace — existing modules:

* **Reversible Encryption** (ADR-001)
* **Key Rotation & Lifecycle Management** (ADR-002)

---

## Design Goals

* Strong **domain separation** between cryptographic consumers
* Deterministic and reviewable behavior
* Stateless and side-effect free operation
* Fail-closed security posture
* Compatibility with key rotation and audit guarantees
* Minimal, library-grade surface area

---

## Non-Goals

This module explicitly does **not**:

* Generate root secrets
* Manage key lifecycle or rotation
* Access environment variables
* Perform encryption or decryption
* Introduce automatic re-encryption
* Replace PEPPER or password hashing logic
* Create per-user or per-message dynamic keys

HKDF is **not a security feature by itself**.
It is a **key organization and isolation mechanism**.

---

## Context-Based Key Derivation

HKDF derives isolated keys using **explicit, stable, versioned context strings**.

Approved context characteristics:

* Constant (not user-controlled)
* Explicitly named
* Versioned (e.g. `:v1`)
* Bounded in length
* Validated at construction

Example categories (illustrative only):

```text
notification:email:v1
notification:sms:v1
notification:telegram:v1
notification:payload:v1
totp:storage:v1
export:file:v1
```

Each context produces a **cryptographically independent key**
derived from the same active root key.

---

## Security Properties

This module guarantees:

* Deterministic derivation (same input → same output)
* Strong isolation between contexts
* Reduced impact of key misuse or leakage
* No hidden state or implicit configuration
* Explicit failure on invariant violations

---

## Enforcement & Invariants

The following rules are **mandatory**:

1. Root keys MUST originate from the KeyRotation module
2. Contexts MUST be explicit and versioned
3. HKDF MUST be stateless and deterministic
4. HKDF MUST NOT access `.env` or external state
5. Derived keys MUST be treated as runtime-only material
6. Output length constraints are strictly enforced

Any violation is considered a **cryptographic architecture breach**.

---

## Related Architectural Decisions

* **ADR-001** — Reversible Encryption Primitive
* **ADR-002** — Key Rotation & Lifecycle Management
* **ADR-003** — HKDF for Context-Based Key Derivation

---

## Status

**Production-ready**
This module is complete, tested, and locked at the architectural level.

Integration into higher-level systems is optional
and must follow the documented invariants.
