# MODULE CONFORMANCE PATCH PLAN (NO CODE)

## 0. Executive Summary
- **Compliance Status:**
  - **ActivityLog:** Non-Compliant (High Severity). Schema violations, Naming violations, Crash risk.
  - **SecurityEvents:** Non-Compliant (High Severity). Reader contract violation (Hard Gate), Schema violations.
  - **Telemetry:** Non-Compliant (Medium Severity). Naming mismatch (Hard Gate), Schema violations.

- **Violations Count:**
  - **Critical/Hard Gates:** 3
  - **Major (Schema/Contract):** 4
  - **Minor (Naming):** 3

- **Top 5 Highest-Risk Violations:**
  1. **SecurityEvents Reader uses `array $filters`** (Section 7.1: "Hard Gates: Readers MUST NOT accept `array $filters`. Typed `QueryDTO` is mandatory").
  2. **Telemetry Write Path uses `eventType` property** (Section 5.3.3: "Hard Gate: `event_type` is not a valid telemetry column name for the write path").
  3. **Missing `correlation_id` in all Modules** (Section 5.3.1: "Shared Columns... `correlation_id` ... MUST exist and MUST be used consistently").
  4. **ActivityLog `metadata` handling risks SQL error** (Section 0.1: "No Swallow"... implicit crash risk due to `json_encode` of null into NOT NULL column).
  5. **ActivityLog Interface Naming** (Section 3.1: "Module Layout... `<Subsystem>LoggerInterface.php`").

## 1. Canonical Requirements Checklist

### 1.1 ActivityLog
- [x] **No Swallow in Module:** Compliant (Throws `ActivityLogStorageException`).
- [ ] **Schema Contract (Section 5.3):** FAILED. Missing `correlation_id`, `route_name`. `metadata` handling incorrect.
- [ ] **Layering & Ownership:** FAILED. Interface named `WriterInterface` instead of `LoggerInterface`.
- [ ] **Read-side Rules:** N/A (No Reader implemented).
- [x] **Context Hydration:** Compliant (Recorder exists).

### 1.2 SecurityEvents
- [x] **No Swallow in Module:** Compliant (Throws `SecurityEventStorageException`).
- [ ] **Schema Contract (Section 5.3):** FAILED. Missing `correlation_id`.
- [ ] **Read-side Rules (Section 7):** FAILED. Uses `array $filters` instead of `QueryDTO`.
- [x] **Layering & Ownership:** Compliant (correct interface names).
- [x] **Context Hydration:** Compliant.

### 1.3 Telemetry
- [x] **No Swallow in Module:** Compliant (Throws `TelemetryStorageException`).
- [ ] **Schema Contract (Section 5.3):** FAILED. `event_type` property used in DTO vs `event_key` in schema. Missing `correlation_id`.
- [x] **Layering & Ownership:** Compliant.
- [x] **Read-side Rules:** Compliant (Uses `TelemetryTraceListQueryDTO`).

## 2. Drift Inventory (Per Module)

### 2.1 ActivityLog
| Drift ID | File | Layer | Current Behavior | Expected Behavior | Rule Violated | Severity | Minimal Fix Scope |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| AL-01 | `Contracts/ActivityLogWriterInterface.php` | Module | Interface named `ActivityLogWriterInterface`, method `write` | Named `ActivityLogLoggerInterface`, method `log` | 3.1 Module Layout | Minor | Rename file & method |
| AL-02 | `DTO/ActivityLogDTO.php` | Module | Named `ActivityLogDTO`, missing `correlation_id`, `route_name` | Named `ActivityLogEntryDTO`, add missing fields | 3.1, 5.3.1 | Major | Rename class, add props |
| AL-03 | `Infrastructure/Mysql/ActivityLogLoggerMysqlRepository.php` | Module | Implements `WriterInterface`, writes `null` to `metadata` | Implement `LoggerInterface`, write `[]` to `metadata`, map new fields | 0.1, 5.3.1 | Critical | Update impl |
| AL-04 | `Domain/ActivityLog/Recorder/ActivityRecorder.php` | Domain | Calls `writer->write` | Calls `logger->log` with new DTO | 2.2 Domain Recorder | Major | Update call site |

