# Key Unification Strategy (Read-Only)

**Role:** Security / Crypto Architect  
**Scope:** Full Repository Analysis  
**Mode:** Strict / Read-Only / Design-Only  
**Target:** `admin-control-panel`

---

## 1. Inventory of Cryptographic Keys

This section lists every cryptographic key currently present in the codebase, its source, and its usage.

| Key / Env Variable        | Purpose                                  | Consuming Classes / Files                                                                               | Primitive                                               | Storage Format           | Rotation Support                                                 | Scope       |
|:--------------------------|:-----------------------------------------|:--------------------------------------------------------------------------------------------------------|:--------------------------------------------------------|:-------------------------|:-----------------------------------------------------------------|:------------|
| `EMAIL_BLIND_INDEX_KEY`   | Blind Indexing for Admin Emails (Lookup) | `AdminConfigDTO`, `AdminController`, `LoginController`, `EmailVerificationController`, `AuthController` | HMAC-SHA256                                             | Raw String (Env)         | **NO** (Requires Index Rebuild)                                  | Identity    |
| `EMAIL_ENCRYPTION_KEY`    | Legacy Encryption for Admin Emails       | `AdminConfigDTO`, `Container` (Fallback for `KeyRotationService`)                                       | AES-GCM (via `ReversibleCryptoService` or legacy logic) | Hex/Raw String (Env)     | **PARTIAL** (Mapped to `CRYPTO_KEYS` if configured, else static) | Identity    |
| `PASSWORD_PEPPER`         | Password Hashing (Mandatory Pepper)      | `AdminConfigDTO`, `PasswordService`, `PasswordHasher`                                                   | HMAC-SHA256 (pre-Argon2id)                              | Raw String (Env)         | **YES** (via `PASSWORD_PEPPER_OLD`)                              | Auth        |
| `PASSWORD_PEPPER_OLD`     | Password Verification (Rotation Support) | `AdminConfigDTO`, `PasswordService`                                                                     | HMAC-SHA256                                             | Raw String (Env)         | **YES**                                                          | Auth        |
| `CRYPTO_KEYS`             | Root Keys for Modern Encryption          | `AdminConfigDTO`, `KeyRotationService`, `Container`                                                     | AES-GCM (Root for HKDF)                                 | JSON Array `[{id, key}]` | **YES** (Native)                                                 | Global Root |
| `CRYPTO_ACTIVE_KEY_ID`    | Active Write Key Identifier              | `AdminConfigDTO`, `KeyRotationService`                                                                  | N/A                                                     | String (UUID/ID)         | **YES**                                                          | Global Root |
| `ADMIN_IDENTIFIER_PEPPER` | Blind Indexing for Admin Identifiers     | `Container` (injected into `AdminIdentifierCryptoService`)                                              | HMAC-SHA256                                             | Raw String (Env)         | **NO**                                                           | Identity    |

---

## 2. Crypto Domain Separation

This section identifies distinct cryptographic domains and their current isolation status.

### Domain: Admin Identity (PII)
*   **Keys:** `EMAIL_BLIND_INDEX_KEY`, `EMAIL_ENCRYPTION_KEY` (Legacy), `ADMIN_IDENTIFIER_PEPPER`
*   **Consumers:** `AdminController`, `AdminAuthenticationService`, `AdminIdentifierCryptoService`
*   **Isolation Status:** **Mixed**.
    *   `AdminIdentifierCryptoService` uses `CryptoProvider` (Modern).
    *   Legacy controllers access `EMAIL_BLIND_INDEX_KEY` directly via `AdminConfigDTO`.
    *   `EMAIL_ENCRYPTION_KEY` is used as a fallback root key in `Container` if `CRYPTO_KEYS` is missing.

### Domain: Authentication / Passwords
*   **Keys:** `PASSWORD_PEPPER`, `PASSWORD_PEPPER_OLD`
*   **Consumers:** `PasswordService`, `PasswordHasher`
*   **Isolation Status:** **High**.
    *   Encapsulated within `PasswordService` / `PasswordHasher`.
    *   Keys are injected via `AdminConfigDTO` -> `PasswordService`.
    *   Strictly separated from reversible encryption keys.

### Domain: Notifications
*   **Keys:** Derived via `CryptoProvider` (HKDF) from `CRYPTO_KEYS`.
*   **Consumers:** `NotificationCryptoService`
*   **Isolation Status:** **High**.
    *   Uses `CryptoProvider->context('notification:...')`.
    *   Keys are ephemeral/derived, not stored directly in Env.

### Domain: TOTP Secrets
*   **Keys:** Derived via `CryptoProvider` (HKDF) from `CRYPTO_KEYS`.
*   **Consumers:** `TotpSecretCryptoService`
*   **Isolation Status:** **High**.
    *   Uses `CryptoProvider->context('totp:...')`.

### Domain: Global Root (Key Rotation)
*   **Keys:** `CRYPTO_KEYS`, `CRYPTO_ACTIVE_KEY_ID`
*   **Consumers:** `KeyRotationService`, `CryptoProvider`
*   **Isolation Status:** **Foundational**.
    *   Serves as the root of trust for all modern services (`Notification`, `Totp`, `AdminIdentifier`).
    *   Legacy keys (`EMAIL_...`) exist outside this hierarchy.

