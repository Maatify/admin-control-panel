# Rate Limiter — Policy Presets (Official)

**Module:** RateLimiter
**Namespace:** `Maatify\RateLimiter`
**Status:** LOCKED — Policy Contract
**Spec Version:** `1.0.0`
**Change Class:** Hardening Alignment

This document defines the **official policy presets** provided by the Rate Limiter module.

Policies are **pre-configured, production-ready rule sets** built strictly on top of:

* `DECISION_MATRIX.md`
* `KEY_STRATEGY.md`
* `FAILURE_SEMANTICS.md`
* `DEVICE_FINGERPRINT.md`

Consumers SHOULD use these presets instead of defining custom rules.

---

## 1. Global Policy Principles (Non-Negotiable)

All policies MUST comply with the following rules:

* Decisions are multi-signal (IP, Device, Account)
* Account-level protection is mandatory for authentication flows
* Progressive blocking is mandatory
* Decay is enabled but MUST NOT allow deterministic equilibrium
* Device Fingerprint is evaluated when available
* Correlation rules are enforced with bounded thresholds + near-threshold watch
* Failure semantics are explicitly defined
* **No policy may introduce account-wide hard blocks based solely on attempt counts**
* Budgets MAY increase friction but MUST NOT enable renewable or scheduled denial-of-owner

---

## 2. Login Protection Policy

### Policy Identifier

```
login_protection
```

### Intended Use

* Password-based login endpoints
* Username/email + password authentication
* Admin and customer login flows

---

### Signals Used

* K1 — IP Prefix
* K2 — IP + UA
* K3 — IP + DeviceFP
* K4 — AccountID
* K5 — AccountID + DeviceFP

Account-level keys (K4) are authoritative.

---

### Scoring Rules (per `DECISION_MATRIX.md`)

| Scenario                                    | Key | Score |
| ------------------------------------------- | --- | ----- |
| Failed login (same known device)            | K5  | +2    |
| Failed login (new device)                   | K4  | +3    |
| Missing device fingerprint                  | K2  | +4    |
| Repeated missing fingerprint (same account) | K4  | +6    |
| Credential spray (correlation)              | K1  | +5    |

**Notes:**

* Missing fingerprint is treated as an **evasion signal**, not a bypass.
* New device creation is capped per account (see `DEVICE_FINGERPRINT.md`).
* Repeated missing fingerprint requires two consecutive failures within 30 minutes.

---

### Thresholds (Account Score)

| Account Score | Decision         |
| ------------- | ---------------- |
| < 5           | ALLOW            |
| 5 – 7         | SOFT_BLOCK (L1)  |
| 8 – 11        | HARD_BLOCK (L2)  |
| ≥ 12          | HARD_BLOCK (L3+) |

---

### Login Failure Budget (Fixed 24h Epoch)

| Condition                                  | Decision                             |
| ------------------------------------------ | ------------------------------------ |
| ≥ 20 failed login attempts within 24 hours | SOFT_BLOCK (Account), minimum **L3** |

**Budget Rules (mandatory):**

* Budget applies at **K4 (AccountID)**.
* Budget uses a **fixed 24h epoch** (no extension).
* Budget MUST NOT directly produce `HARD_BLOCK(Account)`.
* Budget enforcement applies on **failures / attempts**, never on successful login.
* Requests from **trusted session devices** downgrade the applied level by **one step**, never below L2.
* Same-device failures become budget-eligible after `failed_login_count(K5) ≥ 8` within the epoch.

---

### Anti-Equilibrium Gate

If `SOFT_BLOCK` is reached **≥ 3 times within 6 hours** for the same account:

* Next failure MUST apply `HARD_BLOCK(Account)` at minimum **L2**.

---

### Progressive Blocking

Uses the global penalty ladder defined in `DECISION_MATRIX.md`.

**Account-level blocks persist regardless of device rotation.**

---

### Decay Rules

