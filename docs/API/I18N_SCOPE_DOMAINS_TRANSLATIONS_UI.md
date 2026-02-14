# 🌍 Scope Domain Translations (Values Matrix) — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / I18n`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

# 0) Why this document exists

This file is a **runtime integration contract** for the Scope/Domain Translations UI.

It defines, precisely:

* What the UI is allowed to send
* How global search and column filters work
* What identifiers are required for inline mutations
* What response shapes exist (success + failure)
* What validation rules are enforced
* Why you may receive `422` or runtime validation errors
* How multi-language rows behave
* How read-scope differs from write-scope

If something is not documented here → treat it as **not supported**.

This document is self-contained.
It does not depend on any other integration guide.

---

# ⚠️ CRITICAL: UI vs API Distinction

You must understand the difference between:

* The **HTML Page Route**
* The **Query API**
* The **Mutation APIs**

They are not interchangeable.

---

## 0.1 UI Route (HTML Page)

```
GET /i18n/scopes/{scope_id}/domains/{domain_id}/translations
```

Example:

```
GET /i18n/scopes/1/domains/3/translations
```

This route:

* ❌ Is NOT an API
* ❌ Must NEVER be called via fetch/axios
* ✅ Returns `text/html`
* ✅ Renders Twig template
* ✅ Injects runtime context
* ✅ Injects capability flags
* ✅ Loads JavaScript bundle

If called as AJAX → behavior is undefined.

---

# 1) Page Architecture

```
GET /i18n/scopes/{scope_id}/domains/{domain_id}/translations
↓
Twig Controller
├─ Resolves scope context
├─ Resolves domain context
├─ Resolves languages list
├─ Injects capability flags
├─ Renders page
└─ Includes JS bundle

JavaScript
├─ DataTable (query + pagination)
├─ Inline edit handler
├─ Inline delete handler
└─ Capability-based rendering

API (Authoritative Layer)
├─ Schema validation
├─ Query resolver
├─ Pagination envelope
├─ Mutation validation
└─ Persistence layer
```

---

# 2) Runtime Context (Injected by Twig)

The page injects:

```js
window.i18nScopeDomainTranslationsContext = {
    scope_id: 1,
    domain_id: 3,
    scope_code: "ct",
    domain_code: "auth",
    languages: [...]
};
```

AND

```js
window.ScopeDomainTranslationsCapabilities = {
    can_upsert : true,
    can_delete : false
};
```

These values are authoritative.

The UI must never attempt to derive them independently.

---

# 3) Capability Contract

| Capability   | UI Responsibility         |
|--------------|---------------------------|
| `can_upsert` | Enable inline editing     |
| `can_delete` | Enable delete action      |
| `false`      | Disable action completely |

JavaScript must check:

```js
window.ScopeDomainTranslationsCapabilities
```

before rendering edit or delete controls.

The UI must never bypass these flags.

Authorization is server-enforced.

---

# 4) Data Model — Multi-Language Matrix

This page is a **multi-language matrix view**.

Each row returned by the query API represents:

```
(Key × Language)
```

NOT just a key.

---

## 4.1 Example Row

```json
{
  "id": 55,
  "key_id": 101,
  "key_part": "login.title",
  "description": "Login page title",
  "language_id": 1,
  "language_code": "en",
  "language_name": "English",
  "language_icon": "/flags/en.svg",
  "language_direction": "ltr",
  "value": "Login"
}
```

---

## 4.2 Field Semantics

| Field         | Meaning                        |
|---------------|--------------------------------|
| `id`          | translation_id (nullable)      |
| `key_id`      | translation key identifier     |
| `language_id` | language identifier            |
| `value`       | translation value (nullable)   |
| `id = null`   | translation does NOT exist yet |

If `id` is null → upsert will create a new record.

---

# 5) Query Endpoint

```
POST /i18n/scopes/{scope_id}/domains/{domain_id}/translations/query
```

Route name:

```
i18n.scopes.domains.translations.query.api
```

---

