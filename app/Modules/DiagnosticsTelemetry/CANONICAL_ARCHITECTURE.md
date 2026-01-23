# DiagnosticsTelemetry: Canonical Architecture

**Status:** Final / Authoritative
**Type:** Standalone Library
**Scope:** Strict System Diagnostics

---

## 1. Canonical Purpose & Responsibility

The **DiagnosticsTelemetry** module exists to capture, normalize, and persist system health, debugging, and diagnostic information in a fail-safe manner.

**Responsibility:**
- Provide a standardized API for recording diagnostic events.
- Enforce strict structural contracts (DTOs) on all logged data.
- guarantee "Best Effort" persistence (fail-open).
- Provide a primitive cursor-based mechanism for linear data retrieval (archiving).

**MUST NOT Do:**
- **MUST NOT** handle business logic, auditing, or user activity history.
- **MUST NOT** enforce security policies or authorization.
- **MUST NOT** provide complex querying capabilities (filtering, search, pagination) within the module core.
- **MUST NOT** block the host application's execution flow under any failure condition.

---

## 2. Module Boundary & Public Surface

The module is a strict **Black Box**.

**Public Entry Points (Allowed):**
1.  **Recorder:** `DiagnosticsTelemetryRecorder::record(...)`
    - The *only* mechanism to write data.
2.  **Contracts:** `DiagnosticsTelemetryLoggerInterface`
    - For implementing storage drivers.
3.  **Policy:** `DiagnosticsTelemetryPolicyInterface`
    - For configuring validation/normalization rules.
4.  **Read Contract:** `DiagnosticsTelemetryQueryInterface::read(...)`
    - Strictly for cursor-based sequential access.

**Forbidden Access Patterns:**
- **Direct Instantiation of Infrastructure:** Consumers MUST NOT instantiate or access the Storage Repository directly.
- **Bypassing the Recorder:** Writers MUST NOT bypass the `Recorder` to write directly to storage.
- **Mutable State:** Consumers MUST NOT modify DTOs after instantiation.

---

## 3. Canonical Layered Structure

The module follows a strict unidirectional data flow.

### A. Recorder (`Recorder`)
- **Role:** The Orchestrator / Gatekeeper.
- **Responsibility:** Accepts raw inputs, invokes the **Policy**, creates **DTOs**, and delegates to **Contracts**.
- **Invariants:** NEVER throws exceptions to the caller. Always enforces UTF-8 safety.

### B. Policy (`Policy`)
- **Role:** The Rule Enforcer.
- **Responsibility:** Normalizes inputs (e.g., uppercasing Actor Types) and validates constraints (e.g., Metadata size limits).
- **Invariants:** Pure functions. No side effects.

### C. DTOs (`DTO`)
- **Role:** The Data Carriers.
- **Responsibility:** Immutable, strictly typed transfer objects for Context, Events, and Cursors.
- **Invariants:** Immutable. 1:1 mapping with the canonical schema.

### D. Contracts (`Contract`)
- **Role:** The Abstraction Layer.
- **Responsibility:** Defines the interfaces for Storage (`LoggerInterface`) and Reading (`QueryInterface`).
- **Invariants:** Implementation agnostic.

### E. Infrastructure (`Infrastructure`)
- **Role:** The Storage Adapter.
- **Responsibility:** Implements the Contracts to persist data to a physical medium (DB, File, Stream).
- **Invariants:** MUST NOT contain business logic. MUST strictly adhere to DTO structures.

---

## 4. Write-Side Canonical Flow

1.  **Input:** Caller invokes `Recorder::record()`.
2.  **Normalization (Policy):**
    - Inputs are passed to `Policy`.
    - `ActorType` is normalized (e.g., coerced to Interface or valid Enum).
    - `Severity` is normalized.
3.  **Sanitization (Recorder):**
    - Strings are strictly truncated to safe limits.
    - Invalid UTF-8 sequences are scrubbed.
    - Duration is clamped to `>= 0`.
4.  **DTO Construction:**
    - A `DiagnosticsTelemetryEventDTO` is constructed containing a `DiagnosticsTelemetryContextDTO`.
5.  **Persistence (Infrastructure):**
    - The DTO is passed to `DiagnosticsTelemetryLoggerInterface::write()`.
6.  **Failure Handling (Recorder):**
    - Any exception thrown by the Infrastructure is caught, logged to a fallback logger (if present), and suppressed.

---

## 5. Read-Side Canonical Model (Core)

The module provides **Primitive Access** only.

**Capabilities:**
- **Cursor-Based Iteration:** `read(cursor: ?DTO, limit: int)`
- **Guarantees:**
    - Results are ordered by `occurred_at` (and ID for stability).
    - Stateless (no session/connection state).
    - Forward-only iteration suitable for archiving.
