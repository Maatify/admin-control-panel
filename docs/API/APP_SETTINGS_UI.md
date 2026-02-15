# ⚙️ App Settings Management — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / AppSettings`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is a **runtime integration contract** for the App Settings UI.

It answers, precisely:

* What the UI is allowed to send
* How global search and filters actually work
* What each endpoint requires vs what is optional
* What response shapes exist (success + failure)
* Why you are getting `422` / runtime exceptions

If something is not documented here, treat it as **not supported**.

### ⚠️ CRITICAL: UI vs API Distinction

You must understand the difference between the **UI Page** and the **API**:

* **`GET /app-settings`**

  * ❌ **This is NOT an API.**
  * ✅ This is the **browser entry point** that renders the HTML page.
  * It returns `text/html`.
  * Do not call this from JavaScript fetch/axios.

* **`POST /api/app-settings/*`**

  * ✅ **These ARE the APIs.**
  * They return `application/json` (or empty 200).
  * All programmatic interaction happens here.

> ⚠️ **RUNTIME RULES:**
> This document assumes **mandatory compliance** with the **[UI Runtime Integration Rules](UI_RUNTIME_RULES.md)**.
> Refer to that file for:
>
> * Response parsing (JSON vs Empty Body)
> * Error handling (422/403)
> * Null handling in payloads
> * Canonical Query construction

---

## 1) Page Architecture

```
Twig Controller
  ├─ injects capabilities
  ├─ renders app settings page
  └─ includes JS bundle

JavaScript
  ├─ DataTable (query + pagination)
  ├─ Modals (create, update)
  └─ Actions (toggle active)

API (authoritative)
  ├─ validates request schema
  ├─ applies query resolver rules
  ├─ applies whitelist rules
  ├─ applies protection rules
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

| Capability       | UI Responsibility                       |
|------------------|-----------------------------------------|
| `can_create`     | Show/hide **Create App Setting** button |
| `can_update`     | Enable/disable **edit** actions         |
| `can_set_active` | Enable/disable **active toggle**        |

---

## 3) List App Settings (table)

**Endpoint:** `POST /api/app-settings/query`
**Capability:** Available by default for authenticated admins.

### Request Payload

| Field            | Type   | Required | Description                                     |
|:-----------------|:-------|:---------|:------------------------------------------------|
| `page`           | int    | Optional | Page number (default: 1)                        |
| `per_page`       | int    | Optional | Records per page (default: 25, max: 100)        |
| `search`         | object | Optional | Search criteria wrapper                         |
| `search.global`  | string | Optional | Free-text search (matches group, key, or value) |
| `search.columns` | object | Optional | Key-value pairs for column filters              |

### Sorting Rule
* ⚠️ **SERVER-CONTROLLED**: `setting_group ASC, setting_key ASC, id ASC`.
* Clients **MUST NOT** send `sort` parameters.

### Supported Column Filters (`search.columns`)

| Alias           | Type   | Example | Semantics         |
|-----------------|--------|---------|-------------------|
| `id`            | string | `"1"`   | exact match       |
| `setting_group` | string | `"sys"` | exact match       |
| `setting_key`   | string | `"ver"` | `LIKE %value%`    |
| `is_active`     | string | `"1"`   | cast to int (1/0) |

### Example Request

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "config",
    "columns": {
      "is_active": "1"
    }
  }
}
```

### Success Response

