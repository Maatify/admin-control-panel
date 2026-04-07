# Admin Page Bridge Usage

## Why this bridge exists

`AdminPageBridge` is a small facade layer on top of the existing shared admin JS utilities.

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

## Core usage pattern

Future page files should interact with shared behavior through `AdminPageBridge` first, instead of calling shared low-level globals directly.

### 1) DOM helpers

```javascript
const roleId = AdminPageBridge.DOM.int('#role-id');
const displayName = AdminPageBridge.DOM.value('#display-name', '');
const isActive = AdminPageBridge.DOM.checked('#is-active', false);
```

### 2) Form payload helpers

```javascript
const payload = AdminPageBridge.Form.collect({
  id: { selector: '#id', type: 'int' },
  name: { selector: '#name', type: 'value' },
  active: { selector: '#active', type: 'checked' }
});

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

### 4) Modal helpers

```javascript
AdminPageBridge.Modal.open('#edit-role-modal');
AdminPageBridge.Modal.close('#edit-role-modal', { resetForm: true });
```

### 5) Event delegation helpers

```javascript
AdminPageBridge.Events.onClick('.edit-btn', (event, button) => {
  const roleId = button.dataset.roleId;
  // ...
});
```

## What to avoid

- Do **not** modify existing shared utility files.
- Do **not** hardcode page-specific behavior in the bridge.
- Do **not** bypass `AdminPageBridge` for repeated cross-page patterns.
- Do **not** convert this layer to modules/framework-specific syntax.

## Notes

- This bridge wraps existing globals (like `ApiHandler` and `ErrorNormalizer`) when available.
- If a wrapped utility is missing, the bridge returns safe fallbacks instead of throwing hard runtime failures.
