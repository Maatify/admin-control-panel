# ADR-001: Canonical Input Normalization Boundary

## Status
**ACCEPTED — LOCKED**

## Date
2026-01-10

## Context

The Admin Control Panel handles input from multiple clients and historical integrations
(Web UI, API clients, legacy JS, and older endpoints).

Over time, this resulted in **inconsistent input shapes**, including but not limited to:

- Pagination keys: `limit` vs `per_page`
- Date range keys: `from/to` vs `from_date/to_date`
- Optional nesting differences in filters/search
- Controllers passing raw `(array)$request->getParsedBody()` directly to validation

This caused:
- Schema duplication
- phpstan shape violations
- Ambiguous validation behavior
- Controllers compensating for legacy input in ad-hoc ways

A deterministic and explicit boundary was required to prevent further architectural drift.

---

## Decision

We introduce a **mandatory Canonical Input Normalization layer**.

### The system SHALL:

1. Normalize request input **before any validation or guards**
2. Convert legacy keys into canonical keys
3. Apply explicit precedence rules when both legacy and canonical keys exist
4. Ensure that **all validation schemas and DTOs receive canonical input only**

This responsibility is implemented as a dedicated module:

```

App\Modules\InputNormalization

````

and enforced via a global middleware.

---

## Canonical Input Rules

### Canonical Keys (Authoritative)

```text
page
per_page
date.from
date.to
````

### Legacy Compatibility (Backward-Safe)

| Legacy Key | Canonical Key |
|------------|---------------|
| limit      | per_page      |
| from       | date.from     |
| to         | date.to       |

### Precedence Rule (LOCKED)

> If both legacy and canonical keys exist,
> **canonical keys ALWAYS win**.

Legacy keys are ignored once canonical keys are present.

---

## Responsibilities (Strict)

### Input Normalization Layer

**ALLOWED**

* Key mapping
* Canonicalization
* Precedence resolution

**FORBIDDEN**

* Validation
* Sanitization
* Business logic
* Defaulting domain values
* Error generation

This layer is **purely structural**.

---

### Validation Layer

* Operates on **canonical input only**
* Assumes normalization is already complete
* MUST NOT contain legacy key handling

---

### Controllers

* MUST treat the output of normalization as the source of truth
* MUST declare canonical array shapes (phpstan) before constructing DTOs
* MUST NOT compensate for legacy input manually

---

## Middleware Ordering (Enforced)

Normalization MUST execute before validation and guards.

### Canonical Order (Slim)

```
InputNormalizationMiddleware   ← FIRST execution
RecoveryStateMiddleware
ValidationGuard
Authentication / Authorization
Controller
```

> In Slim: the Normalization middleware MUST be added last
> to ensure first execution.

This ordering is **non-negotiable**.

---

## Error Handling Implications

* Input Normalization NEVER produces errors
* Invalid or missing canonical values are handled **only by validation**
* Normalization failure MUST NOT be user-visible

---

## Consequences

### Positive

* Deterministic validation contracts
* Stable DTO construction
* phpstan level=max compliance
* Safe backward compatibility
* Single source of truth for input shape

### Negative / Trade-offs

* Slight upfront complexity
* Controllers must explicitly declare shapes
* Legacy shortcuts are no longer allowed

These trade-offs are **intentional and accepted**.

---

## Non-Goals (Explicit)

This ADR does **NOT** introduce:

* Input sanitization
* Security filtering
* XSS / SQL protections
* Default value injection
* Domain-level transformations
* Automatic migration of legacy clients

Those concerns belong to other layers.

---

## Enforcement

Any of the following is considered an **Architecture Violation**:

* Validation schemas handling legacy keys
* Controllers bypassing normalization
* Middleware reordering that breaks precedence
* DTOs constructed from non-canonical input

Violations MUST be reported and blocked.

---

## Related Decisions

* ValidationResultDTO defines the canonical error shape
* Authentication validation is transport-safety only
* Core security phases (1–13) are frozen

---

## Final Note

This ADR establishes **Input Normalization as a hard architectural boundary**.

Future contributors MUST treat normalized input as immutable truth.
Any change to this behavior requires a **new ADR** and explicit approval.
