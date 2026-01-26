## 🔐 Roles Management API

This section documents **Roles APIs** used to **list roles, manage their UI metadata,
control role activation, and rename technical role keys**, and defines how
**UI capabilities** are computed and consumed.

> ℹ️ Technical role keys (`roles.name`) are **immutable by default** and can **only**
> be changed through the dedicated **Rename API**
>
> ℹ️ Role creation, permission assignment, admin binding, and lifecycle management are **NOT part of this API**
>
> ℹ️ All routes below are prefixed with `/api`.

---

### 🧩 UI Authorization & Capabilities Model

The UI does **NOT** perform authorization.

Authorization decisions are made **server-side** using the `AuthorizationService`.
The backend computes **capabilities** for the current admin and injects them into the Twig view.

These capabilities are used by **Twig and JavaScript for presentation only**
(show / hide / enable / disable UI controls).

> ⚠️ Hiding UI elements does **NOT** replace API authorization
> ⚠️ All API endpoints must still enforce permissions server-side

---

#### Capability Injection (Backend → Twig)

In the UI controller:

```php
$capabilities = [
    'can_create'       => $authorizationService->hasPermission($adminId, 'roles.create'),
    'can_update_meta' => $authorizationService->hasPermission($adminId, 'roles.metadata.update'),
    'can_rename'      => $authorizationService->hasPermission($adminId, 'roles.rename'),
    'can_toggle'      => $authorizationService->hasPermission($adminId, 'roles.toggle'),
];

return $this->view->render($response, 'pages/roles.twig', [
    'capabilities' => $capabilities
]);
```

---

#### Usage in Twig

```twig
{% if capabilities.can_rename %}
  <button class="rename-role">Rename</button>
{% endif %}
```

---

#### Usage in JavaScript

```twig
<script>
  window.rolesCapabilities = {{ capabilities|json_encode|raw }};
</script>
```

```js
if (!window.rolesCapabilities.can_rename) {
  document.querySelectorAll('.rename-role').forEach(el => el.remove());
}
```

---

#### UI Rules (Mandatory)

* ❌ Twig MUST NOT check permissions by name
* ❌ JavaScript MUST NOT infer authorization
* ❌ UI MUST NOT assume API access
* ✅ Backend capabilities are the single UI contract
* ✅ API authorization is always enforced server-side

---

### 📋 List Roles

Returns a paginated list of all roles with derived grouping and UI metadata.

#### Endpoint

```http
POST /api/roles/query
```

**Auth Required:** Yes
**Permission:** `roles.query`

---

#### Response — 200 OK

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
  ]
}
```

---

### ✏️ Update Role Metadata

Updates **UI metadata only** for a role.

#### Endpoint

```http
POST /api/roles/{id}/metadata
```

**Permission:** `roles.metadata.update`

---

### 🔄 Toggle Role Activation

Controls whether a role participates in **authorization decisions**.

#### Endpoint

```http
POST /api/roles/{id}/toggle
```

**Permission:** `roles.toggle`

---

### ✏️ Rename Role (Technical Key)

Renames the **technical role key** (`roles.name`).

This is a **high-impact administrative operation** and must be performed
**explicitly and deliberately**.

---

#### Endpoint

```http
POST /api/roles/{id}/rename
```

**Auth Required:** Yes
**Permission:** `roles.rename`

---

#### Request Body

```json
{
  "name": "admins.super_manage"
}
```

---

#### Validation Rules

* `name` is required
* Must be a non-empty string
* Must follow the canonical format:

```text
<group>.<action>[.<sub_action>]
```

Examples:

* `admins.manage`
* `admins.super.manage`
* `roles.metadata.update`

---

#### Behavior Rules

* Updates **only** the technical role key (`roles.name`)
* Does **NOT** modify:

  * role metadata
  * role activation state
  * role-permission mappings
  * admin-role assignments
* Existing bindings remain valid
* Operation is **idempotent**
* No cascading side effects
* No authorization recalculation is triggered automatically

---

#### Authorization Impact

* All permissions referencing the role **immediately resolve** to the new name
* Admins bound to the role remain bound
* Disabled roles remain disabled

---

#### Responses

**200 OK**

```json
{}
```

---

#### Possible Errors

| Code | Reason                   |
|------|--------------------------|
| 403  | Permission denied        |
| 409  | Role name already exists |
| 500  | Role not found           |
| 500  | Rename operation failed  |

---

### 📊 Role Fields

| Field          | Description                      | Mutable           |
|----------------|----------------------------------|-------------------|
| `id`           | Internal role identifier         | ❌                 |
| `name`         | Technical role key               | ✅ *(rename only)* |
| `group`        | Derived from `name`              | ❌                 |
| `display_name` | UI label                         | ✅                 |
| `description`  | UI help text                     | ✅                 |
| `is_active`    | Authorization participation flag | ✅ *(toggle only)* |

---

### 🧠 Design Principles

* Roles are **RBAC aggregators**, not lifecycle entities
* Technical keys are **stable identifiers**
* Rename is **explicit, isolated, and auditable**
* UI metadata is **presentation-only**
* Authorization logic is **fully decoupled**
* No role deletion via API
* No permission assignment via this API

---

### 🔒 Status

**LOCKED — Roles Query, Metadata, Toggle & Rename Contract**

Any change requires updating:

* Controllers
* Repository contracts
* Validation schemas
* Authorization mapping
* UI capabilities
* This documentation

---

### ✅ Current Implementation Status

| Feature                  | Status |
|--------------------------|--------|
| Roles listing            | ✅ DONE |
| Metadata update API      | ✅ DONE |
| Role activation toggle   | ✅ DONE |
| Role rename API          | ✅ DONE |
| UI capabilities contract | ✅ DONE |
| Role creation            | ⏳ NEXT |
| Role-permission mapping  | ⏳ NEXT |
| Admin-role assignment    | ⏳ NEXT |

---
