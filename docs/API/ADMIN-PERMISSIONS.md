# Admin Permissions Management — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / Authorization`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is a **runtime integration contract** for the Admin Permissions Page.
It answers, precisely:
*   How to view the final (effective) permission state for an admin.
*   How to manage direct permission overrides (allow/deny).
*   How to view assigned roles for context.

### ⚠️ CRITICAL: UI vs API Distinction

*   **`GET /admins/{id}/permissions`**
    *   ❌ **This is NOT an API.**
    *   ✅ This is the **browser entry point** that renders the Admin Permissions HTML page.
    *   It returns `text/html`.

*   **`POST /api/admins/{id}/*`**
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
  ├─ injects capabilities
  ├─ renders admin permissions page
  └─ includes JS bundle

JavaScript
  ├─ Tab Switcher (Effective / Direct / Roles)
  ├─ DataTable (Effective Permissions)
  ├─ DataTable (Direct Permissions)
  ├─ Modal (Assignable Permissions)
  └─ Actions (Assign / Revoke)
```

---

## 2) Capabilities (Authorization Contract)

The UI receives these **Admin Permissions-specific capability flags**:

### 2.1 Injected Flags

```php
$capabilities = [
    // Overview
    'can_view_admin_roles'            => $hasPermission('admin.roles.query'),
    'can_view_permissions_effective'  => $hasPermission('admin.permissions.effective'),

    // Permissions tab
    'can_view_admin_direct_permissions'     => $hasPermission('admin.permissions.direct.query'),
    'can_assign_admin_direct_permissions'   => $hasPermission('admin.permissions.direct.assign'),
    'can_revoke_admin_direct_permissions'   => $hasPermission('admin.permissions.direct.revoke'),

    // Navigation
    'can_view_admin_profile'           => $hasPermission('admins.profile.view'),
    'can_view_admins'                  => $hasPermission('roles.admins.view'),
];
```

### 2.2 Capability → UI Behavior Mapping

| Capability                            | UI Responsibility                                  |
|---------------------------------------|----------------------------------------------------|
| `can_view_permissions_effective`      | Show/hide **Effective Permissions Tab**            |
| `can_view_admin_direct_permissions`   | Show/hide **Direct Permissions Tab**               |
| `can_assign_admin_direct_permissions` | Enable **Assign Permission** button (opens modal)  |
| `can_revoke_admin_direct_permissions` | Enable **Revoke** action on Direct Permissions row |
| `can_view_admin_roles`                | Show/hide **Roles Tab**                            |

---

## 3) Effective Permissions (Read-Only)

**Endpoint:** `POST /api/admins/{id}/permissions/effective`
**Capability:** `can_view_permissions_effective`

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

| Alias   | Type   | Example   | Semantics                          |
|---------|--------|-----------|------------------------------------|
| `id`    | int    | `12`      | exact match                        |
| `name`  | string | `"admin"` | `LIKE %value%`                     |
| `group` | string | `"users"` | `LIKE %value%`                     |

### Response Model

```json
{
  "data": [
    {
      "id": 42,
      "name": "admin.permissions.direct.assign",
      "group": "admin",
      "display_name": "Assign Direct Permissions",
      "description": "Allow assigning direct permissions",
      "source": "direct_allow",
      "role_name": null,
      "is_allowed": true,
      "expires_at": null
    }
  ],
  "pagination": { ... }
}
```

**Source Semantics:**
*   `role`: Granted via a role.
*   `direct_allow`: Explicitly allowed for this admin.
*   `direct_deny`: Explicitly denied for this admin.

---

## 4) Direct Permissions (Manage)

**Endpoint:** `POST /api/admins/{id}/permissions/direct/query`
**Capability:** `can_view_admin_direct_permissions`

### Request — Specifics

*   **Global Search:** Matches **name**, **display_name**, **description**, or **group**.
*   **Filter:** `is_allowed` ("1" for Allow, "0" for Deny).

**Example Request:**

```json
{
  "page": 1,
  "per_page": 20,
  "search": {
    "columns": {
      "is_allowed": "1"
    }
  }
}
```

### Supported Column Filters (`search.columns`)

| Alias        | Type   | Example | Semantics                       |
|--------------|--------|---------|---------------------------------|
| `id`         | int    | `42`    | exact match                     |
| `name`       | string | `"adm"` | `LIKE %value%`                  |
| `group`      | string | `"adm"` | `LIKE %value%`                  |
| `is_allowed` | string | `"1"`   | `"1"` (Allow) / `"0"` (Deny)    |

### Response Model

```json
{
  "data": [
    {
      "id": 42,
      "name": "admin.permissions.direct.assign",
      "group": "admin",
      "display_name": "Assign Direct Permissions",
      "description": "...",
      "is_allowed": true,
      "expires_at": "2026-12-31 23:59:59",
      "granted_at": "2026-02-01 20:11:44"
    }
  ],
  "pagination": { ... }
}
```

---

## 5) Assignable Permissions (Modal)

**Endpoint:** `POST /api/admins/{id}/permissions/direct/assignable/query`
**Capability:** `can_assign_admin_direct_permissions`

### Request — Specifics

*   **Global Search:** Matches **name**, **display_name**, **description**, or **group**.
*   **Filter:** `assigned` ("0" to find new permissions to assign).

**Example Request:**

```json
{
  "page": 1,
  "per_page": 10,
  "search": {
    "global": "assign",
    "columns": {
      "assigned": "0"
    }
  }
}
```

### Supported Column Filters (`search.columns`)

| Alias      | Type   | Example | Semantics                              |
|------------|--------|---------|----------------------------------------|
| `id`       | int    | `42`    | exact match                            |
| `name`     | string | `"adm"` | `LIKE %value%`                         |
| `group`    | string | `"adm"` | `LIKE %value%`                         |
| `assigned` | string | `"0"`   | `"1"` (Already Assigned) / `"0"` (New) |

---

## 6) Assign Direct Permission

**Endpoint:** `POST /api/admins/{id}/permissions/direct/assign`
**Capability:** `can_assign_admin_direct_permissions`

### Request Body

*   `permission_id` (int, required)
*   `is_allowed` (bool, required)
*   `expires_at` (string Y-m-d H:i:s, optional)

**Example:**
```json
{
  "permission_id": 42,
  "is_allowed": true,
  "expires_at": "2026-12-31 23:59:59"
}
```

### Response

*   ✅ **204 No Content**

---

## 7) Revoke Direct Permission

**Endpoint:** `POST /api/admins/{id}/permissions/direct/revoke`
**Capability:** `can_revoke_admin_direct_permissions`

### Request Body

*   `permission_id` (int, required)

**Example:**
```json
{
  "permission_id": 42
}
```

### Response

*   ✅ **204 No Content**

---

## 8) Admin Roles (Context)

**Endpoint:** `POST /api/admins/{id}/roles/query`
**Capability:** `can_view_admin_roles`

### Request — Specifics

*   **Global Search:** Matches **name**, **display_name**, **description**, or **group**.
*   **Filter:** `is_active`.

**Example Request:**

```json
{
  "page": 1,
  "per_page": 20,
  "search": {
    "global": "admin"
  }
}
```

### Supported Column Filters (`search.columns`)

| Alias       | Type   | Example | Semantics                       |
|-------------|--------|---------|---------------------------------|
| `id`        | int    | `1`     | exact match                     |
| `name`      | string | `"adm"` | `LIKE %value%`                  |
| `group`     | string | `"adm"` | `LIKE %value%`                  |
| `is_active` | string | `"1"`   | `"1"` (Active) / `"0"` (Inactive) |

---

## 9) Implementation Checklist (Admin Permissions)

*   [ ] **Modal Strategy**: Load "Assignable Permissions" only when modal opens.
*   [ ] **Pagination**: Modal must have its own independent pagination state.
*   [ ] **Refresh**: After Assign/Revoke, refresh "Direct" and "Effective" tabs.
*   [ ] **Expiration**: If `expires_at` is passed, format it as `Y-m-d H:i:s`.
