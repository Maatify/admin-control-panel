# Cryptographic Implementation Audit

## 1. Executive Snapshot (Facts Only)
- **Project Status**: Hybrid State (Legacy Direct Crypto + Modern Provider).
- **Key Management**: `KeyRotationService` exists but is only partially adopted.
- **Critical Gaps**: 
  - Admin Email encryption bypasses the rotation system (Hardcoded Key).
  - TOTP Secrets are stored in **PLAINTEXT** files.
  - `AdminIdentifierCryptoService` and `TotpSecretCryptoService` are **UNUSED** (Dead Code).
- **Rotation Support**: Only available for `Email Queue`. Admin Identifiers will break if keys change.

## 2. Key Sources Inventory

| Variable Name             | Source         | Purpose                                               | Format                   | Rotation Support                    |
|:--------------------------|:---------------|:------------------------------------------------------|:-------------------------|:------------------------------------|
| `CRYPTO_KEYS`             | `$_ENV` (JSON) | Root keys for `KeyRotationService` (Modern)           | JSON Array `[{id, key}]` | **YES** (via ID)                    |
| `CRYPTO_ACTIVE_KEY_ID`    | `$_ENV`        | Identifies current write key for `KeyRotationService` | String (UUID/ID)         | **YES**                             |
| `EMAIL_ENCRYPTION_KEY`    | `$_ENV`        | Legacy/Fallback encryption key (Admin Emails)         | Hex/Raw String           | **NO** (Hard Break)                 |
| `PASSWORD_PEPPER`         | `$_ENV`        | HMAC key for Password Hashing                         | String                   | **YES** (via `PASSWORD_PEPPER_OLD`) |
| `PASSWORD_PEPPER_OLD`     | `$_ENV`        | Previous HMAC key for Password Verification           | String                   | **YES**                             |
| `EMAIL_BLIND_INDEX_KEY`   | `$_ENV`        | Blind Indexing for Admin Emails (Controller)          | String                   | **NO** (Index Rebuild Req.)         |
| `ADMIN_IDENTIFIER_PEPPER` | `$_ENV`        | Blind Indexing for `AdminIdentifierCryptoService`     | String                   | **NO**                              |
| `MAIL_ENCRYPTION`         | `$_ENV`        | SMTP Transport Encryption (TLS/SSL)                   | String                   | N/A (Config)                        |

## 3. Key Rotation Reality

| Mechanism              | Status        | Details                                                                                                             |
|:-----------------------|:--------------|:--------------------------------------------------------------------------------------------------------------------|
| **KeyRotationService** | **Active**    | Fully implemented with `StrictSingleActiveKeyPolicy`. Loads from `CRYPTO_KEYS`.                                     |
| **Admin Email Flow**   | **Bypassed**  | Uses `EMAIL_ENCRYPTION_KEY` directly via `AdminConfigDTO`. No `key_id` storage. **Rotation will break decryption.** |
| **Email Queue Flow**   | **Supported** | Uses `CryptoProvider`. Stores `key_id` alongside ciphertext. Supports rotation.                                     |
| **TOTP Flow**          | **N/A**       | Secrets are stored in **PLAINTEXT** files. No encryption used.                                                      |
| **Passwords**          | **Supported** | `PasswordService` checks `PASSWORD_PEPPER` then `PASSWORD_PEPPER_OLD`.                                              |

## 4. Encryption Flow Matrix

### A) Admin Identifiers (Emails)
- **Entry Point**: `AdminController::addEmail`, `AdminController::getEmail`
- **Key Source**: `EMAIL_ENCRYPTION_KEY` (Direct Access)
- **Algorithm**: `AES-256-GCM` (via `openssl_encrypt`)
- **Key ID Stored**: **NO**
- **HKDF Used**: **NO**
- **Rotation**: **IMPOSSIBLE** (Data loss on key change)
- **Blind Index**: Uses `EMAIL_BLIND_INDEX_KEY` (HMAC-SHA256).

