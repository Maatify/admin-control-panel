# How To Use Activity Log

This guide shows **how to use Activity Log safely and correctly**.

---

## 1️⃣ Basic Usage

### Inject the Service

```php
use App\Domain\ActivityLog\Recorder\ActivityRecorder;

final class UserService
{
    public function __construct(
        private ActivityRecorder $activityRecorder,
    ) {}
}
````

---

### Log an Activity

```php
$this->activityRecorder->log(
    action    : 'admin.user.update',
    actorType : 'admin',
    actorId   : 1,
    entityType: 'user',
    entityId  : 42,
    metadata  : ['changed' => ['email']],
);
```

---

## 2️⃣ Using Enums (Recommended)

Define canonical actions:

```php
enum CoreActivityAction: string implements ActivityActionInterface
{
    case ADMIN_USER_UPDATE = 'admin.user.update';

    public function toString(): string
    {
        return $this->value;
    }
}
```

Usage:

```php
$this->activityLog->log(
    action    : CoreActivityAction::ADMIN_USER_UPDATE,
    actorType : 'admin',
    actorId   : 1,
    entityType: 'user',
    entityId  : 42,
);
```

---

## 3️⃣ Metadata Guidelines

Metadata should be:

✔️ Contextual
✔️ Non-sensitive
✔️ Serializable

Good example:

```php
metadata: [
    'fields' => ['email', 'status'],
    'source' => 'admin_panel'
]
```

❌ Do NOT store:

* Passwords
* Tokens
* Secrets
* Full request payloads

---

## 4️⃣ Fail-Open Behavior (IMPORTANT)

Activity Log **never throws** to the caller.

Internally:

* Module failures raise explicit exceptions (Storage/Mapping)
* Domain Recorder catches and swallows these exceptions
* User flow continues

This is **intentional**.

If you need guaranteed persistence, use **Audit Logs instead**.

---

## 5️⃣ Static / Legacy Usage (Optional)

For legacy or static contexts:

```php
use App\Modules\ActivityLog\Traits\ActivityLogStaticTrait;

ActivityLogStaticTrait::setActivityLogService($recorder);

self::logActivityStatic(
    action    : 'system.bootstrap',
    actorType : 'system',
    actorId   : null,
);
```

⚠️ This requires explicit bootstrap initialization.

---

## 6️⃣ Database (MySQL Driver)

Schema example:

```sql
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_type VARCHAR(32),
    actor_id BIGINT,
    action VARCHAR(128),
    entity_type VARCHAR(64),
    entity_id BIGINT,
    metadata JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    request_id VARCHAR(64),
    occurred_at DATETIME(6)
);
```

---

## 7️⃣ When NOT to Use Activity Log

❌ Security decisions
❌ Authorization checks
❌ Compliance / audit trails
❌ Transaction control

Use **Audit Logs** for those cases.

---

## 8️⃣ Summary

Activity Log answers one question:

> **"What happened?"**

It must never answer:

> **"Should this be allowed?"**

Keep that separation strict.

---

## 🔍 Querying Activity Logs (API)

The Activity Log module provides a **read-only API endpoint** for querying recorded activity events using the **canonical LIST pipeline**.

This endpoint is intended for **administrative visibility only**.

---

### Endpoint

```
POST /api/activity-logs/query
```

---

### Authorization

* Required permission:

  ```
  activity_logs.view
  ```
* Authorization is enforced **before** query execution.
* Unauthorized requests are rejected.

---

### Request Payload

The request follows the **canonical list/query structure** used across the system.

#### Example

```json
{
  "page": 1,
  "per_page": 20,
  "search": {
    "global": "admin.login",
    "columns": {
      "actor_id": "2"
    }
  },
  "date": {
    "from": "2026-01-11",
    "to": "2026-01-11"
  }
}
```

---

### Supported Query Parameters

#### Pagination

| Field      | Type | Description                  |
|------------|------|------------------------------|
| `page`     | int  | Page number (default: 1)     |
| `per_page` | int  | Items per page (default: 20) |

---

#### Global Search

```json
"search": {
  "global": "login"
}
```

Applies an **OR-based search** across:

* `action`
* `request_id`

---

#### Column Filters

```json
"search": {
  "columns": {
    "actor_type": "admin",
    "actor_id": "2",
    "action": "login"
  }
}
```

Allowed column filters:

* `actor_type`
* `actor_id`
* `action`
* `entity_type`
* `entity_id`
* `request_id`

> ⚠️ Unknown or undeclared filters are ignored or rejected.

---

#### Date Range Filter

```json
"date": {
  "from": "2026-01-11",
  "to": "2026-01-11"
}
```

* Applied to the `occurred_at` column
* Uses inclusive day boundaries:

    * `from` → `00:00:00`
    * `to` → `23:59:59`

---

### Response Format

#### Example Response

```json
{
  "data": [
    {
      "id": 12,
      "action": "admin.login",
      "actor_type": "admin",
      "actor_id": 2,
      "entity_type": null,
      "entity_id": null,
      "metadata": null,
      "ip_address": null,
      "user_agent": null,
      "request_id": "req-123",
      "occurred_at": "2026-01-11 22:40:10"
    }
  ],
  "pagination": {
    "page": 1,
    "perPage": 20,
    "total": 120,
    "filtered": 1
  }
}
```

---

### Important Notes

* Activity Logs are **best-effort and fail-open**
* Listing is **read-only**
* No mutation, deletion, or correction is supported
* Activity Logs **do not replace**:

    * `audit_logs`
    * `security_events`

---

### Recommended Usage

✔ Admin dashboards
✔ Activity history views
✔ Operational monitoring
✔ Debugging and traceability

❌ Compliance auditing
❌ Legal or financial reporting
❌ Security enforcement source

---
