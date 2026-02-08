# 🌍 Scopes Management — UI & API Integration Guide

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

| Capability        | UI Responsibility                                |
|-------------------|--------------------------------------------------|
| `can_create`      | Show/hide **Create Scope** button                |
| `can_update`      | Enable/disable **general update** actions        |
| `can_set_active`  | Enable/disable **active toggle**                 |
| `can_update_sort` | Enable/disable **sort order** controls           |
| `can_update_meta` | Enable/disable **update metadata** UI            |

---

## 3) List Scopes (table)

**Endpoint:** `POST /api/i18n/scopes/query`
**Capability:** Available by default for authenticated admins.

### Request Payload

| Field             | Type   | Required | Description                                      |
|-------------------|--------|----------|--------------------------------------------------|
| `page`            | int    | No       | Pagination page number (default: 1)              |
| `per_page`        | int    | No       | Records per page (default: 25, max: 100)         |
| `search.global`   | string | No       | Matches against **code OR name OR description**. |
| `search.columns`  | object | No       | Specific column filters (see table below).       |

### Validation Rules

*   **Sorting:** ⚠️ **SERVER-CONTROLLED** (`sort_order ASC, code ASC, id ASC`). Clients **MUST NOT** send `sort` parameters.
*   `search`: Must contain either `global` or `columns` if provided.

### Supported Column Filters (`search.columns`)

| Alias       | Type   | Example     | Semantics         |
|-------------|--------|-------------|-------------------|
| `id`        | string | `"1"`       | exact match       |
| `code`      | string | `"api"`     | exact match       |
| `name`      | string | `"API"`     | exact match       |
| `is_active` | string | `"1"`       | cast to int (1/0) |

### Success Response

```json
{
  "data": [
    {
      "id": 1,
      "code": "api",
      "name": "Backend API",
      "description": "Scopes for backend services",
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

### Example Request

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "backend",
    "columns": {
      "is_active": "1"
    }
  }
}
```

### Example Error Response (422 Validation)

```json
{
  "error": "Validation failed",
  "errors": {
    "sort": ["Invalid field"]
  }
}
```

---

## 4) Create Scope

**Endpoint:** `POST /api/i18n/scopes/create`
**Capability:** `can_create`

### Request Payload

| Field         | Type   | Required | Description                     |
|---------------|--------|----------|---------------------------------|
| `code`        | string | **Yes**  | Unique identifier (1-50 chars). |
| `name`        | string | **Yes**  | Display name (1-100 chars).     |
| `description` | string | **Yes**  | Detailed info (0-255 chars).    |
| `is_active`   | bool   | No       | Defaults to `true` (1).         |

> **Note:** `sort_order` is NOT accepted. New scopes are appended.

### Validation Rules

*   `code`: Must be unique.
*   `description`: Must be a string (can be empty), required key in payload.

### Success Response

```json
{
  "id": 123
}
```

### Example Request

```json
{
  "code": "mobile",
  "name": "Mobile App",
  "description": "Scopes for iOS and Android",
  "is_active": true
}
```

### Example Error Response (409 Conflict)

```json
{
  "error": "I18nScope with code 'mobile' already exists."
}
```

---

## 5) Change Scope Code

**Endpoint:** `POST /api/i18n/scopes/change-code`
**Capability:** `can_update` (implied)

### Request Payload

| Field      | Type   | Required | Description                  |
|------------|--------|----------|------------------------------|
| `id`       | int    | **Yes**  | Scope ID.                    |
| `new_code` | string | **Yes**  | New unique code (1-50 chars).|

> **Note:** This is the **ONLY** way to change the code.

### Validation Rules

*   `id`: Must exist.
*   `new_code`: Must be unique.
*   **Safety:** Cannot change code if it is currently in use by domains or translations.

### Success Response

```json
{
  "status": "ok"
}
```

### Example Request

```json
{
  "id": 1,
  "new_code": "mobile_v2"
}
```

### Example Error Response (409 In Use)

```json
{
  "error": "I18nScope code 'mobile' is in use by domains or translations."
}
```

---

## 6) Update Sort Order

**Endpoint:** `POST /api/i18n/scopes/update-sort`
**Capability:** `can_update_sort`

### Request Payload

| Field      | Type | Required | Description             |
|------------|------|----------|-------------------------|
| `id`       | int  | **Yes**  | Scope ID.               |
| `position` | int  | **Yes**  | New position (min 0).   |

### Validation Rules

*   `id`: Must exist.
*   `position`: Must be a non-negative integer.

### Success Response

```json
{
  "status": "ok"
}
```

### Example Request

```json
{
  "id": 1,
  "position": 5
}
```

### Example Error Response (404 Not Found)

```json
{
  "error": "I18nScope with id 999 not found."
}
```

---

## 7) Update Metadata

**Endpoint:** `POST /api/i18n/scopes/update-metadata`
**Capability:** `can_update_meta`

### Request Payload

| Field         | Type   | Required | Description                                  |
|---------------|--------|----------|----------------------------------------------|
| `id`          | int    | **Yes**  | Scope ID.                                    |
| `name`        | string | No       | New display name (1-100 chars).              |
| `description` | string | No       | New description (0-255 chars).               |

> **Note:** At least one of `name` or `description` must be provided.

### Validation Rules

*   `id`: Must exist.
*   Must provide at least one optional field.

### Success Response

```json
{
  "status": "ok"
}
```

### Example Request

```json
{
  "id": 1,
  "name": "Mobile App V2",
  "description": "Updated scope description"
}
```

### Example Error Response (400 Bad Request)

```json
{
  "error": "At least one field (name or description) must be provided"
}
```

---

## 8) Toggle Active

**Endpoint:** `POST /api/i18n/scopes/set-active`
**Capability:** `can_set_active`

### Request Payload

| Field       | Type | Required | Description |
|-------------|------|----------|-------------|
| `id`        | int  | **Yes**  | Scope ID.   |
| `is_active` | bool | **Yes**  | New state.  |

### Validation Rules

*   `id`: Must exist.

### Success Response

```json
{
  "status": "ok"
}
```

### Example Request

```json
{
  "id": 1,
  "is_active": false
}
```

### Example Error Response (404 Not Found)

```json
{
  "error": "I18nScope with id 999 not found."
}
```

---

## 9) Implementation Checklist (Scopes Specific)

*   [ ] **Never send `sort`** to `/api/i18n/scopes/query`.
*   [ ] Refresh list after `update-sort`.
*   [ ] Handle `is_active` as `int` (0/1) in response but `bool` in request.
