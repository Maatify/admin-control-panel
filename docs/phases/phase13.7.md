# Phase 13.7: Auth Boundary Lock & Regression Guard

## 🛑 FROZEN BEHAVIOR
**Authentication behavior is frozen as of Phase 13.7.**
**Any modification beyond this point is a breaking change.**

This document defines the strict, non-negotiable boundaries for authentication and session management.

## 1. Web vs API Detection
The system classifies requests as **Web** or **API** based *strictly* on the transport of credentials. The `Accept` header is **ignored** for security decisions.

| Mode | Detection Rule |
| :--- | :--- |
| **API** | Request has `Authorization` header (even if empty or invalid). |
| **Web** | Request has **NO** `Authorization` header. Defaults to Cookie logic. |

> **Note:** A request with `Authorization: Bearer <token>` is **always** API. A request with no headers (accessing `/dashboard`) is **always** Web.

## 2. Authentication Boundaries

| Scenario | Mode | Behavior | Response |
| :--- | :--- | :--- | :--- |
| **Guest → Protected** | Web | User tries to access `/dashboard` without cookie. | **Redirect** to `/login` |
| **Guest → Protected** | API | Client requests `/admins` with invalid/missing Bearer. | **401 Unauthorized** (JSON) |
| **Auth → Guest** | Web | Logged-in user visits `/login`. | **Redirect** to `/dashboard` |
| **Auth → Guest** | API | Authenticated client hits `/auth/login`. | **403 Forbidden** (JSON `Already authenticated`) |
| **Session Failure** | Web | Session expired, revoked, or invalid. | **Redirect** to `/login` |
| **Session Failure** | API | Session expired, revoked, or invalid. | **401 Unauthorized** (JSON) |
| **Step-Up Required** | Web | User accesses sensitive route without 2FA. | **Redirect** to `/2fa/verify` (or setup) |
| **Step-Up Required** | API | Client accesses sensitive route without Scope. | **403 Forbidden** (JSON `STEP_UP_REQUIRED`) |

## 3. Middleware Ordering (Mandatory)
The execution order is enforced to ensure security layers build upon each other correctly.

1.  `RememberMeMiddleware` (Runs first: Restores session from long-lived cookie)
2.  `SessionGuardMiddleware` (Runs second: Validates Identity, establishes `admin_id`)
3.  `SessionStateGuardMiddleware` (Runs third: Validates State `ACTIVE`, enforces Step-Up)
4.  `ScopeGuardMiddleware` (Runs fourth: Enforces Granular Scopes e.g. `admin:write`)
5.  `AuthorizationGuardMiddleware` (Runs last: Enforces RBAC Permissions e.g. `admin.create`)

## 4. Exception Canonicalization
Only the following exceptions are valid for session validity failures. Generic exceptions must be caught or are considered bugs.

*   `App\Domain\Exception\InvalidSessionException`
*   `App\Domain\Exception\ExpiredSessionException`
*   `App\Domain\Exception\RevokedSessionException`

## 5. Regression Guards
*   **Explicit Responses:** Middleware must return `ResponseInterface` directly for failures (401/403/302) rather than throwing exceptions that rely on global handlers.
*   **Strict Extraction:** API Guards must *only* look at Headers. Web Guards must *only* look at Cookies. No fallback or mixing.
