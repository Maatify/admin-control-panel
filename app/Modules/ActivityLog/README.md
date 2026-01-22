# Activity Log

Lightweight, non-authoritative activity logging library designed for
**user actions, UI events, and business activities** that must **never**
affect system authority, security, or control flow.

This library is intentionally **fail-open** and **side-effect only**.

---

## 🎯 Purpose

Activity Log is used to record **what happened** in the system for:

- UX history
- Admin visibility
- Debugging
- Operational insights

It is **NOT** used for:

- Security decisions
- Authorization
- Auditing authority changes
- Enforcement or blocking logic

---

## 🔒 What This Is NOT

| Concern                       | Use This Library? |
|-------------------------------|-------------------|
| Security Events               | ❌ No              |
| Permission Changes            | ❌ No              |
| Authentication / Login Audits | ❌ No              |
| Legal / Compliance Audits     | ❌ No              |
| Fail-closed operations        | ❌ No              |

For those, use a **dedicated Audit or Security Log system**.

---

## 🧠 Design Principles

- **Fail-Open**  
  Logging failures must never break user flow.

- **Side-Effect Only**  
  No return values, no control decisions.

- **Explicit Intent**  
  Activity meaning is defined by canonical action strings or enums.

- **Driver-Based Persistence**  
  Storage is abstracted via `ActivityLogWriterInterface`.

---

## 📦 Architecture Overview

```

ActivityRecorder (Domain)
│
▼
ActivityLogService (Module)
│
▼
ActivityLogWriterInterface
│
├── MySQLActivityLogWriter
├── (Future: MongoDB, Queue, File, etc.)

```

---

## 🧩 Core Components

### ActivityLogService
Main entry point used by application code.

### ActivityLogDTO
Immutable data carrier describing a single activity.

### ActivityLogWriterInterface
Contract for persistence drivers.

### Drivers
Concrete implementations (e.g. MySQL).

---

## 🏷️ Activity Actions

Actions can be provided as:

- **Enum implementing `ActivityActionInterface`**
- **Plain string** (fallback / custom actions)

Example canonical action:
```

admin.user.update

```

---

## 🧪 Testing

The library is fully testable using:

- Fake writers for unit tests
- Real drivers for integration tests

---

## 📊 Activity Log Listing & Querying

The Activity Log module supports **read-only listing and querying** of recorded activity events through a canonical LIST pipeline.

This functionality is designed for **administrative visibility and operational monitoring only**.

> ⚠️ **Important:**
> Activity Logs are **NOT authoritative audit records**.
> They are **best-effort, fail-open, non-blocking** records intended for observability, not compliance.

---

### Architecture Overview

```
ActivityLogQueryController
        ↓
ActivityLogListReaderInterface
        ↓
PdoActivityLogListReader
        ↓
activity_logs (MySQL)
```

---

### Key Characteristics

* **Read-Only**
  No mutation or side effects are allowed during listing.

* **Authorization-Driven**
  Access is enforced by the caller (Controller), not by the Reader.

* **Canonical LIST Pipeline**
  Querying follows the same standardized flow used across the system:

  * `SharedListQuerySchema`
  * `ListQueryDTO`
  * `ListCapabilities`
  * `ListFilterResolver`
  * `ResolvedListFilters`

* **Strict Filter Whitelisting**
  Only explicitly declared filters are accepted.
  Unknown or undeclared filters are ignored or rejected.

* **Date Filtering**

  * Supported via a single trusted column: `occurred_at`
  * Uses inclusive day boundaries (`00:00:00` → `23:59:59`)

---

### Supported Query Features

* **Global Search**

  * Matches against:

    * `action`
    * `request_id`

* **Column Filters**

  * `actor_type`
  * `actor_id`
  * `action`
  * `entity_type`
  * `entity_id`
  * `request_id`

* **Pagination**

  * Page / per-page based
  * Includes total vs filtered counts

---

### Security & Scope Notes

* Activity Logs are **security-sensitive** but **non-authoritative**
* They **do not replace**:

  * `audit_logs`
  * `security_events`
* Deletion, mutation, or correction of activity logs is **out of scope**

---

### Intended Use Cases

✔ Admin dashboards
✔ Operational monitoring
✔ Debugging and traceability
✔ User-facing history views (read-only)

❌ Compliance auditing
❌ Legal or financial authority
❌ Security enforcement source

---

## 📄 License

MIT (or project license)

---

## ✨ Status

- Stable
- Extractable as standalone library
- No framework dependency
