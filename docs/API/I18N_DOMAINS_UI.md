# 🌍 I18n Domains Management — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / I18n`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is a **runtime integration contract** for the I18n Domains UI.

It answers, precisely:

*   What the UI is allowed to send
*   How global search and filters actually work
*   What each endpoint requires vs what is optional
*   What response shapes exist (success + failure)
*   Why you are getting `422` / runtime exceptions

If something is not documented here, treat it as **not supported**.

### ⚠️ CRITICAL: UI vs API Distinction

You must understand the difference between the **UI Page** and the **API**:

*   **`GET /i18n/domains`**
    *   ❌ **This is NOT an API.**
    *   ✅ This is the **browser entry point** that renders the HTML page.
    *   It returns `text/html`.
    *   Do not call this from JavaScript fetch/axios.

*   **`POST /api/i18n/domains/*`**
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
  ├─ renders domains page
  └─ includes JS bundle

JavaScript
  ├─ DataTable (query + pagination)
  ├─ Modals (create, update metadata)
  └─ Actions (toggle active, change code, update sort)

API (authoritative)
  ├─ validates request schema
  ├─ applies query resolver rules
  └─ returns canonical envelope (queries) or empty 200 (actions)
```

---

## 2) Capabilities (Authorization Contract)

The UI receives these **Domains-specific capability flags**:

### 2.1 Injected Flags

```php
$capabilities = [
    'can_create'        => $hasPermission('i18n.domains.create'),
    'can_update'        => $hasPermission('i18n.domains.update'),
    'can_change_code'   => $hasPermission('i18n.domains.change_code'),
    'can_set_active'    => $hasPermission('i18n.domains.set_active'),
    'can_update_sort'   => $hasPermission('i18n.domains.update_sort'),
    'can_update_meta'   => $hasPermission('i18n.domains.update_metadata'),
];
```

### 2.2 Capability → UI Behavior Mapping

| Capability        | UI Responsibility                       |
|-------------------|-----------------------------------------|
| `can_create`      | Show/hide **Create Domain** button      |
| `can_update`      | Reserved / General update permission    |
| `can_change_code` | Enable/disable **update code** UI       |
| `can_set_active`  | Enable/disable **active toggle**        |
| `can_update_sort` | Enable/disable **sort order** controls  |
| `can_update_meta` | Enable/disable **update metadata** UI   |

---

## 3) List Domains (table)

**Endpoint:** `POST /api/i18n/domains/query`
**Capability:** Available by default for authenticated admins.

### Request — Specifics

*   **Global Search:** Free-text search applied on top of column filters. Matches against **code OR name OR description**.
*   **Sorting:** ⚠️ **SERVER-CONTROLLED**.
    *   Clients **MUST NOT** send `sort` parameters.

**Example Request:**

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "auth",
    "columns": {
      "is_active": "1"
    }
  }
}
```

### Supported Column Filters (`search.columns`)

| Alias       | Type   | Example   | Semantics         |
|-------------|--------|-----------|-------------------|
| `id`        | string | `"1"`     | exact match       |
| `code`      | string | `"auth"`  | exact match       |
| `name`      | string | `"Auth"`  | exact match       |
| `is_active` | string | `"1"`     | cast to int (1/0) |

### Response Model

```json
{
  "data": [
    {
      "id": 1,
      "code": "auth",
      "name": "Authentication",
      "description": "Auth related keys",
      "is_active": 1,
      "sort_order": 1
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

## 4) Create Domain

**Endpoint:** `POST /api/i18n/domains/create`
**Capability:** `can_create`

### Request Body

*   `code` (string, required, 1-50 chars)
*   `name` (string, required, 1-100 chars)
*   `description` (string, optional, 0-255 chars)
*   `is_active` (bool, optional, defaults to true)

> **Note:** `sort_order` is NOT accepted. New domains are appended.

---

## 5) Change Domain Code

**Endpoint:** `POST /api/i18n/domains/change-code`
**Capability:** `can_change_code`

### Request Body

*   `id` (int, required)
*   `new_code` (string, required, 1-50 chars)

---

## 6) Toggle Active

**Endpoint:** `POST /api/i18n/domains/set-active`
**Capability:** `can_set_active`

### Request Body

*   `id` (int, required)
*   `is_active` (bool, required)

---

## 7) Update Sort Order

**Endpoint:** `POST /api/i18n/domains/update-sort`
**Capability:** `can_update_sort`

### Request Body

*   `id` (int, required)
*   `position` (int, min 0, required)

> This is the **ONLY** way to change the sort order.

---

## 8) Update Metadata

**Endpoint:** `POST /api/i18n/domains/update-metadata`
**Capability:** `can_update_meta`

### Request Body

*   `id` (int, required)
*   `name` (string, optional, 1-100 chars)
*   `description` (string, optional, 0-255 chars)

> **Validation Rule:** You MUST provide at least one of `name` or `description`.

---

## 9) Implementation Checklist (Domains Specific)

*   [ ] **Never send `sort`** to `/api/i18n/domains/query`.
*   [ ] Refresh list after `update-sort` (transactional shift).
*   [ ] Handle `update-metadata` requirements (at least one field).
