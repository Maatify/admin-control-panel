# Unified Logging System - Migration Blueprint

> **Notice:** The canonical architecture for logging is now owned by `maatify/event-logging`.
> This document only contains host-specific migration history and wiring instructions.
> Do NOT duplicate canonical package documentation here.


## Current Findings
The `maatify/event-logging` library (v1.0.0) is not currently installed. The project relies on 6 custom logging modules inside the `Modules/` directory.

The new library uses the `Maatify\EventLogging` namespace, which differs from the legacy custom namespaces (e.g., `Maatify\AuthoritativeAudit`). Specifically, the legacy `BehaviorTrace` module structurally corresponds to the `OperationalActivity` concept but is explicitly named `BehaviorTrace` in the new library `Maatify\EventLogging\BehaviorTrace`.

### Call Sites Analysis (AuthoritativeAudit)
Upon reviewing `app/Modules/AdminKernel/Application/Services/AuthoritativeAuditService.php` and its call sites, it acts strictly as an abstraction for writing to the audit log. The actual `beginTransaction()` calls happen within domain services or controllers (e.g., `AdminProfileUpdateService.php`, `AdminController.php`). The `AuthoritativeAuditService` methods (like `recordAdminCreated`) are invoked inside these external transactions. Because `AuthoritativeAuditRecorder` throws exceptions directly, it correctly fails the surrounding transaction if writing to the `authoritative_audit_outbox` fails, preserving the fail-closed guarantee.

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
The legacy tables are named without the `maa_event_logging_` prefix (e.g., `authoritative_audit_outbox`, `audit_trail`, `security_signals`, `operational_activity`).
The `maatify/event-logging` library expects prefixed table names (e.g., `maa_event_logging_audit_trail`).

**Decision:** We **MUST NOT** delete the current infrastructure/repository implementations (`Infrastructure/Mysql/*MysqlRepository.php`) from the host application unless the new library allows configuring table names.
Assuming the library has hardcoded table names or expects specific repository injections, we will adopt the **Host-Specific Repository Adapter** pattern. The host will implement the library's `*Interface` contracts but keep its own SQL queries pointing to the legacy table names to ensure backward compatibility and zero data migration.

## Safe Phased Deletion Plan
1. **Do NOT delete anything yet.**
2. Require the library (`composer require maatify/event-logging:^1.0.0`).
3. Refactor the host application (`app/Modules/AdminKernel`) to update all `use` statements from the legacy `Maatify\*` namespaces to the new `Maatify\EventLogging\*` namespaces for Recorders, Interfaces, DTOs, and Enums.
4. Refactor `app/Modules/AdminKernel/Bootstrap/Container.php` to bind the new library's Interfaces to the **legacy** host-specific Mysql Repositories.
5. Move the legacy `Infrastructure/Mysql/` repository files from `Modules/*/` into `app/Modules/AdminKernel/Infrastructure/Logging/Repositories/` and update their namespaces and implemented interfaces to point to `Maatify\EventLogging\*`.
6. Once PHPStan confirms zero references to the old `Maatify\AuthoritativeAudit`, `Maatify\AuditTrail`, etc., and all tests pass, delete the `Modules/AuthoritativeAudit`, `Modules/AuditTrail`, `Modules/SecuritySignals`, `Modules/BehaviorTrace`, `Modules/DiagnosticsTelemetry`, and `Modules/DeliveryOperations` directories entirely.
7. Remove the legacy PSR-4 autoload entries from `composer.json`.

## Files Expected to be Modified by Codex
- `composer.json` (Require library, remove old PSR-4 namespaces)
- `app/Modules/AdminKernel/Bootstrap/Container.php` (Update bindings)
- `app/Modules/AdminKernel/Infrastructure/Logging/*MaatifyAdapter.php` (Update imports)
- `app/Modules/AdminKernel/Application/Services/*Service.php` (Update imports if applicable)
- Legacy `*MysqlRepository.php` files (Moved and updated with new namespaces/interfaces)

## Files/Directories That Must NOT be Deleted Yet
- `database/*.sql` and `Modules/*/Database/*.sql` (Host schema MUST remain untouched).
- `Modules/*/Infrastructure/Mysql/*MysqlRepository.php` (These must be preserved and moved into the `app/` namespace to act as host-specific adapters pointing to legacy table names).

## Verification Commands
- `composer validate`
- `composer dump-autoload`
- `vendor/bin/phpstan analyse app Modules --level=max` (Must pass with 0 errors about missing classes)
- `vendor/bin/phpunit` (if applicable)
- `php tools/permission_linter.php`
- `php -l` for all modified PHP files.
