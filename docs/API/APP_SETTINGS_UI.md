# ⚙️ App Settings — UI & API Integration Guide

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
  ├─ Modals (create, update)
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

| Capability       | UI Responsibility                      |
|------------------|----------------------------------------|
| `can_create`     | Show/hide **Create Setting** button    |
| `can_update`     | Enable/disable **edit** functionality  |
| `can_set_active` | Enable/disable **active toggle**       |

---

## 3) List App Settings (table)

**Endpoint:** `POST /app-settings/query`
**Capability:** Available by default for authenticated admins.

### Request — Specifics

*   **Global Search:** Free-text search applied on top of column filters. Matches against **group, key OR value**.
*   **Sorting:** Clients can request sorting on supported columns.

**Example Request:**

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

### Supported Column Filters (`search.columns`)

| Alias           | Type   | Example    | Semantics         |
|-----------------|--------|------------|-------------------|
| `id`            | string | `"1"`      | exact match       |
| `setting_group` | string | `"general"`| exact match       |
| `setting_key`   | string | `"mode"`   | exact match       |
| `is_active`     | string | `"1"`      | cast to int (1/0) |

### Response Example

```json
{
  "data": [
    {
      "id": 1,
      "setting_group": "general",
      "setting_key": "app_mode",
      "setting_value": "production",
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

*   `total`: total records in DB (no filters)
*   `filtered`: total records after applying `search.global` and/or `search.columns`
*   When no filters are applied, `filtered` MAY equal `total`

---

## 4) App Settings Metadata

**Endpoint:** `POST /app-settings/metadata`
**Capability:** `can_create` (Required for creation context)

### Purpose
This endpoint provides the structural definitions (Groups and Keys) allowed for App Settings.
It must be loaded before rendering the **Create** form to populate dropdowns and validations.

### Request Payload
*   Empty JSON object `{}`.

### Response Example

```json
{
  "groups": [
    {
      "name": "system",
      "label": "System Configuration",
      "keys": [
        {
          "key": "maintenance_mode",
          "protected": true,
          "wildcard": false
        }
      ]
    }
  ]
}
```

---

## 5) Create App Setting

**Endpoint:** `POST /app-settings/create`
**Capability:** `can_create`

### ⚠️ Dependency Warning
You **MUST** call `POST /app-settings/metadata` first to validate allowed Groups and Keys.

### Request Body

| Field           | Type    | Required | Description                     |
|-----------------|---------|----------|---------------------------------|
| `setting_group` | string  | ✅ Yes    | Must match a valid group name   |
| `setting_key`   | string  | ✅ Yes    | Must match a valid key name     |
| `setting_value` | string  | ✅ Yes    | The value of the setting        |
| `is_active`     | boolean | ❌ No     | Defaults to `true` if omitted   |

### Example Request

```json
{
  "setting_group": "system",
  "setting_key": "maintenance_message",
  "setting_value": "We will be back shortly.",
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
      "setting_group": "REQUIRED_FIELD"
    }
  }
}
```

---

## 6) Update App Setting

**Endpoint:** `POST /app-settings/update`
**Capability:** `can_update`

### Request Body

| Field           | Type    | Required | Description                   |
|-----------------|---------|----------|-------------------------------|
| `setting_group` | string  | ✅ Yes    | The group of the setting      |
| `setting_key`   | string  | ✅ Yes    | The key of the setting        |
| `setting_value` | string  | ✅ Yes    | The new value                 |

> **Note:** The setting is identified by the combination of `setting_group` and `setting_key`.

### Example Request

```json
{
  "setting_group": "system",
  "setting_key": "maintenance_message",
  "setting_value": "Scheduled maintenance in progress."
}
```

### Success Response
```json
{
  "status": "ok"
}
```

---

## 7) Toggle Active

**Endpoint:** `POST /app-settings/set-active`
**Capability:** `can_set_active`

### Request Body

| Field           | Type    | Required | Description                   |
|-----------------|---------|----------|-------------------------------|
| `setting_group` | string  | ✅ Yes    | The group of the setting      |
| `setting_key`   | string  | ✅ Yes    | The key of the setting        |
| `is_active`     | boolean | ✅ Yes    | `true` or `false`             |

### Example Request

```json
{
  "setting_group": "system",
  "setting_key": "maintenance_message",
  "is_active": false
}
```

### Success Response
```json
{
  "status": "ok"
}
```

---

## 8) Implementation Checklist

*   [ ] **Load Metadata** before showing the Create modal.
*   [ ] Use `setting_group` + `setting_key` as the composite identifier for updates.
*   [ ] Respect the **Pagination Contract** (`total` vs `filtered`).
