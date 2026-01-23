# BehaviorTrace: Canonical Architecture

**Domain:** Operational Activity
**Intent:** Non-authoritative tracing of operational mutations and day-to-day actions.
**Status:** Library / Module

---

## 1. Domain Intent & Boundaries

The **Operational Activity** domain is strictly for recording **mutations** (changes in state) that occur during day-to-day operations.

### ✅ MUST Log
- Create / Update / Delete actions
- Approve / Reject / Assign actions
- Configuration changes (non-security)
- Content edits

### ❌ MUST NOT Log
- **Reads/Views:** Use `Audit Trail` instead.
- **Security Events:** Use `Security Signals` (e.g. login failed, permission denied).
- **Governance/Compliance:** Use `Authoritative Audit` (e.g. role changes, secret access).
- **Technical/Performance:** Use `Diagnostics Telemetry`.
- **Job/Queue Lifecycle:** Use `Delivery Operations`.

### The One-Domain Rule
Every event belongs to exactly **one** domain. If an event seems to be both a security signal and an operational activity, log them separately or choose the primary intent.

---

## 2. Library Responsibility

`BehaviorTrace` is a **passive recorder library**.

- **Fail-Open:** It NEVER blocks the application execution. All errors (storage, encoding) are swallowed.
- **Side-Effect Free:** It ONLY writes to storage. It does not trigger emails, webhooks, or other logic.
- **Policy Driven:** Validation rules (e.g. string truncation, actor normalization) are encapsulated in a Policy.

---

## 3. Schema (MySQL)

Table: `operational_activity`

| Column | Type | Description |
|---|---|---|
| `id` | BIGINT PK AI | Sequential ID |
| `event_id` | CHAR(36) | UUIDv4 |
| `occurred_at` | DATETIME(6) | UTC Timestamp |
| `actor_type` | VARCHAR | Enum (USER, ADMIN, SYSTEM...) |
| `actor_id` | BIGINT/INT | Nullable Actor ID |
| `action` | VARCHAR(255) | What happened (e.g. `user.create`) |
| `resource` | VARCHAR(255) | Target Entity (e.g. `User`) |
| `resource_id` | VARCHAR(255) | Target ID |
| `payload` | JSON | Change details (Diff) |
| `correlation_id`| VARCHAR(36) | Trace ID |
| `request_id` | VARCHAR(64) | HTTP Request ID |
| `route_name` | VARCHAR(255) | Route |
| `ip_address` | VARCHAR(45) | IP |
| `user_agent` | VARCHAR(512) | User Agent |

Indexes:
- `(occurred_at, id)` (Cursor Pagination)
- `(correlation_id)` (Tracing)
- `(resource, resource_id)` (Entity History)

---

## 4. Usage Example (Pseudo-code)

```php
// In a Service or Controller

public function updateUser(int $userId, array $data)
{
    // 1. Perform Business Logic
    $user = $this->repo->update($userId, $data);

    // 2. Record Behavior (Fire and Forget)
    $this->behaviorTrace->record(
        action: 'user.update',
        resource: 'User',
        resourceId: (string)$userId,
        payload: ['diff' => $data], // What changed
        actorType: 'ADMIN',
        actorId: $currentAdminId
    );
}
```
