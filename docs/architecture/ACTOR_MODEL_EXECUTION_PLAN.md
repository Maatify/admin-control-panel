# Execution Checklist: Canonical Actor Model

## Phase 1: Core Domain Definition

### Objective
Establish the fundamental types and contracts for the Actor Model without breaking existing code.

### Files / Modules to TOUCH
*   `app/Domain/Actor/ActorType.php` (Create Enum)
*   `app/Domain/Actor/Actor.php` (Create Value Object)
*   `app/Domain/Contracts/ActorProviderInterface.php` (Create Interface)

### Files / Modules to VERIFY
*   `app/Domain/Actor/Actor.php` (Ensure logic forbids `ADMIN` + `NULL`)

### DO NOT TOUCH
*   Existing `AdminContext` (yet)
*   Existing Middleware

### Preconditions
*   None

### Post-conditions / Validation
*   `Actor` class exists and throws exception on `(ADMIN, NULL)` or `(SYSTEM, int)`.
*   `ActorType` Enum contains `ADMIN`, `SYSTEM`, `EXTERNAL`.

---

## Phase 2: Context & Middleware Refactor

### Objective
Replace `AdminContext` with `ActorContext` and enable System injection.

### Files / Modules to TOUCH
*   `app/Context/ActorContext.php` (Create new context)
*   `app/Http/Middleware/ActorContextMiddleware.php` (Create/Rename from AdminContextMiddleware)
*   `app/Bootstrap/Container.php` (Register ActorContext)
*   `routes/web.php` (Update middleware reference)

### Files / Modules to VERIFY
*   `app/Http/Middleware/ActorContextMiddleware.php` (Ensure it handles `admin_id` -> `Actor(ADMIN, id)`)
*   `app/Http/Middleware/ActorContextMiddleware.php` (Ensure it allows System injection if configured)
*   `routes/web.php` (Ensure `AdminContextMiddleware` is replaced by `ActorContextMiddleware`)

### DO NOT TOUCH
*   `app/Context/RequestContext.php`
*   `app/Http/Middleware/RequestContextMiddleware.php`

### Preconditions
*   Phase 1 Complete

### Post-conditions / Validation
*   Middleware successfully creates `ActorContext` for authenticated requests.
*   Container can provide `ActorProviderInterface`.

---

## Phase 3: DTO & Service Alignment

### Objective
Update Data Transfer Objects to carry full Actor information instead of just `admin_id`.

### Files / Modules to TOUCH
*   `app/Domain/DTO/AuditEventDTO.php`
*   `app/Domain/SecurityEvents/DTO/SecurityEventRecordDTO.php`
*   `app/Modules/Telemetry/DTO/TelemetryEventDTO.php`
*   `app/Application/SecurityEvents/HttpSecurityEventAdminRecorder.php` (Rename to `HttpSecurityEventRecorder`)
*   `app/Domain/SecurityEvents/Recorder/SecurityEventRecorder.php`

### Files / Modules to VERIFY
*   `app/Domain/DTO/AuditEventDTO.php` (Check `actor_type` property existence)
*   `app/Application/SecurityEvents/HttpSecurityEventRecorder.php` (Check dependency on `ActorContext` not `AdminContext`)

### DO NOT TOUCH
*   Database Schemas (yet)

### Preconditions
*   Phase 2 Complete

### Post-conditions / Validation
*   All DTOs accept `Actor` object or (Type, ID) tuple.
*   Services dependent on `AdminContext` now accept `ActorContext` or `ActorProviderInterface`.

---

## Phase 4: Persistence Layer

### Objective
Update Writers and Repositories to persist `actor_type` and handle `SYSTEM` actors.

### Files / Modules to TOUCH
*   `app/Infrastructure/Audit/PdoAuthoritativeAuditWriter.php`
*   `app/Modules/SecurityEvents/Infrastructure/Mysql/SecurityEventLoggerMysqlRepository.php`
*   `app/Modules/Telemetry/Infrastructure/Mysql/TelemetryLoggerMysqlRepository.php`

### Files / Modules to VERIFY
*   `app/Infrastructure/Audit/PdoAuthoritativeAuditWriter.php` (Check SQL INSERT includes `actor_type`)
*   `app/Modules/SecurityEvents/Infrastructure/Mysql/SecurityEventLoggerMysqlRepository.php` (Check SQL uses `actorType->value`)

### DO NOT TOUCH
*   `audit_logs` table (Legacy Admin View)

### Preconditions
*   Phase 3 Complete
*   Database migration for `actor_type` column (if not already compatible)

### Post-conditions / Validation
*   Writers successfully insert records with `actor_type='admin'` and `actor_type='system'`.

---

## Phase 5: Bootstrap & Cleanup

### Objective
Switch Bootstrap script to use System Actor and remove legacy "hotfixes".

### Files / Modules to TOUCH
*   `scripts/bootstrap_admin.php`
*   `app/Modules/ActivityLog/Service/ActivityLogService.php` (Remove swallowed exceptions)
*   `app/Context/AdminContext.php` (Delete if fully replaced)
*   `app/Http/Middleware/AdminContextMiddleware.php` (Delete if fully replaced)

### Files / Modules to VERIFY
*   `scripts/bootstrap_admin.php` (Check explicit System Context initialization)
*   `audit_outbox` table (Check for `system` actor entries after bootstrap)

### DO NOT TOUCH
*   `docs/FIRST_ADMIN_SETUP.md` (Update if instructions change, otherwise leave)

### Preconditions
*   Phase 4 Complete

### Post-conditions / Validation
*   Bootstrap runs without errors.
*   Audit logs reflect System actions.
*   No "AdminContext missing" errors in logs.
*   **Grep Check:** No references to `AdminContext` remain in `app/`.

---

## Final Safety Review

### Critical Risks
*   **Dependency Injection Failure:** If `ActorContext` is not correctly bound in Container, all authenticated routes will 500.
*   **Schema Mismatch:** If `audit_outbox.actor_type` does not support 'system' value, writes will fail.
*   **Null Pointer:** If `ActorContext` is null in a service that expects it, system will crash.

### Manual Verification Required
*   Run `scripts/bootstrap_admin.php` on a fresh DB.
*   Log in as the new Admin.
*   Trigger a Security Event (e.g., failed login).
*   Inspect `audit_outbox` for both `system` (bootstrap) and `admin` (login) types.

---

## GO / NO-GO Decision Gate

*   **GO** if:
    *   `Actor` logic forbids invalid states.
    *   Writers persist `actor_type` correctly.
    *   Bootstrap script completes with strict logging enabled.
*   **NO-GO** if:
    *   Any legacy code still hard-requires `AdminContext`.
    *   `actor_type` column is missing from DB.
    *   (ADMIN, NULL) state is reachable.
