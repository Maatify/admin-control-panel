# Cryptographic Census Report
**Target:** `admin-control-panel`
**Date:** 2026-01-13
**Scope:** Full Repository (All Layers)

---

## 1. Executive Summary

The `admin-control-panel` project implements a centralized, layered cryptographic architecture.
Core cryptographic operations are isolated within the `App\Modules\Crypto` namespace, providing services for reversible encryption (AES-256-GCM), key rotation, HKDF key derivation, and password hashing (Argon2id).

The application layer consumes these services primarily through a unified `CryptoProvider` facade or specific infrastructure adapters (`*CryptoService`).
Legacy or direct usage of `openssl_*` functions is largely contained within the core crypto modules, with some read-only legacy decryption logic found in older repositories (`PdoSessionListReader`, `PdoAdminQueryReader`).

Key management is driven by environment variables (`CRYPTO_KEYS`, `CRYPTO_ACTIVE_KEY_ID`), with a strict policy enforcing a single active encryption key while allowing decryption with older keys.

---

## 2. Crypto Artifact Inventory (Master Table)

| File Path                                                                      | Class / Function                    | Layer          | Type        | Purpose                                                                      | Runtime Usage | Consumed By                                                                                                   |
|:-------------------------------------------------------------------------------|:------------------------------------|:---------------|:------------|:-----------------------------------------------------------------------------|:--------------|:--------------------------------------------------------------------------------------------------------------|
| `app/Domain/Security/CryptoContext.php`                                        | `CryptoContext`                     | Domain         | Registry    | Defines canonical crypto contexts (HKDF info strings).                       | **Used**      | `NotificationCryptoService`, `AdminIdentifierCryptoService`, `TotpSecretCryptoService`, `PdoEmailQueueWriter` |
| `app/Modules/Crypto/DX/CryptoProvider.php`                                     | `CryptoProvider`                    | Module         | Facade      | Unified entry point for Context, Direct, and Password crypto.                | **Used**      | `NotificationCryptoService`, `AdminIdentifierCryptoService`, `TotpSecretCryptoService`, `PdoEmailQueueWriter` |
| `app/Modules/Crypto/DX/CryptoContextFactory.php`                               | `CryptoContextFactory`              | Module         | Factory     | Creates `ReversibleCryptoService` with HKDF-derived keys.                    | **Used**      | `CryptoProvider`                                                                                              |
| `app/Modules/Crypto/DX/CryptoDirectFactory.php`                                | `CryptoDirectFactory`               | Module         | Factory     | Creates `ReversibleCryptoService` with raw root keys.                        | **Used**      | `CryptoProvider`                                                                                              |
| `app/Modules/Crypto/Reversible/ReversibleCryptoService.php`                    | `ReversibleCryptoService`           | Module         | Core Crypto | Orchestrates encryption/decryption using registered algorithms.              | **Used**      | `CryptoContextFactory`, `CryptoDirectFactory`                                                                 |
| `app/Modules/Crypto/Reversible/Algorithms/Aes256GcmAlgorithm.php`              | `Aes256GcmAlgorithm`                | Module         | Core Crypto | Implements AES-256-GCM using `openssl_*`.                                    | **Used**      | `ReversibleCryptoAlgorithmRegistry`                                                                           |
| `app/Modules/Crypto/Reversible/Registry/ReversibleCryptoAlgorithmRegistry.php` | `ReversibleCryptoAlgorithmRegistry` | Module         | Registry    | Registry of allowed crypto algorithms.                                       | **Used**      | `Container`, `ReversibleCryptoService`                                                                        |
| `app/Modules/Crypto/KeyRotation/KeyRotationService.php`                        | `KeyRotationService`                | Module         | Core Crypto | Manages key lifecycle (active vs inactive) and exports keys.                 | **Used**      | `CryptoContextFactory`, `CryptoDirectFactory`                                                                 |
| `app/Modules/Crypto/HKDF/HKDFService.php`                                      | `HKDFService`                       | Module         | Core Crypto | Provides HKDF key derivation logic.                                          | **Used**      | `CryptoContextFactory`                                                                                        |
| `app/Modules/Crypto/Password/PasswordHasher.php`                               | `PasswordHasher`                    | Module         | Core Crypto | Implements password hashing (Argon2id + HMAC pepper).                        | **Used**      | `PasswordService` (conceptually), `PasswordCryptoService`                                                     |
| `app/Domain/Service/PasswordService.php`                                       | `PasswordService`                   | Domain         | Service     | Domain-level password hashing service (wraps logic).                         | **Used**      | `PasswordCryptoService`, `AdminAuthenticationService`                                                         |
| `app/Infrastructure/Crypto/PasswordCryptoService.php`                          | `PasswordCryptoService`             | Infrastructure | Adapter     | Adapter for `PasswordService` implementing `PasswordCryptoServiceInterface`. | **Used**      | `Container`                                                                                                   |
| `app/Infrastructure/Crypto/NotificationCryptoService.php`                      | `NotificationCryptoService`         | Infrastructure | Adapter     | Encrypts/Decrypts notification payloads & recipients.                        | **Used**      | `Container`                                                                                                   |
| `app/Infrastructure/Crypto/AdminIdentifierCryptoService.php`                   | `AdminIdentifierCryptoService`      | Infrastructure | Adapter     | Encrypts/Decrypts admin emails & derives blind indexes.                      | **Used**      | `Container`                                                                                                   |
| `app/Infrastructure/Crypto/TotpSecretCryptoService.php`                        | `TotpSecretCryptoService`           | Infrastructure | Adapter     | Encrypts/Decrypts TOTP seeds.                                                | **Used**      | `Container`                                                                                                   |
| `app/Modules/Email/Queue/PdoEmailQueueWriter.php`                              | `PdoEmailQueueWriter`               | Module         | Service     | Encrypts email queue payloads before DB insertion.                           | **Used**      | `Container`                                                                                                   |
| `app/Infrastructure/Reader/Session/PdoSessionListReader.php`                   | `decryptEmail`                      | Infrastructure | Legacy      | Decrypts admin emails for session listing (Legacy/Direct OpenSSL).           | **Used**      | `SessionListController`                                                                                       |
| `app/Infrastructure/Reader/Admin/PdoAdminQueryReader.php`                      | `decryptEmail`                      | Infrastructure | Legacy      | Decrypts admin emails for admin listing (Legacy/Direct OpenSSL).             | **Used**      | `AdminController` (via interface)                                                                             |
| `app/Infrastructure/Repository/AdminSessionRepository.php`                     | `createSession`                     | Infrastructure | Helper      | Generates random session tokens and hashes them (SHA-256).                   | **Used**      | `AdminAuthenticationService`                                                                                  |
| `app/Domain/Service/VerificationCodeGenerator.php`                             | `generate`                          | Domain         | Service     | Generates random OTPs and hashes them (SHA-256).                             | **Used**      | `VerificationCodeGeneratorInterface`                                                                          |
| `app/Domain/Service/VerificationCodeValidator.php`                             | `validate`                          | Domain         | Service     | Validates OTP hashes (SHA-256).                                              | **Used**      | `VerificationCodeValidatorInterface`                                                                          |

