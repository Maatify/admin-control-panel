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

### ⚠️ Optional Field Rule (CRITICAL)

Optional fields MUST be omitted entirely if not provided.

UI MUST NOT send:
- null
- empty string
- empty object

Sending optional fields as null or empty
may result in validation errors (422).

---

## 1) Page Architecture

```
Twig Controller
  ├─ injects capabilities
  ├─ renders scopes page
  └─ includes JS bundle

JavaScript
  ├─ DataTable (query + pagination)
  ├─ Modals (create, change-code, update-metadata)
  └─ Actions (toggle active, update sort)

API (authoritative)
  ├─ validates request schema
  ├─ applies query resolver rules
  └─ returns canonical envelope (queries) or empty 200 (actions)
```

---

## 2) Capabilities (Authorization Contract)

The UI receives these **I18n Scopes-specific capability flags**:

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
| `can_update`      | Enable/disable **Change Code** UI                      |
| `can_set_active`  | Enable/disable **active toggle**                       |
| `can_update_sort` | Enable/disable **sort order** controls                 |
| `can_update_meta` | Enable/disable **update name/description** UI          |

---

## 3) List Scopes (table)

**Endpoint:** `POST /api/i18n/scopes/query`
**Capability:** Available by default for authenticated admins.

### Request — Specifics

*   **Global Search:** Free-text search applied on top of column filters. Matches against **code, name OR description**.
*   **Sorting:** ⚠️ **SERVER-CONTROLLED**.
    *   `sort_order ASC, id ASC`
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

| Alias       | Type   | Example    | Semantics         |
|-------------|--------|------------|-------------------|
| `id`        | string | `"1"`      | exact match       |
| `code`      | string | `"front"`  | exact match       |
| `name`      | string | `"Front"`  | `LIKE %value%`    |
| `is_active` | string | `"1"`      | cast to int (1/0) |

### Response Model

```json
{
  "data": [
    {
      "id": 1,
      "code": "frontend",
      "name": "Frontend Scope",
      "description": "Used for frontend translations",
      "sort_order": 1,
      "is_active": true
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
*   `total`: total rows without filters
*   `filtered`: rows after filters/search
*   When no filters are applied, `filtered` MAY equal `total`.

---

## 4) Create Scope

**Endpoint:** `POST /api/i18n/scopes/create`
**Capability:** `can_create`

### Request Body

*   `code` (string, required)
*   `name` (string, required)
*   `description` (string, optional)
*   `is_active` (bool, optional, default: true)
*   `sort_order` (int, optional, default: 0)

> **Note:** If `is_active` or `sort_order` are not provided, server defaults apply.

**Example Request:**

```json
{
  "code": "mobile_app",
  "name": "Mobile Application",
  "description": "Scope for mobile app strings",
  "is_active": true,
  "sort_order": 10
}
```

**Example Success Response:**

```json
{
  "id": 42
}
```

**Example Error Response:**

```json
{
  "error": "EntityAlreadyExistsException",
  "message": "Scope with code 'mobile_app' already exists."
}
```

---

## 5) Change Scope Code

**Endpoint:** `POST /api/i18n/scopes/change-code`
**Capability:** `can_update`

### Purpose
Allows renaming the `code` identifier. This is a sensitive operation as it affects translation keys.

### Request Body

*   `id` (int, required)
*   `new_code` (string, required)

**Example Request:**

```json
{
  "id": 42,
  "new_code": "mobile_app_v2"
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
  "error": "EntityInUseException",
  "message": "Scope 'mobile_app' is in use by domains or translations."
}
```

---

## 6) Set Active Status

**Endpoint:** `POST /api/i18n/scopes/{id}/set-active`
**Capability:** `can_set_active`

### Request Body

*   `id` (int, required) — **MUST match URL ID**
*   `is_active` (bool, required)

**Example Request:**

```json
{
  "id": 42,
  "is_active": false
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
  "error": "EntityNotFoundException",
  "message": "I18nScope with ID 42 not found."
}
```

---

## 7) Update Sort Order

**Endpoint:** `POST /api/i18n/scopes/update-sort`
**Capability:** `can_update_sort`

### Request Body

*   `id` (int, required)
*   `position` (int, min 0, required)

**Example Request:**

```json
{
  "id": 42,
  "position": 5
}
```

**Example Success Response:**

```json
{
  "status": "ok"
}
```

---

## 8) Update Metadata (Name/Description)

**Endpoint:** `POST /api/i18n/scopes/update-metadata`
**Capability:** `can_update_meta`

### Purpose
Update the human-readable name or internal description. At least one field MUST be provided.

### Request Body

*   `id` (int, required)
*   `name` (string, optional)
*   `description` (string, optional)

**Example Request:**

```json
{
  "id": 42,
  "name": "Mobile Application V2",
  "description": "Updated description for V2"
}
```

**Example Success Response:**

```json
{
  "status": "ok"
}
```

**Example Error Response (Missing fields):**

```json
{
  "error": "InvalidOperationException",
  "message": "At least one field (name or description) must be provided"
}
```

---

## 9) Implementation Checklist (Scopes Specific)

*   [ ] **Never send `sort`** to `/api/i18n/scopes/query`.
*   [ ] Omit optional fields (`description`, `sort_order`) entirely if empty.
*   [ ] Ensure `id` is included in the JSON body for `set-active`, even though it is in the URL.
*   [ ] Refresh list after `update-sort` (transactional shift).
