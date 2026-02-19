# AdminKernel Exception Baseline Audit

This document serves as a strict, read-only audit of the current exception handling architecture within `AdminKernel`. It captures the state of exception classes, inheritance, HTTP mapping, and manual JSON construction as of the baseline commit.

## 1. Exception Inventory

The following 19 exception classes are defined in `app/Modules/AdminKernel/Domain/Exception`:

| Exception Class | Parent Class | Namespace |
| :--- | :--- | :--- |
| `AuthStateException` | `DomainException` | `Maatify\AdminKernel\Domain\Exception` |
| `EntityAlreadyExistsException` | `RuntimeException` | `Maatify\AdminKernel\Domain\Exception` |
| `EntityInUseException` | `RuntimeException` | `Maatify\AdminKernel\Domain\Exception` |
| `EntityNotFoundException` | `RuntimeException` | `Maatify\AdminKernel\Domain\Exception` |
| `ExpiredSessionException` | `Exception` | `Maatify\AdminKernel\Domain\Exception` |
| `IdentifierNotFoundException` | `LogicException` | `Maatify\AdminKernel\Domain\Exception` |
| `InvalidCredentialsException` | `DomainException` | `Maatify\AdminKernel\Domain\Exception` |
| `InvalidIdentifierFormatException` | `LogicException` | `Maatify\AdminKernel\Domain\Exception` |
| `InvalidIdentifierStateException` | `LogicException` | `Maatify\AdminKernel\Domain\Exception` |
| `InvalidOperationException` | `RuntimeException` | `Maatify\AdminKernel\Domain\Exception` |
| `InvalidSessionException` | `Exception` | `Maatify\AdminKernel\Domain\Exception` |
| `MustChangePasswordException` | `DomainException` | `Maatify\AdminKernel\Domain\Exception` |
| `PermissionDeniedException` | `DomainException` | `Maatify\AdminKernel\Domain\Exception` |
| `RecoveryLockException` | `RuntimeException` | `Maatify\AdminKernel\Domain\Exception` |
| `RevokedSessionException` | `Exception` | `Maatify\AdminKernel\Domain\Exception` |
| `TwoFactorAlreadyEnrolledException` | `RuntimeException` | `Maatify\AdminKernel\Domain\Exception` |
| `TwoFactorEnrollmentFailedException` | `RuntimeException` | `Maatify\AdminKernel\Domain\Exception` |
| `UnauthorizedException` | `DomainException` | `Maatify\AdminKernel\Domain\Exception` |
| `UnsupportedNotificationChannelException` | `DomainException` | `Maatify\AdminKernel\Domain\Exception` |

**Note:** None of these exceptions extend `MaatifyException` or implement `ApiAwareExceptionInterface`.

## 2. http.php Mapping Analysis

The `app/Modules/AdminKernel/Bootstrap/http.php` file defines explicit mappings for exceptions to HTTP responses.

| Exception Class | HTTP Status | Error Code String | Category |
| :--- | :--- | :--- | :--- |
| `ValidationFailedException` | 422 | `INVALID_ARGUMENT` | `VALIDATION` |
| `HttpBadRequestException` | 400 | `BAD_REQUEST` | `VALIDATION` |
| `PermissionDeniedException` | 403 | `PERMISSION_DENIED` | `AUTHORIZATION` |
| `HttpUnauthorizedException` | 401 | `UNAUTHORIZED` | `AUTHENTICATION` |
| `HttpForbiddenException` | 403 | `FORBIDDEN` | `AUTHORIZATION` |
| `HttpNotFoundException` | 404 | `RESOURCE_NOT_FOUND` | `NOT_FOUND` |
| `EntityAlreadyExistsException` | 409 | `ENTITY_ALREADY_EXISTS` | `CONFLICT` |
| `EntityInUseException` | 409 | `ENTITY_IN_USE` | `CONFLICT` |
| `EntityNotFoundException` | 404 | `NOT_FOUND` | `NOT_FOUND` |
| `InvalidOperationException` | 409 | `INVALID_OPERATION` | `UNSUPPORTED` |
| `DomainNotAllowedException` | 422 | `DOMAIN_NOT_ALLOWED` | `BUSINESS_RULE` |
| `MaatifyException` | (Variable) | (Dynamic `getValue()`) | (Dynamic `getValue()`) |
| `HttpMethodNotAllowedException` | 405 | `METHOD_NOT_ALLOWED` | `UNSUPPORTED` |

**Fallback Handler:**
*   Catches `Throwable` (Catch-All)
*   HTTP Status: 500
*   Error Code: `INTERNAL_ERROR`
*   Category: `SYSTEM`

## 3. Middleware Manual JSON Construction

The following files construct JSON responses manually, bypassing the unified error handler in `http.php`.

| File | Context | Response Shape (JSON) | HTTP Status |
| :--- | :--- | :--- | :--- |
| `SessionStateGuardMiddleware.php` | API Step-Up Check | `{"code": "STEP_UP_REQUIRED", "scope": "login"}` | 403 |
| `SessionStateGuardMiddleware.php` | Auth Missing (AdminContext) | `{"error": "Authentication required"}` | 401 |
| `SessionStateGuardMiddleware.php` | Session Missing | `{"error": "Session required"}` | 401 |
| `SessionGuardMiddleware.php` | API Failure (Invalid/Missing Token) | `{"error": "message"}` (Dynamic) | 401 |
| `SessionGuardMiddleware.php` | API Remember-Me Failure | `{"error": "Invalid session"}` | 401 |

**Note:** `AuthController` also manually constructs JSON error responses for `InvalidCredentialsException` and `AuthStateException`.

## 4. Unified Envelope Conformance Check

*   **Unified Envelope:** Defined in `http.php` as `['success' => false, 'error' => ['code' => ..., 'category' => ..., 'message' => ..., 'meta' => ..., 'retryable' => ...]]`.
*   **Conformance:**
    *   `http.php` handlers strictly conform to the unified envelope.
    *   `MaatifyException` instances conform.
*   **Violations:**
    *   `SessionStateGuardMiddleware` and `SessionGuardMiddleware` return raw JSON objects (e.g., `{"error": "..."}` or `{"code": ...}`), violating the unified envelope structure.
    *   AdminKernel domain exceptions do not extend `MaatifyException`, so they rely on external mapping in `http.php` to achieve conformance. Unmapped exceptions (e.g., `InvalidCredentialsException`) fall back to 500 `INTERNAL_ERROR` unless caught manually in controllers/middleware.
