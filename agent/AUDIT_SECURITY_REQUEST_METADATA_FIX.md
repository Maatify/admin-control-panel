# Audit & Security Request Metadata Fix Report

## 1. Problem Summary
The project previously lacked reliable `request_id` propagation in Audit Logs and Security Events. `AuditEventDTO` relied on random `correlation_id` generation or `bin2hex` calls, and `SecurityEventDTO` did not enforce `request_id` presence. Services were using `ClientInfoProvider` (likely accessing globals) instead of the canonical `RequestContext`. This violated the security invariant that "Any Audit or Security event without request_id is INVALID".

## 2. Files Touched

### DTOs
- `app/Domain/DTO/AuditEventDTO.php`
- `app/Domain/DTO/SecurityEventDTO.php`
- `app/Domain/DTO/LegacyAuditEventDTO.php`

### Services
- `app/Domain/Service/SessionRevocationService.php`
- `app/Domain/Service/AuthorizationService.php`
- `app/Domain/Service/StepUpService.php`
- `app/Domain/Service/RoleAssignmentService.php`
- `app/Domain/Service/RememberMeService.php`
- `app/Domain/Service/AdminAuthenticationService.php`
- `app/Domain/Service/AdminEmailVerificationService.php`
- `app/Domain/Service/RecoveryStateService.php`
- `app/Domain/Service/SessionValidationService.php`

### Middleware
- `app/Http/Middleware/AuthorizationGuardMiddleware.php`
- `app/Http/Middleware/ScopeGuardMiddleware.php`
- `app/Http/Middleware/SessionStateGuardMiddleware.php`
- `app/Http/Middleware/RecoveryStateMiddleware.php`
- `app/Http/Middleware/SessionGuardMiddleware.php`
- `app/Http/Middleware/RememberMeMiddleware.php`
- `app/Http/Middleware/GuestGuardMiddleware.php`

### Controllers
- `app/Http/Controllers/Api/SessionBulkRevokeController.php`
- `app/Http/Controllers/Api/SessionRevokeController.php`
- `app/Http/Controllers/Web/TwoFactorController.php`
- `app/Http/Controllers/StepUpController.php`
- `app/Http/Controllers/Web/LogoutController.php`
- `app/Http/Controllers/Web/LoginController.php`
- `app/Http/Controllers/AuthController.php`
- `app/Http/Controllers/AdminEmailVerificationController.php`
- `app/Http/Controllers/Web/EmailVerificationController.php`

## 3. Exact Fix Applied

### Audit Logs
- **Action**: Updated `AuditEventDTO` and `LegacyAuditEventDTO` to explicitly require a `request_id` string in the constructor.
- **Guarantee**: Every instantiation of an audit event must now provide this ID. If missing, PHP will throw a `TypeError` at runtime.
- **Source**: Passed down from `RequestContext->requestId`.

### Security Events
- **Action**: Updated `SecurityEventDTO` constructor to require `string $requestId`.
- **Implementation**: The constructor automatically merges `['request_id' => $requestId]` into the context array.
- **Guarantee**: No security event can be created without a request ID.

## 4. Context Source
- **Mandatory Source**: `App\Context\RequestContext` retrieved via `$request->getAttribute(RequestContext::class)`.
- **Implementation**: All updated Controllers and Middleware fetch this context and pass it explicitly to the Service layer.
- **Prohibited Sources**: `ClientInfoProvider` usage for IP/UserAgent in these paths was replaced by `RequestContext` properties. `$_SERVER` access was verified to be absent in the modified paths.

## 5. Fail-Closed Confirmation
- **Mechanism**: In all updated Controllers/Middleware, the code attempts to retrieve `RequestContext`.
- **Enforcement**: strict check `if (!$context instanceof RequestContext) { throw new \RuntimeException("Request context missing"); }`.
- **Outcome**: If the `RequestContextMiddleware` fails or is bypassed, the application throws an exception immediately, preventing any action or logging without metadata. This ensures fail-closed behavior.

## 6. What was intentionally NOT changed
- **Authentication Flow**: The core logic for verifying credentials remains unchanged.
- **Session Logic**: Session repository and token generation logic is untouched.
- **Middleware Order**: The existing middleware pipeline order was preserved.
- **ClientInfoProvider**: The class remains in the codebase for other potential uses, but was removed from the Audit/Security critical path.
