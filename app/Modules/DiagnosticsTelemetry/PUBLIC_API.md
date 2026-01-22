# Diagnostics Telemetry: Public API & Boundary

**Scope:** This document defines the **only** allowed entry points into the `DiagnosticsTelemetry` module. Consumers (the Application) must rely **only** on these contracts and classes.

---

## 1. Primary Entry Point (Write)

**Class:** `App\Modules\DiagnosticsTelemetry\Recorder\DiagnosticsTelemetryRecorder`

**Usage:**
The Application should inject this class into its services/controllers (via Dependency Injection).

**Method:** `record(...)`

```php
public function record(
    string $eventKey,
    DiagnosticsTelemetrySeverityInterface|string $severity,
    DiagnosticsTelemetryActorTypeInterface|string $actorType,
    ?int $actorId = null,
    ?string $correlationId = null,
    ?string $requestId = null,
    ?string $routeName = null,
    ?string $ipAddress = null,
    ?string $userAgent = null,
    ?int $durationMs = null,
    ?array $metadata = null
): void
```

**Guarantees:**
- **Best Effort:** This method suppresses storage exceptions (logging them to a fallback PSR logger). It effectively never throws, ensuring telemetry failures do not crash the application.
- **Validation:** Input is validated and sanitized (e.g., ActorType regex, string truncation) before storage.
- **Type Safety:** Accepts Primitives or Interfaces.

---

## 2. Query Entry Point (Read / Archive)

**Interface:** `App\Modules\DiagnosticsTelemetry\Contract\DiagnosticsTelemetryQueryInterface`

**Usage:**
The Application should inject this interface to read logs (e.g., for Admin UI or Archiving jobs).

**Method:** `read(...)`

```php
public function read(
    ?DiagnosticsTelemetryCursorDTO $cursor,
    int $limit = 100
): iterable
```

**Guarantees:**
- **Cursor Stability:** Uses `(occurred_at, id)` for stable pagination.
- **Fail-Safe Hydration:** Rows with invalid enum values in the DB are handled gracefully (sanitized/fallback) rather than throwing.

---

## 3. Extensibility Points

**Policy:**
- `App\Modules\DiagnosticsTelemetry\Contract\DiagnosticsTelemetryPolicyInterface`
- Can be implemented to override validation rules (e.g., allow different Actor Types).

**Enums:**
- `App\Modules\DiagnosticsTelemetry\Enum\DiagnosticsTelemetrySeverityInterface`
- `App\Modules\DiagnosticsTelemetry\Enum\DiagnosticsTelemetryActorTypeInterface`
- Application can implement these interfaces to support custom Severities or Actor Types.

---

## 4. Infrastructure (Internal)

**Do NOT use directly:**
- `App\Modules\DiagnosticsTelemetry\Infrastructure\**`
- These are implementation details (MySQL repositories). They should be bound to the Contracts in the application's Service Provider.

---

## 5. Data Transfer Objects (DTOs)

**Read-Only:**
- `App\Modules\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryEventDTO` (Output of Read)
- `App\Modules\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryContextDTO`
- `App\Modules\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryCursorDTO` (Input for Read)

These DTOs are strict, immutable, and part of the public contract.
