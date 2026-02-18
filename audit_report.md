# AdminKernel Exception Audit

## 1️⃣ FULL EXCEPTION LIST

| Exception | File Path | Extends | Abstract/Concrete |
| :--- | :--- | :--- | :--- |
| `AuthStateException` | `app/Modules/AdminKernel/Domain/Exception/AuthStateException.php` | `DomainException` | Concrete |
| `EntityAlreadyExistsException` | `app/Modules/AdminKernel/Domain/Exception/EntityAlreadyExistsException.php` | `RuntimeException` | Concrete |
| `EntityInUseException` | `app/Modules/AdminKernel/Domain/Exception/EntityInUseException.php` | `RuntimeException` | Concrete |
| `EntityNotFoundException` | `app/Modules/AdminKernel/Domain/Exception/EntityNotFoundException.php` | `RuntimeException` | Concrete |
| `ExpiredSessionException` | `app/Modules/AdminKernel/Domain/Exception/ExpiredSessionException.php` | `Exception` | Concrete |
| `IdentifierNotFoundException` | `app/Modules/AdminKernel/Domain/Exception/IdentifierNotFoundException.php` | `LogicException` | Concrete |
| `InvalidCredentialsException` | `app/Modules/AdminKernel/Domain/Exception/InvalidCredentialsException.php` | `DomainException` | Concrete |
| `InvalidIdentifierFormatException` | `app/Modules/AdminKernel/Domain/Exception/InvalidIdentifierFormatException.php` | `LogicException` | Concrete |
| `InvalidIdentifierStateException` | `app/Modules/AdminKernel/Domain/Exception/InvalidIdentifierStateException.php` | `LogicException` | Concrete |
| `InvalidOperationException` | `app/Modules/AdminKernel/Domain/Exception/InvalidOperationException.php` | `RuntimeException` | Concrete |
| `InvalidSessionException` | `app/Modules/AdminKernel/Domain/Exception/InvalidSessionException.php` | `Exception` | Concrete |
| `MustChangePasswordException` | `app/Modules/AdminKernel/Domain/Exception/MustChangePasswordException.php` | `DomainException` | Concrete |
| `PermissionDeniedException` | `app/Modules/AdminKernel/Domain/Exception/PermissionDeniedException.php` | `DomainException` | Concrete |
| `RecoveryLockException` | `app/Modules/AdminKernel/Domain/Exception/RecoveryLockException.php` | `RuntimeException` | Concrete |
| `RevokedSessionException` | `app/Modules/AdminKernel/Domain/Exception/RevokedSessionException.php` | `Exception` | Concrete |
| `TwoFactorAlreadyEnrolledException` | `app/Modules/AdminKernel/Domain/Exception/TwoFactorAlreadyEnrolledException.php` | `RuntimeException` | Final |
| `TwoFactorEnrollmentFailedException` | `app/Modules/AdminKernel/Domain/Exception/TwoFactorEnrollmentFailedException.php` | `RuntimeException` | Concrete |
| `UnauthorizedException` | `app/Modules/AdminKernel/Domain/Exception/UnauthorizedException.php` | `DomainException` | Concrete |
| `UnsupportedNotificationChannelException` | `app/Modules/AdminKernel/Domain/Exception/UnsupportedNotificationChannelException.php` | `DomainException` | Concrete |

## 2️⃣ INHERITANCE STATUS

*   **Does it extend MaatifyException?**
    *   **NO** (0/19 exceptions).
    *   All exceptions currently extend standard PHP exceptions (`RuntimeException`, `DomainException`, `LogicException`, `Exception`).
*   **Multi-level inheritance?**
    *   No internal hierarchy found (e.g., `ChildException extends ParentException extends DomainException`). All are direct descendants of PHP SPL exceptions.

## 3️⃣ HTTP STATUS ANALYSIS

*   **Explicit Definition:** None.
*   **Default Behavior:**
    *   These exceptions currently rely on global exception handlers (e.g., in `http.php`) to map them to HTTP statuses (e.g., `EntityNotFoundException` -> 404).
    *   Without external mapping, they would default to 500 (Internal Server Error) in a generic handler.

## 4️⃣ ERROR CODE ANALYSIS

*   **Defined in Class:** None.
*   **Status:** All exceptions lack an internal `ErrorCodeEnum` mapping.
*   **Missing:** 100% of exceptions need `ErrorCodeEnum` assignment during migration.

## 5️⃣ CATEGORY ANALYSIS

