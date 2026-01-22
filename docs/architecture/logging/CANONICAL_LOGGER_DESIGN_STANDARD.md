# CANONICAL_LOGGER_DESIGN_STANDARD

> **Project:** maatify/admin-control-panel
> **Status:** CANONICAL (Binding — Subordinate to unified-logging-system.*)
> **Scope:** Defines the **mandatory design standard** for building any logging domain as a standalone, extractable library.
> **Terminology Source of Truth:** `docs/architecture/logging/LOG_DOMAINS_OVERVIEW.md`
> **ASCII Language Source of Truth:** `docs/architecture/logging/ASCII_FLOW_LEGENDS.md`
> **Architecture Source of Truth:**
>
> * `unified-logging-system.ar.md`
> * `unified-logging-system.en.md`
    >   If a conflict exists, the Unified Logging System documents win.

---

## 0) Purpose

This standard exists to guarantee that **every logging domain**:

* is architecturally isolated
* has honest and predictable failure semantics
* enforces strict DTO discipline
* respects explicit policy boundaries
* can be extracted later as a standalone library **without redesign**

This standard applies to **all six logging domains** defined in
`LOG_DOMAINS_OVERVIEW.md`.

---

## 1) Domain Isolation (Hard Rule)

A logging module MUST represent **exactly one logging domain**.

* A domain module MUST NOT accept events from another domain.
* A domain module MUST NOT write to another domain’s storage.
* A domain module MUST NOT reuse another domain logger “for convenience”.

**Important:**
If a real-world action maps to multiple domains, it is **multiple events**,
not a shared event.

---

## 2) Mandatory Architectural Layers

Every logging domain implementation MUST contain the following layers:

1. **Recorder (Policy Boundary)**
2. **Contract (Writer / Logger Interface)**
3. **DTO Layer (Strong Types Only)**
4. **Infrastructure Driver (Storage Adapter)**

No layer may be skipped or merged.

---

### 2.1 Recorder Layer (Policy Boundary)

The Recorder is the **only policy-aware component**.

#### Mandatory Responsibilities

* Construct domain DTOs
* Normalize context:

    * `actor_type`, `actor_id`
    * `request_id`, `correlation_id`
    * `route_name`
    * `ip_address`, `user_agent`
    * `occurred_at` (**UTC only**)
* Enforce metadata policy:

    * allowlisted keys
    * sanitized values
    * **maximum size: 64KB**
* Decide whether storage failures may be swallowed (best-effort domains only)

#### Forbidden Responsibilities

* SQL or storage logic
* Infrastructure concerns
* Business decisions unrelated to logging policy

---

### 2.2 Contract Layer (Interfaces)

Each domain MUST define a stable, explicit contract.

Allowed patterns:

* `DomainLoggerInterface`
* `DomainWriterInterface`

Method signatures MUST:

* accept a **single DTO**
* return `void`
* throw domain-specific exceptions

Examples (conceptual, not code):

* `record(DomainRecordDTO $dto): void`
* `write(DomainWriteDTO $dto): void`

❌ Raw arrays are FORBIDDEN.

---

### 2.3 DTO Layer (Strict Discipline)

All logging APIs MUST accept DTOs.

#### Naming Rules

* DTO class names MUST end with `DTO`
* Enum names MUST end with `Enum`

#### DTO Properties

* Immutable (readonly where possible)
* Serializable into primitives only
* Contain:

    * domain-specific fields
    * normalized context fields
* MUST NOT contain:

    * secrets
    * raw request payloads
    * unserialized objects

---

### 2.4 Infrastructure Drivers (Storage Adapters)

Infrastructure drivers implement the domain contract and perform **I/O only**.

#### Hard Rules

* MUST NOT contain policy logic
* MUST NOT swallow exceptions
* MUST NOT log via PSR-3
* MUST throw **domain-specific storage exceptions**

Supported baseline driver:

* MySQL (PDO)

Archive drivers (if enabled) MUST comply with:

