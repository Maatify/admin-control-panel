# Unified Logging System - Migration Blueprint

> **Notice:** The canonical architecture for logging is now owned by `maatify/event-logging`.
> This document only contains host-specific migration history and wiring instructions.
> Do NOT duplicate canonical package documentation here.


## Current Findings
The `maatify/event-logging` library is currently installed as the canonical package. The host is integrating/using it.

The new library uses the `Maatify\EventLogging` namespace, which differs from the legacy custom namespaces (e.g., `Maatify\AuthoritativeAudit`). Specifically, the legacy `BehaviorTrace` module structurally corresponds to the `OperationalActivity` concept but is explicitly named `BehaviorTrace` in the new library `Maatify\EventLogging\BehaviorTrace`.

### Call Sites Analysis (AuthoritativeAudit)
Upon reviewing `app/Modules/AdminKernel/Application/Services/AuthoritativeAuditService.php` and its call sites, it acts strictly as an abstraction for writing to the audit log. The actual `beginTransaction()` calls happen within domain services or controllers (e.g., `AdminProfileUpdateService.php`, `AdminController.php`). The `AuthoritativeAuditService` methods (like `recordAdminCreated`) are invoked inside these external transactions. Because `AuthoritativeAuditRecorder` throws exceptions directly, it correctly fails the surrounding transaction if writing to the `maa_event_logging_authoritative_audit_outbox` fails, preserving the fail-closed guarantee.

## Exact Namespace Replacement Map
| Legacy Namespace | New Library Namespace |
|---|---|
| `Maatify\AuthoritativeAudit` | `Maatify\EventLogging\AuthoritativeAudit` |
| `Maatify\AuditTrail` | `Maatify\EventLogging\AuditTrail` |
| `Maatify\SecuritySignals` | `Maatify\EventLogging\SecuritySignals` |
| `Maatify\BehaviorTrace` | `Maatify\EventLogging\BehaviorTrace` |
| `Maatify\DiagnosticsTelemetry` | `Maatify\EventLogging\DiagnosticsTelemetry` |
| `Maatify\DeliveryOperations` | `Maatify\EventLogging\DeliveryOperations` |

## Exact Class/Interface Replacement Map (Examples)
- `Maatify\AuthoritativeAudit\Recorder\AuthoritativeAuditRecorder` ➡️ `Maatify\EventLogging\AuthoritativeAudit\Recorder\AuthoritativeAuditRecorder`
- `Maatify\AuditTrail\Contract\AuditTrailLoggerInterface` ➡️ `Maatify\EventLogging\AuditTrail\Contract\AuditTrailLoggerInterface`
- `Maatify\BehaviorTrace\Contract\BehaviorTraceWriterInterface` ➡️ `Maatify\EventLogging\BehaviorTrace\Contract\BehaviorTraceWriterInterface`
- (This applies universally to DTOs, Enums, Interfaces, and Recorders).

## Exact Table Compatibility Decision
The legacy tables were previously named without the `maa_event_logging_` prefix (e.g., `authoritative_audit_outbox`, `audit_trail`, `security_signals`, `operational_activity`).
The `maatify/event-logging` library expects prefixed table names (e.g., `maa_event_logging_audit_trail`).

**Decision:** The repository already contains migrations renaming legacy tables to `maa_event_logging_*` names, ensuring compatibility with the new canonical package. Legacy names referenced here are only for migration history.

## Historical Migration Notes
The host application has been refactored to consume the new `Maatify\EventLogging\*` namespaces, bindings, and interfaces from the external package. Legacy table names have been migrated via SQL updates.

These notes serve purely as historical context on the transition from the internal custom modules to the external library.

## Verification Commands
- `composer validate`
- `composer dump-autoload`
- `vendor/bin/phpstan analyse app Modules --level=max` (Must pass with 0 errors about missing classes)
- `vendor/bin/phpunit` (if applicable)
- `php tools/permission_linter.php`
- `php -l` for all modified PHP files.
