# AdminKernel Error Unification Closure Report

**Date:** 2024-10-24
**HEAD Commit:** 7503eda6938cf11bd206f54404853fdf96477f3c
**Mode:** STRICT Closure

## 1. Fixed 500 Leaks (AdminKernel Domain)

The following exceptions were previously extending SPL `DomainException` or `LogicException`, causing them to bypass the `MaatifyException` handler and result in a generic `HTTP 500 Internal Server Error`. They have been refactored to extend `AdminKernel` base exceptions, ensuring they are handled as `MaatifyException` with correct status codes.

| Exception FQCN | Previous Behavior | New Behavior (Unified) | Base Class |
| :--- | :--- | :--- | :--- |
| `MustChangePasswordException` | 500 Internal Error | **403 Forbidden** (Authorization) | `AdminKernelAuthorizationExceptionBase` |
| `IdentifierNotFoundException` | 500 Internal Error | **404 Not Found** | `AdminKernelNotFoundExceptionBase` |
| `UnauthorizedException` | 500 Internal Error | **403 Forbidden** (Authorization) | `AdminKernelAuthorizationExceptionBase` |
| `InvalidIdentifierFormatException` | 500 Internal Error | **422 Validation Failed** | `AdminKernelValidationExceptionBase` |
| `InvalidIdentifierStateException` | 500 Internal Error | **409 Conflict** (Invalid Op) | `AdminKernelUnsupportedExceptionBase` |

## 2. Removed Duplicate Handlers (http.php)

The following explicit handlers were removed from `app/Modules/AdminKernel/Bootstrap/http.php` as they were redundant. The generic `MaatifyException` handler now manages these exceptions using their internal configuration.

| Exception | Status | Category | Verification of Preservation |
| :--- | :--- | :--- | :--- |
| `EntityAlreadyExistsException` | 409 | CONFLICT | Extends `AdminKernelConflictExceptionBase`. Defaults to `ENTITY_ALREADY_EXISTS` (409). |
| `EntityInUseException` | 409 | CONFLICT | Updated constructor to explicit `ENTITY_IN_USE` override. Extends `AdminKernelConflictExceptionBase` (409). |
| `EntityNotFoundException` | 404 | NOT_FOUND | Extends `AdminKernelNotFoundExceptionBase`. Defaults to `NOT_FOUND` (404). |
| `InvalidOperationException` | 409 | UNSUPPORTED | Extends `AdminKernelUnsupportedExceptionBase`. Defaults to `INVALID_OPERATION` (409). |
| `PermissionDeniedException` | 403 | AUTHORIZATION | Extends `AdminKernelAuthorizationExceptionBase`. Defaults to `PERMISSION_DENIED` (403). |
| `DomainNotAllowedException` | 422 | BUSINESS_RULE | Extends `BusinessRuleMaatifyException`. Defaults to `DOMAIN_NOT_ALLOWED` (422). |

**Note on `EntityInUseException`:** The constructor was updated to explicitly pass `AdminKernelErrorCodeEnum::ENTITY_IN_USE` to the parent, ensuring the error code matches the previous explicit handler behavior (which hardcoded `ENTITY_IN_USE`).

## 3. Constraints Verification

*   **STEP_UP_REQUIRED:** The middleware logic in `SessionStateGuardMiddleware.php` and `ScopeGuardMiddleware.php` was **NOT** touched. The response format remains manually constructed JSON as per strict instructions.
*   **AuthController:** The manual catch blocks for `InvalidCredentialsException` and `AuthStateException` were **NOT** touched, preserving the legacy API contract for the frontend login flow.
*   **JS/UI:** No changes were made to `public/` assets or templates.

## 4. Conclusion

AdminKernel error handling is now significantly more unified. Domain exceptions no longer leak as 500 errors, and the `http.php` handler is cleaner with fewer explicit overrides, relying on the robust `MaatifyException` inheritance structure.
