# STRUCTURE-FIRST, LIBRARY-EXTRACTION AUDIT

## 0. Global Terminology Lock
* **Audit**: Authoritative + transactional (NOT in scope here).
* **SecurityEvents**: Observational / best-effort / non-authoritative.
* **ActivityLog**: Operational history / best-effort / non-authoritative.
* **Telemetry**: Observability / best-effort / non-authoritative.

---

## 1. Subsystem: ActivityLog

### 0) Terminology Lock
ActivityLog is operational history / best-effort / non-authoritative.

### 1) Complete Inventory
**Module Root:** `app/Modules/ActivityLog`

*   `app/Modules/ActivityLog/README.md` (Docs)
*   `app/Modules/ActivityLog/HOW_TO_USE.md` (Docs)
*   `app/Modules/ActivityLog/DTO/ActivityLogDTO.php`
    *   **Responsibility:** Immutable data carrier for activity log entries.
    *   **Dependencies:** `DateTimeImmutable` (Core PHP).
    *   **Portability:** Portable.
*   `app/Modules/ActivityLog/Enums/CoreActivityAction.php`
    *   **Responsibility:** Enum for standard activity actions.
    *   **Dependencies:** `App\Modules\ActivityLog\Contracts\ActivityActionInterface`.
    *   **Portability:** Portable.
*   `app/Modules/ActivityLog/Contracts/ActivityActionInterface.php`
    *   **Responsibility:** Contract for action enums/classes.
    *   **Dependencies:** None.
    *   **Portability:** Portable.
*   `app/Modules/ActivityLog/Contracts/ActivityLogWriterInterface.php`
    *   **Responsibility:** Contract for persistence drivers.
    *   **Dependencies:** `App\Modules\ActivityLog\DTO\ActivityLogDTO`, `Throwable`.
    *   **Portability:** Portable.
*   `app/Modules/ActivityLog/Service/ActivityLogService.php`
    *   **Responsibility:** Main entry point, orchestrates logging, ensures fail-open behavior.
    *   **Dependencies:** `ActivityLogWriterInterface`, `ActivityActionInterface`, `ActivityLogDTO`, `DateTimeImmutable`.
    *   **Portability:** Portable.
*   `app/Modules/ActivityLog/Drivers/MySQL/MySQLActivityLogWriter.php`
    *   **Responsibility:** MySQL implementation of Writer.
    *   **Dependencies:** `PDO`, `ActivityLogWriterInterface`, `ActivityLogDTO`.
    *   **Portability:** Portable (Requires PDO).
*   `app/Modules/ActivityLog/Traits/ActivityLogAwareTrait.php`
    *   **Responsibility:** Convenience trait for injecting service.
    *   **Dependencies:** `ActivityLogService`.
    *   **Portability:** Portable.
*   `app/Modules/ActivityLog/Traits/ActivityLogStaticTrait.php`
    *   **Responsibility:** Static proxy wrapper (Legacy/Helper style).
    *   **Dependencies:** `ActivityLogService`.
    *   **Portability:** Portable.

**Surrounding Layers (App Glue):**
*   `app/Domain/ActivityLog/Service/AdminActivityLogService.php` (Domain Wrapper)
*   `app/Infrastructure/ActivityLog/MySQLActivityLogWriter.php` (App Driver - Duplicate)

### 2) Layer Map
*   **Pure Module (Portable Library Core):**
    *   `Contracts/*`
    *   `DTO/*`
    *   `Enums/*`
    *   `Service/ActivityLogService.php`
    *   `Drivers/MySQL/*` (Reference implementation)
*   **Domain Policy Layer:**
    *   `app/Domain/ActivityLog/Service/AdminActivityLogService.php`: Enforces `AdminContext`, `RequestContext`, and specific Actor Type ('admin').
*   **Application Context Layer:**
    *   `app/Infrastructure/ActivityLog/MySQLActivityLogWriter.php`: Binds `PDOFactory` instead of `PDO`.

### 3) End-to-End Execution Graph

