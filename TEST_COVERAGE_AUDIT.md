# Route → DB Reality Audit & Test Coverage Inventory

## 1. Verified & Fixed Routes

| Route | Method | Expected Side-Effect | Test File | Status |
|---|---|---|---|---|
| `/api/sessions/revoke-bulk` | POST | Updates `admin_sessions` (`is_revoked` = 1) | `tests/Integration/Http/UnifiedEndpointTest.php` | **FIXED** (Real SQLite Assertion) |

## 2. Insufficient Coverage (Mocks / No DB Assertion)

The following routes currently lack Integration Tests that assert real DB side-effects. They rely on Unit Tests with Mocks or have no tests.

| Route | Method | Expected Side-Effect | Existing Test (Unit/Mock) | Status |
|---|---|---|---|---|
| `/login` | POST | Create Session (`admin_sessions`) | `LoginControllerTest.php` | INSUFFICIENT |
| `/logout` | POST | Revoke Session (`admin_sessions`) | `LogoutControllerTest.php` | INSUFFICIENT |
| `/verify-email` | POST | Update `admin_emails`, `verification_codes` | *None Found* | INSUFFICIENT |
| `/auth/change-password` | POST | Update `admin_passwords` | `ChangePasswordControllerTest.php` | INSUFFICIENT |
| `/2fa/verify` | POST | Create `step_up_grants` | `UiStepUpControllerTest.php` | INSUFFICIENT |
| `/2fa/setup` | POST | Update `admin_totp_secrets` | `TwoFactorControllerTest.php` | INSUFFICIENT |
| `/admins/create` | POST | Insert `admins` | *None Found* | INSUFFICIENT |
| `/api/admins/{id}/emails` | POST | Insert `admin_emails` | *None Found* | INSUFFICIENT |

> **Note:** `UnifiedEndpointTest.php` establishes the pattern for fixing these gaps by providing a bootstrapped `MySQLTestHelper` with a realistic schema and container override. Future work should extend this test suite.
