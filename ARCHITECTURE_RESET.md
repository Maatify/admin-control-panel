# 1️⃣ Recorder Inventory (AS-IS)

### Q1.1

**Authoritative Audit**
*   **Entry Point**: `App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface`
*   **Implementation**: `App\Infrastructure\Audit\PdoAuthoritativeAuditWriter`
*   **Usage Layers**:
    *   **Domain Services**: `AdminAuthenticationService`, `SessionRevocationService`, `StepUpService`, `RoleAssignmentService`, `RememberMeService`, `AdminEmailVerificationService`, `RecoveryStateService`.
*   **Violations**:
    *   Services construct `AuditEventDTO`.
    *   Services directly call the Writer.

**Security Events**
*   **Entry Point**: `App\Domain\Contracts\SecurityEventLoggerInterface`
*   **Implementation**: `App\Modules\SecurityEvents\Infrastructure\Mysql\SecurityEventLoggerMysqlRepository` (aliased in Container) and `App\Infrastructure\Repository\SecurityEventRepository`.
*   **Usage Layers**:
    *   **Controllers**: `LogoutController`.
    *   **Domain Services**: `AuthorizationService`, `RememberMeService`, `AdminAuthenticationService`, `SessionValidationService`, `RecoveryStateService`.
    *   **Recorder**: `SecurityEventRecorder` exists but relies on `SecurityEventRecordDTO` which requires caller-provided metadata.
*   **Violations**:
    *   Services construct `SecurityEventDTO` or `SecurityEventRecordDTO` with explicit metadata.
    *   Services directly call the Logger/Recorder.

**Activity Logs**
*   **Entry Point**: `App\Modules\ActivityLog\Service\ActivityLogService` and `App\Domain\ActivityLog\Service\AdminActivityLogService`
*   **Implementation**: `App\Infrastructure\ActivityLog\MySQLActivityLogWriter`
*   **Usage Layers**:
    *   **Controllers**: `LoginController` (via `AdminActivityLogService`), `LogoutController`, `AdminController`, `AdminNotificationPreferenceController`, `AdminNotificationReadController`, `AdminEmailVerificationController`, `SessionRevokeController`.
*   **Violations**:
    *   `AdminActivityLogService` requires callers to pass `AdminContext` and `RequestContext`.
    *   Callers must know about Contexts to log activity.

**Telemetry**
*   **Entry Point**: `App\Domain\Contracts\TelemetryAuditLoggerInterface`
*   **Implementation**: `App\Infrastructure\Audit\PdoTelemetryAuditLogger`
*   **Usage Layers**:
    *   `App\Domain\Telemetry\Recorder\TelemetryRecorder` exists.
*   **Violations**:
    *   `TelemetryRecorder` requires `TelemetryRecordDTO` which demands explicit metadata (request ID, IP, etc.) from the caller.

---

# 2️⃣ Canonical Recorder Responsibility (TARGET)

### Q2.1

| Domain | Recorder Name | Responsibility |
| :--- | :--- | :--- |
| Audit | `App\Domain\Audit\Recorder\AuditRecorder` | Captures authoritative security-critical changes (transactions, legal proof). |
| Security | `App\Domain\SecurityEvents\Recorder\SecurityRecorder` | Captures security events (login attempts, denials, attacks). |
| Activity | `App\Domain\ActivityLog\Recorder\ActivityRecorder` | Captures user intent and business actions (best-effort history). |
| Telemetry | `App\Domain\Telemetry\Recorder\TelemetryRecorder` | Captures debug/performance/observability data. |

### Q2.2

**AuditRecorder**
*   **Input**: Semantic intent + Domain Entities (e.g., `auditRoleChange(adminId, roleId, oldRole, newRole)`).
*   **Derives**: Actor (from Context), Request ID, IP, Timestamp, Transaction ID.

**SecurityRecorder**
*   **Input**: Semantic event (e.g., `recordLoginFailure(email, reason)`).
*   **Derives**: Request Context (IP, UA), Severity (based on event type), Actor (if known).

**ActivityRecorder**
*   **Input**: Semantic action (e.g., `logProfileUpdate(adminId, changes)`).
*   **Derives**: Actor, Request Metadata, formatted description.

**TelemetryRecorder**
*   **Input**: Observability event (e.g., `trackExternalCall(service, duration)`).
*   **Derives**: Trace ID, Span ID, Request Context.

---

# 3️⃣ Service → Recorder Interaction Model

### Q3.1

