# LOGGING MODULE BLUEPRINT

**Status:** Canonical / Authoritative
**Scope:** Universal Logging Standard
**Audience:** Architects & Library Developers

---

## 1. Purpose & Responsibility Template

Every logging module MUST define a strict, single-purpose scope. It is a **Library**, not a **Service**.

### Defining Scope
A logging module exists to **capture and persist** a specific category of events. It MUST NOT cross into business logic, authorization, or user management.

### Mandatory Rules
- **MUST** be "Fail-Open" (never block the application).
- **MUST** be "Side-Effect Free" (persistence only).
- **MUST** be "Framework Agnostic" (no reliance on HTTP stacks or DI containers).
- **MUST NOT** contain business rules (e.g., "If user is admin, do X").
- **MUST NOT** enforce security (e.g., "Check permissions before logging").

### Prevention of Scope Creep
- If an event triggers a side effect (e.g., sending an email), that logic belongs in the **Application**, not the Logging Module.
- The module is a **Passive Recorder**.

---

## 2. Canonical Module Boundary

The module is a strict **Black Box**.

### Inside the Module (The Library)
- **Recorder:** The entry point for writing.
- **Policy:** The logic for validation and normalization.
- **DTOs:** The strict data structure contracts.
- **Contracts:** The interfaces for storage.
- **Infrastructure:** The default storage drivers (e.g., MySQL).

### Outside the Module (The Host Application)
- **Configuration:** Injecting dependencies (PDO, Logger, Clock).
- **UI & Presentation:** Any dashboards or admin panels.
- **Complex Querying:** Filtering, searching, and analytics.

### Forbidden Access Patterns
- **Direct Repository Access:** Consumers MUST NOT instantiate Infrastructure classes directly.
- **Bypassing the Recorder:** Writers MUST NOT bypass the Recorder to write to storage.
- **Mutable State:** DTOs MUST be immutable.

---

## 3. Mandatory Directory Structure

A logging module MUST follow this structure to ensure predictability and separation of concerns.

```text
ModuleName/
├── Contract/          # Interfaces (Writer, Reader, Policy)
├── DTO/               # Immutable Data Transfer Objects
├── Database/          # Canonical SQL Schema
├── Enum/              # Allowed values (Severity, ActorType)
├── Exception/         # Domain-specific Exceptions
├── Infrastructure/    # Storage Drivers (MySQL, etc.)
├── Recorder/          # The Public Entry Point & Logic
│   ├── {Name}Recorder.php
│   └── {Name}DefaultPolicy.php
├── README.md          # Usage Documentation
├── PUBLIC_API.md      # Strict Surface Definition
└── TESTING_STRATEGY.md # Testing Rules
```

