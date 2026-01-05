# Authentication Flow Reference

**STATUS: FROZEN (Phase C2.2)**
**LOCKED SINCE:** v1.3.6

This document defines the strictly enforced authentication flows in the Admin Control Panel. Any deviation from this document is considered a security violation.

---

## 1. Login Flow

The login process is deterministic, fail-closed, and audit-bound.

### Conceptual Flow

1. **Recovery Check**: System verifies if `RECOVERY_MODE` is active.
   - If Active: Action `login` is blocked. `RecoveryLockException` is thrown.
2. **Blind Index Calculation**: Email is converted to Blind Index (HMAC-SHA256).
3. **Identity Lookup**: Blind Index is queried against the database.
   - If Not Found: Generic failure. Security Event `login_failed` (reason: `user_not_found`).
4. **Verification Status Check**:
   - If `status !== VERIFIED`: `AuthStateException` is thrown. Security Event `login_failed` (reason: `not_verified`).
5. **Credential Verification**:
   - Password hash is retrieved and verified (Argon2id).
   - If Invalid: `InvalidCredentialsException`. Security Event `login_failed` (reason: `invalid_password`).
6. **Session Creation (Transactional)**:
   - A new opaque session token is generated.
   - `admin_sessions` table is updated.
   - Audit Event `login_credentials_verified` is written to Outbox.
   - **Transaction Commit**.
7. **Cookie Issuance**:
   - `auth_token` cookie is set with `HttpOnly`, `SameSite=Strict`, `Path=/`, and `Secure` (if HTTPS).
   - Expiration matches backend session TTL.

### Forbidden Behavior

- No "User not found" messages are ever displayed to the user.
- No partial sessions are created on failure.
- No "Account Locked" hints are exposed (Internal logging only).

---

## 2. Logout Flow

Logout is an explicit revocation process ensuring immediate invalidation.

### Conceptual Flow

1. **Context Resolution**: Current `admin_id` and `auth_token` are resolved.
2. **Security Logging**: `admin_logout` event is logged (Info severity).
3. **Session Revocation (Transactional)**:
   - The specific session token is revoked in `admin_sessions`.
   - Audit Event `session_revoked` is written to Outbox.
   - **Transaction Commit**.
4. **Remember-Me Revocation**:
   - If a remember-me cookie is present, its selector is extracted.
   - The specific token is revoked via `revokeBySelector`.
5. **Cookie Cleanup**:
   - `auth_token` set to `Max-Age=0`.
   - `remember_me` set to `Max-Age=0`.

---

## 3. Remember-Me Interaction

Remember-Me allows session restoration on a trusted device without re-entering password.

### Conceptual Flow

1. **Trigger**: `RememberMeMiddleware` detects missing `auth_token` but present `remember_me` cookie.
2. **Validation**:
   - Selector is used to find the token record.
   - Validator hash is compared (SHA-256).
3. **Theft Detection**:
   - If Selector exists but Validator hash mismatches:
     - **CRITICAL SECURITY EVENT**: `remember_me_theft_suspected`.
     - The selector is immediately deleted.
     - `InvalidCredentialsException` is thrown.
4. **Rotation**:
   - Old token is deleted.
   - New Selector/Validator pair is issued.
   - New Session is created.
5. **Restoration**:
   - Request is injected with new `auth_token`.
   - Response includes new `auth_token` cookie (Session Cookie) and new `remember_me` cookie (Persistent).

### Intentional Behavior
- The restored `auth_token` cookie via Remember-Me is a **Session Cookie** (no Max-Age), unlike the **Persistent Cookie** issued during explicit Login. This is an accepted behavior; closing the browser clears the session cookie, triggering Remember-Me again on next visit.

---

## 4. Step-Up Authentication

Step-Up is a secondary verification layer for high-risk actions.

### Conceptual Flow

1. **Gate**: `SessionStateGuardMiddleware` checks `step_up_grants` (MySQL).
   - Checks for `Scope::LOGIN` grant.
   - Checks Risk Context (IP + User Agent hash).
2. **State**:
   - If Grant exists and matches context: `SessionState::ACTIVE`.
   - If Missing or Mismatch: `SessionState::PENDING_STEP_UP`.
3. **Enforcement**:
   - API: Returns 403 `STEP_UP_REQUIRED`.
   - Web: Redirects to `/2fa/verify`.

### Forbidden Behavior
- No "Verified" flag is stored in the PHP Session.
- No "Verified" flag is stored in the `admin_sessions` table.
- Grants are never valid if the Risk Context changes.

---

## 5. Recovery-Locked Mode

A fail-safe state protecting the system during cryptographic compromise or environment failure.

### Triggers
1. **Manual**: DB state `RECOVERY_LOCKED`.
2. **Environment**: `RECOVERY_MODE=true`.
3. **Weak Crypto**: `EMAIL_BLIND_INDEX_KEY` is empty or too short.

### Enforcement
- **Blocked Actions**: Login, OTP Verify, OTP Resend, Step-Up, Role Assignment, Permission Change.
- **Audit**: All blocked attempts emit `recovery_action_blocked` (CRITICAL).
- **Exit**: Requires manual intervention or correcting the environment. No automated exit.
