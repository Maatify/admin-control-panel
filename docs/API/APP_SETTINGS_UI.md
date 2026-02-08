# 🌍 App Settings Management — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / AppSettings`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is a **runtime integration contract** for the App Settings UI.

It answers, precisely:

*   What the UI is allowed to send
*   How global search and filters actually work
*   What each endpoint requires vs what is optional
*   What response shapes exist (success + failure)
*   Why you are getting `422` / runtime exceptions

If something is not documented here, treat it as **not supported**.

### ⚠️ CRITICAL: UI vs API Distinction

You must understand the difference between the **UI Page** and the **API**:

*   **`GET /app-settings`**
    *   ❌ **This is NOT an API.**
    *   ✅ This is the **browser entry point** that renders the HTML page.
    *   It returns `text/html`.
    *   Do not call this from JavaScript fetch/axios.

*   **`POST /app-settings/*`**
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
  ├─ renders app settings page
  └─ includes JS bundle

JavaScript
  ├─ DataTable (query + pagination)
  ├─ Modals (create, update settings)
  └─ Actions (toggle active)

API (authoritative)
  ├─ validates request schema
  ├─ applies query resolver rules
  └─ returns canonical envelope (queries) or empty 200 (actions)
```

---

## 2) Capabilities (Authorization Contract)

The UI receives these **App Settings-specific capability flags**:

### 2.1 Injected Flags

```php
$capabilities = [
    'can_create'     => $hasPermission('app_settings.create'),
    'can_update'     => $hasPermission('app_settings.update'),
    'can_set_active' => $hasPermission('app_settings.update'),
];
```

### 2.2 Capability → UI Behavior Mapping

| Capability       | UI Responsibility                                      |
|------------------|--------------------------------------------------------|
| `can_create`     | Show/hide **Create Setting** button                    |
| `can_update`     | Enable/disable **edit** functionality                  |
| `can_set_active` | Enable/disable **active toggle**                       |

---

## 3) List App Settings (table)

**Endpoint:** `POST /app-settings/query`
**Capability:** Available by default for authenticated admins.

### Request — Specifics

*   **Global Search:** Free-text search applied on top of column filters. Matches against **group, key, OR value**.
*   **Sorting:** ⚠️ **SERVER-CONTROLLED**.
    *   `setting_group ASC, setting_key ASC, id ASC`
    *   Clients **MUST NOT** send `sort` parameters.

### Request Body

| Field      | Type   | Required | Description                                      |
|------------|--------|----------|--------------------------------------------------|
| `page`     | int    | Optional | Page number (default: 1)                         |
| `per_page` | int    | Optional | Items per page (default: 25)                     |
| `search`   | object | Optional | Search object (`global` + `columns`)             |
| `date`     | object | Optional | Date filter (`from` + `to`) - **NOT SUPPORTED**  |

**Example Request:**

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "social",
    "columns": {
      "is_active": "1"
    }
  }
}
```

### Supported Column Filters (`search.columns`)

| Alias           | Type   | Example    | Semantics         |
|-----------------|--------|------------|-------------------|
| `id`            | string | `"1"`      | exact match       |
| `setting_group` | string | `"social"` | exact match       |
| `setting_key`   | string | `"face"`   | `LIKE %value%`    |
| `is_active`     | string | `"1"`      | cast to int (1/0) |

### Response Model

