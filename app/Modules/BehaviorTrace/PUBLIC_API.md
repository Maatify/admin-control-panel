# BehaviorTrace: Public API

**Scope:** This document defines the **only** allowed entry points into the `BehaviorTrace` module.

---

## 1. Writing (Recorder)

**Class:** `Maatify\BehaviorTrace\Recorder\BehaviorTraceRecorder`

**Method:** `record(...)`

```php
public function record(
    string $action,
    string $resource,
    ?string $resourceId,
    ?array $payload,
    string|BehaviorTraceActorTypeEnum $actorType,
    ?int $actorId = null,
    ?string $correlationId = null,
    ?string $requestId = null,
    ?string $routeName = null,
    ?string $ipAddress = null,
    ?string $userAgent = null
): void
```

**Guarantees:**
- **Fail-Open:** Swallows exceptions.
- **Validation:** Enforces limits (strings, payload size).
- **Normalization:** Converts Actor Type to Enum.

---

## 2. Reading (Primitive Query)

**Interface:** `Maatify\BehaviorTrace\Contract\BehaviorTraceQueryInterface`

**Method:** `read(...)`

```php
public function read(
    ?BehaviorTraceQueryDTO $cursor,
    int $limit = 100
): iterable // Yields BehaviorTraceViewDTO
```

**Guarantees:**
- **Cursor-Based:** Stable pagination using `occurred_at` and `id`.
- **Stateless:** No offset/page number logic.

---

## 3. Data Transfer Objects (DTOs)

All DTOs are immutable and located in `Maatify\BehaviorTrace\DTO`.

- `BehaviorTraceRecordDTO` (Internal Write Contract)
- `BehaviorTraceViewDTO` (Read Output)
- `BehaviorTraceQueryDTO` (Read Input / Cursor)
- `BehaviorTraceContextDTO` (Shared Context)

---

## 4. Enums

- `Maatify\BehaviorTrace\Enum\BehaviorTraceActorTypeEnum`

Allowed values:
- `SYSTEM`
- `ADMIN`
- `USER`
- `SERVICE`
- `API_CLIENT`
- `ANONYMOUS`
