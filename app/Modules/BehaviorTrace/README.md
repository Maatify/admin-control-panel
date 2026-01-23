# BehaviorTrace Module (Operational Activity)

**Project:** maatify/admin-control-panel
**Module:** BehaviorTrace
**Namespace:** `Maatify\BehaviorTrace`

## Purpose
This module provides a standalone, isolated logging mechanism for **Operational Activity** (Mutations Only). It tracks "Who did what to what" (e.g., Create, Update, Delete actions). It is NOT for read/view logs (use Audit Trail) or technical logs (use Diagnostics Telemetry).

## Architecture

The module follows the Canonical Logger Design Standard:

1.  **Recorder** (`BehaviorTraceRecorder`): The policy layer. It accepts activity data, validates it, enforces DB constraints (UTF-8 safe truncation), creates DTOs, and handles storage failures (fail-open).
2.  **Contract** (`BehaviorTraceWriterInterface`): The interface for the storage driver.
3.  **DTOs**: Strict Data Transfer Objects for Context, Events, and Cursors.
4.  **Infrastructure** (`BehaviorTraceWriterMysqlRepository`): The MySQL implementation of the writer using PDO.
5.  **Policy** (`BehaviorTracePolicyInterface`): Interface for normalizing inputs (ActorType) and validating rules. A default implementation (`BehaviorTraceDefaultPolicy`) is provided.

### Module Boundary / Public Surface

Consumers should strictly use the defined Public API:
- **Write:** `BehaviorTraceRecorder::record(...)`
- **Read:** `BehaviorTraceQueryInterface::read(...)`
- **Configure:** `BehaviorTracePolicyInterface` (optional implementation)

See `PUBLIC_API.md` for full contract details.

### Data Flow

```
Caller (Controller/Service)
  |
  v
Call BehaviorTraceRecorder::record(action, actorType, entityType, ...)
  |
  v
BehaviorTraceRecorder
  - Enforces DB Constraints (UTF-8 safe truncation)
  - Normalizes Actor Type (via Policy)
  - Validates Metadata Size (64KB via Policy)
  - Generates Event ID (UUID)
  - Constructs Context and Event DTOs
  |
  v
BehaviorTraceWriterInterface::write(DTO)
  |
  v
BehaviorTraceWriterMysqlRepository (Infrastructure)
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

The module requires the `operational_activity` table. A canonical schema definition is provided within the module:

`app/Modules/BehaviorTrace/Database/schema.behavior_trace.sql`

This file should be used to initialize the database table.

## Usage

```php
use Maatify\BehaviorTrace\Recorder\BehaviorTraceRecorder;
use Maatify\BehaviorTrace\Enum\BehaviorTraceActorTypeEnum;
use Maatify\BehaviorTrace\Infrastructure\Mysql\BehaviorTraceWriterMysqlRepository;
use Maatify\BehaviorTrace\Services\SystemClock;

// Dependencies (usually injected)
$writer = new BehaviorTraceWriterMysqlRepository($pdo);
$clock = new SystemClock();
$recorder = new BehaviorTraceRecorder($writer, $clock, $psrLogger);

// Record Event
$recorder->record(
    action: 'user.create',
    actorType: BehaviorTraceActorTypeEnum::ADMIN, // or 'ADMIN'
    actorId: 123,
    entityType: 'user',
    entityId: 456,
    correlationId: 'abc-123',
    requestId: 'req-456',
    routeName: 'admin.user.store',
    ipAddress: '127.0.0.1',
    userAgent: 'Mozilla/5.0...',
    metadata: ['role' => 'editor']
);
```

### Failure Semantics (Best Effort)

The `BehaviorTraceRecorder` is designed to be **fail-open**.
- If the database write fails (`PDOException`), the exception is **caught and swallowed**.
- The failure is logged to the fallback `Psr\Log\LoggerInterface` (if provided).
- This ensures that a logging failure does not crash the main application request.

### Archiving Readiness

The module is designed to support future archiving via the `BehaviorTraceQueryInterface`.
- **Stable Cursors**: The `read()` method accepts a `BehaviorTraceCursorDTO` (last occurred_at + id) to allow reliable, stateless iteration.
- **Read-Side Resilience**: The reader handles unknown or invalid data gracefully.

### Extensibility

- **ActorType**: Implement `BehaviorTraceActorTypeInterface`.
- **Policy**: Implement `BehaviorTracePolicyInterface` and inject it into the Recorder/Repository to change normalization/validation logic.

> **Reader Scope Clarification**
>
> The read-side provided by this module is a **primitive, cursor-based reader**
> intended strictly for archiving and sequential processing.
> It is **not designed** for UI-driven querying such as search, filtering,
> pagination, or analytics.

### Constraints & Guards

- **Timezone**: Dates are strictly enforced as UTC.
- **String Constraints**: The Recorder automatically truncates strings to fit database columns (e.g., `action` to 128, `user_agent` to 512).
- **Metadata**: MUST be an array or null. Maximum size is 64KB (JSON encoded).
- **Secrets**: Metadata MUST NOT contain secrets (passwords, tokens, OTPs).
- **Actor Type**: Default policy enforces uppercase, max length 32, and sanitizes characters.
