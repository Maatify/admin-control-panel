# 🌍 I18n Scope Coverage — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / I18n`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

# 0) Why this document exists

This file defines the **Scope Coverage Read Model integration contract**.

It documents:

* What Coverage endpoints return
* What UI is allowed to send
* What navigation flow is permitted
* How Derived Layer must be consumed
* What is NOT allowed

If it is not defined here, it is **not supported**.

---

# 1) Architectural Positioning

Scope Coverage is:

* ✅ **Read-only**
* ✅ Based on `i18n_domain_language_summary`
* ✅ Derived (non-authoritative)
* ❌ Not part of Scopes CRUD
* ❌ Not allowed to mutate data

This is a **Derived Read Model**.

It exists to improve translator workflow and visual clarity.

---

# 2) Data Source (Authoritative for Coverage)

Primary Table:

```
i18n_domain_language_summary
```

Granularity:

```
(scope, domain, language_id)
```

Columns:

* total_keys
* translated_count
* missing_count

This table is maintained by backend services.
UI MUST NOT assume or recompute values from `i18n_translations`.

---

# 3) Coverage Flow (UX Contract)

### Step 1 — Scope Overview

```
GET /i18n/scopes/{scope_id}
```

Page shows:

* Scope details
* Linked domains
* **NEW SECTION:** Scope Coverage (aggregated per language)

---

### Step 2 — Select Language

User clicks:

```
View Domains (Language Row)
```

Navigation:

```
GET /i18n/scopes/{scope_id}/coverage/languages/{language_id}
```

This is a UI page (HTML), not API.

---

### Step 3 — Select Domain

User clicks:

```
Go → Translations
```

Navigation:

```
/i18n/scopes/{scope_id}/domains/{domain_id}/translations?language_id={language_id}
```

UI must pre-select language in select2.

---

# 4) API Endpoints

---

## 4.1 Scope Coverage (Aggregated by Language)

### Endpoint

```
GET /api/i18n/scopes/{scope_id}/coverage
```

### Capability

Authenticated admin only
(AuthorizationGuardMiddleware applied)

---

### Response Model

```json
{
  "data": [
    {
      "language_id": 1,
      "language_code": "en",
      "language_name": "English",
      "language_icon": null,
      "total_keys": 120,
      "translated_count": 110,
      "missing_count": 10,
      "completion_percent": 91.6
    }
  ]
}
```

---

### Semantics

* Aggregates ALL domains assigned to the scope
* Uses SUM over summary table
* Must join `i18n_domain_scopes`
* Must NOT count via `i18n_translations`
* Must NOT ignore policy mapping

---

## 4.2 Scope Coverage Breakdown (Domains per Language)

### Endpoint

```
GET /api/i18n/scopes/{scope_id}/coverage/languages/{language_id}
```

---

### Response Model

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

### Semantics

* Returns domains for specific language
* Sorted by:

  ```
  missing_count DESC
  sort_order ASC
  ```
* Must join `i18n_domain_scopes`
* Must filter by scope
* Must filter by language_id

---

# 5) UI Implementation Rules

---

## 5.1 Layout Rules

Templates MUST follow same structure as:

* languages_list.twig
* scopes.list.twig
* scope_details.twig
* scope_domain_translations.twig

Required:

* extends layouts/base.twig
* breadcrumb block
* capability injection
* script block at bottom
* Tailwind utilities only

Forbidden:

* New layout system
* Inline styling
* Different table component
* Language-as-columns matrix table

---

## 5.2 Table Rules

Tables must:

* Render rows only
* No language columns expansion
* No pivot matrix layout

Correct pattern:

| Language | Total | Translated | Missing | Completion | Action |

---

## 5.3 JavaScript Rules

All JS must live under:

```
public/assets/maatify/admin-kernel/js/pages/i18n/
```

Must use:

* Existing DataTable logic
* Existing Api utilities
* Existing Select2 handling

Forbidden:

* Introducing new API wrapper patterns
* Using non-existent ApiHandler methods
* Hardcoding fetch logic inconsistent with project pattern

---

## 5.4 Language Pre-Selection Contract

When landing on:

```
/i18n/scopes/{scope_id}/domains/{domain_id}/translations?language_id={language_id}
```

JS must:

1. Detect query param
2. Set select2 value
3. Trigger normal data reload

Must not:

* Manually override internal translation filters
* Bypass canonical reload logic

---

# 6) Performance Guarantees

Coverage must:

* Use summary table
* Avoid scanning i18n_translations
* Avoid heavy joins
* Be O(domains × languages)

Expected scale:

* 50 domains
* 20 languages
* ~1000 rows in summary
* Very fast

---

# 7) Error Handling

---

## 7.1 Scope Not Found

API returns:

```
[]
```

UI must render empty state.

---

## 7.2 Unauthorized

403 handled by middleware.
UI must not override.

---

## 7.3 Invalid language_id

Returns empty list.

No crash.

---

# 8) Explicit Non-Goals

The following are NOT supported:

* Editing coverage
* Modifying summary table from UI
* Recomputing coverage client-side
* Bypassing scope-domain policy
* Using i18n_key_stats for language-based coverage

---

# 9) Implementation Checklist

* [ ] Coverage API implemented
* [ ] Joins enforce policy mapping
* [ ] UI page exists
* [ ] JS file exists
* [ ] Select2 preselect works
* [ ] Breadcrumb correct
* [ ] No ApiHandler misuse
* [ ] No duplicated route names

---

# 10) Final Architectural Note

Scope Coverage:

* Improves translator workflow
* Reduces cognitive load
* Highlights weak domains first
* Does NOT alter data model
* Does NOT alter write paths

It is a **read-optimization layer only**.

---

If any behavior contradicts this document,
the document wins.

---
