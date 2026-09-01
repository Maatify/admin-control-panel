# ADR-010: Crypto Key Rotation via Bootstrap Wiring

**Status:** ACCEPTED
**Date:** 2026-01-09
**Decision Owner:** Architecture / Security
**Scope:** Admin Control Panel – Crypto Subsystem

---

## 🎯 Context

The project implements a centralized Crypto Subsystem used by multiple features:

* Email Queue (recipient & payload encryption)
* OTP / Verification
* TOTP Seeds
* PII Identifiers
* Password hashing (pepper-based, non-reversible)

The Crypto Subsystem is designed to be **extractable as a standalone library** in the future.
Therefore:

* Domain logic must remain crypto-agnostic.
* Crypto modules must not depend on environment variables.
* Operational concerns (keys, rotation, activation) must live outside the library.

A safe, deterministic **key rotation strategy** is required without:

* Data loss
* Downtime
* Re-encryption side effects
* Violating locked phases

---

## 🧠 Decision

### 🔒 Key Rotation is handled **via Bootstrap Wiring only**

We **do NOT** introduce a central “Crypto Manager” class.

Instead, rotation is enabled by:

* Supplying multiple keys at bootstrap time
* Explicitly marking exactly one key as ACTIVE
* Preserving old keys as INACTIVE (decrypt-only)

All cryptographic logic remains unchanged.

---

## 🧱 Architectural Principles

### 1️⃣ Crypto is a Subsystem, not a Service

* No feature calls “rotation” APIs
* No runtime mutation of keys
* No feature awareness of key lifecycle

Crypto behavior is determined **once**, during container bootstrap.

---

### 2️⃣ Library Isolation (Hard Requirement)

Crypto modules:

* DO NOT read `.env`
* DO NOT know about Email / OTP / Admin
* DO NOT implement operational policy

All environment parsing and validation happens in:

```
app/Bootstrap/Container.php
```

This guarantees future extraction as a standalone library.

---

## 🔄 Key Lifecycle Model (LOCKED)

```
ACTIVE   → used for encrypt()
INACTIVE → allowed for decrypt() only
RETIRED  → must not be loaded
```

Rules:

* Exactly **ONE ACTIVE key** must exist
* Zero or multiple ACTIVE keys → **FAIL-CLOSED**
* Old keys are NEVER overwritten
* Old keys are NEVER deleted implicitly

---

## 🆔 Key Identity Rule (CRITICAL)

> **Any change in key material MUST require a new key_id**

* Reusing a `key_id` with different key material is **forbidden**
* This prevents catastrophic decryption failures
* `key_id` is stored inside every encrypted payload

There are **NO default key IDs** (e.g. no implicit `v1`).

---

## 🔐 Rotation Strategy

### ✔️ Rotation occurs **on encrypt only**

* New encryptions always use the ACTIVE key
* Decrypt supports all loaded keys (ACTIVE + INACTIVE)

### ❌ No implicit re-encryption

* No re-encrypt on read
* No background mutation
* No hidden side effects

Optional future migration can be handled by a **dedicated CLI tool**, not by runtime logic.

---

## ⚙️ Configuration Model

### Environment Inputs

* `ADMIN_CRYPTO_ACTIVE_KEY_ID` (required)
* `ADMIN_CRYPTO_KEYS` (required JSON array)
* `EMAIL_ENCRYPTION_KEY` (forbidden legacy source; no fallback)

Example:

```env
ADMIN_CRYPTO_KEYS='[
  {"id":"v1","key":"...old..."},
  {"id":"v2","key":"...new..."}
]'
ADMIN_CRYPTO_ACTIVE_KEY_ID=v2
```

Validation is strict and fail-closed.

---

## 🧪 Safety & Determinism

The system guarantees:

* Deterministic decryption
* Zero downtime rotation
* No data corruption
* phpstan level max compliance
* No architectural drift

---

## 🚫 Explicit Non-Goals

This ADR does **NOT** introduce:

* Runtime rotation APIs
* Auto-migration
* Key deletion logic
* Feature-level crypto awareness
* Central “CryptoKernel” or “CryptoManager” classes

---

## 🏁 Outcome

* Crypto Rotation is **enabled, safe, and production-ready**
* Crypto Subsystem remains **pure, isolated, and extractable**
* Operational control is explicit and auditable
* All previous architectural decisions remain intact

---

## 🔒 Final Note

This ADR is **LOCKED**.
Any future change to crypto rotation **requires a new ADR**.

---
