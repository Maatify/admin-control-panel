# Admin Permissions Management – API Documentation

**Frontend Reference**

---

## 🧭 Scope & Relationship

This document (`ADMIN-PERMISSIONS.md`) defines **Admin-Centric Permission Management**:

* Effective permissions (final RBAC resolution)
* Direct permissions (explicit allow / deny)
* Role-based permissions (read-only context)
* Assignable permissions (selection & assignment flow)

For **Role-Centric Permission Management**, refer to:

> **[ROLE-MANAGEMENT.md](ROLE-MANAGEMENT.md)**

> These two documents are **complementary**:
>
> * **Admin → Permissions** (this document)
> * **Role → Permissions** (`ROLE-MANAGEMENT.md`)

---

## Scope

This document describes:

* Admin Permissions page
* Tabs behavior and visibility
* Query APIs (pagination, filters, search)
* Assign / revoke direct permissions
* Assignable permissions flow (modal-based)
* Authorization & UI capability contract

All APIs follow the **canonical `/query` pagination pattern**.

---

## Page Context

### Admin Permissions Page

```
GET /admins/{adminId}/permissions
```

This page provides a **complete permission snapshot for a single admin**, split into logical tabs and contextual actions.

---

## Page Tabs Overview

| Tab Name              | Purpose                                      | Mutations |
|-----------------------|----------------------------------------------|-----------|
| Effective Permissions | Final RBAC result (roles + direct overrides) | ❌ No      |
| Direct Permissions    | Explicit allow / deny overrides              | ✅ Yes     |
| Roles (Context)       | Roles assigned to admin                      | ❌ No      |

> ❗ **Roles tab is informational only**
> Role assignment is managed elsewhere.

---

## Assignable Permissions (Important UX Rule)

**Assignable permissions are NOT a tab.**

They are loaded inside a **Modal / Drawer** triggered from the **Direct Permissions tab**.

Reasoning:

* Assignable permissions are:

    * Large
    * Paginated
    * Contextual
* Mixing them with Direct Permissions would:

    * Break pagination semantics
    * Confuse state (assigned vs assignable)

---

## Capabilities Object (UI Control Contract)

The backend injects a **capabilities object**.
The frontend **MUST NOT infer permissions**.

```json
{
  "can_view_permissions_effective": true,

  "can_view_admin_direct_permissions": true,
  "can_assign_admin_direct_permissions": true,
  "can_revoke_admin_direct_permissions": true,

  "can_view_admin_roles": true,

  "can_view_admin_profile": true,
  "can_view_admins": true
}
```

### UI Rules

* Tabs are shown only if the corresponding `can_view_*` is `true`
* Action buttons depend on `can_assign_*` / `can_revoke_*`
* Navigation links depend on `can_view_admin_profile`

---

# Tab 1: Effective Permissions

## Purpose

Shows the **final permission decision** after applying:

1. Role-based permissions
2. Direct permission overrides
3. Expiration rules

This is a **read-only snapshot**.

---

## Query Effective Permissions

### Endpoint

```
POST /api/admins/{adminId}/permissions/effective
```

### Required Capability

```
admin.permissions.effective
```

---

