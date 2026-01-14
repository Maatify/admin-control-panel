# Telemetry Logging Governance

**Status:** ARCHITECTURE-APPROVED
**Author:** Architecture Team
**Last Updated:** 2026-01-12

---

## 1. Purpose

The Telemetry Logging system (`telemetry_traces`) exists to provide **high-volume, best-effort observability** into system behavior, access patterns, and internal logic flows.

It is distinct from Audit Logs in that it:
*   Prioritizes **volume and speed** over strict consistency.
*   Is **non-blocking** (failures must not stop user flow).
*   Is **not authoritative** (cannot be used for legal proof of state).
*   Is **not transactional** (writes happen regardless of transaction rollback, if implemented as such, though currently tied to PDO it shares the connection but should swallow errors).

Common Use Cases:
*   Tracing authorization decisions (grants/denials).
*   Debugging step-up flows.
*   Analyzing high-frequency access patterns.
*   Performance monitoring (via timestamps).

---

## 2. Strict Rules

1.  **NO Audit Pollution:** Telemetry MUST NEVER write to the `audit_logs` table. That table is strictly reserved for `AuthoritativeSecurityAuditWriterInterface`.
2.  **Best-Effort:** Writers MUST catch and suppress all exceptions. A database failure in telemetry must never cause a 500 error for the user.
3.  **No Secrets:** Telemetry metadata MUST NOT contain passwords, raw tokens, TOTP secrets, or full PII unless encrypted/masked.

---

## 3. Data Model

Table: `telemetry_traces`

| Column           | Type         | Description                                                                 |
|------------------|--------------|-----------------------------------------------------------------------------|
| `id`             | BIGINT (PK)  | Auto-increment primary key.                                                 |
| `event_key`      | VARCHAR(255) | Dot-notation event name (e.g., `auth.login.attempt`, `system_capability`). |
| `severity`       | VARCHAR(20)  | Log level (`info`, `warning`, `error`, `debug`). Default: `info`.           |
| `actor_admin_id` | INT (NULL)   | ID of the admin performing the action, if authenticated.                    |
| `ip_address`     | VARCHAR(45)  | Client IP address (IPv4/IPv6).                                              |
| `user_agent`     | VARCHAR(255) | Client User-Agent string (truncated).                                       |
| `route_name`     | VARCHAR(255) | Slim route name or path pattern (e.g., `admins.list`).                      |
| `request_id`     | VARCHAR(64)  | Unique request correlation ID (from `RequestContext`).                      |
| `metadata`       | JSON         | Arbitrary context data (target IDs, changes, reasons).                      |
| `created_at`     | DATETIME(6)  | High-precision timestamp.                                                   |

**Indexes:**
*   `idx_created_at`: For time-range queries (pruning/analysis).
*   `idx_event_key`: For filtering by event type.
*   `idx_actor`: For tracing specific user activity.
*   `idx_request`: For correlating logs within a single request.

---

## 4. Retention & Pruning Policy

Due to the high volume of telemetry data, this table is expected to grow rapidly.

*   **Retention Window:** 30 Days (Guidance).
*   **Pruning Strategy:** A scheduled job (Cron) should `DELETE FROM telemetry_traces WHERE created_at < NOW() - INTERVAL 30 DAY`.
*   **Optimization:** For very high volumes, table partitioning by range (date) is recommended in the future to allow `DROP PARTITION`.

---

## 5. Non-Goals

*   **Auth Decisions:** Do NOT query `telemetry_traces` to determine if a user "has done X" for permission checks. Use `audit_logs` or domain tables.
*   **Source of Truth:** Telemetry is lossy. Gaps may exist. Do not rely on it for billing or legal compliance.
