# Permission Details — APIs

These APIs provide **read-only access** to entities related to a single permission:

* Roles that use the permission
* Admins that have **direct overrides** for the permission

> **Important Notes**
>
> * All endpoints are **read-only**
> * Authorization is enforced via `AuthorizationGuardMiddleware`
> * UI behavior (visibility, navigation, clickable links) is controlled via **Capabilities**
> * APIs never infer permissions or UI logic

---

## 1️⃣ Query Roles Using a Permission

### Endpoint

```http
POST /api/permissions/{permission_id}/roles/query
```

---

### Description

Returns a paginated list of **roles that use a specific permission**.

* Paginated
* Filterable
* UI-driven
* Read-only

---

### Path Parameters

| Name          | Type | Description   |
|---------------|------|---------------|
| permission_id | int  | Permission ID |

---

### Request Body (Canonical List Query)

```json
{
  "page": 1,
  "per_page": 10,
  "search": {
    "global": "admin",
    "columns": {
      "group": "permissions",
      "is_active": "1"
    }
  }
}
```

All fields are optional.
Validated using `SharedListQuerySchema`.

---

### Supported Search & Filters

#### Global Search

Searches across:

* `name`
* `display_name`
* `description`
* permission group (derived from role name prefix)

#### Column Filters

| Column    | Type   | Description                    |
|-----------|--------|--------------------------------|
| id        | int    | Exact role ID                  |
| name      | string | Partial match on role name     |
| group     | string | Role group (prefix before `.`) |
| is_active | bool   | Active / inactive roles        |

---

### Response — 200 OK

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
    "perPage": 10,
    "total": 4,
    "filtered": 1
  }
}
```

---

### UI Behavior Notes

* Each row **may be clickable**
* Clicking a role ID or name should navigate to:

```text
GET /roles/{role_id}
```

* Navigation is allowed **only if** the UI capability
  `can_view_role_details === true`

The API **does not decide** navigation or visibility.

---

### Error Responses

| Status | Reason                     |
|--------|----------------------------|
| 404    | Permission not found       |
| 400    | Invalid list query payload |
| 403    | Authorization failed       |

---

## 2️⃣ Query Admins With Direct Permission Overrides

### Endpoint

```http
POST /api/permissions/{permission_id}/admins/query
```

---

### Description

Returns a paginated list of **admins who have direct overrides** for a permission.

* Includes allow/deny overrides
* Includes expiration metadata
* Paginated and filterable
* Read-only

---

### Path Parameters

| Name          | Type | Description   |
|---------------|------|---------------|
| permission_id | int  | Permission ID |

---

### Request Body (Canonical List Query)

```json
{
  "page": 1,
  "per_page": 10,
  "search": {
    "global": "john",
    "columns": {
      "is_allowed": "1"
    }
  }
}
```

---

### Supported Search & Filters

#### Global Search

Searches:

* `admin_display_name`

#### Column Filters

| Column     | Type | Description           |
|------------|------|-----------------------|
| admin_id   | int  | Exact admin ID        |
| is_allowed | bool | Allow / deny override |

---

### Response — 200 OK

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
    "perPage": 10,
    "total": 5,
    "filtered": 1
  }
}
```

---

### UI Behavior Notes

* Each admin row **may be clickable**
* Clicking an admin ID or name should navigate to:

```text
GET /admins/{admin_id}/profile
```

* Navigation is allowed **only if** the UI capability
  `can_view_admin_profile === true`

Again:

> The API **never enforces UI navigation** — it only returns data.

---

### Error Responses

| Status | Reason                     |
|--------|----------------------------|
| 404    | Permission not found       |
| 400    | Invalid list query payload |
| 403    | Authorization failed       |

---

## Summary

| Endpoint                             | Purpose                  |
|--------------------------------------|--------------------------|
| `/api/permissions/{id}/roles/query`  | Roles using a permission |
| `/api/permissions/{id}/admins/query` | Admin direct overrides   |

Both endpoints:

* Are read-only
* Use canonical list querying
* Are safe to consume directly by UI DataTables
* Delegate navigation & visibility to UI capabilities

---
