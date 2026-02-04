# 🔐 Roles Management — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / Authorization`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is a **runtime integration contract** for the Roles UI.

It answers, precisely:

*   What the UI is allowed to send
*   How global search and filters actually work
*   What each endpoint requires vs what is optional
*   What response shapes exist (success + failure)
*   Why you are getting `422` / runtime exceptions

If something is not documented here, treat it as **not supported**.

### ⚠️ CRITICAL: UI vs API Distinction

You must understand the difference between the **UI Page** and the **API**:

*   **`GET /roles`**
    *   ❌ **This is NOT an API.**
    *   ✅ This is the **browser entry point** that renders the HTML page.
    *   It returns `text/html`.
    *   Do not call this from JavaScript fetch/axios.

*   **`POST /api/roles/*`**
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
  ├─ renders roles list page
  └─ includes JS bundle

JavaScript
  ├─ DataTable (query + pagination)
  ├─ Modals (create, rename, update metadata)
  └─ Actions (toggle active)

API (authoritative)
  ├─ validates request schema
  ├─ applies query resolver rules
  └─ returns canonical envelope (queries) or empty 200 (actions)
```

---

## 2) Capabilities (Authorization Contract)

The UI receives these **Roles-specific capability flags**:

### 2.1 Injected Flags

```php
$capabilities = [
    'can_create'       => $this->authorizationService->hasPermission($adminId, 'roles.create'),
    'can_update_meta'  => $this->authorizationService->hasPermission($adminId, 'roles.metadata.update'),
    'can_rename'       => $this->authorizationService->hasPermission($adminId, 'roles.rename'),
    'can_toggle'       => $this->authorizationService->hasPermission($adminId, 'roles.toggle'),
    'can_view_role'    => $this->authorizationService->hasPermission($adminId, 'roles.view'),
];
```

### 2.2 Capability → UI Behavior Mapping

| Capability        | UI Responsibility                                |
|-------------------|--------------------------------------------------|
| `can_create`      | Show/hide **Create Role** button                 |
| `can_update_meta` | Enable/disable **metadata edit** (name/desc)     |
| `can_rename`      | Enable/disable **rename** action (technical key) |
| `can_toggle`      | Enable/disable **active toggle**                 |
| `can_view_role`   | Show/hide **Role Details** link                  |

---

## 3) List Roles (table)

**Endpoint:** `POST /api/roles/query`
**Capability:** `can_query` (implicit/base)

### Request — Specifics

*   **Global Search:** Free-text search applied on top of column filters. Matches against **name**.
*   **Sorting:** ⚠️ **SERVER-CONTROLLED**.
    *   `sort_order ASC` (Server default)
    *   Clients **MUST NOT** send `sort` parameters.

**Example Request:**

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "admins",
    "columns": {
      "group": "admins"
    }
  }
}
```

### Supported Column Filters (`search.columns`)

| Alias   | Type   | Example   | Semantics      |
|---------|--------|-----------|----------------|
| `id`    | int    | `12`      | exact match    |
| `name`  | string | `"admin"` | `LIKE %value%` |
| `group` | string | `"users"` | `LIKE %value%` |

### Response Model

```json
{
  "data": [
    {
      "id": 1,
      "name": "admins.manage",
      "group": "admins",
      "display_name": "Admin Management",
      "description": "Full access to admin management features",
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
*   `total`: total records in DB (no filters)
*   `filtered`: total records after applying `search.global` and/or `search.columns`
*   When no filters are applied, `filtered` MAY equal `total`.

---

## 4) Create Role

**Endpoint:** `POST /api/roles/create`
**Capability:** `can_create`

### Request Body

*   `name` (string, required, canonical format `group.action`)
*   `display_name` (string, optional)
*   `description` (string, optional)

> **Format:** `name` must match `^[a-z][a-z0-9_.-]*$`.

**Example:**
```json
{
  "name": "admins.manage",
  "display_name": "Admin Management",
  "description": "Full access to admin management features"
}
```

### Response

*   ✅ **200 OK (EMPTY BODY)**
*   ❌ **409** if role name already exists.

---

## 5) Update Role Metadata

**Endpoint:** `POST /api/roles/{id}/metadata`
**Capability:** `can_update_meta`

### Request Body

*   `display_name` (string, optional)
*   `description` (string, optional)

> **Note:** At least one field should be provided.
> Send **real values** only. Do not send `null`.

**Example:**
```json
{
  "display_name": "Updated Label"
}
```

### Response

*   ✅ **200 OK (EMPTY BODY)**
*   ✅ **204 No Content** (if no changes were requested)

---

## 6) Rename Role (Technical Key)

**Endpoint:** `POST /api/roles/{id}/rename`
**Capability:** `can_rename`

### Request Body

*   `name` (string, required)

> ⚠️ This changes the stable technical key.

**Example:**
```json
{
  "name": "admins.super_manage"
}
```

### Response

*   ✅ **200 OK (EMPTY BODY)**

---

## 7) Toggle Role Activation

**Endpoint:** `POST /api/roles/{id}/toggle`
**Capability:** `can_toggle`

### Request Body

*   `is_active` (boolean, required)

**Example:**
```json
{
  "is_active": false
}
```

### Response

*   ✅ **200 OK (EMPTY BODY)**

---

## 8) Implementation Checklist (Roles Specific)

*   [ ] **Never send `sort`** to `/api/roles/query`.
*   [ ] Validate `name` format regex in UI before sending.
*   [ ] Handle **204 No Content** gracefully for metadata updates.
