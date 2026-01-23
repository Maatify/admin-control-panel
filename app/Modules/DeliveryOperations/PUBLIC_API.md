# Public API

## Interfaces

### `Maatify\DeliveryOperations\Contract\DeliveryOperationsLoggerInterface`

Contract for the storage writer.

```php
public function log(DeliveryOperationRecordDTO $dto): void;
```

### `Maatify\DeliveryOperations\Contract\DeliveryOperationsClockInterface`

Contract for time provider.

```php
public function now(): DateTimeImmutable;
```

## Recorder

### `Maatify\DeliveryOperations\Recorder\DeliveryOperationsRecorder`

The primary entry point. Handles UUID generation, timestamping, and fail-open logic.

```php
public function __construct(
    DeliveryOperationsLoggerInterface $logger,
    DeliveryOperationsClockInterface $clock
);

public function record(
    DeliveryChannelEnum $channel,
    DeliveryOperationTypeEnum $operationType,
    DeliveryStatusEnum $status,
    int $attemptNo = 0,
    ?string $actorType = null,
    ?int $actorId = null,
    ?string $targetType = null,
    ?int $targetId = null,
    ?DateTimeImmutable $scheduledAt = null,
    ?DateTimeImmutable $completedAt = null,
    ?string $correlationId = null,
    ?string $requestId = null,
    ?string $provider = null,
    ?string $providerMessageId = null,
    ?string $errorCode = null,
    ?string $errorMessage = null,
    array $metadata = []
): void;
```

## DTOs

### `Maatify\DeliveryOperations\DTO\DeliveryOperationRecordDTO`

Immutable data carrier matching the database schema 1:1.

## Enums

*   `Maatify\DeliveryOperations\Enum\DeliveryChannelEnum`
*   `Maatify\DeliveryOperations\Enum\DeliveryOperationTypeEnum`
*   `Maatify\DeliveryOperations\Enum\DeliveryStatusEnum`

## Exceptions

*   `Maatify\DeliveryOperations\Exception\DeliveryOperationsStorageException`
