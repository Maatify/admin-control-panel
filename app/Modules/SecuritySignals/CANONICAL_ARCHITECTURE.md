# SecuritySignals Module - Canonical Architecture

## 1. Purpose
The **SecuritySignals** module records "What security-relevant signals happened?"
It strictly covers:
- Best-effort security indicators
- Alerts (login failed, permission denied, etc.)
- Non-authoritative events

It DOES NOT cover:
- State changes (Authoritative Audit / Operational Activity)
- Data access (Audit Trail)
- Performance (Diagnostics Telemetry)

## 2. Layering

### Recorder (`Maatify\SecuritySignals\Recorder\SecuritySignalsRecorder`)
- **Responsibility**: Public entry point.
- **Behavior**: Fail-open (swallows exceptions).
- **Logic**: Validates input, constructs DTO, delegates to Logger.

### DTO (`Maatify\SecuritySignals\DTO\*`)
- **Responsibility**: Immutable data transfer.
- **Rules**: Strict typing, 1:1 mapping with Database Schema.

### Contract (`Maatify\SecuritySignals\Contract\*`)
- **Responsibility**: Define interfaces for Logger and Policy.

### Infrastructure (`Maatify\SecuritySignals\Infrastructure\*`)
- **Responsibility**: Persistence (MySQL).
- **Behavior**: Fail-closed (throws StorageException).

## 3. Database Schema
- **Table**: `security_signals`
- **Key Columns**:
  - `event_id` (UUID)
  - `actor_type` / `actor_id`
  - `signal_type`
  - `severity`
  - `metadata` (JSON, max 64KB)

## 4. Failure Semantics
- **Recorder**: MUST NOT throw. Swallows errors and logs to fallback PSR-logger.
- **Infrastructure**: MUST throw `SecuritySignalsStorageException` on failure.
