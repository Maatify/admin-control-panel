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
* ❌ No UI/API-specific permissions in the database
* ❌ No authorization branching based on transport layer
* ✅ Mapping MUST occur before permission existence checks
* ✅ AuthorizationService only works with canonical permissions

---

## 9. Non-Goals

This document does NOT define:

* RBAC schema design
* Permission grouping strategy
* UI permission visibility rules
* Future refactor plans

Those are handled in their respective documents.

---

## 10. Summary

* Routes are **implementation details**
* Permissions are **business capabilities**
* Mapping is the boundary between them
* Authorization logic remains clean, stable, and future-proof
