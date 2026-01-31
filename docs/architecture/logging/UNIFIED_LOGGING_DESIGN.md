# UNIFIED_LOGGING_DESIGN

> **Project:** maatify/admin-control-panel
> **Status:** CANONICAL (Unified Design + Enforcement Rules)
> **Scope:** Defines the unified logging architecture, layering, authority boundaries, storage semantics, and forbidden patterns.
> **Terminology Source of Truth:** `docs/architecture/logging/LOG_DOMAINS_OVERVIEW.md`
> **Storage Guidance (Optional):** `docs/architecture/logging/LOG_STORAGE_AND_ARCHIVING.md` *(not required for baseline)*

---

## 0) Purpose

This document defines a single unified approach to logging across the system that:

* prevents domain mixing (conceptual confusion)
* enforces consistent layering (HTTP → Domain policy → Storage)
* provides honest failure semantics (no hidden failures except where explicitly permitted)
* supports scalable retention (baseline first; archiving remains optional guidance)
* enables future extraction of each logging domain as an independent library

---

## 1) Canonical Domains (6)

**Domain intent and classification rules are defined only in:**

* `docs/architecture/logging/LOG_DOMAINS_OVERVIEW.md`

This design supports exactly **six** domains:

1. Authoritative Audit
2. Audit Trail
3. Security Signals
4. Operational Activity
5. Diagnostics Telemetry
6. Delivery Operations

**Hard rule:** Every event belongs to exactly **one** domain.

---

## 2) High-Level Architecture

All logging domains follow the same conceptual pipeline:

@@@
HTTP / UI / Controllers
|
v
Domain Recorder (policy + context)
|
v
Domain Logger / Writer (storage adapter interface)
|
v
Storage Driver (MySQL baseline; optional additional backends)
@@@

### 2.1 What “Recorder” Means (Mandatory)

A Recorder is the **single** place where:

* event policy is applied (what to log, when, what metadata is allowed)
* request context is normalized (actor, correlation, requestId, routeName, ip, userAgent)
* DTO construction is centralized

Recorders prevent:

* controllers/services inventing ad-hoc log shapes
* cross-domain contamination
* repeated copy/paste metadata extraction

---

## 3) Layering Rules (Hard Rules)

### 3.1 Allowed Responsibilities by Layer

**Controllers / HTTP**

* MAY call domain recorders (directly or via services that call recorders).
* MUST NOT build logging DTOs manually.
* MUST NOT write directly to storage.

**Domain Services**

* MAY call recorders (preferred) or trigger recorder calls through workflow orchestration.
* MUST NOT write directly to storage drivers.

**Recorders**

* MUST be pure “policy + DTO construction” using safe context.
* MUST NOT contain SQL / direct storage logic.
* MUST enforce data safety and metadata limits (see Section 9 and Section 14).

**Infrastructure Drivers**

* MUST be storage-specific only (MySQL, optional backends if enabled).
* MUST NOT reinterpret policy or classify domains.
* MUST throw domain-specific storage exceptions (never swallow).

### 3.2 Forbidden Shortcuts

* Direct `PDO->prepare/execute` from controllers for logging
* Direct external DB writes from controllers for logging
* “One Logger to log everything” design
* Reusing Telemetry to log Data Access (Audit Trail)
* Reusing Operational Activity for views/reads/exports
* Writing Security Signals into Audit tables (or vice versa)

---

## 4) Authority Model

### 4.1 Authoritative vs Non-Authoritative

There are two authority classes:

#### A) Authoritative (Compliance Grade)

* Applies only to **Authoritative Audit**
* Has integrity expectations and controlled writer pipeline
* Uses outbox + materialized log pattern

#### B) Non-Authoritative (Best-Effort)

Applies to:

* Audit Trail
* Security Signals
* Operational Activity
* Diagnostics Telemetry
* Delivery Operations

These logs are operationally important, but not “compliance source of truth”.

---

## 5) Storage Semantics (Canonical Baseline)

### 5.1 Baseline Storage Targets (MySQL)

The baseline schema defines one dedicated MySQL table per domain:

* Authoritative Audit:

  * `authoritative_audit_outbox` *(authoritative source)*
  * `authoritative_audit_log` *(materialized query table; written only by consumer)*

* Audit Trail:

  * `audit_trail`

