# 🌍 Languages Management — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / I18n`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is a **runtime integration contract** for the Languages UI.

It answers, precisely:

*   What the UI is allowed to send
*   How global search and filters actually work
*   What each endpoint requires vs what is optional
*   What response shapes exist (success + failure)
*   Why you are getting `422` / runtime exceptions

If something is not documented here, treat it as **not supported**.

### ⚠️ CRITICAL: UI vs API Distinction

You must understand the difference between the **UI Page** and the **API**:

*   **`GET /languages`**
    *   ❌ **This is NOT an API.**
    *   ✅ This is the **browser entry point** that renders the HTML page.
    *   It returns `text/html`.
    *   Do not call this from JavaScript fetch/axios.

*   **`POST /api/languages/*`**
    *   ✅ **These ARE the APIs.**
    *   They return `application/json` (or empty 200).
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
  ├─ renders languages page
  └─ includes JS bundle

JavaScript
  ├─ DataTable (query + pagination)
  ├─ Modals (create, update settings)
  └─ Actions (toggle active, fallback, update name/code/sort)

API (authoritative)
  ├─ validates request schema
  ├─ applies query resolver rules
  └─ returns canonical envelope (queries) or empty 200 (actions)
```

---

## 2) Capabilities (Authorization Contract)

The UI receives these **Languages-specific capability flags**:

### 2.1 Injected Flags

```php
$capabilities = [
    'can_create'         => $hasPermission('languages.create.api'),
    'can_update'         => $hasPermission('languages.update.settings.api'),
    'can_update_name'    => $hasPermission('languages.update.name.api'),
    'can_update_code'    => $hasPermission('languages.update.code.api'),
    'can_update_sort'    => $hasPermission('languages.update.sort.api'),
    'can_active'         => $hasPermission('languages.set.active.api'),
    'can_fallback_set'   => $hasPermission('languages.set.fallback.api'),
    'can_fallback_clear' => $hasPermission('languages.clear.fallback.api'),
];
```

### 2.2 Capability → UI Behavior Mapping

| Capability           | UI Responsibility                                      |
|----------------------|--------------------------------------------------------|
| `can_create`         | Show/hide **Create Language** button                   |
| `can_update`         | Enable/disable **settings edit** (direction/icon only) |
| `can_update_name`    | Enable/disable **update name** UI                      |
| `can_update_code`    | Enable/disable **update code** UI                      |
| `can_update_sort`    | Enable/disable **sort order** controls                 |
| `can_active`         | Enable/disable **active toggle**                       |
| `can_fallback_set`   | Allow selecting fallback                               |
| `can_fallback_clear` | Allow clearing fallback                                |

---

## 2.3) Language Selector API (Shared Infrastructure)

Although the Languages UI primarily operates on **row-level language entities**,
the system exposes a shared **Language Selector API** used by other modules.

### Endpoint

`POST /languages/dropdown`

### Purpose

This endpoint exists to provide a **safe, ordered list of active languages** for:

* Translations UI
* Any future i18n-aware modules
* Cross-module language context selection

### Important Notes

* The **Languages Management UI does NOT depend on this endpoint**
* Languages listed in the table may include:

    * inactive languages
    * languages without settings (visible for management)
* `/languages/dropdown` returns **ONLY languages valid for UI context usage**

> ⚠️ Do NOT use `/languages/dropdown` to populate the Languages table.
> It is intentionally restrictive.

### Authorization

Access is granted if the admin has **any of**:

* `i18n.translations.upsert`
* `i18n.languages.dropdown`

This is enforced centrally via `PermissionMapperV2`.

---

## 3) List Languages (table)

**Endpoint:** `POST /api/languages/query`
**Capability:** Available by default for authenticated admins.

### Request — Specifics

*   **Global Search:** Free-text search applied on top of column filters. Matches against **name OR code**.
*   **Sorting:** ⚠️ **SERVER-CONTROLLED**.
    *   `sort_order ASC, id ASC`
    *   Clients **MUST NOT** send `sort` parameters.

**Example Request:**

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "en",
    "columns": {
      "is_active": "1"
    }
  }
}
```

### Supported Column Filters (`search.columns`)

| Alias       | Type   | Example | Semantics         |
|-------------|--------|---------|-------------------|
| `id`        | string | `"1"`   | exact match       |
| `name`      | string | `"Eng"` | `LIKE %value%`    |
| `code`      | string | `"en"`  | exact match       |
| `is_active` | string | `"1"`   | cast to int (1/0) |
| `direction` | string | `"ltr"` | `ltr` / `rtl`     |

### Response Model

```json
{
  "data": [
    {
      "id": 1,
      "name": "English",
      "code": "en",
      "direction": "ltr",
      "sort_order": 1,
      "icon": "🇬🇧",
      "is_active": true,
      "fallback_language_id": 2
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

## 4) Create Language

**Endpoint:** `POST /api/languages/create`
**Capability:** `can_create`

### Request Body

*   `name` (string, required)
*   `code` (string, required)
*   `direction` (`"ltr" | "rtl"`, required)
*   `icon` (string, optional)
*   `is_active` (bool, optional)
*   `fallback_language_id` (int, optional)

> **Note:** `sort_order` is NOT accepted. New languages are appended.

---

## 5) Update Language Settings

**Endpoint:** `POST /api/languages/update-settings`
**Capability:** `can_update`

### Request Body

*   `language_id` (int, required)
*   `direction` (`"ltr" | "rtl"`, optional)
*   `icon` (string, optional)

> **Icon Logic:** Send empty string `""` to clear the icon.

---

## 6) Update Language Sort Order

**Endpoint:** `POST /api/languages/update-sort`
**Capability:** `can_update_sort`

### Request Body

*   `language_id` (int, required)
*   `sort_order` (int, min 1, required)

> This is the **ONLY** way to change the sort order.

---

## 7) Update Language Name

**Endpoint:** `POST /api/languages/update-name`
**Capability:** `can_update_name`

### Request Body

*   `language_id` (int, required)
*   `name` (string, required)

---

## 8) Update Language Code

**Endpoint:** `POST /api/languages/update-code`
**Capability:** `can_update_code`

### Request Body

*   `language_id` (int, required)
*   `code` (string, required)

---

## 9) Toggle Active

**Endpoint:** `POST /api/languages/set-active`
**Capability:** `can_active`

### Request Body

*   `language_id` (int, required)
*   `is_active` (bool, required)

---

## 10) Set Fallback

**Endpoint:** `POST /api/languages/set-fallback`
**Capability:** `can_fallback_set`

### Logic
*   Only ONE fallback language exists.
*   Setting one automatically unsets the previous.

### Request Body

*   `language_id` (int, required)
*   `fallback_language_id` (int, optional)

---

## 11) Clear Fallback

**Endpoint:** `POST /api/languages/clear-fallback`
**Capability:** `can_fallback_clear`

### Request Body

*   `language_id` (int, required)

---

## 12) Implementation Checklist (Languages Specific)

*   [ ] **Never send `sort`** to `/api/languages/query`.
*   [ ] Handle icon clearing by sending `""`.
*   [ ] Refresh list after `update-sort` (transactional shift).
