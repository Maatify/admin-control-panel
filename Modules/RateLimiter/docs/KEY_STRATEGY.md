# RateLimiter — Key Strategy (Official)

**Module:** RateLimiter
**Namespace:** `Maatify\RateLimiter`
**Status:** LOCKED — Design & Security Contract
**Spec Version:** `1.0.0`

This document defines the **key construction strategy** used by the RateLimiter.  
Keys determine how limits, scores, correlation, and blocks are applied.

Incorrect key design weakens security, increases false positives, or enables evasion.  
This strategy is mandatory for all implementations.

---

## 1. Purpose of the Key Strategy

The key strategy exists to:

* Avoid IP-only decisions
* Support multi-device and distributed attack detection
* Reduce false positives in shared IP environments
* Enable progressive penalties scoped correctly
* Preserve privacy by avoiding raw identifiers
* Prevent key-explosion and storage exhaustion attacks
* Prevent threshold-gaming and IPv6 boundary exploits

Keys are **security primitives**, not implementation details.

---

## 2. Core Principles (Non-Negotiable)

* No single-signal key may determine a final decision
* IP-only keys are advisory, never final
* Device-aware keys are preferred whenever available
* Account-scoped keys are mandatory for authentication flows
* Keys must be deterministic and stable
* Keys must be privacy-safe (no raw PII)
* Keys MUST be bounded: no unbounded per-request key creation

---

## 3. Canonical Evaluation Keys

The module defines a **fixed, minimal key set**.

### 3.1 K1 — IP / IP Prefix (Hierarchical IPv6 Aggregation)

```
K1 = IP_PREFIX
```

**Definition:**

* IPv4: exact IP address
* IPv6: default normalization `/64`, with **hierarchical adaptive aggregation**:

#### IPv6 Hierarchical Aggregation (Required)

Correlation storage MUST support grouping IPv6 prefixes into larger scopes for detection:

* `/64` → `/48` → `/40` → `/32`

**Escalation Triggers (Deterministic):**

If, within a 10-minute correlation window:

1. Multiple `/64` prefixes under the same `/48` participate in correlation signals (spray/churn/dilution), then
  * detection operates at `/48` scope, and
  * enforcement remains scoped to **offending `/64` prefixes** unless otherwise stated by policy.

2. If the system observes correlation activity across **≥ 4 distinct `/48`** under the same `/40`, then
  * detection also operates at `/40` scope (anti “/48 boundary spray”),
  * enforcement remains scoped to offending `/48` and `/64` prefixes.

3. If correlation activity spans **≥ 8 distinct `/40`** under the same `/32`, then
  * detection operates at `/32` scope,
  * enforcement remains scoped to offending `/40`/`/48`/`/64` prefixes.

**Purpose:** Detect large-scale IPv6 spray without blanket-banning all users in a macro-scope.

**Usage:**

* Advisory limits
* Correlation rules
* Never used alone for final account blocks

---

### 3.2 K2 — IP Prefix + User-Agent

```
K2 = IP_PREFIX + UA
```

**Purpose:**

* Differentiate devices behind shared IPs
* Reduce NAT/VPN false positives
* Provide a safer fallback when DeviceFP confidence is LOW

---

### 3.3 K3 — IP Prefix + Device Fingerprint

```
K3 = IP_PREFIX + DeviceFP
```

**Purpose:**

* Device-scoped abuse detection
* Fair enforcement under shared IPs

**Confidence Rule:**
If `DeviceConfidence = LOW` (passive-only), K3 is permitted for throttling but MUST NOT be the sole basis of a global DeviceFP block (see `DECISION_MATRIX.md` 5.3).

---

### 3.4 K4 — Account Identifier

```
K4 = AccountID
```

**Purpose:**

* Protect accounts independently of network
* Detect distributed attacks
* Provide account memory across device rotation

**Rules:**

* AccountID MUST be a blind index or internal ID
* Raw usernames/emails MUST NOT be used

---

### 3.5 K5 — Account Identifier + Device Fingerprint

```
K5 = AccountID + DeviceFP
```

**Purpose:**

* Detect repeated failures from the same device
* Differentiate legitimate retries from attacks

**Constraint:**

* K5 MUST NOT be the only persistence mechanism
* Account-wide protection via K4 is mandatory
* K5 failures are subject to per-device micro-caps for budget eligibility (see `DECISION_MATRIX.md` 2.4.1)

