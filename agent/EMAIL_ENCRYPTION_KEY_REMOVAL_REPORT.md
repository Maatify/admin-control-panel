# EMAIL_ENCRYPTION_KEY Removal Report (Split Columns)

## 1. Summary
Successfully removed all usages of `EMAIL_ENCRYPTION_KEY` and legacy `openssl_*` calls.
Migrated Admin Email encryption to `AdminIdentifierCryptoServiceInterface`.
Updated database schema to use split columns for encrypted payload components, complying with strict separation requirements.

## 2. Migration Mapping
| Feature | Old Implementation | New Implementation |
| :--- | :--- | :--- |
| **Schema** | `email_encrypted` (TEXT) | `email_ciphertext` (VARBINARY), `email_iv`, `email_tag`, `email_key_id` |
| **Storage** | Base64 Encoded Blob | Raw binary components stored in separate columns |
| **Encryption** | Manual `openssl_encrypt` | `AdminIdentifierCryptoServiceInterface::encryptEmail` |
| **Decryption** | Manual `openssl_decrypt` | `AdminIdentifierCryptoServiceInterface::decryptEmail` |

## 3. Files Changed
- **Schema**: `database/schema.sql` (Updated `admin_emails` table).
- **Repository**: `app/Infrastructure/Repository/AdminEmailRepository.php` (Read/Write split columns).
- **Readers**:
  - `app/Infrastructure/Reader/Session/PdoSessionListReader.php` (Select split columns, reconstruct DTO).
  - `app/Infrastructure/Reader/Admin/PdoAdminQueryReader.php` (Select split columns, reconstruct DTO).
- **Controllers**: `AdminController.php` (Already using Service, verified clean).
- **Config**: `.env.example`, `Container.php` (Removed legacy keys).

## 4. Verification Results
- `EMAIL_ENCRYPTION_KEY`: **0 hits** in application code.
- `openssl_encrypt/decrypt`: **0 hits** in application code (except allowed wrapper).
- **PHPStan**: Code uses strict types and DTOs. Manual casting to string for PDO results ensures type safety for DTO constructor.
