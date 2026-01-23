# Architectural Review: Logging Modules

**Date:** 2026-01-26
**Auditor:** Jules (AI Architecture Auditor)
**Context:** Comparative analysis of `DiagnosticsTelemetry`, `ActivityLog`, `SecurityEvents`, and `Telemetry`.

---

## 1. Individual Module Review

### A. DiagnosticsTelemetry (`app/Modules/DiagnosticsTelemetry`)
**Status:** 🟢 **Canonical Reference**

*   **Responsibility Clarity:** Exceptional. Strictly scoped to "Diagnostics Telemetry" (system health, debug info).
*   **Boundary Discipline:** Perfect. Self-contained in `app/Modules`. No leakage into `app/Domain`.
*   **Write-side Design:** Mature. `Recorder` orchestrates `Policy` (validation/normalization) and `Writer` (persistence).
*   **Read-side Design:** Primitive. Cursor-based (`DiagnosticsTelemetryCursorDTO`). Designed for archiving/streams, not UI.
*   **Failure Semantics:** Fail-open. `try-catch` in Recorder. Uses explicit `fallbackLogger`.
*   **Dependency Isolation:** High. Depends only on standard PSRs and internal Contracts.
*   **Extensibility:** High. `PolicyInterface`, `ActorTypeInterface`, `SeverityInterface`.
*   **Archiving Readiness:** Ready. Cursor-based reading allows reliable iteration.
*   **Testing Strategy:** Mature. `TESTING_STRATEGY.md` exists.
*   **Documentation:** Excellent. `README.md`, `PUBLIC_API.md`.
*   **Library Readiness:** **High**. Can be extracted immediately.

### B. ActivityLog (`app/Modules/ActivityLog` + `app/Domain/ActivityLog`)
**Status:** 🟡 **Split Brain**

*   **Responsibility Clarity:** Good. User/Business actions. Explicitly "Non-Authoritative".
*   **Boundary Discipline:** **Mixed**. Infra/Contracts in `Modules`, but Recorder/Reader logic in `Domain`.
*   **Write-side Design:** Simple. Domain Recorder wraps Writer. No explicit Policy layer (implicit validation).
*   **Read-side Design:** **Expressive**. Uses Canonical List Pipeline (`ListQueryDTO`, `ResolvedListFilters`). Excellent for Admin UIs.
*   **Failure Semantics:** Fail-open. Swallows exceptions in Recorder.
*   **Dependency Isolation:** **Medium**. Module depends on Domain classes (via usage in App), or rather App splits logic.
*   **Extensibility:** Medium. Interfaces for Writer/Action.
*   **Archiving Readiness:** Low/Implicit. Focus is on "Listing" (Offset/Limit), not Archiving.
*   **Testing Strategy:** Standard.
*   **Documentation:** Good. `README.md`, `HOW_TO_USE.md`.
*   **Library Readiness:** **Medium**. Requires refactoring to move Recorder into Module.

### C. SecurityEvents (`app/Modules/SecurityEvents` + `app/Domain/SecurityEvents`)
**Status:** 🟠 **Weak & Split**

*   **Responsibility Clarity:** Clear (Security Events).
*   **Boundary Discipline:** **Mixed**. Split between Module and Domain.
*   **Write-side Design:** Domain Recorder maps Domain DTO → Module DTO.
*   **Read-side Design:** Moderate. Standard `paginate()` (Page/PerPage).
*   **Failure Semantics:** Fail-open.
*   **Dependency Isolation:** Medium.
*   **Extensibility:** Medium.
*   **Archiving Readiness:** Low.
*   **Testing Strategy:** Unknown (no specific docs).
*   **Documentation:** **Poor**. Missing README.
*   **Library Readiness:** Low. Needs consolidation and documentation.

### D. Telemetry (`app/Modules/Telemetry` + `app/Domain/Telemetry`)
**Status:** 🟠 **Weak & Split**

*   **Responsibility Clarity:** Observability (Traces).
*   **Boundary Discipline:** **Mixed**. Split between Module and Domain.
*   **Write-side Design:** Domain Recorder maps Domain DTO → Module DTO.
*   **Read-side Design:** Specific. `TelemetryTraceListQueryDTO`.
*   **Failure Semantics:** Fail-open.
*   **Dependency Isolation:** Medium.
*   **Extensibility:** Medium.
*   **Archiving Readiness:** Low.
*   **Testing Strategy:** Unknown.
*   **Documentation:** **Poor**. Missing README.
*   **Library Readiness:** Low. Needs consolidation.

