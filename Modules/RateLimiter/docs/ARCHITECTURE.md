# RateLimiter — Architecture (Official)

**Module:** RateLimiter
**Namespace:** `Maatify\RateLimiter`
**Status:** LOCKED — Architecture Contract
**Spec Version:** `1.0.0`
**Change Class:** Adversarial Hardening Alignment
**Location:** `Modules/RateLimiter` (library-first)

This document explains **why** the RateLimiter module is designed the way it is.  
It is an architectural contract intended to prevent accidental weakening, incorrect refactors, or scope creep.

Behavioral rules are specified in:
- `docs/DECISION_MATRIX.md`
- `docs/POLICIES.md`
- `docs/DEVICE_FINGERPRINT.md`
- `docs/KEY_STRATEGY.md`
- `docs/FAILURE_SEMANTICS.md`

---

## 1. Goals

### 1.1 Security Goals
- Prevent brute-force attacks on authentication flows (password + OTP/Step-Up)
- Detect credential spray (single IP scope attempting many accounts)
- Detect distributed attacks (single account attacked from many devices/IP scopes)
- Detect fingerprint dilution (same fingerprint reused across many IP scopes)
- Reduce false positives caused by NAT, VPNs, mobile networks, and shared IPs
- Provide deterministic, testable decisions (auditable security behavior)
- Prevent storage exhaustion via adversarial key creation (key explosion)
- Prevent deterministic **equilibrium** and **threshold-gaming** (N-1 attacks)
- Prevent renewable **denial-of-owner** via budget/cooldown arithmetic

### 1.2 Product / UX Goals
- Prefer throttling (`SOFT_BLOCK`) before full blocking where possible
- Progressive penalties with decay **without allowing stable equilibrium**
- Budget friction that cannot be turned into scheduled recurring lockout
- Fast fail for clearly malicious patterns
- Minimal integration burden for host applications

### 1.3 Engineering Goals
- Storage-agnostic core: works with PDO, Redis, MongoDB
- Library-first structure: safe extraction into a standalone package
- DTO-first public API: no arrays in public contracts
- Clear boundaries between Engine, Policy, Penalty, and Store (testability and replaceability)
- Explicit failure behavior that cannot be weaponized as a kill-switch
- Bounded operations (no scans, no unbounded key creation)

---

## 2. Non-Goals (Explicitly Out of Scope)
- A permanent ban system (account bans, IP bans beyond temporary rate-limiting)
- A WAF replacement (CDN/WAF remains a separate security layer)
- User tracking across sites or contexts
- Advanced browser fingerprinting (canvas/audio/WebGL)
- Risk scoring based on external intelligence feeds

---

## 3. Architectural Principles (Non-Negotiable)

### 3.1 Multi-Signal Decisions (No Single-Signal Security)
Decisions MUST never rely on a single signal (e.g., IP-only).  
The module combines:
- IP scope
- User-Agent
- Device Fingerprint (confidence-aware)
- Account Identifier
- Correlation patterns

This reduces false positives and improves detection of distributed attacks.

### 3.2 Device Awareness is Mandatory
Device signals are foundational to fairness and attack detection:
- `IP_PREFIX + DeviceFP` is preferred over `IP_PREFIX` alone
- `AccountID + DeviceFP` is required for account-sensitive operations
- Passive-only fingerprints are treated as **LOW confidence** and have explicit enforcement constraints

The fingerprint system is defined in `docs/DEVICE_FINGERPRINT.md`.

### 3.3 Progressive Blocking + Persistence (Anti-Equilibrium)
Blocking MUST be progressive and MUST decay over time.

Additionally, the design MUST include:
- Account-wide persistence (prevents device-rotation ladder resets)
- Hard caps for low-and-slow abuse (fixed budget epochs)
- Deterministic **anti-equilibrium gates** (e.g., “3 soft blocks within 6 hours → next failure hard block”)
- Deterministic **near-threshold watch** to prevent N-1 correlation gaming

Ladder, decay modifiers, budgets, and gates are locked in `docs/DECISION_MATRIX.md`.

### 3.4 Determinism (Without Deterministic Bypass)
Given the same inputs and same stored state, the module MUST produce the same decision.

Determinism MUST NOT create an “attacker roadmap”. Therefore:
- Budgets use fixed epochs (no rolling extension)
- Correlation uses near-threshold watch (anti N-1)
- Same-device failures are budget-eligible after micro-caps

### 3.5 Bounded State (Key Explosion Resistance)
The module MUST NOT allow adversarial traffic to create unbounded keys.
Device-related key creation MUST be capped and aggregated as defined in:
- `docs/KEY_STRATEGY.md`
- `docs/DEVICE_FINGERPRINT.md`

Ephemeral routing must protect storage **without** bypassing active blocks.

### 3.6 Explicit Failure Behavior (No Silent Bypass, No Kill-Switch)
Failure behavior MUST be explicit, observable, and bounded.

Auth-critical flows MUST remain protected without turning RateLimiter into a global denial lever.

Therefore:
- Login/OTP use FAIL_CLOSED with mandatory bounded DEGRADED_MODE
- API Heavy may FAIL_OPEN, but still must be bounded by local guardrails
- Circuit breaker parameters are locked (no undefined N/window)

Failure semantics are locked in `docs/FAILURE_SEMANTICS.md`.

---

## 4. Module Layers and Responsibilities

### 4.1 Contracts (Public Boundary)
**Location:** `Contract/`