---

## 4. Key Construction Rules

### 4.1 Normalization

All key components MUST be normalized before hashing:

* IP normalized to canonical form
* IPv6 normalized per hierarchical aggregation rules
* UA normalized (major version only)
* Device fingerprints normalized per `DEVICE_FINGERPRINT.md`
* Account identifiers pre-hashed or blind-indexed

Normalization MUST be deterministic and versioned.

---

### 4.2 Hashing

* Keys MUST be hashed using a keyed hash (HMAC)
* Plain hashes are forbidden
* HMAC secrets MUST be server-side only
* Key rotation MUST be supported
* Hash output MUST NOT be reversible

---

### 4.3 Key Rotation (Survival Contract)

Key rotation MUST NOT erase active enforcement.

Required behavior:

* Dual-key window is mandatory:

  * writes use `key_v2`
  * reads check `key_v2` then `key_v1`
* Window duration MUST be ≥ max block duration or key TTL
* After window expiry, old keys MAY be dropped

---

### 4.4 Namespacing & Scoping

All keys MUST include:

* Action / policy identifier
* Algorithm & version
* Environment scope
* Module scope (`rate_limiter`)

This prevents cross-action and cross-module leakage.

---

## 5. Key Usage by Context

### 5.1 Login / Password Authentication

Primary:

* K4 (Account)
* K5 (Account + Device)

Secondary:

* K2, K3, K1

Rules:

* IP-only keys MUST NOT cause final account blocks
* Missing fingerprint accelerates K4 escalation only

---

### 5.2 OTP / Step-Up Verification

Primary:

* K4
* K5

Secondary:

* K2

Rules:

* OTP enforcement MUST be account-centric
* IP-only signals are advisory

---

### 5.3 API Heavy / Brute Endpoints

Primary:

* K2
* K3

Secondary:

* K1

Account keys optional based on endpoint nature.

---

## 6. Correlation-Oriented Key Usage

Correlation relies on **relationships between keys**, not single counters.

### 6.1 Canonical Correlations

* Many K4 under one K1 → credential spray
* Many K3 under one K4 → distributed account attack
* Rapid churn of K3 under one K2 → device evasion
* Same DeviceFP across many K1 prefixes → fingerprint dilution

---

### 6.2 Queryability Requirement

Correlation storage MUST support one of:

* Atomic counters with TTL, OR
* Bounded exact sets, OR
* Approximate distinct counting only if:

  * error ≤ 2%
  * safety margin applied
  * near-threshold detections set WATCH flags (see `DECISION_MATRIX.md` 5.0)
  * confirmation window required for high-impact decisions

If guarantees cannot be met, exact bounded sets are mandatory.

---

## 7. Key Explosion & Flood Guards (Mandatory)

The system MUST protect against adversarial key creation.

Required constraints:

* Cap new DeviceFP-related keys per AccountID and per IP prefix
* After caps are exceeded:

  * DO NOT create additional unique keys
  * Route to a **rate-limited ephemeral bucket**
  * Ephemeral bucket MUST:

    * have TTL ≤ 30 minutes
    * NOT inherit historical **penalties**
    * MUST still honor **active blocks** (see `DEVICE_FINGERPRINT.md` 7.3 and `DECISION_MATRIX.md` 2.1 invariant)
    * escalate correlation signals only
    * continue to accumulate K4 scoring/budget signals

This prevents storage exhaustion without providing block evasion.

---

## 8. Anti-Patterns (Forbidden)

* IP-only blocking for authentication
* Raw email/username in keys
* Unbounded custom keys
* Per-request random keys
* Cross-policy shared counters
* Cross-module key reuse

Violations invalidate the security model.

---

## 9. Privacy Guarantees

The key strategy ensures:

* No raw identifiers are stored
* No cross-context tracking
* Keys expire naturally via TTL and decay
* Fingerprints are **risk signals, not identities**

Rules:

* Fingerprint presence ≠ trust
* Inconsistency > absence in severity
* Stability combined with abuse escalates risk

---

## 10. Stability & Versioning

* Key definitions are part of the public contract
* Any change requires:

  * version bump
  * documentation update
  * migration strategy with rotation survival

---

**This document is authoritative.  
Key strategy MUST NOT be altered without explicit versioning and security review.**
