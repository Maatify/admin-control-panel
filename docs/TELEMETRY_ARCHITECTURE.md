# Telemetry Reader Architecture Report

**Status:** APPROVED FOR IMPLEMENTATION
**Target Project:** `maatify/admin-control-panel`
**Scope:** Telemetry Reader Layer (Read-Only)

---

## 1. Executive Summary

The Telemetry Reader is designed as a **Non-Authoritative, Observational** component. It strictly adheres to the Clean Architecture principles observed in the `SecurityEvents` module, enforcing a hard separation between the **Module Layer** (Standalone, Infrastructure-aware) and the **Domain/Application Layer** (Use-case specific, Infrastructure-agnostic).

This architecture ensures:
*   **Zero Impact on Flow:** Failures in telemetry reading never block application logic.
*   **Strict Isolation:** No Application or RequestContext dependencies leak into the Reader Module.
*   **Maintainability:** The Domain Layer consumes telemetry via strict contracts, preventing tight coupling to the underlying storage (MySQL/PDO).

---

## 2. SecurityEvents Reader — Observed Pattern

The `SecurityEvents` feature provides the canonical reference architecture.

### 2.1 Structure Analysis
*   **Module Layer (`app/Modules/SecurityEvents`)**:
    *   **Contract:** `SecurityEventReaderInterface` - Defines low-level access (filtering by actor, event type, severity).
    *   **DTO:** `SecurityEventReadDTO` - Maps 1:1 to the persistence schema (raw data).
    *   **Infrastructure:** `SecurityEventReaderMysqlRepository` - Implements the interface using `PDO`. Handles SQL generation and mapping. **Standalone.**
*   **Domain Layer (`app/Domain/...`)**:
    *   **Contract:** `AdminSecurityEventReaderInterface` (`app/Domain/Contracts`) - Defines the use-case for Admin viewing (e.g., `getMySecurityEvents`).
    *   **DTO:** `GetMySecurityEventsQueryDTO` - Encapsulates the query parameters (Admin ID, time range, pagination).
    *   **DTO:** `SecurityEventViewDTO` - The formatted output for the UI.
*   **Application/Infrastructure (`app/Infrastructure/Audit`)**:
    *   **Adapter:** `PdoAdminSecurityEventReader` - Implements `AdminSecurityEventReaderInterface`.
    *   *Observation:* Currently, this adapter re-implements SQL logic. While acceptable for `SecurityEvents` (critical path optimizations), the Telemetry Reader will strictly favor **composition** (Adapter using Module Reader) to avoid code duplication.

### 2.2 Why This is Correct
1.  **Separation of Concerns:** The Module handles *storage* (How to read). The Domain handles *requirements* (What to read).
2.  **No Leaky Abstractions:** The Controller talks to the Domain Interface. It knows nothing about PDO or SQL.
3.  **Portability:** The Module can be extracted to a separate package without bringing along Application dependencies.

---

## 3. Telemetry Reader — Required Architecture

The Telemetry Reader will mirror the SecurityEvents structure but enforce a stricter Adapter pattern.

### 3.1 Architectural Blueprint

1.  **Module Layer (`app/Modules/Telemetry`)**:
    *   **Role:** pure data access library.
    *   **Capabilities:** `paginate()`, `count()`, `find()`.
    *   **Dependencies:** `PDO` only. NO Application classes.

2.  **Domain Layer (`app/Domain/Telemetry`)**:
    *   **Role:** Business logic definitions.
    *   **Capabilities:** Defines the Query shape and View shape.
    *   **Dependencies:** None (Pure PHP).

3.  **Application Infrastructure (`app/Infrastructure/Telemetry`)**:
    *   **Role:** The Glue.
    *   **Action:** Implements Domain Interface -> Calls Module Interface.
    *   **Dependencies:** Module Contracts, Domain Contracts.

---

## 4. Layer-by-Layer Responsibilities

### A. Module Layer
**Location:** `app/Modules/Telemetry`

*   **Interface:** `Contracts\TelemetryReaderInterface`
    *   `public function paginate(array $filters, int $page, int $perPage): array;`
    *   `public function count(array $filters): int;`
*   **DTO:** `DTO\TelemetryReadDTO`
    *   Properties: `id`, `projectId`, `metricName`, `metricValue`, `timestamp`, `metadata` (array).
    *   **Immutable.**
*   **Repository:** `Infrastructure\Mysql\TelemetryReaderMysqlRepository`
    *   Extends/Implements: `TelemetryReaderInterface`.
    *   Logic: Builds generic SQL (WHERE clauses based on `$filters`). Maps rows to `TelemetryReadDTO`.

