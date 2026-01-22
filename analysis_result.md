# Logging DI Binding Map & Usage Proof

## A) Container Binding Map

| Interface / Service | Bound Implementation | Notes |
| :--- | :--- | :--- |
| **`SecurityEventLoggerInterface`** (Legacy) | `App\Infrastructure\Repository\SecurityEventRepository` | **BROKEN / FAILING SILENTLY**. <br>Implementation attempts to write to `security_events` using legacy columns (`admin_id`, `event_name`, `context`) which do not match the current schema (`actor_id`, `event_type`, `metadata`). <br>Swallows all exceptions, so data is lost without error. |
| **`SecurityEventRecorderInterface`** | `App\Domain\SecurityEvents\Recorder\SecurityEventRecorder` | **ACTIVE / CORRECT**. <br>Uses `SecurityEventLoggerMysqlRepository` internally. |
| `SecurityEventLoggerInterface` (Module) | `App\Modules\SecurityEvents\Infrastructure\Mysql\SecurityEventLoggerMysqlRepository` | **CORRECT**. <br>Writes to `security_events` using the correct schema. Used only by `SecurityEventRecorder`. |
| `TelemetryAuditLoggerInterface` | `App\Infrastructure\Audit\PdoTelemetryAuditLogger` | **UNUSED**. <br>Bound in Container but has no consumers in the codebase. |
| `TelemetryLoggerInterface` (Module) | `App\Modules\Telemetry\Infrastructure\Mysql\TelemetryLoggerMysqlRepository` | **ACTIVE**. <br>Used by `TelemetryRecorder`. |
| `TelemetryRecorderInterface` | `App\Domain\Telemetry\Recorder\TelemetryRecorder` | **ACTIVE**. <br>Used by `HttpTelemetryRecorderFactory` and `LogoutController`. |
| `ActivityLogWriterInterface` | `App\Modules\ActivityLog\Infrastructure\Mysql\ActivityLogLoggerMysqlRepository` | **ACTIVE**. <br>Used by `ActivityRecorder`. |
| `ActivityRecorder` | `App\Domain\ActivityLog\Recorder\ActivityRecorder` | **ACTIVE**. <br>Used by `AdminActivityLogService`. |
| `AdminSecurityEventReaderInterface` | `App\Infrastructure\Audit\PdoAdminSecurityEventReader` | **BROKEN**. <br>Used by `AdminSecurityEventController`. Selects legacy columns (`admin_id`, `context`) which are likely incompatible with new schema or empty. |

---

## B) Usage Proof (References)

### 1. `app/Infrastructure/Repository/SecurityEventRepository.php` (Legacy Logger)
*Status: **ACTIVE (But Broken)** - Injected into critical paths.*

| File Path | Class | Method / Context | Purpose | Logging Domain | Classification |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `app/Http/Controllers/Web/LogoutController.php` | `LogoutController` | `__construct` | Injected to log logout events. | Security | **ACTIVE** (Failing) |
| `app/Http/Controllers/Web/ChangePasswordController.php` | `ChangePasswordController` | `__construct` | Injected to log password changes. | Security | **ACTIVE** (Failing) |
| `app/Domain/Service/AuthorizationService.php` | `AuthorizationService` | `__construct` | Injected to log authorization failures/decisions. | Security | **ACTIVE** (Failing) |
| `app/Domain/Service/RememberMeService.php` | `RememberMeService` | `__construct` | Injected to log remember-me token usage. | Security | **ACTIVE** (Failing) |
| `app/Domain/Service/AdminAuthenticationService.php` | `AdminAuthenticationService` | `__construct` | Injected to log login attempts/failures. | Security | **ACTIVE** (Failing) |
| `app/Domain/Service/SessionValidationService.php` | `SessionValidationService` | `__construct` | Injected to log session validation issues. | Security | **ACTIVE** (Failing) |
| `app/Domain/Service/RecoveryStateService.php` | `RecoveryStateService` | `__construct` | Injected to log recovery mode events. | Security | **ACTIVE** (Failing) |

### 2. `app/Infrastructure/Audit/PdoTelemetryAuditLogger.php`
*Status: **UNUSED** - Bound but never instantiated or injected.*

| File Path | Class | Method / Context | Purpose | Logging Domain | Classification |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `app/Bootstrap/Container.php` | `Container` | `create` (Definition) | Defined/Bound but `TelemetryAuditLoggerInterface` has no consumers. | Telemetry | **UNUSED** |

### 3. `app/Infrastructure/Audit/PdoAdminSecurityEventReader.php`
*Status: **ACTIVE (But Broken)** - Injected into Controller.*

| File Path | Class | Method / Context | Purpose | Logging Domain | Classification |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `app/Http/Controllers/AdminSecurityEventController.php` | `AdminSecurityEventController` | `__construct` | Injected to read/list security events for the UI. | Security | **ACTIVE** (Failing) |

---

## Conclusion

To eliminate the "Split-Brain" (where most services use the broken legacy logger while `StepUpService` uses the correct new recorder), the single most effective switch based on bindings is to **rebind `App\Domain\Contracts\SecurityEventLoggerInterface` in `app/Bootstrap/Container.php`**. Instead of returning the broken `SecurityEventRepository`, the container should return an **Adapter** (e.g., `LegacySecurityEventLoggerAdapter`) that implements the legacy interface but delegates calls to the new `SecurityEventRecorder` (or `SecurityEventLoggerMysqlRepository`). This would instantly fix the data loss and unify the logging pipeline for all existing consumers without modifying their constructor signatures.
