# Architectural Analysis: Canonical Actor Model

## 1. Executive Summary

The current system suffers from **Actor Blindness**, effectively treating all actions as originating from an "Admin" by default. This implicit assumption breaks down during **System-initiated operations**, such as the first-admin bootstrap process, scheduled tasks (CRON), and asynchronous workers. In these scenarios, the absence of an authenticated Admin session (`AdminContext`) causes failures in logging, security event recording, and telemetry.

The observed "bootstrap failure" is a symptom of this deeper architectural gap. The system currently relies on a temporary hotfix (fail-open or implicit defaults) to tolerate these failures, which compromises audit integrity.

This document proposes a formal, canonical **Actor Model** that explicitly distinguishes between **Human Administrators** and the **System** itself. By formalizing `ActorType` and `ActorId` combinations, the system can safely operate in both authenticated (Admin) and autonomous (System) contexts without ambiguity or reliance on legacy tolerance.

---

## 2. Actor Model Definition

We introduce a composite **Actor** concept defined by a **Type** and an optional **ID**.

### 2.1 Actor Types (`ActorType`)

We define an explicit Enum `ActorType` with the following values:

| Type | Code | Description |
| :--- | :--- | :--- |
| **Administrator** | `ADMIN` | A human operator authenticated via session or token. |
| **System** | `SYSTEM` | The application itself performing autonomous actions (e.g., Bootstrap, Workers). |
| **External** | `EXTERNAL` | (Future) Third-party systems accessing via API keys/Integration. |

### 2.2 Valid Combinations (ActorType, ActorId)

The `Actor` Value Object must enforce these valid state combinations:

| Actor Type | Actor ID | Semantic Meaning |
| :--- | :--- | :--- |
| `ADMIN` | `int {id}` | **Authenticated Admin**. ID corresponds to a record in the `admins` table. |
| `SYSTEM` | `NULL` | **The System**. Represents the singular application entity. No ID is required. |
| `EXTERNAL` | `string {id}` | **External Client**. ID corresponds to an API Key ID or Service Account ID. |

**Invalid States:**
* `(ADMIN, NULL)`: An Admin must always have an ID. "Guest Admin" is not a valid concept.
* `(SYSTEM, {id})`: The System is treated as a singleton context for now.

---

## 3. Actor Presence Matrix

This matrix maps execution flows to their expected Actor context.

| Execution Flow | Actor State | Context Source |
| :--- | :--- | :--- |
| **First Admin Bootstrap** | `(SYSTEM, NULL)` | `scripts/bootstrap_admin.php` |
| **Console Command / CRON** | `(SYSTEM, NULL)` | `ConsoleKernel` / Scheduler |
| **Async Queue Worker** | `(SYSTEM, NULL)` | `WorkerScript` |
| **Admin Login (Auth)** | `(ADMIN, {id})` | `SessionMiddleware` → `ActorContext` |
| **Public Endpoint (Guest)** | `(ANONYMOUS, NULL)` | `GuestMiddleware` (if tracking guests is required) |
| **System Startup** | `(SYSTEM, NULL)` | `Container` Initialization |

---

## 4. Lifecycle & Flow Explanation

### 4.1 Bootstrap Flow (System Context)
**Current Problem:** The bootstrap script initiates actions (creating DB records). Services (Audit, Security) attempt to resolve the current actor using `AdminContext`. Since no session exists, this resolution fails or returns `NULL`, leading to crashes or "Admin NULL" logs.

**Proposed Flow:**
1. **Entry:** `scripts/bootstrap_admin.php` initializes a **SystemContext** (or configures the Container to provide a `SystemActor`).
2. **Action:** The script calls `AdminRepository->create()`.
3. **Logging:** The Audit Writer receives the context. It sees `ActorType::SYSTEM` and `ActorId::NULL`.
4. **Persistence:** The log is written with `actor_type='system'`, `actor_id=NULL`.
5. **Result:** The audit trail correctly reflects that the *System* created the first Admin.

