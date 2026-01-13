# EMAIL_ENCRYPTION_KEY Migration Plan

## 1. Goal
Remove all dependencies on `EMAIL_ENCRYPTION_KEY` and legacy `openssl_*` calls, replacing them with `AdminIdentifierCryptoServiceInterface`. Update storage format to JSON-serialized `EncryptedPayloadDTO`.

## 2. Component Migration

### A. Storage Layer (`AdminEmailRepository`)
- **Modify** `addEmail(int $adminId, string $blindIndex, EncryptedPayloadDTO $encryptedEmail): void`
  - Serialize `EncryptedPayloadDTO` to JSON (`json_encode`).
  - Store in `email_encrypted` column.
- **Modify** `getEncryptedEmail(int $adminId): ?EncryptedPayloadDTO`
  - Fetch JSON string from `email_encrypted`.
  - Deserialize (`json_decode`) to array.
  - Reconstruct and return `EncryptedPayloadDTO`.
- **Note**: This changes the expected data in the DB. DB reset is assumed/allowed.

### B. Read Layer (`PdoSessionListReader` & `PdoAdminQueryReader`)
- **Inject** `AdminIdentifierCryptoServiceInterface`.
- **Remove** `emailEncryptionKey` (and `emailBlindIndexKey` for `PdoAdminQueryReader`) from constructor/properties.
- **Refactor** `decryptEmail` (private helper):
  - Input: JSON string from DB.
  - Action: Decode JSON to `EncryptedPayloadDTO` -> call `service->decryptEmail($dto)`.
- **Refactor** `queryAdmins` (`PdoAdminQueryReader`):
  - Use `service->deriveEmailBlindIndex($email)` for filter logic.

### C. Application Layer (`AdminController`)
- **Inject** `AdminIdentifierCryptoServiceInterface`.
- **Remove** `emailBlindIndexKey` and `emailEncryptionKey` from constructor.
- **Refactor** `addEmail`:
  - `blindIndex` = `service->deriveEmailBlindIndex($email)`.
  - `encryptedDto` = `service->encryptEmail($email)`.
  - Call `repo->addEmail(..., $blindIndex, $encryptedDto)`.
- **Refactor** `lookupEmail`:
  - `blindIndex` = `service->deriveEmailBlindIndex($email)`.
- **Refactor** `getEmail`:
  - `encryptedDto` = `repo->getEncryptedEmail(...)`.
  - `email` = `service->decryptEmail($encryptedDto)`.

### D. Bootstrap Script (`scripts/bootstrap_admin.php`)
- **Get** `AdminIdentifierCryptoServiceInterface` from container.
- **Remove** direct `openssl_encrypt` and `$_ENV` access.
- **Action**:
  - Derive blind index using service.
  - Encrypt email using service.
  - Call `repo->addEmail` with DTO.

### E. Configuration (`Container.php` & `.env`)
- **Remove** `EMAIL_ENCRYPTION_KEY` from required ENV list.
- **Remove** `EMAIL_ENCRYPTION_KEY` injection into:
  - `AdminController`
  - `PdoSessionListReader`
  - `PdoAdminQueryReader`
- **Ensure** `AdminIdentifierCryptoServiceInterface` is correctly bound (it is).

## 3. Verification Plan
1. **Grep Check**: Ensure 0 hits for `EMAIL_ENCRYPTION_KEY`, `openssl_encrypt`, `openssl_decrypt` (except in unrelated/allowed files if any - none expected).
2. **PHPStan**: Run analysis to ensure type safety (especially with new DTO usage).
3. **Manual Verification**: Since I cannot run the app, I will rely on unit tests if available, or strict code review. (I'll check for tests).
4. **Pre-commit**: Run provided pre-commit instructions.
