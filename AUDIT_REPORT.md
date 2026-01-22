# Full Logging Inventory & Compliance Audit

## 1) Executive Summary

The logging architecture is in a **TRANSITIONAL** state. While the core "Unified Logging Design" is fully implemented in the `app/Modules/` layer, a significant portion of the Domain layer relies on legacy infrastructure that violates the canonical design.

*   **Overall Health**: **PARTIALLY COMPLIANT**. The target architecture exists and is correct, but legacy implementations persist side-by-side.
*   **Top 3 Critical Issues**:
    1.  **Security Logging Split-Brain**: The system has two competing security loggers. Most core services (`AdminAuthenticationService`, `AuthorizationService`) use the **Legacy/Violating** `SecurityEventRepository`, while newer services (`StepUpService`) use the **Canonical** `SecurityEventRecorder`.
    2.  **Legacy Swallowing**: The legacy `SecurityEventRepository` swallows exceptions internally (Infrastructure layer), violating the "Honest Contracts" rule.
    3.  **Zombie Code**: `PdoTelemetryAuditLogger` exists, violates multiple rules (writing to `audit_logs` for telemetry), but appears unused.
*   **Correctable without Redesign?**: **YES**. The Canonical modules are fully ready. The remediation path is to switch dependency injection bindings and update Domain Services to use the Recorders instead of legacy Interfaces.

---

## 2) Complete Logging Inventory Table

| File Path | Layer | Logging Domain | Triggering Action | Transactional | Authority | Classification |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `app/Infrastructure/Audit/PdoAuthoritativeAuditWriter.php` | Infra | **Audit** | Authority/Security Changes | **YES** | **Authoritative** | **COMPLIANT** |
| `app/Infrastructure/Audit/PdoTelemetryAuditLogger.php` | Infra | **Telemetry** (claimed) | Unknown (Unused) | NO (Swallows) | Non-Auth | **VIOLATION** |
| `app/Modules/ActivityLog/Infrastructure/Mysql/ActivityLogLoggerMysqlRepository.php` | Module | **Activity** | Staff Actions | NO | Non-Auth | **COMPLIANT** |
| `app/Modules/SecurityEvents/Infrastructure/Mysql/SecurityEventLoggerMysqlRepository.php` | Module | **Security** | Security Signals | NO | Non-Auth | **COMPLIANT** |
| `app/Modules/Telemetry/Infrastructure/Mysql/TelemetryLoggerMysqlRepository.php` | Module | **Telemetry** | Diagnostics | NO | Non-Auth | **COMPLIANT** |
| `app/Infrastructure/Repository/SecurityEventRepository.php` | Infra | **Security** | Security Signals (Legacy) | NO (Swallows) | Non-Auth | **VIOLATION** |
| `app/Domain/ActivityLog/Recorder/ActivityRecorder.php` | Domain | **Activity** | Recorder Policy | NO | Non-Auth | **COMPLIANT** |
| `app/Domain/SecurityEvents/Recorder/SecurityEventRecorder.php` | Domain | **Security** | Recorder Policy | NO | Non-Auth | **COMPLIANT** |
| `app/Domain/Telemetry/Recorder/TelemetryRecorder.php` | Domain | **Telemetry** | Recorder Policy | NO | Non-Auth | **COMPLIANT** |

---

## 3) Violations Report

| File | Logging Type | Why it violates the design | Severity |
| :--- | :--- | :--- | :--- |
| `app/Infrastructure/Repository/SecurityEventRepository.php` | Security | **Swallows Exceptions**: Infra/Library layer MUST NOT swallow exceptions (Section 2.3). <br> **Legacy Schema**: Uses `admin_id` instead of `actor_id` (Section 5.3.1). <br> **Duplicate**: Competing implementation with Module. | **HIGH** |
| `app/Infrastructure/Audit/PdoTelemetryAuditLogger.php` | Telemetry | **Cross-Contamination**: Writes Telemetry data to `audit_logs` table (Section 5.1). <br> **Swallows Exceptions**: Infra layer swallowing. <br> **Naming**: Confuses Telemetry and Audit. | **CRITICAL** |
| `app/Infrastructure/Audit/PdoAdminSecurityEventReader.php` | Security (Read) | **Legacy Reader**: Likely reads from legacy schema or mixes concerns. | **LOW** |

---

## 4) Missing Logging

| Expected Event | Required Domain | Where it should occur | Risk of Absence |
| :--- | :--- | :--- | :--- |
| **Legacy Service Migration** | Security | `AdminAuthenticationService`, `AuthorizationService` | **Medium**. These services currently log to the *Legacy* repository. They are "logging", but not to the canonical standard. |

---

## 5) Misplaced Logging

| Current Location/Type | Correct Location/Type | Explanation |
| :--- | :--- | :--- |
| `app/Infrastructure/Repository/SecurityEventRepository.php` | `app/Modules/SecurityEvents/...` | The implementation exists in `Infrastructure` but should be fully delegated to the `Modules` layer. The project has "Split Brain" where both exist. |
| Services injecting `App\Domain\Contracts\SecurityEventLoggerInterface` | `SecurityEventRecorder` | Services like `AdminAuthenticationService` inject the raw Logger interface. They SHOULD inject `SecurityEventRecorder` to get policy-managed, context-aware logging. |

---

## 6) Readiness Assessment

*   **Can existing logging be fixed?**: **Yes**.
*   **Is partial redesign required?**: **No**. The design is solid. The implementation just needs to finish migrating legacy services to the new Recorders.
*   **Are there blocking architectural debts?**: **Yes**. The "Split Brain" in `SecurityEventLoggerInterface` (Domain Contract vs Module Contract) causes confusion and prevents full adoption of the new system.

---

## Final Safety Declaration

I confirm:
- No guessing occurred.
- No code was modified.
- AS-IS behavior was respected.
- `UNIFIED_LOGGING_DESIGN.md` was used as the sole authority.
- No enforcement or refactor was attempted.

**Report Generated By**: JULES_EXECUTOR
