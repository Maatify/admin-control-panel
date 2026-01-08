# ADR-005: Crypto DX Layer & Unification

## Status
Accepted

## Context
The cryptographic architecture of the Admin Control Panel is composed of several strict, isolated modules:
* **KeyRotation**: Manages root key lifecycle.
* **HKDF**: Handles key derivation for domain separation.
* **Reversible**: Performs AES-GCM encryption/decryption.
* **Password**: Handles Argon2id hashing.

While this separation provides strong security guarantees and auditability, consuming these modules requires significant boilerplate code.
To encrypt a value correctly using the "Context-Based" pipeline, a developer must:
1. Inject `KeyRotationService`, `HKDFService`, and `ReversibleCryptoAlgorithmRegistry`.
2. Retrieve root keys.
3. Manually iterate and derive keys for the specific context.
4. Construct a `ReversibleCryptoService`.

This complexity introduces the risk of:
* **Misconfiguration**: Using raw keys where derived keys are expected.
* **Inconsistency**: Varying implementation of the derivation loop.
* **Developer Friction**: High barrier to entry for secure operations.

## Decision
We introduced a **Developer Experience (DX) Layer** (`App\Modules\Crypto\DX`) to orchestrate these existing modules.

This layer provides:
1. **Factories**: Automate the wiring of pipelines.
   * `CryptoContextFactory`: Automates `KeyRotation` -> `HKDF` -> `Reversible`.
   * `CryptoDirectFactory`: Automates `KeyRotation` -> `Reversible`.
2. **Facade**: A unified entry point (`CryptoProvider`) that exposes these pipelines and password services.

### Boundaries
* This layer is **orchestration only**.
* It does **not** implement cryptographic primitives.
* It does **not** manage key storage or lifecycle.
* It does **not** alter the behavior of the underlying frozen modules.

## Consequences
### Positive
* **Correctness by Default**: Developers can request a context-bound encrypter with a single method call (`$provider->context('email:v1')`).
* **Reduced Boilerplate**: Removes ~20 lines of wiring code from consumers.
* **Discoverability**: All crypto capabilities are discoverable via the `CryptoProvider` interface.

### Negative
* **Coupling**: The DX layer couples the previously independent modules (KeyRotation, HKDF, Reversible). This is acceptable as it is an optional upper layer.

## Compliance
This decision strictly adheres to the constraints of:
* ADR-001 (Reversible)
* ADR-002 (Key Rotation)
* ADR-003 (HKDF)
* ADR-004 (Password)
