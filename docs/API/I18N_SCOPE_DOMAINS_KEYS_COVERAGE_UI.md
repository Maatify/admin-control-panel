# 🔑 I18n Scope+Domain Keys Coverage — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / I18n`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is the **runtime integration contract** for the **Scope+Domain Keys Coverage** page.

It defines, precisely:

* What the UI is allowed to send
* How global search and column filters work
* What is required vs optional
* Response models (success + failure)
* Why you may get `422` or `DomainScopeViolationException`

If something is not documented here, treat it as **not supported**.

---

## 1) CRITICAL: UI Route vs API Route

### 1.1 UI Route (HTML Page)

**Route:**

```
GET /i18n/scopes/{scope_id}/domains/{domain_id}/keys
```

Example:

```
GET /i18n/scopes/1/domains/2/keys
```

**Rules**

* ❌ NOT an API
* ✅ Renders Twig (returns `text/html`)
* ❌ Must NOT be called via fetch/axios

**This route must:**

* Resolve `scope` (by id)
* Resolve `domain` (by id)
* Validate `scope-domain` assignment
* Inject:

    * `capabilities`
    * `languages` options for filter dropdown (recommended)
* Render:

    * Breadcrumb + header context
    * Scope/Domain cards
    * Filters area (including Language dropdown)
    * Table container
    * JS bundle

---

## 2) Page Architecture

```
GET /i18n/scopes/{scope_id}/domains/{domain_id}/keys
↓
Twig Controller
├─ resolve scope details by scope_id
├─ resolve domain details by domain_id
├─ verify scope-domain assignment (scope_domains)
├─ inject capabilities
├─ inject languages list for filter dropdown (recommended)
└─ render Keys Coverage page

JavaScript
├─ reads injected scope/domain ids
├─ reads injected languages (or loads via dropdown API if not injected)
└─ DataTable → calls Keys Coverage Query API

API (authoritative)
├─ validates request schema (SharedListQuerySchema)
├─ resolves filters via ListCapabilities
└─ returns {data,pagination}
```

---

## 3) Capabilities Contract (Authorization)

> **This page is REPORT-ONLY.**
> There is **NO upsert** and **NO delete** here.

Example capability shape:

```php
$capabilities = [
    'can_view_i18n_scopes' => $hasPermission('i18n.scopes.list'),
    'can_view_i18n_keys'   => $hasPermission('i18n.scopes.domains.keys'),
];
```

### 3.1 Capability → UI Mapping

| Capability             | UI Responsibility                            |
|------------------------|----------------------------------------------|
| `can_view_i18n_keys`   | Allow opening page + calling table query API |
| `can_view_i18n_scopes` | Enable breadcrumb link to `/i18n/scopes`     |

**Rules**

* JS MUST NOT infer authorization.
* Twig MUST only read **the injected boolean flags**.

---

## 4) Context Binding (MANDATORY)

This page is bound to **(scope_id + domain_id)** via route:

```
/i18n/scopes/{scope_id}/domains/{domain_id}/keys
```

Therefore:

* `scope_id` and `domain_id` come from the route.
* They MUST be injected into JS context.
* They MUST NOT be sent in request body.
* They are enforced server-side.
* If scope-domain assignment is invalid → server throws `DomainScopeViolationException`.

There is:

* ❌ No scope selector on this page.
* ❌ No domain selector on this page.
* ❌ No in-place context switching.

---

## 5) Keys Coverage (What this table represents)

This table is a **coverage report** over `i18n_keys` for a given `(scope,domain)`:

* Row identity: `i18n_keys.id`
* Computed metrics:

    * `total_languages` (depends on language filters)
    * `missing_count` (depends on language filters)

This is NOT the “translations list”.
This is a **keys coverage list**.

---

## 6) Keys Coverage Query API

### 6.1 API Endpoint

**Endpoint:**

```
POST /api/i18n/scopes/{scope_id}/domains/{domain_id}/keys/query
```

**Route Name:**

```
i18n.scopes.domains.keys.query.api
```

**Rules**

* `scope_id` and `domain_id` are NOT part of request body.
* They are resolved from the route.

---

### 6.2 Request Payload (SharedListQuerySchema)

| Field            | Type   | Required | Notes                                             |
|------------------|--------|---------:|---------------------------------------------------|
| `page`           | int    |       No | default: 1                                        |
| `per_page`       | int    |       No | default: 25                                       |
| `search`         | object |       No | wrapper                                           |
| `search.global`  | string |       No | applies to searchable columns                     |
| `search.columns` | object |       No | column filters                                    |
| `date`           | object |       No | **not supported** for this page (ignored by repo) |

---

### 6.3 Global Search (Supported Columns)

Global search applies to:

* `key_part`
* `description`

No other columns are searched by global text.

---

### 6.4 Column Filters (Supported / Explicit Only)

| Alias                | Type       | Required | Semantics                                    |
|----------------------|------------|---------:|----------------------------------------------|
| `key_id`             | int/string |       No | `k.id = value`                               |
| `key_part`           | string     |       No | `k.key_part = value` (exact)                 |
| `missing`            | int/string |       No | if `1` → `HAVING missing_count > 0`          |
| `language_id`        | int/string |       No | compute coverage against **single language** |
| `language_is_active` | int/string |       No | chooses language subset (see below)          |

---

## 7) Language Filters (CRITICAL CONTRACT)

### 7.1 `language_id` (optional)

If provided:

* Coverage is computed against **that single language** only.
* `total_languages` becomes **1** (if language exists in subset).
* `missing_count` becomes either **0** or **1** per key.

