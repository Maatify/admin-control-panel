# Permission Governance

## Purpose

This document defines the strict governance rules controlling the growth, usage, and lifecycle of the three permission layers in the system: Canonical, Variant, and Transport. These rules ensure the RBAC architecture remains scalable, predictable, and free of duplication.

---

## 1. Canonical Rules

Canonical permissions represent the core, assignable business capabilities of the system.

### When to Create
- **New Feature Scope:** When a distinct new business capability is introduced that requires distinct authorization (e.g., managing a new entity).
- **Hard API Boundary:** When an action fundamentally differs in risk or domain from existing actions (e.g., viewing logs vs. deleting logs).

### Naming Conventions
- Must be domain-centric, not transport-centric.
- Format: `[domain].[action]` or `[domain].[subdomain].[action]`.
- Examples: `admin.create`, `sessions.revoke`, `languages.list`.
- **Must NOT** end in `.api`, `.ui`, `.web`, or specific execution vectors like `.bulk`.

### Ownership & Storage
- **Ownership:** Exclusively owned by the Domain logic and validated at the API/Service layer.
- **Storage:** MUST be seeded in `database/seeders/permissions_seed.sql` and stored in the `permissions` table.

---

## 2. Variant Rules

Variant permissions represent specific behavioral pathways, feature toggles, or UI actions of a single business capability.

### When Variant is Allowed
- **UI Distinctions:** When the UI needs to conditionally render distinct elements (e.g., a "Revoke All" button vs. a single "Revoke" button) for the *same* canonical capability.
- **Granular Toggling:** When specific administrative roles require access to a subset of an action's execution forms, but the underlying API capability remains identical.

### When Variant is NOT Allowed
- **API Enforcement:** Variants MUST NOT be used in the core service/domain layer for authorization checks (`checkPermission`). They are strictly for UI logic (`hasPermission`).
- **Fake Distinctions:** If the API endpoints require completely different authorization rules, they are distinct capabilities, not variants.

### Required Justification
Every variant must be justified by an explicit conditional branch in UI rendering logic or a frontend feature toggle.

### UI-Only vs API-Triggered Variants
- **UI-Only:** Used purely to show/hide sections (e.g., `admins.profile.edit.view`).
- **API-Triggered:** Triggers a specific route but maps back to a canonical permission (e.g., `sessions.revoke.bulk` triggers a bulk endpoint but requires the canonical `sessions.revoke` capability).

---

## 3. Transport Rules

Transport permissions represent the routing or execution method (API, UI, Web) of a capability.

### Strict Mapping Enforcement
- **ALL** transport permissions extracted from guarded routes MUST be mapped in `PermissionMapperV2`.
- They must resolve directly to a Canonical permission, a Variant permission, or an `anyOf`/`allOf` array.

### No DB Storage
- Transport keys (ending in `.api`, `.ui`, `.web`) MUST NOT be stored in the database.
- *Exception:* Explicitly approved standalone exceptions (e.g., `auth.logout.web`) that do not share logic with API counterparts.

---

## 4. Anti-Patterns

The following practices violate the governance model and are strictly prohibited:

- ❌ **Creating a variant without a UI need:** Introducing `users.delete.soft` when the UI simply has one "Delete" button that determines soft/hard deletion via payload.
- ❌ **Duplicating canonical as variant:** Creating `admin.create.single` when `admin.create` already perfectly describes the capability.
- ❌ **Using transport in DB:** Inserting `languages.list.api` into `permissions_seed.sql`.
- ❌ **Using variant in API logic:** A service calling `$auth->checkPermission($adminId, 'sessions.revoke.bulk')`. The API must only check the canonical `sessions.revoke`.

---

## 5. Decision Matrix

Given a new permission requirement, use this matrix to determine its classification:

| Scenario | Classification | Action |
| :--- | :--- | :--- |
| Represents a completely new business action (e.g., "Export Data") | **Canonical** | Add to DB seed (`export.data`), use in API service. |
| UI needs to hide a specific button for an existing action (e.g., "Export All") | **Variant** | Add to DB seed (`export.data.all`), use in UI `hasPermission`. Map route to Canonical. |
| A new endpoint is created to serve data for an existing UI view (e.g., `/api/export`) | **Transport** | DO NOT add to DB. Name route `export.data.api`, map to `export.data` in `PermissionMapperV2`. |

---

## 6. Real System Examples

### `sessions.revoke.*`
- **Canonical:** `sessions.revoke` (Used in `SessionBulkRevokeController` to enforce access).
- **Variant:** `sessions.revoke.bulk` and `sessions.revoke.id` (Stored in DB, used in `SessionListController` to toggle specific UI buttons).
- **Transport:** Not explicitly needed if routes match variants, but if an API existed like `sessions.revoke.api`, it would map to `sessions.revoke`.

### `roles.*`
- **Canonical:** `roles.view` (Core capability to view roles).
- **Transport:** `roles.view.ui` (The specific UI route to render the page, mapped to `roles.view` in `PermissionMapperV2`).

### `languages.*`
- **Canonical:** `languages.set.fallback` (Core capability).
- **Transport:** `languages.clear.fallback.api` and `languages.set.fallback.api` (Two distinct API routes mapped to the exact same canonical capability via `PermissionMapperV2`).
