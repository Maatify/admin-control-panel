# Rate Limiter — Decision Matrix (Official Specification)

**Module:** RateLimiter
**Namespace:** `Maatify\RateLimiter`
**Status:** LOCKED — Behavioral Contract
**Scope:** Login, OTP, API Heavy Endpoints
**Spec Version:** `1.0.0`

This document defines the **deterministic decision rules** used by the Rate Limiter.  
It is a **behavioral contract**, not explanatory documentation.

Any implementation, policy, or test MUST comply with this matrix exactly.

---

## 0. Definitions

### Signals (Inputs)

* `IP` — Client IP address (**IPv4** or **IPv6 prefix-aware**)
* `UA` — Normalized User-Agent (major version only)
* `DeviceFP` — Device Fingerprint (passive / client / session)
* `DeviceConfidence` — `{LOW, MEDIUM, HIGH}` derived per `DEVICE_FINGERPRINT.md`
* `AccountID` — Account identifier (blind index or internal ID)
* `Action` — Logical action (e.g. `auth.login`, `auth.otp`, `api.heavy`)

### Decisions (Outputs)

* `ALLOW`
* `SOFT_BLOCK` — Temporary throttle with Retry-After
* `HARD_BLOCK` — Active block for a calculated duration

### Evaluation Keys

* `K1 = IP_PREFIX`
* `K2 = IP_PREFIX + UA`
* `K3 = IP_PREFIX + DeviceFP`
* `K4 = AccountID`
* `K5 = AccountID + DeviceFP`

### Trusted Session Device (Definition)

A request is considered from a **trusted session device** only if:

* A **Level 3 session-bound device identifier** is present (per `DEVICE_FINGERPRINT.md`), **and**
* It is bound to the same `AccountID`, **and**
* It matches the server’s stored association for that account.

No other “trust” signal is allowed.

---

## 1. Evaluation Order (Fail-Fast)

Evaluation order is **strict and non-negotiable**.

1. Active Hard Block check (all relevant keys)
2. Account-wide cumulative protection (K4)
3. Account + Device protection (K5)
4. Device-based protection (K3)
5. IP-based protection (K1 / K2)
6. Correlation rules
7. Final decision aggregation

Any `HARD_BLOCK` immediately terminates evaluation.

---

## 2. Login / Password Attempts

### 2.1 Pre-Attempt Hard Block Check

| Condition                      | Key | Decision   |
| ------------------------------ | --- | ---------- |
| Active block on account        | K4  | HARD_BLOCK |
| Active block on account+device | K5  | HARD_BLOCK |
| Active block on device         | K3  | HARD_BLOCK |
| Active block on IP / IP prefix | K1  | HARD_BLOCK |

**Invariant:** Safety-valves (ephemeral routing, degraded mode, etc.) MUST NOT bypass this step.

---

### 2.2 Failed Login Scoring

| Scenario                                    | Key | Score Delta | Notes                  |
| ------------------------------------------- | --- | ----------- | ---------------------- |
| Failure from same known device              | K5  | +2          | Lower risk, not safe   |
| Failure from new device                     | K4  | +3          | Suspicious             |
| Missing device fingerprint                  | K2  | +4          | Evasion indicator      |
| Repeated missing fingerprint (same account) | K4  | +6          | Accelerated escalation |
| IP attempts multiple accounts               | K1  | +5          | Credential spray       |

#### 2.2.1 “Repeated missing fingerprint” Rule (Deterministic)

A “Repeated missing fingerprint (same account)” event applies if:

* `DeviceFP` is missing, **and**
* The **previous failed login** for the same `AccountID` within the last **30 minutes** was also missing `DeviceFP`.

---

### 2.3 Login Thresholds (Per Account Score)

| Account Score | Decision   | Block Level |
| ------------- | ---------- | ----------- |
| < 5           | ALLOW      | —           |
| 5 – 7         | SOFT_BLOCK | L1          |
| 8 – 11        | HARD_BLOCK | L2          |
| ≥ 12          | HARD_BLOCK | L3+         |