```json
{
  "data": [
    {
      "id": 1,
      "setting_group": "social",
      "setting_key": "facebook_url",
      "setting_value": "https://facebook.com/maatify",
      "is_active": 1
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

### Error Responses

*   `422 Unprocessable Entity`: Invalid filter format or types.

**Example Error Response:**

```json
{
  "error": "Invalid request payload",
  "errors": {
    "page": "must be an integer"
  }
}
```

---

## 4) Metadata (Pre-Create Dependency)

**Endpoint:** `POST /app-settings/metadata`
**Capability:** `can_create` (Derived dependency)

### ⚠️ CRITICAL DEPENDENCY

The **Create App Setting** UI **MUST** call this endpoint before rendering the creation form.
It defines:
*   Allowed setting groups (whitelist).
*   Which keys are protected.
*   Which keys allow wildcard usage.

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| -     | -    | -        | Empty JSON object `{}` |

### Response Model

```json
{
  "groups": [
    {
      "name": "social",
      "label": "Social",
      "keys": [
        {
          "key": "facebook_url",
          "protected": false,
          "wildcard": false
        },
        {
          "key": "*",
          "protected": false,
          "wildcard": true
        }
      ]
    },
    {
      "name": "system",
      "label": "System",
      "keys": [
        {
          "key": "base_url",
          "protected": true,
          "wildcard": false
        }
      ]
    }
  ]
}
```

### Error Responses

*   `403 Forbidden`: If user lacks permissions.

**Example Error Response:**

```json
{
  "message": "Access Denied",
  "code": "FORBIDDEN"
}
```

---

## 5) Create App Setting

**Endpoint:** `POST /app-settings/create`
**Capability:** `can_create`

### Request Body

| Field           | Type   | Required | Description                                      |
|-----------------|--------|----------|--------------------------------------------------|
| `setting_group` | string | **Yes**  | Group name (must be whitelisted in Metadata).    |
| `setting_key`   | string | **Yes**  | Key name (must be valid for group).              |
| `setting_value` | string | **Yes**  | Value content.                                   |
| `is_active`     | bool   | No       | Active status (default: true).                   |

### Validation Rules

*   `setting_group`: Length 1-64.
*   `setting_key`: Length 1-64.
*   `setting_value`: Min length 1.
*   **Business Rule:** Group/Key must exist in Metadata whitelist.

**Example Request:**

```json
{
  "setting_group": "social",
  "setting_key": "instagram_url",
  "setting_value": "https://instagram.com/maatify",
  "is_active": true
}
```

### Success Response

```json
{
  "status": "ok"
}
```

### Error Responses

*   `422 Unprocessable Entity`: Validation failure.
*   `400 Bad Request`: Domain exception (e.g. Setting already exists).

**Example Error Response:**

```json
{
  "error": "Invalid request payload",
  "errors": {
    "setting_group": "Length must be between 1 and 64"
  }
}
```

---

## 6) Update App Setting

**Endpoint:** `POST /app-settings/update`
**Capability:** `can_update`

### Request Body

| Field           | Type   | Required | Description                                      |
|-----------------|--------|----------|--------------------------------------------------|
| `setting_group` | string | **Yes**  | Group identifier.                                |
| `setting_key`   | string | **Yes**  | Key identifier.                                  |
| `setting_value` | string | **Yes**  | New value content.                               |

> **Note:** The update is keyed by `(group, key)`, NOT by `id`.

### Validation Rules

*   `setting_group`: Length 1-64.
*   `setting_key`: Length 1-64.
*   `setting_value`: Required string.

**Example Request:**

```json
{
  "setting_group": "social",
  "setting_key": "instagram_url",
  "setting_value": "https://instagram.com/new_maatify"
}
```

### Success Response

```json
{
  "status": "ok"
}
```

### Error Responses

*   `404 Not Found`: If the setting `(group, key)` does not exist.
*   `422 Unprocessable Entity`: Validation failure.

**Example Error Response:**

```json
{
  "message": "App Setting not found",
  "code": "NOT_FOUND"
}
```

---

## 7) Toggle Active

**Endpoint:** `POST /app-settings/set-active`
**Capability:** `can_set_active`

### Request Body

| Field           | Type   | Required | Description                                      |
|-----------------|--------|----------|--------------------------------------------------|
| `setting_group` | string | **Yes**  | Group identifier.                                |
| `setting_key`   | string | **Yes**  | Key identifier.                                  |
| `is_active`     | bool   | **Yes**  | New active status.                               |

> **Note:** The toggle is keyed by `(group, key)`, NOT by `id`.

### Validation Rules

*   `setting_group`: Length 1-64.
*   `setting_key`: Length 1-64.
*   `is_active`: Must be boolean.

**Example Request:**

```json
{
  "setting_group": "social",
  "setting_key": "instagram_url",
  "is_active": false
}
```

### Success Response

```json
{
  "status": "ok"
}
```

### Error Responses

*   `404 Not Found`: If the setting `(group, key)` does not exist.
*   `422 Unprocessable Entity`: Validation failure.

**Example Error Response:**

```json
{
  "error": "Invalid request payload",
  "errors": {
    "is_active": "must be a boolean"
  }
}
```

---

## 8) Implementation Checklist (App Settings Specific)

*   [ ] **Never send `sort`** to `/app-settings/query`.
*   [ ] Fetch **Metadata** before showing the Create Modal.
*   [ ] Use `(group, key)` composite key for Update/Toggle, not `id`.
*   [ ] Respect `protected` flag in Metadata (prevent edit/toggle if true).
