# Architectural Audit Report

**Auditor:** Jules (AI Architecture Auditor)
**Date:** 2026-01-22
**Scope:** `app/Modules/{DiagnosticsTelemetry, ActivityLog, SecurityEvents, Telemetry}`

---

## 1. Individual Module Reviews

### A. DiagnosticsTelemetry

* **Responsibility:** Strictly defined (System Health, Debugging). Explicitly excludes business logic.
* **Boundary Discipline:** **Exemplary**. The module is a self-contained "Black Box" that includes its own Recorder, Policy, and Contracts. It does not rely on an external "Domain" layer to function correctly.
* **Write-side Design:** **Strong**. `Recorder -> Policy -> DTO -> Contract`. The Recorder is part of the module, ensuring the "Fail-Open" guarantee is intrinsic to the library.
* **Read-side Design:** **Primitive**. Cursor-based `(occurred_at, id)` pagination. Designed strictly for archiving/exporting, not for UI.
* **Failure Semantics:** **Fail-Open (Guaranteed)**. The Recorder explicitly catches and suppresses exceptions.
* **Library Readiness:** **100%**. Can be extracted to a vendor package immediately without losing functionality or guarantees.
* **Documentation:** Complete (`CANONICAL_ARCHITECTURE.md`, `PUBLIC_API.md`, `TESTING_STRATEGY.md`).

### B. ActivityLog

* **Responsibility:** User Actions & UX History.
* **Boundary Discipline:** **Split/Leaky**. The `app/Modules/ActivityLog` directory acts primarily as a "Storage Driver" (Contracts + Infra). The crucial "Recorder" logic lives in `app/Domain/ActivityLog`, separating the *tool* from the *usage*.
* **Write-side Design:** **Split**. The Module defines the *interface*, but the Application (Domain) defines the *behavior* (fail-open try/catch).
* **Read-side Design:** **Expressive**. Supports rich filtering, searching, and pagination via a canonical List Pipeline. Optimized for UI.
* **Failure Semantics:** **Fail-Open (Consumer-Enforced)**. The module's driver is "honest" (throws exceptions), relying on the Domain Recorder to suppress them.
* **Library Readiness:** **Partial**. Extracting `app/Modules/ActivityLog` yields only a storage driver, not a complete logging solution.

### C. SecurityEvents

* **Responsibility:** Security & Audit.
* **Boundary Discipline:** **Split/Leaky**. Similar to ActivityLog, logic is split between Module (Storage) and Domain (Recorder).
* **Write-side Design:** **Weak**. The Module lacks a root Recorder. The Domain Recorder (`app/Domain/SecurityEvents`) handles the DTO conversion and policy.
* **Read-side Design:** **Weak**. Uses `array $filters` in `SecurityEventReaderInterface`, which provides no type safety or contract clarity compared to DTOs.
* **Failure Semantics:** **Ambiguous**. Contract implies "Honest/Fail-Closed", but Domain Recorder enforces "Best Effort/Fail-Open".
* **Library Readiness:** **Low**. Missing root documentation and strict entry points.

### D. Telemetry

* **Responsibility:** Traces (High overlap with Diagnostics).
* **Boundary Discipline:** **Split/Leaky**. Logic split between Module and Domain.
* **Write-side Design:** **Strict**. `TelemetryLoggerInterface` explicitly forbids swallowing failures, contrasting with the other modules.
* **Read-side Design:** **Moderate**. Uses `TelemetryTraceListQueryDTO` for filtering, which is better than arrays but less standardized than ActivityLog's pipeline.
* **Failure Semantics:** **Fail-Closed**.
* **Library Readiness:** **Low**. Lacks clear identity vs. DiagnosticsTelemetry.

---

## 2. Comparative Analysis

| Feature | DiagnosticsTelemetry | ActivityLog | SecurityEvents | Telemetry |
| :--- | :--- | :--- | :--- | :--- |
| **Architecture** | **Rich Module** (Self-Contained) | **Split** (Module + Domain) | **Split** (Module + Domain) | **Split** (Module + Domain) |
| **Recorder Location** | Inside Module | Inside Domain | Inside Domain | Inside Domain |
| **Write Policy** | Intrinsic (Safe) | Extrinsic (Dependent) | Extrinsic (Dependent) | Extrinsic (Strict) |
| **Read Capability** | **Primitive** (Cursor) | **Expressive** (List/Search) | Weak (Array Filters) | Moderate (DTO Filters) |
| **Fail-Safety** | **Guaranteed** | Delegated | Delegated | **Fail-Closed** |
| **Documentation** | **Excellent** | Good | Poor | Poor |
| **Extractability** | **High** | Medium | Low | Low |