Uses `DECISION_MATRIX.md` decay rules + deterministic modifiers.

---

### Failure Semantics

```
FAIL_CLOSED
```

(Handled strictly per `FAILURE_SEMANTICS.md`.)

---

## 3. OTP / Step-Up Protection Policy

### Policy Identifier

```
otp_protection
```

### Intended Use

* OTP verification endpoints
* Step-Up authentication
* Sensitive confirmation actions

---

### Signals Used

* K4 — AccountID
* K5 — AccountID + DeviceFP
* K2 — IP + UA

IP-only signals are advisory only.

---

### Scoring Rules

| Scenario                                 | Key | Score |
| ---------------------------------------- | --- | ----- |
| OTP failure from same device             | K5  | +4    |
| OTP failure from new device              | K4  | +5    |
| OTP failure without fingerprint          | K2  | +6    |
| Repeated OTP failure without fingerprint | K4  | +8    |

---

### Thresholds

| Score | Decision         |
| ----- | ---------------- |
| < 4   | ALLOW            |
| 4 – 6 | SOFT_BLOCK (L1)  |
| 7 – 9 | HARD_BLOCK (L2)  |
| ≥ 10  | HARD_BLOCK (L3+) |

---

### OTP Failure Budget (Fixed 24h Epoch)

| Condition                         | Decision                             |
| --------------------------------- | ------------------------------------ |
| ≥ 10 OTP failures within 24 hours | SOFT_BLOCK (Account), minimum **L4** |

**Rules:**

* Budget is account-scoped and uses a fixed epoch (no extension).
* Applies only on failures, never on successful OTP.
* Trusted session devices downgrade one level, never below L3.
* Includes the **Recovery Collision Guard** from `DECISION_MATRIX.md` 3.3.5.

---

### Decay Rules

OTP follows `DECISION_MATRIX.md` decay rules (no custom OTP-only decay in policies).  
(OTP strictness is driven by higher score deltas + budget + thresholds.)

---

### Failure Semantics

```
FAIL_CLOSED
```

---

## 4. API Heavy / Brute-Force Protection Policy

### Policy Identifier

```
api_heavy_protection
```

### Intended Use

* Public APIs
* Resource-intensive endpoints
* Export, search, bulk operations

---

### Signals Used

* K2 — IP + UA
* K3 — IP + DeviceFP
* K1 — IP Prefix

---

### Rate Enforcement

| Condition        | Decision           |
| ---------------- | ------------------ |
| Minor overuse    | SOFT_BLOCK         |
| Moderate overuse | HARD_BLOCK (L1–L2) |
| Severe overuse   | HARD_BLOCK (L3–L4) |

Maximum block level is capped at **L4**.

---

### Decay Rules

* IP score: −1 every 2 minutes

---

### Failure Semantics

```
FAIL_OPEN
```

**Constraints:**

* FAIL_OPEN MUST NOT allow unlimited throughput.
* Implementations MUST apply coarse local throttles during outage (see `FAILURE_SEMANTICS.md`).

---

## 5. Correlation Rules (Applied to All Policies)

Correlation rules are mandatory and enforced per `DECISION_MATRIX.md`, including:

* Near-threshold WATCH flags (anti N-1 gaming)
* Confidence constraints for DeviceFP dilution blocks

---

## 6. Custom Policies (Restricted)

Custom policies MAY exist, but:

* MUST pass validation
* MUST include:

  * Account-level protection (if auth-related)
  * Device awareness
  * Decay + budgets + anti-equilibrium gate
  * Explicit failure semantics
* MUST NOT weaken defaults
* MUST NOT introduce renewable denial-of-owner vectors

Invalid policies MUST be rejected at runtime.

---

## 7. Versioning & Stability

* Policy behavior is part of the public contract
* Any change requires:

  * Policy version bump
  * Changelog entry
  * Security review

Policy identifiers MUST remain stable.

---

**This document is authoritative.  
Any deviation requires explicit approval and versioning.**
