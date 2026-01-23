# AuditTrail Module - Canonical Architecture

## 1. Purpose
The **AuditTrail** module records "Who accessed what sensitive thing, when?"
It strictly covers:
- Data access / Views
- Navigation
- Exports / Downloads
- Read-only exposure events

It DOES NOT cover:
- Mutations (Operational Activity)
- Security events (Security Signals)
- Performance (Diagnostics Telemetry)

## 2. Layering

### Recorder (`Maatify\AuditTrail\Recorder\AuditTrailRecorder`)
- **Responsibility**: Public entry point.
- **Behavior**: Fail-open (swallows exceptions).
- **Logic**: Validates input, constructs DTO, delegates to Logger.

### DTO (`Maatify\AuditTrail\DTO\*`)
- **Responsibility**: Immutable data transfer.
- **Rules**: Strict typing, 1:1 mapping with Database Schema.

### Contract (`Maatify\AuditTrail\Contract\*`)
- **Responsibility**: Define interfaces for Logger, Query, and Policy.

### Infrastructure (`Maatify\AuditTrail\Infrastructure\*`)
- **Responsibility**: Persistence (MySQL).
- **Behavior**: Fail-closed (throws StorageException).

## 3. Database Schema
- **Table**: `audit_trail`
- **Key Columns**:
  - `event_id` (UUID)
  - `actor_type` / `actor_id`
  - `event_key` (semantic event name)
  - `entity_type` / `entity_id` (resource accessed)
  - `metadata` (JSON, max 64KB)

## 4. Failure Semantics
- **Recorder**: MUST NOT throw. Swallows errors and logs to fallback PSR-logger.
- **Infrastructure**: MUST throw `AuditTrailStorageException` on failure.
