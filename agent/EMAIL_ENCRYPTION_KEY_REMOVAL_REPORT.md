# EMAIL_ENCRYPTION_KEY Removal Report

## 1. Summary
Successfully removed all usages of `EMAIL_ENCRYPTION_KEY` and legacy `openssl_*` calls in the application code. Migrated all Admin Identity encryption/decryption to the approved `AdminIdentifierCryptoServiceInterface`.

## 2. Migration Mapping
| Feature | Old Implementation | New Implementation |
| :--- | :--- | :--- |
| **Admin Email Storage** | `openssl_encrypt` (AES-256-GCM) + Base64 blob | `AdminIdentifierCryptoServiceInterface::encryptEmail` + JSON serialized `EncryptedPayloadDTO` |
| **Admin Email Retrieval** | `openssl_decrypt` (AES-256-GCM) from Base64 blob | `AdminIdentifierCryptoServiceInterface::decryptEmail` from JSON serialized `EncryptedPayloadDTO` |
| **Session List Display** | Manual `openssl_decrypt` in `PdoSessionListReader` | `AdminIdentifierCryptoServiceInterface::decryptEmail` in `PdoSessionListReader` |
| **Admin List Display** | Manual `openssl_decrypt` in `PdoAdminQueryReader` | `AdminIdentifierCryptoServiceInterface::decryptEmail` in `PdoAdminQueryReader` |
| **Bootstrap Script** | Manual `openssl_encrypt` in script | `AdminIdentifierCryptoServiceInterface` injected from Container |

## 3. Files Changed
1.  `app/Infrastructure/Repository/AdminEmailRepository.php` (Updated storage format to JSON)
2.  `app/Infrastructure/Reader/Session/PdoSessionListReader.php` (Migrated to Service)
3.  `app/Infrastructure/Reader/Admin/PdoAdminQueryReader.php` (Migrated to Service)
4.  `app/Http/Controllers/AdminController.php` (Migrated to Service)
5.  `scripts/bootstrap_admin.php` (Migrated to Service)
6.  `app/Bootstrap/Container.php` (Removed legacy key injection)
7.  `.env.example` (Removed deprecated key)

## 4. Verification Results

### Grep Analysis
- `EMAIL_ENCRYPTION_KEY`: **0 hits** in application code (found only in docs/reports).
- `openssl_encrypt`: **0 hits** in application code (except allowed low-level `Aes256GcmAlgorithm`).
- `openssl_decrypt`: **0 hits** in application code (except allowed low-level `Aes256GcmAlgorithm`).

### Type Safety (PHPStan)
- Code changes strictly follow `AdminIdentifierCryptoServiceInterface`.
- `EncryptedPayloadDTO` usage enforced.
- JSON decoding includes type checks before object construction.

## 5. Next Steps
- **Database Reset**: Since the storage format changed from "Base64 string" to "JSON string", a database reset is required (as per scope).
- **Environment**: Update `.env` to remove `EMAIL_ENCRYPTION_KEY` and ensure `CRYPTO_KEYS` are set.
