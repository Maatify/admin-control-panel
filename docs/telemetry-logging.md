# Telemetry Logging

## 1) Purpose & Scope

Telemetry is a **best-effort, non-authoritative tracing signal** system.
It exists to support:

- request correlation
- operational debugging
- performance visibility (slow operations)
- diagnostics at scale

Telemetry is explicitly **NOT**:

- an audit log
- a security event log
- a source of truth
- a transactional guarantee

## 2) Non-Goals (Explicit)

Telemetry MUST NOT:

- write to `audit_logs`
- write to `activity_logs`
- affect authorization or security decisions
- block any request, command, or authentication flow
- become required for correctness

## 3) Data Model

Telemetry is stored in `telemetry_traces` (write-side only).

Suggested columns:

- `actor_type` (string)
- `actor_id` (nullable int)
- `event_type` (string enum)
- `severity` (string enum)
- `request_id` (nullable)
- `route_name` (nullable)
- `ip_address` (nullable)
- `user_agent` (nullable)
- `metadata` (nullable JSON)
- `occurred_at` (datetime with microseconds)

## 4) Layering Model (Canonical)

Telemetry follows a strict layered model:

- **Module (`app/Modules/Telemetry`)**
    - storage only (INSERT)
    - throws `TelemetryStorageException` on failure

- **Domain (`app/Domain/Telemetry`)**
    - intent-only
    - transforms Domain DTO → Module DTO
    - swallows `TelemetryStorageException` (best-effort policy)

- **Application/HTTP (`app/Application/Telemetry`)**
    - enriches with `RequestContext`
    - no persistence
    - delegates to Domain recorder

## 5) Failure Semantics (Best-Effort)

If telemetry storage fails:

- the exception is thrown in the Module
- the Domain recorder swallows it silently
- the user-facing flow continues without interruption

Telemetry failures MUST NEVER:

- surface to UI/API responses
- throw to controllers
- break auth/session/step-up flows

## 6) Retention Policy (Policy Only)

Telemetry is high-volume and non-authoritative.
Retention SHOULD be configured operationally (policy only), e.g.:

- 7 days (default)
- 14 days (standard)
- 30 days (extended)

Retention enforcement is out of scope for this phase.

## 7) Future Work (Out of Scope)

NOT implemented in this phase:

- reader/query APIs
- admin UI pages
- dashboards/aggregations
- migration from any legacy telemetry
- container wiring + runtime enable/disable flags

## 8) Reference Implementation

Telemetry must match the architectural pattern used by `SecurityEvents`:

- explicit enums
- DB-aligned module DTOs
- domain-level silence policy
- HTTP enrichment via RequestContext only
