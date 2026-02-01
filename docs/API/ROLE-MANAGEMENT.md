# Roles Management – API Documentation

**Frontend Reference**

## 🧭 Scope & Relationship

This document (`ROLE-MANAGEMENT.md`) defines the **Advanced Role Operations** (Permission Assignment, Bulk Admin Assignment).

For **Core Role APIs** (CRUD, Listing, Metadata), refer to:

> **[ROLES.md](ROLES.md)**
>
> *`ROLE-MANAGEMENT.md` is an **extension** of `ROLES.md`.*
> *Both documents MUST be read together to understand the full Role API surface.*

---

## Scope

This document describes the APIs and UI rules for:

* Role Details page
* Permissions management
* Admins assignment to roles
* Pagination, filtering, and authorization handling

All APIs follow the **canonical `/query` pagination pattern**.

---

## Page Context

### Role Details Page

```
GET /roles/{roleId}
```

This page provides:

* Role overview data
* Permissions tab (if allowed)
* Admins tab (if allowed)

The backend provides a **capabilities object** that must be used by the frontend to control visibility and actions.

---

## Capabilities Object (UI Control Contract)

The frontend **must not infer permissions**.
All UI behavior must be driven by the following boolean flags:

```json
{
  "can_view_permissions": true,
  "can_assign_permissions": true,
  "can_unassign_permissions": true,

  "can_view_admin_profile": true,
  "can_view_admins": true,
  "can_assign_admins": true,
  "can_unassign_admins": true
}
```

### Frontend Responsibilities

* Tabs visibility depends on `can_view_*`
* Action buttons (assign / unassign / toggle) depend on the corresponding capability
* Profile links depend on `can_view_admin_profile`

---

# Permissions Tab

## Query Role Permissions (Paginated)

### Endpoint

```
POST /api/roles/{roleId}/permissions/query
```

### Required Capability

```
roles.permissions.view
```

---

### Request Body

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "roles",
    "columns": {
      "group": "permissions",
      "assigned": "1"
    }
  }
}
```

### Supported Filters

| Type   | Field         | Description                        |
|--------|---------------|------------------------------------|
| Global | search.global | Searches permission technical name |
| Column | id            | Permission ID                      |
| Column | name          | Permission technical name          |
| Column | group         | First segment of permission name   |
| Column | assigned      | `1` = assigned, `0` = not assigned |

---

### Response

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
    "perPage": 25,
    "total": 184,
    "filtered": 12
  }
}
```

### UI Usage Notes

* `assigned` determines toggle state
* Pagination data must be respected
* No client-side filtering or guessing

---

## Assign Permission to Role

### Endpoint

```
POST /api/roles/{roleId}/permissions/assign
```

### Required Capability

```
roles.permissions.assign
```

### Request Body

```json
{
  "permission_id": 12
}
```

### Response

```
204 No Content
```

---

## Unassign Permission from Role

### Endpoint

```
POST /api/roles/{roleId}/permissions/unassign
```

### Required Capability

```
roles.permissions.unassign
```

### Request Body

```json
{
  "permission_id": 12
}
```

### Response

```
204 No Content
```

---

# Admins Tab

## Query Admins for Role (Paginated)

### Endpoint

```
POST /api/roles/{roleId}/admins/query
```

### Required Capability

```
roles.admins.view
```

---

### Request Body

```json
{
  "page": 1,
  "per_page": 20,
  "search": {
    "global": "ACTIVE",
    "columns": {
      "assigned": "0"
    }
  }
}
```

### Supported Filters

| Type   | Field         | Description                        |
|--------|---------------|------------------------------------|
| Global | search.global | display_name or status             |
| Column | id            | Admin ID                           |
| Column | status        | ACTIVE / SUSPENDED / DISABLED      |
| Column | assigned      | `1` = assigned, `0` = not assigned |

---

### Response

```json
{
  "data": [
    {
      "id": 5,
      "display_name": "Ahmed Hassan",
      "status": "ACTIVE",
      "assigned": false
    }
  ],
  "pagination": {
    "page": 1,
    "perPage": 20,
    "total": 43,
    "filtered": 9
  }
}
```

---

## Assign Admin to Role

### Endpoint

```
POST /api/roles/{roleId}/admins/assign
```

### Required Capability

```
roles.admins.assign
```

### Request Body

```json
{
  "admin_id": 5
}
```

### Response

```
204 No Content
```

---

## Unassign Admin from Role

### Endpoint

```
POST /api/roles/{roleId}/admins/unassign
```

### Required Capability

```
roles.admins.unassign
```

### Request Body

```json
{
  "admin_id": 5
}
```

### Response

```
204 No Content
```

---

# Admin Profile Navigation

### Rule

If the frontend receives:

```json
"can_view_admin_profile": true
```

Then every admin item may link to:

```
/admins/{admin_id}/profile
```

### UI Behavior

* If capability is `false`, render plain text
* If capability is `true`, render clickable link

---

# Frontend Implementation Checklist

* Use `capabilities` object to control:

  * Tabs visibility
  * Action buttons visibility
  * Toggle availability
* Use `assigned` flag only for toggle state
* Always send pagination parameters
* Do not infer permissions or role state
* Do not cache assignment state client-side

---

## End of Document