### Rationale
- **Recorder/**: Isolates the "Application-Facing" logic (validation, safety) from the "Storage-Facing" logic.
- **Infrastructure/**: Keeps external dependencies (PDO, Redis) isolated from the Domain logic.
- **DTO/**: Enforces structural contracts across boundaries.

---

## 4. Write-Side Blueprint (Recorder Pattern)

The **Recorder** is the heart of the module. It is the **only** permitted entry point for writing logs.

### Responsibilities
1.  **Accept Primitive/Enum Inputs:** Do not force the caller to build DTOs.
2.  **Validate & Normalize:** Delegate to the **Policy**.
3.  **Construct DTOs:** Convert valid inputs into immutable DTOs.
4.  **Persist:** Pass DTOs to the **Logger Interface**.
5.  **Guarantee Safety:** Catch and suppress **ALL** storage exceptions.

### Fail-Open Guarantee
The `record()` method MUST return `void` and MUST NOT throw exceptions to the caller. Failures are swallowed (logged to a fallback logger) to protect the host application.

### Validation vs Sanitization
- **Validation:** Reject impossible states (e.g., "Event Key is null").
- **Sanitization:** Fix recoverable states (e.g., "Truncate strings to DB limit", "Coerce negative duration to 0").

### Requirements
- **Clock Abstraction:** MUST inject a `ClockInterface` (never call `new DateTime()` directly).
- **UUID Strategy:** MUST generate IDs (UUIDv4 or ULID) within the Recorder.

### Method Signature Template (Pseudocode)

```php
class ModuleRecorder {
    public function record(
        string $event,
        Enum|string $severity,
        Enum|string $actorType,
        ?int $actorId,
        ?array $metadata
    ): void {
        try {
            // 1. Policy Normalization
            // 2. DTO Construction
            // 3. Logger->write(DTO)
        } catch (StorageException $e) {
            // 4. Suppress & Fallback Log
        }
    }
}
```

---

## 5. Policy Pattern Blueprint

The **Policy** encapsulates the rules for "What is allowed to be logged".

### Purpose
To keep the Recorder clean and allow the Host Application to customize rules without modifying the library.

### Logic Responsibilities
- **Actor Type Normalization:** e.g., "Convert 'super-admin' to 'ADMIN'".
- **Severity Normalization:** e.g., "Truncate custom levels".
- **Metadata Validation:** e.g., "Enforce 64KB JSON limit".

### Forbidden Logic
- **Database Access:** Policies MUST be pure functions.
- **Side Effects:** Policies MUST NOT modify external state.

### Extensibility
The module MUST provide a `DefaultPolicy`. The Host Application MAY implement a custom Policy and inject it.

---

## 6. DTO Strategy

DTOs are the currency of the module.

### Rules
1.  **Immutable:** Properties MUST be `readonly`.
2.  **Strictly Typed:** No `mixed` types (except within verified metadata arrays).
3.  **Canonical Alignment:** DTO properties MUST map 1:1 to the canonical database schema.
4.  **No Behavior:** DTOs are data carriers only.

### Arrays
- **Structured Data:** MUST use DTOs.
- **Unstructured Data:** `metadata` arrays are allowed but MUST be validated by the Policy (size limits, depth).

### Cursor DTO
A specialized `CursorDTO` (containing `occurredAt` and `lastId`) is REQUIRED for the Read-Side to ensure stateless pagination.

---

## 7. Read-Side Blueprint (CORE)

The module MUST provide a **Primitive Reader** for archiving and system access.

### Primitive Reader Characteristics
- **Cursor-Based:** Pagination via `(occurred_at, id)`.
- **Sequential:** Ordered by time descending.
- **Stateless:** No "Page 5" logic; only "After Cursor X".

### Guarantees
- **Fail-Safe Hydration:** If the DB contains invalid data (e.g., manual edits), the Reader MUST NOT crash. It should sanitize on read.

### Why Required?
Even if the application uses a separate UI reader, the module MUST be independently verifiable and exportable.

---

## 8. Optional UI Reader Pattern (NON-CANONICAL)

**WARNING:** This pattern belongs to the **Host Application**, NOT the Module.

### Concept
The Host Application often needs rich filtering (search, page numbers) that the Module's Primitive Reader does not support.

### Implementation Guidelines
1.  **Location:** `App\Http\Controllers` or `App\Domain`.
2.  **Access:** The Host MAY query the module's storage table directly (Read-Only).
3.  **Pipeline:**
    - **Input:** UI Request.
    - **Normalize:** Validate filters.
    - **Query:** SQL `SELECT ... WHERE ...`.
    - **Hydrate:** Map rows to **Host DTOs** (not Module DTOs).
    - **Output:** JSON Response.

### Why Separation?
The Module owns the **Write Semantics** (Schema). The Host owns the **Read Experience** (UX).

---

## 9. Failure Semantics (MANDATORY)

### The Golden Rule
**Logging must never break the application.**

### Rules
- **Recorder:** MUST catch `Throwable`.
- **Infrastructure:** MAY throw `StorageException` (honest failure).
- **Policy:** MUST NOT throw (return safe defaults).
- **Reader:** MAY throw (reads are not critical to user flow).

### Handling Failures
- **Swallow:** Connection timeouts, SQL errors, Serialization errors.
- **Log:** Send exception details to a fallback PSR Logger.

---

## 10. Testing Blueprint

### Unit Tests
- **Target:** Recorder, Policy, DTOs.
- **Strategy:** Mock the Storage Interface.
- **Assert:** Correct DTO construction, correct Policy application, correct exception suppression.

### Integration Tests
- **Target:** Infrastructure (Repository).
- **Strategy:** Real Database (SQLite/MySQL).
- **Assert:** Data persists, Round-trip (Write -> Read) works, Constraints (foreign keys, types) are honored.

### Constraints
- **MUST NOT** assert specific UI behaviors (filtering, sorting).
- **MUST** assume the Reader is primitive/sequential.

---

## 11. Library-Readiness Checklist (Reusable)

Use this checklist to certify a module as "Blueprint Compliant".

- [ ] **Directory Structure**: strict separation of `Recorder`, `DTO`, `Contract`.
- [ ] **Dependency Safety**: No dependence on framework helpers (`request()`, `auth()`).
- [ ] **DTO Strictness**: All inputs/outputs are DTOs.
- [ ] **Fail-Open**: Recorder catches all exceptions.
- [ ] **Policy Isolated**: Validation logic is in a separate class.
- [ ] **Primitive Reader**: A cursor-based reader is present.
- [ ] **Documentation**: `PUBLIC_API.md` exists.

---

## 12. Anti-Patterns to Explicitly Avoid

### ❌ The Split-Brain Recorder
**Anti-Pattern:** The Module contains only Storage Drivers, and the Application (Domain) implements the Recorder.
**Fix:** The Recorder MUST live inside the Module.

### ❌ Magic Arrays
**Anti-Pattern:** Passing associative arrays (`['user_id' => 1]`) deep into the system.
**Fix:** Convert to DTOs immediately at the Recorder boundary.

### ❌ UI Coupling
**Anti-Pattern:** Adding `search($term)` to the Module's Reader interface.
**Fix:** Keep the Module Reader primitive. Build a separate UI Reader in the Host.

### ❌ Hardcoded Dependencies
**Anti-Pattern:** `new MySQLRepository()`.
**Fix:** Inject `LoggerInterface`.

### ❌ Throwing on Write
**Anti-Pattern:** Allowing DB errors to bubble up to the Controller.
**Fix:** The Recorder MUST try-catch block everything.