### 4.2 Authenticated Flow (Admin Context)
**Flow:**
1. **Entry:** HTTP Request hits `AdminContextMiddleware`.
2. **Resolution:** Middleware validates the session and extracts `admin_id`.
3. **Context:** Middleware constructs an `Actor` with `(ADMIN, admin_id)` and places it in `ActorContext`.
4. **Action:** Controller invokes a Domain Service.
5. **Logging:** Service logs an event. The logger extracts `(ADMIN, admin_id)` from the context.
6. **Result:** Audit trail reflects the specific Admin's action.

---

## 5. Execution Roadmap

This roadmap implements the model without breaking existing functionality.

### Phase 1: Domain Definition (Safe)
1.  **Create Enum:** `App\Domain\Actor\ActorType`.
2.  **Create Value Object:** `App\Domain\Actor\Actor` (encapsulating Type + ID).
3.  **Define Interface:** `App\Domain\Contracts\ActorProviderInterface` to abstract context retrieval.

### Phase 2: Context & Middleware (Refactor)
1.  **Create Context:** `App\Context\ActorContext` (replacing or wrapping `AdminContext`).
2.  **Update Middleware:** Rename `AdminContextMiddleware` to `ActorContextMiddleware`.
    *   If `admin_id` present: Set `Actor(ADMIN, id)`.
    *   If CLI/System: Allow injection of `Actor(SYSTEM, NULL)`.

### Phase 3: DTO Alignment (Strictness)
Update DTOs to accept the unified `Actor` object or the explicit (Type, ID) pair.
1.  **Audit:** Update `AuditEventDTO` to include `public readonly string $actor_type`.
2.  **Security:** Update `SecurityEventRecordDTO` to enforce `ActorType`.
3.  **Telemetry:** Update `TelemetryEventDTO`.
4.  **Refactor Recorders:** Update `HttpSecurityEventAdminRecorder` to be `HttpSecurityEventRecorder`, accepting the generic `ActorContext`.

### Phase 4: Persistence Layer (Migration)
1.  **Audit Outbox:** Update `PdoAuthoritativeAuditWriter` to write `actor_type` to the DB.
    *   *Schema Note:* Ensure `audit_outbox.actor_type` column allows 'system' (if enum constrained) or is VARCHAR (existing).
    *   *Default:* Remove DB default 'admin' implies strictness, but 'admin' default is acceptable for legacy data compatibility if managed in code.
2.  **Writers:** Update `SecurityEventLogger` and `TelemetryLogger` to strictly use the `ActorType` enum values.

### Phase 5: Cleanup
1.  **Remove Hotfix:** Remove any code swallowing exceptions in `ActivityLogService` or generic catch blocks related to missing context.
2.  **Verify:** Run Bootstrap script and verify `audit_outbox` contains `actor_type='system'` for system events (if any occur before admin creation).

---

## 6. Risk & Failure Modes

### 6.1 Data Interpretation Risks
*   **Legacy Data:** Existing rows in `audit_outbox` have `actor_type='admin'` (default). This is historically accurate (only admins acted).
*   **System vs "Admin NULL":** Code must distinguish between `(SYSTEM, NULL)` and a broken `(ADMIN, NULL)`. The `Actor` VO should throw an exception if constructed with `(ADMIN, NULL)`.

### 6.2 Admin-Only Views
*   **Impact:** `audit_logs` table (used for "My Actions" UI) currently relies on `actor_admin_id`.
*   **Risk:** System events (with no admin ID) will not appear in these views.
*   **Mitigation:** This is actually *desired behavior*. System events belong in the Authoritative Audit Log (`audit_outbox` / Security Logs), not in a specific Admin's activity feed.

---

## 7. Exit Criteria

The architecture is considered "Fixed" when:

1.  **Explicit Actor Definition:** The codebase uses `Actor` (Type+ID) instead of naked `$adminId`.
2.  **Bootstrap Success:** The First Admin Bootstrap process completes successfully with full logging strictness enabled (no swallowed exceptions).
3.  **Correct Attribution:** System-initiated events in the database show `actor_type='system'` (or equivalent) and `actor_id=NULL`.
4.  **No "AdminContext" Dependency:** Core services depend on `ActorProviderInterface`, not `AdminContext`.
