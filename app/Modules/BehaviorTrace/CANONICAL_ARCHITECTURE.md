# BehaviorTrace: Canonical Architecture

**Status:** Final / Authoritative
**Type:** Standalone Library
**Scope:** Operational Activity

---

## 1. Canonical Purpose & Responsibility

The **BehaviorTrace** module exists to capture, normalize, and persist operational activity (mutations) in a fail-safe manner.

**Responsibility:**

* Provide a standardized API for recording operational activity (actions).
* Enforce strict structural contracts (DTOs) on all logged data.
* Guarantee **Best Effort** persistence (fail-open).
* Provide a primitive cursor-based mechanism for linear data retrieval (archiving).

**MUST NOT Do:**

* **MUST NOT** handle business logic or read/view logs (Audit Trail).
* **MUST NOT** enforce security policies or authorization.
* **MUST NOT** provide complex querying capabilities (filtering, search, pagination) within the module core.
* **MUST NOT** block the host application's execution flow under any failure condition.
* **MUST NOT** assume the existence of any UI, HTTP layer, or framework.

---

## 2. Module Boundary & Public Surface

The module is a strict **Black Box**.

**Public Entry Points (Allowed):**

1. **Recorder:** `BehaviorTraceRecorder::record(...)`

    * The *only* mechanism to write data.
2. **Contracts:** `BehaviorTraceWriterInterface`

    * For implementing storage drivers.
3. **Policy:** `BehaviorTracePolicyInterface`

    * For configuring validation/normalization rules.
4. **Read Contract:** `BehaviorTraceQueryInterface::read(...)`

    * Strictly for cursor-based sequential access.

**Forbidden Access Patterns:**

* **Direct Instantiation of Infrastructure:**
  Consumers **MUST NOT** instantiate or access storage repositories directly.
* **Bypassing the Recorder:**
  Writers **MUST NOT** write to storage without going through the `Recorder`.
* **Mutable State:**
  Consumers **MUST NOT** mutate DTOs after construction.
* **Semantic Coupling:**
  Consumers **MUST NOT** infer business meaning from trace fields.

---

## 3. Canonical Layered Structure

The module follows a **strict unidirectional data flow**.

### A. Recorder (`Recorder`)

* **Role:** Orchestrator / Gatekeeper.
* **Responsibility:**
  Accepts raw inputs, invokes the **Policy**, creates **DTOs**, delegates to **Contracts**.
* **Invariants:**

    * MUST NEVER throw exceptions to the caller.
    * MUST enforce UTF-8 safety.
    * MUST remain stateless.

### B. Policy (`Policy`)

* **Role:** Rule Enforcer.
* **Responsibility:**
  Normalize and validate inputs (actor types, metadata size).
* **Invariants:**

    * Pure functions only.
    * No side effects.
    * No infrastructure access.

### C. DTOs (`DTO`)

* **Role:** Data Carriers.
* **Responsibility:**
  Immutable, strictly typed objects for Context, Events, and Cursors.
* **Invariants:**

    * Immutable.
    * 1:1 alignment with canonical schema.
    * No behavior.

### D. Contracts (`Contract`)

* **Role:** Abstraction Layer.
* **Responsibility:**
  Define interfaces for writing and reading.
* **Invariants:**

    * Implementation-agnostic.
    * No assumptions about storage or transport.

### E. Infrastructure (`Infrastructure`)

* **Role:** Storage Adapter.
* **Responsibility:**
  Persist DTOs to a physical medium.
* **Invariants:**

    * MUST NOT contain business logic.
    * MUST strictly honor DTO structure.
    * MUST be replaceable.

---

## 4. Write-Side Canonical Flow

1. **Input:** Caller invokes `Recorder::record()`.
2. **Normalization (Policy):**

    * ActorType normalization.
3. **Sanitization (Recorder):**

    * UTF-8 safe truncation.
4. **DTO Construction:**

    * Build `BehaviorTraceEventDTO` with `BehaviorTraceContextDTO`.
5. **Persistence (Infrastructure):**

    * Delegate to `BehaviorTraceWriterInterface::write()`.
6. **Failure Handling (Recorder):**

    * Catch **all** exceptions.
    * Optionally log to fallback logger.
    * Suppress failure completely.

---

## 5. Read-Side Canonical Model (Core)

The module provides **Primitive Access ONLY**.

**Capabilities:**

* Cursor-based iteration: `read(cursor, limit)`
* Stable ordering by `(occurred_at, id)`
* Stateless, forward-only access

**Intentionally Omitted:**

* Filtering
* Searching
* Sorting
* Offset / page-based pagination

**Purpose:**
This reader exists **exclusively** for archiving, exporting, and stream processing.

---

## 6. Optional UI Reader Pattern

*(Design-Only, Non-Canonical)*

> **WARNING:**
> This pattern describes a **consumer** of the library, not the library itself.

**Purpose:**
Human-oriented inspection of trace data via dashboards.

**Hard Constraints:**

* MUST live outside `app/Modules/BehaviorTrace`
* MUST NOT affect module correctness
* MUST NOT be required for module operation
* MUST treat storage as immutable / append-only
* MUST NOT reuse or extend core QueryInterface

**Important Guarantee:**

> The existence or absence of a UI Reader
> **MUST NOT** affect the correctness, guarantees, or testability of the module.

---

## 7. UI Reader Pipeline (Conceptual Design)

A host application MAY implement the following **conceptual pipeline**:

1. **Query Input (Host)**

    * Map HTTP/UI inputs to host DTOs.
2. **Normalization (Host)**

    * Validate filters against allowed columns.
3. **Storage Access (Host Infra)**

    * Query storage directly (bypassing cursor reader).
    * This is safe because the module guarantees:

        * Schema stability
        * Append-only semantics
4. **Hydration (Host)**

    * Map rows to UI-specific DTOs.
5. **Output (Host)**

    * Return paginated, formatted response.

**Boundary Rule:**
The module owns **schema + write semantics**.
The host owns **query expressiveness**.

---

## 8. DTO Strategy

**Core DTOs (Inside Module):**

* Immutable
* Strictly typed
* Transport-only

**UI DTOs (Outside Module):**

* Host-defined
* Optimized for presentation

**Arrays:**

* Forbidden for structured data
* Allowed only for unstructured `metadata`

---

## 9. Failure Semantics

**Fail-Open Mandate:**

* `record()` MUST NEVER throw.
* Infrastructure, serialization, and IO failures MUST be caught and suppressed by the Recorder.

**Fallback:**

* Recorder SHOULD log failures to a fallback logger if provided.

**Return Contract:**

* `record()` returning `void` means *accepted*, not *durable*.

---

## 10. Library-Readiness Checklist

* [x] No framework coupling
* [x] No domain leaks
* [x] Interface-only configuration
* [x] Strict DTO contracts
* [x] Storage agnostic

---

## 11. Explicit Anti-Patterns

1. **Split-Brain Recorder**
   Recorder MUST NOT live outside the module.
2. **Magic Arrays**
   Structured arrays are forbidden.
3. **Throwing on Write**
   Logging must never block the system.
4. **Audit Misuse**
   This module is for operational mutations, not authoritative audit compliance.
5. **Hardcoded Infrastructure**
   Implementations MUST be injected.

---

> This module is implemented according to the canonical rules defined in:
> docs/architecture/logging/LOGGING_MODULE_BLUEPRINT.md
