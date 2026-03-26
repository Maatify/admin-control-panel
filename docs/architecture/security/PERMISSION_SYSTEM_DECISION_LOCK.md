# 🔒 Permission System — Decision Lock

## Status
FINAL — OVERRIDES ALL OTHER DOCUMENTS

---

## 1. Core Model

The system follows a **Route-Based Flexible Permission Model**.

- Route names MAY represent permissions
- Mapping is OPTIONAL and used only when needed
- Variants are VALID assignable permissions
- Transport-aware permissions MAY exist when intentionally designed

---

## 2. Valid Permission Resolution

A permission is considered **valid** if ANY of the following is true:

1. It exists directly in the database (`permissions` table)
2. It is resolved via `PermissionMapperV2`
3. It is part of a valid `anyOf` / `allOf` structure

---

## 3. Resolution is NOT Optional

If a permission cannot be resolved into a valid decision:

→ It MUST NOT proceed to authorization enforcement
→ It MUST be treated as a controlled authorization failure

---

## 4. Failure Semantics (CRITICAL)

Under NO circumstances should permission resolution failures cause:

- Runtime crashes
- Infrastructure exceptions
- HTTP 500 errors

ALL failures MUST result in:

→ HTTP 403 (NOT_AUTHORIZED)

---

## 5. Authorization Boundary

`AuthorizationService` is responsible ONLY for:

- Evaluating resolved permissions
- Handling valid permission inputs

It MUST NOT:

- Receive unresolved transport permissions
- Handle fallback logic
- Interpret route names

---

## 6. Middleware Responsibility

`AuthorizationGuardMiddleware` is the ONLY layer responsible for:

- Resolving route-based permissions
- Applying mapping (if exists)
- Validating resolution BEFORE enforcement

If resolution fails:

→ The request MUST be rejected immediately (403)

---

## 7. Mapping Rules

- Mapping is OPTIONAL
- Mapping is used for:
  - Deduplication (UI/API sharing)
  - Complex rules (`anyOf`, `allOf`)
- Mapping MUST NOT be required for every route

---

## 8. Variants

- Variants MAY exist in the database
- Variants MAY be assignable
- Variants MAY be used in UI logic

Variants MUST NOT:

→ Be enforced directly in AuthorizationService unless resolved intentionally

---

## 9. Transport Permissions

- `.api`, `.ui`, `.web` MAY exist
- They are NOT inherently invalid
- They MAY be:
  - Direct permissions (if seeded intentionally)
  - Mapped permissions
  - Legacy permissions

They MUST NOT:

→ Cause system failure if unresolved

---

## 10. Absolute Rule

> NO permission resolution failure may result in a system crash.

ALL unresolved cases MUST degrade safely into:

→ Authorization Denied (403)

---

## Summary

- Flexibility is ALLOWED
- Ambiguity is NOT ALLOWED
- Resolution MUST succeed OR fail safely
- System stability is PRIORITY over strict normalization
