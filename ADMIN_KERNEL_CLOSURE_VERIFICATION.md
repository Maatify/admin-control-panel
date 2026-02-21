# AdminKernel Error Surface Closure Verification

**Date:** 2024-10-24
**HEAD Commit:** 7503eda6938cf11bd206f54404853fdf96477f3c
**Mode:** STRICT CLOSURE

## 1. SessionRevocationService (Fixed 500 Leak)
*   **Before:** Threw `DomainException` (SPL) -> HTTP 500.
*   **After:** Throws `SessionRevocationFailedException` (extends `AdminKernelBusinessRuleExceptionBase`) -> **HTTP 422**.
*   **Result:** Business rule violation ("Cannot revoke own session") is now correctly categorized as a client-side error (Unprocessable Entity) rather than a server crash.

## 2. SessionBulkRevokeController (Standardized)
*   **Before:**
    *   Manually constructed JSON `{ "error": "Current session not found" }` (401).
    *   Caught `DomainException` and returned `{ "error": $e->getMessage() }` (400).
*   **After:**
    *   Throws `InvalidSessionException` -> **HTTP 401** (Unified JSON).
    *   Bubbles `SessionRevocationFailedException` -> **HTTP 422** (Unified JSON).
*   **Verification:** Frontend code (`sessions.js`, `admin_sessions.js`) uses individual DELETE requests via `revokeSession()` loop, confirmed by code inspection. `SessionBulkRevokeController` appears to be a separate/unused endpoint or intended for external API usage, so standardizing it is safe and improves consistency.

## 3. Languages*Controller (Fixed 500 Leak)
*   **Before:** Threw `RuntimeException` ("Invalid validated payload") -> HTTP 500.
*   **After:** Throws `HttpBadRequestException` -> **HTTP 400**.
*   **Result:** Input validation gaps (e.g., type mismatch after schema validation) now result in a generic Bad Request error instead of a System Error.

## 4. Constraints Verification
*   **STEP_UP_REQUIRED:** No changes made to middleware. Behavior preserved.
*   **JS/UI:** No changes made to `public/` assets.
*   **AuthController:** No changes made. Legacy login behavior preserved.
