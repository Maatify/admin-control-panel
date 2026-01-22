# Telemetry Logging Module

**Project:** maatify/admin-control-panel
**Module:** TelemetryLogging
**Namespace:** `App\Modules\TelemetryLogging`

## Purpose
This module provides a standalone, isolated logging mechanism for **Diagnostics Telemetry** ONLY. It is designed to be the simplest starting point for a unified logging architecture.

## Architecture

The module follows the Canonical Logger Design Standard:

1.  **Recorder** (`TelemetryRecorder`): The policy layer. It accepts telemetry data, validates it (e.g., actor types, metadata size), creates DTOs, and handles storage failures (best-effort).
2.  **Contract** (`TelemetryWriterInterface`): The interface for the storage driver.
3.  **DTOs**: Strict Data Transfer Objects for Context, Events, and Cursors.
4.  **Infrastructure** (`TelemetryMySqlWriter`): The MySQL implementation of the writer using PDO.

### Data Flow

```
Caller (Controller/Service)
  |
  v
Construct TelemetryContextDTO
  |
  v
Call TelemetryRecorder::record(eventKey, severity, context, ...)
  |
  v
TelemetryRecorder
  - Validates Actor Type
  - Enforces Metadata Size (64KB)
  - Generates Event ID (UUID)
  - Creates TelemetryEventDTO
  |
  v
TelemetryWriterInterface::write(DTO)
  |
  v
TelemetryMySqlWriter (Infrastructure)
  - Serializes Metadata (JSON)
  - Formats Dates (UTC)
  - Executes INSERT SQL
```

### Dependency Flow

The module is designed to be isolated.
- **Inbound**: Caller depends on `Recorder`, `DTOs`, `Enum`.
- **Outbound**: Module depends only on:
    - `PDO` (standard PHP extension)
    - `Psr\Log\LoggerInterface` (standard PSR)
    - `Ramsey\Uuid` (standard library)
    - `ClockInterface` (internal abstraction)

## Usage

```php
use App\Modules\TelemetryLogging\Recorder\TelemetryRecorder;
use App\Modules\TelemetryLogging\DTO\TelemetryContextDTO;
use App\Modules\TelemetryLogging\Enum\TelemetryLevelEnum;

// Dependencies (usually injected)
$writer = new TelemetryMySqlWriter($pdo);
$clock = new SystemClock();
$recorder = new TelemetryRecorder($writer, $clock, $psrLogger);

// Context (usually extracted from Request)
$context = new TelemetryContextDTO(
    actorType: 'USER',
    actorId: 123,
    correlationId: 'abc-123',
    requestId: 'req-456',
    routeName: 'api.test',
    ipAddress: '127.0.0.1',
    userAgent: 'Mozilla/5.0...',
    occurredAt: $clock->now()
);

// Record Event
$recorder->record(
    eventKey: 'http.request',
    severity: TelemetryLevelEnum::INFO,
    context: $context,
    durationMs: 45,
    metadata: ['url' => '/api/test']
);
```
