# Permission Enforcement Rules

## Purpose
This document establishes the strict rules for permission classification, validation, and database storage within the system.

## 1. Database Seed Rules
The `permissions` table in the database MUST be the single source of truth for all assignable capabilities.

**Allowed in DB:**
- ✅ **Canonical Permissions:** Core business capabilities (e.g., `admin.create`, `sessions.revoke`).
- ✅ **Variant Permissions:** Explicit feature toggles or UI paths (e.g., `sessions.revoke.bulk`, `sessions.revoke.id`) are allowed if they represent specific assignable capabilities.
- ✅ **Approved Standalone Permissions:** Specific transport or interface permissions explicitly intended to bypass transport merging for legitimate architectural reasons (rare).

**Strictly Forbidden in DB:**
- ❌ **Transport Permissions:** Any permission key ending in `.api`, `.ui`, or `.web` MUST NOT be stored in the database unless explicitly approved as a standalone exception.

## 2. Transport Rules
Transport permissions define the method of execution but refer to the same underlying capability.

- ✅ **Mapping Requirement:** ALL transport permissions (`*.api`, `*.ui`, `*.web`) extracted from protected routes (via `AuthorizationGuardMiddleware`) MUST be explicitly mapped in `PermissionMapperV2`.
- ✅ **Normalization:** Transport permissions MUST normalize to exactly one Canonical Permission or a structured Array (e.g., `anyOf`/`allOf`) of Canonical Permissions.

## 3. Variant Rules
Variant permissions define specific forms or UI-driven toggles of a single business capability.

- ✅ **UI Logic Integration:** Variant permissions MUST map to a canonical permission OR be explicitly used in UI logic (`hasPermission` checks).
- ✅ **Normalization:** If a variant triggers an API route, it MUST resolve to its base Canonical Permission via the mapper (e.g., `sessions.revoke.id` → `sessions.revoke`).

## 4. Architectural Checks (CI Guidelines)
- All protected routes MUST be analyzed to extract required permission keys.
- Extracted permission keys MUST either:
  1. Exist in the database seed as a Canonical Permission.
  2. Exist in `PermissionMapperV2` and resolve to a valid Canonical Permission.
- Any unmapped `.api`, `.ui`, or `.web` permission is considered an explicit architectural violation.
- Any unapproved duplicate permissions found in the database seed are considered an explicit architectural violation.
