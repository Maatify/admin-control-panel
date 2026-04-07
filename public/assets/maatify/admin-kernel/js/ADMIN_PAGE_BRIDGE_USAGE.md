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
```

`DOM.bool()` vs `DOM.checked()`:
- `DOM.checked()` reads the checkbox `.checked` state directly (`true/false`).
- `DOM.bool()` reads an element value and normalizes string/number forms such as `"1"`, `"true"`, `"yes"`, `1`.

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

### 6) Event delegation helpers

```javascript
const unbind = AdminPageBridge.Events.onClick('.edit-btn', (event, button) => {
  const roleId = button.dataset.roleId;
  // ...
});

// later
unbind();
```

## What to avoid

- Do **not** modify existing shared utility files.
- Do **not** hardcode page-specific behavior in the bridge.
- Do **not** bypass `AdminPageBridge` for repeated cross-page patterns.
- Do **not** convert this layer to modules/framework-specific syntax.

## Notes

- The bridge wraps existing globals (such as `ApiHandler` and `ErrorNormalizer`) when available.
- No currencies-specific logic is included.
- No page implementation coupling is included.