---

## 3. Canonical Crypto Entry Points

These components act as the official gateways for cryptographic operations:

1.  **`App\Modules\Crypto\DX\CryptoProvider`**
    *   Primary entry point for all modern crypto operations.
    *   Exposes `context()`, `direct()`, and `password()` pipelines.

2.  **`App\Modules\Crypto\KeyRotation\KeyRotationService`**
    *   Authority on key state (Active/Inactive/Retired).
    *   Source of truth for root key material.

3.  **`App\Domain\Security\CryptoContext`**
    *   Registry of all valid encryption contexts (e.g., `notification:email:recipient:v1`).

4.  **`App\Infrastructure\Crypto\*CryptoService`**
    *   Specific adapters for domain entities (Passwords, Notifications, Admin Identifiers, TOTP).

---

## 4. Parallel / Duplicate Crypto Paths

1.  **Admin Email Decryption (Modern vs. Legacy)**
    *   **Modern:** `AdminIdentifierCryptoService` uses `CryptoProvider` -> `CryptoContext` -> `HKDF` -> `ReversibleCryptoService`.
    *   **Legacy:** `PdoSessionListReader` and `PdoAdminQueryReader` contain private `decryptEmail` methods that directly invoke `openssl_decrypt` using `AdminConfigDTO->emailEncryptionKey`.
    *   *Observation:* The readers bypass the central `CryptoProvider` and Key Rotation logic, relying on a single config key.

2.  **Password Hashing**
    *   `PasswordService` (Domain) contains the actual hashing logic (HMAC + `password_hash`).
    *   `PasswordHasher` (Module) exists but `PasswordService` seems to implement its own logic in the provided file content, or `PasswordCryptoService` delegates to `PasswordService`.
    *   *Observation:* `PasswordService` appears to be the active implementation used by `AdminAuthenticationService`.

---

## 5. Direct Crypto Primitive Usage