### 2.2 SecurityEvents
| Drift ID | File | Layer | Current Behavior | Expected Behavior | Rule Violated | Severity | Minimal Fix Scope |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| SE-01 | `Contracts/SecurityEventReaderInterface.php` | Module | `paginate(array $filters, ...)` | `paginate(SecurityEventQueryDTO $query, ...)` | 7.1 Read-Side | Critical | Change signature |
| SE-02 | `DTO/SecurityEventDTO.php` | Module | Named `SecurityEventDTO`, missing `correlation_id` | Named `SecurityEventEntryDTO`, add `correlation_id` | 3.1, 5.3.1 | Major | Rename class, add prop |
| SE-03 | `DTO/SecurityEventReadDTO.php` | Module | Missing `correlation_id` | Add `correlation_id` | 5.3.1 | Major | Add prop |
| SE-04 | `Infrastructure/Mysql/SecurityEventReaderMysqlRepository.php` | Module | Uses `array $filters`, missing `correlation_id` mapping | Use `SecurityEventQueryDTO`, map `correlation_id` | 7.1, 5.3.1 | Critical | Update logic |
| SE-05 | `Infrastructure/Mysql/SecurityEventLoggerMysqlRepository.php` | Module | Missing `correlation_id` mapping | Map `correlation_id` | 5.3.1 | Major | Update insert |
| SE-06 | `Domain/SecurityEvents/Recorder/SecurityEventRecorder.php` | Domain | No `correlation_id` | Pass `correlation_id` | 8.1 | Major | Update mapping |
| SE-NEW | `DTO/SecurityEventQueryDTO.php` | Module | **MISSING** | Create class | 7.1 | Critical | Create file |

### 2.3 Telemetry
| Drift ID | File | Layer | Current Behavior | Expected Behavior | Rule Violated | Severity | Minimal Fix Scope |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| TL-01 | `DTO/TelemetryEventDTO.php` | Module | Named `TelemetryEventDTO`, property `eventType`, missing `correlation_id` | Named `TelemetryEntryDTO`, property `eventKey`, add `correlation_id` | 3.1, 5.3.3 | Critical | Rename class/prop, add prop |
| TL-02 | `DTO/TelemetryTraceReadDTO.php` | Module | Missing `correlation_id` | Add `correlation_id` | 5.3.1 | Major | Add prop |
| TL-03 | `Infrastructure/Mysql/TelemetryLoggerMysqlRepository.php` | Module | Maps `eventType`, missing `correlation_id` | Map `eventKey`, map `correlation_id` | 5.3.3, 5.3.1 | Major | Update insert |
| TL-04 | `Infrastructure/Mysql/TelemetryTraceReaderMysqlRepository.php` | Module | Missing `correlation_id` mapping | Map `correlation_id` | 5.3.1 | Major | Update select/map |
| TL-05 | `Domain/Telemetry/Recorder/TelemetryRecorder.php` | Domain | Uses `eventType`, missing `correlation_id` | Use `eventKey`, pass `correlation_id` | 5.3.3 | Major | Update mapping |

## 3. Sequenced Patch Plan (NO CODE)

### 3.1 ActivityLog Patch
1.  **Rename DTO:** Rename `ActivityLogDTO` to `ActivityLogEntryDTO`. Add `correlation_id` and `route_name` properties.
2.  **Rename Interface:** Rename `ActivityLogWriterInterface` to `ActivityLogLoggerInterface`. Change method `write(ActivityLogDTO)` to `log(ActivityLogEntryDTO)`.
3.  **Fix Repository:** Update `ActivityLogLoggerMysqlRepository` to implement `ActivityLogLoggerInterface`. Update `write` to `log`. Update `INSERT` statement to include `correlation_id` and `route_name`. Fix `metadata` null check to default to `[]`.
4.  **Fix Recorder:** Update `ActivityRecorder` to use `ActivityLogLoggerInterface` and `ActivityLogEntryDTO`. Map fields correctly.