### B) Email Queue Recipients & Payloads
- **Entry Point**: `PdoEmailQueueWriter::enqueue`
- **Key Source**: `CryptoProvider` -> `KeyRotationService` (`CRYPTO_KEYS`)
- **Algorithm**: `AES-256-GCM` (via `ReversibleCryptoService`)
- **Key ID Stored**: **YES** (`recipient_key_id`, `payload_key_id`)
- **HKDF Used**: **YES** (Contexts: `email:recipient:v1`, `email:payload:v1`)
- **Rotation**: **POSSIBLE**

### C) TOTP Secrets
- **Entry Point**: `StepUpService` -> `FileTotpSecretRepository`
- **Key Source**: **NONE**
- **Algorithm**: **NONE** (Plaintext)
- **Storage**: Files in `storage/totp/{id}.secret`
- **Rotation**: N/A

### D) Passwords
- **Entry Point**: `AdminAuthenticationService` -> `PasswordService`
- **Key Source**: `PASSWORD_PEPPER`
- **Algorithm**: `Argon2id` (pre-hashed with HMAC-SHA256)
- **Rotation**: **POSSIBLE** (Dual-pepper check)

## 5. CryptoProvider Adoption Map

| Component                      | Uses CryptoProvider? | Context                                  | Status                    |
|:-------------------------------|:---------------------|:-----------------------------------------|:--------------------------|
| `PdoEmailQueueWriter`          | **YES**              | `email:recipient:v1`, `email:payload:v1` | ✅ Correct                 |
| `AdminController`              | **NO**               | N/A                                      | ❌ Direct `openssl` usage  |
| `FileTotpSecretRepository`     | **NO**               | N/A                                      | ❌ Plaintext storage       |
| `AdminIdentifierCryptoService` | **YES**              | `identifier:email:v1`                    | 💀 **DEAD CODE** (Unused) |
| `NotificationCryptoService`    | **YES**              | `notification:email:*`                   | 💀 **DEAD CODE** (Unused) |
| `TotpSecretCryptoService`      | **YES**              | `totp:seed:v1`                           | 💀 **DEAD CODE** (Unused) |

## 6. Dead / Legacy / Parallel Crypto Paths

1.  **Parallel Blind Indexing**:
    - `AdminController` uses `EMAIL_BLIND_INDEX_KEY`.
    - `AdminIdentifierCryptoService` uses `ADMIN_IDENTIFIER_PEPPER`.
    - These are likely different keys, leading to incompatible blind indexes if the service is adopted without migration.

2.  **Unused Infrastructure**:
    - `AdminIdentifierCryptoService` is fully implemented but never called by `AdminController` or `AdminEmailRepository`.
    - `TotpSecretCryptoService` is fully implemented but ignored by `FileTotpSecretRepository`.

3.  **Legacy Direct Access**:
    - `AdminController` manually constructs ciphertext (`iv . tag . ciphertext`) and base64 encodes it. This format differs from `ReversibleCryptoEncryptionResultDTO` used by `CryptoProvider`.

## 7. Hard Risks (Technical)

1.  **TOTP Secrets Exposed**: TOTP seeds are stored as plaintext files. Access to the filesystem equals total compromise of 2FA.
2.  **Admin Data Lock-in**: Admin emails are encrypted with a single static key (`EMAIL_ENCRYPTION_KEY`). If this key is compromised or lost, rotation is impossible without downtime/re-encryption script.
3.  **Configuration Drift**: `AdminConfigDTO` exposes raw keys (`emailEncryptionKey`) to controllers, violating the abstraction layer of `CryptoProvider`.
4.  **Inconsistent Blind Indexes**: If `AdminIdentifierCryptoService` is ever used, it will fail to look up users created by `AdminController` due to different pepper variables.

## 8. Open Questions

- Why is `FileTotpSecretRepository` not using `TotpSecretCryptoService`?
- Is `EMAIL_ENCRYPTION_KEY` intended to be the same value as one of the keys in `CRYPTO_KEYS`?
- Why does `AdminController` exist in a "Legacy" state while `PdoEmailQueueWriter` is fully modernized?
