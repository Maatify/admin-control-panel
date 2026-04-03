# Permission Governance

## Status
FINAL — ALIGNED WITH DECISION LOCK

## Purpose
This document outlines the operational rules and standards governing the creation and assignment of permissions within the RBAC system.

## 1. Core Model & Flexibility
The system follows a flexible, route-based permission model.

- **Route names MAY be permissions:** Not all routes require mapping. A route name can directly map to a capability.
- **Mapping is OPTIONAL:** `PermissionMapperV2` is used for deduplication or complex requirement structures (`anyOf`, `allOf`), not as a mandatory bottleneck.
- **System Stability:** Flexibility and safe failure degradation take precedence over strict canonical-only normalization.

## 2. Canonical Capabilities
These are the primary actions a user can take.

- **Storage:** Stored in the `permissions` table.
- **Example:** `sessions.revoke`, `admin.create`.

## 3. Variant Permissions
Variant permissions represent behavioral forms, feature toggles, or UI logic branches for a given capability.

- **Validation:** Variants ARE valid, assignable permissions.
- **Storage:** Variants MAY exist directly in the database.
- **Usage:** They MAY be used in UI rendering logic to show/hide specific options (e.g., `sessions.revoke.bulk` vs `sessions.revoke.id`).

## 4. Transport Permissions
Transport permissions identify the HTTP vector (e.g., `.api`, `.ui`, `.web`).

- **Validity:** They MAY exist and MUST NOT be inherently treated as invalid.
- **Storage:** They SHOULD generally be mapped to a Canonical/Variant permission via `PermissionMapperV2` rather than stored in the database, though exceptions (like `auth.logout.web`) are permitted if explicitly designed.
- **Handling:** If a transport permission remains unresolved, the system MUST NOT crash. It MUST safely degrade to a 403 authorization failure.

## 5. Middleware and Service Governance

### 5.1 The Middleware
The `AuthorizationGuardMiddleware` owns resolution.

- It MUST intercept route permissions.
- It MUST resolve them against the `PermissionMapperV2`.
- It MUST perform final validation BEFORE passing them to the `AuthorizationService`.
- If resolution fails, it MUST throw a 403 `PermissionDeniedException`.

### 5.2 The Service
The `AuthorizationService` owns enforcement.

- It MUST receive valid, resolved permissions.
- It MUST evaluate those permissions against admin roles/assignments.
- It MUST NEVER receive unresolved transport permissions or attempt to handle fallback mappings.

## 6. Failure Guarantee
- Under NO circumstances should permission resolution failures result in an HTTP 500 or infrastructure exception.
- All unresolved permutations MUST result in HTTP 403.
