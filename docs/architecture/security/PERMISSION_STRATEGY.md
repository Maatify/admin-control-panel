# Permission Strategy

**Project:** maatify/admin-control-panel  
**Scope:** Authorization Kernel – Permission Resolution  
**Status:** Active / Canonical  
**Audience:** Core Developers & Reviewers  

---

## 1. Purpose

This document defines **how permissions are interpreted and resolved** inside the system.

It exists to:

- Prevent duplication of permissions across UI and API
- Decouple routing concerns from authorization logic
- Allow internal normalization without changing permission semantics
- Keep RBAC rules **stable** while routes evolve

---

## 2. Core Principle

> **Permissions represent capabilities, not routes.**

A single capability may be exposed through:
- UI endpoints
- API endpoints
- Multiple HTTP methods
- Multiple route names

All of these MUST resolve to **one canonical permission**.

---

## 3. Canonical Permission

A **canonical permission** is:

- Stored in the database
- Assigned to roles or directly to admins
- Used as the final authority in authorization checks

Examples:

```text
admins.list
admin.create
sessions.list
sessions.revoke
```

These are **stable identifiers** and MUST NOT depend on:

* Route names
* Transport layer (UI / API)
* HTTP method

---

## 4. Permission Mapping Layer

To support multiple route representations, the system introduces a **Permission Mapping Layer**.

### Responsibility

* Translate contextual permission identifiers into canonical permissions
* Act as a normalization step only
* Contain **no authorization logic**

### Explicitly NOT responsible for

* Role evaluation
* Ownership checks
* Permission existence rules
* Access decisions

---

## 5. PermissionMapper Contract

The mapping layer is abstracted via:

```php
PermissionMapperInterface
```

This ensures:

* The authorization core does not depend on route naming
* Mapping rules can evolve independently
* Future strategies can replace or extend mapping logic

---

## 6. Resolution Flow

The authorization process follows this order:

1. Receive permission identifier (usually route name)
2. Normalize permission via PermissionMapper
3. Validate canonical permission existence
4. Resolve ownership / role / direct permissions
5. Grant or deny access

```text
Route / Context
      ↓
PermissionMapper
      ↓
Canonical Permission
      ↓
Authorization Decision
```

---

## 7. Current Mapping Strategy

The default implementation maps:

* UI and API routes → single canonical permission
* Granular routes → shared capability

Example:

```text
admins.list.ui
admins.list.api
        ↓
admins.list
```

This avoids duplicating permissions for the same logical action.

---

## 8. Design Constraints (LOCKED)

* ❌ No permission logic inside routes
* ❌ No authorization branching based on transport layer
* ✅ Mapping MUST occur before permission existence checks
* ✅ AuthorizationService MUST evaluate ONLY canonical permissions (after mapping)

---

### 🔒 Canonical Merge Rule

If multiple permission identifiers represent the same capability
(even if they differ in execution context such as bulk or single actions),
they MUST be normalized into a single canonical permission.

Example:

sessions.revoke.bulk
sessions.revoke.id
        ↓
sessions.revoke

Only the canonical permission MUST exist in the database.

Mapping in this case represents a capability merge, not just transport normalization.

---

### ⚠️ Transport-Aware Permissions (Legacy Behavior)

Existing systems MAY include transport-aware permissions
(e.g. `.api`, `.ui`, `.web`) as part of legacy or pre-normalization behavior.

These permissions:

* MUST be treated as valid runtime permissions
* MUST NOT be removed or renamed without a full migration plan
* MAY exist in the database if directly used by the system

---

### 🚫 New Code Constraint

Transport-aware permissions SHOULD NOT be introduced in new code.

All new routes MUST resolve to canonical permissions via the mapping layer.

---

## 9. Authorization Exceptions (Intentional Behavior)

The system MAY include specific routes that intentionally bypass
the permission-based authorization layer.

These routes are:

- Protected by authentication (SessionGuard)
- NOT evaluated through AuthorizationService
- Considered safe within the current system phase

### Examples

- Dashboard and base UI routes (e.g. `/`, `/dashboard`)
- General UI views (e.g. `/settings`, `/examples`)

### Rationale

- These routes do not represent distinct business capabilities
- They serve as entry points or UI containers
- Access is granted to any authenticated admin by design

### Constraints

- Such routes MUST NOT perform sensitive operations
- They MUST NOT expose restricted data beyond admin baseline access
- They MUST remain minimal and UI-oriented

### Future Evolution

In stricter RBAC modes, these routes MAY be converted into
permission-based endpoints (e.g. `dashboard.view`).

---

## 10. Non-Goals

This document does NOT define:

* RBAC schema design
* Permission grouping strategy
* UI permission visibility rules
* Future refactor plans

Those are handled in their respective documents.

---

## 11. Summary

* Routes are **implementation details**
* Permissions are **business capabilities**
* Mapping is the boundary between them
* Authorization logic remains clean, stable, and future-proof
