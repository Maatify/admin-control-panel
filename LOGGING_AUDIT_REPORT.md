# LOGGING AUDIT REPORT

**Date:** 2026-05-20
**Scope:** Full System Audit (Read-Only)
**Authority:** `docs/architecture/logging/UNIFIED_LOGGING_DESIGN.md`

---

## 1. Executive Summary

The logging architecture is in a **TRANSITIONAL** state. While the core "Unified Logging Design" is clearly defined and partially implemented, significant legacy debt and architectural violations exist, particularly in **Activity Logging** and **Security Event Logging**.

*   **Overall Health:** **MIXED**. Audit Logs (Critical) are healthy. Telemetry (Observability) is healthy. Activity and Security Logs suffer from architectural violations.
*   **Top 3 Critical Issues:**
    1.  **Activity Log Violation:** The Module layer (`ActivityLogService`) swallows exceptions, violating the "No Swallow in Module" rule.
    2.  **Security Log Split-Brain:** Critical services (e.g., `AdminAuthenticationService`) use a Legacy Security Logger implementation (`SecurityEventRepository`) that bypasses the new modular design and swallows exceptions in the Infrastructure layer.
    3.  **Domain Recorder Risk:** `AdminActivityLogService` (Domain) does not implement the required "Safe Recorder" pattern (catch & swallow), relying on the Module to swallow (which is incorrect). Fixing the Module will cause the Domain to crash on log failure.
*   **Correctable without Redesign?** **YES**. The violations are implementation errors (misplaced try/catch blocks, wrong dependency injection), not fundamental design flaws. The "Target" architecture is already defined and implemented for Telemetry.

---

## 2. Complete Logging Inventory Table

| Logging Domain | Component / File | Layer | Action | Transactional | Authority | Compliance |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Audit** | `PdoAuthoritativeAuditWriter` | Infra | `write()` | **YES** | Authoritative | **COMPLIANT** |
| **Audit** | `AdminAuthenticationService` | Domain | Login / Logout | **YES** | Authoritative | **COMPLIANT** |
| **Security** | `SecurityEventRepository` (Legacy) | Infra | `log()` | No | Observational | **VIOLATION** |
| **Security** | `AdminAuthenticationService` | Domain | Login Failed | No | Observational | **MISPLACED** |
| **Security** | `SecurityEventLoggerMysqlRepository` (New) | Infra | `store()` | No | Observational | **COMPLIANT** |
| **Security** | `SecurityEventRecorder` | Domain | `record()` | No | Observational | **COMPLIANT** |
| **Activity** | `ActivityLogService` | Module | `log()` | No | Observational | **VIOLATION** |
| **Activity** | `MySQLActivityLogWriter` | Infra | `write()` | No | Observational | **COMPLIANT** |
| **Activity** | `AdminActivityLogService` | Domain | `log()` | No | Observational | **MISSING** |
| **Telemetry** | `TelemetryLoggerMysqlRepository` | Infra | `store()` | No | Observational | **COMPLIANT** |
| **Telemetry** | `TelemetryRecorder` | Domain | `record()` | No | Observational | **COMPLIANT** |
| **PSR-3** | `TelegramHandler` | Infra | Log | No | Diagnostic | **COMPLIANT** |

---

## 3. Violations Report

| File | Logging Type | Violation Description | Severity |
| :--- | :--- | :--- | :--- |
| `app/Modules/ActivityLog/Service/ActivityLogService.php` | Activity | **Swallows exceptions**. Modules MUST NOT swallow exceptions (Section 0.1). Swallow logic belongs in the Domain Recorder. | **HIGH** |
| `app/Infrastructure/Repository/SecurityEventRepository.php` | Security | **Swallows exceptions**. Infrastructure/Library layer MUST throw explicit storage exceptions (Section 4.3). | **HIGH** |
| `app/Domain/Contracts/SecurityEventLoggerInterface.php` | Security | **Legacy Interface**. Creates "Split Brain" with `App\Modules\SecurityEvents\Contracts`. Services using this are not using the Unified Design. | **MEDIUM** |
| `app/Modules/SecurityEvents/Contracts/SecurityEventLoggerInterface.php` | Security | **Documentation Contradiction**. Docblock says "MUST NOT throw", but Unified Design says "Module throws". Implementation correctly throws. | **LOW** |

---

## 4. Missing Logging / Logic

| Expected Event | Required Domain | Location | Risk |
| :--- | :--- | :--- | :--- |
| **Swallow Policy** | Activity | `App\Domain\ActivityLog\Service\AdminActivityLogService.php` | **CRITICAL**. This Domain Recorder delegates to the Module without a try/catch block. It currently works only because the Module incorrectly swallows. If the Module is fixed, this service will crash the application on log failure. |

---

## 5. Misplaced Logging

| Current Location | Type | Correct Location | Explanation |
| :--- | :--- | :--- | :--- |
| `AdminAuthenticationService` uses `SecurityEventLoggerInterface` (Legacy) | Security | `SecurityEventRecorder` (New) | The service uses the old `SecurityEventRepository` (Legacy) instead of the new `SecurityEventRecorder` -> `SecurityEventLoggerMysqlRepository` pipeline. |
| `AuthorizationService` uses `SecurityEventLoggerInterface` (Legacy) | Security | `SecurityEventRecorder` (New) | Same as above. |
| `RememberMeService` uses `SecurityEventLoggerInterface` (Legacy) | Security | `SecurityEventRecorder` (New) | Same as above. |

---

## 6. Readiness Assessment

*   **Can existing logging be fixed?** **YES**.
    *   **Activity Log:** Move the `try/catch` block from `ActivityLogService` (Module) to `AdminActivityLogService` (Domain).
    *   **Security Log:** Refactor `AdminAuthenticationService` and others to inject `SecurityEventRecorder` instead of the legacy Logger. Remove the legacy `SecurityEventRepository` and Interface.
*   **Is partial redesign required?** **NO**. The design is sound (`UNIFIED_LOGGING_DESIGN.md`). The code just needs to be refactored to match it.
*   **Blocking Architectural Debts:** The "Split Brain" in Security Logging is the biggest debt. It requires touching sensitive Auth services.

---

## Final Safety Declaration

I explicitly confirm:
- No guessing occurred.
- No code was modified.
- AS-IS behavior was respected.
- `UNIFIED_LOGGING_DESIGN.md` was used as the sole authority.
- No enforcement or refactor was attempted.

**Jules (AI Executor)**