---

### 2.4 Login Failure Budget (24h) — Anti-Harassment Contract

A budget exists to stop low-and-slow abuse **without enabling remote permanent lockout**.

| Condition                                  | Decision                                                 |
| ------------------------------------------ | -------------------------------------------------------- |
| ≥ 20 failed login attempts within 24 hours | **SOFT_BLOCK (Account)** with **minimum block level L3** |

#### 2.4.1 Budget Eligibility (Deterministic)

Budget counters are counted at **K4 (AccountID)** and include:

* Any failed login that increments **K4** (e.g., “new device”, “repeated missing fingerprint”), and
* Any failed login **without DeviceFP** (K2 missing fingerprint), and
* Any failed login from **same known device (K5)** **after** a per-device micro-cap:

    * If `failed_login_count(K5) ≥ 8 within 24h`, subsequent failures from that same `K5` become budget-eligible.

This prevents “same-device equilibrium” from bypassing the budget indefinitely.

#### 2.4.2 Budget Epoch (Fixed Window, No Extension)

To prevent “rolling-window prisoning”, the budget uses a fixed **Budget Epoch**:

* The epoch starts at the timestamp of the **first budget-eligible failure** that contributes to the threshold crossing.
* Once the threshold is crossed, the epoch becomes **BudgetActive** and ends exactly **24h** after epoch start.
* **Additional failures MUST NOT extend the epoch end time.**
* The budget counter MAY continue counting for analytics, but **must not** extend enforcement.

> This is a behavioral guarantee: “44 attempts cannot stretch a 24h prison into an infinite prison.”

#### 2.4.3 Enforcement Rule (No Hourly Re-Issuance)

While **BudgetActive**:

* The budget decision applies **only** on:
    * a failed login attempt, OR
    * an unauthenticated login attempt that would otherwise be ALLOW.
* The budget decision MUST NOT re-issue on a timer.
* A successful login MUST return `ALLOW` and MUST NOT be blocked by an account budget.

This prevents “scheduled denial-of-owner” loops where the victim is periodically blocked even when correct.

#### 2.4.4 Cooldown (Anti-Spam Guard)

A budget-issued `SOFT_BLOCK(Account)` has an enforcement cooldown:

* Login budget cooldown: **60 minutes**
* Cooldown prevents repeating the same `SOFT_BLOCK(Account)` decision on every request.
* Cooldown does **not** prevent normal scoring thresholds from producing `HARD_BLOCK` decisions.

#### 2.4.5 Trusted Session Downgrade

If a request is from a **trusted session device**:

* The budget decision MUST be downgraded by one level (e.g., L3 → L2),
* but never below L2 while BudgetActive.

---

### 2.5 Anti-Equilibrium Gate (Deterministic)

To prevent mathematically planned “low-and-slow” equilibrium:

If, for the same `AccountID`:

* The account enters `SOFT_BLOCK` (any reason) **≥ 3 times within 6 hours**,

THEN:

* Apply `HARD_BLOCK (Account)` at **minimum level L2** on the next failure.

This is deterministic, testable, and breaks stable decay arithmetic.

---

## 3. OTP / Step-Up Verification

OTP actions are **stricter** than password attempts.

### 3.1 Failed OTP Scoring

| Scenario                                  | Key | Score Delta |
| ----------------------------------------- | --- | ----------- |
| OTP failure from same device              | K5  | +4          |
| OTP failure from new device               | K4  | +5          |
| OTP failure without device fingerprint    | K2  | +6          |
| Repeated OTP failures without fingerprint | K4  | +8          |

#### 3.1.1 “Repeated OTP failures without fingerprint” Rule (Deterministic)

Applies if:

