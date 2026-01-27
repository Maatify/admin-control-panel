# Test Reality Audit Report

## 1. Tests Referencing Deleted Interfaces (INVALID)
**Status:** INVALID
**Violations:**
- **Codebase Reality:** The interface `App\Domain\Contracts\SecurityEventLoggerInterface` has been deleted (replaced by `SecurityEventRecorderInterface` or new architecture).
- **Test Reality:** These tests explicitly import and mock this deleted interface, making them impossible to run (compilation/autoload errors).

**Affected Files:**
- `tests/Http/Controllers/Web/LogoutControllerTest.php`
- `tests/Http/Controllers/Web/ChangePasswordControllerTest.php`
- `tests/Integration/Flow/ForcedPasswordChangeFlowTest.php`

---

## 2. Controller Unit Tests Mocking Authorization (INVALID)
**Status:** INVALID
**Violations:**
- **Canonical Rule I.2:** "Endpoint / Integration Tests are the authoritative verification mechanism... Calling services or repositories directly is FORBIDDEN."
- **Canonical Rule I.6:** "The following are STRICTLY FORBIDDEN in tests: Mocking authorization decisions... Mocking security event loggers."
- **Test Reality:** These are "Unit Tests" for Controllers that instantiate the Controller directly and inject Mocks for Authentication, Authorization, and Security services. This bypasses the actual security stack (Middleware, Guards) and verifies only that "the service method was called", which provides no security value in the current architecture.

**Affected Files:**
- `tests/Http/Controllers/Web/LoginControllerTest.php` (Mocks `AdminAuthenticationService`)
- `tests/Http/Controllers/AuthControllerTest.php` (Mocks `AdminAuthenticationService`)
- `tests/Http/Controllers/Api/SessionBulkRevokeControllerTest.php` (Mocks `AuthorizationService`)
- `tests/Http/Controllers/Api/SessionRevokeControllerTest.php` (Mocks `AuthorizationService`)
- `tests/Http/Controllers/StepUpControllerTest.php` (Mocks `StepUpService`)
- `tests/Http/Controllers/Web/TwoFactorControllerTest.php` (Mocks `StepUpService`, Mocks Telemetry)

---

## 3. Repository Integration Tests (PARTIALLY_VALID)
**Status:** PARTIALLY_VALID
**Violations:**
- **Canonical Rule I.3:** "Tests MUST NOT rely on mocks or fakes for Database access."
- **Test Reality:** `AdminPasswordRepositoryTest.php` uses a mix of Real SQLite (good) and Mock PDO (bad).
  - The Mock PDO is used to assert MySQL-specific SQL syntax (`ON DUPLICATE KEY UPDATE`) which SQLite does not support. This is a pragmatic necessity but technically violates the "No Mocks" rule.
  - The test also defines its own minimal table schema (`CREATE TABLE IF NOT EXISTS...`) inside `setUp()`, ignoring the Canonical `database/schema.sql`. This creates a risk where the test passes against a fake schema that doesn't match production.

**Affected Files:**
- `tests/Integration/Infrastructure/Repository/AdminPasswordRepositoryTest.php`

---

## 4. Valid Canonical Tests (STILL_VALID)
**Status:** STILL_VALID
**Verification:**
- **Integration:** `UnifiedEndpointTest` correctly sets up the container, uses `MySQLTestHelper` (SQLite), and executes full request flows without mocks.
- **Canonical Contracts:** `AdminsQueryContractTest` and `PdoSessionListReaderTest` correctly verify the strict `POST /api/{resource}/query` contract, Schema validation, and SQL generation logic. Mocks here are limited to `PDO` for SQL string assertion (Unit Testing the Reader), which is acceptable for "Contract" tests as long as "Integration" tests exist.
- **Modules & Services:** Unit tests for `Modules/Crypto`, `Modules/Validation`, and `Domain/Service` correctly test isolated business logic without violating architectural boundaries.

**Affected Files:**
- `tests/Integration/UnifiedEndpointTest.php`
- `tests/Integration/Context/HttpContextProviderRegressionTest.php`
- `tests/Canonical/Admins/AdminsQueryContractTest.php`
- `tests/Canonical/Sessions/PdoSessionListReaderTest.php`
- `tests/Canonical/Sessions/ListQueryDTOTest.php`
- `tests/Canonical/Sessions/ListFilterResolverTest.php`
- `tests/Canonical/Sessions/SharedListQuerySchemaTest.php`
- `tests/Domain/Service/StepUpServiceTest.php`
- `tests/Unit/PasswordServiceTest.php`
- `tests/Unit/SessionHashingTest.php`
- `tests/Modules/Crypto/*`
- `tests/Modules/Validation/*`
