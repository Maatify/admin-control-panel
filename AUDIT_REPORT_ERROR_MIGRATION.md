# 🧠 JULES EXECUTOR — ERROR MIGRATION READINESS AUDIT

**MODE**: STRICT READ-ONLY ARCHITECTURAL AUDIT
**SCOPE**: FULL REPOSITORY (ALL MODULES)
**OBJECTIVE**: Verify 100% readiness for Error Migration Roadmap.
**DATE**: 2026-03-22 (Based on Runtime Code)

---

## PART 1 — EXCEPTION LAYER COMPATIBILITY CHECK

**1. Base Components Verification:**
*   `MaatifyException`: **FOUND** (`Modules/Exceptions/Exception/MaatifyException.php`)
*   `ApiAwareExceptionInterface`: **FOUND** (`Modules/Exceptions/Contracts/ApiAwareExceptionInterface.php`)
*   `ErrorCode Enum`: **FOUND** (`Modules/Exceptions/Enum/ErrorCodeEnum.php`)
*   `ErrorCategory Enum`: **FOUND** (`Modules/Exceptions/Enum/ErrorCategoryEnum.php`)

**2. Exception Hierarchy Analysis (Sample of Key Findings):**

| EXCEPTION CLASS | EXTENDS | HAS ERROR CODE | HAS CATEGORY | HAS HTTP STATUS | MIGRATION SAFE? | REASON |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `MaatifyException` | `RuntimeException` | YES | YES | YES | **YES** | Base Class |
| `ValidationFailedException` | `RuntimeException` | NO | NO | NO | **NO** | Does not extend MaatifyException; Missing API contract. |
| `I18nException` | `RuntimeException` | NO | NO | NO | **NO** | Root of I18n module exceptions is not MaatifyException. |
| `LanguageCoreException` | `MaatifyException` | YES | YES | YES | **YES** | Correctly extends MaatifyException. |
| `AppSettingException` | `MaatifyException` | YES | YES | YES | **YES** | Correctly extends MaatifyException. |
| `ContentDocumentsException` | `MaatifyException` | YES | YES | YES | **YES** | Correctly extends MaatifyException. |
| `EntityNotFoundException` | `RuntimeException` | NO | NO | NO | **NO** | Core kernel exception does not extend MaatifyException. |
| `AuditTrailStorageException` | `RuntimeException` | NO | NO | NO | **NO** | Infrastructure exception not compliant. |
| `EmailQueueWriteException` | `RuntimeException` | NO | NO | NO | **NO** | Infrastructure exception not compliant. |
| `CryptoAlgorithmNotSupportedException` | `RuntimeException` | NO | NO | NO | **NO** | Crypto module exception not compliant. |

**3. ValidationFailedException Check:**
*   **Extends MaatifyException?**: **NO** (`extends RuntimeException`)
*   **Impact**: **CRITICAL**. It has a divergent schema (`errors` array vs `error` object) and is handled separately in `Bootstrap/http.php`. Migration would break validation error parsing on frontend.

---

## PART 2 — ENDPOINT MANUAL RESPONSE DETECTION

| FILE | LINE | SNIPPET | TYPE | CAN REPLACE? | REASON |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `SessionBulkRevokeController.php` | 55 | `json_encode(['error' => ...])` | ERROR_MANUAL | **YES** | Standard error response. |
| `SessionBulkRevokeController.php` | 57 | `->withStatus(401)` | STATUS_MANUAL | **YES** | Can be `UnauthorizedMaatifyException`. |
| `SessionBulkRevokeController.php` | 74 | `->withStatus(400)` | STATUS_MANUAL | **YES** | Can be `InvalidArgumentMaatifyException`. |
| `SessionRevokeController.php` | 57 | `json_encode(['error' => ...])` | ERROR_MANUAL | **YES** | Standard error response. |
| `SessionRevokeController.php` | 59 | `->withStatus(401)` | STATUS_MANUAL | **YES** | Can be `UnauthorizedMaatifyException`. |
| `SessionRevokeController.php` | 88 | `->withStatus(404)` | STATUS_MANUAL | **YES** | Can be `NotFoundMaatifyException`. |
| `Permissions/PermissionAdminsQueryController.php` | 79 | `->withStatus(200)` | SUCCESS_MANUAL | **NO** | Success response (OK). |
| `Bootstrap/http.php` | 27 | `json_encode(['message' => ..., 'code' => ...])` | ERROR_MANUAL | **YES** | Legacy HTTP Error Handler. |

---

## PART 3 — ERROR SCHEMA DIVERGENCE MAP

