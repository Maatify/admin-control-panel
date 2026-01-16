# 🗺️ Telemetry Audit Logger Map (Hybrid Path)

**PROJECT:** `maatify/admin-control-panel`
**DATE:** 2026-01-13
**AUDITOR:** Jules (AI Agent)
**SCOPE:** Exhaustive mapping of `TelemetryAuditLoggerInterface` usages.

---

## 1. Inventory Table

| Class | Method | Triggering Action | Log Event Name | Severity | Risk | Nature |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `AuthorizationService` | `checkPermission` | **System Owner Bypass** | `access_granted` | N/A | **HIGH** | Authority (Missing Authoritative Log) |
| `AuthorizationService` | `checkPermission` | **Direct Permission** | `access_granted` | N/A | **HIGH** | Authority (Missing Authoritative Log) |
| `AuthorizationService` | `checkPermission` | **Role Permission** | `access_granted` | N/A | **HIGH** | Authority (Missing Authoritative Log) |
| `StepUpService` | `verifyTotp` | **TOTP Not Enrolled** | `stepup_primary_failed` | error | **HIGH** | Security (Misplaced in Audit Log) |
| `StepUpService` | `verifyTotp` | **Invalid TOTP Code** | `stepup_primary_failed` | error | **HIGH** | Security (Misplaced in Audit Log) |
| `StepUpService` | `enableTotp` | **Invalid TOTP Code** | `stepup_enroll_failed` | error | **HIGH** | Security (Misplaced in Audit Log) |
| `StepUpService` | `hasGrant` | **Context Risk Change** | `stepup_risk_mismatch` | error | **HIGH** | Security (Misplaced in Audit Log) |
| `StepUpService` | `enableTotp` | TOTP Enrolled | `stepup_enrolled` | N/A | **MEDIUM** | Operational (Double Write) |
| `StepUpService` | `issuePrimaryGrant` | Grant Issued | `stepup_primary_issued` | N/A | **MEDIUM** | Operational (Double Write) |
| `StepUpService` | `issueScopedGrant` | Grant Issued | `stepup_scoped_issued` | N/A | **MEDIUM** | Operational (Double Write) |
| `StepUpService` | `logDenial` | Grant Denied | `stepup_denied` | warning | **MEDIUM** | Operational (Double Write) |
| `StepUpService` | `hasGrant` | Single-Use Consumed | `stepup_grant_consumed` | N/A | **MEDIUM** | Operational (Double Write) |
| `AdminAuthenticationService` | `login` | Login Success | `login_credentials_verified` | N/A | **MEDIUM** | Operational (Double Write) |

---

## 2. Call Graph (Textual)

**A) Authorization Flow (Critical Risk)**
> **Controller** (`AuthorizationGuardMiddleware`)
> → **Service** (`AuthorizationService::checkPermission`)
> → **Logger** (`TelemetryAuditLoggerInterface::log`)
> → **DB** (`audit_logs` table)
>
> *Status:* **Synchronous / Best-Effort**. If the logger fails (e.g., DB full), the exception is swallowed by `PdoTelemetryAuditLogger`, and the user is granted access **without any audit record**.

**B) Step-Up Security Failures (Misplacement Risk)**
> **Controller** (`UiStepUpController` / `StepUpController`)
> → **Service** (`StepUpService::verifyTotp` etc.)
> → **Private Method** (`logSecurityEvent`)
> → **Logger** (`TelemetryAuditLoggerInterface::log`)
> → **DB** (`audit_logs` table)
>
> *Status:* **Synchronous / Best-Effort**. Security failures are written to the audit log table instead of `security_events`.

**C) Double-Write Flows (Operational Noise)**
> **Service** (`AdminAuthenticationService`, `StepUpService`)
> → **1. Authoritative Writer** (`audit_outbox`)
> → **2. Telemetry Logger** (`audit_logs`)
>
> *Status:* **Redundant**. The system writes the same event twice: once transactionally to the outbox, and once purely for telemetry/legacy compatibility to `audit_logs`.

---

## 3. Risk Classification

### 🔴 HIGH RISK (Authority & Security Impact)
**Locations:** `AuthorizationService`, `StepUpService` (Failure paths)

*   **Authorization:** Access grants are **solely** recorded via this non-authoritative logger. A failure here means "Ghost Access" (access without record).
*   **Step-Up Failures:** Security signals (invalid TOTP, risk mismatch) are buried in `audit_logs` via a best-effort channel, potentially bypassing security monitoring alert rules that watch `security_events`.

### 🟡 MEDIUM RISK (Operational Confusion)
**Locations:** `AdminAuthenticationService`, `StepUpService` (Success paths)

*   **Double Writes:** These create data duplication. While not dangerous per se, they confuse the "Source of Truth". Analysts might query `audit_logs` (non-authoritative) and miss data that exists in `audit_outbox` (authoritative) if the telemetry write failed.

### 🟢 LOW RISK
*   None. All usages involve sensitive domains (Auth, Step-Up).

---

## 4. Why This Is Non-Authoritative

All usages mapped above rely on `PdoTelemetryAuditLogger`, which contains the following logic:

```php
try {
    $stmt->execute(...);
} catch (\Throwable $e) {
    // swallow — telemetry MUST NOT break flow
}
```

This **"Swallow-All"** behavior disqualifies it from being an authoritative audit trail.
For `AuthorizationService`, this is critical because it is the **only** audit trail.

---

> “This inventory is exhaustive to the best of my analysis.”