* `DeviceFP` is missing, **and**
* The **previous OTP failure** for the same `AccountID` within the last **30 minutes** was also missing `DeviceFP`.

---

### 3.2 OTP Thresholds

| Score | Decision   | Block Level |
| ----- | ---------- | ----------- |
| < 4   | ALLOW      | —           |
| 4 – 6 | SOFT_BLOCK | L1          |
| 7 – 9 | HARD_BLOCK | L2          |
| ≥ 10  | HARD_BLOCK | L3+         |

---

### 3.3 OTP Failure Budget (24h) — Anti-Weaponization

| Condition                         | Decision                                                 |
| --------------------------------- | -------------------------------------------------------- |
| ≥ 10 OTP failures within 24 hours | **SOFT_BLOCK (Account)** with **minimum block level L4** |

#### 3.3.1 Budget Epoch & No-Extension

OTP budget uses the same **fixed Budget Epoch** rules as 2.4.2.

#### 3.3.2 Enforcement Rule (Owner Safety)

While OTP BudgetActive:

* Budget `SOFT_BLOCK(Account)` applies only on **OTP failures** (not on successful OTP).
* A successful OTP MUST return `ALLOW` and MUST NOT be blocked by an account budget.

#### 3.3.3 Cooldown

OTP budget cooldown: **120 minutes**

#### 3.3.4 Trusted Session Downgrade

Trusted session devices downgrade by one level, never below **L3** while BudgetActive.

#### 3.3.5 Recovery Collision Guard (Deterministic)

To mitigate “preload 9 then wait for victim” attacks:

If `otp_budget_count(K4) = 9 within epoch` and the next OTP attempt is from:

* a device with `DeviceConfidence = HIGH` (session-bound device), OR
* a device previously verified for this account (known K5),

THEN:

* The 10th failure MUST NOT immediately activate the budget.
* Instead, apply `SOFT_BLOCK (L2)` once and require **one additional failure** to activate the budget.

This adds a deterministic safety margin for legitimate recovery attempts from known devices.

---

## 4. API Heavy / Brute-Force Endpoints

### 4.1 Rate Enforcement

| Condition               | Key | Decision   |
| ----------------------- | --- | ---------- |
| Minor limit exceeded    | K2  | SOFT_BLOCK |
| Moderate limit exceeded | K3  | HARD_BLOCK |
| Severe limit exceeded   | K1  | HARD_BLOCK |

---

## 5. Correlation Rules (Attack Detection)

### 5.0 Near-Threshold Watch (Mandatory Anti N-1 Gaming)

Many rules use thresholds. To prevent stable `N-1` bypass:

* If a correlation metric reaches **(threshold − 1)** within a window,
    * set a **WATCH flag** for the same scope (key) with TTL **30 minutes**.
* If the same WATCH flag is observed **twice** within its TTL,
    * upgrade the decision to the same action as if the threshold were met.

This is deterministic and testable (no randomness), and blocks “hover forever at N-1”.

---

### 5.1 Credential Spray Detection (IP-Based)

| Rule                      | Condition                                   | Decision        |
| ------------------------- | ------------------------------------------- | --------------- |
| IP attempts many accounts | `distinct(AccountID) ≥ 5 within 10 minutes` | HARD_BLOCK (IP) |

**Advisory Constraint:**  
IP-only blocks are advisory and MUST NOT affect **trusted session devices**.

---

### 5.2 Distributed Account Attack (Device Rotation)

| Rule                               | Condition                                  | Decision                      |
| ---------------------------------- | ------------------------------------------ | ----------------------------- |
| Account accessed from many devices | `distinct(DeviceFP) ≥ 4 within 10 minutes` | HARD_BLOCK (each involved K5) |

**IMPORTANT:**

* The AccountID MUST NOT be hard-blocked immediately by this rule.
* Only the **involved devices for that account** are blocked (apply blocks on each **K5**).
* Account-wide blocking requires **repeated occurrences** (see 5.2.1).

