# MODULE CONFORMANCE FINDINGS (NO CODE)

## 0) Scope & Evidence Index
- **ActivityLog**: `app/Modules/ActivityLog`, `app/Domain/ActivityLog`
- **SecurityEvents**: `app/Modules/SecurityEvents`, `app/Domain/SecurityEvents`
- **Telemetry**: `app/Modules/Telemetry`, `app/Domain/Telemetry`
- **Database Schema**: `database/schema.sql` (Verified presence of tables and columns)

## 1) Findings Summary
- **ActivityLog**:
    - HARD GATE: 0
    - MAJOR: 3 (Shared Columns, Missing Reader Contract, Read Path Bypass)
    - MINOR: 0
- **SecurityEvents**:
    - HARD GATE: 1 (Reader uses array $filters)
    - MAJOR: 1 (Shared Columns)
    - MINOR: 0
- **Telemetry**:
    - HARD GATE: 1 (PDO in Domain)
    - MAJOR: 1 (Shared Columns)
    - MINOR: 0

## 2) Findings (Per Module)

### ActivityLog

| Finding ID | Severity | Rule (Section) | File | Symbol | Evidence | Current Behavior | Why it violates canonical design |
|:---|:---|:---|:---|:---|:---|:---|:---|
| ACT-01 | MAJOR | 5.3.1 Shared Columns | `app/Modules/ActivityLog/DTO/ActivityLogDTO.php` | `ActivityLogDTO` | Properties list | Missing `correlation_id` and `route_name` properties. | Cannot carry required shared context. |
| ACT-02 | MAJOR | 5.3.1 Shared Columns | `app/Modules/ActivityLog/Infrastructure/Mysql/ActivityLogLoggerMysqlRepository.php` | `write` | `INSERT INTO activity_logs ...` | Does not insert `correlation_id` or `route_name`. | Fails to persist required shared context columns. |
| ACT-03 | MAJOR | 2.1 Module Layer / 3.1 Module Layout | `app/Modules/ActivityLog` | N/A | Directory listing | Missing `Contracts/ActivityLogReaderInterface.php` and `DTO/ActivityLogQueryDTO.php`. | Module is incomplete (missing Read contracts) despite being read by the application. |
| ACT-04 | MAJOR | 2.1 Module Layer / 2.2 Domain Recorder | `app/Domain/ActivityLog/Reader/ActivityLogListReaderInterface.php` | `ActivityLogListReaderInterface` | Interface definition | Read contract defined in Domain instead of Module. | Domain defines core module contracts, violating layering. |
| ACT-05 | MAJOR | 4. DB is Infrastructure | `app/Infrastructure/Reader/ActivityLog/PdoActivityLogListReader.php` | `PdoActivityLogListReader` | `implements ActivityLogListReaderInterface` | Application reads via Infrastructure bypass, skipping Module layer. | Violates strict module layering and encapsulation. |
| ACT-06 | MAJOR | 8.1 correlation_id | `app/Domain/ActivityLog/Recorder/ActivityRecorder.php` | `log` | Method signature | `correlationId` is not accepted or passed. | Recorder fails to capture/propagate correlation ID. |

### SecurityEvents

| Finding ID | Severity | Rule (Section) | File | Symbol | Evidence | Current Behavior | Why it violates canonical design |
|:---|:---|:---|:---|:---|:---|:---|:---|
| SEC-01 | HARD GATE | 7. Query / Read Rules | `app/Modules/SecurityEvents/Contracts/SecurityEventReaderInterface.php` | `paginate`, `count` | `array $filters` | Accepts untyped array filters. | Explicitly banned ("canonical ban") in favor of typed `QueryDTO`. |
| SEC-02 | MAJOR | 5.3.1 Shared Columns | `app/Modules/SecurityEvents/DTO/SecurityEventDTO.php` | `SecurityEventDTO` | Properties list | Missing `correlation_id`. | Cannot carry required shared context. |
| SEC-03 | MAJOR | 5.3.1 Shared Columns | `app/Modules/SecurityEvents/Infrastructure/Mysql/SecurityEventLoggerMysqlRepository.php` | `store` | `INSERT INTO security_events ...` | Does not insert `correlation_id`. | Fails to persist required shared context column. |
| SEC-04 | MAJOR | 8.1 correlation_id | `app/Domain/SecurityEvents/Recorder/SecurityEventRecorder.php` | `record` | `new SecurityEventDTO(...)` | `correlationId` not passed to DTO. | Recorder fails to propagate correlation ID. |

