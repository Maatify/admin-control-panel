# Roles Management (Advanced) — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / Authorization`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is a **runtime integration contract** for the Role Details Page (Permissions & Admins assignment).
It extends the core `ROLES.md`.

It answers, precisely:
*   How to list and filter permissions within a role context.
*   How to assign/unassign permissions.
*   How to list and filter admins within a role context.
*   How to assign/unassign admins.

### ⚠️ CRITICAL: UI vs API Distinction

*   **`GET /roles/{id}`**
    *   ❌ **This is NOT an API.**
    *   ✅ This is the **browser entry point** that renders the Role Details HTML page.
    *   It returns `text/html`.

*   **`POST /api/roles/{id}/*`**
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
  ├─ loads role entity
  ├─ injects capabilities (permissions & admins view/assign)
  └─ renders role details page

JavaScript
  ├─ Tab Switcher (Permissions / Admins)
  ├─ DataTable (Permissions Query with 'assigned' filter)
  ├─ DataTable (Admins Query with 'assigned' filter)
  └─ Actions (Assign / Unassign)
```

---

## 2) Capabilities (Authorization Contract)

The UI receives these **Role Details-specific capability flags**:

### 2.1 Injected Flags

```php
$capabilities = [
    // Overview
    'can_view_roles'            => $hasPermission('roles.query'),

    // Permissions tab
    'can_view_permissions'      => $hasPermission('roles.permissions.view'),
    'can_assign_permissions'    => $hasPermission('roles.permissions.assign'),
    'can_unassign_permissions'  => $hasPermission('roles.permissions.unassign'),

    // Admins tab
    'can_view_admin_profile'    => $hasPermission('admins.profile.view'),
    'can_view_admins'           => $hasPermission('roles.admins.view'),
    'can_assign_admins'         => $hasPermission('roles.admins.assign'),
    'can_unassign_admins'       => $hasPermission('roles.admins.unassign'),
];
```

### 2.2 Capability → UI Behavior Mapping

| Capability                 | UI Responsibility                                  |
|----------------------------|----------------------------------------------------|
| `can_view_permissions`     | Show/hide **Permissions Tab**                      |
| `can_assign_permissions`   | Enable **Assign** action (on unassigned items)     |
| `can_unassign_permissions` | Enable **Unassign** action (on assigned items)     |
| `can_view_admins`          | Show/hide **Admins Tab**                           |
| `can_assign_admins`        | Enable **Assign** action (on unassigned admins)    |
| `can_unassign_admins`      | Enable **Unassign** action (on assigned admins)    |
| `can_view_admin_profile`   | Enable link to Admin Profile from table            |

---

## 3) List Role Permissions (table)

**Endpoint:** `POST /api/roles/{id}/permissions/query`
**Capability:** `can_view_permissions`

### Request — Specifics

*   **Global Search:** Matches against **permission name** (`roles.view`).
*   **Sorting:** ⚠️ **SERVER-CONTROLLED**.
*   **Assigned Filter:** Use `assigned` column (`"1"` or `"0"`) to show only assigned or unassigned permissions.

**Example Request:**

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "roles",
    "columns": {
      "assigned": "1"
    }
  }
}
```

### Supported Column Filters (`search.columns`)

| Alias      | Type   | Example   | Semantics                          |
|------------|--------|-----------|------------------------------------|
| `id`       | int    | `12`      | exact match                        |
| `name`     | string | `"roles"` | `LIKE %value%`                     |
| `group`    | string | `"admin"` | `LIKE %value%`                     |
| `assigned` | string | `"1"`     | `"1"` (Assigned) / `"0"` (Unassigned) |

### Response Model

```json
{
  "data": [
    {
      "id": 12,
      "name": "roles.permissions.assign",
      "display_name": "Assign Permissions",
      "description": "Allow assigning permissions to roles",
      "assigned": true
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 25,
    "total": 184,
    "filtered": 12
  }
}
```

**Pagination Meanings:**
*   `total`: total records in DB (no filters)
*   `filtered`: total records after applying `search.global` and/or `search.columns`
*   When no filters are applied, `filtered` MAY equal `total`.

---

## 4) Assign Permission to Role

**Endpoint:** `POST /api/roles/{id}/permissions/assign`
**Capability:** `can_assign_permissions`

### Request Body

*   `permission_id` (int, required)

**Example:**
```json
{
  "permission_id": 12
}
```

### Response

*   ✅ **204 No Content**
*   ❌ **409** if already assigned.

---

## 5) Unassign Permission from Role

**Endpoint:** `POST /api/roles/{id}/permissions/unassign`
**Capability:** `can_unassign_permissions`

### Request Body

*   `permission_id` (int, required)

**Example:**
```json
{
  "permission_id": 12
}
```

### Response

*   ✅ **204 No Content**
*   ❌ **404** if not assigned.

---

## 6) List Role Admins (table)

**Endpoint:** `POST /api/roles/{id}/admins/query`
**Capability:** `can_view_admins`

### Request — Specifics

*   **Global Search:** Matches against **display_name** OR **status**.
*   **Assigned Filter:** Use `assigned` column (`"1"` or `"0"`).

**Example Request:**

```json
{
  "page": 1,
  "per_page": 20,
  "search": {
    "columns": {
      "assigned": "1"
    }
  }
}
```

### Supported Column Filters (`search.columns`)

| Alias      | Type   | Example    | Semantics                          |
|------------|--------|------------|------------------------------------|
| `id`       | int    | `5`        | exact match                        |
| `status`   | string | `"ACTIVE"` | exact match (enum)                 |
| `assigned` | string | `"1"`      | `"1"` (Assigned) / `"0"` (Unassigned) |

### Response Model

```json
{
  "data": [
    {
      "id": 5,
      "display_name": "Ahmed Hassan",
      "status": "ACTIVE",
      "assigned": true
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 43,
    "filtered": 1
  }
}
```

**Pagination Meanings:**
*   `total`: total records in DB (no filters)
*   `filtered`: total records after applying `search.global` and/or `search.columns`
*   When no filters are applied, `filtered` MAY equal `total`.

---

## 7) Assign Admin to Role

**Endpoint:** `POST /api/roles/{id}/admins/assign`
**Capability:** `can_assign_admins`

### Request Body

*   `admin_id` (int, required)

**Example:**
```json
{
  "admin_id": 5
}
```

### Response

*   ✅ **204 No Content**

---

## 8) Unassign Admin from Role

**Endpoint:** `POST /api/roles/{id}/admins/unassign`
**Capability:** `can_unassign_admins`

### Request Body

*   `admin_id` (int, required)

**Example:**
```json
{
  "admin_id": 5
}
```

### Response

*   ✅ **204 No Content**

---

## 9) Implementation Checklist (Role Management)

*   [ ] **Never send `sort`** to query endpoints.
*   [ ] Use `assigned="1"` filter to show "My Permissions" or "My Admins".
*   [ ] Use `assigned="0"` filter to show "Available Permissions" or "Available Admins".
*   [ ] Refresh table after Assign/Unassign (204 response).
