# 🌍 Languages Management — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / I18n`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

> ⚠️ **RUNTIME RULES:**
> This document strictly follows the **[UI Runtime Integration Rules](UI_RUNTIME_RULES.md)**.
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

## 3) List Languages (table)

**Endpoint:** `POST /api/languages/query`
**Capability:** `can_query` (implicit/base)

### Request — Specifics

*   **Global Search:** Matches against **name OR code**.
*   **Sorting:** ⚠️ **SERVER-CONTROLLED**.
    *   `sort_order ASC, id ASC`
    *   Clients **MUST NOT** send `sort` parameters.

### Supported Column Filters (`search.columns`)

| Alias       | Type   | Example | Semantics         |
|-------------|--------|---------|-------------------|
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
  "pagination": { ... }
}
```

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