**A) Library Core Write Path**
```
[Client Code]
    │
    ▼
ActivityLogService::log()  ──(creates)──> ActivityLogDTO
    │
    │ (try/catch boundary: swallows Throwable)
    ▼
ActivityLogWriterInterface::write(ActivityLogDTO)
    │
    ▼
MySQLActivityLogWriter::write() ──> [DB: activity_logs]
```

**B) Full Application Usage Path**
```
AdminController/Service
    │
    ▼
AdminActivityLogService::log(AdminContext, RequestContext, ...)
    │
    │ (Extracts: adminId, ip, ua, requestId)
    │ (Sets: actorType='admin')
    ▼
ActivityLogService::log()
    │
    ▼
ActivityLogWriterInterface (Impl: App\Infrastructure\...\MySQLActivityLogWriter)
    │
    ▼
[DB: activity_logs]
```

### 4) Public API Surface (Library Contract)
**Exported:**
*   `ActivityLogService` (Main Class)
*   `ActivityLogWriterInterface` (SPI)
*   `ActivityActionInterface` (Contract)
*   `ActivityLogDTO` (Data)
*   `CoreActivityAction` (Enum)

**MUST NOT Export (App Glue):**
*   `AdminActivityLogService` (Depends on App Contexts)
*   `App\Infrastructure\...\MySQLActivityLogWriter` (Depends on PDOFactory)

### 5) Failure Semantics Audit
*   **Origin:** `MySQLActivityLogWriter::write` (throws `RuntimeException` on failure).
*   **Swallowed:** `ActivityLogService::log` catches `\Throwable` and swallows it completely.
*   **Contract:** `ActivityLogWriterInterface` docblock says "Implementations MUST... NOT throw domain exceptions... @throws Throwable Infrastructure failures only".
*   **Policy Style:** **A) “Safe-by-default”** (Library explicitly swallows at the service entry point).

### 6) Strengths / Weaknesses / Shoib
*   **Strengths:**
    *   Clear separation of concern (`Service` vs `Writer`).
    *   Fail-open design is explicitly implemented in the Service.
    *   Portable DTOs.
    *   Good interface abstraction.
*   **Weaknesses:**
    *   **Duplicate Drivers:** `app/Modules/.../MySQLActivityLogWriter` is unused; app uses `app/Infrastructure/.../MySQLActivityLogWriter` which introduces `PDOFactory` dependency.
    *   **Split Brain (Read/Write):** Read-side logic (`Reader`, `ListDTO`) is entirely missing from `app/Modules` and lives in `app/Infrastructure` and `app/Domain`. The module is "Write-Only" in practice.

### 7) Library Extraction Readiness Score
**Score: 8/10**

*   **Extraction Risks:**
    *   The Read-side is missing from the module; extracting it would leave the library as a "Logger" only, while the App retains the "Reader".
    *   The app uses a custom driver in `Infrastructure` instead of the one in `Modules`. The extraction must decide whether to standardize on `PDO` (Module) or `PDOFactory` (App).

### Call-Site Map
*   `app/Bootstrap/Container.php` :: `new ActivityLogService`
*   `app/Domain/ActivityLog/Service/AdminActivityLogService.php` :: `ActivityLogService::log` (Primary Usage)
*   `app/Infrastructure/ActivityLog/MySQLActivityLogWriter.php` :: `implements ActivityLogWriterInterface`

---

## 2. Subsystem: SecurityEvents

### 0) Terminology Lock
SecurityEvents is observational / best-effort / non-authoritative.

### 1) Complete Inventory
**Module Root:** `app/Modules/SecurityEvents`

*   `DTO/SecurityEventDTO.php` (Portable, Module Core)
*   `DTO/SecurityEventReadDTO.php` (Portable, Module Core)
*   `Contracts/SecurityEventLoggerInterface.php` (Portable)
*   `Contracts/SecurityEventReaderInterface.php` (Portable)
*   `Contracts/SecurityEventContextInterface.php` (Portable, but implies context injection)
*   `Contracts/SecurityEventStorageInterface.php` (Infra Contract)
*   `Enum/SecurityEventSeverityEnum.php` (Portable)
*   `Enum/SecurityEventTypeEnum.php` (Portable)
*   `Exceptions/SecurityEventStorageException.php` (Portable)
*   `Exceptions/SecurityEventRowMappingException.php` (Portable)
*   `Infrastructure/Mysql/SecurityEventLoggerMysqlRepository.php` (Driver)
*   `Infrastructure/Mysql/SecurityEventReaderMysqlRepository.php` (Driver)

