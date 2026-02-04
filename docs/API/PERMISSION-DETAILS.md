# Permission Details — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / Authorization`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is a **runtime integration contract** for the Permission Details Page.
It answers, precisely:
*   How to view usage of a permission across Roles.
*   How to view usage of a permission across Admins (Direct Overrides).

### ⚠️ CRITICAL: UI vs API Distinction

*   **`GET /permissions/{id}`**
    *   ❌ **This is NOT an API.**
    *   ✅ This is the **browser entry point** that renders the Permission Details HTML page.
    *   It returns `text/html`.

*   **`POST /api/permissions/{id}/*`**
    *   ✅ **These ARE the APIs.**
    *   They return `application/json` (or empty 200/204).
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
  ├─ loads permission entity
  ├─ injects capabilities
  └─ renders permission details page

JavaScript
  ├─ Tab Switcher (Roles / Admins)
  ├─ DataTable (Roles using Permission)
  └─ DataTable (Admins with Direct Overrides)
```

---

## 2) Capabilities (Authorization Contract)

The UI receives these **Permission Details-specific capability flags**:

### 2.1 Injected Flags

```php
$capabilities = [
    // Navigation
    'can_view_permissions'   => $hasPermission('permissions.query.ui'),
    'can_view_admin_profile' => $hasPermission('admins.profile.view'),
    'can_view_role_details'  => $hasPermission('roles.view.ui'),

    // Tabs
    'can_view_roles_tab'     => $hasPermission('permissions.roles.query'),
    'can_view_admins_tab'    => $hasPermission('permissions.admins.query'),
];
```

### 2.2 Capability → UI Behavior Mapping

| Capability               | UI Responsibility                                  |
|--------------------------|----------------------------------------------------|
| `can_view_roles_tab`     | Show/hide **Roles Tab**                            |
| `can_view_admins_tab`    | Show/hide **Admins Tab**                           |
| `can_view_role_details`  | Enable link to Role Details from table             |
| `can_view_admin_profile` | Enable link to Admin Profile from table            |

---

## 3) Roles using Permission (Read-Only)

**Endpoint:** `POST /api/permissions/{id}/roles/query`
**Capability:** `can_view_roles_tab`

### Request — Specifics

*   **Global Search:** Matches **name**, **display_name**, **description**, or **group**.
*   **Sorting:** ⚠️ **SERVER-CONTROLLED**.

**Example Request:**

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "admin"
  }
}
```

### Supported Column Filters (`search.columns`)

| Alias       | Type   | Example   | Semantics                       |
|-------------|--------|-----------|---------------------------------|
| `id`        | int    | `12`      | exact match                     |
| `name`      | string | `"admin"` | `LIKE %value%`                  |
| `group`     | string | `"users"` | `LIKE %value%`                  |
| `is_active` | string | `"1"`     | `"1"` (Active) / `"0"` (Inactive) |

### Response Model

```json
{
  "data": [
    {
      "role_id": 3,
      "role_name": "permissions.manage",
      "display_name": "Permissions Manager",
      "is_active": true
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 25,
    "total": 4,
    "filtered": 1
  }
}
```

---

## 4) Admins with Direct Overrides (Read-Only)

**Endpoint:** `POST /api/permissions/{id}/admins/query`
**Capability:** `can_view_admins_tab`

### Request — Specifics

*   **Global Search:** Matches **admin_display_name**.
*   **Sorting:** ⚠️ **SERVER-CONTROLLED**.

**Example Request:**

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "john"
  }
}
```

### Supported Column Filters (`search.columns`)

| Alias        | Type   | Example | Semantics                       |
|--------------|--------|---------|---------------------------------|
| `admin_id`   | int    | `12`    | exact match                     |
| `is_allowed` | string | `"1"`   | `"1"` (Allowed) / `"0"` (Denied)|

### Response Model

```json
{
  "data": [
    {
      "admin_id": 12,
      "admin_display_name": "John Doe",
      "is_allowed": true,
      "expires_at": null,
      "granted_at": "2026-01-25 14:32:10"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 25,
    "total": 5,
    "filtered": 1
  }
}
```

---

## 5) Implementation Checklist (Permission Details)

*   [ ] **Read-Only**: This page is strictly for viewing relationships.
*   [ ] **Navigation**: Use capabilities to conditionalize links to Admin Profile / Role Details.
*   [ ] **Context**: This view shows *who* has this permission, not *what* this permission does.