---

## 2. Comparative Analysis Matrix

| Feature | DiagnosticsTelemetry | ActivityLog | SecurityEvents | Telemetry |
| :--- | :--- | :--- | :--- | :--- |
| **Boundary** | 🟢 **Unified (Module)** | 🔴 Split (Domain/Module) | 🔴 Split (Domain/Module) | 🔴 Split (Domain/Module) |
| **Recorder Location** | 🟢 **Module** | 🔴 Domain | 🔴 Domain | 🔴 Domain |
| **Policy Layer** | 🟢 **Explicit Interface** | 🔴 Implicit | 🔴 Implicit | 🔴 Implicit |
| **Read Strategy** | 🟡 Cursor (Primitive) | 🟢 **Pipeline (Expressive)** | 🟡 Pagination | 🟡 Specific DTO |
| **Documentation** | 🟢 **Excellent** | 🟢 Good | 🔴 Missing | 🔴 Missing |
| **Fail-Open** | 🟢 Yes (w/ Fallback) | 🟢 Yes | 🟢 Yes | 🟢 Yes |
| **Archiving** | 🟢 **Native** | 🔴 Manual | 🔴 Manual | 🔴 Manual |

### Identified Patterns & Gaps

**Strongest Patterns:**
1.  **Fail-Open Universalism:** All modules correctly prioritize application flow over logging success.
2.  **Explicit DTOs:** All modules use DTOs, avoiding loose arrays.
3.  **Policy Isolation (Diagnostics):** Normalization logic is separated from the Recorder.

**Weakest Decisions:**
1.  **The "Split Brain" Anti-Pattern:** 3 out of 4 modules place the *recording logic* in `App\Domain` and the *contracts/infra* in `App\Modules`. This creates a dependency cycle where the Module is not truly standalone.
2.  **Inconsistent Reading:** No unified strategy for reading logs. One uses Cursors, one uses Pipelines, one uses Pagination.
3.  **Implicit Validation:** `ActivityLog` assumes inputs are valid or handles them deep in the driver, whereas `Diagnostics` validates them upfront in the Policy.

---

## 3. Canonical Best Practices (Extracted)

Based on `DiagnosticsTelemetry` (The Gold Standard):

1.  **Module Autonomy:** A Logging Module MUST contain its own Recorder, Policy, Contracts, and DTOs. It MUST NOT depend on `App\Domain`.
2.  **Recorder as Orchestrator:** The Recorder MUST be the entry point. It orchestrates `Policy::validate()` -> `DTO Construction` -> `Writer::write()`.
3.  **Fail-Open Safety:** The Recorder MUST wrap the Writer in a `try-catch` block and swallow exceptions (optionally logging to a fallback PSR logger).
4.  **Cursor-Based Archiving:** For high-volume logs, the module MUST provide a cursor-based reading interface for reliable archiving/processing.
5.  **Strict Contracts:** Inputs to the Recorder MUST be typed (Interfaces or Enums), not magic strings.

---

## 4. Recommendations for Future Blueprint

**✅ MUST Include:**
*   **Unified Module Structure:** Adopt the `DiagnosticsTelemetry` directory structure (`Recorder`, `Policy`, `Contract`, `Infrastructure`).
*   **Policy Pattern:** Extract validation logic (truncation, allowed types) into a `Policy` class.
*   **Clock Abstraction:** Inject `ClockInterface` into Recorders for testable timestamps.
*   **Dual-Read Interfaces:**
    *   `ReaderInterface` (Cursor/Stream) for Archiving.
    *   `QueryInterface` (List/Pagination) for Admin UIs (optional, implemented only if needed).

**❌ MUST NOT Include:**
*   **Domain-Side Recorders:** Do not place Recorders in `App\Domain`. The Domain should only *call* the Module's Recorder.
*   **Implicit Dependencies:** Do not rely on "Traits" that assume the existence of a specific Domain class.

**Next Steps:**
Refactor `ActivityLog`, `SecurityEvents`, and `Telemetry` to match the `DiagnosticsTelemetry` architectural standard, moving their Recorders from `Domain` to `Modules`.
