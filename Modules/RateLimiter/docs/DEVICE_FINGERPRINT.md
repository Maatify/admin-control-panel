# Device Fingerprint — Official Specification

**Module:** RateLimiter
**Namespace:** `Maatify\RateLimiter`
**Status:** LOCKED — Behavioral & Privacy Contract
**Spec Version:** `1.0.0`

This document defines the **Device Fingerprint system** used by the Rate Limiter.  
It specifies how device identity is derived, normalized, hashed, bounded, and evaluated.

The Device Fingerprint is designed to be:

* Privacy-respecting
* Deterministic
* Storage-agnostic
* Resistant to trivial, synthetic, and replay-based evasion
* Suitable for security decisions (not tracking, not identity)

---

## 1. Purpose

The Device Fingerprint exists to support:

* Rate limiting decisions
* Attack correlation
* Distributed and multi-device attack detection
* Reduction of false positives caused by IP-only checks
* Fair enforcement in shared IP environments

It is **not** intended for:

* User tracking
* Cross-site identification
* Advertising or analytics
* Acting as an authentication factor
* Acting as proof of device ownership

---

## 2. Core Principles (Non-Negotiable)

* Raw headers MUST NOT be stored
* Raw fingerprint components MUST NOT be persisted
* Only hashed fingerprints may be stored or logged
* Fingerprints are probabilistic risk signals, not identities
* Fingerprints MAY change naturally
* Absence of fingerprint data is a security signal, not a fault
* Fingerprints MUST be bounded to prevent storage abuse
* Fingerprints MUST NOT be treated as trusted identifiers
* Account-level signals always override device-level signals
* Passive-only fingerprints MUST NOT be used to trigger global DeviceFP blocks

---

## 3. Fingerprint Levels

The system defines **three fingerprint levels**.  
Levels increase confidence but NEVER replace account-level protection.

### 3.1 Level 1 — Passive Fingerprint (Mandatory)

**Source:** Backend-only  
**Availability:** Always available

#### Inputs (Normalized)

* User-Agent (major version only)
* Accept-Language (normalized and ordered)
* Platform / OS hints
* HTTP/TLS-level hints (if available)

#### Output

* `passive_fingerprint` (hashed)

#### Properties

* Requires no client-side code
* Available on first request
* Lowest confidence
* Baseline signal only
* MUST NOT be used alone for blocking decisions
* MUST NOT be used for `HARD_BLOCK(DeviceFP)` decisions (see `DECISION_MATRIX.md` 5.3)

---

### 3.2 Level 2 — Client-Assisted Fingerprint (Optional)

**Source:** Minimal client-provided hints  
**Availability:** Optional

#### Client Inputs

* Timezone offset (bucketed)
* Screen resolution (bucketed)
* Platform hint
* Browser major version
* Client-generated random ID (local storage)

#### Mandatory Constraints

* Inputs MUST be normalized
* High-entropy fingerprinting (canvas, audio, WebGL, fonts) is FORBIDDEN
* Client ID MUST be random, opaque, and non-derivable
* Client ID MUST NOT be treated as stable identity
* Absence MUST NOT block alone

#### Output

* `client_fingerprint` (hashed)

#### Properties

* Medium confidence
* Improves differentiation under NAT/VPN
* Fully optional
* Absence increases suspicion weight only

---

### 3.3 Level 3 — Session-Bound Device Identifier (Optional)

**Source:** Server-generated  
**Availability:** Post-authentication only

#### Behavior

* Server generates a cryptographically random identifier
* Identifier is:

  * Bound to authenticated AccountID
  * Stored in an HttpOnly, Secure cookie
* Rotation MUST NOT reset account-level penalties
* Loss MUST NOT imply trust reset

#### Output

* `session_device_id` (hashed)

#### Properties

* Highest confidence
* Account-scoped
* Advisory only
* NOT proof of device ownership

---

## 4. Device Identity Resolution

All fingerprint levels are combined into a single resolved identity.

### DeviceIdentity Components

* Passive fingerprint (mandatory)
* Client fingerprint (optional)
* Session device identifier (optional)
* Confidence level (derived)
* Stability flag (derived)

### Confidence Levels

| Signals Present            | Confidence |
| -------------------------- | ---------- |
| Passive only               | LOW        |
| Passive + Client           | MEDIUM     |
| Passive + Client + Session | HIGH       |

