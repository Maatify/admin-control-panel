# Authentication Failure Semantics

**STATUS: FROZEN (Phase C2.2)**
**LOCKED SINCE:** v1.3.6

This document defines the canonical responses and logging behaviors for authentication failures. The system adheres to **Fail-Closed** and **Information Hiding** principles.

---

## 1. User-Facing Errors

The user interface (Web and API) MUST expose only generic error messages to prevent enumeration.

| Scenario | Generic Message (User) |
| :--- | :--- |
| Invalid Email | "Authentication failed." |
| Invalid Password | "Authentication failed." |
| User Not Found | "Authentication failed." |
| Account Unverified | Redirects to `/verify-email`. |
| Session Expired | Redirects to Login / 401 JSON. |
| Step-Up Required | Redirects to 2FA / 403 JSON (`STEP_UP_REQUIRED`). |
| Recovery Locked | "Action blocked by Recovery-Locked Mode." (or Generic 503) |

---

## 2. Internal Failure Reasons (Audit/Log Only)

Internal logs contain specific reasons for debugging and forensic analysis. These MUST NOT be exposed to the user.

| Reason Code | Meaning | Severity | Source |
| :--- | :--- | :--- | :--- |
| `user_not_found` | Blind Index lookup returned null. | WARNING | `AdminAuthenticationService` |
| `not_verified` | Identifier exists but status is not `VERIFIED`. | WARNING | `AdminAuthenticationService` |
| `invalid_password` | Password hash verification failed. | WARNING | `AdminAuthenticationService` |
| `remember_me_theft_suspected` | Selector found but validator hash mismatch. | CRITICAL | `RememberMeService` |
| `stepup_risk_mismatch` | Grant exists but IP/UA changed. | HIGH | `StepUpService` |
| `recovery_action_blocked` | Action attempted while in Recovery-Locked mode. | CRITICAL | `RecoveryStateService` |
| `stepup_denied` | Required scope not present. | WARNING | `StepUpService` |

---

## 3. Fail-Closed Behavior

The system is designed to fail securely in the event of infrastructure or logic failure.

- **Transaction Failures**: If the Audit Log cannot be written (MySQL Transaction), the entire Authentication operation rolls back. The user is **not** logged in.
- **Risk Context Changes**: If the user's IP or User Agent changes during a Step-Up session, the grant is immediately invalidated. The system assumes a session hijacking attempt.
- **Crypto Failures**: If Random Byte generation fails (e.g., OS CSPRNG failure), the operation throws a `RuntimeException` and aborts.
- **Recovery Mode**: If the environment configuration is weak (short keys), the system defaults to **Locked Mode**, blocking all access.

---

## 4. Information Hiding

To prevent User Enumeration and Timing Attacks:

- **Timing**: `password_verify` is only called if the user exists. While timing differences may technically exist, the system does not artificially pad time (accepted trade-off for DoS resistance).
- **Existence**: Login attempts for non-existent users log `user_not_found` internally but return the exact same generic error as a wrong password.
- **Blind Index**: The raw Blind Index is logged in `login_failed` events (Warning level) to allow debugging without revealing the plaintext email.

---

## 5. Forbidden Implementations

- **NO** detailed error messages in HTTP responses (e.g., "Password incorrect").
- **NO** "Account exists" hints in registration or login flows.
- **NO** falling back to "Open" if a check fails. (e.g., "If Redis down, allow").
- **NO** bypassing Audit Logs for "read-only" failures. Failed attempts are writes to the Security Log.