* Security Signals:

  * `security_signals`

* Operational Activity:

  * `operational_activity`

* Diagnostics Telemetry:

  * `diagnostics_telemetry`

* Delivery Operations:

  * `delivery_operations`

### 5.2 Optional Backends (Deferred / Not Required)

Additional backends (e.g., Mongo cold store) are OPTIONAL and MUST NOT be assumed.

If enabled in the future, storage and retention behavior MUST be documented in:

* `docs/architecture/logging/LOG_STORAGE_AND_ARCHIVING.md`

**Baseline rule:** the system MUST remain correct and complete with MySQL-only storage.

---

## 6) Failure Semantics (“Honest Contracts”)

### 6.1 Core Principle

Logging must not fail silently in infrastructure unless explicitly permitted by policy.

### 6.2 Allowed Swallowing (Very Limited)

**Recorder Exception Boundary (Hard Rule):**
- For all **Non-Authoritative** domains, `Recorder::record()` MUST be **fail-open** and MUST NOT throw under any condition.
- Therefore, the Recorder MUST catch **`Throwable` at the top-level boundary** of `record()`.
- Infrastructure MUST remain honest (never swallow) and MUST throw **domain-specific storage exceptions**.
- The Recorder MUST swallow after catching `Throwable` (record() MUST NOT throw), and MUST surface the failure via PSR-3 (and/or safe last-resort channel) without recursion.

**Recursion Guard (Hard Rule):**
- Failure reporting MUST NOT call any logging domain recorder/writer again.
- The last-resort channel MUST be primitive (e.g., `error_log`, syslog, stderr) and MUST NOT depend on DTOs/UUID/JSON encoding.

* **Non-authoritative recorders MAY treat storage failures as best-effort** if and only if:
  * the swallow is explicit and documented as “best-effort logging”
  * the infrastructure driver itself remains honest (does not swallow)
  * the failure is surfaced operationally (PSR-3 warning) and SHOULD be captured via Diagnostics Telemetry **without creating recursive failures** (sanitized)

**Important:** If Diagnostics Telemetry write fails too, it MUST NOT cascade into further writes; PSR-3 is the last-resort operational visibility channel.

### 6.3 Forbidden Swallowing

* Infrastructure drivers MUST NOT swallow exceptions silently.
* “Try/catch empty” in storage drivers is forbidden.
* Best-effort does not mean “silent”.
* Swallowing is ONLY permitted at the **Recorder boundary** for **Non-Authoritative** domains (best-effort).
* Any swallowing inside Infrastructure/Repository/DTO layers is forbidden.

### 6.3.1 Trait Usage Policy (Strict)
The following traits MUST NOT be used inside Domain, Application Services, Security, or Audit code:
* `Maatify\PsrLogger\Traits\LoggerContextTrait`
* `Maatify\PsrLogger\Traits\StaticLoggerTrait`

**Reason:** They bypass Dependency Injection and violate transactional boundaries.
`StaticLoggerTrait` is permitted **only** in bootstrap scripts, CLI tools, and cron jobs.

**Read-Mapping Corruption Tolerance (Explicit Exception):**
- Reader implementations MAY swallow JSON decode errors for `metadata` ONLY during read-mapping.
- In case of corruption, `metadata` MUST become `null` (best-effort hydration), and the event MUST still be returned.
- No other swallowing is permitted in read-mapping.

### 6.4 Authoritative Audit Semantics

* Authoritative audit MUST preserve integrity.
* If outbox write fails, this is not “best-effort” — it is a system integrity failure and MUST be handled as such.

---

## 7) Canonical Fields & Context (All Domains)

Each log domain event MUST support the following normalized context (where applicable):

* `actor_type` (string)
* `actor_id` (nullable integer)
* `correlation_id` (nullable string)
* `request_id` (nullable string)
* `route_name` (nullable string)
* `ip_address` (nullable string)
* `user_agent` (nullable string)
* `occurred_at` (DATETIME(6))

### 7.1 Timezone Rule (Hard)

* `occurred_at` MUST be stored in **UTC** across ALL domains.
* Application layer MUST convert local time to UTC before insert.
* Display/UI layer is responsible for timezone rendering per user.

