# BehaviorTrace Module

**Domain:** Operational Activity
**Namespace:** `Maatify\BehaviorTrace`

A standalone library for tracing operational mutations (create, update, delete) in the system.

## Installation

This module is designed to be extracted as a library. In this monorepo, it is autoloaded via `composer.json`.

## Usage

### 1. Setup

Inject dependencies (`BehaviorTraceLoggerInterface`, `ClockInterface`) in your ServiceProvider.

```php
// Example binding (Pseudo-code)
$container->set(BehaviorTraceLoggerInterface::class, new BehaviorTraceMysqlRepository($pdo));
$container->set(ClockInterface::class, new SystemClock()); // You must implement ClockInterface
```

### 2. Recording Events

Inject `BehaviorTraceRecorder` into your application service.

```php
use Maatify\BehaviorTrace\Recorder\BehaviorTraceRecorder;
use Maatify\BehaviorTrace\Enum\BehaviorTraceActorTypeEnum;

class UserService
{
    public function __construct(
        private BehaviorTraceRecorder $recorder
    ) {}

    public function create(array $data)
    {
        // ... business logic ...
        $user = ...;

        $this->recorder->record(
            action: 'user.create',
            resource: 'User',
            resourceId: (string)$user->id,
            payload: $data,
            actorType: BehaviorTraceActorTypeEnum::ADMIN,
            actorId: $adminId
        );
    }
}
```

### 3. Reading Events

Use `BehaviorTraceQueryInterface` for primitive access (e.g. archiving).

```php
$reader = $container->get(BehaviorTraceQueryInterface::class);

foreach ($reader->read(null, 100) as $dto) {
    // Process $dto (BehaviorTraceViewDTO)
}
```
