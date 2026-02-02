# AbuseProtection Module

**Namespace:** `Maatify\AbuseProtection`
**Type:** Security Core Module (Library-grade)
**Purpose:** Prevent automated abuse (bots, brute force, scripted attacks)

---

## Scope & Responsibility (LOCKED)

This module provides **pure abuse-mitigation logic** based on:

* Failure signals
* Stateless signed abuse indicators
* Policy-driven decisions
* Pluggable challenge mechanisms

It **does NOT** implement:

* Authentication
* Sessions
* Rate limiting
* Cryptography
* Storage
* HTTP clients

---

## Design Principles

* **Pure domain logic**

    * No framework assumptions
    * No infrastructure dependencies
* **Stateless**

    * Abuse state carried via signed signals
* **Policy-driven**

    * Decisions expressed as isolated policies
* **Provider-agnostic**

    * CAPTCHA / Turnstile / PoW / JS challenges via contracts
* **Middleware-first**

    * Designed to run early in request lifecycle

---

## Core Concepts

### 1) Abuse Signal

A signed, time-bounded indicator representing client behavior
(e.g. login failure count).

* Issued by `AbuseProtectionService`
* Verified statelessly
* Signature handled by host adapter

### 2) Abuse Policy

Pure decision logic that determines **when a challenge is required**.

Example:

* Login failures ≥ N → require challenge

### 3) Challenge Provider

Pluggable verification mechanism:

* CAPTCHA
* Turnstile
* Proof-of-Work
* Custom JS challenges

---

## What This Module Knows

✅ Routes (string)
✅ HTTP method
✅ Failure count
✅ Request context (IP, UA)
❌ Identity
❌ Session
❌ User
❌ Passwords
❌ Crypto details

---

## Cryptography Boundary (IMPORTANT)

* This module **does not perform cryptography**
* All signing & verification is delegated via:

```php
AbuseSignatureProviderInterface
```

* The **host application** is responsible for:

    * Key rotation
    * HKDF / HMAC
    * Crypto contexts
    * Signature format

This guarantees:

* Zero crypto coupling
* Safe extraction
* Environment independence

---

## Typical Use Cases

* Login abuse protection
* Registration throttling
* Password reset defense
* Public form abuse mitigation
* API brute-force soft blocking

---

## Integration Pattern

```
Request
  ↓
AbuseProtectionMiddleware
  ↓
AbuseDecisionPolicy
  ↓
[optional] ChallengeProvider
  ↓
Controller
```

---

## Extraction Guarantee

This module is designed to be extracted as:

```
maatify/abuse-protection
```

✔️ Zero refactor
✔️ Zero architectural changes
✔️ Host provides adapters only

---

## Stability Contract

* Payload structure is **locked**
* Signing context is **versioned**
* Policy semantics are **explicit**
* No hidden side effects

---