**Rule:**  
Confidence affects **scoring weight** and certain correlation enforcement constraints; never authorization.

---

## 5. Fingerprint Hashing

All fingerprints MUST be hashed using a keyed hash (HMAC).

### Hashing Rules

* Plain hashes are forbidden
* Secrets MUST be server-side only
* Hashes MUST be module-scoped
* Hash output MUST be deterministic
* Hash rotation MUST preserve enforcement continuity
* Fingerprint hashes MUST NOT be reused outside RateLimiter

---

## 6. Normalization Rules

To ensure stability and collision resistance:

* UA normalized to major version only
* Languages normalized, ordered, and truncated
* Screen resolution bucketed coarsely
* Platform identifiers canonicalized
* Missing values normalized explicitly (never omitted)
* Normalization rules MUST be versioned

---

## 7. Churn, Evasion & Flood Protection

### 7.1 Churn Detection (Mandatory)

The system MUST detect:

* Rapid fingerprint changes under same IP + UA
* Fingerprint disappearance after prior presence
* Excessive “first-seen” fingerprints on auth endpoints
* Same fingerprint reused across many IP prefixes
* Oscillation between present/missing fingerprints

---

### 7.2 Mandatory Responses

Detected churn or evasion MUST trigger:

* Increased scoring weight
* Accelerated escalation
* Correlation-based enforcement

Churn MUST NOT create unlimited keys.

---

### 7.3 New Device Flood Guard (Mandatory) — Ephemeral Bucket Contract

To prevent storage exhaustion and account poisoning:

* Hard caps MUST exist on:

  * New fingerprints per AccountID
  * New fingerprints per IP prefix
* Caps MUST be time-windowed and bounded
* After cap is exceeded:

  * DO NOT create new fingerprint keys
  * Route attempts to an **ephemeral device bucket**

#### 7.3.1 Ephemeral Bucket Properties (LOCKED)

Ephemeral bucket MUST:

* Have TTL ≤ 30 minutes
* NOT inherit historical **penalties**
* NOT create persistent device identities / keys
* Continue to accumulate **K4 (Account)** scoring and budget signals
* Escalate **correlation signals** only (bounded sets/counters)

#### 7.3.2 Critical Safety Invariants (Anti “Ephemeral Ghost”)

Ephemeral bucket MUST NOT be a bypass:

1. **Active blocks are always enforced**
  * Pre-attempt hard-block checks (K4/K5/K3/K1) MUST run **before** deciding to route to ephemeral.
  * If a request presents a DeviceFP that is already under active `HARD_BLOCK` (K3/K5), that block MUST apply.

2. **Ephemeral routing cannot “erase” a block**
  * Ephemeral mode may avoid creating new keys, but MUST still consult existing active block state.

3. **Ephemeral routing is scoped**
  * Ephemeral applies per (IP_PREFIX, AccountID) context; it MUST NOT become a global “unknown device” bucket.

---

## 8. Replay, Pollution & Impersonation Resistance

* Fingerprints MUST NOT be treated as secrets
* Replay MUST NOT grant trust or stability
* Captured fingerprints MUST NOT allow impersonation
* Fingerprint dilution MUST NOT permanently block legitimate users
* Account-level signals MUST dominate all outcomes

Device fingerprints accelerate suspicion; they do not define guilt.

---

## 9. Usage in Rate Limiting

Device fingerprints are used to construct:

* `IP_PREFIX + DeviceFP` (K3)
* `AccountID + DeviceFP` (K5)

Rules:

* Device-aware keys are preferred over IP-only
* Device-only enforcement MUST NOT override account protection
* Device trust MUST decay naturally
* Absence is less severe than inconsistency
* Passive-only fingerprints cannot trigger `HARD_BLOCK(DeviceFP)` (see `DECISION_MATRIX.md` 5.3)

---

## 10. Privacy & Compliance Guarantees

This system guarantees:

* No raw fingerprint inputs stored
* No cross-context or cross-module tracking
* No permanent identifiers
* Mandatory TTL on all fingerprint data
* Privacy-by-design compliance

Frequency analysis MUST NOT be used for identity inference.

---

## 11. Stability & Versioning

* Fingerprint algorithms are versioned
* Any change requires:

  * Version bump
  * Migration strategy
  * Changelog entry
* Old versions MUST remain readable during transition

---

**This document is authoritative.  
Any deviation requires explicit versioning and security approval.**