### Request Body

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "permissions",
    "columns": {
      "group": "admin"
    }
  }
}
```

---

### Global Search Behavior

`search.global` searches across:

* `permission.name`
* `permission.display_name`
* `permission.description`
* permission **group** (first segment before `.`)

📌 **Suggested placeholder**

> “Search by permission name, group, or description”

---

### Supported Column Filters

| Field | Description                      |
|-------|----------------------------------|
| id    | Permission ID                    |
| name  | Permission technical name        |
| group | First segment of permission name |

---

### Response

```json
{
  "data": [
    {
      "id": 42,
      "name": "admin.permissions.direct.assign",
      "group": "admin",
      "display_name": "Assign Direct Permissions",
      "description": "Allow assigning direct permissions to admins",
      "source": "direct_allow",
      "role_name": null,
      "is_allowed": true,
      "expires_at": null
    }
  ],
  "pagination": {
    "page": 1,
    "perPage": 25,
    "total": 180,
    "filtered": 12
  }
}
```

---

### Source Field Semantics

| source       | Meaning                 |
|--------------|-------------------------|
| role         | Granted via role        |
| direct_allow | Explicit allow override |
| direct_deny  | Explicit deny override  |

---

### UI Rules

* ❌ No action buttons
* ❌ No toggles
* ✔ Visual indicators only
* Expired direct permissions are **not shown**

---

# Tab 2: Direct Permissions

## Purpose

Manage **explicit overrides** for a specific admin.

* Allow or deny a permission
* Optional expiration
* Overrides role-based decisions

---

## Query Direct Permissions

### Endpoint

```
POST /api/admins/{adminId}/permissions/direct/query
```

### Required Capability

```
admin.permissions.direct.query
```

---

### Global Search Behavior

`search.global` searches:

* `permission.name`
* `permission.display_name`
* `permission.description`
* permission **group**

📌 **Suggested placeholder**

> “Search direct permissions by name or group”

---

### Supported Column Filters

| Field      | Description             |
|------------|-------------------------|
| id         | Permission ID           |
| name       | Permission name         |
| group      | Permission group        |
| is_allowed | `1` = allow, `0` = deny |

---

### Response

```json
{
  "data": [
    {
      "id": 42,
      "name": "admin.permissions.direct.assign",
      "group": "admin",
      "display_name": "Assign Direct Permissions",
      "description": "Allow assigning direct permissions",
      "is_allowed": true,
      "expires_at": "2026-12-31 23:59:59",
      "granted_at": "2026-02-01 20:11:44"
    }
  ],
  "pagination": {
    "page": 1,
    "perPage": 20,
    "total": 8,
    "filtered": 3
  }
}
```

---

## Assign / Update Direct Permission (Action Entry Point)

### UI Entry

* Button: **“+ Assign Permission”**
* Visible only if:

  ```
  can_assign_admin_direct_permissions = true
  ```

Clicking this button opens the **Assignable Permissions Modal**.

---

# Assignable Permissions (Modal / Drawer)

## Purpose

Provide a **paginated, searchable list of all system permissions**, annotated with assignment state for the current admin.

This view exists **only inside a modal**.

---

## Query Assignable Permissions

### Endpoint

```
POST /api/admins/{adminId}/permissions/direct/assignable/query
```

### Required Capability

```
admin.permissions.direct.assign
```

---

### Request Body

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "assign",
    "columns": {
      "group": "admin",
      "assigned": "0"
    }
  }
}
```

---

### Global Search Behavior

Searches:

* `permission.name`
* `permission.display_name`
* `permission.description`
* permission **group**

📌 Placeholder

> “Search available permissions”

---

### Supported Column Filters

| Field    | Description                                |
|----------|--------------------------------------------|
| id       | Permission ID                              |
| name     | Permission name                            |
| group    | Permission group                           |
| assigned | `1` = already assigned, `0` = not assigned |

---

### Response

```json
{
  "data": [
    {
      "id": 42,
      "name": "admin.permissions.direct.assign",
      "group": "admin",
      "display_name": "Assign Direct Permissions",
      "description": "Allow assigning direct permissions",
      "assigned": false,
      "is_allowed": null,
      "expires_at": null
    }
  ],
  "pagination": {
    "page": 1,
    "perPage": 25,
    "total": 180,
    "filtered": 60
  }
}
```

---

### UI Rules (Assignable Modal)

* Each row shows:

    * Permission info
    * Assignment state
* Actions per row:

    * **Assign** (if not assigned)
    * **Edit / Revoke** (if already assigned)
* Pagination is **modal-scoped**
* No client-side caching

---

## Assign Direct Permission

### Endpoint

```
POST /api/admins/{adminId}/permissions/direct/assign
```

### Required Capability

```
admin.permissions.direct.assign
```

---

### Request Body

```json
{
  "permission_id": 42,
  "is_allowed": true,
  "expires_at": "2026-12-31 23:59:59"
}
```

* `expires_at` is optional
* Format: `Y-m-d H:i:s`

---

### Response

```
204 No Content
```

---

## Revoke Direct Permission

### Endpoint

```
POST /api/admins/{adminId}/permissions/direct/revoke
```

### Required Capability

```
admin.permissions.direct.revoke
```

---

### Request Body

```json
{
  "permission_id": 42
}
```

---

### Response

```
204 No Content
```

---

### Revoke Rules

* Hard delete
* No soft state
* No client-side confirmation cache

---

# Tab 3: Roles (Context)

## Purpose

Show roles assigned to the admin for **context only**.

---

## Query Admin Roles

### Endpoint

```
POST /api/admins/{adminId}/roles/query
```

### Required Capability

```
admin.roles.query
```

---

### Global Search Behavior

Searches:

* `role.name`
* `role.display_name`
* `role.description`
* role group (first segment)

📌 Placeholder

> “Search roles by name or group”

---

### UI Rules

* ❌ No assign / unassign buttons
* ❌ No toggles
* ✔ Display-only table

---

# Navigation Rules

If:

```json
"can_view_admin_profile": true
```

Then allow links to:

```
/admins/{adminId}/profile
```

Otherwise, render text only.

---

# Frontend Implementation Checklist

* Use `capabilities` strictly
* Never infer permissions client-side
* Always send pagination params
* Assignable permissions live **only in modal**
* Re-fetch Effective + Direct tabs after mutation
* Do not cache permission state
* Respect read-only vs mutable contexts

---

## End of Document
