# AdminKernel Error Surface Sweep

**Date:** 2024-10-24
**HEAD Commit:** 7503eda6938cf11bd206f54404853fdf96477f3c
**Mode:** STRICT VERIFICATION
**Scope:** `app/Modules/AdminKernel`

## SECTION 1 — THROW SWEEP

Exceptions thrown within `AdminKernel` (excluding `http.php` handler).

| File | Exception Class | Extends MaatifyException? | HTTP Outcome |
| :--- | :--- | :--- | :--- |
| `RememberMeService.php` | `InvalidCredentialsException` | Yes | 401 |
| `RememberMeService.php` | `RuntimeException` | No | **500** (System Error) |
| `AdminEmailVerificationService.php` | `InvalidIdentifierStateException` | Yes | 409 |
| `AdminAuthenticationService.php` | `InvalidCredentialsException` | Yes | 401 |
| `AdminAuthenticationService.php` | `AuthStateException` | Yes | 403 |
| `AdminAuthenticationService.php` | `MustChangePasswordException` | Yes | 403 |
| `SessionValidationService.php` | `InvalidSessionException` | Yes | 401 |
| `SessionValidationService.php` | `RevokedSessionException` | Yes | 401 |
| `SessionValidationService.php` | `ExpiredSessionException` | Yes | 401 |
| `RoleAssignmentService.php` | `PermissionDeniedException` | Yes | 403 |
| **`SessionRevocationService.php`** | **`DomainException`** | **No** | **500** (Leak) |
| `AuthorizationService.php` | `PermissionDeniedException` | Yes | 403 |
| `AuthorizationService.php` | `UnauthorizedException` | Yes | 403 |
| `RecoveryStateService.php` | `RecoveryLockException` | Yes | 403 |
| `RecoveryStateService.php` | `RuntimeException` | No | **500** (System Error) |
| `TwoFactorEnrollmentService.php` | `TwoFactor*Exception` | Yes | 422/409 |
| `Actor.php` | `InvalidArgumentException` | No | **500** (System Error) |
| `TotpEnrollmentConfig.php` | `RuntimeException` | No | **500** (System Error) |
| `*RequestDTO.php` | `InvalidIdentifierFormatException` | Yes | 422 |
| `I18nScopeDomainsService.php` | `InvalidOperationException` | Yes | 409 |
| `I18nScopeDomainsService.php` | `EntityNotFoundException` | Yes | 404 |
| `I18nScopeKeyCommandService.php` | `TranslationKeyNotFoundException` | Yes | 404 |
| `*Config.php` | `Exception`/`RuntimeException` | No | **500** (Config Error) |
| `Container.php` | `Exception`/`RuntimeException` | No | **500** (Config Error) |

## SECTION 2 — MANUAL RESPONSE SWEEP

Occurrences of manual response construction bypassing the unified error handler.

| File | Context | Status | Payload Shape |
| :--- | :--- | :--- | :--- |
| `SessionStateGuardMiddleware.php` | Step-Up Required | 403 | `{ "code": "STEP_UP_REQUIRED", "scope": "..." }` |
| `GuestGuardMiddleware.php` | Already Authenticated | 200/Redirect | `{ "error": "Already authenticated." }` |
| `SessionGuardMiddleware.php` | Invalid/No Session | 401 | `{ "error": "Session required" }` |
| `ScopeGuardMiddleware.php` | Auth/Scope Missing | 401/403 | `{ "error": "Authentication required" }` / `{ "code": "STEP_UP_REQUIRED" }` |
| `AuthController.php` | Login Success/Fail | 200/401/403 | `{ "token": ... }` / `{ "error": "..." }` |
| `StepUpController.php` | Step-Up Check | 200 | `{ "status": "granted", ... }` |
| `TwoFactorController.php` | Auth Check | 401 | "Unauthorized" (String) |
| `SessionBulkRevokeController.php` | Validation Error | 400 | `{ "error": "Current session not found" }` |
| `HealthRoutes.php` | Health Check | 200 | `{ "status": "ok" }` |

## SECTION 3 — SPL THROW DETECTION

The following SPL exceptions are thrown outside the MaatifyException hierarchy:

1.  **`DomainException` in `SessionRevocationService.php`**:
    *   `throw new DomainException('Cannot revoke own session via bulk operation.');`
    *   `throw new DomainException('Cannot revoke own session via global view.');`
    *   **Impact:** Unintended 500 error when a user attempts self-revocation via these paths.

2.  **`RuntimeException` in `Languages*Controller.php`**:
    *   "Invalid validated payload."
    *   **Impact:** 500 error. Indicates a disconnect between Validation Schema and Controller expectations (Server Error).

3.  **`RuntimeException` / `Exception` in Config/Bootstrap**:
    *   `Container.php`, `*Config.php`.
    *   **Impact:** 500 error. Correct behavior for system misconfiguration.

## SECTION 4 — FINAL CLASSIFICATION

The AdminKernel error surface consists of four distinct paths:

**A) Unified Path (Primary)**
*   Handled by `MaatifyException` handler in `http.php`.
*   Includes all Domain Exceptions refactored in commit `7503eda` (e.g., `MustChangePassword`, `EntityNotFound`).
*   Result: Unified JSON `{ "success": false, "error": { ... } }`.

**B) Manual/Legacy Path (Auth & Middleware)**
*   Handled explicitly in Middleware (`SessionStateGuard`, `ScopeGuard`) and Controllers (`AuthController`).
*   Result: Legacy JSON `{ "error": "..." }` or `{ "code": "STEP_UP_REQUIRED" }`.
*   **Status:** Intentional bypass preserved for backward compatibility.

**C) System Error Path (Config/Logic)**
*   Handled by `Throwable` catch-all in `http.php`.
*   Includes `Container` config errors, `RuntimeException` in factories.
*   Result: Unified JSON (HTTP 500) `{ "success": false, "error": { "code": "INTERNAL_ERROR", ... } }`.

**D) Unintended Leaks (Residual)**
*   **`SessionRevocationService.php`**: Throws `DomainException` for business rules.
    *   **Verdict:** This is a **confirmed leak**. It results in a 500 System Error instead of a 409/403 Business Rule error.
*   **`SessionBulkRevokeController.php`**: Manually constructs 400 JSON response for "Current session not found".
    *   **Verdict:** Inconsistent. Should likely throw `EntityNotFoundException`.

## FINAL VERDICT
The AdminKernel error layer is **mostly deterministic**, with the following exceptions:
1.  **Leak:** `SessionRevocationService` throws raw `DomainException` (500).
2.  **Inconsistency:** `SessionBulkRevokeController` manually handles errors.
3.  **Legacy:** Auth/Middleware paths intentionally bypass the unified structure.

The "500 leaks" addressed in the previous commit (`7503eda`) are verified fixed. The `SessionRevocationService` leak was identified in this deeper sweep.
