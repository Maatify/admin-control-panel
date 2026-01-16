# Telemetry Audit Report

**Date:** 2026-01-20
**Project:** maatify/admin-control-panel
**Scope:** Telemetry Architecture, Usage, and Test Coverage
**Status:** **CRITICAL / POOR HEALTH**

---

## A) Executive Summary

The Telemetry implementation in `maatify/admin-control-panel` is currently in a **fragmented and hazardous state**.

While a modern, well-structured Telemetry module exists (`App\Modules\Telemetry`), it is barely used. The application relies heavily on **Legacy Audit** and **Legacy Security Event** systems that violate core architectural principles:
1.  **They are not best-effort:** Database failures will crash the application logic (e.g., Login flows).
2.  **They are duplicative:** Multiple overlapping systems record the same events (e.g., Login Success is recorded by three separate systems).
3.  **They are untested:** Zero unit test coverage exists for the Telemetry domain and modules.

Additionally, a **critical bug** exists in the `HttpRequestTelemetryMiddleware` that prevents Admin request telemetry from being recorded correctly, likely causing silent runtime errors or missing data.

---

## B) Telemetry Usage Map

| Location | Purpose | Type | Status | Notes |
| :--- | :--- | :--- | :--- | :--- |
| `HttpRequestTelemetryMiddleware` | HTTP Request Tracking | **Telemetry** | ❌ **Incorrect** | Uses incompatible method signature for Admin recorder. |
| `SessionRevokeController` | Revocation Event | **Telemetry** | ✅ Correct | Uses `HttpTelemetryRecorderFactory` correctly. |
| `SessionBulkRevokeController` | Revocation Event | **Telemetry** | ✅ Correct | Uses `HttpTelemetryRecorderFactory` correctly. |
| `SessionQueryController` | Data Access | **Telemetry** | ✅ Correct | Best-effort query tracking. |
| `StepUpController` | Step-Up Auth | **Telemetry** | ✅ Correct | Tracks success/failure without breaking flow. |
| `AdminAuthenticationService` | Login Success | **Legacy Audit** | ⚠️ **Risk** | No error handling; DB fail crashes login. |
| `AdminAuthenticationService` | Login Success | **Outbox Audit** | ⚠️ **Risk** | Duplicate logging source. |
| `LoginController` | Login Success | **Activity Log** | ⚠️ **Excessive** | 3rd place logging Login Success. |
| `AdminAuthenticationService` | Login Failure | **Security Event** | ❌ **Hazard** | Uses `SecurityEventRepository` (Legacy) which throws on error. |
| `AuthorizationService` | Permission Check | **Legacy Audit** | ⚠️ **Risk** | Logs "access_granted"; no error handling. |
| `AuthorizationService` | Permission Check | **Security Event** | ❌ **Hazard** | Logs "permission_denied"; throws on error. |
| `LogoutController` | Logout | **Security Event** | ❌ **Hazard** | Throws on error. |

---

## C) Violations & Risks

### 1. Critical Runtime Bug in Middleware
**File:** `app/Http/Middleware/HttpRequestTelemetryMiddleware.php`
**Finding:** The middleware calls `$recorder->record(...)` assuming a unified signature. However, `HttpTelemetryAdminRecorder::record` requires `?int $actorId` as the first argument, while `HttpTelemetrySystemRecorder::record` does not.
**Impact:** Admin request telemetry likely fails or throws `TypeError` (caught and swallowed, hiding the bug).

### 2. Violation of Best-Effort Principle (System Hazard)
**Files:**
*   `app/Infrastructure/Repository/SecurityEventRepository.php`
*   `app/Infrastructure/Audit/PdoTelemetryAuditLogger.php`
*   `app/Modules/SecurityEvents/Infrastructure/Mysql/SecurityEventLoggerMysqlRepository.php`
**Finding:** These classes execute PDO statements directly or explicitly throw exceptions (`SecurityEventStorageException`) on failure.
**Impact:** A database outage or storage full event will cause **Critical Flows (Login, Authorization)** to crash with 500 errors, rather than failing open/silently as required for telemetry.

### 3. Duplicate Security Logging Systems
**Finding:** Two competing "Security Event" systems exist:
1.  `App\Infrastructure\Repository\SecurityEventRepository` (Legacy, used by Domain Services)
2.  `App\Modules\SecurityEvents\Infrastructure\Mysql\SecurityEventLoggerMysqlRepository` (New Module, barely used)
**Impact:** Schema mismatch, code confusion, and maintenance burden.

### 4. Fragmentation of "Login" Event
**Finding:** A successful login triggers:
1.  `LegacyAuditEventDTO` ("login_credentials_verified")
2.  `AuditEventDTO` (Outbox "login_credentials_verified")
3.  `AdminActivityAction::LOGIN_SUCCESS` (Activity Log)
**Impact:** Wasted storage, ambiguous source of truth for audit trails.

---

## D) Test Coverage Matrix

| Component | Scope | Coverage Status | Notes |
| :--- | :--- | :--- | :--- |
| **Telemetry Domain** | `App\Domain\Telemetry` | ❌ **0%** | No unit tests found. |
| **Telemetry Modules** | `App\Modules\Telemetry` | ❌ **0%** | No unit tests found. |
| **Telemetry Application** | `App\Application\Telemetry` | ❌ **0%** | No unit tests found. |
| **Legacy Audit** | `App\Infrastructure\Audit` | ❌ **0%** | Only mocked in Service tests, implementation untested. |
| **Security Events** | `App\Infrastructure\Repository` | ❌ **0%** | Implementation untested. |

**Verdict:** The entire Telemetry and Audit subsystem is effectively **untested** at the unit level.

---

## E) Required Actions

### Priority 1: Fix Critical Hazards (Stability)
1.  **Refactor Legacy Loggers:** Wrap `SecurityEventRepository::log` and `PdoTelemetryAuditLogger::log` in `try-catch` blocks to swallow exceptions. Ensure they never throw.
2.  **Fix Middleware Bug:** Update `HttpRequestTelemetryMiddleware` to correctly handle `HttpTelemetryAdminRecorder` signature (pass `admin_id`).

### Priority 2: Unification & Cleanup (Architecture)
3.  **Deprecate Legacy Security Repo:** Migrate all usages of `App\Infrastructure\Repository\SecurityEventRepository` to `App\Modules\SecurityEvents\Infrastructure\Mysql\SecurityEventLoggerMysqlRepository`.
4.  **Consolidate Login Logging:** Choose **ONE** authoritative source for Login events (likely Security Event or Audit) and remove the others from the critical path.

### Priority 3: Test Coverage (Quality)
5.  **Add Unit Tests:** Create `tests/Domain/Telemetry`, `tests/Modules/Telemetry`, and `tests/Application/Telemetry`.
6.  **Verify Failure Resilience:** specific tests must prove that `record()` does not throw even when storage fails.
