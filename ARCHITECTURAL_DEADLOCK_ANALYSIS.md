# ARCHITECTURAL DEADLOCK ANALYSIS

## 1️⃣ Mandatory Rule Map

1.  **Auth Boundary (Security):** `AuthSurface::isApi($request)` is the **single source of truth** for distinguishing API vs Web traffic.
2.  **Kernel Boundary (Infrastructure):** Infrastructure layers (Repositories) must **never** leak implementation details (`PDOException`) to the Application layer.
3.  **Unified Error Envelope (Contract):** All API errors must return `{ "success": false, "error": { ... } }` with `Content-Type: application/json`.
4.  **Web UX Contract (Legacy):** Web routes (non-API) must **redirect** (302) on authentication failure, never return JSON.
5.  **Fail-Open Logging (Resilience):** Telemetry and Audit logging must swallow errors to preventing crashing the main request flow.

## 2️⃣ Required Changes for Full Unification

To fully adopt `Maatify\Exceptions`, we must:
1.  **Refactor Middleware:** `SessionGuardMiddleware` must stop manually returning `Response` objects and instead throw `HttpUnauthorizedException`.
2.  **Refactor Controllers:** Remove all `try/catch` blocks that manually format JSON. Throw Domain Exceptions instead.
3.  **Refactor Repositories:** Wrap all PDO calls in `try/catch` and throw `DatabaseConnectionMaatifyException`.
4.  **Update Global Handler:** The Global Handler in `http.php` needs to handle the exceptions thrown by Middleware.

## 3️⃣ Conflict Matrix

| Rule A | Rule B | Conflict? | Why |
| :--- | :--- | :--- | :--- |
| **Unified Exception (Middleware)** | **Web UX Contract** | 🔴 **YES** | If Middleware throws `HttpUnauthorizedException` for a Web Route, the Global Handler (`http.php`) currently **forces JSON response**, breaking the Redirect requirement. |
| **Unified Exception (Controller)** | **Unified Envelope** | 🟢 NO | Throwing exceptions delegates formatting to the handler, which enforces the envelope (Fixes current violations). |
| **Kernel Boundary (Repo)** | **Fail-Open Logging** | 🟢 NO | Repositories throw Domain Exceptions. Services catch them if "Fail-Open" is needed (e.g. AuditTrail). Business logic lets them bubble. |
| **Auth Surface** | **Global Handler** | 🔴 **YES** | The Global Handler in `http.php` does **not** currently check `AuthSurface::isApi()`. It treats *all* exceptions as JSON API errors. |

## 4️⃣ Deadlock Status
**CONFIRMED**

A strict deadlock exists between **Unified Exception Handling** and **Web UX Contract** because the current Global Handler (`http.php`) is incapable of performing content negotiation or context-aware error rendering (Redirect vs JSON).

## 5️⃣ Resolution Options

### Option A: The "Smart Handler" (Recommended)
**Refactor `http.php`** to check `AuthSurface::isApi($request)` (or Accept header) inside the exception handler closure.
- **If API:** Return Unified JSON (Current behavior).
- **If Web:** Return 302 Redirect (for Auth errors) or HTML Error Page (for others).
*Effect:* Breaks the deadlock. Allows Middleware to throw exceptions universally.

### Option B: The "Dual Middleware"
Split `SessionGuardMiddleware` into `ApiSessionGuard` (throws Exception) and `WebSessionGuard` (returns Redirect).
*Effect:* Resolves conflict but duplicates logic. Increases maintenance surface.

### Option C: The "Exception Payload"
Subclass `HttpUnauthorizedException` into `WebAuthException` that carries a redirect URL.
*Effect:* Dirty. Pollutes the Exception domain with HTTP concerns.

## 6️⃣ Final Governance Verdict

**Deadlock Resolved via Option A.**

The repository **CAN** fully adopt `Maatify\Exceptions`, provided that the **Global Exception Handler (`http.php`) is upgraded** to be context-aware.

Currently, it is a "dumb" JSON-only handler. It must become "smart" to support the Unified Model across both API and Web surfaces without breaking the Web UX contract.

**Without this upgrade, adopting MaatifyExceptions in Middleware will break the Web Admin Panel.**