```json
{
  "data": [
    {
      "id": 1,
      "setting_group": "system",
      "setting_key": "version",
      "setting_value": "1.0.0",
      "setting_type": "string",
      "is_active": 1,
      "is_protected": true,
      "is_editable": false
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

**Row Meanings (additional fields):**

* `setting_type`: `string`, `int`, `bool`, or `json`.
* `is_protected`: this setting is protected by policy and cannot be modified/deactivated.
* `is_editable`: UI must use this to enable/disable edit + toggle actions.

**Pagination Meanings:**

* `total`: total records in DB (no filters)
* `filtered`: total records after applying `search.global` and/or `search.columns`
* When no filters are applied, `filtered` MAY equal `total`.

### Error Response Example (422 Invalid Filter)

```json
{
  "success": false,
  "error": {
    "code": 422,
    "type": "VALIDATION_FAILED",
    "details": {
      "search": "INVALID_VALUE"
    }
  }
}
```

---

## 4) Metadata (Critical Dependency)

**Endpoint:** `POST /api/app-settings/metadata`
**Capability:** Available by default for authenticated admins.

> ⚠️ **CRITICAL DEPENDENCY:**
> The UI **MUST** call this endpoint **before** rendering the **Create App Setting** form.
> It defines the allowed Groups and Keys that can be created.

### Request Payload

| Field | Type | Required | Description                                       |
|:------|:-----|:---------|:--------------------------------------------------|
| -     | -    | -        | No payload required. Send empty JSON object `{}`. |

### Success Response

```json
{
  "groups": [
    {
      "name": "system",
      "label": "System",
      "keys": [
        {
          "key": "version",
          "protected": true,
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

### Error Response Example (401 Unauthorized)

```json
{
  "success": false,
  "error": {
    "code": 401,
    "type": "UNAUTHORIZED"
  }
}
```

---

## 5) Create App Setting

**Endpoint:** `POST /api/app-settings/create`
**Capability:** `can_create`

### Request Payload

| Field           | Type   | Required | Description                                                                             |
|:----------------|:-------|:---------|:----------------------------------------------------------------------------------------|
| `setting_group` | string | **Yes**  | 1-64 characters. Must match a valid group from Metadata.                                |
| `setting_key`   | string | **Yes**  | 1-64 characters. Must match a valid key from Metadata (or any key if wildcard allowed). |
| `setting_value` | string | **Yes**  | The value to store. At least 1 character.                                               |
| `setting_type`  | string | No       | One of: `string`, `int`, `bool`, `json`. Defaults to `string`.                          |
| `is_active`     | bool   | No       | Defaults to `true` if omitted.                                                          |

### Example Request

```json
{
  "setting_group": "system",
  "setting_key": "maintenance_mode",
  "setting_value": "off",
  "setting_type": "string",
  "is_active": true
}
```

### Success Response

```json
{
  "status": "ok"
}
```

### Error Response Example (422 Missing Field)

```json
{
  "success": false,
  "error": {
    "code": 422,
    "type": "VALIDATION_FAILED",
    "details": {
      "setting_key": "REQUIRED_FIELD"
    }
  }
}
```

---

## 6) Update App Setting

**Endpoint:** `POST /api/app-settings/update`
**Capability:** `can_update`

### Request Payload

| Field           | Type   | Required | Description      |
|:----------------|:-------|:---------|:-----------------|
| `setting_group` | string | **Yes**  | 1-64 characters. |
| `setting_key`   | string | **Yes**  | 1-64 characters. |
| `setting_value` | string | **Yes**  | The new value.   |
| `setting_type`  | string | No       | Optional type override. One of: `string`, `int`, `bool`, `json`. |

### Example Request

```json
{
  "setting_group": "system",
  "setting_key": "maintenance_mode",
  "setting_value": "on",
  "setting_type": "string"
}
```

### Success Response

```json
{
  "status": "ok"
}
```

### Error Response Example (403 Forbidden)

```json
{
  "success": false,
  "error": {
    "code": 403,
    "type": "INSUFFICIENT_PERMISSIONS"
  }
}
```

---

## 7) Set Active

**Endpoint:** `POST /api/app-settings/set-active`
**Capability:** `can_set_active`

### Request Payload

| Field           | Type   | Required | Description                           |
|:----------------|:-------|:---------|:--------------------------------------|
| `setting_group` | string | **Yes**  | 1-64 characters.                      |
| `setting_key`   | string | **Yes**  | 1-64 characters.                      |
| `is_active`     | bool   | **Yes**  | `true` to enable, `false` to disable. |

### Example Request

```json
{
  "setting_group": "system",
  "setting_key": "maintenance_mode",
  "is_active": false
}
```

### Success Response

```json
{
  "status": "ok"
}
```

### Error Response Example (422 Type Mismatch)

```json
{
  "success": false,
  "error": {
    "code": 422,
    "type": "VALIDATION_FAILED",
    "details": {
      "is_active": "INVALID_TYPE"
    }
  }
}
```

---

## 8) Implementation Checklist (App Settings Specific)

* [ ] **Never send `sort`** to `/api/app-settings/query`.
* [ ] Fetch **Metadata** before opening the Create Modal.
* [ ] Use Metadata to validate/populate Group and Key dropdowns/inputs.
* [ ] Update requires Group and Key to identify the record (Composite Key).
* [ ] Respect `is_protected` and `is_editable` from `/api/app-settings/query` when rendering actions.
