# Legacy Security Logger Adapter Feasibility

## A) Signature Tables

### 1. Legacy Interface
**Interface:** `App\Domain\Contracts\SecurityEventLoggerInterface`

| Method | Parameters | Return Type | Exceptions |
| :--- | :--- | :--- | :--- |
| `log` | `SecurityEventDTO $event` | `void` | None documented (implied `best-effort`) |

**DTO:** `App\Domain\DTO\SecurityEventDTO`

| Property | Type | Notes |
| :--- | :--- | :--- |
| `adminId` | `?int` | Nullable |
| `eventName` | `string` | Free-text legacy strings |
| `severity` | `string` | 'info', 'warning', 'critical' |
| `context` | `array` | Merged with `requestId` in constructor |
| `ipAddress` | `?string` | |
| `userAgent` | `?string` | |
| `occurredAt` | `DateTimeImmutable` | |
| *`requestId`* | *`string`* | *Constructor arg only; stored in `$context['request_id']`* |

---

### 2. Recorder Interface
**Interface:** `App\Domain\SecurityEvents\Recorder\SecurityEventRecorderInterface`

| Method | Parameters | Return Type | Exceptions |
| :--- | :--- | :--- | :--- |
| `record` | `SecurityEventRecordDTO $event` | `void` | None (swallows `SecurityEventStorageException`) |

**DTO:** `App\Domain\SecurityEvents\DTO\SecurityEventRecordDTO`

| Property | Type | Required? |
| :--- | :--- | :--- |
| `actorType` | `SecurityEventActorTypeEnum` | **Yes** |
| `actorId` | `?int` | Yes (nullable) |
| `eventType` | `SecurityEventTypeEnum` | **Yes** |
| `severity` | `SecurityEventSeverityEnum` | **Yes** |
| `requestId` | `?string` | No (Optional) |
| `routeName` | `?string` | No (Optional) |
| `ipAddress` | `?string` | No (Optional) |
| `userAgent` | `?string` | No (Optional) |
| `metadata` | `array` | No (Default `[]`) |

---

## B) Mapping Plan (Method-by-Method)

**Target:** `LegacySecurityEventLoggerAdapter::log(SecurityEventDTO $legacy)`

| Field | Source (`$legacy`) | Target (`SecurityEventRecordDTO`) | Mapping Logic / Rule |
| :--- | :--- | :--- | :--- |
| **Actor** | `$legacy->adminId` | `actorType`, `actorId` | **IF** `adminId` is set: `actorType=ADMIN`, `actorId=$adminId`<br>**ELSE**: `actorType=ANONYMOUS`, `actorId=null` |
| **Event Type** | `$legacy->eventName` | `eventType` | **Switch Case:**<br>`'admin_logout'` -> `LOGOUT`<br>`'login_failed'`, `'login_blocked'` -> `LOGIN_FAILED`<br>`'permission_denied'` -> `PERMISSION_DENIED`<br>`'session_validation_failed'` -> `SESSION_INVALID`<br>`'password_changed'` -> `PASSWORD_RESET_REQUESTED` (Approximate) or `LOGIN_SUCCEEDED` (fallback w/ metadata)<br>`'remember_me_...'` -> `LOGIN_SUCCEEDED` (Fallback w/ metadata)<br>`'recovery_action_blocked'` -> `PERMISSION_DENIED`<br>**Default:** `LOGIN_FAILED` (Safe fail-closed categorization) or specific "UNKNOWN" enum if added. |
| **Severity** | `$legacy->severity` | `severity` | `tryFrom($legacy->severity)`<br>Fallback: `SecurityEventSeverityEnum::INFO` |
| **Request ID** | `$legacy->context['request_id']` | `requestId` | Extract from context array. |
| **IP Address** | `$legacy->ipAddress` | `ipAddress` | Direct copy. |
| **User Agent** | `$legacy->userAgent` | `userAgent` | Direct copy. |
| **Route Name** | `$legacy->context['route_name']` | `routeName` | Extract if present, else `null`. |
| **Metadata** | `$legacy->context` | `metadata` | 1. Copy full `$legacy->context`<br>2. Add `legacy_event_name` => `$legacy->eventName`<br>3. Add `legacy_severity` => `$legacy->severity`<br>4. (Optional) Remove `request_id` to avoid duplication. |

---

## C) Dependency Injection Impact

**File:** `app/Bootstrap/Container.php`

**Binding to Change:**
```php
// OLD
SecurityEventLoggerInterface::class => function (ContainerInterface $c) {
    $pdo = $c->get(PDO::class);
    assert($pdo instanceof PDO);
    return new SecurityEventRepository($pdo);
},

// NEW
SecurityEventLoggerInterface::class => function (ContainerInterface $c) {
    // 1. Get the new Recorder
    $recorder = $c->get(SecurityEventRecorderInterface::class);
    assert($recorder instanceof SecurityEventRecorderInterface);

    // 2. Return the Adapter
    return new \App\Infrastructure\Adapter\LegacySecurityEventLoggerAdapter($recorder);
},
```

**Risk Notes:**
1.  **Enum Gaps:** The legacy system uses specific event strings (`password_changed`, `remember_me_issued`) that have **no direct equivalent** in `SecurityEventTypeEnum`. Mapping them to "closest matches" (e.g. `LOGIN_SUCCEEDED` or `PERMISSION_DENIED`) relies heavily on `metadata` for disambiguation. This "pollution" of generic event types is the primary risk.
2.  **Information Hiding:** By mapping `recovery_action_blocked` to `PERMISSION_DENIED` (for example), the high-level dashboard might lose the distinct "Recovery Mode" signal unless it explicitly queries the metadata column.
3.  **No Conflicts:** There are no DI conflicts. The legacy interface and the module interface share the same short name but are distinct types handled correctly by the container.
