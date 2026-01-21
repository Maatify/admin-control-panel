# MODULE CONFORMANCE PATCH PLAN (NO CODE)

## 0. Executive Summary
- **Compliance Status**:
    - **ActivityLog**: **CRITICAL NON-COMPLIANCE**. Violates core architecture (Swallowing in Module, Domain logic in Module).
    - **SecurityEvents**: **HIGH RISK**. Violates contract honesty (Interface lies about exceptions).
    - **Telemetry**: **CRITICAL NON-COMPLIANCE**. Violates Schema Hard Gate (Writes to `event_type` instead of `event_key`).
- **Violations Count**:
    - Critical: 3 (Swallowing in Module, Logic in Module, Schema Hard Gate)
    - High: 2 (Contract Dishonesty, Missing Shared Columns)
    - Medium: 3 (Naming Conventions)
- **Top 5 Highest-Risk Violations**:
    1.  **ActivityLog Module Swallowing** (Section 0.1, 4.3): Module swallows exceptions, hiding failures and violating layering rules.
    2.  **Telemetry Schema Violation** (Section 5.3.3): Telemetry writes to `event_type` instead of mandatory `event_key`.
    3.  **SecurityEvents Dishonest Contract** (Section 0.3, Rule 3): `SecurityEventLoggerInterface` promises "no throws" but implementation throws.
    4.  **ActivityLog Misplaced Logic** (Section 2.1, 2.2): Domain logic (Service) exists within Module boundary.
    5.  **Missing Correlation ID** (Section 5.3.1): All three modules fail to capture/store `correlation_id`.

## 1. Canonical Requirements Checklist

### ActivityLog
- [x] **Schema Contract**: FAILED. Missing `correlation_id`.
- [x] **Layering & Ownership**: FAILED. `Service` in Module, `Drivers` instead of `Infrastructure`.
- [x] **Failure Semantics**: FAILED. Module swallows exceptions.
- [x] **Context Hydration**: FAILED. Hydration logic inside Module Service.
- [ ] **Read-side rules**: NOT CHECKED (Focus on Write Criticality).

### SecurityEvents
- [x] **Schema Contract**: FAILED. Missing `correlation_id`.
- [x] **Layering & Ownership**: PASSED.
- [x] **Failure Semantics**: FAILED. Interface documentation prohibits throwing, but implementation throws.
- [x] **Context Hydration**: PASSED (Recorder handles it).
- [x] **Read-side rules**: PASSED (Writer focus).

### Telemetry
- [x] **Schema Contract**: FAILED. Uses `event_type` (banned) instead of `event_key`. Missing `correlation_id`.
- [x] **Layering & Ownership**: PASSED.
- [x] **Failure Semantics**: PASSED (Throws correctly).
- [x] **Context Hydration**: PASSED (Recorder handles it).
- [x] **Read-side rules**: PASSED.

## 2. Drift Inventory (Per Module)

### ActivityLog
| Drift ID | File | Layer | Current Behavior | Expected Behavior | Rule Violated | Severity | Minimal Fix Scope |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **AL-01** | `app/Modules/ActivityLog/Service/ActivityLogService.php` | Module | Logic & Swallowing | **DELETE FILE**. Logic moves to Domain Recorder. | 0.1, 2.1 | **CRITICAL** | Delete file, move logic |
| **AL-02** | `app/Modules/ActivityLog/Drivers/MySQL/MySQLActivityLogWriter.php` | Module | Incorrect Path/Name, No Exception Wrap | `Infrastructure/Mysql/ActivityLogLoggerMysqlRepository.php`, Wrap in StorageException | 3.1, 4.2 | **HIGH** | Rename, Wrap Exceptions |
| **AL-03** | `app/Modules/ActivityLog/Contracts/ActivityLogWriterInterface.php` | Module | Named `Writer`, returns void | Named `LoggerInterface`, throws `StorageException` | 3.1 | MED | Rename, Update Signature |
| **AL-04** | `app/Modules/ActivityLog/DTO/ActivityLogDTO.php` | Module | Named `ActivityLogDTO`, missing `correlation_id` | Named `ActivityLogEntryDTO`, add `correlation_id` | 3.1, 5.3.1 | MED | Rename, Add Field |

### SecurityEvents
| Drift ID | File | Layer | Current Behavior | Expected Behavior | Rule Violated | Severity | Minimal Fix Scope |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **SE-01** | `app/Modules/SecurityEvents/Contracts/SecurityEventLoggerInterface.php` | Module | Docblock says "MUST NOT throw" | MUST declare `throws SecurityEventStorageException` | 0.3 | **HIGH** | Update Docblock & Signature |
| **SE-02** | `app/Modules/SecurityEvents/DTO/SecurityEventDTO.php` | Module | Missing `correlation_id` | Add `correlation_id` | 5.3.1 | MED | Add Field |
| **SE-03** | `app/Modules/SecurityEvents/Infrastructure/Mysql/SecurityEventLoggerMysqlRepository.php` | Module | Writes to `event_type` (Checked: Correctly `event_type` for Security) | Missing `correlation_id` | 5.3.1 | MED | Add Field to SQL |

