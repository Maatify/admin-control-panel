# Activity Logs Inventory Report

## 1️⃣ Inventory Table

| Class Name | Method Name | Endpoint / Route | Action Description | Activity Log Present? | Reason (if NO) | Suggested Event Name |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `AdminController` | `create` | `POST /api/admins/create` | Admin creates a new admin user | NO | Admin CRUD operation | `admin_created` |
| `AdminController` | `addEmail` | `POST /api/admins/{id}/emails` | Admin adds an email address to an admin | NO | Admin email add/remove | `admin_email_added` |
| `AdminEmailVerificationController` | `verify` | `POST /api/admins/{id}/emails/verify` | Admin verifies an email address | NO | Admin CRUD/Update (Currently uses Audit Log only) | `admin_email_verified` |
| `AdminNotificationPreferenceController` | `upsertPreference` | `PUT /api/admins/{admin_id}/preferences` | Admin updates notification preferences | NO | Admin settings / preferences updates | `admin_preference_updated` |
| `AdminNotificationReadController` | `markAsRead` | `POST /api/admin/notifications/{id}/read` | Admin marks a notification as read | NO | Admin settings / operational action | `admin_notification_read` |
| `Api\SessionRevokeController` | `__invoke` | `DELETE /api/sessions/{session_id}` | Admin manually revokes a session | NO | Manual session revocation (Currently uses Telemetry & Audit) | `session_revoked` |
| `Api\SessionBulkRevokeController` | `__invoke` | `POST /api/sessions/revoke-bulk` | Admin manually revokes multiple sessions | NO | Manual session revocation (Currently uses Telemetry & Audit) | `session_bulk_revoked` |
| `RoleAssignmentService` | `assignRole` | N/A (Service Internal) | Admin assigns a role to another admin | NO | Role assignment (Currently uses Audit Log only) | `admin_role_assigned` |
| `AuthController` | `login` | `POST /api/auth/login` | Admin logs in | YES | **INCORRECTLY LOGGED** - Authentication should NOT be Activity Log | N/A (REMOVE) |

---

## 2️⃣ Grouped Summary

### Admin Management
*   **Create Admin:** `AdminController::create`
*   **Add Email:** `AdminController::addEmail`
*   **Verify Email:** `AdminEmailVerificationController::verify` (Audit Log only)

### Sessions
*   **Revoke Session:** `Api\SessionRevokeController` (Telemetry/Audit only)
*   **Bulk Revoke:** `Api\SessionBulkRevokeController` (Telemetry/Audit only)

### Roles / Permissions
*   **Assign Role:** `RoleAssignmentService::assignRole` (Audit Log only)
*   *Note: Role removal and Permission changes appear to be missing from the codebase entirely.*

### Settings / Preferences
*   **Update Preferences:** `AdminNotificationPreferenceController::upsertPreference`
*   **Mark Notification Read:** `AdminNotificationReadController::markAsRead`

### Other
*   **Authentication (Misuse):** `AuthController::login` is currently logging to Activity Log, which violates the canonical definition.

---

## 3️⃣ Risk Notes

*   **Telemetry Misuse in Sessions:** `SessionRevokeController` and `SessionBulkRevokeController` rely on `HttpTelemetryRecorderFactory` for operational tracing. Telemetry is non-authoritative and ephemeral. This is a high-risk gap for critical security actions like session revocation.
*   **Audit vs. Activity Confusion:** Several critical actions (`verify`, `assignRole`, `revokeSession`) are logging to `AuthoritativeSecurityAuditWriterInterface` but missing from the user-facing Activity Log (`ActivityLogService`). This creates a fragmented history where some operational actions are hidden from the "Activity" view.
*   **Authentication Noise:** `AuthController` is polluting the Activity Log with `LOGIN_SUCCESS` events. This dilutes the value of the Activity Log (which should focus on *operational* actions) and violates the canonical definition.

---

This inventory is exhaustive to the best of my analysis.
