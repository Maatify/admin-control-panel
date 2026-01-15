# Audit Report: RequestContext Unification

## Inventory

| File | Line | What is being read | Current Source | Recommended Source | Action Taken |
|------|------|--------------------|----------------|--------------------|--------------|
| `app/Http/Middleware/RequestContextMiddleware.php` | 27 | `request_id` | `$request->getAttribute('request_id')` | Keep as-is | Skipped (Source of Truth) |
| `app/Http/Middleware/RequestContextMiddleware.php` | 37 | `REMOTE_ADDR` | `$request->getServerParams()` | Keep as-is | Skipped (Source of Truth) |
| `app/Http/Middleware/RequestContextMiddleware.php` | 42 | `HTTP_USER_AGENT` | `$request->getServerParams()` | Keep as-is | Skipped (Source of Truth) |
| `app/Http/Middleware/RequestIdMiddleware.php` | 27 | `request_id` | `X-Request-ID` Header | Keep as-is | Skipped (Source of Truth) |
| `app/Http/Controllers/Web/LoginController.php` | 62 | `RequestContext` | `$request->getAttribute(RequestContext::class)` | `RequestContext` | Skipped (Already Correct) |
| `app/Http/Controllers/StepUpController.php` | 55 | `RequestContext` | `$request->getAttribute(RequestContext::class)` | `RequestContext` | Skipped (Already Correct) |
| `app/Http/Controllers/Api/SessionBulkRevokeController.php` | 33 | `RequestContext` | `$request->getAttribute(RequestContext::class)` | `RequestContext` | Skipped (Already Correct) |
| `app/Domain/Service/AdminAuthenticationService.php` | 41 | `ip/ua/req_id` | `RequestContext` (Method Argument) | Remove Dependency | **REPORTED (Layering Violation)** |
| `app/Domain/Service/SessionRevocationService.php` | 25 | `ip/req_id` | `RequestContext` (Method Argument) | Remove Dependency | **REPORTED (Layering Violation)** |
| `app/Domain/Service/AuthorizationService.php` | 32 | `ip/ua/req_id` | `RequestContext` (Method Argument) | Remove Dependency | **REPORTED (Layering Violation)** |
| `app/Domain/Service/RememberMeService.php` | 39 | `userAgent` | `RequestContext` (Method Argument) | Remove Dependency | **REPORTED (Layering Violation)** |
| `app/Domain/Service/StepUpService.php` | 36 | `ip/ua/req_id` | `RequestContext` (Method Argument) | Remove Dependency | **REPORTED (Layering Violation)** |
| `app/Domain/Service/AdminEmailVerificationService.php` | * | `ip/req_id` | `RequestContext` (Method Argument) | Remove Dependency | **REPORTED (Layering Violation)** |
| `app/Domain/Service/RecoveryStateService.php` | * | `RequestContext` | `RequestContext` (Method Argument) | Remove Dependency | **REPORTED (Layering Violation)** |

## Summary

The audit was conducted to identify consumers of `request_id`, `ip_address`, and `user_agent`.

- **HTTP Layer**: All Controllers and Middleware are correctly using `RequestContext` or extracting it from the request attributes properly. No mixed or global reads (`$_SERVER`) were found outside of the `RequestContextMiddleware`.
- **Infrastructure Layer**: Uses DTOs populated by Services, which is correct.
- **Domain Layer**: A widespread violation of the "Domain services must NOT depend on RequestContext" rule was identified. Multiple Domain Services accept `App\Context\RequestContext` as a method argument.

## Actions Taken

- **No Code Changes Applied**: As the HTTP layer is already unified and compliant, and fixing the Domain Layer violations would require a significant architectural refactor (forbidden by "No architectural refactors" rule), no code changes were made.
- **Reporting**: The Domain Layer violations are documented in this report for future refactoring.

## Confirmation

- **PHPStan**: No code changes were made, so no new PHPStan errors should be introduced. Existing status presumed unchanged.
