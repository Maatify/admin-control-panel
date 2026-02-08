# 🌍 I18n Scopes — UI & API Integration Guide

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

> ⚠️ **OPTIONAL FIELD RULE:**
> Optional fields MUST be omitted entirely if not provided.
> UI MUST NOT send:
> - `null`
> - empty string `""`
> - empty object `{}`
>
> Sending optional fields as null or empty may result in validation errors (422).

---

## 1) Page Architecture

```
Twig Controller
  ├─ injects capabilities
  ├─ renders scopes page
  └─ includes JS bundle

JavaScript
  ├─ DataTable (query + pagination)
  ├─ Modals (create, update metadata, change code)
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

| Capability        | UI Responsibility                                      |
|-------------------|--------------------------------------------------------|
| `can_create`      | Show/hide **Create Scope** button                      |
| `can_update`      | Enable/disable **Change Code** action                  |
| `can_set_active`  | Enable/disable **active toggle**                       |
| `can_update_sort` | Enable/disable **sort order** controls                 |
| `can_update_meta` | Enable/disable **edit metadata** (name/desc)           |

---

## 3) List Scopes (table)

**Endpoint:** `POST /api/i18n/scopes/query`
**Capability:** Available by default for authenticated admins.

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
    "global": "frontend",
    "columns": {
      "is_active": "1"
    }
  }
}
```

### Supported Column Filters (`search.columns`)

| Alias       | Type   | Example | Semantics         |
|-------------|--------|---------|-------------------|
| `id`        | int    | `1`     | exact match       |
| `code`      | string | `"app"` | exact match       |
| `name`      | string | `"App"` | exact match       |
| `is_active` | string | `"1"`   | cast to int (1/0) |

### Response Model

```json
{
  "data": [
    {
      "id": 1,
      "code": "app_frontend",
      "name": "Frontend Application",
      "description": "Main client application scope",
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

### Example Error Response

```json
{
  "status": "error",
  "error_code": "INVALID_PAGINATION",
  "message": "Page must be >= 1"
}
```

---

## 4) Create Scope

**Endpoint:** `POST /api/i18n/scopes/create`
**Capability:** `can_create`

### Request Body

*   `code` (string, required, max 50)
*   `name` (string, required, max 100)
*   `description` (string, optional, max 255)
*   `is_active` (bool, optional, default: true)

> **Note:** `sort_order` is NOT accepted. New scopes are appended.

**Example Request:**

```json
{
  "code": "new_scope",
  "name": "New Scope",
  "description": "A new scope for testing",
  "is_active": 1
}
```

**Example Success Response:**

```json
{
  "id": 12
}
```

**Example Error Response:**

```json
{
  "status": "error",
  "error_code": "ENTITY_ALREADY_EXISTS",
  "message": "Scope code 'new_scope' already exists",
  "details": {
      "field": "code"
  }
}
```

---

## 5) Change Scope Code

**Endpoint:** `POST /api/i18n/scopes/change-code`
**Capability:** `can_update`

### Request Body

*   `id` (int, required)
*   `new_code` (string, required, max 50)

**Example Request:**

```json
{
  "id": 12,
  "new_code": "renamed_scope"
}
```

**Example Success Response:**

```json
{
  "status": "ok"
}
```

**Example Error Response:**

```json
{
  "status": "error",
  "error_code": "ENTITY_IN_USE",
  "message": "Cannot rename scope: Code is currently in use by domains or translations"
}
```

---

## 6) Toggle Active

**Endpoint:** `POST /api/i18n/scopes/{id}/set-active`
**Capability:** `can_set_active`

### ⚠️ IMPORTANT: ID Requirement
Although the ID is present in the URL path, the **Request Body MUST also contain the ID**.

### Request Body

*   `id` (int, required)
*   `is_active` (bool, required)

**Example Request:**

```json
{
  "id": 12,
  "is_active": 0
}
```

**Example Success Response:**

```json
{
  "status": "ok"
}
```

**Example Error Response:**

```json
{
  "status": "error",
  "error_code": "VALIDATION_ERROR",
  "message": "Field 'id' is required",
  "details": {
      "id": "REQUIRED_FIELD"
  }
}
```

---

## 7) Update Sort Order

**Endpoint:** `POST /api/i18n/scopes/update-sort`
**Capability:** `can_update_sort`

### Request Body

*   `id` (int, required)
*   `position` (int, required, min 0)

> This is the **ONLY** way to change the sort order.

**Example Request:**

```json
{
  "id": 12,
  "position": 5
}
```

**Example Success Response:**

```json
{
  "status": "ok"
}
```

**Example Error Response:**

```json
{
  "status": "error",
  "error_code": "ENTITY_NOT_FOUND",
  "message": "I18nScope 12 not found"
}
```

---

## 8) Update Metadata

**Endpoint:** `POST /api/i18n/scopes/update-metadata`
**Capability:** `can_update_meta`

### Request Body

*   `id` (int, required)
*   `name` (string, optional, max 100)
*   `description` (string, optional, max 255)

> **Constraint:** You MUST provide at least one of `name` or `description`.

**Example Request:**

```json
{
  "id": 12,
  "name": "Updated Name",
  "description": "Updated description text"
}
```

**Example Success Response:**

```json
{
  "status": "ok"
}
```

**Example Error Response:**

```json
{
  "status": "error",
  "error_code": "INVALID_OPERATION",
  "message": "At least one field (name or description) must be provided"
}
```

---

## 9) Implementation Checklist (Scopes Specific)

*   [ ] **Never send `sort`** to `/api/i18n/scopes/query`.
*   [ ] **Include `id` in the body** for `set-active` endpoint even if it is in the URL.
*   [ ] Refresh list after `update-sort` (transactional shift).
*   [ ] Ensure `change-code` is only available if `can_update` is true.
