# ADR-001: Reversible Cryptography Architecture

## Status
**Accepted / Locked**

## Date
2026-01-08

## Context

The system requires a cryptographic mechanism that allows:

- Encrypting sensitive data
- Decrypting it later back to its original form

Primary use cases include:
- Email queues
- Webhook payloads
- Deferred job processing
- Secure storage of recoverable data

This explicitly excludes:
- Password hashing
- One-way secrets
- Authentication tokens
- Integrity-only cryptography

---

## Decision

We implemented a **Reversible Cryptography Module** with the following principles:

### 1. Reversible ≠ Hashing
Reversible cryptography is treated as a **distinct concern** from hashing.

- Passwords and secrets MUST NOT use this module
- Only data that must be recovered is allowed

All naming, interfaces, and structure explicitly reflect reversibility.

---

### 2. Algorithm Execution Is Isolated

Each cryptographic algorithm is implemented as a **self-contained class**:

- Implements `ReversibleCryptoAlgorithmInterface`
- Owns its execution details (cipher name, IV size, tag size)
- Does NOT depend on enums or configuration for execution

Example:
- `Aes256GcmAlgorithm` defines its OpenSSL cipher internally

This prevents:
- Implicit mappings
- Configuration-driven crypto behavior
- Hidden execution logic

---

### 3. Enums Define Policy, Not Execution

`ReversibleCryptoAlgorithmEnum` exists only to:

- Act as a security whitelist
- Identify allowed algorithms
- Provide metadata (AEAD, IV required, tag required)

Enums MUST NOT:
- Contain cryptographic logic
- Provide OpenSSL cipher names
- Influence execution details

---

### 4. Registry Enforces Security Boundaries

A dedicated registry:
- Explicitly binds enums to implementations
- Prevents unsupported algorithms
- Prevents accidental or dynamic algorithm usage

No algorithm can be used unless:
- It is defined in the enum
- It is registered in the registry

---

### 5. Service Is Pure Orchestration

`ReversibleCryptoService`:
- Does NOT perform cryptographic operations
- Does NOT know cipher details
- Does NOT manage keys

It only:
- Selects the algorithm
- Selects the key
- Passes data and metadata
- Returns structured results

---

### 6. Key Management Is External

The module explicitly does NOT handle:
- Key generation
- Key storage
- Key rotation
- Environment loading

Key rotation is considered a **policy concern**, not a cryptographic primitive.

The module only requires:
- A key map
- An active key identifier

---

### 7. Fail-Closed Security Model

All cryptographic operations are **fail-closed**:

- No silent failures
- No fallback behavior
- No partial success

Any failure results in a hard exception:
- Unsupported algorithm
- Missing key
- Invalid authentication tag
- Corrupted ciphertext
- Decryption failure

---

### 8. AEAD-First Strategy

Authenticated Encryption with Associated Data (AEAD) is the default.

- AES-256-GCM is the reference implementation
- Integrity and authenticity are mandatory
- Non-AEAD algorithms are restricted

ECB mode is explicitly forbidden.

---

## Consequences

### Positive
- Clear separation of responsibilities
- Strong security guarantees
- Easy extensibility
- Deterministic behavior
- Library-ready design
- Safe for long-term storage and queues

### Trade-offs
- Slightly more boilerplate than ad-hoc encryption
- Requires explicit key management
- Requires storing metadata (iv, tag, algorithm, key_id)

These trade-offs are intentional and accepted.

---

## Alternatives Considered

### ❌ Single Service with OpenSSL Calls
Rejected due to:
- Tight coupling
- Hard-to-test code
- Unsafe extensibility

### ❌ Enum-Driven Cipher Execution
Rejected due to:
- Hidden execution behavior
- Configuration-driven crypto risks
- Poor separation of concerns

### ❌ Automatic Key Rotation Inside Module
Rejected due to:
- Mixing crypto primitives with policy
- Increased complexity
- Reduced auditability

---

## Final Notes

This design is intentionally strict.

Cryptography must be:
- Explicit
- Predictable
- Auditable
- Difficult to misuse

Any deviation from this architecture requires a new ADR.

---

**End of Record**