If NOT provided:

* Coverage is computed against the chosen subset (see `language_is_active`).

✅ UI rule:

* Dropdown includes `All` at top.
* If user selects **All** → do **NOT** send `language_id`.
* If user selects a language → send `language_id = <id>`.

---

### 7.2 `language_is_active` (optional + DEFAULT)

This controls whether the subset is:

* Active languages only
* All languages

**IMPORTANT DEFAULT RULE (as agreed):**

> If `language_is_active` is NOT sent → behave as **ALL languages**.

So:

| Sent `language_is_active` | Meaning                                 |
|--------------------------:|-----------------------------------------|
|                (not sent) | ALL languages                           |
|                       `1` | active-only (`languages.is_active = 1`) |
|                       `0` | ALL languages                           |

✅ UI recommended behavior:

* If “Active-only” toggle is ON → send `language_is_active = 1`
* If OFF → **omit** the filter entirely (preferred)
  *(you may send `0`, but omit is preferred)*

---

## 8) Example Requests

### A) Default (ALL languages)

```
POST /api/i18n/scopes/1/domains/2/keys/query
```

```json
{
  "page": 1,
  "per_page": 25
}
```

### B) Active-only subset

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "columns": {
      "language_is_active": 1
    }
  }
}
```

### C) Missing-only (within current subset)

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "columns": {
      "missing": 1
    }
  }
}
```

### D) Single-language coverage

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "columns": {
      "language_id": 2
    }
  }
}
```

---

## 9) Response Model

```json
{
  "data": [
    {
      "id": 101,
      "key_part": "login.title",
      "description": "Login title",
      "total_languages": 12,
      "missing_count": 3
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 25,
    "total": 200,
    "filtered": 40
  }
}
```

Notes:

* `id` is the key id (`i18n_keys.id`)
* `total_languages` depends on `language_id` + `language_is_active`
* `missing_count` depends on the same rules

---

## 10) Errors / Failures

### 10.1 Validation Error (Schema)

```
422 Unprocessable Entity
```

```json
{
  "success": false,
  "errors": {
    "page": ["INVALID_INT"]
  }
}
```

### 10.2 Domain-Scope Violation

If domain is not assigned to scope:

* Controller throws `DomainScopeViolationException`
* Response format depends on global exception handler
* UI should treat as **hard failure** (stop rendering table + show error)

---

## 11) Languages Dropdown Contract

### 11.1 Preferred (Recommended): Inject from UI Controller

**Reason**

* No extra request on page load
* Keeps UI consistent with server rules (e.g., exclude languages without settings)

Twig receives:

* `languages: array<LanguageOption>`

UI renders:

1. `All Languages` option (`value=""`)
2. injected language options

✅ Selecting `All` → omit `language_id` in query body.

---

### 11.2 Alternative (Allowed): Load via Dropdown API

Only if languages are NOT injected.

**Endpoint:**

```
POST /api/languages/dropdown
```

UI renders:

1. `All Languages`
2. returned list

Same rule:

* All → omit `language_id`

---

## 12) Frontend JS Injection Contract (Must exist)

The Twig page MUST inject:

* `scope_id`
* `domain_id`
* (optional) `languages` data if injected
* capabilities flags

Example:

```html
<script>
  window.i18nScopeDomainKeysContext = {
    scope_id: {{ scope.id }},
    domain_id: {{ domain.id }},
    scope_code: "{{ scope.code }}",
    domain_code: "{{ domain.code }}",
    languages: {{ languages|json_encode|raw }},
    capabilities: {
      can_view_i18n_keys: {{ capabilities.can_view_i18n_keys ? 'true' : 'false' }}
    }
  };
</script>
```

---

## 13) Implementation Checklist (Binding)

* [ ] Read `scope_id` & `domain_id` from route only
* [ ] Inject `scope` + `domain` models into Twig
* [ ] Inject JS context (scope/domain ids + codes)
* [ ] Render Language dropdown:

    * [ ] All first (`value=""`)
    * [ ] if All selected → omit `language_id`
* [ ] Active-only toggle:

    * [ ] ON → send `language_is_active = 1`
    * [ ] OFF → omit `language_is_active`
* [ ] Missing-only toggle → send `missing = 1`
* [ ] Never send `scope_id` or `domain_id` in body
* [ ] Never call UI route via fetch
* [ ] Page is REPORT-ONLY (no upsert/delete)

---

## 14) IMPORTANT Naming & Endpoint Safety Note (Avoid calling wrong endpoint)

This page is **Keys Coverage**, not “Translations”.

Use ONLY:

* UI Page:

    * `GET /i18n/scopes/{scope_id}/domains/{domain_id}/keys`
* Query API:

    * `POST /api/i18n/scopes/{scope_id}/domains/{domain_id}/keys/query`

**Never call:**

* `/.../translations/query` for this page
  because that implies “values editing”, not keys coverage.

**Code naming must reflect the contract:**

* UI route name: `i18n.scopes.domains.keys.ui`
* API route name: `i18n.scopes.domains.keys.query.api`
* Controller: `I18nScopeDomainKeysQueryController`
* Reader/Repository: `...KeysCoverageQueryReader...` (not Translations)

If code still uses `...Translations...` for keys coverage → treat as **technical debt** and rename to avoid future confusion.

---

## 15) “Don’t call endpoint while you already have it” (Practical note)

* UI route already provides **scope/domain context** + (recommended) **languages list**.
* Therefore, JS should **not** call extra endpoints to re-resolve scope/domain or rebuild context.
* Only the table query API is required for runtime rows.

---
