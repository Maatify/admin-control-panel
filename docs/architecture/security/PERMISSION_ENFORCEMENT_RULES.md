# Permission Enforcement Rules

## Purpose
This document establishes the strict rules for permission classification, validation, and database storage within the system.

## 1. Database Seed Rules
The `permissions` table in the database MUST be the single source of truth for all assignable capabilities.

**Allowed in DB:**
- ✅ **Canonical Permissions:** Core business capabilities (e.g., `admin.create`, `sessions.revoke`).
- ✅ **Approved Standalone Permissions:** Specific transport or interface permissions explicitly intended to bypass transport merging for legitimate architectural reasons (rare).

**Strictly Forbidden in DB:**
- ❌ **Transport Permissions:** Any permission key ending in `.api`, `.ui`, or `.web` MUST NOT be stored in the database unless explicitly approved as a standalone exception.
- ❌ **Variant (Merged) Permissions:** Specific action variants like `sessions.revoke.bulk` or `sessions.revoke.id` MUST NOT be stored in the database. They must be normalized to their canonical form.

## 2. Transport Rules
Transport permissions define the method of execution but refer to the same underlying capability.

- ✅ **Mapping Requirement:** ALL transport permissions (`*.api`, `*.ui`, `*.web`) extracted from protected routes (via `AuthorizationGuardMiddleware`) MUST be explicitly mapped in `PermissionMapperV2`.
- ✅ **Normalization:** Transport permissions MUST normalize to exactly one Canonical Permission or a structured Array (e.g., `anyOf`/`allOf`) of Canonical Permissions.

## 3. Variant Rules
Variant permissions define specific forms of a single business capability.

- ✅ **Mapping Requirement:** All variant permissions (e.g., specific actions on individual vs. bulk targets) MUST exist in the mapper.
- ✅ **Normalization:** Variants MUST resolve to their base Canonical Permission (e.g., `sessions.revoke.id` → `sessions.revoke`).

## 4. Architectural Checks (CI Guidelines)
- All protected routes MUST be analyzed to extract required permission keys.
- Extracted permission keys MUST either:
  1. Exist in the database seed as a Canonical Permission.
  2. Exist in `PermissionMapperV2` and resolve to a valid Canonical Permission.
- Any unmapped `.api`, `.ui`, or `.web` permission is considered an explicit architectural violation.
- Any duplicate or variant permissions found in the database seed are considered an explicit architectural violation.
