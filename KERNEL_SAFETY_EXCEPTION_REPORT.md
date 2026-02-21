# KERNEL SAFETY EXCEPTION REPORT

## 1️⃣ Kernel Breach Risks

*   **Risk:** `SessionBulkRevokeController` manually constructs an error response that **violates the Unified Envelope Contract**.
    *   **Breach:** Returns `{"error": "message"}` instead of `{"success": false, "error": {...}}`.
    *   **Impact:** Clients expecting the unified structure (as promised by `Maatify\Exceptions` and `JsonResponseFactory`) will fail to parse this error.

*   **Risk:** `PdoRememberMeRepository` (and others) leaks `PDOException` directly to the Global Handler.
    *   **Breach:** Exposes infrastructure details (SQLSTATE) in logs and potentially responses if debug mode is on.
    *   **Impact:** Violates Layer Boundaries. Infrastructure implementation details must not leak past the Repository boundary.

*   **Risk:** `SessionGuardMiddleware` returns manual 401/403 responses.
    *   **Breach:** Inconsistent error format (similar to Controllers).
    *   **Impact:** Breaks Middleware Contract (middleware should throw exceptions for auth failures, letting the global handler standardize the response).

## 2️⃣ Boundary Violations

| File | Layer | Contract Impact | Risk Level | Kernel Safe? |
| :--- | :--- | :--- | :--- | :--- |
| `SessionBulkRevokeController.php` | Controller | **Breaks Envelope** | **CRITICAL** | ❌ NO |
| `SessionRevokeController.php` | Controller | Manual Mapping (OK) | LOW | ✅ YES |
| `PdoRememberMeRepository.php` | Infrastructure | Leaks `PDOException` | MEDIUM | ❌ NO |
| `SessionGuardMiddleware.php` | Middleware | **Breaks Envelope** | HIGH | ❌ NO |
| `JsonResponseFactory.php` | Http | Enforces Contract | N/A | ✅ YES |
| `http.php` (Global Handler) | Bootstrap | Enforces Contract | N/A | ✅ YES |

## 3️⃣ Safe Refactor Zones

*   **Controllers:** Replacing manual `json_encode` with `throw new MaatifyException(...)` is **SAFE** and **RECOMMENDED**. It aligns the implementation with the intended contract.
*   **Middleware:** Replacing manual `Response` returns with `throw new HttpUnauthorizedException(...)` is **SAFE**.
*   **Repositories:** Wrapping `PDO` calls in `try/catch` and re-throwing `DatabaseConnectionMaatifyException` is **SAFE** and improves encapsulation.

## 4️⃣ High-Sensitivity Areas

*   **`SessionGuardMiddleware`:**
    *   This is the Auth Boundary. Changes here must be tested rigorously.
    *   Currently, it returns manual JSON for API requests. Switching to `HttpUnauthorizedException` relies on the Global Handler to produce the JSON. This dependency is correct but critical.

*   **`AdminEmailVerificationService`:**
    *   Uses `InvalidIdentifierStateException`. This is a Domain Exception. Changing its behavior affects business rules. Refactoring usage here should be purely structural (e.g., ensuring correct status codes), not logical.

## 5️⃣ Migration Impact on Public Contracts

*   **DTOs:** No impact. DTOs are data carriers.
*   **API Behavior:** **SIGNIFICANT POSITIVE IMPACT.**
    *   Currently: Errors are inconsistent (some unified, some manual/legacy).
    *   After Migration: ALL errors will follow the Unified Envelope.
    *   **Note:** This is a *breaking change* for clients that *only* handle the legacy `{"error": "..."}` format. However, it is a *fix* towards the documented standard.

## 6️⃣ Safe Adoption Strategy

1.  **Phase 1 (Infrastructure):** Wrap Repository PDO calls. Stop the leaks. This is purely internal and invisible to API clients (except 500s become clean system errors).
2.  **Phase 2 (Middleware):** Update `SessionGuardMiddleware` to throw `HttpUnauthorizedException`. Verify Global Handler output matches current manual output structure (except for wrapper).
3.  **Phase 3 (Controllers):** Update Controllers one by one. Start with `SessionBulkRevokeController`. Replace manual JSON with `MaatifyException` throws.

**Verdict:** Adopting Maatify\Exceptions is **REQUIRED** to restore Kernel Safety. The current state violates the Kernel's own contract regarding error response uniformity.
