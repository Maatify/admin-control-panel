# RateLimiter — Failure Semantics (Official)

**Module:** RateLimiter
**Namespace:** `Maatify\RateLimiter`
**Status:** LOCKED — Security Contract
**Spec Version:** `1.0.0`

This document defines how the RateLimiter behaves when **internal failures occur**.  
It specifies when the system must fail closed, fail open, or enter a strictly bounded degraded mode.

Failure semantics are **security-critical** and MUST NOT be altered implicitly.

---

## 1. Purpose

Failure semantics exist to guarantee **security without weaponized denial-of-service**.

They define safe, bounded, and deterministic behavior when:

* Storage backends are unavailable
* Atomic guarantees cannot be upheld
* State cannot be reliably read or written

Goals:

* Preserve authentication security guarantees
* Prevent permanent or attacker-driven lockout
* Prevent silent bypass or unlimited abuse
* Ensure predictable, auditable behavior
* Prevent infrastructure failure from becoming a security primitive

---

## 2. Failure Categories

### 2.1 Storage Failures

* Redis unavailable, timed out, or saturated
* MongoDB operation failure
* PDO transaction failure
* Connection pool exhaustion
* Memory pressure preventing writes

### 2.2 Atomicity Failures

* Partial writes
* Lost updates under concurrency
* Non-atomic read-modify-write
* Backend isolation guarantees violated

### 2.3 Internal Logic Failures

* Invalid or inconsistent policy configuration
* Corrupted stored state
* Algorithm or version mismatch
* Contract violations by host application

---

## 3. Failure Modes

The RateLimiter supports **three explicit and mutually exclusive failure modes**.

### 3.1 FAIL_CLOSED (Security First)

**Definition:**

* The action is denied.
* A blocking decision is returned.

**Rules:**

* MUST NOT silently allow actions
* MUST be deterministic
* MUST NOT depend on retries

FAIL_CLOSED is allowed only for security-critical operations and MUST NOT persist indefinitely without bounded fallback.

---

### 3.2 FAIL_OPEN (Availability First)

**Definition:**

* The action is allowed.
* No shared counters are incremented.
* No shared penalties are escalated.

**Constraints:**

* MUST be explicitly declared by policy
* MUST NOT create or mutate shared state
* MUST NOT be applied to authentication or step-up flows

**v1.2.0 Constraint (Anti “Circuit Breaker Hammer”):**

FAIL_OPEN MUST still be **bounded**:

* Implementations MUST apply local coarse throttles (per node) on K1/K2,
* with static caps defined below.

---

### 3.3 DEGRADED_MODE (Bounded Safety Mode)

**Definition:**  
A temporary, explicit, and strictly bounded mode entered when the primary backend is unhealthy.

DEGRADED_MODE is **not FAIL_OPEN** and **not FAIL_CLOSED**.

**Core Properties:**

* Time-bounded
* Scope-bounded
* Non-persistent to shared stores
* Observable
* Exit is mandatory
* Must not create “harvest windows” via unlimited resets

---

## 4. Policy-Based Failure Semantics

Failure semantics are selected **per policy**, never globally.

---

### 4.1 Login Protection Policy

**Primary Mode:**

```
FAIL_CLOSED
```

**Mandatory Degraded Behavior:**

If storage failures persist beyond a short bounded interval:

* Enter `DEGRADED_MODE`
* Apply coarse, local, in-memory limits:

  * Per IP prefix (K1)
  * Per AccountID (K4) if resolvable
* Caps MUST be:

  * Lower than normal operation
  * Identical across all nodes (static constants)
  * Independent of historical shared state

**Degraded Caps (LOCKED constants):**

* Per `AccountID` (K4): max **3** login attempts per **10 minutes**
* Per `IP_PREFIX` (K1): max **20** login attempts per **10 minutes**
* Max block level in degraded: **L2**

**Anti-Reset Guard (v1.2.0):**

While in DEGRADED_MODE, the local in-memory counters MUST persist for the entire degraded epoch and MUST NOT reset on successful requests. This prevents “clean slate” harvesting within the same degraded period.

**Explicit Guarantees:**

