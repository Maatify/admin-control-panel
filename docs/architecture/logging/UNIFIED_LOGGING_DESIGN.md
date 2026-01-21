# UNIFIED_LOGGING_DESIGN.md

**Status:** LOCKED  
**Project:** maatify/admin-control-panel  
**Purpose:** Canonical design for all logging subsystems (existing remediation and future extensions)  
**Audience:** Maintainers, reviewers, AI agents, future contributors  
**Rule:** ZERO-AMBIGUITY

---

## 0) Non-Negotiable Global Rules

1) **No Swallow Inside Any Extractable Module**
   - Any Module intended to be “library-like” MUST NOT swallow exceptions.
   - Modules MUST throw **custom exceptions only** (subsystem-specific).
   - Swallow / fail-open policy is a **Domain / Application responsibility** only.

2) **No Cross-Contamination Between Log Types**
   - Each log type has a strict purpose and **MUST NOT write into another type’s storage**.
   - **Audit** tables/flows must never be written by Telemetry/Security/Activity.

3) **Honest Contracts**
   - Any interface that can fail MUST declare its thrown exceptions (docblock or signature).
   - “Best-effort” is a **policy**, not a contract lie.

4) **DB is Infrastructure**
   - Only Infrastructure drivers talk to PDO/DB.
   - Domain does not know PDO for logging subsystems (target design).

---

## 1) Canonical Terminology Lock

### 1.1 Audit (Authoritative)
- **Authoritative + transactional**
- Used for security-critical, compliance-grade evidence.
- MUST be written within real transactions (e.g., outbox inside tx).
- **Not best-effort.**
- **Never** used for UX/observability.

### 1.2 SecurityEvents (Observational)
- Observational signals (suspicious login attempts, step-up failures, policy denials…).
- Best-effort at the policy layer (Domain may swallow).
- Never relied upon for correctness.

### 1.3 ActivityLog (Operational History)
- “Who did what” operational history (non-authoritative).
- Best-effort at the policy layer (Domain may swallow).
- Never relied upon for correctness.

### 1.4 Telemetry (Observability)
- Performance/diagnostics/trace-level events.
- Best-effort at the policy layer (Domain may swallow).
- Never relied upon for correctness.

### 1.5 Access / Page View Tracking (UX Navigation)
- Admin moving between pages is a **VIEW** signal.
- Classified as **Telemetry.Access** (NOT Audit, NOT Security, NOT Activity).
- Implemented as a dedicated recorder in the Domain, writing to Telemetry storage.

---

## 2) Canonical Architecture Layers

### 2.1 The “Module” Layer (Library-like Core)
**Goal:** Portable, strict, honest, reusable.

A subsystem module MUST contain:
- Contracts (Logger/Reader)
- DTOs (Entry/Read/Query)
- Enums (Type/Severity/Action as needed)
- Exceptions (Storage/Mapping/Validation)
- Infrastructure reference implementations (PDO/MySQL) — optional but preferred

> Note:
> Enums and ValidationException classes MUST be introduced **only when the subsystem semantics require them**.
> Subsystems with simple or unambiguous behavior SHOULD avoid unnecessary boilerplate.

**Key rule:** Module throws; Module never swallows.

### 2.2 The “Domain Recorder” Layer (Policy)
**Goal:** Best-effort + context hydration + routing.

Domain recorders:
- Convert domain intent into module DTOs.
- Attach RequestContext (ip, ua, request_id, correlation_id, admin_id).
- Decide the failure policy (swallow / retry / degrade).
- NEVER write SQL directly.

---

## 3) Canonical Folder Layout

### 3.1 Module Layout (Required)
For each subsystem:

```

app/Modules/<Subsystem>/
├── Contracts/
│   ├── <Subsystem>LoggerInterface.php      # throws <Subsystem>StorageException
│   ├── <Subsystem>ReaderInterface.php      # throws <Subsystem>StorageException / MappingException
│   └── (Optional) ContextInterface.php
├── DTO/
│   ├── <Subsystem>EntryDTO.php
│   ├── <Subsystem>ReadDTO.php
│   └── <Subsystem>QueryDTO.php             # MUST be typed (no array filters)
├── Enums/
│   ├── <Subsystem>TypeEnum.php
│   └── <Subsystem>SeverityEnum.php         # if applicable
├── Exceptions/
│   ├── <Subsystem>StorageException.php
│   ├── <Subsystem>MappingException.php
│   └── <Subsystem>ValidationException.php  # if applicable
└── Infrastructure/
    └── Mysql/
        ├── <Subsystem>LoggerMysqlRepository.php   # PDO writes; throws only
        └── <Subsystem>ReaderMysqlRepository.php   # PDO reads; throws only

```

### 3.2 Domain Recorder Layout (Required)
```

app/Domain/<Subsystem>/Recorder/
└── <Subsystem>Recorder.php

```

**No swallow in Module. Swallow lives here (if Best-effort).**

---

## 4) Exception Policy (Canonical)

