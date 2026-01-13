# EMAIL_ENCRYPTION_KEY Inventory Report

## 1. Codebase Occurrences

### `app/Bootstrap/Container.php`
- **Line 64**: `EMAIL_ENCRYPTION_KEY` is required in `.env` validation.
- **Line 205**: Injected into `AdminController`.
- **Line 889**: Injected into `PdoSessionListReader`.
- **Line 953**: Injected into `PdoAdminQueryReader`.

### `app/Http/Controllers/AdminController.php`
- **Class**: `AdminController`
- **Method**: `addEmail`
- **Operation**: `openssl_encrypt` (Write Path)
- **Data**: Admin Email
- **Context**: Encrypting new admin email before storage.

- **Method**: `getEmail`
- **Operation**: `openssl_decrypt` (Read Path)
- **Data**: Admin Email
- **Context**: Decrypting admin email for display/response.

### `app/Infrastructure/Reader/Session/PdoSessionListReader.php`
- **Class**: `PdoSessionListReader`
- **Method**: `decryptEmail` (Private helper)
- **Operation**: `openssl_decrypt` (Read Path)
- **Data**: Admin Email
- **Context**: Decrypting admin email for session list display.

### `app/Infrastructure/Reader/Admin/PdoAdminQueryReader.php`
- **Class**: `PdoAdminQueryReader`
- **Method**: `decryptEmail` (Private helper)
- **Operation**: `openssl_decrypt` (Read Path)
- **Data**: Admin Email
- **Context**: Decrypting admin email for admin list display.

### `scripts/bootstrap_admin.php`
- **File**: `scripts/bootstrap_admin.php`
- **Operation**: `openssl_encrypt` (Write Path)
- **Data**: Admin Email
- **Context**: Encrypting admin email during initial bootstrap.

## 2. Database Columns
- **Table**: `admin_emails`
- **Column**: `email_encrypted`
- **Description**: Stores the AES-256-GCM encrypted email, encoded as `base64(iv . tag . ciphertext)`.
