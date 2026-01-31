# ADR-002: Key Rotation Architecture

## Status
**Accepted / Locked**

## Date
2026-01-08

## Related Decisions
- ADR-001: Reversible Cryptography Architecture

> This ADR is a direct continuation of ADR-001 and exists within the same
> cryptographic decision space, despite being implemented as a separate module.

---

## Context

The system uses **reversible cryptography** for encrypting data that must be
decrypted later (e.g. email queues, webhooks, deferred jobs).

ADR-001 intentionally excluded **key lifecycle management**, stating that:
- Cryptographic primitives must remain deterministic
- Key rotation is an operational and policy concern

However, the system still requires:
- Controlled key activation
- Safe key retirement
- Backward-compatible decryption
- Future support for re-encryption workflows

This ADR defines **how key rotation is designed and enforced** without violating
the guarantees of ADR-001.

---

## Decision

We introduce a **dedicated Key Rotation module** that is:

- Architecturally independent from the cryptography module
- Responsible for key lifecycle and policy only
- Explicitly prohibited from performing cryptographic operations

---

## Scope Definition

### The Key Rotation module IS responsible for:
- Defining cryptographic keys
- Tracking key status (active / inactive / retired)
- Enforcing a single active key
- Providing key material and active key identity
- Supporting future rotation and re-encryption policies

### The Key Rotation module is NOT responsible for:
- Encryption or decryption
- OpenSSL or cryptographic primitives
- Algorithm selection
- Cipher execution
- Hashing or signing

---

## Architectural Separation (Hard Boundary)

### Reversible Crypto Module
- Stateless
- Deterministic
- Receives keys and key identifiers
- Performs encryption/decryption only

### Key Rotation Module
- Stateful (policy-driven)
- Owns key lifecycle
- Controls which key is active
- Supplies keys to crypto services

**No circular dependency is allowed.**

---

## Key Identity Model

Each cryptographic key is identified by an immutable `key_id`.

A key has:
- `key_id` (string, immutable)
- `status` (active | inactive | retired)
- `created_at`
- optional metadata (purpose, notes, origin)

`key_id` MUST NEVER change.

---

## Active Key Rule (Invariant)

At any point in time:
- **Exactly one key MAY be active**
- Zero active keys is a hard error
- Multiple active keys are forbidden

This invariant is enforced by the Key Rotation module.

---

## Key Status Semantics

| Status | Encrypt | Decrypt | Notes |
|------|--------|---------|------|
| active | ✔️ | ✔️ | Used for new encryption |
| inactive | ❌ | ✔️ | Old data only |
| retired | ❌ | ✔️ | Grace period / legacy data |

Encryption MUST NEVER use inactive or retired keys.

---

## Rotation Flow (Version 1)

1. A new key is added as `inactive`
2. The new key is promoted to `active`
3. The previous active key becomes `inactive`
4. New data is encrypted with the new active key
5. Existing data remains decryptable

**No automatic re-encryption occurs in this phase.**

---

## Re-Encryption (Explicitly Out of Scope)

Automatic or background re-encryption:
- Is NOT part of this ADR
- Will be addressed in a future ADR
- Must be explicit, observable, and auditable

This avoids:
- Silent data mutation
- Unbounded background work
- Hidden security risks

---

## Failure Semantics (Fail-Closed)

The Key Rotation module MUST fail closed:

- No active key → hard failure
- Unknown key_id → hard failure
- Invalid status transition → hard failure
- Attempt to encrypt with non-active key → hard failure

No fallbacks are allowed.

---

## Extensibility Model

The module supports multiple key providers via an interface:

- In-memory provider (tests, bootstrap)
- Database-backed provider (future)
- External vault provider (future)

The provider is replaceable without modifying cryptographic code.

---

## Security Considerations

- Key material must never be logged
- Key identifiers may be logged for audit purposes
- All transitions must be auditable
- Rotation actions should be restricted to privileged operations

---

## Consequences

### Positive
- Clean separation of concerns
- Predictable cryptographic behavior
- Safe long-term key lifecycle
- Library-grade extractability
- Strong auditability

### Trade-offs
- Requires explicit wiring
- Slightly higher operational complexity
- Re-encryption deferred by design

These trade-offs are intentional and accepted.

---

## Alternatives Considered

### ❌ Rotation Inside Crypto Module
Rejected due to:
- Mixed responsibilities
- Non-deterministic crypto behavior
- Poor auditability

### ❌ Automatic Re-Encryption
Rejected due to:
- Hidden side effects
- Operational risk
- Difficult rollback

---

## Final Notes

Key rotation is a **policy problem**, not a cryptographic primitive.

This design ensures:
- Cryptography remains predictable
- Rotation remains explicit
- Future expansion is safe and controlled

Any deviation from this architecture requires a new ADR.

---

**End of Record**
