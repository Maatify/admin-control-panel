# EMAIL_ENCRYPTION_KEY Migration Plan (Split Columns)

## 1. Schema Change (`database/schema.sql`)
Replace the `admin_emails` table definition to support split columns.

**Old:**
```sql
CREATE TABLE admin_emails (
    ...
    email_encrypted TEXT NOT NULL,
    ...
);
```

**New:**
```sql
CREATE TABLE admin_emails (
    ...
    email_ciphertext VARBINARY(512) NOT NULL,
    email_iv VARBINARY(16) NOT NULL,
    email_tag VARBINARY(16) NOT NULL,
    email_key_id VARCHAR(64) NOT NULL,
    ...
);
```
*Note: Using `VARBINARY` for ciphertext/iv/tag as they are raw bytes from `EncryptedPayloadDTO`. `VARCHAR` for key ID.*

## 2. Code Migration

### A. Repository (`AdminEmailRepository`)
- **Insert**: Explode `EncryptedPayloadDTO` into the 4 columns.
- **Select**: Select the 4 columns and reconstruct `EncryptedPayloadDTO`.
- **Note**: Handle potential type casting if PDO returns strings for VARBINARY (usually fine, but `EncryptedPayloadDTO` expects strings).

### B. Readers (`PdoSessionListReader` & `PdoAdminQueryReader`)
- **Query**: Update SQL to fetch `email_ciphertext`, `email_iv`, `email_tag`, `email_key_id` instead of `email_encrypted`.
- **Reconstruction**: Reconstruct `EncryptedPayloadDTO` from the row data.
- **Decryption**: Pass DTO to `AdminIdentifierCryptoService`.

### C. Services / Controllers
- No changes required (they operate on DTOs/Interfaces).

## 3. Verification
- `grep` checks (ensure no `email_encrypted` references remain in active code).
- Verification of schema file.
