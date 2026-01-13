# Bug Report

## 1. Missing Class `LoginSchema` in Tests
The following test files reference `App\Modules\Validation\Schemas\LoginSchema`, which does not exist in the codebase:
- `tests/Modules/Validation/Schemas/LoginSchemaTest.php`
- `tests/Modules/Validation/Validator/RespectValidatorTest.php`

The class seems to have been replaced by `App\Modules\Validation\Schemas\AuthLoginSchema`, but the tests were not updated.

## 2. Outdated Test Logic
`tests/Modules/Validation/Schemas/LoginSchemaTest.php` contains a test case `testInvalidInput` that expects a simple password "123" to fail validation.
However, the replacement schema `AuthLoginSchema` only enforces transport safety (sanitization) and does not enforce password complexity (policy). Therefore, "123" is considered valid by `AuthLoginSchema`, causing the test to fail if simply updated to use the new class without adjusting the test data.

## 3. Environment Configuration
Integration tests fail due to missing `.env` configuration and missing database connection.
- `tests/Integration/ActivityLog/MySQLActivityLogWriterTest.php`
- `tests/Integration/ActivityLog/PdoActivityLogListReaderTest.php`
- `tests/Integration/Http/Api/ActivityLogQueryControllerTest.php`

These require a configured `.env` file (based on `.env.example`) and a running MySQL instance with the `admin_control_panel` database.