**Surrounding Layers (App Glue):**
*   `app/Domain/SecurityEvents/Recorder/SecurityEventRecorder.php` (Domain Wrapper)
*   `app/Application/SecurityEvents/HttpSecurityEventAdminRecorder.php` (App Wrapper)

### 2) Layer Map
*   **Pure Module (Portable Library Core):**
    *   `Contracts/*`
    *   `DTO/*`
    *   `Enum/*`
    *   `Exceptions/*`
    *   `Infrastructure/Mysql/*` (Self-contained implementation)
*   **Domain Policy Layer:**
    *   `app/Domain/SecurityEvents/Recorder/SecurityEventRecorder.php`: Converts `DomainDTO` to `ModuleDTO`, swallows exceptions.

### 3) End-to-End Execution Graph

**A) Library Core Write Path**
```
[Client Code]
    │
    ▼
SecurityEventLoggerInterface::log(SecurityEventDTO)
    │
    ▼
SecurityEventLoggerMysqlRepository::log()
    │
    ▼
SecurityEventLoggerMysqlRepository::store()
    │ (throws SecurityEventStorageException on error)
    ▼
[DB: security_events]
```

**B) Full Application Usage Path**
```
StepUpService / AuthController
    │
    ▼
SecurityEventRecorder::record(SecurityEventRecordDTO)
    │ (Maps DomainDTO -> ModuleDTO)
    │
    ▼
SecurityEventLoggerInterface::log(SecurityEventDTO)
    │
    │ (try/catch boundary in Recorder: swallows SecurityEventStorageException)
    ▼
[DB: security_events]
```

### 4) Public API Surface (Library Contract)
**Exported:**
*   `SecurityEventLoggerInterface`
*   `SecurityEventReaderInterface`
*   `SecurityEventDTO` / `SecurityEventReadDTO`
*   `SecurityEventTypeEnum` / `SecurityEventSeverityEnum`
*   `SecurityEventStorageException`

**MUST NOT Export:**
*   `SecurityEventRecorder` (Domain specific)

### 5) Failure Semantics Audit
*   **Origin:** `SecurityEventLoggerMysqlRepository` catches `Throwable`, rethrows `SecurityEventStorageException`.
*   **Swallowed:** `SecurityEventRecorder` (Domain) catches `SecurityEventStorageException` and swallows.
*   **Contract Violation:** `SecurityEventLoggerInterface` docblock says "Implementations MUST NOT throw runtime exceptions". Implementation **DOES** throw. The contract is dishonest.
*   **Policy Style:** **B) “Low-level throws + wrapper swallows”**. The Module is "unsafe" (throws), the Domain makes it "safe" (swallows).

### 6) Strengths / Weaknesses / Shoib
*   **Strengths:**
    *   **Self-Contained:** Includes both Read and Write sides (Repository, DTOs for both).
    *   **Rich Domain Modeling:** Enums for Event Types and Severity are well defined.
    *   **No App Glue in Module:** The module itself relies only on PDO.
*   **Weaknesses:**
    *   **Dishonest Contract:** Interface promises no throws, but implementation throws.
    *   **Inconsistent Failure Policy:** Relies on the Domain wrapper to be "Best Effort". If someone uses the Module directly, they might crash the app.

### 7) Library Extraction Readiness Score
**Score: 9/10**

*   **Extraction Risks:**
    *   The interface contract needs to be updated to match reality (`@throws`), or the implementation changed to swallow.
    *   Extraction is very safe as it includes the Reader side.

### Call-Site Map
*   `app/Domain/SecurityEvents/Recorder/SecurityEventRecorder.php` :: `SecurityEventLoggerInterface::log`
*   `app/Bootstrap/Container.php` :: `new SecurityEventLoggerMysqlRepository`
*   `app/Context/RequestContext.php` :: `implements SecurityEventContextInterface`

---

## 3. Subsystem: Telemetry

