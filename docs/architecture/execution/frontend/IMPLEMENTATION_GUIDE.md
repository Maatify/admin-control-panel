# IMPLEMENTATION_GUIDE.md
## Feature Implementation Guide (v2 bridge-first)

This guide defines the default path for implementing new admin frontend features.

---

## 1) Start with the smallest safe v2 shape

Before writing files, choose the minimum module shape that can safely handle the feature:

- Single page module + family helper seam (default)
- Add a second module only when interaction complexity requires it
- Expand further only when needed

Avoid pre-emptive splitting into `core/modals/actions` unless the page complexity already justifies it.

---

## 2) Define runtime contracts in Twig

In the Twig page template:

1. Inject capabilities in content scope so they exist before page JS initializes.
2. Define table container contract:
   - default: `id="table-container"`
   - custom: set `window.{feature}TableContainerId = 'your-id'`
3. Load scripts in a bridge-first order (bridge + helper + feature entry module).

---

## 3) Build feature family helper seam

Create or extend a helper for the feature family (for example: roles/domains/scopes family):

- Query param builders
- Action button builders
- Row-level UI decisions
- Shared formatting helpers

The entry module should orchestrate, not contain all domain logic.

---

## 4) Implement page entry module

In the entry module:

- Validate `AdminPageBridge` and required helper existence.
- Store page state (`page`, `perPage`, filters, container id).
- Use `Bridge.API.execute` / `Bridge.API.runMutation` for data and mutations.
- Use `Bridge.Table` helpers (`bindActionState`, `withTargetContainer`, `reload`) for table lifecycle wiring.
- Escape all user-sourced text with `Bridge.Text.escapeHtml`.
- Export a `window.reload{Feature}Table` hook for mutation flows.

---

## 5) Pagination and event scoping

Handle `tableAction` carefully:

- Read `event.detail.action` and `event.detail.value`
- When container identifiers are present, ignore events not for this table
- Reset page to 1 when `perPage` or filters change

This is required on pages with more than one interactive table.

---

## 6) API usage rules

- Prefer bridge API wrappers (`Bridge.API.execute` / `Bridge.API.runMutation`) for standard calls
- Do not prepend `/api/` manually when using project API helpers
- Only bypass helper wrappers when technically required (example: multipart upload edge cases)

---

## 7) Definition of done checklist

A feature is done when all are true:

- [ ] Twig injects capabilities before page module runs
- [ ] Table container contract is explicit (default or custom global id)
- [ ] Feature logic uses helper seam (no monolithic page-only business logic)
- [ ] API calls use bridge/helper path
- [ ] Rendered user text is escaped via bridge text helper
- [ ] `tableAction` handling is correctly scoped
- [ ] `window.reload{Feature}Table` exists for post-mutation refresh

---

## Legacy note

Older implementations may still use split files and direct `ApiHandler` patterns. Keep those stable when required, but for new work and rewrites, adopt this v2 bridge-first guide.
