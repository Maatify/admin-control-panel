# 🌍 App Settings — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / App Settings`
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
    *   **Route Name:** `app_settings.list.ui`
    *   **Purpose:** Render the App Settings UI (HTML).
    *   **Capabilities:** Injects `window.appSettingsCapabilities`.
    *   It returns `text/html`.
    *   Do not call this from JavaScript fetch/axios.

*   **`POST /app-settings/*`**
    *   ✅ **These ARE the APIs.**
    *   They return `application/json` (or empty 204).
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
  └─ returns canonical envelope (queries) or empty 204 / status ok (actions)
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

| Capability       | UI Responsibility                      |
|------------------|----------------------------------------|
| `can_create`     | Show/hide **Create Setting** button    |
| `can_update`     | Enable/disable **edit** actions        |
| `can_set_active` | Enable/disable **active toggle**       |

---

## 3) List App Settings (table)

**Endpoint:** `POST /app-settings/query`
**Route Name:** `app_settings.list.api`
**Capability:** Available by default for authenticated admins.

### Request Payload

| Field            | Type   | Required | Description                                      |
|------------------|--------|----------|--------------------------------------------------|
| `page`           | int    | Optional | Page number (default: 1)                         |
| `per_page`       | int    | Optional | Items per page (default: 25)                     |
| `search.global`  | string | Optional | Free-text search (matches group, key, or value)  |
| `search.columns` | object | Optional | Key-value pairs for column filters               |

### Supported Column Filters (`search.columns`)

| Alias           | Type   | Example   | Semantics         |
|-----------------|--------|-----------|-------------------|
| `id`            | string | `"1"`     | exact match       |
| `setting_group` | string | `"general"` | exact match     |
| `setting_key`   | string | `"name"`  | `LIKE %value%`    |
| `is_active`     | string | `"1"`     | cast to int (1/0) |

**Example Request:**

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "site",
    "columns": {
      "is_active": "1"
    }
  }
}
```

### Response Example (Success)

```json
{
  "data": [
    {
      "id": 1,
      "setting_group": "general",
      "setting_key": "site_name",
      "setting_value": "My App",
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

### Pagination Meanings (REQUIRED)

* `total`: total records in DB (no filters)
* `filtered`: total records after applying `search.global` and/or `search.columns`
* When no filters are applied, `filtered` MAY equal `total`

### Error Response Example

```json
{
  "success": false,
  "error": {
    "code": 422,
    "message": "Validation Failed",
    "details": {
      "page": "must be a positive integer"
    }
  }
}
```

---

## 4) Metadata

**Endpoint:** `POST /app-settings/metadata`
**Route Name:** `app_settings.metadata.api`
**Capability:** Required for Create/Update forms.

### ⚠️ CRITICAL: Metadata → Create Dependency

The **Create App Setting** endpoint depends on metadata.
The UI **MUST** call `POST /app-settings/metadata` **before** rendering or submitting the create form.

Metadata defines:
*   Allowed setting groups and keys
*   Protection status (read-only vs editable)
*   Wildcard support

### Request Payload

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| -     | -    | -        | No body     |

### Response Example (Success)

```json
{
  "groups": [
    {
      "name": "general",
      "label": "General",
      "keys": [
        {
          "key": "site_name",
          "protected": false,
          "wildcard": false
        },
        {
          "key": "*",
          "protected": false,
          "wildcard": true
        }
      ]
    }
  ]
}
```

### Error Response Example

```json
{
  "success": false,
  "error": {
    "code": 403,
    "message": "Forbidden",
    "details": "Insufficient permissions to view metadata"
  }
}
```

---

## 5) Create App Setting

**Endpoint:** `POST /app-settings/create`
**Route Name:** `app_settings.create.api`
**Capability:** `can_create`

### Request Payload

| Field           | Type   | Required | Description                                |
|-----------------|--------|----------|--------------------------------------------|
| `setting_group` | string | **Yes**  | Group identifier (1-64 chars)              |
| `setting_key`   | string | **Yes**  | Key identifier (1-64 chars)                |
| `setting_value` | string | **Yes**  | Setting value (cannot be empty)            |
| `is_active`     | bool   | No       | Active status (default: true)              |

**Example Request:**

```json
{
  "setting_group": "general",
  "setting_key": "maintenance_mode",
  "setting_value": "0",
  "is_active": true
}
```

### Success Response

**Code:** `204 No Content`
(Empty Body)

### Error Response Example

```json
{
  "success": false,
  "error": {
    "code": 422,
    "message": "Validation Failed",
    "details": {
      "setting_key": "Required field"
    }
  }
}
```

---

## 6) Update App Setting

**Endpoint:** `POST /app-settings/update`
**Route Name:** `app_settings.update.api`
**Capability:** `can_update`

### Request Payload

| Field           | Type   | Required | Description                                |
|-----------------|--------|----------|--------------------------------------------|
| `setting_group` | string | **Yes**  | Target group identifier                    |
| `setting_key`   | string | **Yes**  | Target key identifier                      |
| `setting_value` | string | **Yes**  | New value for the setting                  |

**Example Request:**

```json
{
  "setting_group": "general",
  "setting_key": "site_name",
  "setting_value": "New App Name"
}
```

### Success Response

```json
{
  "status": "ok"
}
```

### Error Response Example

```json
{
  "success": false,
  "error": {
    "code": 422,
    "message": "Validation Failed",
    "details": {
      "setting_value": "Required field"
    }
  }
}
```

---

## 7) Set Active

**Endpoint:** `POST /app-settings/set-active`
**Route Name:** `app_settings.set_active.api`
**Capability:** `can_set_active`

### Request Payload

| Field           | Type   | Required | Description                                |
|-----------------|--------|----------|--------------------------------------------|
| `setting_group` | string | **Yes**  | Target group identifier                    |
| `setting_key`   | string | **Yes**  | Target key identifier                      |
| `is_active`     | bool   | **Yes**  | New active state (true/false)              |

**Example Request:**

```json
{
  "setting_group": "general",
  "setting_key": "maintenance_mode",
  "is_active": true
}
```

### Success Response

```json
{
  "status": "ok"
}
```

### Error Response Example

```json
{
  "success": false,
  "error": {
    "code": 422,
    "message": "Validation Failed",
    "details": {
      "is_active": "Required field"
    }
  }
}
```
