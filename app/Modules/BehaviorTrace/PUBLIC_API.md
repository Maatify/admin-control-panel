# BehaviorTrace: Public API & Boundary

**Scope:** This document defines the **only** allowed entry points into the `BehaviorTrace` module. Consumers (the Application) must rely **only** on these contracts and classes.

---

## 1. Primary Entry Point (Write)

**Class:** `Maatify\BehaviorTrace\Recorder\BehaviorTraceRecorder`

**Usage:**
The Application should inject this class into its services/controllers (via Dependency Injection).

**Method:** `record(...)`

```php
public function record(
    string $action,
    BehaviorTraceActorTypeInterface|string $actorType,
    ?int $actorId = null,
    ?string $entityType = null,
    ?int $entityId = null,
    ?string $correlationId = null,
    ?string $requestId = null,
    ?string $routeName = null,
    ?string $ipAddress = null,
    ?string $userAgent = null,
    ?array $metadata = null
): void
```

**Guarantees:**
- **Best Effort:** This method suppresses storage exceptions (logging them to a fallback PSR logger). It effectively never throws, ensuring failures do not crash the application.
- **Validation:** Input is validated and sanitized (e.g., ActorType regex, string truncation) before storage.
- **Type Safety:** Accepts Primitives or Interfaces.

---

## 2. Query Entry Point (Read / Archive)

**Interface:** `Maatify\BehaviorTrace\Contract\BehaviorTraceQueryInterface`

**Usage:**
The Application should inject this interface to read logs (e.g., for Archiving jobs).

**Method:** `read(...)`

```php
public function read(
    ?BehaviorTraceCursorDTO $cursor,
    int $limit = 100
): iterable
```

**Guarantees:**
- **Cursor Stability:** Uses `(occurred_at, id)` for stable pagination.
- **Fail-Safe Hydration:** Rows with invalid enum values in the DB are handled gracefully (sanitized/fallback) rather than throwing.

> **Reader Scope & Limitations**
>
> The query interface exposed by this module represents a **primitive,
> cursor-based read-side**.
>
> It is designed for:
> - Archiving
> - Sequential processing
> - Export and migration jobs
>
> It is **not designed** to support:
> - UI pagination
> - Searching or filtering
> - Aggregations or analytics
>
> Any advanced or UI-driven querying MUST be implemented outside the
> module, using application-level services or optional utilities built
> on top of the module contracts.

---

## 3. Extensibility Points

**Policy:**
- `Maatify\BehaviorTrace\Contract\BehaviorTracePolicyInterface`
- Can be implemented to override validation rules (e.g., allow different Actor Types).

**Enums:**
- `Maatify\BehaviorTrace\Enum\BehaviorTraceActorTypeInterface`
- Application can implement this interface to support custom Actor Types.

---

## 4. Infrastructure (Internal)

**Do NOT use directly:**
- `Maatify\BehaviorTrace\Infrastructure\**`
- These are implementation details (MySQL repositories). They should be bound to the Contracts in the application's Service Provider.

---

## 5. Data Transfer Objects (DTOs)

**Read-Only:**
- `Maatify\BehaviorTrace\DTO\BehaviorTraceEventDTO` (Output of Read)
- `Maatify\BehaviorTrace\DTO\BehaviorTraceContextDTO`
- `Maatify\BehaviorTrace\DTO\BehaviorTraceCursorDTO` (Input for Read)

These DTOs are strict, immutable, and part of the public contract.
