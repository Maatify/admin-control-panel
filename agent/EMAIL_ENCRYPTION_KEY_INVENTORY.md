# EMAIL_ENCRYPTION_KEY Inventory Report (Updated)

## 1. Codebase Occurrences
*No active usage found in application code.*
Previous remediation successfully removed `EMAIL_ENCRYPTION_KEY` and direct `openssl_*` calls from:
- `app/Bootstrap/Container.php`
- `app/Http/Controllers/AdminController.php`
- `app/Infrastructure/Reader/Session/PdoSessionListReader.php`
- `app/Infrastructure/Reader/Admin/PdoAdminQueryReader.php`
- `scripts/bootstrap_admin.php`

All hits in `grep` are within documentation, reports, or the allowed low-level algorithm wrapper (`Aes256GcmAlgorithm`).

## 2. Database Status
Current schema (`admin_emails` table):
- `email_encrypted`: `TEXT` (Previously used for Base64 blob, currently being used for JSON by recent patch).

## 3. Requirement Mismatch
The current implementation uses a single column (`email_encrypted`) storing a JSON string.
The new requirement strictly demands **split columns**:
- `email_ciphertext`
- `email_iv`
- `email_tag`
- `email_key_id`

This requires a schema migration and code update.
