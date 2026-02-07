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
  ├─ Modals (create, update metadata)
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

| Capability        | UI Responsibility                      |
|-------------------|----------------------------------------|
| `can_create`      | Show/hide **Create Scope** button      |
| `can_update`      | **General update permission** (unused) |
| `can_set_active`  | Enable/disable **active toggle**       |
| `can_update_sort` | Enable/disable **sort order** controls |
| `can_update_meta` | Enable/disable **edit metadata** modal |

---

## 3) List Scopes (table)

**Endpoint:** `POST /api/i18n/scopes/query`
**Capability:** Available by default for authenticated admins.

### Request — Specifics

*   **Global Search:** Free-text search applied on top of column filters. Matches against **code OR name OR description**.
*   **Sorting:** ⚠️ **SERVER-CONTROLLED**.
    *   `sort_order ASC, code ASC, id ASC`
    *   Clients **MUST NOT** send `sort` parameters.

**Request Payload:**

| Field            | Type   | Required | Description                                      |
|------------------|--------|----------|--------------------------------------------------|
| `page`           | int    | Optional | Page number (default: 1)                         |
| `per_page`       | int    | Optional | Items per page (default: 25)                     |
| `search.global`  | string | Optional | Search term for code/name/description            |
| `search.columns` | object | Optional | Key-value pairs for column filtering             |

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

```json
{
  "data": [
    {
      "id": 1,
      "code": "app",
      "name": "Application",
      "description": "General application scope",
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

### Error Response Example (Invalid Filter)

```json
{
  "success": false,
  "error": {
    "type": "validation_error",
    "code": 422,
    "details": {
      "search.columns.unknown": "Invalid column filter 'unknown'"
    }
  }
}
```

---

## 4) Create Scope

**Endpoint:** `POST /api/i18n/scopes/create`
**Capability:** `can_create`

### Request Payload

| Field         | Type   | Required | Description                                      |
|---------------|--------|----------|--------------------------------------------------|
| `code`        | string | **Yes**  | Unique scope identifier (max 50 chars)           |
| `name`        | string | **Yes**  | Display name (max 100 chars)                     |
| `description` | string | No       | Description text (max 255 chars)                 |
| `is_active`   | bool   | No       | Initial active state (default: true)             |

> **Note:** `sort_order` is NOT accepted. New scopes are appended automatically.

**Example Request:**

```json
{
  "code": "backend",
  "name": "Backend System",
  "description": "Scopes related to backend errors",
  "is_active": true
}
```

**Success Response:**

```json
{
  "id": 123
}
```

**Error Response (Duplicate Code):**

```json
{
  "success": false,
  "error": {
    "type": "entity_already_exists",
    "code": 409,
    "message": "I18nScope with code 'backend' already exists"
  }
}
```

---

## 5) Update Scope Metadata

**Endpoint:** `POST /api/i18n/scopes/update-metadata`
**Capability:** `can_update_meta`

### Rules
*   At least ONE of (`name`, `description`) must be provided.
*   If neither is provided, the request is rejected.

### Request Payload

| Field         | Type   | Required | Description                                      |
|---------------|--------|----------|--------------------------------------------------|
| `id`          | int    | **Yes**  | Scope ID                                         |
| `name`        | string | Optional | New display name                                 |
| `description` | string | Optional | New description text                             |

**Example Request:**

```json
{
  "id": 1,
  "name": "Updated Name",
  "description": "Updated description text"
}
```

**Success Response:**

```json
{
  "status": "ok"
}
```

**Error Response (No Fields Provided):**

```json
{
  "success": false,
  "error": {
    "type": "invalid_operation",
    "code": 422,
    "message": "At least one field (name or description) must be provided"
  }
}
```

---

## 6) Update Sort Order

**Endpoint:** `POST /api/i18n/scopes/update-sort`
**Capability:** `can_update_sort`

### Request Payload

| Field      | Type | Required | Description                                      |
|------------|------|----------|--------------------------------------------------|
| `id`       | int  | **Yes**  | Scope ID                                         |
| `position` | int  | **Yes**  | New sort position (min 0)                        |

> This is the **ONLY** way to change the sort order. The parameter name is strictly `position`.

**Example Request:**

```json
{
  "id": 1,
  "position": 5
}
```

**Success Response:**

```json
{
  "status": "ok"
}
```

**Error Response (Not Found):**

```json
{
  "success": false,
  "error": {
    "type": "entity_not_found",
    "code": 404,
    "message": "I18nScope with ID '999' not found"
  }
}
```

---

## 7) Set Active Status

**Endpoint:** `POST /api/i18n/scopes/{id}/set-active`
**Capability:** `can_set_active`

### Request Payload

| Field       | Type | Required | Description                                      |
|-------------|------|----------|--------------------------------------------------|
| `id`        | int  | **Yes**  | Scope ID (Must match URL ID)                     |
| `is_active` | bool | **Yes**  | New active status                                |

> **Note:** The `{id}` in the URL is required for routing, but the `id` in the JSON body is used for validation and processing.

**Example Request:**

```json
{
  "id": 1,
  "is_active": false
}
```

**Success Response:**

```json
{
  "status": "ok"
}
```

**Error Response (Validation):**

```json
{
  "success": false,
  "error": {
    "type": "validation_error",
    "code": 422,
    "details": {
      "is_active": "is_active is required"
    }
  }
}
```

---

## 8) Implementation Checklist (Scopes Specific)

*   [ ] **Never send `sort`** to `/api/i18n/scopes/query`.
*   [ ] Use `position` (not `sort_order`) for `/update-sort`.
*   [ ] Include `{id}` in the URL for `/set-active`.
*   [ ] Refresh list after `update-sort` (transactional shift).