## 5.1 Request Payload

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "auth",
    "columns": {
      "key_part": "login",
      "language_id": 1,
      "value": "Sign"
    }
  }
}
```

---

## 5.2 Supported Column Filters

| Alias         | Behavior    |
|---------------|-------------|
| `key_id`      | exact match |
| `key_part`    | LIKE        |
| `language_id` | exact match |
| `value`       | LIKE        |

---

## 5.3 Global Search Matches

Global search matches:

* key_part
* description
* value
* language_code
* language_name

---

## 5.4 Query Response Envelope

```json
{
  "data": [ ... ],
  "pagination": {
    "page": 1,
    "per_page": 25,
    "total": 120,
    "filtered": 25
  }
}
```

Query endpoints ALWAYS return the canonical envelope.

---

# 6) Inline Upsert (Matrix Row)

⚠ IMPORTANT ARCHITECTURAL NOTE

Read scope:

```
Scope + Domain
```

Write scope:

```
Language
```

This split is intentional.

---

## Endpoint

```
POST /languages/{language_id}/translations/upsert
```

Route name:

```
languages.translations.upsert.api
```

Capability required:

```
can_upsert
```

---

## 6.1 Request Body

```json
{
  "key_id": 101,
  "value": "Sign In"
}
```

---

## 6.2 Rules

* `language_id` comes from route
* `key_id` comes from row
* `value` must be string length ≥ 1
* Scope/domain are NOT sent in body
* Server validates that key belongs to correct domain

---

## 6.3 Success

```
200 OK
{"status":"ok"}
```

---

## 6.4 Validation Error

```
422 Unprocessable Entity
```

```json
{
  "success": false,
  "errors": {
    "value": ["REQUIRED_FIELD"]
  }
}
```

---

# 7) Inline Delete (Matrix Row)

## Endpoint

```
POST /languages/{language_id}/translations/delete
```

Route name:

```
languages.translations.delete.api
```

Capability required:

```
can_delete
```

---

## 7.1 Request Body

```json
{
  "key_id": 101
}
```

---

## 7.2 Rules

* `language_id` comes from route
* `key_id` must be integer > 0
* Safe if translation does not exist

---

## 7.3 Success

```
200 OK
{"status":"ok"}
```

---

# 8) Inline Action Flow

### Edit Flow

1. User edits value cell
2. JS checks `can_upsert`
3. Send request to language-scoped endpoint
4. On success:

  * Update row value
  * Refresh table OR patch row

---

### Delete Flow

1. User clicks delete
2. JS checks `can_delete`
3. Send request
4. On success:

  * Set `value = null`
  * Set `id = null`
  * Refresh table OR patch row

---

# 9) Hard Rules

❌ Never send `scope_id` in mutation body
❌ Never send `domain_id` in mutation body
❌ Never bypass capability flags
❌ Never assume fallback language
❌ Never call HTML route via fetch
❌ Never alter response envelope format

---

# 10) Validation & Error Model

Mutation endpoints may return:

### 400 — malformed request

### 401 — unauthorized

### 403 — forbidden

### 404 — invalid scope/domain/language

### 422 — validation failure

Query endpoints always return canonical envelope.

Mutation endpoints always return:

```
{"status":"ok"}
```

on success.

---

# 11) Implementation Checklist (Final)

* [ ] Extract `scope_id` + `domain_id` from route
* [ ] Inject runtime context into JS
* [ ] Inject capabilities into JS
* [ ] Render inline edit if `can_upsert`
* [ ] Render delete if `can_delete`
* [ ] Use row `key_id` for upsert/delete
* [ ] Use route-scoped `language_id` for mutation
* [ ] Support null value
* [ ] Refresh table after mutation
* [ ] Never send unsupported fields
* [ ] Never change response shape

---

# 12) Summary

This page:

* Is domain-scoped for reads
* Is language-scoped for writes
* Is multi-language matrix-based
* Supports inline upsert
* Supports inline delete
* Is permission-aware
* Is backend-authoritative
* Is schema-validated
* Is contract-locked

---