### 0) Terminology Lock
Telemetry is observability / best-effort / non-authoritative.

### 1) Complete Inventory
**Module Root:** `app/Modules/Telemetry`

*   `DTO/TelemetryEventDTO.php`
*   `DTO/TelemetryTraceReadDTO.php`
*   `DTO/TelemetryTraceListQueryDTO.php`
*   `Contracts/TelemetryLoggerInterface.php`
*   `Contracts/TelemetryTraceReaderInterface.php`
*   `Contracts/TelemetryStorageInterface.php`
*   `Contracts/TelemetryContextInterface.php`
*   `Enum/TelemetryEventTypeEnum.php`
*   `Enum/TelemetrySeverityEnum.php`
*   `Exceptions/TelemetryStorageException.php`
*   `Exceptions/TelemetryTraceRowMappingException.php`
*   `Infrastructure/Mysql/TelemetryLoggerMysqlRepository.php`
*   `Infrastructure/Mysql/TelemetryTraceReaderMysqlRepository.php`

**Surrounding Layers (App Glue):**
*   `app/Domain/Telemetry/Recorder/TelemetryRecorder.php` (Domain Wrapper)
*   `app/Domain/Telemetry/DTO/TelemetryRecordDTO.php`

### 2) Layer Map
*   **Pure Module (Portable Library Core):**
    *   All files in `app/Modules/Telemetry`.
*   **Domain Policy Layer:**
    *   `app/Domain/Telemetry/Recorder/TelemetryRecorder.php`: Wraps Logger, swallows exceptions, adds "now" timestamp.

### 3) End-to-End Execution Graph

**A) Library Core Write Path**
```
[Client Code]
    │
    ▼
TelemetryLoggerInterface::log(TelemetryEventDTO)
    │
    ▼
TelemetryLoggerMysqlRepository::log()
    │ (throws TelemetryStorageException)
    ▼
[DB: telemetry_traces]
```

**B) Full Application Usage Path**
```
HttpTelemetryAdminRecorder / Controllers
    │
    ▼
TelemetryRecorder::record(TelemetryRecordDTO)
    │ (Maps DomainDTO -> ModuleDTO)
    │
    │ (try/catch boundary: swallows TelemetryStorageException)
    ▼
TelemetryLoggerInterface::log(TelemetryEventDTO)
    │
    ▼
[DB: telemetry_traces]
```

### 4) Public API Surface (Library Contract)
**Exported:**
*   `TelemetryLoggerInterface`
*   `TelemetryTraceReaderInterface`
*   `TelemetryEventDTO` (and Read/Query DTOs)
*   `TelemetryEventTypeEnum` / `Severity`
*   `TelemetryStorageException`

**MUST NOT Export:**
*   `TelemetryRecorder` (Domain)

### 5) Failure Semantics Audit
*   **Origin:** `TelemetryLoggerMysqlRepository` throws `TelemetryStorageException`.
*   **Swallowed:** `TelemetryRecorder` catches `TelemetryStorageException` and swallows.
*   **Contract:** `TelemetryLoggerInterface` explicitly states `@throws TelemetryStorageException`. **Honest Contract**.
*   **Policy Style:** **B) “Low-level throws + wrapper swallows”**. Explicit and correct.

### 6) Strengths / Weaknesses / Shoib
*   **Strengths:**
    *   **Honest Contracts:** Unlike SecurityEvents, the interface admits it throws.
    *   **Full Feature Set:** Includes Read/Write/Query DTOs.
    *   **Clean Layering:** Strict separation between Domain Recorder and Module Logger.
*   **Weaknesses:**
    *   **Verbosity:** Requires a separate `TelemetryStorageInterface` and `TelemetryLoggerInterface` which seem redundant in `Infrastructure/Mysql`.

### 7) Library Extraction Readiness Score
**Score: 10/10**

*   **Extraction Risks:**
    *   None. This is the most "Library-ready" subsystem.

### Call-Site Map
*   `app/Domain/Telemetry/Recorder/TelemetryRecorder.php` :: `TelemetryLoggerInterface::log`
*   `app/Bootstrap/Container.php` :: `new TelemetryLoggerMysqlRepository`
*   `app/Http/Middleware/HttpRequestTelemetryMiddleware.php` :: Usage of Enums

