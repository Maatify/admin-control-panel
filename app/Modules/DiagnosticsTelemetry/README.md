# Diagnostics Telemetry Module

**Project:** maatify/admin-control-panel
**Module:** DiagnosticsTelemetry
**Namespace:** `Maatify\DiagnosticsTelemetry`

## Purpose
This module provides a standalone, isolated logging mechanism for **Diagnostics Telemetry** ONLY. It is designed to be the simplest starting point for a unified logging architecture.

## Architecture

The module follows the Canonical Logger Design Standard:

1.  **Recorder** (`DiagnosticsTelemetryRecorder`): The policy layer. It accepts telemetry data (scalars or Interfaces), validates it (e.g., actor types, metadata size), enforces DB constraints (UTF-8 safe truncation), creates DTOs, and handles storage failures (best-effort).
2.  **Contract** (`DiagnosticsTelemetryLoggerInterface`): The interface for the storage driver.
3.  **DTOs**: Strict Data Transfer Objects for Context, Events, and Cursors. DTOs depend on Extensible Interfaces.
4.  **Infrastructure** (`DiagnosticsTelemetryLoggerMysqlRepository`): The MySQL implementation of the writer using PDO.
5.  **Policy** (`DiagnosticsTelemetryPolicyInterface`): Interface for normalizing inputs (Severity, ActorType) and validating rules. A default implementation (`DiagnosticsTelemetryDefaultPolicy`) is provided.

### Module Boundary / Public Surface

Consumers should strictly use the defined Public API:
- **Write:** `DiagnosticsTelemetryRecorder::record(...)`
- **Read:** `DiagnosticsTelemetryQueryInterface::read(...)`
- **Configure:** `DiagnosticsTelemetryPolicyInterface` (optional implementation)

See `PUBLIC_API.md` for full contract details.

### Data Flow

```
Caller (Controller/Service)
  |
  v
Call DiagnosticsTelemetryRecorder::record(eventKey, severity, actorType, ...)
  |
  v
DiagnosticsTelemetryRecorder
  - Enforces DB Constraints (UTF-8 safe truncation)
  - Normalizes Duration (>= 0)
  - Normalizes Actor Type (via Policy)
  - Normalizes Severity (via Policy)
  - Validates Metadata Size (64KB via Policy)
  - Generates Event ID (UUID)
  - Constructs Context and Event DTOs
  |
  v
DiagnosticsTelemetryLoggerInterface::write(DTO)
  |
  v
DiagnosticsTelemetryLoggerMysqlRepository (Infrastructure)
  - Serializes Metadata (JSON)
  - Formats Dates (UTC)
  - Executes INSERT SQL
```

### Dependency Flow

The module is designed to be isolated.
- **Inbound**: Caller depends on `Recorder`, `Enum Interfaces`.
- **Outbound**: Module depends only on:
    - `PDO` (standard PHP extension)
    - `Psr\Log\LoggerInterface` (standard PSR)
    - `Ramsey\Uuid` (explicit dependency for UUIDv4 generation)
    - `ClockInterface` (internal abstraction)



## Database Schema

The module requires the `diagnostics_telemetry` table. A canonical schema definition is provided within the module:

`app/Modules/DiagnosticsTelemetry/Database/schema.diagnostics_telemetry.sql`

This file should be used to initialize the database table.

## Usage

```php
use Maatify\DiagnosticsTelemetry\Recorder\DiagnosticsTelemetryRecorder;
use Maatify\DiagnosticsTelemetry\Enum\DiagnosticsTelemetrySeverityEnum;
use Maatify\DiagnosticsTelemetry\Enum\DiagnosticsTelemetryActorTypeEnum;

// Dependencies (usually injected)
$writer = new DiagnosticsTelemetryLoggerMysqlRepository($pdo);
$clock = new SystemClock();
$recorder = new DiagnosticsTelemetryRecorder($writer, $clock, $psrLogger);

// Record Event (Pass scalars or Enums)
$recorder->record(
    eventKey: 'http.request',
    severity: DiagnosticsTelemetrySeverityEnum::INFO, // or 'INFO'
    actorType: DiagnosticsTelemetryActorTypeEnum::USER, // or 'USER'
    actorId: 123,
    correlationId: 'abc-123',
    requestId: 'req-456',
    routeName: 'api.test',
    ipAddress: '127.0.0.1',
    userAgent: 'Mozilla/5.0...',
    durationMs: 45,
    metadata: ['url' => '/api/test']
);
```

### Failure Semantics (Best Effort)

The `DiagnosticsTelemetryRecorder` is designed to be **fail-open**.
- If the database write fails (`PDOException`), the exception is **caught and swallowed**.
- The failure is logged to the fallback `Psr\Log\LoggerInterface` (if provided).
- This ensures that a telemetry logging failure does not crash the main application request.

### Archiving Readiness

The module is designed to support future archiving via the `DiagnosticsTelemetryQueryInterface`.
- **Stable Cursors**: The `read()` method accepts a `DiagnosticsTelemetryCursorDTO` (last occurred_at + id) to allow reliable, stateless iteration over large datasets (e.g. for moving old logs to an archive).
- **Read-Side Resilience**: The reader handles unknown or invalid data (e.g. from an old version of the app or manual DB edit) gracefully by sanitizing it into valid DTOs.

### Extensibility

- **Severity**: Implement `DiagnosticsTelemetrySeverityInterface`.
- **ActorType**: Implement `DiagnosticsTelemetryActorTypeInterface`.
- **Policy**: Implement `DiagnosticsTelemetryPolicyInterface` and inject it into the Recorder/Repository to change normalization/validation logic (e.g., allowed actor types, regex patterns).

> **Reader Scope Clarification**
>
> The read-side provided by this module is a **primitive, cursor-based reader**
> intended strictly for archiving and sequential processing.
> It is **not designed** for UI-driven querying such as search, filtering,
> pagination, or analytics.


### Constraints & Guards

- **Timezone**: Dates are strictly enforced as UTC.
- **String Constraints**: The Recorder automatically truncates strings to fit database columns (e.g., `event_key` to 255, `user_agent` to 512) using UTF-8 safe truncation (if `mbstring` is available).
- **Duration**: `duration_ms` is automatically coerced to 0 if negative.
- **Metadata**: MUST be an array or null. Maximum size is 64KB (JSON encoded).
- **Secrets**: Metadata MUST NOT contain secrets (passwords, tokens, OTPs).
- **Actor Type**: Default policy enforces uppercase, max length 32, and sanitizes characters (replacing invalid chars with `_`) using pattern `[^A-Z0-9_.:-]`. It does NOT collapse invalid types to ANONYMOUS by default, but sanitizes them to valid ad-hoc types. Falls back to ANONYMOUS if sanitization results in an empty string.
