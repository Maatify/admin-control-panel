# Context & Logging Status Report

## 1) Current Architecture Inventory (Context & Logging)

### 1.1 Request ID
*   **Generation**: `App\Http\Middleware\RequestIdMiddleware::resolveRequestId`
*   **Middleware Order**: Runs first (added last in `routes/web.php`).
*   **Attachment**:
    *   Request Attribute: `request_id`
    *   Response Header: `X-Request-ID`
*   **Status**: Guaranteed ✅

### 1.2 Context Objects
*   `App\Context\AdminContext`:
    *   Fields: `public int $adminId`
    *   Used: Yes (e.g., `AdminActivityLogService`).
*   `App\Context\RequestContext`:
    *   Fields: `requestId`, `ipAddress`, `userAgent`
    *   Used: Yes (e.g., `AdminActivityLogService`, `HttpContextProvider`).

### 1.3 Context Wiring Approach
**Mechanism**:
1.  `RequestIdMiddleware`: Sets `request_id` attribute.
2.  `ContextProviderMiddleware`: Injects `ServerRequestInterface` (with `request_id`) into DI Container.
3.  `SessionGuardMiddleware` (Inner): Validates session and sets `admin_id` attribute on Request.
4.  `HttpContextProvider`: Resolved from Container (injects `ServerRequestInterface`).
5.  **Issue**: `HttpContextProvider` receives the *original* Request (from Step 2), lacking `admin_id` (from Step 3).
    *   `$this->contextProvider->request()`: Works ✅ (RequestId is present early).
    *   `$this->contextProvider->admin()`: Fails ❌ (AdminId missing in injected request).

**Pipeline**:
`RequestIdMiddleware` (sets ID) → `ContextProviderMiddleware` (injects Request) → `SessionGuardMiddleware` (sets AdminID) → Controller (resolves `HttpContextProvider` with **stale** Request).

---

## 2) End-to-End Verification (Login Flow)

### 2.1 Web Login Controller
**File**: `App\Http\Controllers\Web\LoginController.php`

*   **Blind Index**: ✅ `$this->cryptoService->deriveEmailBlindIndex($dto->email)`
*   **Auth Call**: ✅ `$this->authService->login(...)`
*   **Logging**:
    *   `LOGIN_SUCCESS`: ✅ (Activity Log wired manually via `AdminActivityLogService`).
    *   `LOGIN_FAILED`: ⚠️ (Security Event logged by Service, but **Activity Log** missing in Controller).

### 2.2 API Auth Controller
**File**: `App\Http\Controllers\AuthController.php`

*   **Blind Index**: ✅
*   **Auth Call**: ✅
*   **Logging**:
    *   `LOGIN_SUCCESS`: ✅
    *   `LOGIN_FAILED`: ⚠️ (Security Event logged by Service, but **Activity Log** missing in Controller).

---

## 3) Logging Pipeline Verification

### 3.1 Activity Log
*   **Service**: `App\Domain\ActivityLog\Service\AdminActivityLogService`
*   **Underlying Service**: `App\Modules\ActivityLog\Service\ActivityLogService`
*   **Writer**: `App\Infrastructure\ActivityLog\MySQLActivityLogWriter`
*   **Invocation**: Verified in Login Controllers.
*   **Bindings**: `request_id`, `ip_address`, `user_agent` are wired correctly to `activity_logs` table. ✅

### 3.2 Audit + Security Events (Metadata compliance check)

| Location (File) | Logger Type | Metadata (IP/UA/ReqID) | Status | Minimal Fix |
| :--- | :--- | :--- | :--- | :--- |
| `PdoAuthoritativeAuditWriter.php` | Audit | IP: ✅ (Payload)<br>UA: ✅ (Payload)<br>ReqID: ❌ (Random UUID used) | ❌ | Inject `requestId` into `AuditEventDTO` payload or top-level. |
| `SecurityEventRepository.php` | Security | IP: ✅ (Column)<br>UA: ✅ (Column)<br>ReqID: ❌ (Missing) | ❌ | Add `request_id` to `context` JSON blob. |

---

## 4) Issues & Fix Plan

### 1. Context Wiring Broken (Blocker)
`ContextProviderMiddleware` injects the Request into the container *before* Authentication middleware sets the `admin_id`. `HttpContextProvider` therefore sees a stale request and cannot resolve `AdminContext`.

*   **Fix**: Update `SessionGuardMiddleware` (and `AuthorizationGuardMiddleware` if applicable) to update the `ServerRequestInterface` entry in the Container after modifying the request.
    *   **File**: `App\Http\Middleware\SessionGuardMiddleware.php`
    *   **Change**: `$container->set(ServerRequestInterface::class, $request)` (if Container is available).

### 2. Missing Request ID in Audit/Security Logs (High)
*   **Fix (Audit)**:
    *   **File**: `App\Domain\Service\AdminAuthenticationService.php` (and others using `AuditEventDTO`)
    *   **Change**: Add `'request_id' => ...` to the payload array. Requires passing `requestId` to Service (e.g. via `RequestContext` or `ClientInfoProvider` update).
*   **Fix (Security)**:
    *   **File**: `App\Domain\Service\AdminAuthenticationService.php`
    *   **Change**: Add `'request_id' => ...` to `SecurityEventDTO` context array.

### 3. Missing `LOGIN_FAILED` Activity Log (Medium)
*   **Fix**:
    *   **Files**: `App\Http\Controllers\Web\LoginController.php`, `App\Http\Controllers\AuthController.php`
    *   **Change**: Add `$this->adminActivityLogService->log(...)` in `catch` blocks for `InvalidCredentialsException` / `AuthStateException`. Note: Actor is unknown, so might need `AnonymousContext` or log as System (or just rely on Security Event). **Recommendation**: Security Event is sufficient for failed login (AuthService does this). Activity Log requires an Actor. If no Actor, it's not an Admin Activity. **Downgrading to Non-Issue** (Security Log covers it).