### 7.1.1 Numeric Hydration Rule (PDO / MySQL)
- For MySQL drivers, numeric columns (e.g., BIGINT) MAY be returned as strings by PDO.
- Query mappers MUST treat numeric strings as valid and cast safely (e.g., `is_numeric` + `(int)`), instead of relying on `is_int`.

### 7.2 Event Identity (Recommended)

For non-authoritative domains:

* `event_id` (UUID string) is strongly recommended for idempotency and traceability.

### 7.3 Correlation vs Request (Canonical Glossary)

* `request_id`:

  * Unique ID for a single HTTP request/response cycle.
  * Used to correlate logs generated inside the same request.

* `correlation_id`:

  * Links events across multiple requests that belong to one business/operational transaction.
  * Example: multi-step flow spanning multiple requests or async continuation.

---

## 8) Domain-Specific Contracts (What Each Domain Must Provide)

### 8.1 Authoritative Audit

* MUST use controlled write pipeline (outbox + consumer)
* MUST record governance/security posture changes only
* MUST NOT be used for:

  * views/reads/exports
  * security failures
  * diagnostics telemetry
  * delivery lifecycle

### 8.2 Audit Trail

* MUST represent data exposure:

  * reads/views/navigation/exports/downloads
* MUST sanitize URLs and referrers (see Section 9.1 and Section 14.3)
* SHOULD support “subject tracking” when accessing user/customer data

### 8.3 Security Signals

* MUST represent auth/authorization/session anomalies
* MUST include severity
* SHOULD include safe “reason” and minimal metadata
* MUST NOT affect control flow (best-effort)

### 8.4 Operational Activity

* MUST represent mutations and operational actions only
* MUST NOT include views/reads/exports

### 8.5 Diagnostics Telemetry

* MUST represent technical observability:

  * durations, subsystem markers, sanitized errors
* MUST avoid PII and secrets

### 8.6 Delivery Operations

* MUST represent lifecycle of async operations:

  * queued/sent/delivered/failed/retrying
* MUST include attempt counters and safe provider info
* MUST follow retry policy rules (Section 14.6)

---

## 9) Data Safety (Hard Rules)

Logging must NEVER store:

* passwords
* raw OTP codes
* access tokens
* session secrets
* encryption keys
* signed URLs containing secrets

### 9.1 URL Sanitization (Hard)

* store safe paths only (strip query parameters)
* never store full URLs that include tokens/codes/signatures
* path segments MAY contain sensitive tokens in some systems; therefore:

  * any path segments that can contain secrets MUST be masked or hashed
  * schema and documentation MUST explicitly call this out for fields like `referrer_path`

### 9.2 Metadata Discipline (Hard)

* prefer allowlisted keys
* avoid dumping raw payloads
* keep JSON minimal and structured
* enforce maximum size policy (Section 14.1)

---

## 10) Naming & Taxonomy Rules

To prevent confusion, each domain must use a consistent naming model:

* Audit Trail: `event_key` (e.g., `customer.view`, `orders.export`)
* Security Signals: `signal_type` + severity (e.g., `login_failed`, `permission_denied`)
* Operational Activity: `action` (e.g., `customer.update`, `settings.change`)
* Diagnostics Telemetry: `event_key` + metrics (e.g., `http.request`, `db.slow_query`)
* Delivery Operations: `operation_type` + `channel` + `status`

Avoid free-text strings where structured enums/taxonomy exist.

---

## 11) ASCII Documentation Rules

ASCII diagrams must follow:

* `docs/architecture/logging/ASCII_FLOW_LEGENDS.md`

No alternative symbols, no informal arrows, no custom markers.
All flow diagrams must use the canonical legend.

---

## 12) Future Library Extraction (Design Constraint)

The code and structure MUST remain compatible with extracting each domain as its own library.

This implies:

* domain-specific contracts are isolated
* shared primitives are minimal
* no “mega logger” module
* no domain-specific policy hidden inside generic tooling

---

## 13) Compliance Checklist (Quick)

A logging implementation is compliant only if:

* It is classified into exactly one of the six domains.
* It routes through a domain recorder (policy + context).
* Infrastructure does not silently swallow exceptions.
* Telemetry is not used for access tracking.
* Operational Activity does not contain reads/views.
* Audit Trail contains reads/views/exports/navigation.
* Authoritative Audit uses outbox + consumer pipeline.
* Optional storage backends (if enabled) are documented explicitly.

