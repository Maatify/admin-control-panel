# ADR-018: Use String Codes Instead of Foreign Keys for Scope/Domain in I18n

**Status:** ACCEPTED
**Date:** 2026-02-12
**Decision ID:** ADR-018

---

## 1. Context

Within `maatify/i18n`:

* The `i18n_keys` table stores:

    * `scope` (VARCHAR)
    * `domain` (VARCHAR)

* The `i18n_domain_scopes` table stores:

    * `scope_code`
    * `domain_code`

Instead of using:

* `scope_id` → FK to `i18n_scopes.id`
* `domain_id` → FK to `i18n_domains.id`

The architectural question was:

> Should we use numeric Foreign Keys?
> Or use string codes as the canonical identity?

---

## 2. Decision

We formally adopt:

> **String Codes (VARCHAR) as the canonical identity for Scope and Domain within I18n tables.**

No numeric Foreign Keys are used between:

* `i18n_keys`
* `i18n_scopes`
* `i18n_domains`

---

## 3. Rationale

### 3.1 Library Extraction Readiness

`maatify/i18n` is designed to be:

* A standalone library
* Extraction-ready
* Independent from centralized database identity coupling

Using string identity:

* Makes data portable
* Avoids ID remapping during extraction
* Prevents tight coupling to primary key values

---

### 3.2 Stable Business Identity

In this context:

* `scope` represents a business boundary (admin, client, api, etc.)
* `domain` represents a functional boundary (auth, dashboard, products, etc.)

These are **stable business identifiers**, not arbitrary row references.

Using string codes:

* Makes the dataset self-descriptive
* Improves dump readability
* Simplifies debugging
* Avoids indirect lookups

---

### 3.3 Governance Is Enforced at the Service Layer

Referential correctness is enforced via:

`I18nGovernancePolicyService`

This is intentional:

* Policy is enforced at the application layer
* Not delegated entirely to database constraints

This preserves modular boundaries and keeps governance logic centralized.

---

### 3.4 Developer Experience (DX)

Using string codes:

* Eliminates ID-to-code resolution joins
* Simplifies queries
* Reduces query complexity
* Simplifies caching strategies
* Improves maintainability

---

## 4. Trade-offs

### 4.1 No Database-Level Referential Integrity

There are no Foreign Key constraints between:

* `i18n_keys.scope` → `i18n_scopes.code`
* `i18n_keys.domain` → `i18n_domains.code`

This is an accepted trade-off in exchange for:

* Greater modular independence
* Extraction portability
* Simpler runtime reads

---

### 4.2 Potential Manual Data Inconsistency

If data is inserted manually (bypassing services), invalid values could be stored.

Mitigation:

* STRICT governance mode
* Fail-Hard write services
* Centralized validation logic

---

## 5. Rejected Alternative

### Using Numeric Foreign Keys (`scope_id`, `domain_id`)

Rejected because:

* Introduces tighter coupling
* Increases migration complexity during extraction
* Complicates caching
* Makes dumps less portable
* Reduces readability
* Adds unnecessary joins for resolving business identity

---

## 6. Long-Term Impact

This decision:

* Preserves clean module boundaries
* Keeps I18n extraction-ready
* Makes data dumps portable across systems
* Avoids ID-based coupling to governance tables
* Simplifies caching and read patterns

---

## 7. Conditions for Re-evaluation

This ADR may be revisited if:

* A large-scale multi-tenant system requires strict DB-level enforcement
* Governance logic proves insufficient at the application layer
* Data integrity issues arise that cannot be mitigated via services

Otherwise, this decision remains closed.

---

## Final Statement

`scope` and `domain` are **business identity strings**, not row identifiers.

Using string codes instead of numeric Foreign Keys is:

> Intentional, architectural, and protected by design.

---
