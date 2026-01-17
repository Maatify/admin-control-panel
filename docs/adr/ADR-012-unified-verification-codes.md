# ADR-012: Unified Verification Codes — Deferred Implementation

**Status:** ACCEPTED (Deferred)  
**Date:** 2026-01-18  
**Decision ID:** ADR-012  
**Scope:** Verification Codes / OTP / Confirmation Flows  
**Related Areas:** Authentication, Email Verification, Step-Up, Channel Linking  
**Supersedes:** None  
**Superseded by:** None

---

## 1. Context

The system currently uses a single table (`verification_codes`) to store
verification / confirmation codes (OTP) for multiple purposes such as:

- Admin email verification
- Password reset
- Step-Up / sensitive action confirmation
- Channel linking (e.g. Telegram)

The existing schema uses a polymorphic model:

- `identity_type` (ENUM)
- `identity_id` (VARCHAR)

This design was intentionally chosen to avoid table proliferation and to keep
verification data centralized as a **single source of truth**, especially as
the database grows over time.

During architectural review, alternative designs were evaluated, including:

- Separate tables per domain (admin / user / customer)
- Domain-specific verification tables
- Unified verification ledger with explicit ownership columns

---

## 2. Decision

### ✅ The project will RETAIN the current unified table approach for now
### ⏳ The improved unified design is DEFERRED to a future phase

Specifically:

- The existing `verification_codes` table **will NOT be modified at this time**
- No table split (admin / user / customer) will be performed in the current phase
- No ownership-column refactor (`admin_id`, `user_id`, `customer_id`) will be implemented yet

At the same time:

- The **long-term target architecture** is a **Unified Verification Ledger**
  with explicit ownership columns and strict service-level invariants
- This target design is formally accepted and documented, but postponed

This decision intentionally balances **architectural correctness** with
**operational safety**.

---

## 3. Rationale

### 3.1 Why NOT refactor now?

The refactor was deferred due to the following risks:

- Verification flows currently have **no automated test coverage**
- The table is involved in critical authentication paths (email verification, step-up)
- Refactoring schema + logic without tests would introduce unacceptable risk
- Some consumers (e.g. Telegram linking) are themselves scheduled for redesign

Changing the schema now would provide limited immediate value while risking
authentication stability.

---

### 3.2 Why KEEP a single unified table concept?

The decision to keep (and later improve) a unified table is intentional:

- Verification data is short-lived and high-volume
- A single table enables:
    - Centralized cleanup / purge jobs
    - Consistent TTL and attempt policies
    - Easier monitoring, auditing, and analytics
- Avoids long-term duplication of near-identical tables

The issue identified is **not the unification itself**, but the **current
polymorphic representation**, which will be addressed later.

---

## 4. Accepted Future Target (Not Implemented Yet)

The accepted future design is:

- One unified `verification_codes` table
- Explicit ownership columns:
    - `admin_id`
    - `user_id`
    - `customer_id`
- Exactly ONE ownership column set per row (enforced at service level)
- No `identity_type` / `identity_id` polymorphism
- Domain-specific services accessing only their relevant ownership column

This design is **explicitly approved** but **not active**.

---

## 5. Constraints Until Refactor

Until the deferred refactor is executed, the following constraints apply:

1. No new verification use-cases may be added casually
2. All new usage must go through existing verification services
3. Controllers must not implement verification logic directly
4. No new polymorphic behavior should be introduced
5. Any future consumer must be reviewed against this ADR

---

## 6. Preconditions for Future Execution

The deferred refactor may only proceed once ALL of the following are true:

- Automated test coverage exists for:
    - Verification code generation
    - Verification code validation
    - Expiry and attempt limits
- Ownership of verification logic is fully encapsulated in services
- Telegram / channel linking flows are finalized
- Logging is compliant with canonical security event handling
- Migration plan is reviewed and approved

---

## 7. Consequences

### Positive
- No risk introduced into active authentication flows
- Clear architectural direction documented
- Prevents ad-hoc or accidental schema changes
- Preserves single source of truth philosophy

### Trade-offs
- Some architectural imperfections remain temporarily
- Polymorphic schema remains in use for the short term
- Future refactor will require coordinated migration work

These trade-offs are accepted.

---

## 8. Final Statement

This ADR formally records the decision to:

> **Delay the implementation of the improved unified verification ledger,
> while explicitly committing to it as the long-term architecture.**

Any future work touching verification codes **must respect this decision**.

---

**Decision Locked.**