*   **Defined in Class:** None.
*   **Status:** All exceptions lack an internal `ErrorCategoryEnum` mapping.
*   **Missing:** 100% of exceptions need `ErrorCategoryEnum` assignment.

## 6️⃣ API EXPOSURE RISK

| Exception | API Exposure Risk | Rationale |
| :--- | :--- | :--- |
| `AuthStateException` | **High** | Auth flows (Login/Verify) |
| `EntityAlreadyExistsException` | **High** | CRUD Create/Update |
| `EntityInUseException` | **High** | CRUD Delete/Update |
| `EntityNotFoundException` | **High** | CRUD Read/Update/Delete |
| `ExpiredSessionException` | **High** | Auth Middleware/Token checks |
| `IdentifierNotFoundException` | Medium | Internal logic or specific lookups |
| `InvalidCredentialsException` | **High** | Login flow |
| `InvalidIdentifierFormatException` | Medium | Input validation/Internal logic |
| `InvalidIdentifierStateException` | Medium | Internal logic |
| `InvalidOperationException` | **High** | Business logic constraints |
| `InvalidSessionException` | **High** | Auth Middleware |
| `MustChangePasswordException` | **High** | Login flow (Force Password Change) |
| `PermissionDeniedException` | **High** | Authorization (RBAC) |
| `RecoveryLockException` | **High** | Account Recovery flows |
| `RevokedSessionException` | **High** | Auth Middleware |
| `TwoFactorAlreadyEnrolledException` | **High** | 2FA Enrollment |
| `TwoFactorEnrollmentFailedException` | **High** | 2FA Enrollment |
| `UnauthorizedException` | **High** | Auth Middleware (401) |
| `UnsupportedNotificationChannelException` | Low | Internal/Configuration |

## 7️⃣ CANONICALIZATION READINESS SCORE

*   **Migration Status:** 0% (0/19)
*   **Exceptions to Migrate:** 19
*   **Structural Inconsistencies:**
    *   Mixed inheritance (`Exception` vs `RuntimeException` vs `DomainException` vs `LogicException`).
    *   Inconsistent constructor signatures (some take formatted messages, others build them).
*   **Risk Areas:**
    *   Heavily used in API flows (Auth, CRUD).
    *   Mapping to `ErrorCodeEnum` must be precise to avoid breaking client contracts (e.g., `EntityNotFound` must remain 404).

## 8️⃣ SUMMARY TABLE

| Exception | Extends | Has Code | Has Category | Has Status | API Risk | Needs Migration |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `AuthStateException` | `DomainException` | ❌ | ❌ | ❌ | High | ✅ |
| `EntityAlreadyExistsException` | `RuntimeException` | ❌ | ❌ | ❌ | High | ✅ |
| `EntityInUseException` | `RuntimeException` | ❌ | ❌ | ❌ | High | ✅ |
| `EntityNotFoundException` | `RuntimeException` | ❌ | ❌ | ❌ | High | ✅ |
| `ExpiredSessionException` | `Exception` | ❌ | ❌ | ❌ | High | ✅ |
| `IdentifierNotFoundException` | `LogicException` | ❌ | ❌ | ❌ | Medium | ✅ |
| `InvalidCredentialsException` | `DomainException` | ❌ | ❌ | ❌ | High | ✅ |
| `InvalidIdentifierFormatException` | `LogicException` | ❌ | ❌ | ❌ | Medium | ✅ |
| `InvalidIdentifierStateException` | `LogicException` | ❌ | ❌ | ❌ | Medium | ✅ |
| `InvalidOperationException` | `RuntimeException` | ❌ | ❌ | ❌ | High | ✅ |
| `InvalidSessionException` | `Exception` | ❌ | ❌ | ❌ | High | ✅ |
| `MustChangePasswordException` | `DomainException` | ❌ | ❌ | ❌ | High | ✅ |
| `PermissionDeniedException` | `DomainException` | ❌ | ❌ | ❌ | High | ✅ |
| `RecoveryLockException` | `RuntimeException` | ❌ | ❌ | ❌ | High | ✅ |
| `RevokedSessionException` | `Exception` | ❌ | ❌ | ❌ | High | ✅ |
| `TwoFactorAlreadyEnrolledException` | `RuntimeException` | ❌ | ❌ | ❌ | High | ✅ |
| `TwoFactorEnrollmentFailedException` | `RuntimeException` | ❌ | ❌ | ❌ | High | ✅ |
| `UnauthorizedException` | `DomainException` | ❌ | ❌ | ❌ | High | ✅ |
| `UnsupportedNotificationChannelException` | `DomainException` | ❌ | ❌ | ❌ | Low | ✅ |
