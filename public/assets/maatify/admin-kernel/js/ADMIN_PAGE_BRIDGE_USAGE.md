# Admin Page Bridge Usage

## Why this bridge exists

`AdminPageBridge` is a small facade layer on top of existing shared admin JS utilities.

It exists to:
- reduce repetitive page-level integration code
- give page authors one stable entry point
- keep page code focused on business flow instead of low-level utility wiring
- preserve backward compatibility by wrapping existing globals instead of replacing them

## Files added

- `public/assets/maatify/admin-kernel/js/admin-page-bridge.js`
- `public/assets/maatify/admin-kernel/js/ADMIN_PAGE_BRIDGE_USAGE.md`

## Global API exposed

`window.AdminPageBridge`

Main namespaces:
- `AdminPageBridge.DOM`
- `AdminPageBridge.API`
- `AdminPageBridge.UI`
- `AdminPageBridge.Modal`
- `AdminPageBridge.Table`
- `AdminPageBridge.Form`
- `AdminPageBridge.Events`

New high-value shared methods:
- `AdminPageBridge.Table.applyActionParams(...)`
- `AdminPageBridge.Table.bindActionState(...)`
- `AdminPageBridge.Table.withTargetContainer(...)`
- `AdminPageBridge.Events.bindFilterForm(...)`
- `AdminPageBridge.Events.createResetReload(...)`
- `AdminPageBridge.Events.bindDebouncedInput(...)`
- `AdminPageBridge.API.runMutation(...)`

## Logging contract (guaranteed)

All bridge lifecycle and operations log with prefix:
- `[AdminPageBridge]`

Lifecycle-oriented events emitted by the bridge:
- `loaded`
- `init`
- `bind`
- `action:start`
- `action:success`
- `action:failure`
- `api:start`
- `api:response`
- `api:parse-failed`
- `callback:received`
- `table:reload`

## Core usage pattern

Future page files should interact with shared behavior through `AdminPageBridge` first, instead of calling low-level globals directly.

### 1) DOM helpers

```javascript
const roleId = AdminPageBridge.DOM.int('#role-id');
const displayName = AdminPageBridge.DOM.value('#display-name', '');
const isEnabledByValue = AdminPageBridge.DOM.bool('#is-enabled-input', false);
const isChecked = AdminPageBridge.DOM.checked('#is-active-checkbox', false);
AdminPageBridge.DOM.setValue('#display-name', 'Support Agent');
AdminPageBridge.DOM.setValue('#is-active-checkbox', true);
```

`DOM.bool()` vs `DOM.checked()`:
- `DOM.checked()` reads the checkbox `.checked` state directly (`true/false`).
- `DOM.bool()` reads an element value and normalizes string/number forms such as `"1"`, `"true"`, `"yes"`, `1`.
- `DOM.setValue()` writes values safely for inputs/textarea/select and supports checkbox normalization and multi-select arrays.

Both DOM helpers support scoped lookup via options:

```javascript
const modal = document.getElementById('edit-role-modal');
const name = AdminPageBridge.DOM.value('#name', '', { root: modal });
```

### 2) Form payload helpers (with root/scope)

```javascript
const modal = document.getElementById('edit-role-modal');

const payload = AdminPageBridge.Form.collect({
  id: { selector: '#id', type: 'int' },
  name: { selector: '#name', type: 'value' },
  active: { selector: '#active', type: 'checked' }
}, { root: modal });

const cleanPayload = AdminPageBridge.Form.omitEmpty(payload);
```

### 3) API execution wrapper

```javascript
const result = await AdminPageBridge.API.execute({
  endpoint: 'roles/update',
  payload: cleanPayload,
  operation: 'Update Role',
  method: 'POST',
  showSuccessMessage: 'Role updated successfully',
  onSuccess: () => AdminPageBridge.Table.reload('reloadTableData')
});

if (!result.success) return;
```

API logging includes (when available from real shared utility contract):
- action name (`operation`)
- endpoint
- method
- payload
- content-type guess from raw response
- raw response when exposed by `ApiHandler.call`
- parsed JSON response when available
- parse-failure branch logging
- failure branch logging with normalized error output

### 4) Modal helpers

```javascript
AdminPageBridge.Modal.open('#edit-role-modal');
AdminPageBridge.Modal.close('#edit-role-modal', { resetForm: true });
```

`Modal` is intentionally basic: it is a visibility/reset helper wrapper (`hidden` class + body overflow reset), not a universal modal engine.

### 5) Table reload helper (standard + fallback)

```javascript
// Preferred standard: explicit handler
AdminPageBridge.Table.reload(reloadTableData);

// Also supported: explicit global function name
AdminPageBridge.Table.reload('reloadTableData');

// Secondary convenience only: no arg => heuristic fallback
AdminPageBridge.Table.reload();
```