* No unlimited retries
* No permanent account lockout
* No escalation beyond L2 in degraded mode
* No persistence to shared stores
* No timer-based periodic denial (only attempt-based throttling)

**Explicitly Forbidden:**

* Silent FAIL_OPEN
* Infinite retries
* Permanent account blocks
* Carrying degraded penalties back into shared stores

---

### 4.2 OTP / Step-Up Protection Policy

**Primary Mode:**

```
FAIL_CLOSED
```

**Degraded Behavior:**

* DEGRADED_MODE MAY be entered
* Enforcement MUST be stricter than login
* OTP attempts MUST always have:

  * A hard attempt cap
  * Account-level enforcement when AccountID resolvable

**Degraded Caps (LOCKED constants):**

* Per `AccountID` (K4): max **2** OTP attempts per **15 minutes**
* Per `IP_PREFIX` (K1): max **10** OTP attempts per **15 minutes**
* Max block level in degraded: **L2**

**Explicitly Forbidden:**

* FAIL_OPEN
* Unlimited OTP attempts
* OTP bypass due to backend failure

---

### 4.3 API Heavy / Brute-Force Protection Policy

**Primary Mode:**

```
FAIL_OPEN
```

**Constraints:**

* FAIL_OPEN MUST NOT create shared state
* FAIL_OPEN MUST NOT escalate shared penalties
* FAIL_OPEN MUST be observable

**Mandatory Local Guardrails (v1.2.0):**

Even in FAIL_OPEN, apply per-node coarse throttles:

* Per `IP_PREFIX` (K1): max **120** requests per **minute**
* Per `IP_PREFIX + UA` (K2): max **60** requests per **minute**

These guardrails are best-effort and do not require shared storage.

**Forbidden:**

* Account-level enforcement in degraded API mode
* Escalation based on degraded data

---

## 5. Circuit Breaker Rules (Mandatory)

To prevent exploitation of failure behavior:

* Repeated backend failures MUST trip a circuit breaker
* Circuit breaker MUST:

  * Halt backend operations
  * Enter DEGRADED_MODE
  * Enforce a minimum degraded duration
* Circuit breaker MUST NOT:

  * Flap rapidly
  * Reset without sustained backend health

### 5.1 LOCKED Circuit Breaker Constants (v1.2.0)

Per policy, per node:

* **Trip Threshold:** 3 backend failures within 10 seconds
* **Minimum Degraded Duration:** 5 minutes
* **Minimum Healthy Interval (before reset):** 2 minutes of sustained success
* **Re-Entry Guard:** DEGRADED_MODE MUST NOT be re-entered more than **2 times** within **30 minutes** for the same policy.

### 5.2 Re-Entry Guard Action

If the re-entry guard is violated:

* The policy MUST enter `FAIL_CLOSED` for **10 minutes** (static backoff),
* and MUST emit an observable critical signal to the host application.

This prevents deliberate “flap to harvest” cycles.

---

## 6. Failure Handling Rules (Non-Negotiable)

* Infrastructure drivers MUST throw on failure
* Failures MUST NOT be swallowed
* The Engine MUST explicitly select a failure mode
* Failure mode selection MUST be deterministic
* Silent fallback behavior is forbidden
* Failure handling MUST be testable

---

## 7. Observability Requirements

On failure:

* Failure state MUST be visible to host application
* Failure mode MUST be explicit
* DEGRADED_MODE entry and exit MUST be observable
* Circuit breaker trip/reset MUST be observable
* No sensitive internals may be leaked externally

---

## 8. Security Guarantees

This model guarantees:

* Authentication is never silently unprotected
* Infrastructure failure cannot be used as a renewable kill-switch
* RateLimiter cannot be used as a permanent lockout lever
* Abuse during outages is bounded (even under FAIL_OPEN)
* Behavior remains deterministic and auditable

---

## 9. Explicit Non-Guarantees

The RateLimiter does NOT guarantee:

* Protection during total system collapse
* Protection against network-layer DoS
* Correct behavior if host contracts are violated
* Infinite availability under adversarial infrastructure attacks

---

## 10. Versioning Rules

* Any change requires:

  * Version bump
  * Explicit documentation
  * Security review

---

**This document is authoritative.  
Failure semantics MUST NOT be altered without explicit versioning and security approval.**
