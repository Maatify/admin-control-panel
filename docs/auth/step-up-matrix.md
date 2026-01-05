# Step-Up Authentication Matrix

**STATUS: FROZEN (Phase C2.2)**
**LOCKED SINCE:** v1.3.6

This document defines the canonical Scopes, TTLs, and enforcement rules for Step-Up Authentication (MFA).

---

## 1. Canonical Scopes

Only the following Scopes are recognized by the system.

| Scope | Purpose | TTL | Single Use |
| :--- | :--- | :--- | :--- |
| `LOGIN` | Primary Session Activation | 2 Hours | No |
| `SECURITY` | Sensitive Security Ops (e.g., Promote Admin) | 15 Minutes | No |
| `ROLES_ASSIGN` | Assigning Roles to Admins | 15 Minutes | No |
| `AUDIT_READ` | Viewing PII Audit Logs | 15 Minutes | No |
| `EXPORT_DATA` | Bulk Data Export | 15 Minutes | No |
| `SYSTEM_SETTINGS` | Modifying Global Config | 15 Minutes | No |

*Note: `LOGIN` is the "Primary Grant" required to transition a session from `PENDING_STEP_UP` to `ACTIVE`.*

---

## 2. Grant Storage & Architecture

- **Storage**: Grants are stored in MySQL table `step_up_grants`.
- **Atomic Truth**: Grants are authoritative only if present in the database.
- **Redis Usage**: Phase C2.1 confirms usage of MySQL instead of Redis for grants to ensure transactional integrity with Audit Logs. This is the **Frozen Implementation**.

---

## 3. Risk Context Binding

Every grant is cryptographically bound to the client's context at issuance time.

- **Binding Factor**: `SHA-256(IP Address | User Agent)`
- **Enforcement**:
  - `StepUpService::hasGrant` recalculates the hash on every check.
  - If Mismatch:
    1. Grant is **Revoked**.
    2. Security Event `stepup_risk_mismatch` is logged.
    3. Access is **Denied**.

---

## 4. Invalidation Triggers

A Step-Up Grant is invalidated when:

1. **TTL Expiry**: `expires_at` timestamp is passed.
2. **Context Change**: IP or User Agent changes.
3. **Session Revocation**: The parent Session ID is revoked.
4. **Explicit Revocation**: Admin logs out or specific grants are wiped.
5. **Consumption**: If the grant is marked `Single Use` (none currently defined in enum, but supported by schema), it is deleted after first verification.

---

## 5. What Step-Up Does NOT Grant

- **Permission**: Step-Up proves *identity* (AuthN), not *permission* (AuthZ). A user with `Scope::SECURITY` but without `can_manage_security` permission is still denied.
- **Session Extension**: Obtaining a Step-Up grant does not extend the base session lifetime.
- **Immunity**: Step-Up grants do not bypass Recovery-Locked Mode.
