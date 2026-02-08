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

```js
window.i18nScopesCapabilities = {
  can_create,
  can_update,
  can_change_code,
  can_set_active,
  can_update_sort,
  can_update_meta
};
```

### 2.2 Capability → UI Behavior Mapping

| Capability        | UI Responsibility                                      |
|-------------------|--------------------------------------------------------|
| `can_create`      | Show/hide **Create Scope** button                      |
| `can_update`      | Enable/disable **update** controls generally           |
| `can_change_code` | Enable/disable **change code** action                  |
| `can_set_active`  | Enable/disable **active toggle**                       |
| `can_update_sort` | Enable/disable **sort order** controls                 |
| `can_update_meta` | Enable/disable **update name/description** UI          |

---

## 3) List Scopes (table)

**Endpoint:** `POST /api/i18n/scopes/query`
**Capability:** Available by default for authenticated admins.

### Request — Specifics

*   **Global Search:** Free-text search applied on top of column filters. Matches against **code OR name OR description** (`LIKE %value%`).
*   **Sorting:** ⚠️ **SERVER-CONTROLLED**.
    *   `sort_order ASC, code ASC, id ASC`
    *   Clients **MUST NOT** send `sort` parameters.

**Example Request:**

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "app",
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
| `code`      | string | `"app"` | exact match       |
| `name`      | string | `"App"` | exact match       |
| `is_active` | string | `"1"`   | cast to int (1/0) |

### Response Model

**Success Response:**

```json
{
  "data": [
    {
      "id": 1,
      "code": "app.core",
      "name": "Application Core",
      "description": "Core application strings",
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

**Example Error Response:**

```json
{
  "error": "validation_error",
  "errors": {
    "page": "must be an integer"
  }
}
```

---

## 4) Create Scope

**Endpoint:** `POST /api/i18n/scopes/create`
**Capability:** `can_create`

### Request Body

| Field         | Type    | Required | Constraints        |
|---------------|---------|----------|--------------------|
| `code`        | string  | YES      | 1-50 chars         |
| `name`        | string  | YES      | 1-100 chars        |
| `description` | string  | NO       | 0-255 chars        |
| `is_active`   | boolean | NO       | defaults to `true` |

**Example Request:**

```json
{
  "code": "users.profile",
  "name": "User Profile",
  "description": "Strings related to user profile page",
  "is_active": true
}
```

**Success Response:**

```json
{
  "id": 123
}
```

**Example Error Response:**

```json
{
  "error": "validation_error",
  "errors": {
    "code": "must be between 1 and 50 characters"
  }
}
```

---

## 5) Change Scope Code

**Endpoint:** `POST /api/i18n/scopes/change-code`
**Capability:** `can_change_code`

### Request Body

| Field      | Type    | Required | Constraints |
|------------|---------|----------|-------------|
| `id`       | integer | YES      | min 1       |
| `new_code` | string  | YES      | 1-50 chars  |

**Example Request:**

```json
{
  "id": 123,
  "new_code": "users.settings"
}
```

**Success Response:**

```json
{
  "status": "ok"
}
```

**Example Error Response:**

```json
{
  "error": "entity_already_exists",
  "message": "Scope with code 'users.settings' already exists"
}
```

---

## 6) Set Active Status

**Endpoint:** `POST /api/i18n/scopes/set-active`
**Capability:** `can_set_active`

### Request Body

| Field       | Type    | Required | Constraints |
|-------------|---------|----------|-------------|
| `id`        | integer | YES      | min 1       |
| `is_active` | boolean | YES      | strict bool |

**Example Request:**

```json
{
  "id": 123,
  "is_active": false
}
```

**Success Response:**

```json
{
  "status": "ok"
}
```

**Example Error Response:**

```json
{
  "error": "validation_error",
  "errors": {
    "is_active": "must be a boolean"
  }
}
```

---

## 7) Update Sort Order

**Endpoint:** `POST /api/i18n/scopes/update-sort`
**Capability:** `can_update_sort`

### Request Body

| Field      | Type    | Required | Constraints |
|------------|---------|----------|-------------|
| `id`       | integer | YES      | min 1       |
| `position` | integer | YES      | min 0       |

> Note: Field is `position`, not `sort_order`.

**Example Request:**

```json
{
  "id": 123,
  "position": 5
}
```

**Success Response:**

```json
{
  "status": "ok"
}
```

**Example Error Response:**

```json
{
  "error": "entity_not_found"
}
```

---

## 8) Update Metadata (Name/Description)

**Endpoint:** `POST /api/i18n/scopes/update-metadata`
**Capability:** `can_update_meta`

### Request Body

| Field         | Type    | Required | Constraints          |
|---------------|---------|----------|----------------------|
| `id`          | integer | YES      | min 1                |
| `name`        | string  | NO       | 1-100 chars          |
| `description` | string  | NO       | 0-255 chars          |

> **Runtime Rule:** You MUST provide at least one of `name` or `description`.

**Example Request:**

```json
{
  "id": 123,
  "name": "User Settings",
  "description": "Updated description for user settings"
}
```

**Success Response:**

```json
{
  "status": "ok"
}
```

**Example Error Response:**

```json
{
  "error": "invalid_operation",
  "message": "At least one field (name or description) must be provided"
}
```

---

## 9) Implementation Checklist (Scopes Specific)

*   [ ] **Never send `sort`** to `/api/i18n/scopes/query`.
*   [ ] Use `position` field when updating sort order.
*   [ ] Send `is_active` as strict boolean (true/false) in Create/SetActive.
*   [ ] Refresh list after any modification to reflect server-side ordering.

---

## 🔍 Audit Corrections Summary

*   **Verified:** All endpoints (`query`, `create`, `change-code`, `set-active`, `update-sort`, `update-metadata`) checked against controllers and schemas.
*   **Verified:** Boolean handling in `create` and `set-active` explicitly enforced via `is_bool` check in controllers.
*   **Verified:** Filter semantics (global search columns, exact matches) confirmed in `PdoI18nScopesQueryReader`.
*   **Verified:** Response shapes match controller outputs.
*   **No Corrections Needed:** The documentation was accurate to the implementation.
