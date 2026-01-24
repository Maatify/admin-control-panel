# DeliveryOperations Module

**Project:** maatify/admin-control-panel
**Module:** DeliveryOperations
**Namespace:** `Maatify\DeliveryOperations`

## Purpose
This module provides a standalone, isolated logging mechanism for **Delivery Operations** (Jobs, Queues, Notifications, Webhooks). It tracks the lifecycle of asynchronous operations (e.g., queued, sent, delivered, failed).

**Key Characteristics:**
- **Best-Effort:** Logging failures are swallowed (fail-open) to prevent disrupting the core operation.
- **Fail-Open:** If the database write fails, the error is logged to a fallback logger but not thrown.

## Architecture

The module follows the Canonical Logger Design Standard:

1.  **Recorder** (`DeliveryOperationsRecorder`): The policy layer. It accepts operation data, validates it, enforces DB constraints, creates DTOs, and handles storage failures.
2.  **Contract** (`DeliveryOperationsLoggerInterface`): The interface for the storage driver.
3.  **DTOs**: Strict Data Transfer Objects for Write.
4.  **Infrastructure** (`DeliveryOperationsLoggerMysqlRepository`): The MySQL implementation of the writer using PDO.
5.  **Policy** (`DeliveryOperationsPolicyInterface`): Interface for normalizing inputs. A default implementation (`DeliveryOperationsDefaultPolicy`) is provided.

### Module Boundary / Public Surface

Consumers should strictly use the defined Public API:
- **Write:** `DeliveryOperationsRecorder::record(...)`
- **Configure:** `DeliveryOperationsPolicyInterface`

### Data Flow

```
Caller (Job/Service)
  |
  v
Call DeliveryOperationsRecorder::record(...)
  |
  v
DeliveryOperationsRecorder
  - Enforces DB Constraints
  - Normalizes Enums (Channel, Status, Type)
  - Validates Metadata Size
  - Constructs DTO
  |
  v
DeliveryOperationsLoggerInterface::log(DTO)
  |
  v
DeliveryOperationsLoggerMysqlRepository (Infrastructure)
  - Serializes Metadata (JSON)
  - Formats Dates (UTC)
  - Executes INSERT SQL (delivery_operations)
```

## Database Schema

The module requires the `delivery_operations` table. A canonical schema definition is provided within the module:

`app/Modules/DeliveryOperations/Database/schema.delivery_operations.sql`

This file should be used to initialize the database table.

## Usage

```php
use Maatify\DeliveryOperations\Recorder\DeliveryOperationsRecorder;
use Maatify\DeliveryOperations\Enum\DeliveryChannelEnum;
use Maatify\DeliveryOperations\Enum\DeliveryStatusEnum;
use Maatify\DeliveryOperations\Enum\DeliveryOperationTypeEnum;
use Maatify\DeliveryOperations\Infrastructure\Mysql\DeliveryOperationsLoggerMysqlRepository;
use Maatify\DeliveryOperations\Services\SystemClock;

// Dependencies
$writer = new DeliveryOperationsLoggerMysqlRepository($pdo);
$clock = new SystemClock();
$recorder = new DeliveryOperationsRecorder($writer, $clock, $psrLogger);

// Record Event
$recorder->record(
    channel: DeliveryChannelEnum::EMAIL,
    operationType: DeliveryOperationTypeEnum::NOTIFICATION,
    status: DeliveryStatusEnum::SENT,
    attemptNo: 1,
    actorType: 'SYSTEM',
    targetType: 'user',
    targetId: 456,
    provider: 'sendgrid',
    providerMessageId: 'msg_12345',
    metadata: ['template' => 'welcome_email']
);
```

### Constraints & Guards

- **Timezone**: Dates are strictly enforced as UTC.
- **Fail-Open**: Exceptions during logging are SWALLOWED (after fallback logging).
- **Metadata**: MUST be an array or null. Maximum size is 64KB (JSON encoded).
- **String Constraints**: Strings are truncated to safe limits.