* `LOG_STORAGE_AND_ARCHIVING.md`

---

## 3) Failure Semantics (Honest Contracts)

### 3.1 Infrastructure MUST Throw

Infrastructure drivers:

* ALWAYS throw on failure
* NEVER return boolean success flags
* NEVER silently ignore errors

---

### 3.2 Swallow Is a Policy Decision

Only the Recorder (or explicit project policy boundary) may:

* catch storage exceptions
* optionally swallow them

Swallowing is allowed ONLY when:

* the domain is defined as best-effort
* business flow must not be broken

If swallowed:

* failure SHOULD be surfaced via **Diagnostics Telemetry** (sanitized)

---

### 3.3 Authoritative Audit — Special Case

Authoritative Audit is **NOT best-effort**.

Rules:

* failures MUST propagate
* swallowing is FORBIDDEN by default
* must use controlled pipeline:

    * outbox (transactional)
    * consumer (materialized log)

Integrity failures MUST block the governed change.

---

## 4) Canonical Context Model (All Domains)

Every domain event MUST support these normalized fields:

* `actor_type` (validated, enum-like)
* `actor_id`
* `request_id` (single request scope)
* `correlation_id` (multi-request workflow scope)
* `route_name`
* `ip_address`
* `user_agent`
* `occurred_at` (UTC)

### actor_type Allowed Values

The Recorder MUST validate `actor_type` against:

* SYSTEM
* ADMIN
* USER
* SERVICE
* API_CLIENT
* ANONYMOUS

Any other value is invalid.

---

## 5) Storage Target Discipline (Hard Rule)

A domain module MUST write **only** to its canonical storage target.

Storage targets MUST align with:

* `LOG_DOMAINS_OVERVIEW.md`
* `LOG_STORAGE_AND_ARCHIVING.md`

Examples:

* Audit Trail → `audit_trail` (+ `_archive` if enabled)
* Security Signals → `security_signals` (+ `_archive`)
* Operational Activity → `operational_activity` (+ `_archive`)
* Diagnostics Telemetry → `diagnostics_telemetry` (+ `_archive`)
* Delivery Operations → `delivery_operations` (+ `_archive`)
* Authoritative Audit → `authoritative_audit_outbox` + `authoritative_audit_log`

Cross-domain writes are FORBIDDEN.

---

## 6) Data Safety & Sanitization (Hard Rules)

### 6.1 Never Log Secrets

Forbidden in ALL domains:

* passwords
* raw OTP codes
* access tokens
* session secrets
* encryption keys
* signed URLs containing secrets

---

### 6.2 URL Sanitization

If URLs or referrers are logged:

* store path only
* strip query strings
* mask sensitive path segments (tokens, secrets)

---

### 6.3 Metadata Discipline

Metadata MUST be:

* structured
* minimal
* allowlisted where possible
* size-limited to **64KB**
* free of PII/secrets

Raw payload dumps are FORBIDDEN.

---

## 7) Taxonomy & Naming Standards

Domains MUST use stable taxonomy keys:

* Audit Trail → `event_key`
* Security Signals → `signal_type`
* Operational Activity → `action`
* Diagnostics Telemetry → `event_key`
* Delivery Operations → `operation_type`, `channel`, `status`

Free-text strings MUST NOT be used as primary classifiers.

---

## 8) Mandatory Diagram Compliance

All diagrams describing logging behavior MUST comply with:

* `ASCII_FLOW_LEGENDS.md`

Custom arrows, implicit semantics, or informal notation are INVALID.

---

## 9) Canonical Compliance Checklist

A logging domain implementation is compliant ONLY if:

* Domain is explicit and isolated
* Public API accepts DTOs only
* Infrastructure throws honest exceptions
* Recorder is the only swallow boundary (if any)
* Context normalization is complete and UTC-based
* Data safety rules are enforced
* Storage targets are correct and exclusive

---

**END OF CANONICAL LOGGER DESIGN STANDARD**
