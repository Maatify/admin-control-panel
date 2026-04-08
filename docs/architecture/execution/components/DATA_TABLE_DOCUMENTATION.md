# Data Table Documentation (Canonical v2 Execution)

## 1. Component Reality

`data_table.js` primitives (`createTable`, `TableComponent`) still render through `#table-container` internally.

In canonical v2 execution, page modules should not treat this as a page-level limitation.
Use bridge/helper targeting wrappers at page level.

---

## 2. Page-Level Table Orchestration Boundary

For v2 pages:
- Use `AdminPageBridge.Table` as the page-level table orchestration boundary.
- Use family helpers (e.g., `I18nHelpersV2.withTableContainerTarget`) to safely target non-default containers.

This is the canonical way to reconcile component internals with per-page container contracts.

---

## 3. Canonical Decision Policy

1. If page uses default container and simple behavior:
   - `createTable(...)` is acceptable.
2. If page needs custom error handling, payload shaping, or non-default container:
   - use bridge/helper orchestration with `TableComponent(...)` or existing family patterns.
3. If endpoint is GET non-paginated report/list:
   - use API call + manual render path.

Do not bypass bridge/helper seam in new/edited v2 pages when scoped orchestration is available.

---

## 4. tableAction Event Rules (v2)

- `tableAction` payload includes source metadata (`tableContainerId`).
- Canonical handling for v2 pages is scoped binding via helper/bridge (`bindTableActionState` + source container filtering), not broad global listeners.

Example (scoped listener):
```javascript
document.addEventListener('tableAction', (event) => {
  const { action, value, tableContainerId } = event.detail || {};
  if (tableContainerId !== state.tableContainerId) return;
  if (action === 'pageChange') state.page = value;
  if (action === 'perPageChange') { state.per_page = value; state.page = 1; }
  reloadTable();
});
```

---

## 5. Container Contract Alignment

- Tier A (default): `#table-container`.
- Tier B (non-default): Twig injects explicit container-id global; page module uses helper-targeting wrapper.

This must be aligned with Twig standards and UI execution rules.

Example (non-default container targeting):
```javascript
AdminPageBridge.Table.withTargetContainer(window.rolesTableContainerId, () => {
  createTable('roles/query', params, headers, rows, false, 'id');
});
```

---

## 6. Superseded Note

Older docs that treat hardcoded `#table-container` as the only valid page-level pattern are superseded.
Canonical v2 execution uses bridge/helper targeting at the page layer.