---

## 14) Canonical Operational Policies (Clarifications Added)

This section upgrades previously “non-blocking review notes” into **canonical, enforceable documentation** to remove ambiguity and ensure this document is a true source of truth.

### 14.1 Metadata Size Policy (Hard)

* `metadata` MUST have an enforced maximum size at the application layer.
* Canonical limit: **64 KB per event** (post-serialization).
* Violations MUST result in:

  * trimming to allowlisted safe keys, OR
  * rejection for Authoritative Audit events (integrity), OR
  * best-effort drop with PSR-3 warning for non-authoritative domains (policy decision)

**Forbidden patterns:**

* storing full request bodies
* storing full stack traces as metadata payloads
* storing multi-megabyte debug dumps

### 14.2 actor_type Allowed Values (Hard)

To prevent taxonomy drift, `actor_type` MUST be validated against canonical values.

Allowed values:

* `SYSTEM`
* `ADMIN`
* `USER`
* `SERVICE`
* `API_CLIENT`
* `ANONYMOUS`

Any new value requires an explicit documented architectural decision.

### 14.3 Audit Trail referrer_path / URL Safety (Hard)

Even “path-only” can contain sensitive segments.

For any stored path or referrer field:

* Strip all query strings
* Mask or hash sensitive path parameters where tokens/codes/signatures may appear
* Prefer templated representation:

  * Example: `/reset-password/{hashed}` rather than `/reset-password/abc123`

### 14.4 Authoritative Outbox Processing Guarantees (Canonical)

The outbox pipeline MUST be resilient to consumer failure.

Canonical requirements:

* Consumer MUST be idempotent (keyed by `event_id` or equivalent)
* Consumer MUST retry with exponential backoff
* Consumer MUST stop infinite retries and surface failures

Minimum operational policy (baseline):

* Retry: exponential backoff
* Max attempts: **10**
* After max attempts: move to a **manual intervention queue** (dead-letter semantics) or mark terminal failure in a dedicated status/field

Monitoring requirement:

* Alert if outbox lag exceeds a policy threshold (example: > 5 minutes)

### 14.5 Archiving Trigger Policy (If Optional Archiving Is Enabled)

Archiving is OPTIONAL and not required for baseline correctness.
If enabled, an explicit trigger policy MUST be documented and implemented.

Recommended canonical defaults (adjust per deployment):

* Trigger: records older than **90 days** (domain-specific retention may differ)
* Frequency: daily (off-peak)
* Batch size: **10,000** rows/run (tunable)
* Verification: ensure transfer success before delete (hard rule)
* Rollback safety: if archive fails, hot data stays

### 14.6 Delivery Operations Retry Policy (Canonical)

To avoid infinite loops, Delivery Operations MUST have a bounded retry policy.

Minimum operational policy:

* Max attempts: **5**
* Backoff: exponential (example schedule: 1m, 5m, 15m, 1h, 6h)
* After max: status MUST transition to a terminal failure state (example: `failed_permanent`)
* Terminal failures MUST be discoverable by query/UI

### 14.7 Performance & Scale Guidance (Non-Blocking but Canonical-Aware)

This document does not mandate a specific throughput target, but it mandates design awareness:

* High-volume domains (Telemetry, Security Signals) MUST remain best-effort.
* Index strategy MUST remain aligned with investigation query patterns (actor/time, event_key/time).
* If sustained write contention occurs, the system MAY evolve via:

  * partitioning (future ADR)
  * read replicas for reporting
  * archiving automation (optional modes)

### 14.8 GDPR / Retention / Right-to-Be-Forgotten (Policy Boundary)

Logs can be compliance-sensitive. Deleting logs may break audit integrity.

Canonical posture:

* Authoritative Audit:

  * MUST NOT be deleted by default (legal/compliance obligation)
* Other domains:

  * MUST follow retention policy
  * For “right-to-be-forgotten” requests:

    * prefer anonymization/pseudonymization (policy decision per domain)
    * never remove integrity-critical governance history

Any GDPR strategy MUST be explicitly documented per deployment, but this design sets the default principle:

* preserve integrity
* minimize personal data
* apply retention
* anonymize where legally required and technically safe

---
