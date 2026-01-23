# AuditTrail Module

A standalone library for logging data access, views, and exports.

## Installation

This module is part of the `Maatify` logging system.
Namespace: `Maatify\AuditTrail\`

## Usage

### Recording an Event

Inject `Maatify\AuditTrail\Recorder\AuditTrailRecorder` and call `record()`.

```php
use Maatify\AuditTrail\Recorder\AuditTrailRecorder;
use Maatify\AuditTrail\Enum\AuditTrailActorTypeEnum;

class CustomerController {
    public function __construct(
        private AuditTrailRecorder $auditRecorder
    ) {}

    public function show(int $id) {
        // ... business logic ...

        $this->auditRecorder->record(
            eventKey: 'customer.view',
            actorType: AuditTrailActorTypeEnum::ADMIN,
            actorId: $currentUserId,
            entityType: 'customer',
            entityId: $id,
            metadata: ['section' => 'billing']
        );
    }
}
```

### Querying Events

Inject `Maatify\AuditTrail\Contract\AuditTrailQueryInterface`.

```php
use Maatify\AuditTrail\Contract\AuditTrailQueryInterface;
use Maatify\AuditTrail\DTO\AuditTrailQueryDTO;

$query = new AuditTrailQueryDTO(
    actorId: 123,
    limit: 10
);

$logs = $queryRepo->find($query);
```

## Configuration

Ensure `AuditTrailRecorder` is wired in your DI container with:
- `AuditTrailLoggerInterface` implementation (e.g. `AuditTrailLoggerMysqlRepository`)
- `ClockInterface` implementation
