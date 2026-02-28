# 🌍 I18n Scope Domain Coverage — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / I18n`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

# 0) Why this document exists

This file defines the **Scope → Language → Domain Coverage integration contract**.

It documents:

* What the Scope Domain Coverage endpoint returns
* What the UI is allowed to send
* What navigation is allowed
* How the Derived Layer must be consumed
* What is explicitly forbidden

This is a **read-only derived view**.

If behavior is not documented here, it is not supported.

---

# 1) Architectural Context

Scope Domain Coverage is:

* ✅ A breakdown of a selected **Scope**
* ✅ For a selected **Language**
* ✅ Aggregated per **Domain**
* ❌ Not a standalone Domain module
* ❌ Not allowed to mutate data

Granularity:

```
(scope, domain, language)
```

Primary Data Source:

```
i18n_domain_language_summary
```

---

# 2) UX Flow

---

## Step 1 — User selects Scope

```
GET /i18n/scopes/{scope_id}
```

---

## Step 2 — User selects Language

From:

```
/i18n/scopes/{scope_id}/coverage
```

Navigation:

```
GET /i18n/scopes/{scope_id}/coverage/languages/{language_id}
```

---

## Step 3 — User selects Domain

From:

```
/i18n/scopes/{scope_id}/coverage/languages/{language_id}
```

Navigation:

```
/i18n/scopes/{scope_id}/domains/{domain_id}/translations?language_id={language_id}
```

This must preserve context.

---

# 3) API Endpoint

---

## 3.1 Scope Domain Coverage

### Endpoint

```
GET /api/i18n/scopes/{scope_id}/coverage/languages/{language_id}
```

### Middleware

Must be inside protected group:

* AuthorizationGuardMiddleware
* Admin session validation

---

# 4) Response Model

```json
{
  "data": [
    {
      "domain_id": 1,
      "domain_code": "home",
      "domain_name": "Home",
      "total_keys": 50,
      "translated_count": 40,
      "missing_count": 10,
      "completion_percent": 80.0
    }
  ]
}
```

---

# 5) Response Semantics

The API MUST:

* Filter by `scope`
* Filter by `language_id`
* Join `i18n_domain_scopes`
* Join `i18n_domains`
* Use only summary table for metrics
* NOT join `i18n_translations`

---

# 6) Sorting Rules

Domains MUST be ordered by:

```
missing_count DESC,
d.sort_order ASC
```

Purpose:

* Weakest domains appear first
* Respect domain ordering

---

# 7) UI Table Contract

---

## 7.1 Table Structure

Rows only.

Correct structure:

| Domain | Total | Translated | Missing | Completion | Action |

Forbidden:

* Pivot layout
* Language columns
* Matrix table
* Horizontal expansion

---

## 7.2 Completion Display

Completion must:

* Use `completion_percent`
* Be formatted to 1 decimal
* Use existing Tailwind progress styling
* Follow same visual pattern as translations page

---

# 8) Navigation Contract

When user clicks “Go”:

```
/i18n/scopes/{scope_id}/domains/{domain_id}/translations?language_id={language_id}
```

Rules:

* Use numeric IDs
* Do not derive code client-side
* Do not call API before redirect
* Let server enforce authorization

---

# 9) JavaScript Rules

---

## 9.1 Location

JS must exist under:

```
public/assets/maatify/admin-kernel/js/pages/i18n/
```

---

## 9.2 Allowed Utilities

Must use:

* Existing DataTable implementation
* Existing API request utility
* Existing Select2 integration pattern

Forbidden:

* Introducing new HTTP client pattern
* Using non-existent methods (e.g., ApiHandler.get if not defined)
* Bypassing canonical query resolver

---

## 9.3 Empty State Handling

If API returns:

```
[]
```

UI must render:

“Nothing to translate for this language.”

No crash.

---

# 10) Performance Constraints

The implementation must:

* Use summary table only
* Avoid heavy COUNT queries
* Avoid scanning translations
* Avoid nested subqueries

Expected scale:

* ~1000 summary rows per scope
* Fast aggregation

---

# 11) Explicit Non-Goals

The following are not allowed:

* Editing coverage metrics
* Mutating summary table
* Client-side recomputation
* Ignoring scope-domain policy
* Introducing domain-first coverage outside scope context

---

# 12) Consistency Requirements

Must visually match:

* scope_details.twig
* scope_domain_keys_summary.twig
* scope_domain_translations.twig

Must use:

* layouts/base.twig
* Same breadcrumb pattern
* Same script injection pattern
* Same Tailwind design language

---

# 13) Implementation Checklist

* [ ] API endpoint exists
* [ ] Policy mapping enforced
* [ ] Sorting correct
* [ ] DTO matches response shape
* [ ] UI page exists
* [ ] JS file exists
* [ ] Pre-select language works
* [ ] Breadcrumb correct
* [ ] No ApiHandler misuse
* [ ] No duplicated routes

---

# 14) Final Architectural Note

Scope Domain Coverage:

* Is a Derived Read Model
* Improves translator efficiency
* Maintains clean separation between Governance and Derived Layers
* Does not affect write model
* Must remain lightweight

If any implementation contradicts this document,
this document wins.

---