Contracts define stable APIs:
- `RateLimiterInterface` — single entrypoint for consumption/guarding
- `RateLimitStoreInterface` — storage abstraction
- `DeviceIdentityResolverInterface` — device identity resolution abstraction
- `BlockPolicyInterface` — penalty computation (ladder + decay + caps)
- `CorrelationStoreInterface` (if separated) — bounded distinct counting support

Contracts are pure and storage-agnostic.

### 4.2 DTOs (Public Data Shapes)
**Location:** `DTO/`

All data crossing boundaries MUST be DTO-based:
- Context DTOs (signals)
- Request DTOs (policy + action + cost)
- Result DTOs (decision + retry-after + block level + failure mode)
- Internal state DTOs (score/level/windows)

No public arrays are allowed.

DTO naming MUST end with `DTO`.

### 4.3 Engine (Decision Orchestration)
**Location:** `Engine/`

The Engine is the “brain”:
- Evaluates keys in strict order (fail-fast)
- Applies scoring rules
- Applies correlation rules (bounded windows + watch flags)
- Applies caps and persistence rules (fixed epochs; anti-equilibrium gates)
- Aggregates decisions deterministically
- Enforces failure semantics explicitly

The Engine MUST NOT depend on specific storage implementations.

### 4.4 Policies (Presets)
**Location:** `Policy/`

Policies are pre-configured rule sets built on the Decision Matrix:
- `login_protection`
- `otp_protection`
- `api_heavy_protection`

Policies define:
- which keys are used
- scoring deltas
- thresholds
- failure semantics mode (including degraded behavior)

Policies MUST remain compliant with `docs/POLICIES.md`.

### 4.5 Penalty (Escalation + Decay + Caps)
**Location:** `Penalty/`

Penalty logic is separated to:
- prevent mixing “decision” with “punishment”
- keep escalation rules testable and deterministic
- centralize caps, gates, and epoch rules
- avoid hidden behavioral changes inside Store drivers

### 4.6 Device Identity (Fingerprinting)
**Location:** `Device/`

Device identity is resolved into a single `DeviceIdentityDTO` with:
- hashed fingerprints only
- confidence level
- churn/evasion awareness
- bounded creation rules integration (no key explosion)

The module MUST NOT store raw fingerprint components.

### 4.7 Infrastructure (Drivers)
**Location:** `Infrastructure/`

Drivers implement store contracts for:
- Redis
- MongoDB
- PDO

**Infrastructure rules:**
- Drivers MUST provide deterministic, bounded behavior
- Drivers MUST NOT swallow exceptions
- Drivers MUST expose backend capability flags explicitly
- If a backend cannot satisfy required atomicity for an operation, the driver MUST fail explicitly and defer to Engine failure semantics
- Drivers must be interchangeable without changing Engine logic

---

## 5. Key Strategy (Why These Keys Exist)
The module relies on a small, explicit set of evaluation keys:
- `K1 = IP_PREFIX`
- `K2 = IP_PREFIX + UA`
- `K3 = IP_PREFIX + DeviceFP`
- `K4 = AccountID`
- `K5 = AccountID + DeviceFP`

This key set is designed to:
- minimize false positives
- enable correlation detection
- avoid IP-only enforcement
- prevent key explosion via bounded creation rules
- defeat IPv6 boundary spray via hierarchical aggregation

Detailed key rules are defined in `docs/KEY_STRATEGY.md`.

---

## 6. Failure Semantics (Security vs Availability)
Rate limiting is security-critical for login and OTP.

When storage fails:
- Login/OTP MUST be FAIL_CLOSED with mandatory bounded DEGRADED_MODE
- API Heavy MAY be FAIL_OPEN, but MUST still apply local guardrails
- Circuit breaker parameters are LOCKED to prevent “undefined N/window” exploitation

Rules are defined in `docs/FAILURE_SEMANTICS.md`.

---

## 7. Privacy-by-Design Commitments
The module is designed to support security without tracking:
- No raw headers persisted
- No raw fingerprint components stored
- Only hashed, keyed identifiers persisted
- Fingerprints are probabilistic and versioned
- No cross-context or cross-module tracking

See `docs/DEVICE_FINGERPRINT.md` for full rules.

---

## 8. Testing Strategy (What Must Be Proven)
The module is only acceptable if tests prove:
- Deterministic outcomes for known states
- Correct ladder escalation, persistence, decay modifiers, and anti-equilibrium gates
- Correct budget epoch behavior (no extension) and owner-safety enforcement
- Correct retry-after calculations
- Correct correlation detection triggers with watch flags + confidence constraints
- Key explosion resistance (caps + ephemeral behavior + “no bypass” invariants)
- Failure semantics correctness (including circuit breaker constants and re-entry guard)
- Driver conformance: same behavior across drivers within declared capabilities

Backends must demonstrate:
- atomic correctness where required OR explicit failure with documented semantics
- bounded operations (no unbounded scans)

---

## 9. Extraction Readiness
This module is structured for extraction without redesign:
- No coupling to HTTP frameworks
- No reliance on globals (`$_SERVER`, `$_COOKIE`)
- DTO + Contract boundaries
- Drivers isolated under `Infrastructure/`

Composer autoload is expected to map:
- `Maatify\RateLimiter\` → `Modules/RateLimiter`

---

## 10. Stability & Versioning Rules
- Any change to behavior requires:
  - version bump
  - changelog entry
  - updated tests aligned to the Decision Matrix
- Documents in `docs/` are part of the public contract
- Policies are stable identifiers and must not change without explicit versioning

---

**This document is authoritative.  
Do not “simplify” this module by removing multi-signal logic, device awareness, bounded state rules, progressive blocking, caps, gates, and determinism.**