### 4.1 Standard Exception Set (Per Subsystem)
Each subsystem MUST define:
- `<Subsystem>StorageException`
  - DB failures, PDO failures, constraints, connectivity.
- `<Subsystem>MappingException`
  - JSON encode/decode, row mapping, invalid formats.
- `<Subsystem>ValidationException` (Optional)
  - Only if validation is inside module boundaries (prefer Domain validation).

### 4.2 Throw Rules
- Infrastructure catches `PDOException` and rethrows `<Subsystem>StorageException`.
- JSON encode/decode errors -> `<Subsystem>MappingException`.
- **No RuntimeException leak** from module code.

### 4.3 Swallow Rules
- Only Domain Recorder may swallow.
- Swallow is allowed only for:
  - Telemetry
  - SecurityEvents
  - ActivityLog
- Audit is **never swallow**.
  - Any Audit failure MUST propagate and abort the calling operation.

---

## 5) Storage Ownership Rules (Canonical)

### 5.1 Table Ownership
- `audit_outbox` / `audit_logs`:
  - Owned by an Authoritative Audit pipeline only.
  - MUST NOT be written by Telemetry/SecurityEvents/ActivityLog.
- `security_events`:
  - Owned by SecurityEvents module.
- `activity_logs`:
  - Owned by ActivityLog module.
- `telemetry_traces` (or equivalent):
  - Owned by Telemetry module.
- Access/page views MUST go to Telemetry storage (not audit_logs).

### 5.2 “No Wrong Table” Rule (Hard Blocker)
If any subsystem writes into another subsystem’s table, it is a **hard architectural violation** and must be rejected.

---

## 6) Public API Rules

### 6.1 Module API
- Modules export:
  - LoggerInterface, ReaderInterface
  - DTOs
  - Enums
  - Exceptions

### 6.2 Domain API
- Application code SHOULD call:
  - `Domain/<Subsystem>/Recorder/<Subsystem>Recorder`
- Application code SHOULD NOT call:
  - module logger repositories directly
  - PDO drivers directly

---

## 7) Query / Read Rules

1) Read filters MUST be typed (`QueryDTO`).
2) No `array $filters` in Reader signatures (canonical ban).
3) Reader returns DTO objects, not raw arrays.

---

## 8) Correlation & Request IDs

### 8.1 correlation_id
- Used to correlate events across subsystems and requests.
- Generated at the request boundary and reused across recorders.

### 8.2 request_id
- Request-scoped ID (traceability).
- Stored in ActivityLog and Telemetry where applicable.

---

## 9) Migration Strategy for Current Code

### 9.1 Identify “Split Brain” & Duplicates
- If both `app/Infrastructure/...` and `app/Modules/...` implement same writer:
  - Target design: keep the driver in module (PDO) and bind it in a container.
  - Infrastructure copy should be removed or converted to thin adapter only.

### 9.2 Normalize Contracts
- Any interface promising “no throws” while implementation throws is a contract lie.
- Fix by:
  - Declaring thrown exceptions in the interface, OR
  - Moving swallow to Domain recorder explicitly.

### 9.3 Extract Swallow Out of Modules (If Exists)
- If a module currently swallows (e.g., service entry point):
  - Replace swallow with explicit throw (custom exception).
  - Create/extend Domain recorder to swallow per policy.

### 9.4 Fix Access/PageView Stub
- Keep the current stub (interface and empty class) but ensure:
  - It is classified as Telemetry.Access.
  - It does not write to Audit tables.
  - It is called only via Domain recorder.

---

## 10) “Add New Log Type” Checklist (Mandatory)

When introducing a new log type/subsystem:

1) Define its classification:
   - Audit vs. SecurityEvents vs. ActivityLog vs. Telemetry vs. Telemetry.Access

2) Create Module skeleton:
   - Contracts, DTOs, Enums, Exceptions, Infrastructure/Mysql driver.

3) Define storage table:
   - MUST be a dedicated table owned by the subsystem.

4) Create Domain recorder:
   - Maps context, decides swallow policy, calls module logger.

5) Add API docs entry (if exposed):
   - Ensure it appears in docs/API_PHASE1.md (project requirement).

6) Add tests:
   - Module driver tests (if applicable)
   - Domain recorder behavior test (swallow vs. fail-closed)

---

## 11) Enforcement Rules for Review (Hard Gates)

A change MUST be rejected if it violates any of:
- Telemetry writes into audit_logs
- Module swallows exceptions
- Interface hides throws (dishonest contract)
- Reader uses array filters instead of QueryDTO
- Domain code writes SQL for logging (except existing legacy, explicitly flagged)

---

## 12) Current Known Legacy Debt (Documented, Not Expanded)

Some existing Domain services may use PDO for transaction control.
This is legacy architecture and is **not expanded** by new work.
Target state is to move transaction orchestration to application boundary or dedicated infra services.

---

## 13) Out of Scope (Future Enhancements)

This section documents **explicitly excluded topics** from this design.
Their absence is **intentional**, not accidental.