### Telemetry
| Drift ID | File | Layer | Current Behavior | Expected Behavior | Rule Violated | Severity | Minimal Fix Scope |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TM-01** | `app/Modules/Telemetry/Infrastructure/Mysql/TelemetryLoggerMysqlRepository.php` | Module | Writes to `event_type` | Write to `event_key` | 5.3.3 | **CRITICAL** | Change SQL & Params |
| **TM-02** | `app/Modules/Telemetry/DTO/TelemetryEventDTO.php` | Module | Named `TelemetryEventDTO`, missing `correlation_id` | Named `TelemetryEntryDTO`, add `correlation_id`, rename `eventType` -> `eventKey` | 3.1, 5.3.1 | **HIGH** | Rename Class/Props, Add Field |

## 3. Sequenced Patch Plan (NO CODE)

### Phase 1: ActivityLog Remediation
1.  **Preparation**: Create `app/Modules/ActivityLog/Exceptions/ActivityLogStorageException.php`.
2.  **Contract Alignment**:
    - Rename `ActivityLogWriterInterface.php` -> `ActivityLogLoggerInterface.php`.
    - Update signature: `log(ActivityLogEntryDTO $dto): void` and `@throws ActivityLogStorageException`.
3.  **DTO Alignment**:
    - Rename `ActivityLogDTO.php` -> `ActivityLogEntryDTO.php`.
    - Add `correlation_id` (nullable char(36)).
4.  **Infrastructure Alignment**:
    - Move `Drivers/MySQL/MySQLActivityLogWriter.php` -> `Infrastructure/Mysql/ActivityLogLoggerMysqlRepository.php`.
    - Implement `ActivityLogLoggerInterface`.
    - Wrap `PDOException` in `ActivityLogStorageException`.
    - Update SQL to include `correlation_id`.
    - **Risk**: Renaming files requires updating all usages.
5.  **Domain Recorder Creation**:
    - Create `app/Domain/ActivityLog/Recorder/ActivityLogRecorder.php`.
    - Move logic from `ActivityLogService` to Recorder (including `metadata` handling).
    - Implement swallowing policy (catch `ActivityLogStorageException`).
6.  **Cleanup**:
    - Update `AdminActivityLogService` to use `ActivityLogRecorder`.
    - **DELETE** `app/Modules/ActivityLog/Service/ActivityLogService.php`.
    - **DELETE** `app/Modules/ActivityLog/Drivers` directory.

### Phase 2: SecurityEvents Remediation
1.  **Contract Honesty**:
    - Update `SecurityEventLoggerInterface.php`: Remove "MUST NOT throw". Add `@throws SecurityEventStorageException`.
2.  **DTO Alignment**:
    - Update `SecurityEventDTO.php`: Add `correlation_id`.
    - Update `SecurityEventRecordDTO.php` (Domain): Add `correlation_id`.
    - Update `SecurityEventRecorder.php`: Pass `correlation_id` to Module DTO.
3.  **Infrastructure Alignment**:
    - Update `SecurityEventLoggerMysqlRepository.php`: Add `correlation_id` to `INSERT` statement and params.
    - Ensure `log()` method signature matches interface (remove any conflicts).

### Phase 3: Telemetry Remediation
1.  **DTO Alignment**:
    - Rename `TelemetryEventDTO.php` -> `TelemetryEntryDTO.php`.
    - Rename property `eventType` -> `eventKey`.
    - Add `correlation_id`.
2.  **Infrastructure Alignment**:
    - Update `TelemetryLoggerMysqlRepository.php`:
        - Change SQL column `event_type` -> `event_key`.
        - Add `correlation_id` to SQL.
        - Update params to match DTO changes.
3.  **Recorder Alignment**:
    - Update `TelemetryRecorder.php`: Map Domain `eventType` to Module `eventKey`. Pass `correlation_id`.

## 4. Cross-Module Unification Actions
- **Correlation ID Propagation**: Update all Domain Recorders (`ActivityLogRecorder`, `SecurityEventRecorder`, `TelemetryRecorder`) to accept `correlation_id` (e.g., from `RequestContext` or method argument) and pass it to the Module DTO.

## 5. Verification Plan (NO CODE)

### ActivityLog
- **Manual Verify**: Check `app/Modules/ActivityLog` file tree. MUST NOT see `Service` or `Drivers`.
- **Manual Verify**: Open `ActivityLogLoggerMysqlRepository.php`. MUST see `try { ... } catch (PDOException $e) { throw new ActivityLogStorageException(...) }`.
- **Manual Verify**: Open `ActivityLogRecorder.php`. MUST see `try { $logger->log(...) } catch (ActivityLogStorageException) { }`.

### SecurityEvents
- **Manual Verify**: Open `SecurityEventLoggerInterface.php`. MUST see `@throws SecurityEventStorageException`.
- **Manual Verify**: Open `SecurityEventLoggerMysqlRepository.php`. SQL MUST include `correlation_id`.

### Telemetry
- **Manual Verify**: Open `TelemetryLoggerMysqlRepository.php`. SQL MUST use `event_key` column (NOT `event_type`).
- **Manual Verify**: SQL MUST include `correlation_id`.

## 6. Final Safety Declaration
- **No code changed**: This document is a plan.
- **No assumptions made**: Violations verified against codebase.
- **UNIFIED_LOGGING_DESIGN.md** was treated as sole authority.