### 3.2 SecurityEvents Patch
1.  **Create QueryDTO:** Create `App\Modules\SecurityEvents\DTO\SecurityEventQueryDTO`. Define properties matching the old array keys (`actor_id`, `event_type`, etc.).
2.  **Rename EntryDTO:** Rename `SecurityEventDTO` to `SecurityEventEntryDTO`. Add `correlation_id`.
3.  **Update ReadDTO:** Update `SecurityEventReadDTO` to include `correlation_id`.
4.  **Fix Reader Interface:** Change `paginate` signature in `SecurityEventReaderInterface` to accept `SecurityEventQueryDTO`.
5.  **Fix Reader Repository:** Update `SecurityEventReaderMysqlRepository` to use `SecurityEventQueryDTO` getters instead of array keys. Update `SELECT` and `mapRowToDTO` to handle `correlation_id`.
6.  **Fix Logger Repository:** Update `SecurityEventLoggerMysqlRepository` to handle `SecurityEventEntryDTO` and persist `correlation_id`.
7.  **Fix Recorder:** Update `SecurityEventRecorder` to use `SecurityEventEntryDTO` and pass `correlation_id`.

### 3.3 Telemetry Patch
1.  **Rename EntryDTO:** Rename `TelemetryEventDTO` to `TelemetryEntryDTO`. Rename `eventType` property to `eventKey`. Add `correlation_id`.
2.  **Update ReadDTO:** Update `TelemetryTraceReadDTO` to include `correlation_id`.
3.  **Fix Logger Repository:** Update `TelemetryLoggerMysqlRepository` to use `TelemetryEntryDTO`. Map `$dto->eventKey` to `event_key` column. Map `correlation_id`.
4.  **Fix Reader Repository:** Update `TelemetryTraceReaderMysqlRepository` to map `correlation_id` in `mapRowToDTO`.
5.  **Fix Recorder:** Update `TelemetryRecorder` to use `TelemetryEntryDTO`. Pass `eventKey` and `correlation_id`.

## 4. Cross-Module Unification Actions
- **Correlation ID Standard:** All DTOs and Repositories are adding `correlation_id`. Ensure the source of `correlation_id` in Recorders is consistent (likely from a `RequestContext` object, though Recorders currently seem to not have it injected or passed. The plan assumes it is available or nullable). *Note: The Recorders currently take DTOs or scalar arguments. The Domain layer calling the Recorder is responsible for passing the ID.*

## 5. Verification Plan (NO CODE)

### 5.1 Manual Verification Steps
1.  **Interface Check:** Run `ls -R app/Modules` to verify `*LoggerInterface.php` naming and `*QueryDTO.php` existence.
2.  **Signature Check:** Read `SecurityEventReaderInterface.php` to confirm `array $filters` is gone.
3.  **Schema Check:** Read `*EntryDTO.php` files to confirm `correlation_id` is present.
4.  **Property Check:** Read `TelemetryEntryDTO.php` to confirm `eventKey` exists and `eventType` is gone.
5.  **Null Safety:** Inspect `ActivityLogLoggerMysqlRepository` to confirm `metadata` falls back to `[]` or valid JSON.

### 5.2 Confirmation Outcome
- All Modules MUST have `LoggerInterface`.
- All Readers MUST use `QueryDTO`.
- All Logging Tables MUST be written to with `correlation_id`.
- Telemetry MUST write to `event_key`.

## 6. Final Safety Declaration
- **No code changed:** This is a plan document only.
- **No assumptions made:** Violations verified against codebase.
- **Authority:** `UNIFIED_LOGGING_DESIGN.md` treated as supreme law.
