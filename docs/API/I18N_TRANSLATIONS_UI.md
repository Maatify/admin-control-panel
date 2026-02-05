# 🌍 Translations (Values) — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / I18n`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is a **runtime integration contract** for the Translations (Values) UI.

It answers, precisely:

*   What the UI is allowed to send
*   How global search and filters actually work
*   What each endpoint requires vs what is optional
*   What response shapes exist (success + failure)
*   Why you are getting `422` / runtime exceptions

If something is not documented here, treat it as **not supported**.

### ⚠️ CRITICAL: UI vs API Distinction

You must understand the difference between the **UI Page** and the **API**:

*   **`GET /i18n/translations`**
    *   ❌ **This is NOT an API.**
    *   ✅ This is the **browser entry point** that renders the HTML page.
    *   It returns `text/html`.
    *   Do not call this from JavaScript fetch/axios.

*   **`POST /api/i18n/translations/*`**
    *   ✅ **These ARE the APIs.**
    *   They return `application/json`.
    *   All programmatic interaction happens here.

> ⚠️ **RUNTIME RULES:**
> This document assumes **mandatory compliance** with the **[UI Runtime Integration Rules](UI_RUNTIME_RULES.md)**.
> Refer to that file for:
> *   Response parsing (JSON vs Empty Body)
> *   Error handling (422/403)
> *   Null handling in payloads
> *   Canonical Query construction

---

## 1) Page Architecture

```
Twig Controller
  ├─ injects capabilities
  ├─ renders translations list page
  └─ includes JS bundle

JavaScript
  ├─ DataTable (query + pagination)
  ├─ Modals (upsert)
  └─ Actions (delete)

API (authoritative)
  ├─ validates request schema
  ├─ applies query resolver rules
  └─ returns canonical envelope (queries) or {"status":"ok"} (actions)
```

---

## 2) Capabilities (Authorization Contract)

The UI receives these **Translations-specific capability flags**:

### 2.1 Injected Flags

```php
// Injected as 'capabilities' variable to Twig
$capabilities = [
    'can_upsert' => $hasPermission('i18n.translations.upsert.api'),
    'can_delete' => $hasPermission('i18n.translations.delete.api'),
];
```

### 2.2 Capability → UI Behavior Mapping

| Capability   | UI Responsibility                                      |
|--------------|--------------------------------------------------------|
| `can_upsert` | Enable/disable **edit value** functionality            |
| `can_delete` | Enable/disable **delete** (clear value) functionality  |

---

## 2.5) Language Context Selector (VERY IMPORTANT)

The Translations UI **does NOT invent** `language_id`.

It must always be obtained from the **Language Context Selector API**.

---

### 2.5.1 Selector Endpoint

**Endpoint:**
`POST /api/languages/select`

**Route name:**
`i18n.languages.select.api`

**Permission rule (resolved by PermissionMapperV2):**

Access is granted if the admin has **ANY** of:

* `i18n.languages.select`
* `i18n.translations.upsert`

> This allows the selector to be reused safely in:
>
> * Languages UI
> * Translations UI
> * Any future i18n-aware screen

---

### 2.5.2 What this endpoint returns

This endpoint returns **ONLY languages that are valid for UI context selection**.

A language is included **iff**:

* `languages.is_active = 1`
* A matching record exists in `language_settings`
* The language is correctly ordered via `language_settings.sort_order`

Languages **without settings are NEVER exposed to UI**.

---

### 2.5.3 Response Shape

```json
{
  "data": [
    {
      "id": 1,
      "code": "en",
      "name": "English",
      "direction": "ltr",
      "icon": "🇺🇸",
      "is_default": true
    },
    {
      "id": 2,
      "code": "ar",
      "name": "Arabic",
      "direction": "rtl",
      "icon": "🇪🇬",
      "is_default": false
    }
  ]
}
```

#### Field Semantics

| Field        | Meaning                                                       |
|--------------|---------------------------------------------------------------|
| `id`         | **Canonical language_id** to be sent to ALL translations APIs |
| `code`       | ISO / project language code (display only)                    |
| `name`       | Human-readable name                                           |
| `direction`  | UI layout direction (`ltr` / `rtl`)                           |
| `icon`       | Optional UI icon / flag                                       |
| `is_default` | `true` if this is the fallback (base) language                |

---

### 2.5.4 UI Rules (MANDATORY)

The frontend **MUST** obey the following rules:

* The language dropdown **MUST be populated exclusively** from `/api/languages/select`
* The UI **MUST NOT hardcode language IDs**
* The selected `language_id`:

    * MUST be sent in **every** `/api/i18n/translations/*` request
    * MUST be treated as the **current UI context**
* If the selector API returns an empty list:

    * The Translations UI **MUST NOT render**
    * This indicates a misconfigured system state

---

### 2.5.5 Relationship to Translations APIs

