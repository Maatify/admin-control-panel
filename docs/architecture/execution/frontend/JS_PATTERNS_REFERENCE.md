# JS_PATTERNS_REFERENCE.md
## Source of Truth — JavaScript Implementation Patterns (v2)

> This reference is bridge-first. For new work, use `AdminPageBridge` + a feature-family helper seam.

---

## Canonical Pattern (Default)

Use this for almost every new page:

- Entry module initializes `AdminPageBridge`
- API calls go through `Bridge.API`
- Table lifecycle goes through `Bridge.Table`
- User-provided content is escaped via `Bridge.Text.escape`
- Feature-specific logic is delegated to a family helper (`window.{Family}Helper`)

### Minimal template

```javascript
(function () {
    'use strict';

    const Bridge = window.AdminPageBridge;
    const Helper = window.RolesHelper;

    if (!Bridge || !Helper) {
        console.error('Missing AdminPageBridge or RolesHelper');
        return;
    }

    const state = {
        page: 1,
        perPage: 25,
        tableContainerId: window.rolesTableContainerId || 'table-container'
    };

    function renderName(value) {
        return `<span>${Bridge.Text.escape(value ?? '')}</span>`;
    }

    async function loadTable() {
        const params = Helper.buildQueryParams(state);
        const response = await Bridge.API.post('roles/query', params, 'Load roles');
        if (!response.success) return;

        Bridge.Table.render({
            containerId: state.tableContainerId,
            headers: ['ID', 'Name', 'Status', 'Actions'],
            rows: ['id', 'name', 'is_active', 'actions'],
            data: response.data,
            renderers: {
                name: renderName,
                actions: (value, row) => Helper.renderActions(row)
            },
            pagination: response.pagination
        });
    }

    document.addEventListener('tableAction', (event) => {
        const { action, value, containerId } = event.detail || {};
        if (containerId && containerId !== state.tableContainerId) return;
        if (action === 'pageChange') state.page = value;
        if (action === 'perPageChange') {
            state.perPage = value;
            state.page = 1;
        }
        loadTable();
    });

    window.reloadRolesTable = () => loadTable();
    loadTable();
})();
```

---

## Twig/Container Contract (Two-tier)

Pages may use either container contract:

1. **Default contract** — use `id="table-container"`
2. **Custom contract** — define the element id and expose `window.{feature}TableContainerId`

If a page uses a custom container id, all listeners and reload flows must remain scoped to that id.

---

## Choosing Module Size (smallest-safe-v2-shape)

Pick the smallest shape that is safe for the feature:

- **Single module + helper**: basic list pages with limited actions
- **Two modules + helper**: list + modal/form complexity
- **Additional split modules**: only when complexity requires it

Do **not** split into `core/modals/actions` by default. Start small and split when justified.

---

## Legacy Pattern Mapping (Historical)

Older docs referred to Pattern A/B/C/D and files like `{feature}-core.js` / `{feature}-actions.js`.
Those names remain useful for reading legacy code, but they are **not** the default for new implementations.

When touching legacy pages, modernize incrementally by routing API/table/text flows through bridge helpers first.
