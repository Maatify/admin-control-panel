# 🔍 Logging Architecture Audit Report

**PROJECT:** `maatify/admin-control-panel`
**DATE:** 2026-01-13
**AUDITOR:** Jules (AI Agent)
**STATUS:** ⚠️ Partially Compliant

---

## 1. Executive Summary

The logging architecture demonstrates a clear intent to separate concerns (Audit, Security, Activity, Telemetry) as per the Canonical Context. However, the implementation diverges significantly from the architectural specification in several critical areas.

**Key Findings:**
1.  **Critical Misuse:** Telemetry is being used for operational activity tracking (Session Revocation), and Activity Logs are being used for Authentication events (Login), directly violating the separation of concerns.
2.  **Structural Violation:** The Authoritative Audit Writer writes to an `audit_outbox` table, while the `audit_logs` table (mandated by docs) is populated by a non-authoritative "Telemetry Audit Logger".
3.  **Missing Coverage:** New Admin CRUD endpoints (`create`, `addEmail`) and Configuration endpoints (`upsertPreference`) completely lack the mandatory Activity Logging and, in some cases, Audit Logging.

The system is currently **Partially Compliant**, with blocking violations that must be addressed before further feature development.

---

## 2. Log Types Overview

| Log Type | Canonical Requirement | Observed Implementation | Status |
| :--- | :--- | :--- | :--- |
| **Audit Logs** | `audit_logs` table, Transactional, Fail-Closed | `audit_outbox` table (Transactional) via `PdoAuthoritativeAuditWriter` | ⚠️ **Deviation** |
| **Security Events** | `security_events` table, Best-Effort | `security_events` table via `SecurityEventRepository` | ✅ **Compliant** |
| **Activity Logs** | `activity_logs` table, Best-Effort | `activity_logs` table via `MySQLActivityLogWriter` | ✅ **Compliant** |
| **Telemetry** | Observability Only | Used for Business Logic Logging in Controllers | ❌ **Misuse** |
| **PSR-3** | Diagnostics Only | Not observed in business logic (Correct) | ✅ **Compliant** |

**Note on Audit Deviation:**
The Canonical Document explicitly states Audit Logs MUST be stored in the `audit_logs` table. The current implementation uses an Outbox Pattern (`audit_outbox`), while `audit_logs` is written to by a `PdoTelemetryAuditLogger` which swallows exceptions. This creates a risk where the "Audit Log" table contains non-authoritative data.

---

## 3. Correct Usage Map

| Log Type | Class / Layer | Use Case | Correct? | Notes |
| :--- | :--- | :--- | :--- | :--- |
| **Audit** | `SessionRevocationService` | `session_revoked`, `sessions_bulk_revoked` | ✅ **YES** | Correctly uses `AuthoritativeSecurityAuditWriterInterface`. |
| **Audit** | `AdminAuthenticationService` | `login_credentials_verified`, `session_revoked` | ✅ **YES** | Correctly uses `AuthoritativeSecurityAuditWriterInterface`. |
| **Security** | `AdminAuthenticationService` | `login_failed` (user_not_found, not_verified, invalid_password) | ✅ **YES** | Correctly uses `SecurityEventLoggerInterface`. |
| **Activity** | `AdminActivityLogService` | Wrapper for Activity Logging | ✅ **YES** | Service layer abstraction is correct. |

---

## 4. Violations & Misuse

| Violation | Location | Description | Canonical Reference |
| :--- | :--- | :--- | :--- |
| **Activity Log Misuse** | `LoginController::login` | Logs `AdminActivityAction::LOGIN_SUCCESS` to Activity Log. | **D.7 / D.1**: "Activity Logs MUST NOT be used for: Authentication attempts... Login / logout". |
| **Telemetry Misuse** | `SessionRevokeController::__invoke` | Logs `session_revoke` (success) via `HttpTelemetryRecorderFactory`. | **D.4**: Telemetry/Observability must not be used as security or activity logging. |
| **Telemetry Misuse** | `SessionBulkRevokeController::__invoke` | Logs `session_revoke_bulk` via `HttpTelemetryRecorderFactory`. | **D.4**: Telemetry/Observability must not be used as security or activity logging. |
| **Audit Storage Violation** | `PdoAuthoritativeAuditWriter` | Writes to `audit_outbox` instead of `audit_logs`. | **D.1**: "Storage: Database only (`audit_logs` table)." |
| **Confusing Audit Source** | `PdoTelemetryAuditLogger` | Writes to `audit_logs` but swallows errors (Non-Authoritative). | **D.1**: Audit logs must be Fail-closed. |

---

## 5. Missing Logs

### A) Missing Activity Logs (Operational)
The following actions modify system state but **DO NOT** write to `activity_logs`:

*   **Session Revocation**: `SessionRevokeController` (Uses Telemetry instead).
*   **Bulk Session Revocation**: `SessionBulkRevokeController` (Uses Telemetry instead).
*   **Create Admin**: `AdminController::create` (No logs).
*   **Add Admin Email**: `AdminController::addEmail` (No logs).
*   **Update Preferences**: `AdminNotificationPreferenceController::upsertPreference` (No logs).

### B) Missing Audit Logs (Authoritative)
The following actions affect Authority/Security but **DO NOT** write to `audit_logs` (or `audit_outbox`):

*   **Create Admin**: `AdminController::create`. Creating a new admin is a fundamental authority change.
*   **Add Admin Email**: `AdminController::addEmail`. Modifying admin identifiers is a security-critical action.

---

## 6. Critical Gaps

1.  **No Activity Logging Standard Implementation**: While `ActivityLogService` exists, it is not consistently applied. Controllers are either using Telemetry or nothing at all for mutation tracking.
2.  **Audit Log Identity Crisis**: The system has two "Audit" writers:
    *   `PdoAuthoritativeAuditWriter` (Strict, writes to `audit_outbox`)
    *   `PdoTelemetryAuditLogger` (Loose, writes to `audit_logs`)
    *   This contradicts the documentation which expects `audit_logs` to be the authoritative source.

---

## 7. Final Verdict

**⚠️ Partially Compliant**

The infrastructure for compliance exists (Interfaces, DTOs, Writers), but the **application usage** is inconsistent and violates strict separation rules defined in `PROJECT_CANONICAL_CONTEXT.md`.

**Immediate Corrections Required (Reference Only):**
1.  Stop logging Login/Logout to Activity Logs.
2.  Replace Telemetry logging in Session Revocation with Activity Logs.
3.  Implement Activity and Audit logging for Admin Creation and Email Management.
4.  Align Audit Writer implementation with Canonical Storage requirements (`audit_logs` vs `audit_outbox`).

---

## 8. Confirmation

✅ **No Code Changes Performed.** This report is purely diagnostic.
