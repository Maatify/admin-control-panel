# Architecture & Design Principles

The Crypto module is structured into distinct, decoupled sub-modules, each responsible for a specific cryptographic domain. This modularity ensures clear boundaries and facilitates standalone extraction.

## Module Structure

The module is organized into the following sub-directories:

-   **`Password/`**: Handles secure, one-way password hashing and verification.
-   **`KeyRotation/`**: Manages the lifecycle and states of cryptographic keys (Active, Inactive, Retired).
-   **`HKDF/`**: Implements HMAC-based Extract-and-Expand Key Derivation Function (RFC 5869) for domain separation.
-   **`Reversible/`**: Provides symmetric encryption and decryption services (AEAD).
-   **`DX/`** (Developer Experience): Offers high-level facades (`CryptoProvider`) to simplify orchestration of the underlying primitives.
-   **`Contract/`**: Contains shared interfaces used across the module.
-   **`Bootstrap/`**: Houses Dependency Injection bindings (`CryptoBindings.php`).

## Architectural Flow

The module supports primarily two types of cryptographic operations:

### 1. Reversible Encryption (The Primary Pipeline)

For encrypting sensitive data, the standard pipeline involves `KeyRotation`, `HKDF`, and `Reversible` modules orchestrated by the `DX` layer.

1.  **Key Selection:** The `KeyRotationService` identifies the currently *Active* root key for encryption or the necessary key (based on ID) for decryption.
2.  **Domain Separation (Derivation):** The `HKDFService` takes the root key and a specific *Context String* (e.g., `user:email:v1`) to derive a unique, ephemeral encryption key for that specific context.
3.  **Encryption/Decryption:** The `ReversibleCryptoService` uses the derived key to encrypt or decrypt the data using an AEAD algorithm (e.g., XChaCha20-Poly1305).

This flow ensures that data encrypted for one context cannot be decrypted using the key derived for another, even if the root key is identical.

### 2. Password Hashing

The `Password` module operates independently. It takes a plaintext password, optionally applies a global pepper (HMAC-SHA256), and hashes it using Argon2id.

## Dependency Injection (Composition Root)

The `CryptoBindings` class in the `Bootstrap/` directory serves as the composition root for the module. It defines how the various interfaces are mapped to their concrete implementations. Host applications should use this class to register the module's services in their DI container.

### Injected Dependencies

The module requires the host application to provide certain dependencies, primarily:

-   **`KeyProviderInterface`**: To supply the root keys to the `KeyRotationService`.
-   **`PasswordPepperProviderInterface`** (Optional but recommended): To supply the global pepper to the `PasswordHasher`.
