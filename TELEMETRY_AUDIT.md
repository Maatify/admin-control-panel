# Telemetry Audit Report

**Date:** 2026-01-16
**Scope:** Telemetry Architecture, Usage, and Test Coverage
**Project:** maatify/admin-control-panel

---

## A) Executive Summary

The Telemetry implementation follows a solid Domain-Driven Design (DDD) approach with clear separation of concerns between Domain (Recorder), Application (Factory), and Infrastructure (Logger). The "best-effort" and "silent failure" principles are correctly implemented in the core `TelemetryRecorder`.

However, there are significant gaps in **Test Coverage** and **Web UI Instrumentation**:
1.  **Critical Testing Deficit:** Unit tests for the Telemetry module itself are non-existent. Controller tests are either missing or outdated/broken.
2.  **Usage Gap:** The Web UI flow for Two-Factor Authentication (`TwoFactorController`) is completely uninstrumented, creating a blind spot compared to the API equivalent (`StepUpController`).
3.  **Service Layer Ambiguity:** The `StepUpService` relies on a legacy `TelemetryAuditLoggerInterface` which mixes "Telemetry" naming with "Security" event logging, creating potential confusion with the new Telemetry system.

---

## B) Telemetry Usage Map

| Location | Purpose | Event Type | Status |
| :--- | :--- | :--- | :--- |
| `app/Http/Middleware/HttpRequestTelemetryMiddleware.php` | Request Duration & Metadata | `HTTP_REQUEST_END` | ✅ Correct |
| `app/Http/Controllers/AuthController.php` | Login Success | `AUTH_LOGIN_SUCCESS` | ✅ Correct |
| `app/Http/Controllers/AuthController.php` | Login Failure (Credentials/State) | `AUTH_LOGIN_FAILURE` | ✅ Correct |
| `app/Http/Controllers/Web/LogoutController.php` | Admin Logout | `RESOURCE_MUTATION` (Action: `self_logout`) | ✅ Correct |
| `app/Http/Controllers/Api/SessionRevokeController.php` | Session Revoke | `RESOURCE_MUTATION` | ✅ Correct |
| `app/Http/Controllers/Api/SessionBulkRevokeController.php` | Bulk Session Revoke | `RESOURCE_MUTATION` | ✅ Correct |
| `app/Http/Controllers/StepUpController.php` | Step-Up Verification (API) | `AUTH_STEPUP_SUCCESS` / `FAILURE` | ✅ Correct |
| `app/Http/Controllers/Api/SessionQueryController.php` | Session List Query | `DATA_QUERY_EXECUTED` | ✅ Correct |
| `public/index.php` (Error Handler) | Global Exception Handling | `SYSTEM_EXCEPTION` | ✅ Correct |
| **`app/Http/Controllers/Web/TwoFactorController.php`** | **2FA Setup & Verify (Web UI)** | **N/A** | **❌ Missing** |
| `app/Domain/Service/StepUpService.php` | Security/Audit Logging | Legacy `TelemetryAuditLoggerInterface` | ⚠️ Legacy/Mixed |

---

## C) Violations & Risks

### 1. Missing Instrumentation in Web UI
*   **File:** `app/Http/Controllers/Web/TwoFactorController.php`
*   **Finding:** The `doSetup` (enable TOTP) and `doVerify` (verify TOTP) methods contain no Telemetry recording.
*   **Risk:** Operations performed via the Web UI are not visible in Telemetry dashboards, unlike their API counterparts (`StepUpController`).

### 2. Broken & Missing Test Coverage
*   **File:** `tests/Http/Controllers/AuthControllerTest.php`
*   **Finding:** The test instantiates `AuthController` with 4 arguments, but the actual class requires 6 (including Telemetry dependencies). This test cannot pass in its current state.
*   **Finding:** No unit tests exist for `app/Domain/Telemetry/Recorder/TelemetryRecorder.php` or `app/Application/Telemetry/HttpTelemetryRecorderFactory.php`.
*   **Risk:** Refactoring or logic changes in Telemetry core could silently break the monitoring system.

### 3. Ambiguous Interface Naming
*   **File:** `app/Domain/Contracts/TelemetryAuditLoggerInterface.php`
*   **Finding:** This interface is used in `StepUpService` to log "Security" events, but is named "Telemetry" and documented as "Best-effort". This conflicts with the strict requirement for authoritative security logging (which is handled separately by `AuthoritativeSecurityAuditWriterInterface`, but the naming overlap is confusing).
*   **Risk:** Developers might mistakenly use the "Best-effort" logger for critical security audit trails, thinking it is the authoritative source.

---

## D) Test Coverage Matrix

| Component | Telemetry Covered? | Status | Notes |
| :--- | :--- | :--- | :--- |
| **Core: `TelemetryRecorder`** | No | ❌ Missing | No unit tests for `record()` or failure swallowing. |
| **Core: `HttpTelemetryRecorderFactory`** | No | ❌ Missing | No unit tests. |
| **Middleware: `HttpRequestTelemetryMiddleware`** | No | ❌ Missing | No integration tests verifying middleware emission. |
| **Controller: `AuthController`** | Yes (Theoretically) | ⚠️ Broken | Test file exists but constructor signature is outdated. |
| **Controller: `LogoutController`** | No | ❌ Missing | No tests found. |
| **Controller: `StepUpController`** | No | ❌ Missing | No tests found. |
| **Controller: `TwoFactorController`** | No | ❌ Missing | No tests found. |
| **Service: `StepUpService`** | Partial (Legacy) | ⚠️ Weak | Mocks `TelemetryAuditLoggerInterface` (Legacy), not the new Telemetry system. |

---

## E) Required Actions

### High Priority
1.  **Instrument `TwoFactorController`**:
    *   Inject `HttpTelemetryRecorderFactory`.
    *   Record `AUTH_STEPUP_SUCCESS` / `AUTH_STEPUP_FAILURE` in `doVerify`.
    *   Record `RESOURCE_MUTATION` (action: `2fa_setup`) in `doSetup`.
2.  **Fix `AuthControllerTest`**:
    *   Update constructor to match `AuthController`.
    *   Add mock expectations for `HttpTelemetryRecorderFactory` and `TelemetryEmailHasherInterface`.

### Medium Priority
3.  **Add Unit Tests for Telemetry Core**:
    *   Create `tests/Unit/Domain/Telemetry/TelemetryRecorderTest.php` to verify DTO transformation and exception swallowing.
    *   Create `tests/Unit/Application/Telemetry/HttpTelemetryRecorderFactoryTest.php`.
4.  **Add Controller Tests**:
    *   Create tests for `StepUpController`, `LogoutController`, and `SessionRevokeController` verifying that `record()` is called with correct parameters.

### Low Priority
5.  **Refactor Legacy Interface**:
    *   Rename `TelemetryAuditLoggerInterface` to `LegacyAuditLoggerInterface` to avoid confusion with the new `App\Domain\Telemetry` namespace.
