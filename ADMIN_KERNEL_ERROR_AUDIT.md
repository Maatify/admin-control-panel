# AdminKernel Error Unification Audit

**Date:** 2024-10-24
**Scope:** AdminKernel (`app/Modules/AdminKernel`)
**Mode:** Audit Only (No Code Changes)

## 1. Executive Summary

AdminKernel error handling is currently **hybrid/duplicated**. While a unified `MaatifyException` handler exists in `http.php`, multiple layers of legacy handling persist:
1.  **Duplicate Explicit Handlers:** 5 specific `MaatifyException` subclasses have explicit handlers in `http.php` that override the generic handler.
2.  **Middleware Bypass:** Authentication and Authorization middlewares manually construct JSON responses, bypassing the unified error format.
3.  **Controller Bypass:** `AuthController` catches exceptions and returns legacy JSON structures.
4.  **500 Leaks:** Several domain exceptions extend standard PHP SPL exceptions (`DomainException`, `LogicException`) instead of `MaatifyException`, causing them to fall through to the catch-all `Throwable` handler and result in HTTP 500 (System Error).

## 2. Exception Catch Matrix

| Exception FQCN | Usage (Top Call Sites) | Catch Handler (Effective) | Status Source | Risk/Notes |
| :--- | :--- | :--- | :--- | :--- |
| `EntityAlreadyExistsException` | `PdoRoleRepository.php` | `http.php` Explicit Handler | Hardcoded (409) | **Duplicate**: Also covered by `MaatifyException` handler. |
| `EntityInUseException` | `PdoRoleRepository.php` | `http.php` Explicit Handler | Hardcoded (409) | **Duplicate**: Also covered by `MaatifyException` handler. |
| `EntityNotFoundException` | `PdoRoleRepository.php`, `PdoI18n...` | `http.php` Explicit Handler | Hardcoded (404) | **Duplicate**: Also covered by `MaatifyException` handler. |
| `InvalidOperationException` | `PdoRoleRepository.php` | `http.php` Explicit Handler | Hardcoded (409) | **Duplicate**: Also covered by `MaatifyException` handler. |
| `PermissionDeniedException` | `RoleAssignmentService.php` | `http.php` Explicit Handler | Hardcoded (403) | **Duplicate**: Also covered by `MaatifyException` handler. |
| `InvalidCredentialsException` | `AdminAuthenticationService.php` | `AuthController.php` (Manual Catch) | Manual JSON (401) | **Bypass**: Controller intercepts and returns legacy JSON. |
| `AuthStateException` | `AdminAuthenticationService.php` | `AuthController.php` (Manual Catch) | Manual JSON (403) | **Bypass**: Controller intercepts and returns legacy JSON. |
| `MustChangePasswordException` | `AdminAuthenticationService.php` | `http.php` Catch-All `Throwable` | System Error (500) | **Leak**: Extends `DomainException`. Unhandled by specific logic. |
| `IdentifierNotFoundException` | `AdminEmailRepository.php` | `http.php` Catch-All `Throwable` | System Error (500) | **Leak**: Extends `LogicException`. |
| `UnauthorizedException` | `AuthorizationGuardMiddleware.php` | `http.php` Catch-All `Throwable` | System Error (500) | **Leak**: Extends `DomainException`. Not `HttpUnauthorizedException`. |
| `InvalidIdentifierFormatException` | `VerifyAdminEmailRequestDTO.php` | `http.php` Catch-All `Throwable` | System Error (500) | **Leak**: Extends `LogicException`. |
| `InvalidIdentifierStateException` | `AdminEmailVerificationService.php` | `http.php` Catch-All `Throwable` | System Error (500) | **Leak**: Extends `LogicException`. |
| `RecoveryLockException` | `RecoveryStateService.php` | `http.php` `MaatifyException` Handler | Unified (Exception) | **Unified**: Correctly handled by generic handler. |
| `TwoFactorEnrollmentFailedException` | `TwoFactorEnrollmentService.php` | `http.php` `MaatifyException` Handler | Unified (Exception) | **Unified**: Correctly handled by generic handler. |
| `UnsupportedNotificationChannelException` | `NotificationService.php` (implied) | `http.php` `MaatifyException` Handler | Unified (Exception) | **Unified**: Correctly handled by generic handler. |
| `TwoFactorAlreadyEnrolledException` | `TwoFactorEnrollmentService.php` | `http.php` `MaatifyException` Handler | Unified (Exception) | **Unified**: Correctly handled by generic handler. |
| `InvalidSessionException` | `SessionValidationService.php` | `http.php` `MaatifyException` Handler | Unified (Exception) | **Unified**: Correctly handled by generic handler. |
| `ExpiredSessionException` | `SessionValidationService.php` | `http.php` `MaatifyException` Handler | Unified (Exception) | **Unified**: Correctly handled by generic handler. |
| `RevokedSessionException` | `SessionValidationService.php` | `http.php` `MaatifyException` Handler | Unified (Exception) | **Unified**: Correctly handled by generic handler. |