| SCHEMA TYPE | STRUCTURE | OCCURRENCES | LOCATION |
| :--- | :--- | :--- | :--- |
| **LEGACY_HTTP_ERROR** | `{ "message": "...", "code": "..." }` | 10+ (Handler) | `Bootstrap/http.php` ($httpJsonError closure) |
| **VALIDATION_SCHEMA** | `{ "error": "...", "errors": { ... } }` | 1 (Handler) | `Bootstrap/http.php` (ValidationFailedException) |
| **MANUAL_CONTROLLER_SCHEMA** | `{ "error": "..." }` | ~10 (Controllers) | `Sessions` Module Controllers |
| **MAATIFY_EXCEPTION_SCHEMA** | `{ "success": false, "error": { "code": ..., "category": ..., ... } }` | 1 (Handler) | `Bootstrap/http.php` (MaatifyException) |

---

## PART 4 — BREAKING SURFACE ANALYSIS

**Migration Impact (Unification to MaatifyException Schema):**

1.  **Frontend Assumptions**:
    *   Code expecting flat `error` string (from Manual Controllers) **WILL BREAK**.
    *   Code expecting flat `message` and `code` (from Legacy HTTP Handler) **WILL BREAK**.
    *   Code expecting `errors` key at root (from Validation Handler) **WILL BREAK**.

2.  **Endpoints at Risk**:
    *   **Modules/Sessions**: High risk (Manual responses).
    *   **Modules/AdminKernel**: High risk (Core exceptions `EntityNotFound`, `PermissionDenied`, etc. use Legacy Handler).
    *   **Modules/Validation**: High risk (Schema change).

**Module Risk Assessment:**

| MODULE | BREAKING RISK | WHY |
| :--- | :--- | :--- |
| `AdminKernel` | **HIGH** | Core exceptions (404, 403, 409) use legacy schema in `http.php`. |
| `Validation` | **HIGH** | `ValidationFailedException` has unique schema and does not extend base. |
| `Sessions` | **HIGH** | Manual JSON construction in controllers. |
| `I18n` | **MEDIUM** | `I18nException` does not extend base; relies on generic handling. |
| `Crypto` | **LOW** | Exceptions do not extend base, but usage is likely internal/logged. |
| `Audit/Email` | **LOW** | Infrastructure exceptions, likely 500s (handled by catch-all). |

---

## PART 5 — MIGRATION READINESS SCORE

**SCORE: 20%**

**CLASSIFICATION: PHASE 0 REQUIRED**

**Reasoning**:
The codebase is **NOT READY** for migration.
1.  **Schema Fragmentation**: 4 distinct error schemas exist. Unifying them now would cause widespread frontend breakages.
2.  **Inheritance Gaps**: Major kernel modules (`AdminKernel`, `I18n`, `Validation`, `Crypto`) have exception hierarchies that do not extend `MaatifyException`.
3.  **Manual Handling**: Controllers still manually construct JSON error responses instead of throwing exceptions.
4.  **Handler Divergence**: `Bootstrap/http.php` explicitly handles non-Maatify exceptions with legacy schemas, effectively hardcoding the debt.

---

## PART 6 — PRECISE EXECUTION CHECKLIST

To reach **READY FOR PHASE 1**:

1.  **Architectural Refactoring (Exceptions)**:
    *   [ ] Refactor `Maatify\Validation\Exceptions\ValidationFailedException` to extend `MaatifyException`.
    *   [ ] Refactor `Maatify\I18n\Exception\I18nException` to extend `MaatifyException`.
    *   [ ] Refactor `Maatify\AdminKernel\Domain\Exception\*` (EntityNotFound, PermissionDenied, etc.) to extend `MaatifyException`.
    *   [ ] Refactor `Maatify\Crypto\*\Exceptions\*` to extend `MaatifyException`.
    *   [ ] Refactor `Maatify\EmailDelivery\Exception\*` to extend `MaatifyException`.

2.  **Controller Cleanup**:
    *   [ ] `SessionBulkRevokeController.php`: Replace manual 401/400/JSON with `UnauthorizedMaatifyException` / `InvalidArgumentMaatifyException`.
    *   [ ] `SessionRevokeController.php`: Replace manual 401/404/JSON with `UnauthorizedMaatifyException` / `NotFoundMaatifyException`.

3.  **Middleware/Bootstrap Modification**:
    *   [ ] Update `Bootstrap/http.php`: The `$httpJsonError` closure and specific handlers for `Http*Exception` must be updated to output `MaatifyException` schema format (even if they wrap legacy exceptions temporarily).
    *   [ ] Update `Bootstrap/http.php`: The `ValidationFailedException` handler must be updated to map `errors` into the `meta` field of the new schema.

4.  **Blockers**:
    *   **Frontend Compatibility**: Before changing `http.php`, the frontend **MUST** be audited to handle the new `MaatifyException` JSON structure (`{ success: false, error: { ... } }`) instead of flat structures. This is a dependency for any backend change.