The purpose of this document is to define **canonical structure, ownership, and failure semantics** — not implementation optimizations or deployment strategies.

---

### 13.1 Asynchronous / High-Throughput Logging

Out of scope:

- Queues (Redis, RabbitMQ, Kafka, SQS, etc.)
- Fire-and-forget logging
- Batch flushing
- Background workers
- Sampling strategies
- Rate limiting of log writes

Rationale:
- These are **infrastructure and deployment concerns**, not architectural boundaries.
- The current design intentionally supports **synchronous implementations** to keep semantics explicit and failure modes visible.
- Asynchronous logging MAY be introduced later **behind existing Module contracts** without changing:
    - Domain Recorder logic
    - Failure policy
    - Ownership rules

---

### 13.2 Example: Extending with Async Telemetry (Illustrative Only)

The following example illustrates how asynchronous logging MAY be added
without violating this design.

```php
final class AsyncTelemetryLogger implements TelemetryLoggerInterface
{
    public function log(TelemetryEntryDTO $dto): void
    {
        // enqueue payload to background worker
        // MUST throw TelemetryStorageException on enqueue failure
    }
}
```

Usage remains unchanged at the Domain level:

```php
try {
    $this->telemetryLogger->log($entry);
} catch (TelemetryStorageException $e) {
    // Domain decides whether to swallow or escalate
}
```

This example is **illustrative only**.
No asynchronous behavior is mandated or implied by this design.

---

### 13.3 Storage Backend Variants (Non-MySQL)

Out of scope:

- PostgreSQL implementations
- NoSQL stores (Elasticsearch, ClickHouse, etc.)
- File-based logging
- Cloud-native log sinks

Rationale:
- All Modules are already structured around **Infrastructure drivers**.
- Swapping storage backends is a matter of providing alternative drivers under:
  `Infrastructure/<Backend>/`
- Locking a specific backend is not required to validate:
    - Layering
    - Exception policy
    - Ownership boundaries

---

### 13.4 Audit Integrity Hardening

Out of scope:

- Hash chaining
- WORM / append-only enforcement
- Cryptographic signatures
- Tamper detection
- Legal/compliance retention rules

Rationale:
- These concerns belong to a **dedicated Audit Integrity Model**.
- This document only defines **who is allowed to write audit data and how**.
- Integrity hardening will be addressed in a separate, focused design document.

---

### 13.5 Log Retention, Rotation, and Archival

Out of scope:

- TTL policies
- Table partitioning
- Archival jobs
- Compression
- Purging strategies

Rationale:
- Retention policies are operational and regulatory concerns.
- They vary by environment (dev, staging, prod) and jurisdiction.
- They do not affect logging **semantics or correctness**.

---

### 13.6 Logging Levels and PSR-3 Compatibility

Out of scope:

- PSR-3 log levels (`debug`, `info`, `warning`, etc.)
- Integration with PSR-3 compatible loggers
- Generic “logger” abstractions

Rationale:
- This system models **domain-specific logging subsystems**, not generic application logging.
- Each subsystem has stronger semantics than PSR-3 levels.
- PSR-3 MAY be used at the application boundary, but is intentionally excluded from Modules.

---

### 13.7 Testing Strategy & CI Enforcement

Out of scope:

- Unit vs. integration test strategy
- Mocking guidelines
- CI/CD enforcement rules
- Coverage thresholds

Rationale:
- Testing strategy is defined at the project level.
- This document defines **design constraints**, not process enforcement.

---

### 13.8 Legacy Refactoring Timelines

Out of scope:

- Deadlines for removing legacy PDO usage in Domain services
- Step-by-step refactor plans

Rationale:
- Legacy handling is explicitly documented in Section 12.
- This design does not mandate timelines, only **non-expansion of legacy patterns**.

---

### 13.9 Non-Goals Summary

This document intentionally does NOT aim to:

- Optimize performance
- Minimize boilerplate
- Reduce class count
- Provide implementation shortcuts
- Serve as a general-purpose logging framework

Its sole goal is to:
> **Prevent semantic corruption, ownership violations, and unsafe refactors.**

---

### 13.10 Recommended Follow-Up Designs (Non-Binding)

The following designs are **recommended future work**, ordered by architectural impact.
This list is **informational only** and does NOT imply priority, commitment, or timeline.

1) **Audit Integrity Model**
    - Tamper resistance
    - Cryptographic chaining
    - Append-only enforcement
    - Compliance-grade immutability

2) **Asynchronous Telemetry Pipeline**
    - Queue-backed telemetry writers
    - Fire-and-forget adapters behind existing contracts
    - Optional sampling strategies

3) **Retention & Archival Policies**
    - Table partitioning
    - TTL-based pruning
    - Cold storage strategies

These designs MUST respect all rules defined in this document and MUST NOT introduce:
- Exception swallowing inside Modules
- Cross-subsystem storage writes
- Contract dishonesty


**END OF DOCUMENT — LOCKED**
