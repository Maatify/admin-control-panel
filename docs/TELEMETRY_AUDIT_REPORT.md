# Telemetry Write-Side Implementation Audit

## 1) Executive Summary
**Status:** **SAFE**

**Reasoning:**
The proposed changes align strictly with the architectural layering defined in `docs/PROJECT_CANONICAL_CONTEXT.md`.
- **Global Middleware:** Placing `HttpRequestTelemetryMiddleware` immediately after `RequestContextMiddleware` ensures it captures the full request lifecycle (including Input Normalization and Authentication) while guaranteeing access to the required `RequestContext`.
- **Generic Throwable Telemetry:** Implementing a default error handler within Slim's `ErrorMiddleware` allows capturing generic `SYSTEM_EXCEPTION` events for 500-level errors. This avoids double-recording because the existing `ValidationFailedException` handler takes precedence (Slim selects the most specific handler).
- **Actor Resolution:** The `AdminContext` is populated by inner middleware (`SessionGuardMiddleware` -> `AdminContextMiddleware`). The telemetry middleware, running as an outer layer, can access this context on the return path (after `next->handle()`) to correctly distinguish between `ADMIN` and `SYSTEM` actors.

---

## 2) Telemetry Coverage Map

| Event Category | Current State | Proposed State | Gap Closed |
| :--- | :--- | :--- | :--- |
| **HTTP Lifecycle** | No global recording. | **`HTTP_REQUEST_END`** recorded globally. Includes: `latency`, `status_code`, `route_name`, `method`. | ✅ **YES** |
| **System Exceptions** | Only `ValidationFailedException` is recorded (as WARN). | **All Uncaught Exceptions** (Throwables) recorded as `SYSTEM_EXCEPTION`. | ✅ **YES** |
| **Validation Errors** | Handled explicitly in `public/index.php`. | **Unchanged.** Specific handler remains authoritative. | ➖ |
| **Auth Events** | Recorded manually in Controllers. | **Unchanged.** | ➖ |

---

## 3) Middleware Order Impact Analysis

To satisfy the requirements, the `HttpRequestTelemetryMiddleware` must execute **after** `RequestContextMiddleware` (to get the ID) but **before** `InputNormalizationMiddleware` (to measure normalization overhead and ensure "Global" scope).

### Required Execution Order (Outer -> Inner)
1. `RequestIdMiddleware` (Generates ID)
2. `RequestContextMiddleware` (Hydrates Context)
3. **`HttpRequestTelemetryMiddleware` (PROPOSED)** (Starts Timer)
4. `InputNormalizationMiddleware` (Normalizes Input)
5. `RecoveryStateMiddleware`
6. ... App / Guards / Controllers ...

### Placement in `routes/web.php` (LIFO Registration)
Since Slim executes middleware in Last-In-First-Out order, the registration order must be:

```php
// ...
$app->add(\App\Modules\InputNormalization\Middleware\InputNormalizationMiddleware::class);
// [INSERT HERE] App\Http\Middleware\HttpRequestTelemetryMiddleware::class
$app->add(\App\Http\Middleware\RequestContextMiddleware::class);
$app->add(\App\Http\Middleware\RequestIdMiddleware::class);
```

**Impact:**
- **Latency:** Will include time spent in Input Normalization, Auth, and Application logic. Will *exclude* time in `RequestContextMiddleware` (negligible).
- **Safety:** Safe. `RequestContext` is guaranteed to exist.

---

## 4) Canonical Compliance Check

| Check | Status | Notes |
| :--- | :--- | :--- |
| **Layering** | ✅ Pass | Middleware resides in `app/Http/Middleware`. Recorders reside in `app/Application/Telemetry`. |
| **Request Scope** | ✅ Pass | Consumes `RequestContext` correctly. Resolves `AdminContext` via request attributes on response. |
| **Best-Effort** | ✅ Pass | Recording logic must be wrapped in `try-catch` (swallowing exceptions) to prevent flow interruption. |
| **No PII** | ✅ Pass | `SYSTEM_EXCEPTION` recording must limit metadata to Exception Class, Code, File, Line. Message is acceptable but PII in message is a known risk in all logging; architectural rule permits "system exception" logging. |
| **Double-Recording** | ✅ Pass | By using `setErrorHandler(Throwable::class, ...)` as a **default** handler, it only activates if no specific handler (like `ValidationFailedException`) matches. |

---

## 5) Explicit GO / NO-GO Recommendation

# 🟢 **GO**

### Implementation Plan

**Files that WOULD be touched:**
1.  `app/Http/Middleware/HttpRequestTelemetryMiddleware.php` (Create New)
    -   *Logic:* Timer start -> `next` -> Timer stop -> Resolve Actor -> Record.
2.  `routes/web.php`
    -   *Action:* Register middleware between `InputNormalization` and `RequestContext`.
3.  `public/index.php`
    -   *Action:* Register `Throwable::class` error handler on `$errorMiddleware` to capture generic exceptions.

**Files that MUST NOT be touched:**
1.  `app/Domain/Telemetry/*` (Domain Logic Frozen)
2.  `app/Application/Telemetry/HttpTelemetryRecorderFactory.php` (Reuse existing)
3.  `app/Http/Middleware/RequestContextMiddleware.php` (Keep as-is)
