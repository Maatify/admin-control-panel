# Permission Enforcement Rules

## Status
FINAL — ALIGNED WITH DECISION LOCK

## Purpose
This document establishes the strict rules for permission validation, resolution, and authorization enforcement within the system.

## 1. Route-Based Model & Mapping
The system operates on a flexible route-based permission model.

- Route names MAY represent permissions directly.
- Mapping via `PermissionMapperV2` is OPTIONAL.
- `PermissionMapperV2` MAY be used to map transport-aware routes (e.g., `*.api`) to core permissions to reduce duplication or to define complex `anyOf`/`allOf` arrays.

## 2. Valid Permission Definition
A permission is considered VALID and Enforceable if ANY of the following is true:

1. It exists directly in the `permissions` table in the database.
2. It is mapped via `PermissionMapperV2` to an existing database permission.
3. It is part of an `anyOf`/`allOf` array containing valid permissions.

## 3. Variant Permissions
Variant permissions represent behavioral pathways, explicit toggles, or UI forms (e.g., `sessions.revoke.bulk`, `sessions.revoke.id`).

- Variants ARE valid assignable permissions.
- Variants MAY exist in the database.
- Variants MAY be used directly in UI rendering logic (`hasPermission`).

## 4. Transport Permissions
Transport permissions indicate the specific route context (`.api`, `.ui`, `.web`).

- Transport permissions MAY exist.
- They MUST NOT be treated as inherently invalid or crash the system.
- While they SHOULD NOT typically be stored in the database, doing so is permitted if explicitly intended by the architecture (e.g., `auth.logout.web`).

## 5. Resolution & Middleware Responsibility
The `AuthorizationGuardMiddleware` is the EXCLUSIVE layer responsible for resolving route-based permissions.

- It MUST attempt to resolve permissions using `PermissionMapperV2` before authorization.
- If resolution fails, the request MUST be rejected immediately.

## 6. Authorization Boundary
The `AuthorizationService` enforces final access decisions.

- `AuthorizationService` MUST ONLY receive resolved, valid permissions.
- It MUST NOT receive unresolved transport permissions.
- It MUST NOT perform fallback resolution logic.

## 7. Failure Semantics (CRITICAL)
Under NO circumstances should permission resolution failures cause runtime crashes, infrastructure exceptions, or HTTP 500 errors.

- ALL resolution and validation failures MUST degrade safely to an HTTP 403 (NOT_AUTHORIZED) response.
