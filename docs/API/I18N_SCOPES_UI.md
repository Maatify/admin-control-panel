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
| `can_update`      | Enable/disable **Edit** actions (general guard)        |
| `can_set_active`  | Enable/disable **active toggle**                       |
| `can_update_sort` | Enable/disable **sort order** controls                 |
| `can_update_meta` | Enable/disable **update name/description** UI          |

---

## 3) List Scopes (table)

**Endpoint:** `POST /api/i18n/scopes/query`
**Authorization:** `i18n.scopes.list`

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

| Alias       | Type   | Example | Semantics         |
|-------------|--------|---------|-------------------|
| `id`        | string | `"1"`   | exact match       |
| `code`      | string | `"acc"` | exact match       |
| `name`      | string | `"Acc"` | exact match       |
| `is_active` | string | `"1"`   | cast to int (1/0) |

### Response Model

```json
{
  "data": [
    {
      "id": 1,
      "code": "account",
      "name": "Account",
      "description": "Account related translations",
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

---

## 4) Create Scope

**Endpoint:** `POST /api/i18n/scopes/create`
**Authorization:** `i18n.scopes.create`

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `code` | string | **Yes** | Unique identifier |
| `name` | string | **Yes** | Display name |
| `description` | string | No | Optional description |
| `is_active` | int (0/1) | No | Defaults to `1` (Active) |

**Example Request:**

```json
{
  "code": "checkout",
  "name": "Checkout Flow",
  "description": "Translations for checkout pages",
  "is_active": 1
}
```

### Success Response

```json
{
  "id": 12
}
```

### Error Response

```json
{
  "status": "error",
  "error": "EntityAlreadyExistsException",
  "message": "I18nScope with code 'checkout' already exists"
}
```

---

## 5) Update Scope Metadata

**Endpoint:** `POST /api/i18n/scopes/update-metadata`
**Authorization:** `i18n.scopes.update_metadata`

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | int | **Yes** | Scope ID |
| `name` | string | No | Update display name |
| `description` | string | No | Update description |

> **Note:** At least one of `name` or `description` must be provided.

**Example Request:**

```json
{
  "id": 12,
  "name": "New Checkout Name",
  "description": "Updated description"
}
```

### Success Response

```json
{
  "status": "ok"
}
```

---

## 6) Change Scope Code

**Endpoint:** `POST /api/i18n/scopes/change-code`
**Authorization:** `i18n.scopes.change_code.api`

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | int | **Yes** | Scope ID |
| `new_code` | string | **Yes** | New unique code |

**Example Request:**

```json
{
  "id": 12,
  "new_code": "checkout_v2"
}
```

### Success Response

```json
{
  "status": "ok"
}
```

### Error Response

```json
{
  "status": "error",
  "error": "EntityInUseException",
  "message": "I18nScope code 'checkout' is in use by domains or translations"
}
```

---

## 7) Update Scope Sort Order

**Endpoint:** `POST /api/i18n/scopes/update-sort`
**Authorization:** `i18n.scopes.update_sort`

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | int | **Yes** | Scope ID |
| `position` | int | **Yes** | New sort position (min 0) |

**Example Request:**

```json
{
  "id": 12,
  "position": 5
}
```

### Success Response

```json
{
  "status": "ok"
}
```

---

## 8) Set Active

**Endpoint:** `POST /api/i18n/scopes/set-active`
**Authorization:** `i18n.scopes.set_active`

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | int | **Yes** | Scope ID |
| `is_active` | int (0/1) | No | Defaults to `1` (Active) |

> **Note:** Boolean `true`/`false` are **NOT** supported. Use `1` or `0`.

**Example Request:**

```json
{
  "id": 12,
  "is_active": 0
}
```

### Success Response

```json
{
  "status": "ok"
}
```

---

## 9) Implementation Checklist (Scopes Specific)

*   [ ] **Never send `sort`** to `/api/i18n/scopes/query`.
*   [ ] Use `1` / `0` for `is_active` (never booleans).
*   [ ] Refresh list after `update-sort` (transactional shift).
*   [ ] Handle `422` validation errors for code uniqueness.
