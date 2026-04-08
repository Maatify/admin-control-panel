# COMMON_MISTAKES.md
## Real Mistakes — v2-focused corrections

> These are recurring implementation errors and their canonical fixes.

---

## Quick Reference

| # | Mistake | Symptom |
|---|---------|---------|
| 1 | Capabilities injected too late | Capabilities read as undefined at startup |
| 2 | Container contract mismatch | Table never appears or wrong table updates |
| 3 | Using wrong notification API | Missing alert/toast or runtime errors |
| 4 | Unscoped `tableAction` handling | Pagination from one table controls another |
| 5 | Manually adding `/api/` prefix | 404 / double-prefix endpoint bugs |
| 6 | Bypassing bridge/helper API path | Inconsistent error handling and logs |
| 7 | Rendering unescaped user text | XSS risk |
| 8 | Missing `window.reload{Feature}Table` | Stale table after create/update/delete |

---

## Mistake #1 — Capabilities injected too late

```twig
{# WRONG: capabilities declared after page script initialization #}
{% block scripts %}
  <script src="{{ asset('.../roles.js') }}"></script>
  <script>window.rolesCapabilities = { can_create: true };</script>
{% endblock %}

{# CORRECT: inject in content before module startup #}
{% block content %}
  <script>
    window.rolesCapabilities = {
      can_create: {{ capabilities.can_create ?? false ? 'true' : 'false' }}
    };
  </script>
  ...
{% endblock %}
```

---

## Mistake #2 — Container contract mismatch

```html
<!-- DEFAULT CONTRACT -->
<div id="table-container"></div>

<!-- CUSTOM CONTRACT -->
<div id="roles-table"></div>
<script>window.rolesTableContainerId = 'roles-table';</script>
```

Do not assume every page must use `#table-container`; instead, keep the JS and Twig contract aligned.

---

## Mistake #3 — Using wrong notification API

```javascript
// WRONG: calling legacy global when page is bridge-based
showAlert('s', 'Saved');

// CORRECT: use bridge/UI layer used by the page
Bridge.UI.alert('success', 'Saved');
```

If a legacy page still uses `ApiHandler.showAlert`, keep it consistent for that page until migration.

---

## Mistake #4 — Unscoped tableAction handling

```javascript
// WRONG
document.addEventListener('tableAction', (e) => {
  if (e.detail.action === 'pageChange') loadTable(e.detail.value);
});

// CORRECT
document.addEventListener('tableAction', (e) => {
  const { action, value, containerId } = e.detail || {};
  if (containerId && containerId !== state.tableContainerId) return;
  if (action === 'pageChange') {
    state.page = value;
    loadTable();
  }
});
```

Always scope events on multi-table pages.

---

## Mistake #5 — Manually adding /api/ prefix

```javascript
// WRONG
Bridge.API.post('/api/roles/query', params, 'Query roles');

// CORRECT
Bridge.API.post('roles/query', params, 'Query roles');
```

Project API helpers manage base prefixing.

---

## Mistake #6 — Bypassing bridge/helper API path

```javascript
// WRONG
const response = await fetch('/api/roles/update', { method: 'POST', body: ... });

// CORRECT
const result = await Bridge.API.post('roles/update', payload, 'Update role');
```

Use direct `fetch` only for constrained edge cases (for example multipart uploads), then normalize handling.

---

## Mistake #7 — Rendering unescaped user text

```javascript
// WRONG
const nameCell = (value) => `<span>${value}</span>`;

// CORRECT
const nameCell = (value) => `<span>${Bridge.Text.escape(value ?? '')}</span>`;
```

Any user-sourced text must be escaped.

---

## Mistake #8 — Missing reload export

```javascript
// WRONG: no cross-module refresh hook
async function updateRole(payload) {
  const result = await Bridge.API.post('roles/update', payload, 'Update role');
  if (result.success) Bridge.UI.alert('success', 'Updated');
}

// CORRECT
window.reloadRolesTable = () => loadTable();

async function updateRole(payload) {
  const result = await Bridge.API.post('roles/update', payload, 'Update role');
  if (result.success) {
    Bridge.UI.alert('success', 'Updated');
    window.reloadRolesTable?.();
  }
}
```

---

## Legacy note

Legacy guidance that assumes hardcoded `#table-container`, local `escapeHtml` helpers, or split-pattern-only architecture is superseded by the bridge-first v2 contract.