### B. Domain Layer
**Location:** `app/Domain/Telemetry` (and `app/Domain/Contracts`)

*   **Interface:** `app/Domain/Contracts/TelemetryReaderInterface.php`
    *   `public function getProjectTelemetry(TelemetryQueryDTO $query): array;`
*   **Query DTO:** `app/Domain/Telemetry/DTO/TelemetryQueryDTO.php`
    *   Properties: `projectId` (required), `metricName` (optional), `dateFrom`, `dateTo`, `page`, `limit`.
*   **View DTO:** `app/Domain/Telemetry/DTO/TelemetryViewDTO.php`
    *   Formatted for API response (e.g., timestamps as strings, value formatting).

### C. Application Adapter
**Location:** `app/Infrastructure/Telemetry`

*   **Class:** `TelemetryReaderAdapter`
    *   Implements: `App\Domain\Contracts\TelemetryReaderInterface`.
    *   Injects: `App\Modules\Telemetry\Contracts\TelemetryReaderInterface`.
    *   **Logic:**
        1.  Accepts `TelemetryQueryDTO`.
        2.  Converts DTO to `$filters` array (e.g., `['project_id' => $dto->projectId]`).
        3.  Calls `module->paginate($filters, ...)`.
        4.  Iterates results, mapping `TelemetryReadDTO` -> `TelemetryViewDTO`.
        5.  Returns `array<TelemetryViewDTO>`.

### D. HTTP Layer
**Location:** `app/Http/Controllers`

*   **Controller:** `TelemetryController`
    *   Logic: Extracts query params -> Creates `TelemetryQueryDTO` -> Calls Domain Interface.
    *   **Prohibited:** No direct access to Module or PDO.

---

## 5. Query Model & Constraints

### 5.1 Queryable Fields (Module Filter Support)
| Field | Type | Filter Logic | Notes |
| :--- | :--- | :--- | :--- |
| `project_id` | Int | Exact Match | **Required** for most queries (Tenant isolation) |
| `metric_name` | String | Exact Match | |
| `occurred_at` | DateTime | Range (`>=`, `<=`) | Mapped from `date_from` / `date_to` |
| `event_type` | String | Exact Match | |

### 5.2 Filter Constraints
*   **JSON Metadata:** NEVER filterable by the Reader. (Performance risk).
*   **Full Text:** NO partial matches (LIKE %) on metric names. Exact match only.
*   **Time Range:** Application MUST enforce a default range (e.g., last 24h) if none provided, to prevent table scans.

### 5.3 Pagination
*   **Strategy:** Offset/Limit (Standard SQL).
*   **Constraints:**
    *   `limit` must be clamped (e.g., min 1, max 100).
    *   `page` must be >= 1.

---

## 6. Anti-Patterns & Failure Modes

### 🚫 The "Audit Log" Trap
*   **Mistake:** Treating Telemetry as Audit Logs.
*   **Reality:** Telemetry is *approximate*. Do not implement "Chain of Custody" or "Non-Repudiation" checks here.

### 🚫 The "Fat Controller"
*   **Mistake:** Controller instantiating the Repository directly or building the DTOs manually from `request->all()`.
*   **Fix:** Use the Domain `TelemetryQueryDTO` as the strict boundary.

### 🚫 The "Leaky Context"
*   **Mistake:** Passing `RequestContext` to the Reader.
*   **Fix:** Extract *only* what is needed (e.g., `project_id`) in the Controller and pass it via the DTO.

### 🚫 Write-Side Logic
*   **Mistake:** Adding a `record()` method to the Reader interface.
*   **Fix:** Reader is Read-Only. Writer is a separate stack.

### 🚫 Caching
*   **Mistake:** Adding caching decorators to the Reader.
*   **Fix:** Forbidden by current constraints. Real-time access only.

---

## 7. Final Pre-Implementation Checklist

**STOP** if any of the following are true:

- [ ] You are tempted to add `Request` or `RequestContext` to the Repository constructor.
- [ ] You are tempted to write SQL in the Controller.
- [ ] You are missing a DTO mapping step (exposing DB columns directly to API).
- [ ] You are merging Telemetry logic into the `SecurityEvents` table/module.
- [ ] You are not handling the case where `project_id` is missing (Security risk).
- [ ] The Module layer depends on `App\Domain`. (Dependency direction violation).

**Implementation Order:**
1.  **Module:** Interface -> DTO -> Repository (MySQL).
2.  **Domain:** QueryDTO -> ViewDTO -> Interface.
3.  **App Infra:** Adapter implementation.
4.  **HTTP:** Controller wiring.
