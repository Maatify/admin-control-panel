# ADR-005: Crypto DX Layer & Unification

## Status

Accepted

---

## Context

The cryptographic architecture of the Admin Control Panel is composed of several strict, isolated modules:

* **KeyRotation**: Manages root key lifecycle.
* **HKDF**: Handles key derivation for domain separation.
* **Reversible**: Performs AES-GCM encryption/decryption.
* **Password**: Handles Argon2id hashing.

While this separation provides strong security guarantees and auditability, consuming these modules requires significant boilerplate code.

To encrypt a value correctly using the **Context-Based pipeline**, a developer must:

1. Inject `KeyRotationService`, `HKDFService`, and `ReversibleCryptoAlgorithmRegistry`.
2. Retrieve root keys.
3. Manually iterate and derive keys for the specific context.
4. Construct a `ReversibleCryptoService`.

This complexity introduces the risk of:

* **Misconfiguration**: Using raw keys where derived keys are expected.
* **Inconsistency**: Varying implementations of the derivation loop.
* **Developer Friction**: High barrier to entry for secure operations.

---

## Decision

We introduced a **Developer Experience (DX) Layer** (`App\Modules\Crypto\DX`) to orchestrate these existing modules.

This layer provides:

1. **Factories** to automate pipeline wiring:

   * `CryptoContextFactory`: Automates `KeyRotation` → `HKDF` → `Reversible`.
   * `CryptoDirectFactory`: Automates `KeyRotation` → `Reversible`.
2. **Facade**:

   * `CryptoProvider` as a unified entry point exposing:

      * Context-based encryption
      * Direct encryption
      * Password hashing

---

## Boundaries

* This layer is **orchestration only**.
* It does **not** implement cryptographic primitives.
* It does **not** manage key storage or lifecycle.
* It does **not** alter or extend the behavior of the underlying frozen modules.
* It does **not** define encryption payload formats or cryptographic APIs.

---

## Consequences

### Positive

* **Correctness by Default**
  Developers can request a context-bound encrypter with a single method call:

  ```php
  $provider->context('email:v1');
  ```

* **Reduced Boilerplate**
  Removes ~20 lines of repetitive wiring code from consumers.

* **Discoverability**
  All cryptographic capabilities are discoverable through the `CryptoProvider` interface.

### Negative

* **Coupling**
  The DX layer couples previously independent modules (KeyRotation, HKDF, Reversible).
  This is an explicit and acceptable trade-off, as the DX layer is optional and sits above frozen primitives.

---

## Testing & Verification

Due to the use of **final concrete classes** in the underlying cryptographic modules, the DX layer:

* **Cannot be verified using mock-based contract tests**
* **Cannot spy on internal cryptographic behavior**
* **Must not test cryptographic correctness**

As a result:

* The DX layer is verified using **smoke / wiring tests only**
* Tests assert that:

   * Factories can be constructed
   * Pipelines can be instantiated
   * Returned services are treated as **opaque objects**
* No assumptions are made about:

   * Encryption formats
   * Key material
   * Algorithm behavior

This approach preserves architectural boundaries and avoids duplicating cryptographic tests already enforced at the module level.

---

## Compliance

This decision strictly adheres to the constraints defined in:

* ADR-001 — Reversible Cryptography
* ADR-002 — Key Rotation Architecture
* ADR-003 — HKDF Derivation
* ADR-004 — Password Hashing

---

### 🔒 Final State

* Decision: **Locked**
* DX Layer: **Optional, Non-Invasive**
* Crypto Modules: **Frozen**
* Testing Strategy: **Explicit and Documented**

> **ADR-005 is complete and finalized.**
