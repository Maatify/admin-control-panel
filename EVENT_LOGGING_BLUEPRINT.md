# Unified Logging System - Migration Blueprint

## Current Findings
The `maatify/event-logging` library is not currently installed or referenced in `composer.json`.
Instead, the project uses 6 custom logging modules implemented in the `Modules/` directory:
- `Modules/AuthoritativeAudit`
- `Modules/AuditTrail`
- `Modules/SecuritySignals`
- `Modules/BehaviorTrace`
- `Modules/DiagnosticsTelemetry`
- `Modules/DeliveryOperations`

These modules strictly follow the exact same architecture, naming conventions, and domain definitions as `maatify/event-logging` v1.0.0. They are already designed as "extractable libraries" as per `LOGGING_LIBRARY_STRUCTURE_CANONICAL.md` and `LOGGING_MODULE_BLUEPRINT.md`.

The Host application (`app/Modules/AdminKernel`) connects to these modules using Adapters and Services (e.g., `AuthoritativeAuditMaatifyAdapter`, `AuditTrailService`).

`AuthoritativeAudit` is strictly fail-closed, throws exceptions, and writes to `authoritative_audit_outbox` (transactional). It does not use `PSR-3` for swallowing errors. The other 5 domains are fail-open (best-effort) and swallow exceptions in their Recorders, logging failures to `PSR-3` fallback loggers.

## Existing Logging Inventory
- **AuthoritativeAudit**: Handles governance-grade changes. Written to `authoritative_audit_outbox` (fail-closed).
- **AuditTrail**: Tracks data exposure/reads. Written to `audit_trail` (fail-open).
- **SecuritySignals**: Tracks auth/policy anomalies. Written to `security_signals` (fail-open).
- **BehaviorTrace (Operational Activity)**: Tracks daily mutations. Written to `operational_activity` (fail-open).
- **DiagnosticsTelemetry**: Tracks technical metrics/errors. Written to `diagnostics_telemetry` (fail-open).
- **DeliveryOperations**: Tracks async jobs/webhooks/emails. Written to `delivery_operations` (fail-open).

## Domain Mapping Table
| Current Module | New Library Domain | Current Table | Retention Rule |
|---|---|---|---|
| `Maatify\AuthoritativeAudit` | `Maatify\EventLogging\AuthoritativeAudit` (or exact same if unchanged) | `authoritative_audit_outbox` | Keep DB table (Host Specific schemas remain) |
| `Maatify\AuditTrail` | `Maatify\EventLogging\AuditTrail` | `audit_trail` | Keep DB table (Backward Compatible) |
| `Maatify\SecuritySignals` | `Maatify\EventLogging\SecuritySignals` | `security_signals` | Keep DB table (Backward Compatible) |
| `Maatify\BehaviorTrace` | `Maatify\EventLogging\OperationalActivity` (or `BehaviorTrace`) | `operational_activity` | Keep DB table (Backward Compatible) |
| `Maatify\DiagnosticsTelemetry` | `Maatify\EventLogging\DiagnosticsTelemetry` | `diagnostics_telemetry` | Keep DB table (Backward Compatible) |
| `Maatify\DeliveryOperations` | `Maatify\EventLogging\DeliveryOperations` | `delivery_operations` | Keep DB table (Backward Compatible) |

## DB Tables Mapping
All DB schema files (`database/*.sql` and `Modules/*/Database/*.sql`) are strictly MySQL/PDO compliant. There are NO MongoDB assumptions in the current schema or repositories. They all use `event_id`, `actor_type`, `metadata` JSON, and `occurred_at`. These tables MUST remain untouched to ensure backward compatibility and zero data loss. The event-logging library version 1.0.0 is MySQL/PDO-only.

## Host-Specific Boundaries
- **Adapters**: `app/Modules/AdminKernel/Infrastructure/Logging/*MaatifyAdapter.php` must stay to inject Host-specific `RequestContext` (like `correlation_id`, `request_id`, `ip_address`).
- **Services**: `app/Modules/AdminKernel/Application/Services/*Service.php` must stay as they encapsulate business logic/events (e.g., `ACTION_ADMIN_CREATE`).
- **DI Container**: `app/Modules/AdminKernel/Bootstrap/Container.php` will need to register the library's classes instead of the local module classes.
- **DB Schemas**: `database/*.sql` files are host specific and must stay.

## Replacement Candidates
The following entire directories are exact duplicates of what `maatify/event-logging` provides and MUST be deleted (replaced by Composer dependency):
- `Modules/AuthoritativeAudit/`
- `Modules/AuditTrail/`
- `Modules/SecuritySignals/`
- `Modules/BehaviorTrace/`
- `Modules/DiagnosticsTelemetry/`
- `Modules/DeliveryOperations/`

## Blockers Before Codex Implementation
1. The package `maatify/event-logging:^1.0.0` must be available on Packagist or configured as a VCS repository in `composer.json` for installation.
2. The current `composer.json` uses `Maatify\*` namespaces mapping to `Modules/*/`. These autoload entries must be removed after package installation.

## Safe Codex Implementation Plan
1. **Require Library:** Run `composer require maatify/event-logging:^1.0.0`.
2. **Update Autoload:** Remove the 6 custom namespaces (`Maatify\AuthoritativeAudit\`, `Maatify\AuditTrail\`, `Maatify\SecuritySignals\`, `Maatify\BehaviorTrace\`, `Maatify\DiagnosticsTelemetry\`, `Maatify\DeliveryOperations\`) from the `autoload.psr-4` section in `composer.json`.
3. **Refactor Container:** In `app/Modules/AdminKernel/Bootstrap/Container.php`, update bindings to point to the new library's classes instead of local ones. If the library uses identical namespaces (e.g. `Maatify\AuthoritativeAudit`), this step may only require removing the local modules, as the autoloader will now load from the vendor directory.
4. **Refactor Adapters/Services:** If namespaces changed in the library (e.g. from `Maatify\BehaviorTrace` to `Maatify\EventLogging\OperationalActivity`), update all `use` statements in `app/Modules/AdminKernel/Infrastructure/Logging/*` and `app/Modules/AdminKernel/Application/Services/*`.
5. **Delete Local Modules:** Remove the directories:
   - `Modules/AuthoritativeAudit`
   - `Modules/AuditTrail`
   - `Modules/SecuritySignals`
   - `Modules/BehaviorTrace`
   - `Modules/DiagnosticsTelemetry`
   - `Modules/DeliveryOperations`
6. **Cleanup Docs:** Delete `docs/architecture/logging/LOGGING_LIBRARY_STRUCTURE_CANONICAL.md` (or mark as implemented) and update `docs/architecture/logging/README.md` to indicate the system is now powered by the standalone `maatify/event-logging` library instead of local modules. Any references to specific local modules in `LOG_DOMAINS_OVERVIEW.md` should be updated to point to the library.

## Verification Commands
- `composer validate`
- `composer dump-autoload`
- `vendor/bin/phpstan analyse app Modules --level=max`
- `vendor/bin/phpunit` (if applicable/available)
- `php tools/permission_linter.php`
- `php -l` for any modified files (Adapters, Services, Container.php).