---

## 3. Current State vs Target State (Conceptual)

### Current State (As-Is)
The system is in a **hybrid state**.
1.  **Modern Core:** `CryptoProvider` + `KeyRotationService` + `HKDF` provides a robust, rotatable, domain-separated architecture. Services like `NotificationCryptoService` and `TotpSecretCryptoService` are fully onboarded.
2.  **Legacy Identity:** Admin email handling relies on static keys (`EMAIL_BLIND_INDEX_KEY`, `EMAIL_ENCRYPTION_KEY`) accessed directly from configuration DTOs.
3.  **Password Auth:** Uses a dedicated pepper strategy (`PASSWORD_PEPPER`) which is robust but distinct from the `CryptoProvider` pipeline.

### Conceptual Target State
A **Unified Cryptographic Model** where:
1.  **Single Root of Trust:** All reversible encryption derives from `CRYPTO_KEYS` managed by `KeyRotationService`.
2.  **Domain Derivation:** Every domain (Identity, Notification, TOTP) uses `HKDF` with a unique context string (e.g., `identity:email:v1`) to derive its specific keys from the root.
3.  **No Static Keys:** `EMAIL_ENCRYPTION_KEY` is retired; `EMAIL_BLIND_INDEX_KEY` is either migrated to a derived key (requiring re-indexing) or formally encapsulated as a "Legacy Blind Index" provider.
4.  **Opaque Usage:** Consumers never see raw keys or `AdminConfigDTO` crypto fields. They only request `CryptoProvider->context('...')`.

---

## 4. Key Authority Matrix

| Domain                              | Allowed Key Source                            | Forbidden Key Source                      | Notes                                                               |
|:------------------------------------|:----------------------------------------------|:------------------------------------------|:--------------------------------------------------------------------|
| **Reversible Encryption** (General) | `CryptoProvider` (via `KeyRotationService`)   | `$_ENV`, `AdminConfigDTO` (Direct Access) | All new encryption MUST use `CryptoProvider`.                       |
| **Admin Identity** (Email)          | `AdminIdentifierCryptoService` (Modern)       | `EMAIL_ENCRYPTION_KEY` (Direct)           | Legacy direct access prevents rotation.                             |
| **Blind Indexing**                  | `AdminIdentifierCryptoService` (Encapsulated) | `EMAIL_BLIND_INDEX_KEY` (Direct)          | Direct access scatters key usage across controllers.                |
| **Passwords**                       | `PasswordService` (Injected Pepper)           | `CryptoProvider` (Reversible Keys)        | Passwords MUST use hashing (Argon2id), never reversible encryption. |
| **TOTP Secrets**                    | `TotpSecretCryptoService`                     | `$_ENV`                                   | Secrets are encrypted at rest using derived keys.                   |

---

## 5. Rotation Intent (Design Level Only)

### Rotatable by Design
*   **Root Keys (`CRYPTO_KEYS`):** Fully supported by `KeyRotationService`.
*   **Derived Keys (Notification, TOTP):** Automatically rotate when Root Keys rotate.
*   **Passwords:** Supported via `PASSWORD_PEPPER_OLD` and `needsRehash()` logic.

### Immutable by Design
*   **Blind Indexes:** Changing the key invalidates all lookups. Rotation requires a full database re-indexing (offline migration).

### Currently Preventing Rotation
*   **Legacy Admin Email:** Direct usage of `EMAIL_ENCRYPTION_KEY` in `AdminController` means changing this key breaks decryption immediately. It is not wired into the `KeyRotationService` decryption loop for legacy data.

---

## 6. Legacy Classification

| Path / Class                                             | Classification | Notes                                                                            |
|:---------------------------------------------------------|:---------------|:---------------------------------------------------------------------------------|
| `App\Modules\Crypto\DX\CryptoProvider`                   | **Canonical**  | The standard entry point for all crypto.                                         |
| `App\Modules\Crypto\KeyRotation\KeyRotationService`      | **Canonical**  | The authority on key lifecycle.                                                  |
| `App\Infrastructure\Crypto\AdminIdentifierCryptoService` | **Canonical**  | The target wrapper for identity crypto.                                          |
| `App\Domain\DTO\AdminConfigDTO::$emailEncryptionKey`     | **Legacy**     | Should eventually be removed in favor of `CryptoProvider`.                       |
| `App\Domain\DTO\AdminConfigDTO::$emailBlindIndexKey`     | **Legacy**     | Should be encapsulated, not exposed publicly.                                    |
| `App\Bootstrap\Container` (Legacy Fallback Logic)        | **Parallel**   | Maps `EMAIL_ENCRYPTION_KEY` to `KeyRotationService` if `CRYPTO_KEYS` is missing. |

---

## 7. Explicit Non-Goals

*   **This document does not authorize refactors.** No code changes are permitted based on this document alone.
*   **This document does not mandate migrations.** It describes the state, not the schedule.
*   **This document does not deprecate keys.** Existing keys remain valid and necessary for operation.
*   **This document does not alter runtime behavior.** The system continues to function exactly as currently implemented.