The preferred pattern is explicit handler usage. Heuristic global-name fallback exists only for legacy convenience.

### 5.1) Table action params helper

Purpose:
- Standardize repetitive `tableAction` handling (`pageChange`, `perPageChange`) and empty-value cleanup.

```javascript
document.addEventListener('tableAction', (e) => {
  const { action, value, currentParams } = e.detail;

  const nextParams = AdminPageBridge.Table.applyActionParams(
    currentParams,
    { action, value },
    null,
    { cleanEmpty: true }
  );

  loadWithParams(nextParams);
});
```

Replaces repeated code blocks that clone params, switch action types, and remove empty `search`/`date` values.

### 5.2) Table action-state binding helper

Purpose:
- Centralize repetitive `tableAction` listeners that update state and reload table data.

```javascript
const unbindTableAction = AdminPageBridge.Table.bindActionState({
  getState: () => currentParams,
  setState: (next) => { currentParams = next; },
  reload: reloadTableData
});
```

Behavior notes:
- Uses `detail.currentParams` when provided; otherwise falls back to `getState()`.
- Applies actions through `Table.applyActionParams(...)`.
- Calls `setState(nextState)` then reloads via `Table.reload(...)`.

### 5.3) Table container target helper

Purpose:
- Run code against a temporary `#table-container` target and restore IDs safely.

```javascript
AdminPageBridge.Table.withTargetContainer('i18n-table-container', () => {
  renderOrReloadTable();
});
```

Behavior notes:
- If target does not exist, callback still runs without ID swapping.
- If target exists, bridge temporarily assigns it `id="table-container"` and restores IDs in `finally`.

### 6) Event delegation helpers

```javascript
const unbind = AdminPageBridge.Events.onClick('.edit-btn', (event, button) => {
  const roleId = button.dataset.roleId;
  // ...
});

// later
unbind();
```

### 6.1) Filter form binding helper

Purpose:
- Standardize filter form submit/reset wiring and payload collection.

```javascript
const binding = AdminPageBridge.Events.bindFilterForm({
  form: '#admins-search-form',
  resetButton: '#btn-reset',
  // optional explicit field map
  fields: {
    id: '#filter-admin-id',
    email: '#filter-email',
    active: { selector: '#filter-active', type: 'checked' }
  },
  omitEmpty: true,
  onSubmit: (payload) => loadAdminsWithParams({ page: 1, per_page: 25, search: { columns: payload } }),
  onReset: () => loadAdminsWithParams({ page: 1, per_page: 25 })
});
```

Non-goals:
- It does not impose any fixed backend query contract.
- It does not assume fixed field names.

### 6.1.1) Reset-page then reload trigger helper

Purpose:
- Reuse a single handler factory for “set page to 1 then reload”.

```javascript
const resetAndReload = AdminPageBridge.Events.createResetReload({
  setPage: (page) => { currentParams.page = page; },
  reload: reloadTableData,
  resetPage: 1
});

document.getElementById('btn-reset').addEventListener('click', resetAndReload);
```

### 6.2) Debounced input binding helper

Purpose:
- Standardize search input debounce behavior without embedding search domain logic.

```javascript
const debounced = AdminPageBridge.Events.bindDebouncedInput({
  input: '#admins-global-search',
  delay: 1000,
  onFire: (value) => runGlobalSearch(value)
});
```

What it replaces:
- Repetitive timeout management and repeated `keyup`/`input` debounce wiring per page.

### 6.3) Mutation workflow helper

Purpose:
- Standardize repeated mutation flow:
  - optional confirm
  - execute through bridge API (real `ApiHandler.call` underneath)
  - optional modal close/reset
  - optional table reload
  - optional callbacks

```javascript
await AdminPageBridge.API.runMutation({
  operation: 'Toggle Status',
  endpoint: 'items/set-active',
  method: 'POST',
  payload: { id: 10, is_active: false },
  confirmMessage: 'Are you sure?',
  successMessage: 'Status updated',
  reloadHandler: 'reloadItemsTable',
  modal: '#edit-item-modal',
  modalOptions: { resetForm: true },
  onSuccess: (result) => console.log('done', result),
  onFailure: (result) => console.log('failed', result),
  afterFinally: () => console.log('cleanup')
});
```

Compatibility note:
- `runMutation` delegates execution to `AdminPageBridge.API.execute`, which already wraps the real shared `ApiHandler.call` contract.

## What to avoid

- Do **not** modify existing shared utility files.
- Do **not** hardcode page-specific behavior in the bridge.
- Do **not** bypass `AdminPageBridge` for repeated cross-page patterns.
- Do **not** convert this layer to modules/framework-specific syntax.

## Notes

- The bridge wraps existing globals (such as `ApiHandler` and `ErrorNormalizer`) when available.
- No currencies-specific logic is included.
- No page implementation coupling is included.