### Patterns & Observations

*   **Strongest Design Pattern:** **The Rich Module (DiagnosticsTelemetry)**. By including the Recorder and Policy *inside* the module directory, it ensures that "Safe Logging" is a feature of the library, not a burden on the consumer.
*   **Weakest Design Decision:** **The Split-Brain Recorder**. ActivityLog, SecurityEvents, and Telemetry all force the "Domain" to act as the Recorder. This leads to code duplication (try-catch blocks in every domain recorder) and makes the modules themselves just "dumb" storage adapters.
*   **Inconsistency:** Read-side interfaces are wildly different.
    *   `Diagnostics`: Cursor (for machines).
    *   `ActivityLog`: Pipeline (for humans/UI).
    *   `Security`: Arrays (Weak).
    *   `Telemetry`: DTOs (Decent).

---

## 3. Identified Gaps

1.  **Architecture Gap: The "Recorder" Void.**
    Three out of four modules (`ActivityLog`, `Security`, `Telemetry`) are missing a top-level `Recorder` class within their `app/Modules` directory. They rely on `app/Domain` to provide the safety layer. This prevents them from being true "drop-in" libraries.

2.  **Read-Side Schism.**
    There is no unified contract for "Reading Logs".
    *   System logs need **Cursors** (Archiving).
    *   Activity logs need **Search/Pagination** (UI).
    *   Current modules mix and match these without a clear strategy.

3.  **Documentation Gap.**
    `SecurityEvents` and `Telemetry` completely lack `README.md`, `PUBLIC_API.md`, or usage guides, making them opaque maintenance burdens.

---

## 4. Canonical Best Practices (Extracted)

From the analysis of `DiagnosticsTelemetry` (the strongest module), we extract these best practices:

1.  **The "Safe Recorder" Rule:** A logging library MUST provide a `Recorder` entry point that handles the "Fail-Open" try-catch logic. It should not force the user to wrap every log call in a try-catch.
2.  **Strict DTOs:** All inputs and outputs must be strongly typed DTOs. Arrays (`array $filters`, `array $metadata`) are forbidden for structured data.
3.  **Public API Definition:** A `PUBLIC_API.md` file must explicitly whitelist the allowed entry points (Recorder, Reader, Contracts).
4.  **Policy Isolation:** Validation rules (Policy) should be separate from the Recorder.

From `ActivityLog`, we extract:

5.  **Expressive Read Contracts:** For UI-facing logs, use a structured `QueryDTO` (not arrays) that supports standardized pagination and filtering.

---

## 5. Explicit Recommendations

To prepare for a unified blueprint, the following actions are recommended:

### MUST Be Part of the Future Blueprint
1.  **Move Recorders into Modules:** The `Recorder` classes currently living in `app/Domain/*/Recorder` MUST be moved into `app/Modules/*/Recorder`. The "Domain" should only *call* the Recorder, not *implement* it.
2.  **Adopt the "Rich Module" Pattern:** All logging modules must follow the `DiagnosticsTelemetry` structure:
    *   `Recorder/` (Safe entry point)
    *   `Policy/` (Validation rules)
    *   `Contracts/` (Storage interfaces)
    *   `Infrastructure/` (Drivers)
3.  **Standardize Readers:** Define two distinct Reader interfaces:
    *   `CursorReaderInterface`: For archiving/exporting (like Diagnostics).
    *   `ListReaderInterface`: For UI/filtering (like ActivityLog).
    *   Modules may implement one or both.

### MUST NOT Be Part of the Future Blueprint
1.  **Array-based Filters:** `function read(array $filters)` (as seen in SecurityEvents) MUST be rejected in favor of `QueryDTOs`.
2.  **Split-Brain Architecture:** The pattern of "Module = Infrastructure, Domain = Logic" MUST be abandoned for logging libraries. The Library should contain its own Logic.