| API                                  | Requires `language_id` | Source of truth          |
|--------------------------------------|------------------------|--------------------------|
| `POST /api/i18n/translations/query`  | ✅ Yes                  | `/api/languages/select` |
| `POST /api/i18n/translations/upsert` | ✅ Yes                  | `/api/languages/select` |
| `POST /api/i18n/translations/delete` | ✅ Yes                  | `/api/languages/select` |

There is **NO fallback** and **NO default injection** on the API side.

If `language_id` is missing or invalid → **422 is expected behavior**.

---

## 3) List Translations (table)

**Endpoint:** `POST /api/i18n/translations/query`
**Capability:** Available by default for authenticated admins.

### Request Payload

| Field            | Type   | Required | Description                                          |
|------------------|--------|----------|------------------------------------------------------|
| `language_id`    | int    | **Yes**  | The language to query translations for. Must be > 0. |
| `page`           | int    | No       | Pagination page number (default: 1).                 |
| `per_page`       | int    | No       | Items per page (default: 25).                        |
| `search`         | object | No       | Search criteria wrapper.                             |
| `search.global`  | string | No       | Global search term (matches `key_name` OR `value`).  |
| `search.columns` | object | No       | Specific column filters.                             |

### Validation Rules
*   `language_id` MUST be an integer > 0.
*   `sort` parameter is NOT accepted (sorting is server-controlled).

### Supported Column Filters (`search.columns`)

| Alias      | Type   | Example   | Semantics         |
|------------|--------|-----------|-------------------|
| `id`       | string | `"101"`   | exact match       |
| `key_name` | string | `"auth."` | `LIKE %value%`    |
| `value`    | string | `"Login"` | `LIKE %value%`    |

**Example Request:**

```json
{
  "language_id": 1,
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "welcome",
    "columns": {
      "key_name": "menu"
    }
  }
}
```

### Response Model

```json
{
  "data": [
    {
      "key_id": 101,
      "key_name": "auth.login.title",
      "translation_id": 55,
      "language_id": 1,
      "value": "Login",
      "created_at": "2024-01-01 12:00:00",
      "updated_at": "2024-01-02 14:30:00"
    },
    {
      "key_id": 102,
      "key_name": "auth.login.btn",
      "translation_id": null,
      "language_id": 1,
      "value": null,
      "created_at": "2024-01-01 12:00:00",
      "updated_at": null
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

**Pagination Meanings:**
*   `total`: total records in DB (no filters)
*   `filtered`: total records after applying `search.global` and/or `search.columns`
*   When no filters are applied, `filtered` MAY equal `total`.

---

## 4) Upsert Translation

**Endpoint:** `POST /api/i18n/translations/upsert`
**Capability:** `can_upsert`

### Request Payload

| Field         | Type   | Required | Description                                 |
|---------------|--------|----------|---------------------------------------------|
| `language_id` | int    | **Yes**  | ID of the language.                         |
| `key_id`      | int    | **Yes**  | ID of the translation key.                  |
| `value`       | string | **Yes**  | The translation value. Can be empty string. |

### Validation Rules
*   `language_id` must be > 0.
*   `key_id` must be > 0.
*   `value` must be a string (even if empty).

**Example Request:**

```json
{
  "language_id": 1,
  "key_id": 101,
  "value": "Sign In"
}
```

### Success Response

*   **Status:** `200 OK`
*   **Body:** `{"status": "ok"}`

### Error Response Example (Validation)

*   **Status:** `422 Unprocessable Entity`

```json
{
  "success": false,
  "errors": {
    "value": ["REQUIRED_FIELD"]
  }
}
```

---

## 5) Delete Translation

**Endpoint:** `POST /api/i18n/translations/delete`
**Capability:** `can_delete`

### Purpose
Removes the translation value for the specific language, effectively reverting it to "untranslated".

### Request Payload

| Field         | Type | Required | Description                |
|---------------|------|----------|----------------------------|
| `language_id` | int  | **Yes**  | ID of the language.        |
| `key_id`      | int  | **Yes**  | ID of the translation key. |

### Validation Rules
*   `language_id` must be > 0.
*   `key_id` must be > 0.

**Example Request:**

```json
{
  "language_id": 1,
  "key_id": 101
}
```

### Success Response

*   **Status:** `200 OK`
*   **Body:** `{"status": "ok"}`

### Error Response Example (Validation)

*   **Status:** `422 Unprocessable Entity`

```json
{
  "success": false,
  "errors": {
    "key_id": ["REQUIRED_FIELD"]
  }
}
```

---

## 6) Implementation Checklist (Translations Specific)

*   [ ] **Always send `language_id`** in query payload.
*   [ ] Handle `translation_id` and `value` being `null` (untranslated state).
*   [ ] Handle **200 OK {"status": "ok"}** for write actions.
*   [ ] Refresh list (or update row) after `upsert` or `delete`.
*   [ ] **Never send `sort`** to `/api/i18n/translations/query`.