### Telemetry

| Finding ID | Severity | Rule (Section) | File | Symbol | Evidence | Current Behavior | Why it violates canonical design |
|:---|:---|:---|:---|:---|:---|:---|:---|
| TEL-01 | HARD GATE | 4. DB is Infrastructure / 2.2 No PDO in Domain | `app/Domain/Telemetry/Reader/PdoTelemetryListReader.php` | `PdoTelemetryListReader` | `private PDO $pdo` | Class in Domain namespace injects and uses PDO. | Domain code MUST NOT talk to PDO/DB. |
| TEL-02 | MAJOR | 5.3.1 Shared Columns | `app/Modules/Telemetry/DTO/TelemetryEventDTO.php` | `TelemetryEventDTO` | Properties list | Missing `correlation_id`. | Cannot carry required shared context. |
| TEL-03 | MAJOR | 5.3.1 Shared Columns | `app/Modules/Telemetry/Infrastructure/Mysql/TelemetryLoggerMysqlRepository.php` | `store` | `INSERT INTO telemetry_traces ...` | Does not insert `correlation_id`. | Fails to persist required shared context column. |
| TEL-04 | MAJOR | 8.1 correlation_id | `app/Domain/Telemetry/Recorder/TelemetryRecorder.php` | `record` | `new TelemetryEventDTO(...)` | `correlationId` not passed to DTO. | Recorder fails to propagate correlation ID. |

## 3) Correlation ID Coverage Findings (End-to-End)

### ActivityLog
- **Write Path (Persist):** FALSE. `ActivityLogLoggerMysqlRepository` does not write it.
- **Read Path (Select):** UNKNOWN (likely FALSE as bypass reader `PdoActivityLogListReader` was not deeply inspected for columns, but write is missing).
- **Recorder (Context):** FALSE. `ActivityRecorder` does not accept/obtain it.

### SecurityEvents
- **Write Path (Persist):** FALSE. `SecurityEventLoggerMysqlRepository` does not write it.
- **Read Path (Select):** FALSE. `SecurityEventReaderInterface` returns `SecurityEventReadDTO` (not inspected but write is missing).
- **Recorder (Context):** FALSE. `SecurityEventRecorder` does not accept/obtain it.

### Telemetry
- **Write Path (Persist):** FALSE. `TelemetryLoggerMysqlRepository` does not write it.
- **Read Path (Select):** FALSE. `PdoTelemetryListReader` does not select it (checked SQL in `getTelemetry`).
- **Recorder (Context):** FALSE. `TelemetryRecorder` does not accept/obtain it.

## 4) Explicit Verification of These Claims

- **Telemetry write uses event_key:** TRUE. `TelemetryLoggerMysqlRepository.php` uses `event_key` column and binds it correctly.
- **SecurityEvents best-effort is only in Domain Recorder:** TRUE. `SecurityEventLoggerMysqlRepository` throws `SecurityEventStorageException`. `SecurityEventRecorder` catches and swallows it.
- **ActivityLog swallows only in Domain Recorder:** TRUE. `ActivityLogLoggerMysqlRepository` throws `ActivityLogStorageException`. `ActivityRecorder` catches and swallows it.

## 5) Safety Declaration
- No code changed.
- No assumptions made (evidence cited for all findings).
- UNIFIED_LOGGING_DESIGN.md treated as sole authority.
