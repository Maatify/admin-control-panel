# PHASE-B1 AUDIT: Controller + Middleware Error Unification Readiness

## A) Executive Summary

The backend audit reveals a **Fragmented** error response architecture. While the central exception handler (`Bootstrap/http.php`) strictly enforces the unified `Maatify` envelope (`{ success: false, error: {...} }`), significant legacy logic in **Middleware** and **Controllers** bypasses this system by manually constructing JSON responses (`json_encode`).

Approximately **17 files** (4 Middleware, 13 Controllers) emit raw JSON, creating a dual-contract state where clients receive different error shapes depending on whether the error occurs early (Middleware), in logic (Controller), or via exception (Global Handler). The frontend is partially hardened via `ErrorNormalizer`, but **manual fetch implementations** in page scripts remain vulnerable to losing specific error message details if the backend is normalized without corresponding JS updates.

---

## B) Findings Table

| Type | Full File Path | Method | Current Error Shape | Status | Risk | Fix Strategy |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **MW** | `app/Modules/AdminKernel/Http/Middleware/SessionStateGuardMiddleware.php` | `process` | `{ "error": "...", "code": "STEP_UP_REQUIRED", ... }` | 401, 403 | 🔴 High | Throw `StepUpRequiredException` or `AuthException` to trigger global handler. |
| **MW** | `app/Modules/AdminKernel/Http/Middleware/GuestGuardMiddleware.php` | `process` | `{ "error": "Already authenticated." }` | 403 | 🟡 Med | Throw `PermissionDeniedException` or similar. |
| **MW** | `app/Modules/AdminKernel/Http/Middleware/SessionGuardMiddleware.php` | `process` | `{ "error": "Invalid session" }` | 401 | 🔴 High | Throw `HttpUnauthorizedException`. |
| **MW** | `app/Modules/AdminKernel/Http/Middleware/ScopeGuardMiddleware.php` | `process` | `{ "error": "Authentication required" }` | 401, 403 | 🔴 High | Throw `HttpUnauthorizedException` or `PermissionDeniedException`. |
| **CT** | `app/Modules/AdminKernel/Http/Controllers/Api/Sessions/SessionBulkRevokeController.php` | `__invoke` | `{ "error": "..." }` | 400, 401 | 🟡 Med | Throw `DomainException` or use `JsonResponseFactory->error()` (legacy mode needs update). |
| **CT** | `app/Modules/AdminKernel/Http/Controllers/AuthController.php` | `login` | `{ "error": "Invalid credentials" }` | 401 | 🟡 Med | Throw `LoginFailedException`. |
| **CT** | `app/Modules/AdminKernel/Http/Controllers/AdminNotificationPreferenceController.php` | `update` | `{ "error": "Invalid channel type" }` | 400 | 🟢 Low | Throw `ValidationFailedException`. |
| **CT** | `app/Modules/AdminKernel/Http/Controllers/StepUpController.php` | `verify` | `{ "status": "granted", ... }` | 200 | ⚪ None | Success response (keep or standardize success envelope). |
| **CT** | Various Query Controllers (10+ files) | `__invoke` | `json_encode($result)` | 200 | ⚪ None | Success paths uses manual encoding (technical debt, but low error-risk). |

---

## C) Step-Up Flow Inventory

The Step-Up (2FA) challenge flow is a critical dependency.

1.  **Trigger Source:** `SessionStateGuardMiddleware::process`
2.  **Current Payload:**
    ```json
    {
        "code": "STEP_UP_REQUIRED",
        "scope": "login"
    }
    ```
    *(Note: This is non-unified. Unified would be `{ error: { code: "STEP_UP_REQUIRED", ... } }`)*

3.  **Frontend Consumers:**
    - `public/assets/maatify/admin-kernel/js/pages/roles-create-rename-toggle.js`
    - `public/assets/maatify/admin-kernel/js/pages/admin_emails.js`
    - `public/assets/maatify/admin-kernel/js/pages/role-details-permissions.js`
    - ... (8 files total)

    **Status:** These consumers have been patched in Phase-B to use `ErrorNormalizer.getLegacyStepUpView()`, so they are **READY** for the backend to switch to the unified envelope.

---

## D) Migration Plan for PHASE-B2

**Objective:** Eliminate all manual `json_encode(['error' => ...])` calls.

### 1. Frontend Hardening (Pre-requisite)
**Task:** Update manual `fetch()` calls in page scripts (e.g., `admin_emails.js`) to use `ErrorNormalizer.getMessage(data)` instead of accessing `data.message` directly.
- **Reason:** If we unify the backend error first, `data.message` will disappear (moved to `data.error.message`), causing UI to show generic fallbacks.

### 2. Middleware Refactor
**Task:** Convert Middleware to **Throw Exceptions** instead of returning Responses.
- `SessionStateGuardMiddleware`: Throw `StepUpRequiredException` (Need to create this).
- `SessionGuardMiddleware`: Throw `HttpUnauthorizedException`.
- `GuestGuardMiddleware`: Throw `PermissionDeniedException`.

### 3. Controller Refactor
**Task:** Convert Controllers to Throw Exceptions.
- `SessionBulkRevokeController`: Throw `ValidationFailedException` or `EntityNotFoundException`.
- `AuthController`: Throw `LoginFailedException`.

### 4. Global Handler Update
**Task:** Ensure `StepUpRequiredException` maps to the unified envelope but preserves the specific `code` and `scope` in `meta` so the Frontend Bridge can read it.

---

## E) Completion Checklist

To declare Phase-B complete:

- [ ] `grep -r "json_encode.*error" app/Modules/AdminKernel` returns **ZERO** results.
- [ ] `grep -r "json_encode.*code.*STEP_UP" app/Modules/AdminKernel` returns **ZERO** results.
- [ ] All 4 Auth Middleware throw exceptions instead of returning responses.
- [ ] `StepUpRequiredException` exists and is handled in `http.php` with correct 403 status and unified body.
- [ ] Frontend manual fetch scripts use `ErrorNormalizer` for message extraction.
