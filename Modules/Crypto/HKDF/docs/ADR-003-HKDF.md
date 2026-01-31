# ADR-003 — HKDF for Context-Based Key Derivation

## Status

**ACCEPTED — LOCKED**

---

## Context

The Admin Control Panel has evolved into a **multi-channel, multi-consumer secure platform**.

Current cryptographic architecture includes:

- **ADR-001**: Reversible Encryption Module
  - AES-256-GCM
  - Deterministic, stateless
  - No PEPPER
- **ADR-002**: Key Rotation Module
  - Explicit key lifecycle
  - Exactly one active key
  - Fail-closed behavior
  - Backward-compatible decryption

The system now includes (and will continue to expand):

- Queued Email notifications
- Queued SMS notifications
- Queued Telegram notifications
- Notification payload encryption
- TOTP secret storage (at rest)
- Secure exports and long-lived encrypted artifacts
- Future cryptographic consumers

Using a **single raw active key** across all cryptographic consumers introduces
an unacceptable **blast radius** and weak domain separation.

---

## Decision

Introduce a **dedicated HKDF-based key derivation layer**
as a **pure, stateless, optional module**:

```text
KeyRotation (ACTIVE_ROOT_KEY)
        ↓
      HKDF
        ↓
 Context-Derived Keys
        ↓
Crypto Primitives (AES-GCM, HMAC, etc.)
````

HKDF is used **only for context-based key derivation** and **does not**:

* Generate root secrets
* Manage key lifecycle
* Perform key rotation
* Access environment variables directly

Each cryptographic consumer MUST use a **stable, versioned context string**
to derive its own isolated key.

---

## Approved Context Examples

```text
notification:email:v1
notification:sms:v1
notification:telegram:v1
notification:payload:v1
totp:storage:v1
export:file:v1
```

Context strings are:

* Explicit
* Constant
* Versioned
* NOT derived from user input
* NOT dynamic

---

## Explicit Non-Goals

This module explicitly DOES NOT:

* Replace **Key Rotation**
* Replace **PEPPER**
* Introduce automatic re-encryption
* Introduce per-user or per-message dynamic keys
* Introduce random salts (unless explicitly stored)
* Perform encryption or decryption itself

HKDF is **not a security feature by itself**.
It is a **key organization and isolation mechanism**.

---

## Security Properties

This decision guarantees:

* Strong **domain separation** between crypto consumers
* Reduced blast radius on key misuse or leakage
* Compatibility with existing rotation and audit guarantees
* Deterministic and reviewable behavior
* Zero hidden state

---

## Integration Rules (Mandatory)

1. HKDF MUST receive the root key **only** from `KeyRotation`
2. HKDF MUST be stateless and deterministic
3. HKDF MUST NOT read from `.env`
4. HKDF MUST NOT perform key rotation
5. HKDF MUST use versioned context strings
6. All derived keys MUST be treated as ephemeral runtime material
7. Backward compatibility is enforced by context versioning, not heuristics

Any violation of these rules is considered a **cryptographic architecture breach**.

---

## Consequences

### Positive

* Safe expansion of cryptographic use-cases
* Cleaner separation between channels and modules
* Easier long-term maintenance and review
* Clear audit and forensic boundaries

### Trade-offs

* Slight increase in architectural complexity
* Additional documentation and testing requirements

These trade-offs are accepted due to the system’s security posture
and long-lived operational scope.

---

## Related Decisions

* ADR-001 — Reversible Encryption Primitive
* ADR-002 — Key Rotation & Lifecycle Management

---

## Final Notes

HKDF is introduced as an **additive, optional layer**.
Existing modules remain valid and unchanged.

This ADR does not mandate immediate usage everywhere,
but defines the **only approved mechanism** for future key derivation needs.

**This decision is LOCKED.**
