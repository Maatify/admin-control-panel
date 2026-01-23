# Public API

## Interfaces

### `Maatify\SecuritySignals\Contract\SecuritySignalLoggerInterface`

The primary contract for writing security signals.

```php
public function log(SecuritySignalDTO $dto): void;
```

## DTOs

### `Maatify\SecuritySignals\DTO\SecuritySignalDTO`

Immutable data carrier for the signal.

```php
readonly class SecuritySignalDTO {
    public function __construct(
        public string $event_id,
        public SecuritySignalTypeEnum $signal_type,
        public SecuritySeverityEnum $severity,
        public SecuritySignalContextDTO $context,
        public array $metadata = []
    )
}
```

### `Maatify\SecuritySignals\DTO\SecuritySignalContextDTO`

Immutable normalized context.

```php
readonly class SecuritySignalContextDTO {
    public function __construct(
        public string $actor_type,
        public ?int $actor_id,
        public ?string $request_id,
        public ?string $correlation_id,
        public ?string $route_name,
        public ?string $ip_address,
        public ?string $user_agent,
        public DateTimeImmutable $occurred_at
    )
}
```

## Enums

*   `Maatify\SecuritySignals\Enum\SecuritySignalTypeEnum`
*   `Maatify\SecuritySignals\Enum\SecuritySeverityEnum`

## Exceptions

*   `Maatify\SecuritySignals\Exception\SecuritySignalWriteException`