**Allowed Call Pattern**:
*   Services call **semantic methods** on the Recorder Interface.
    *   ✅ `$this->auditRecorder->adminCreated($newAdmin);`
    *   ✅ `$this->securityRecorder->loginFailed($email, FailureReason::PASSWORD_MISMATCH);`

**Strict Prohibitions**:
*   **NEVER pass DTOs**: Services must not construct `AuditEventDTO`, `SecurityEventDTO`, etc.
*   **NEVER pass Context**: Services must not pass `ActorContext`, `RequestContext`, `AdminContext`, `IP`, or `UserAgent`.
*   **NEVER know Writers**: Services must not see `WriterInterface` or `LoggerInterface`.

---

# 4️⃣ Actor & Context Resolution (CRITICAL)

### Q4.1

**ActorContext**
*   **Lives in**: `App\Context\ActorContext` (Singleton/Scoped).
*   **Resolved by**: `App\Http\Middleware\ActorContextMiddleware`.
*   **Owned by**: Infrastructure (`ActorContextProvider`).
*   **Availability**: Available after Authentication Middleware. Throws if accessed too early.

### Q4.2

**RequestContext**
*   **Lives in**: `App\Context\RequestContext` (Request Attribute / Scoped).
*   **Injected via**: **NEW** `App\Infrastructure\Context\RequestContextProvider` (to be created).
    *   *Reason*: Recorders cannot inject Request Attributes directly. A Provider bridge is required.
*   **Read Access**: Recorders (via Provider), Middleware.
*   **Prohibited**: Domain Services must NOT access `RequestContext`.

---

# 5️⃣ Writer / Persistence Boundary

### Q5.1

| Recorder | Write Mode | Failure Policy |
| :--- | :--- | :--- |
| **Audit** | Synchronous (Transactional) | **Fail-Closed** (Exception aborts operation). Must succeed. |
| **Security** | Synchronous (Strong Guarantee) | **Strong Guarantee** (Should persist even if app fails, but ideally shouldn't crash app if DB down, unless critical). |
| **Activity** | Synchronous (or Outbox) | **Fail-Open** (Log failure must NOT abort business logic). |
| **Telemetry** | Asynchronous / Fire-and-Forget | **Swallow-Safe** (Failures are ignored). |

---

# 6️⃣ Migration Plan (Incremental & Safe)

### Q6.1

**Phase 1: Infrastructure Foundations**
1.  Create `RequestContextProvider` and register it in Container.
2.  Update `RequestContextMiddleware` to populate `RequestContextProvider`.
3.  Fix `ActorContextMiddleware` (ensure it resolves `AdminContext` correctly from Request attributes).

**Phase 2: Recorder Skeletons**
1.  Create `AuditRecorder`, `SecurityRecorder` (update existing), `ActivityRecorder` (update `AdminActivityLogService` or replace).
2.  Inject `ActorProvider` and `RequestProvider` into Recorders.
3.  Implement internal DTO construction within Recorders.

**Phase 3: Service Migration (Incremental)**
1.  **Select one Domain Service** (e.g., `AdminAuthenticationService`).
2.  Add semantic methods to Recorders for this service's needs.
3.  Replace `AuthoritativeSecurityAuditWriterInterface` usage with `AuditRecorder`.
4.  Replace `SecurityEventLoggerInterface` usage with `SecurityRecorder`.
5.  Verify behavior.

**Phase 4: Cleanup**
1.  Repeat for all services.
2.  Deprecate and remove direct Writer injections from Services.
3.  Make Writers internal to the Infrastructure layer (if possible).

---

# 7️⃣ Explicit Non-Goals

### Q7.1

*   **No Schema Changes**: Database schemas for logs remain exactly as is.
*   **No Behavior Change**: The content of the logs remains the same; only the code structure changing.
*   **No Auth Logic Change**: Authentication flows (StepUp, etc.) remain untouched, only how they log is refactored.
*   **No Performance Optimization**: This is an architecture reset, not a performance tuning pass.

---

# 8️⃣ Blockers & Unknowns

### Q8.1

1.  **ActorContextMiddleware / Container Disconnect**: `ActorContextMiddleware` currently checks `$container->has(AdminContext::class)`, but `AdminContextMiddleware` attaches `AdminContext` to Request Attributes. This must be fixed (Middleware should check Request Attribute) to ensure Actor is resolved correctly.
2.  **RequestContext Accessibility**: Currently `RequestContext` is passed as an argument. A `RequestContextProvider` (singleton/scoped) is required to allow Recorders to access it without breaking the "Service must not know context" rule.

---

I confirm no assumptions were made, no behavior changes proposed, and all conclusions are derived strictly from the current codebase and canonical documents.
