# Permission Strategy

## Status
FINAL — ALIGNED WITH DECISION LOCK

## Purpose
This document establishes the strategic vision for the permission system.

The system follows a **Route-Based Flexible Permission Model**. The primary objective is system stability, predictable authorization outcomes, and safe failure degradation.

## 1. Core Model
- **Route Names MAY Represent Permissions:** Routing identifiers can be direct business capabilities.
- **Mapping is OPTIONAL:** `PermissionMapperV2` is a utility for deduplication (sharing UI/API capabilities) or complex `anyOf`/`allOf` arrays. It is NOT required for every route.
- **System Stability:** Resolution MUST succeed OR fail safely. Flexibility is allowed, ambiguity is not.

## 2. Valid Permission Resolution
A permission is considered **valid** and enforceable if ANY of the following is true:

1. It exists directly in the `permissions` table in the database.
2. It is resolved via `PermissionMapperV2`.
3. It is part of a valid `anyOf` / `allOf` structure.

## 3. Variant Permissions
Variants define granular behavior or UI presentation of a capability (e.g., `sessions.revoke.bulk`, `sessions.revoke.id`).

- Variants ARE valid assignable permissions.
- Variants MAY exist in the database.
- Variants MAY be used directly in UI logic (`hasPermission` checks).

## 4. Transport Permissions
Transport permissions identify the HTTP vector (e.g., `.api`, `.ui`, `.web`).

- They MAY exist in the system and are NOT inherently invalid.
- They MAY be direct permissions (if seeded intentionally, e.g., `auth.logout.web`).
- They MUST NOT cause a system failure if unresolved.

## 5. Authorization Boundaries
- `AuthorizationService` is responsible ONLY for evaluating resolved permissions. It MUST NOT receive unresolved transport permissions or interpret route names.
- `AuthorizationGuardMiddleware` is the ONLY layer responsible for resolving route-based permissions, applying mapping, and validating resolution BEFORE enforcement.

## 6. Failure Semantics (CRITICAL)
- **Resolution is NOT Optional:** If a permission cannot be resolved into a valid decision, it MUST NOT proceed to authorization enforcement.
- **No System Crashes:** Under NO circumstances should permission resolution failures cause runtime crashes, infrastructure exceptions, or HTTP 500 errors.
- **Safe Degradation:** ALL unresolved cases MUST degrade safely into an HTTP 403 (NOT_AUTHORIZED) response.
