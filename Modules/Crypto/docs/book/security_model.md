# Security Model

This document outlines the overarching security model and threat mitigations implemented by the Crypto module.

## Threat Mitigations

### 1. Data Tampering (Integrity)
-   **Mitigation:** All reversible encryption relies strictly on Authenticated Encryption with Associated Data (AEAD) algorithms, specifically XChaCha20-Poly1305.
-   **Enforcement:** The `ReversibleCryptoService` will immediately throw an exception if the authentication tag fails validation during decryption. Modifying even a single bit of the ciphertext or the initialization vector (IV) will result in a hard failure.

### 2. Cross-Domain Key Re-use
-   **Threat:** A key used to encrypt low-sensitivity data (e.g., preferences) is compromised and subsequently used to attack high-sensitivity data (e.g., API tokens) encrypted with the same key.
-   **Mitigation:** The module mandates the use of HKDF (HMAC-based Key Derivation Function) via the `CryptoProvider->context()` pipeline.
-   **Enforcement:** By forcing consumers to provide explicit context strings (`user:preferences:v1`, `system:tokens:v1`), the module derives mathematically distinct keys for each domain from the single root key. Compromising a derived key does not expose the root key or other derived keys.

### 3. Offline Password Cracking
-   **Threat:** An attacker dumps the user database and attempts to crack password hashes offline using rainbow tables or brute force.
-   **Mitigation:** The module employs Argon2id, a memory-hard algorithm resistant to GPU/ASIC cracking. Additionally, it strongly encourages a global "Pepper" applied via HMAC-SHA256 *before* Argon2id hashing.
-   **Enforcement:** If a `PasswordPepperProviderInterface` is bound in the DI container, the pepper is automatically applied. Without the secret pepper (which should not be stored in the database), the dumped hashes are mathematically impossible to crack, regardless of the attacker's compute power.

### 4. Key Compromise and Legacy Data
-   **Threat:** A root key is suspected to be compromised, or general security policy mandates periodic key changes.
-   **Mitigation:** The `KeyRotationService` manages explicit key states (Active, Inactive, Retired).
-   **Enforcement:** New keys can be introduced as ACTIVE while old keys are demoted to INACTIVE, allowing seamless decryption of existing data while securing new data with the new key. If a compromise is confirmed, a key can be marked RETIRED, permanently blocking decryption attempts using that key.

### 5. Weak Algorithms and "Downgrade" Attacks
-   **Threat:** An attacker manipulates configuration to force the application to use a weaker, crackable encryption algorithm (e.g., ECB mode or outdated ciphers).
-   **Mitigation:** The module is "fail-closed" and restrictive. It does not provide a generic, easily configurable algorithm switch. It explicitly binds to modern, approved AEAD ciphers (XChaCha20-Poly1305).

## Host Application Responsibilities

The security guarantees of this module are predicated on the host application fulfilling specific responsibilities:

1.  **Secret Management:** The module does not store secrets. The host application *must* provide root keys and peppers securely (e.g., via environment variables or a secure Key Management System) through the required interfaces.
2.  **Dependency Injection Integrity:** The host application must ensure that the implementations provided for interfaces like `KeyProviderInterface` are reliable and secure.
3.  **Context String Stability:** The host application must carefully manage context strings. Changing a context string will permanently orphan the data encrypted under it.
