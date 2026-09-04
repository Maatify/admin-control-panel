# AdminConfigDTO Sensitive Usage Inventory

## 1. Definition
File: `app/Domain/DTO/AdminConfigDTO.php`
Fields:
- `appEnv` (string)
- `appDebug` (bool)
- `timezone` (string)
- `passwordPeppers` (array<string, string>) [SENSITIVE]
- `passwordActivePepperId` (string)
- `emailBlindIndexKey` (string) [SENSITIVE]
- `emailEncryptionKey` (string) [SENSITIVE]
- `dbHost` (string)
- `dbName` (string)
- `dbUser` (string)
- `dbPass` (string) [SENSITIVE]
- `isRecoveryMode` (bool)
- `cryptoKeys` (array<int, array{id: string, key: string}>) [SENSITIVE]
- `activeKeyId` (?string)

## 2. Instantiations
- `app/Bootstrap/Container.php`: Created from `$_ENV` and injected into container.

## 3. Usages
### Sensitive Fields
- `cryptoKeys`:
  - `app/Bootstrap/Container.php`: Used to configure `KeyRotationService`.
  - `tests/admin/Canonical/Admins/AdminsQueryContractTest.php`: Mock usage.
- `passwordPeppers`:
  - `app/Bootstrap/Container.php`: Used to configure `PasswordPepperRing`.
- `emailBlindIndexKey`:
  - `app/Bootstrap/Container.php`: Injected into `AuthController`, `LoginController`, `EmailVerificationController`.
  - `app/Infrastructure/Reader/Admin/PdoAdminQueryReader.php`: Used for searching by email.
  - `app/Http/Controllers/AdminController.php`: Used for blind index calculation.
  - `scripts/bootstrap_admin.php`: Used for bootstrapping.
- `emailEncryptionKey`:
  - `app/Infrastructure/Reader/Admin/PdoAdminQueryReader.php`: Used for decrypting emails.
  - `app/Http/Controllers/AdminController.php`: Used for encrypting/decrypting emails.
  - `scripts/bootstrap_admin.php`: Used for bootstrapping.
- `dbPass`:
  - `app/Bootstrap/Container.php`: Used for PDO connection.

### General Usage
- `AdminConfigDTO` is injected into:
  - `AdminController`
  - `AuthController`
  - `LoginController`
  - `EmailVerificationController`
  - `PdoAdminQueryReader`
  - `RecoveryStateService`
  - `PdoSessionListReader`

## 4. Endpoint Exposure
- `AdminController`:
  - `create`: Returns `ActionResultResponseDTO` (Safe).
  - `addEmail`: Returns `ActionResultResponseDTO` (Safe).
  - `lookupEmail`: Returns `ActionResultResponseDTO` (Safe).
  - `getEmail`: Returns `AdminEmailResponseDTO` (Safe, returns decrypted email but not config).
- No endpoint was found that returns `AdminConfigDTO` directly or serializes it to JSON.

## Conclusion
`AdminConfigDTO` is currently a "God Object" for configuration, carrying both safe settings and critical secrets. While it is not directly exposed to the API, it is passed around to many services and controllers, increasing the risk of accidental exposure or misuse.
