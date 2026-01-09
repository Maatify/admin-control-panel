# Phase V-2: Validation Contract Standardization Analysis

**Status:** DRAFT / ANALYSIS ONLY
**Date:** 2026-02-17
**Scope:** `app/Modules/Validation/Schemas/`
**Mode:** STRICT (Read-Only)

---

## 1. Executive Summary

The validation module is functionally stable but exhibits architectural drift across implementation phases. While the most recent addition, `SessionQuerySchema`, adheres strictly to the **Phase 14 Canonical Template**, older schemas (specifically `AdminListSchema` and `AdminNotificationHistorySchema`) diverge significantly.

Key inconsistencies involve **pagination keys** (`limit` vs `per_page`), **date range naming** (`from` vs `from_date`), and **filter structure** (top-level vs nested `filters` array). These discrepancies create a fragmented API contract.

There is **no immediate security risk**, as all inputs are validated, but the lack of standardization hampers frontend reusability and developer experience.

---

## 2. Findings Table

| Finding | Context | Severity | Risk | Standardization Strategy |
| :--- | :--- | :--- | :--- | :--- |
| **Pagination Key Mismatch** | `AdminNotificationHistorySchema` uses `limit`. Canonical uses `per_page`. | **MEDIUM** | Breaking | Standardize to `per_page` in future refactor. |
| **Date Key Mismatch** | `NotificationQuerySchema` uses `from/to`. `AdminNotificationHistorySchema` uses `from_date/to_date`. | **MEDIUM** | Breaking | Standardize to `from_date/to_date` (explicit). |
| **Filter Structure Violation** | `AdminListSchema` & `AdminNotificationHistorySchema` use top-level keys. Canonical uses `filters: {}`. | **HIGH** | Breaking | Standardize to nested `filters` array. |
| **Missing Max Constraints** | `AdminNotificationHistorySchema` lacks `max()` on limit. | **MEDIUM** | Non-Breaking | Enforce `max(100)` immediately (Security). |
| **Style Inconsistency** | Mix of inline `v::` and reusable `Rule` objects. | **LOW** | Non-Breaking | Low priority refactor. |

---

## 3. Explicit Non-Reuse Decisions

To prevent the propagation of legacy patterns, the following schemas are **tainted** and must **NOT** be used as templates for new work:

*   ❌ **`AdminListSchema`**: Violates Canonical Template (top-level filters).
*   ❌ **`AdminNotificationHistorySchema`**: Violates Pagination (`limit`) and Filter contracts.
*   ❌ **`NotificationQuerySchema`**: Ambiguous date keys (`from`/`to`) and lacks clear pagination structure.

**✅ Approved Template:**
*   `SessionQuerySchema` is the **only** approved reference for LIST/QUERY endpoints.

---

## 4. Recommendations

### Short Term (Security)
*   Update `AdminNotificationHistorySchema` to enforce `v::max(100)` on the `limit` field to mitigate potential DoS vectors.

### Medium Term (Refactor)
*   Migrate `AdminListSchema` and `AdminNotificationHistorySchema` to use the `PaginationDTO` structure:
    *   Rename `limit` -> `per_page`.
    *   Move search/filter fields into a `filters` array.
*   Standardize all date range filters to `from_date` and `to_date`.

### Long Term (Architecture)
*   Extract common pagination validation into a reusable `PaginationSchema` or Trait to enforce consistency at the code level.

---

## 5. What NOT to Change (Strict Prohibitions)

*   🛑 **`AuthLoginSchema`**: This schema correctly implements the **Transport Safety** rule (`CredentialInputRule`). It must **NOT** be modified to include complexity checks or password policies.
*   🛑 **`AdminCreateSchema`**: Correctly separates Policy from Transport. Do not merge logic.
*   🛑 **Legacy Logic**: Do not rename keys in `AdminListSchema` or `NotificationQuerySchema` without a coordinated frontend migration plan, as this would be a breaking change.

---
