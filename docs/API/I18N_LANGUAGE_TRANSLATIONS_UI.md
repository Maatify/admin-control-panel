# 🌍 Translations (Values) — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`  
**Module:** `AdminKernel / I18n`  
**Audience:** UI & Frontend Developers  
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is a **runtime integration contract** for the Translations (Values) UI.

It answers, precisely:

* What the UI is allowed to send
* How global search and filters actually work
* What each endpoint requires vs what is optional
* What response shapes exist (success + failure)
* Why you are getting `422` / runtime exceptions

If something is not documented here, treat it as **not supported**.

---

## ⚠️ CRITICAL: UI vs API Distinction

You must understand the difference between the **UI Page** and the **API**.

### 0.1 UI Route (HTML Page)

**Route:**

```

GET /languages/{language_id}/translations

```

Example:

```

GET /languages/1/translations

```

* ❌ This is NOT an API.
* ✅ This renders the Twig page.
* Returns `text/html`.
* Must NOT be called via fetch/axios.

This route:

* Injects:
  * `language` (view model)
  * `capabilities`
* Renders:
  * Breadcrumb
  * Language Overview Card
  * Filters
  * Table container
* Includes JS bundle.

---

## 1) Page Architecture

```

GET /languages/{id}/translations
↓
Twig Controller
├─ resolves language context (by ID)
├─ injects capabilities
├─ renders Translations page
└─ includes JS bundle

JavaScript
├─ DataTable (query + pagination)
├─ Modals (upsert)
└─ Actions (delete)

API (authoritative)
├─ validates request schema
├─ applies query resolver rules
└─ returns canonical envelope (queries) or {"status":"ok"} (actions)

````

---

## 2) Capabilities (Authorization Contract)

The UI receives these capability flags:

```php
$capabilities = [
    'can_upsert'         => $hasPermission('languages.translations.upsert'),
    'can_delete'         => $hasPermission('languages.translations.delete'),
    'can_view_languages' => $hasPermission('languages.list'),
];
````

---

### 2.1 Capability → UI Mapping

| Capability           | UI Responsibility                      |
|----------------------|----------------------------------------|
| `can_upsert`         | Enable edit value                      |
| `can_delete`         | Enable delete (clear value)            |
| `can_view_languages` | Enable breadcrumb link to `/languages` |

---

## 3) Language Context (MANDATORY)

This page is already bound to a **specific language** via route:

```
/languages/{language_id}/translations
```

Therefore:

* The `language_id` is derived from the route.
* It MUST be injected into JavaScript.
* It MUST NOT be sent inside the request body for write operations.
* It MUST be sent via route parameter only.

There is:

* ❌ No language selector on this page.
* ❌ No dynamic context switching.
* ❌ No fallback injection.

If `language_id` is invalid → controller must return 404.

---

# 4) List Translations (Table)

**Endpoint:**

```
POST /languages/{language_id}/translations/query
```

**Route Name:**

```
languages.translations.list.api
```

---

## 4.1 Request Payload

> `language_id` is NOT part of the body.
> It is resolved from the route.

| Field            | Type   | Required | Description                                          |
|------------------|--------|----------|------------------------------------------------------|
| `page`           | int    | No       | Default: 1                                           |
| `per_page`       | int    | No       | Default: 25                                          |
| `search`         | object | No       | Search wrapper                                       |
| `search.global`  | string | No       | Matches `scope` OR `domain` OR `key_part` OR `value` |
| `search.columns` | object | No       | Column filters                                       |

---

## 4.2 Supported Column Filters

| Alias      | Type   | Semantics      |
|------------|--------|----------------|
| `id`       | string | exact match    |
| `scope`    | string | `LIKE %value%` |
| `domain`   | string | `LIKE %value%` |
| `key_part` | string | `LIKE %value%` |
| `value`    | string | `LIKE %value%` |


---

## 4.3 Example Request

```
POST /languages/1/translations/query
```

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "auth",
    "columns": {
      "domain": "auth"
    }
  }
}
```

---

## 4.4 Response Model

```json
{
  "data": [
    {
      "key_id": 101,
      "scope": "ct",
      "domain": "auth",
      "key_part": "login.title",
      "translation_id": 55,
      "language_id": 1,
      "value": "Login",
      "created_at": "2024-01-01 12:00:00",
      "updated_at": "2024-01-02 14:30:00"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 25,
    "total": 10,
    "filtered": 3
  }
}
```

---

# 5) Upsert Translation

**Endpoint:**

```
POST /languages/{language_id}/translations/upsert
```

**Route Name:**

```
languages.translations.upsert.api
```

**Capability:** `can_upsert`

---

## 5.1 Request Payload

> `language_id` is resolved from the route.

| Field    | Type   | Required | Description                              |
|----------|--------|----------|------------------------------------------|
| `key_id` | int    | **Yes**  | ID of translation key                    |
| `value`  | string | **Yes**  | Translation value (must have length ≥ 1) |

---

## 5.2 Validation Rules

* `key_id` must be integer > 0.
* `value` must be string.
* `value` must have length ≥ 1.
* No max length restriction (DB type is TEXT).

---

## 5.3 Example

```
POST /languages/1/translations/upsert
```

```json
{
  "key_id": 101,
  "value": "Sign In"
}
```

---

## 5.4 Success Response

```
200 OK
{"status":"ok"}
```

---

## 5.5 Validation Error

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

# 6) Delete Translation

**Endpoint:**

```
POST /languages/{language_id}/translations/delete
```

**Route Name:**

```
languages.translations.delete.api
```

**Capability:** `can_delete`

---

## 6.1 Purpose

Removes translation value for the specific language.

---

## 6.2 Request

```
POST /languages/1/translations/delete
```

```json
{
  "key_id": 101
}
```

---

## 6.3 Validation Rules

* `key_id` must be integer > 0.
* `language_id` comes from route and must match page context.

---

## 6.4 Success

```
200 OK
{"status":"ok"}
```

---

# 7) Implementation Checklist (Updated)

* [ ] Extract `language_id` from route `/languages/{id}/translations`
* [ ] Inject `language_id` into JS
* [ ] Do NOT send `language_id` in body for upsert/delete
* [ ] Always use route-scoped endpoints
* [ ] Handle `translation_id` and `value` being `null`
* [ ] Refresh table after upsert/delete
* [ ] Never send `sort`
* [ ] Never call `/languages/{id}/translations` via fetch
* [ ] Never assume fallback language