## 3. Duplicate Coverage List

The following exceptions are handled by **both** an explicit handler in `http.php` and the generic `MaatifyException` handler (if the explicit one were removed). The explicit handlers hardcode the status and category, potentially ignoring the exception's internal configuration (`ApiAware`).

1.  `EntityAlreadyExistsException` (Explicit vs. `ConflictMaatifyException`)
2.  `EntityInUseException` (Explicit vs. `ConflictMaatifyException`)
3.  `EntityNotFoundException` (Explicit vs. `NotFoundMaatifyException`)
4.  `InvalidOperationException` (Explicit vs. `UnsupportedMaatifyException`)
5.  `PermissionDeniedException` (Explicit vs. `AuthorizationMaatifyException`)
6.  `DomainNotAllowedException` (Explicit vs. `BusinessRuleMaatifyException` - from `Maatify\I18n`)

## 4. Manual Construction Findings

### STEP_UP_REQUIRED
**Status:** Manually Constructed (Legacy Format).
**Location:**
*   `app/Modules/AdminKernel/Http/Middleware/SessionStateGuardMiddleware.php`: Returns `{ code: 'STEP_UP_REQUIRED', scope: 'login' }` (403).
*   `app/Modules/AdminKernel/Http/Middleware/ScopeGuardMiddleware.php`: Returns `{ code: 'STEP_UP_REQUIRED', scope: ... }` (403).
**Impact:** Bypasses unified error structure (`{ success: false, error: ... }`).

### AuthController Bypass
**Status:** Manually Constructed (Legacy Format).
**Location:** `app/Modules/AdminKernel/Http/Controllers/AuthController.php`
**Impact:**
*   `InvalidCredentialsException`: Returns `{ error: 'Invalid credentials' }` (401).
*   `AuthStateException`: Returns `{ error: $message }` (403).

## 5. Minimal Closure Recommendations

To achieve a single-path error handling strategy for AdminKernel without changing JS/UI contracts:

1.  **Refactor SPL Exceptions:** Change `MustChangePasswordException`, `IdentifierNotFoundException`, `UnauthorizedException` (AdminKernel Domain), `InvalidIdentifierFormatException`, and `InvalidIdentifierStateException` to extend `MaatifyException` (or appropriate base classes like `ValidationMaatifyException` or `NotFoundMaatifyException`). This prevents 500 leaks.
2.  **Remove Explicit Handlers:** Remove the 6 duplicate explicit handlers in `http.php` (`EntityAlreadyExists`, `EntityInUse`, `EntityNotFound`, `InvalidOperation`, `PermissionDenied`, `DomainNotAllowed`). Rely on the `MaatifyException` handler which uses the exception's internal `getHttpStatus()` and `getErrorCode()`. **Verification Required:** Ensure the exception classes define the same status/code as the removed handlers.
3.  **Standardize Middleware Responses:** Update `SessionStateGuardMiddleware` and `ScopeGuardMiddleware` to throw a `StepUpRequiredException` (new or existing) instead of manually constructing the response. This exception should implement `ApiAwareExceptionInterface` and carry the `STEP_UP_REQUIRED` code. Alternatively, update the manual construction to match the unified schema if throwing is not preferred (though throwing is cleaner).
4.  **Remove Controller Try/Catch:** Remove the manual try/catch blocks in `AuthController.php` for `InvalidCredentialsException` and `AuthStateException`. Let them bubble up to the `MaatifyException` handler in `http.php`. **Risk:** The frontend might expect `{ error: '...' }` (simple) vs `{ success: false, error: { message: '...' } }`. If the frontend expects the simple format, this requires a JS update (out of scope). If so, keep as is but mark as "Intentional Legacy Bypass".
