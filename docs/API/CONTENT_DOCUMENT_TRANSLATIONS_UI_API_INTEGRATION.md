# 📄 Content Document Translations — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / ContentDocuments`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

# 0) Why this document exists

This file is a **runtime integration contract** for the Content Document Translations UI.

It defines, precisely:

* What the UI is allowed to send
* How language-based filtering works
* What identifiers are required
* What response shapes exist (success + failure)
* What validation rules are enforced
* Why you may receive `422` or runtime validation errors
* How translation existence is represented
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

# 0.1 UI Route (HTML Page)

```
GET /content-document-types/{type_id}/documents/{document_id}/translations
```

Example:

```
GET /content-document-types/2/documents/15/translations
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
GET /content-document-types/{type_id}/documents/{document_id}/translations
↓
Twig Controller
├─ Resolves document type
├─ Resolves document (version)
├─ Resolves languages list
├─ Injects capability flags
├─ Renders page
└─ Includes JS bundle

JavaScript
├─ DataTable (query + pagination)
├─ View/Edit button logic
├─ Filter handling
└─ Capability-based rendering

API (Authoritative Layer)
├─ Schema validation
├─ List filter resolver
├─ Pagination envelope
└─ Reader execution
```

---

# 2) Runtime Context (Injected by Twig)

The page injects:

```js
window.contentDocumentTranslationsContext = {
    type_id: 2,
    document_id: 15,
    type_key: "terms",
    version: 3
};
```

AND

```js
window.contentDocumentTranslationsCapabilities = {
    can_view_types: true,
    can_view_versions: true,
    can_update: true
};
```

These values are authoritative.

The UI must never attempt to derive them independently.

---

# 🔤 2.1 Languages List — Runtime Contract (Authoritative)

The Twig controller injects the full language list into the page.

```js
window.contentDocumentTranslationsContext.languages = [
    {
        id: 1,
        code: "en",
        name: "English",
        direction: "ltr",
        icon: "/flags/en.svg",
        is_default: true
    }
]
```

---

## 🔹 Source of Truth

Languages are resolved from:

* `LanguageRepositoryInterface`
* `LanguageSettingsRepositoryInterface`

Only languages that:

* Have valid language settings
* Are active in the system

are injected.

Languages without settings are excluded.

---

## 🔹 Important Rules

1. This list is **NOT paginated**.
2. This list is **authoritative**.
3. UI must NOT fetch languages separately.
4. UI must NOT derive languages from query results.
5. If a language is not in this list → it does not exist for this page.
6. `language_id` filter must use values from this list only.

---

## 🔹 Why This List Exists

The list page query returns:

```
(Document × Language)
```

But the language dropdown:

* Must be populated before first query
* Must not depend on data table load
* Must reflect full system language configuration

Therefore:

> Languages list is injected at page render time.
> It is NOT retrieved via API.

---

## 🔹 UI Responsibilities

* Populate language dropdown from injected list
* Never assume language direction
* Never assume fallback language
* Never hide languages based on `has_translation`
* Allow filtering by language_id even if translation doesn't exist

---

## 🔹 Relationship to Query Endpoint

The query endpoint joins against `languages` table.

But the injected list:

* Is for UI controls
* Not for authoritative filtering

Backend still validates everything.

---

# 3) Capability Contract

| Capability   | UI Responsibility     |
|--------------|-----------------------|
| `can_update` | Show View/Edit action |
| `false`      | Hide action entirely  |

JavaScript must check:

```js
window.contentDocumentTranslationsCapabilities
```

before rendering update controls.

Authorization is server-enforced.

---

# 4) Data Model — Language Translation View

This page is a **language-existence list view**.

Each row represents:

```
(Document × Language)
```

NOT translation content.

---

## 4.1 Example Row

```json
{
  "document_id": 15,
  "language_id": 1,
  "language_code": "en",
  "language_name": "English",
  "language_icon": "/flags/en.svg",
  "language_direction": "ltr",
  "has_translation": true,
  "translation_id": 33,
  "updated_at": "2026-02-23 18:30:00"
}
```

---

## 4.2 Field Semantics

| Field                 | Meaning                          |
|-----------------------|----------------------------------|
| `document_id`         | Current document identifier      |
| `language_id`         | Language identifier              |
| `has_translation`     | Whether translation exists       |
| `translation_id`      | Translation record id (nullable) |
| `translation_id=null` | Translation does NOT exist yet   |
| `updated_at`          | Last modification timestamp      |

If `has_translation = false` → UI should show "Create".

If `has_translation = true` → UI should show "Edit".

---

# 5) Query Endpoint

```
POST /content-document-types/{type_id}/documents/{document_id}/translations/query
```

Route name:

```
content_documents.translations.query.api
```

---

## 5.1 Request Payload

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "eng",
    "columns": {
      "language_id": 1,
      "has_translation": 1
    }
  }
}
```

---

## 5.2 Supported Column Filters

| Alias             | Behavior       |
|-------------------|----------------|
| `language_id`     | exact match    |
| `has_translation` | boolean filter |

---

## 5.3 Global Search Matches

Global search matches:

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
    "total": 8,
    "filtered": 4
  }
}
```

Query endpoints ALWAYS return the canonical envelope.

---

# 6) Translation Details (Full Edit Page)

⚠ IMPORTANT ARCHITECTURAL NOTE

Read scope:

```
Document (Version)
```

Write scope:

```
Specific Translation (by translation_id)
```

---

## UI Route

```
GET /content-document-types/{type_id}/documents/{document_id}/translations/{translation_id}
```

Route name:

```
content_documents.translations.details
```

Capability required:

```
can_update
```

---

# 7) Hard Rules

❌ Never send `type_id` in mutation body
❌ Never send `document_id` in mutation body
❌ Never bypass capability flags
❌ Never assume translation exists
❌ Never call HTML route via fetch
❌ Never alter response envelope format

---

# 8) Validation & Error Model

Query endpoint may return:

### 400 — malformed request

### 401 — unauthorized

### 403 — forbidden

### 404 — invalid document/type

### 422 — validation failure

Query endpoints always return canonical envelope.

Mutation endpoints (details page save) always return:

```
{"status":"ok"}
```

on success.

---

# 9) Inline Action Flow (List Page)

### View/Edit Flow

1. User clicks action button
2. JS checks `can_update`
3. Redirect to details page
4. Details page handles full upsert logic

---

# 10) Implementation Checklist (Final)

* [ ] Extract `type_id` + `document_id` from route
* [ ] Inject runtime context into JS
* [ ] Inject capabilities into JS
* [ ] Build DataTable with canonical envelope
* [ ] Support `language_id` filter
* [ ] Support `has_translation` filter
* [ ] Use `translation_id` for edit navigation
* [ ] Show Create if `has_translation = false`
* [ ] Never send unsupported fields
* [ ] Never change response shape

---

# 11) Summary

This page:

* Is document-scoped for reads
* Is translation-scoped for writes
* Is language-existence based
* Does NOT load full content in list
* Is permission-aware
* Is backend-authoritative
* Is schema-validated
* Is contract-locked

---