- **Intentionally Omitted:**
    - No Filtering (Search, Date Ranges).
    - No Offset/Limit Pagination (Page numbers).
    - No Sorting options.

**Purpose:** This reader is designed for **Archivers**, **Exporters**, and **Stream Processors**, NOT for User Interfaces.

---

## 6. Optional UI Reader Pattern (Design-Only, Non-Canonical)

> **WARNING:** This pattern describes a CONSUMER of the library, not the library itself.
> This logic MUST exist in the **Host Application**, outside the `app/Modules/DiagnosticsTelemetry` directory.

**Purpose:** To present diagnostic data to human administrators via a Dashboard.

**Constraints:**
- **External:** Must be implemented in the Host Application (e.g., `App\Domain\Diagnostics\Read`).
- **Read-Only:** Must treat the underlying storage as Immutable/Append-Only.
- **Non-Blocking:** Complex queries must not impact the write performance of the primary telemetry stream.

---

## 7. UI Reader Pipeline (Conceptual Design)

To display telemetry in a UI, the Host Application should implement a **Pipeline**:

1.  **Query Input (Host):**
    - Receives HTTP params (page, filter, search).
    - Maps to a Host-defined `ListQueryDTO`.
2.  **Normalization (Host):**
    - Validates filters against allowed columns.
3.  **Storage Access (Host Infra):**
    - A dedicated Host-level Reader (e.g., `DiagnosticsListReader`) queries the storage directly (bypassing the Module's Cursor Reader).
    - *Rationale:* The Module's Cursor Reader is too primitive for complex UI filtering.
4.  **Hydration (Host):**
    - Raw storage rows are mapped to **UI DTOs** (optimized for display, formatting dates, etc).
5.  **Output (Host):**
    - Returns a `ListResponseDTO`.

**Boundary:** The Module owns the *Schema* and the *Write Path*. The Host Application owns the *Complex Read/Query Path*.

---

## 8. DTO Strategy

**Core DTOs (Inside Module):**
- **Immutable:** `readonly class` properties.
- **Strict:** Typed properties (no `mixed` unless absolutely necessary for metadata).
- **Purpose:** Data integrity during transport between layers.

**UI DTOs (Outside Module - Recommended):**
- The Host Application should define its own DTOs for UI presentation.
- **Why?** The Module DTOs are "close to the metal" (UUIDs, raw timestamps). UI DTOs need "human" formatting (relative time, status colors).

**Arrays Forbidden:**
- Arrays are forbidden for structured data. Use DTOs to enforce contracts.
- Arrays are permitted ONLY for the unstructured `metadata` payload.

---

## 9. Failure Semantics

**Fail-Open Mandate:**
- The `record()` method **MUST NEVER** throw an exception that bubbles up to the caller.
- Infrastructure failures (connection drops, timeout) **MUST** be caught.
- Serialization failures (JSON encoding) **MUST** be caught.

**Safe Fallback:**
- If a primary write fails, the Recorder **SHOULD** attempt to log the error to a distinct `fallbackLogger` (e.g., standard system error log) if one is provided.

**Guaranteed Returns:**
- `record()` returns `void`. It implies "accepted for processing", not "guaranteed durable".

---

## 10. Library-Readiness Checklist

- [x] **No Framework Coupling:** Depends only on PSRs (`Psr\Log`) and standard PHP extensions (`json`, `mbstring`).
- [x] **No Domain Leaks:** Does not reference `App\Domain` or User entities.
- [x] **Explicit Configuration:** All behavior (Policies, Writers) is injected via Interfaces.
- [x] **Standardized Types:** Uses Enums/Interfaces for categorical data (Severity, ActorType).
- [x] **Database Agnostic:** Storage logic is isolated behind `DiagnosticsTelemetryLoggerInterface`.

---

## 11. Explicit Anti-Patterns

1.  **The "Split Brain" Recorder:**
    - **NEVER** place the Recorder class in the Host Domain. It MUST live in the Module.
2.  **Magic Array Inputs:**
    - **NEVER** accept an associative array as the primary input to `record()`. Use named arguments.
3.  **Throwing on Write:**
    - **NEVER** throw an exception if the database is down. Diagnostics are secondary to system availability.
4.  **Mixing Audit & Diagnostics:**
    - **NEVER** use this module for legally required Audit Logs. It is "Best Effort", not "Guaranteed Delivery".
5.  **Hardcoded Implementations:**
    - **NEVER** use `new MySqlRepository()` inside the Recorder. Always inject the Interface.