Files that directly invoke `openssl_*`, `hash_*`, `password_*`, or `random_*` functions:

*   **`App\Modules\Crypto\Reversible\Algorithms\Aes256GcmAlgorithm`**
    *   `openssl_encrypt`, `openssl_decrypt`, `random_bytes`
*   **`App\Modules\Crypto\Password\PasswordHasher`**
    *   `hash_hmac`, `password_hash`, `password_verify`, `password_needs_rehash`
*   **`App\Domain\Service\PasswordService`**
    *   `hash_hmac`, `password_hash`, `password_verify`
*   **`App\Modules\Crypto\HKDF\HKDFKeyDeriver`**
    *   `hash_hmac`
*   **`App\Infrastructure\Crypto\AdminIdentifierCryptoService`**
    *   `hash_hmac` (for Blind Index derivation)
*   **`App\Infrastructure\Repository\AdminSessionRepository`**
    *   `random_bytes`, `hash` (SHA-256)
*   **`App\Domain\Service\VerificationCodeGenerator`**
    *   `random_int`, `hash` (SHA-256)
*   **`App\Domain\Service\VerificationCodeValidator`**
    *   `hash` (SHA-256), `hash_equals`
*   **`App\Infrastructure\Reader\Session\PdoSessionListReader`**
    *   `openssl_decrypt`, `base64_decode`
*   **`App\Infrastructure\Reader\Admin\PdoAdminQueryReader`**
    *   `openssl_decrypt`, `base64_decode`

---

## 6. Crypto Configuration & ENV Surface

| ENV Variable              | Consumed By                                                                       | Purpose                                                                                             |
|:--------------------------|:----------------------------------------------------------------------------------|:----------------------------------------------------------------------------------------------------|
| `CRYPTO_KEYS`             | `Container` -> `KeyRotationService`                                               | JSON array of root keys.                                                                            |
| `CRYPTO_ACTIVE_KEY_ID`    | `Container` -> `KeyRotationService`                                               | ID of the key used for new encryptions.                                                             |
| `EMAIL_ENCRYPTION_KEY`    | `Container` -> `KeyRotationService` (Fallback)                                    | Legacy key support.                                                                                 |
| `EMAIL_ENCRYPTION_KEY`    | `PdoSessionListReader`, `PdoAdminQueryReader`                                     | Legacy decryption in readers.                                                                       |
| `PASSWORD_PEPPER`         | `Container` -> `PasswordService`                                                  | HMAC key for password hashing.                                                                      |
| `PASSWORD_PEPPER_OLD`     | `Container` -> `PasswordService`                                                  | Rotation support for password pepper.                                                               |
| `EMAIL_BLIND_INDEX_KEY`   | `Container` -> `AuthController`, `LoginController`, `EmailVerificationController` | Key for blind index hashing (though `AdminIdentifierCryptoService` uses `ADMIN_IDENTIFIER_PEPPER`). |
| `ADMIN_IDENTIFIER_PEPPER` | `Container` -> `AdminIdentifierCryptoService`                                     | Pepper for Admin Email Blind Index.                                                                 |

---

## 7. Dead / Orphaned Crypto Artifacts

*   **`App\Modules\Crypto\Password\PasswordHasher`**: While fully implemented, the `Container` wires `PasswordService` (Domain) directly to `PasswordCryptoService`. `PasswordHasher` (Module) does not appear to be injected or used in the provided `Container.php` configuration, suggesting `PasswordService` duplicates or supersedes its logic in the current runtime.

---

## 8. Observations (FACTS ONLY)

1.  **Hybrid Architecture:** The system is in a transitional state. Core write operations (Encryption) use the new `CryptoProvider` pipeline (HKDF, Key Rotation). Read operations in specific Query Readers (`PdoSessionListReader`, `PdoAdminQueryReader`) use legacy direct decryption.
2.  **Context Usage:** `CryptoContext` defines versioned contexts (e.g., `:v1`), which are correctly used by the `NotificationCryptoService` and `PdoEmailQueueWriter`.
3.  **Key Management:** The `KeyRotationService` enforces a "Single Active Key" policy but supports multiple keys for decryption, enabling key rotation.
4.  **Blind Indexes:** Blind indexes are used for searching encrypted fields (Emails). `AdminIdentifierCryptoService` uses `hash_hmac` with a dedicated pepper for this purpose.
5.  **Session Security:** Session IDs are hashed (SHA-256) before storage, preventing session hijacking via database leaks.
6.  **Verification Codes:** OTPs are hashed (SHA-256) before storage.