---

## 4. Final Section: Comparison + Unified Blueprint

### 1) Comparison Matrix

| Dimension | ActivityLog | SecurityEvents | Telemetry |
| :--- | :--- | :--- | :--- |
| **Layering Purity** | High (Service/Writer split) | High (Repository pattern) | High (Repository pattern) |
| **API Clarity** | **Best** (Service enforces fail-open) | Good | Good |
| **Exception Policy** | **Safe-by-default** (Service swallows) | Unsafe (Throws, Contract Dishonest) | Unsafe (Throws, Contract Honest) |
| **Module Completeness** | **Poor** (Write-only, Read is in Domain) | **Excellent** (Read/Write included) | **Excellent** (Read/Write included) |
| **Context Injection** | Via Service Wrapper | Via Context Interface | Via Context Interface |
| **Read/Query Design** | External (App Domain) | Internal (Module DTOs) | Internal (Module DTOs) |
| **Portability** | Good (but requires external Reader) | Excellent | Excellent |
| **Test Strategy** | Easy (Writer Mock) | Easy (Logger Mock) | Easy (Logger Mock) |

### 2) “Best at X” Breakdown

*   **Best at Safety (Fail-Open):** `ActivityLog`.
    *   *Proof:* `ActivityLogService::log` contains the `try { writer->write() } catch` block. The module guarantees safety.
*   **Best at Completeness (Read/Write):** `Telemetry`.
    *   *Proof:* `app/Modules/Telemetry` contains `TelemetryTraceReaderInterface`, `TelemetryTraceReadDTO`, `TelemetryTraceListQueryDTO` AND the Writer components.
*   **Best at Contract Honesty:** `Telemetry`.
    *   *Proof:* `TelemetryLoggerInterface` declares `@throws TelemetryStorageException`, matching the implementation. `SecurityEvents` declares "MUST NOT throw" but does throw.
*   **Best at DTO Discipline:** `Telemetry` / `SecurityEvents`.
    *   *Proof:* Both have dedicated Read/Write DTOs and Enums inside the module. `ActivityLog` lacks Read DTOs in the module.

### 3) Unified Best-of Blueprint (Structure Only)

To create a consistent, portable library family, we should adopt the **Telemetry structure (Completeness + Honesty)** combined with the **ActivityLog Service pattern (Safety)**.

**Target Folder Tree (Generic):**

```
Modules/<Subsystem>/
├── Contracts/
│   ├── <Subsystem>LoggerInterface.php      # Low-level Writer (Throws)
│   ├── <Subsystem>ReaderInterface.php      # Low-level Reader
│   └── <Subsystem>ContextInterface.php     # Optional Context Contract
├── DTO/
│   ├── <Subsystem>EntryDTO.php             # Write Payload
│   ├── <Subsystem>ReadDTO.php              # Read Payload
│   └── <Subsystem>QueryDTO.php             # Filter Payload
├── Enums/
│   ├── <Subsystem>ActionEnum.php
│   └── <Subsystem>SeverityEnum.php
├── Exceptions/
│   └── <Subsystem>StorageException.php
├── Infrastructure/
│   └── Mysql/
│       ├── <Subsystem>LoggerMysqlRepository.php # Implements Logger (Throws)
│       └── <Subsystem>ReaderMysqlRepository.php # Implements Reader
└── Service/                                     # OPTIONAL: The "Safe" Layer
    └── <Subsystem>Recorder.php                  # Wraps Logger, Swallows Exceptions (The "Service" from ActivityLog)
```

**Key Unification Rules:**
1.  **Module Owns Read & Write:** Like Telemetry/SecurityEvents, strictly inside `Modules/`.
2.  **Honest Low-Level Contracts:** Repositories/Loggers explicitly `@throw StorageException`.
3.  **Safe High-Level Service:** A `Recorder` or `Service` class inside the module (or as a standard wrapper) handles the "Fail-Open" policy (Try/Catch/Swallow), adopting the `ActivityLogService` pattern but applied to the honest repositories.
