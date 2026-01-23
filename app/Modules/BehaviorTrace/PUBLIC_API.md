# Public API: BehaviorTrace

## Entry Point

The primary entry point is the `BehaviorTraceRecorder`.

```php
use Maatify\BehaviorTrace\Recorder\BehaviorTraceRecorder;
use Maatify\BehaviorTrace\Enum\BehaviorTraceActorTypeEnum;

public function __construct(
    private readonly BehaviorTraceRecorder $recorder
) {}

public function doSomething(): void
{
    // ... logic ...

    $this->recorder->record(
        action: 'customer.update',
        actorType: BehaviorTraceActorTypeEnum::ADMIN,
        actorId: 123,
        correlationId: 'uuid-123',
        requestId: 'req-456',
        routeName: 'admin.customers.update',
        ipAddress: '127.0.0.1',
        userAgent: 'Mozilla/5.0...',
        metadata: ['field' => 'email', 'old' => 'a@b.com', 'new' => 'b@c.com']
    );
}
```

## Contracts

### `BehaviorTraceRecorder::record`

```php
public function record(
    string $action,
    BehaviorTraceActorTypeInterface|string $actorType,
    ?int $actorId = null,
    ?string $correlationId = null,
    ?string $requestId = null,
    ?string $routeName = null,
    ?string $ipAddress = null,
    ?string $userAgent = null,
    ?array $metadata = null
): void
```

*   **Returns:** `void` (Swallows exceptions, logs to fallback logger if configured).
*   **Safety:** Truncates long strings, validates metadata size.

### `BehaviorTraceQueryInterface::read`

```php
public function read(
    ?BehaviorTraceCursorDTO $cursor,
    int $limit = 100
): iterable
```

*   **Returns:** `iterable<BehaviorTraceEventDTO>`
*   **Usage:** Sequential reading for archiving or inspection.

## Data Structures

*   `BehaviorTraceEventDTO`: Represents a stored event.
*   `BehaviorTraceContextDTO`: Standard context (actor, time, request).
*   `BehaviorTraceCursorDTO`: Pagination state.

## Enums

*   `BehaviorTraceActorTypeEnum`: `SYSTEM`, `ADMIN`, `USER`, `SERVICE`, `API_CLIENT`, `ANONYMOUS`.
