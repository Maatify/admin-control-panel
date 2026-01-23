# Delivery Operations Module

**Domain:** Delivery Operations
**Namespace:** `Maatify\DeliveryOperations`
**Type:** Pure Library

## Purpose
This module implements the **Delivery Operations** logging domain. It captures the lifecycle of asynchronous operations, such as notifications, jobs, queues, and webhooks.

## Core Characteristics
*   **Domain:** Operational Observability (Delivery/Async).
*   **Nature:** Non-authoritative (Observational).
*   **Failure Semantics:** Best-effort (Fail-open).
*   **Storage:** `delivery_operations` table.

## Usage
This module provides a `Recorder` to write events safely.

```php
// 1. Setup (Host Application)
$logger = new PdoDeliveryOperationsWriter($pdo);
$clock = new SystemClock();
$recorder = new DeliveryOperationsRecorder($logger, $clock);

// 2. Record
$recorder->record(
    channel: DeliveryChannelEnum::EMAIL,
    operationType: DeliveryOperationTypeEnum::NOTIFICATION_SEND,
    status: DeliveryStatusEnum::SENT,
    attemptNo: 1,
    // ...
);
```

See `PUBLIC_API.md` for full details.
