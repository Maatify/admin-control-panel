# Preconditions Check — Enum Path + DB Constraint

## 1. Enum Source
**File Path:** `app/Modules/SecurityEvents/Enum/SecurityEventTypeEnum.php`
**Namespace:** `App\Modules\SecurityEvents\Enum`
**Existing Cases:**
- `LOGIN_FAILED` ('login_failed')
- `LOGIN_SUCCEEDED` ('login_succeeded')
- `LOGOUT` ('logout')
- `STEP_UP_FAILED` ('step_up_failed')
- `STEP_UP_SUCCEEDED` ('step_up_succeeded')
- `STEP_UP_NOT_ENROLLED` ('step_up_not_enrolled')
- `STEP_UP_INVALID_CODE` ('step_up_invalid_code')
- `STEP_UP_RISK_MISMATCH` ('step_up_risk_mismatch')
- `STEP_UP_ENROLL_FAILED` ('step_up_enroll_failed')
- `EMAIL_VERIFICATION_FAILED` ('email_verification_failed')
- `EMAIL_VERIFICATION_SUBJECT_NOT_FOUND` ('email_verification_subject_not_found')
- `PERMISSION_DENIED` ('permission_denied')
- `SESSION_INVALID` ('session_invalid')
- `SESSION_EXPIRED` ('session_expired')
- `PASSWORD_RESET_REQUESTED` ('password_reset_requested')
- `PASSWORD_RESET_FAILED` ('password_reset_failed')

## 2. Database Schema
**Table:** `security_events`
**Column:** `event_type`
**Definition:** `VARCHAR(100) NOT NULL`
**Constraints:** None (no `ENUM` or `CHECK` constraints found in `database/schema.sql`).

## 3. Decision
**GO**
Adding `case LEGACY_UNMAPPED = 'legacy_unmapped';` to `SecurityEventTypeEnum` is safe. The database column is a generic `VARCHAR(100)` and will accept the new string value without schema modification.