#### 5.2.1 Repeated Occurrence (Account Escalation Gate)

If the condition in 5.2 occurs **≥ 3 times within 24 hours** for the same `AccountID`, then:

* Apply **HARD_BLOCK (Account)** at **minimum level L4**.

This is the only correlation-based path that may hard-block the account.

---

### 5.3 Fingerprint Evasion / Churn

| Rule                             | Condition                            | Decision              |
| -------------------------------- | ------------------------------------ | --------------------- |
| Rapid fingerprint changes        | `≥ 3 changes within 10 minutes`      | HARD_BLOCK (IP + UA)  |
| Same fingerprint across many IPs | `distinct(IP) ≥ 6 within 10 minutes` | HARD_BLOCK (DeviceFP) |

**Confidence Constraint (Anti-Passive Poisoning):**

`HARD_BLOCK(DeviceFP)` in 5.3 is allowed only if:

* `DeviceConfidence ≥ MEDIUM` (client-assisted or session-bound), AND
* the dilution is confirmed in a second window (two consecutive 10-minute windows).

If `DeviceConfidence = LOW` (passive-only), the decision MUST downgrade to:

* `HARD_BLOCK (IP + UA)` (K2), not DeviceFP.

---

### 5.4 New Device Flood Protection

| Rule                              | Condition                            | Decision                 |
| --------------------------------- | ------------------------------------ | ------------------------ |
| Excessive new devices per account | `≥ 6 new DeviceFP within 15 minutes` | SOFT_BLOCK (Account)     |
| Continued flood after soft block  | Same window                          | HARD_BLOCK (each new K5) |

New DeviceFP creation MUST be capped to prevent storage exhaustion.

**Invariant:** Ephemeral routing MUST NOT erase active blocks (see `DEVICE_FINGERPRINT.md`).

---

## 6. Progressive Blocking (Penalty Ladder)

| Block Level | Duration   |
| ----------- | ---------- |
| L1          | 15 seconds |
| L2          | 60 seconds |
| L3          | 5 minutes  |
| L4          | 30 minutes |
| L5          | 6 hours    |
| L6          | 24 hours   |

Each escalation increases the level.  
Levels decay **slower** as severity increases.

---

## 7. Decay Rules (Penalty Persistence)

| Scope         | Base Decay          |
| ------------- | ------------------- |
| Account score | −1 every 10 minutes |
| Device score  | −1 every 5 minutes  |
| IP score      | −1 every 3 minutes  |

### 7.1 Deterministic Decay Modifiers

* After reaching **L2 or higher**, decay rate is **halved**
* After **multiple block cycles**, decay pauses for a fixed **10 minutes** (deterministic)
* Budgets (Section 2.4, 3.3) are **fixed 24h epochs** and are **not affected by score decay**

---

## 8. Decision Aggregation Rule

When multiple rules produce decisions:

```
HARD_BLOCK > SOFT_BLOCK > ALLOW
```

If multiple blocks apply:

* Select the **highest block level**
* Select the **longest duration**

Account safety always overrides IP convenience.

---

## 9. Failure Semantics (Reference)

| Context     | Failure Mode |
| ----------- | ------------ |
| Login / OTP | FAIL_CLOSED  |
| API Heavy   | FAIL_OPEN    |

(See `FAILURE_SEMANTICS.md` for authoritative rules.)

---

## 10. Guarantees

* No decision is based on a single signal
* Remote permanent account lockout via budget-only rules is impossible
* Budget epochs cannot be extended (“no renewable 24h prisons”)
* Same-device equilibrium cannot bypass budgets indefinitely
* Device rotation does not reset account memory
* IP-only blocking is never final
* Device awareness is mandatory
* Progressive escalation with persistence
* Deterministic and atomic behavior
* Fully testable and auditable

---

**This document is authoritative.  
Any deviation requires a version bump and changelog entry.**
