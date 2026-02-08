# 🌍 I18n Scopes Management — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / I18n`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is a **runtime integration contract** for the I18n Scopes UI.

It answers, precisely:

*   What the UI is allowed to send
*   How global search and filters actually work
*   What each endpoint requires vs what is optional
*   What response shapes exist (success + failure)
*   Why you are getting `422` / runtime exceptions

If something is not documented here, treat it as **not supported**.

### ⚠️ CRITICAL: UI vs API Distinction

You must understand the difference between the **UI Page** and the **API**:

*   **`GET /i18n/scopes`**
    *   ❌ **This is NOT an API.**
    *   ✅ This is the **browser entry point** that renders the HTML page.
    *   It returns `text/html`.
    *   Do not call this from JavaScript fetch/axios.

*   **`POST /api/i18n/scopes/*`**
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
  ├─ renders scopes page
  └─ includes JS bundle

JavaScript
  ├─ DataTable (query + pagination)
  ├─ Modals (create, change code, update metadata)
  └─ Actions (toggle active, update sort)

API (authoritative)
  ├─ validates request schema
  ├─ applies query resolver rules
  └─ returns canonical envelope (queries) or empty 200 (actions)
```

---

## 2) Capabilities (Authorization Contract)

The UI receives these **Scopes-specific capability flags**:

### 2.1 Injected Flags

```php
$capabilities = [
    'can_create'      => $hasPermission('i18n.scopes.create'),
    'can_update'      => $hasPermission('i18n.scopes.update'),
    'can_set_active'  => $hasPermission('i18n.scopes.set_active'),
    'can_update_sort' => $hasPermission('i18n.scopes.update_sort'),
    'can_update_meta' => $hasPermission('i18n.scopes.update_metadata'),
];
```

### 2.2 Capability → UI Behavior Mapping

| Capability           | UI Responsibility                                      |
|----------------------|--------------------------------------------------------|
| `can_create`         | Show/hide **Create Scope** button                      |
| `can_update`         | Enable/disable **Change Code** action                  |
| `can_set_active`     | Enable/disable **active toggle**                       |
| `can_update_sort`    | Enable/disable **sort order** controls                 |
| `can_update_meta`    | Enable/disable **Edit Metadata** (name/desc) action    |

---

## 3) List Scopes (table)

**Endpoint:** `POST /api/i18n/scopes/query`
**Permission:** `i18n.scopes.list`

### Request — Specifics

*   **Global Search:** Free-text search applied on top of column filters. Matches against **code OR name OR description**.
*   **Sorting:** ⚠️ **SERVER-CONTROLLED**.
    *   `sort_order ASC, code ASC, id ASC`
    *   Clients **MUST NOT** send `sort` parameters.

**Example Request:**

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "account",
    "columns": {
      "is_active": "1"
    }
  }
}
```

### Supported Column Filters (`search.columns`)

| Alias       | Type   | Example     | Semantics         |
|-------------|--------|-------------|-------------------|
| `id`        | string | `"1"`       | exact match       |
| `code`      | string | `"account"` | exact match       |
| `name`      | string | `"Account"` | exact match       |
| `is_active` | string | `"1"`       | cast to int (1/0) |

### Response Model

```json
{
  "data": [
    {
      "id": 1,
      "code": "account",
      "name": "Account Management",
      "description": "Scopes related to user accounts",
      "is_active": 1,
      "sort_order": 10
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

## 4) Create Scope

**Endpoint:** `POST /api/i18n/scopes/create`
**Permission:** `i18n.scopes.create`

### Request Body

*   `code` (string, required, 1-50 chars)
*   `name` (string, required, 1-100 chars)
*   `description` (string, optional, 0-255 chars)
*   `is_active` (boolean, optional, default: true)
*   `sort_order` (int, optional, default: 0)

### Success Response

```json
{
  "id": 12
}
```

### Error Response (Code Exists)

```json
{
  "success": false,
  "error": {
    "code": "resource_already_exists",
    "message": "I18nScope with code 'account' already exists."
  }
}
```

---

## 5) Change Scope Code

**Endpoint:** `POST /api/i18n/scopes/change-code`
**Permission:** `i18n.scopes.change_code.api`

### Request Body

*   `id` (int, required)
*   `new_code` (string, required, 1-50 chars)

> **Note:** This operation will fail if the current code is in use by domains or translations, or if the new code already exists.

### Success Response

```json
{
  "status": "ok"
}
```

---

## 6) Update Metadata

**Endpoint:** `POST /api/i18n/scopes/update-metadata`
**Permission:** `i18n.scopes.update_metadata`

### Request Body

*   `id` (int, required)
*   `name` (string, optional, 1-100 chars)
*   `description` (string, optional, 0-255 chars)

> **Rule:** At least one of `name` or `description` must be provided.

### Success Response

```json
{
  "status": "ok"
}
```

---

## 7) Toggle Active

**Endpoint:** `POST /api/i18n/scopes/set-active`
**Permission:** `i18n.scopes.set_active`

### Request Body

*   `id` (int, required)
*   `is_active` (boolean, required)

### Success Response

```json
{
  "status": "ok"
}
```

---

## 8) Update Sort Order

**Endpoint:** `POST /api/i18n/scopes/update-sort`
**Permission:** `i18n.scopes.update_sort`

### Request Body

*   `id` (int, required)
*   `position` (int, required, min 0)

> This is the **ONLY** way to change the sort order. The field name is `position`, not `sort_order`.

### Success Response

```json
{
  "status": "ok"
}
```

---

## 9) Implementation Checklist (Scopes Specific)

*   [ ] **Never send `sort`** to `/api/i18n/scopes/query`.
*   [ ] Use `position` (not `sort_order`) when calling `/update-sort`.
*   [ ] Handle `409 Conflict` when changing code (if in use).
*   [ ] Refresh list after `update-sort` (transactional shift).
