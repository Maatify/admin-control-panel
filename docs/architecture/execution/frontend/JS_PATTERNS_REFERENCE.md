# JS_PATTERNS_REFERENCE.md
## Source of Truth — JavaScript Patterns
> Canonical owner for frontend JS implementation examples.
> Default examples in this file must follow bridge-first v2 runtime entry points.

---

## Overview — Four Patterns

> Note: legacy non-v2 snippets in this file are compatibility references only.
> For new frontend execution defaults, follow bridge-first v2 examples and naming.

| Pattern | When to Use | Real Example |
|---------|-------------|--------------|
| **A — Simple Monolith (Compatibility)** | Read-only list, basic filtering, 1–2 actions | `sessions.js`, `permissions.js` |
| **B — Bridge-first v2 Modular (Default)** | Full CRUD, modals, multiple actions per row | `languages-*-v2.js`, `i18n-*-v2.js`, `currencies-*-v2.js` |
| **C — GET Static List (v2)** | Display flat array, no pagination, report-only | `i18n-scope-coverage-v2.js`, `i18n-scope-language-coverage-v2.js` |
| **D — Context-Driven (v2)** | Page needs parent IDs injected from Twig | `i18n_scope_domain_translations-v2.js`, `i18n_scope_domain_keys_coverage-v2.js` |

---

## Pattern A — Simple Monolith

### When to Use
- Simple features: read + at most 2 actions
- No complex modals
- JS stays under 400 lines

### File Structure
Single file: `{feature}.js`

### Template
```javascript
document.addEventListener('DOMContentLoaded', () => {

    // 1. Capabilities
    const capabilities = window.{feature}Capabilities || {};

    // 2. DOM References
    const filterForm = document.getElementById('{feature}-filter-form');
    const resetBtn   = document.getElementById('{feature}-reset-filters');

    // 3. State
    let currentPage    = 1;
    let currentPerPage = 25;

    // 4. Renderers
    const statusRenderer = (value, row) => {
        const isActive = value === true || value === 1 || value === '1';
        return isActive
            ? `<span class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 rounded-full text-xs font-medium">Active</span>`
            : `<span class="px-3 py-1 bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 rounded-full text-xs font-medium">Inactive</span>`;
    };

    const actionsRenderer = (value, row) => {
        if (!capabilities.can_revoke_id) return '';
        return `
            <button class="revoke-btn text-xs px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700"
                    data-{feature}-id="${row.id}">
                Revoke
            </button>
        `;
    };

    // 5. Build query params
    function buildParams() {
        const params = { page: currentPage, per_page: currentPerPage };
        const columns = {};

        const filterStatus = document.getElementById('filter-status')?.value;
        if (filterStatus) columns.status = filterStatus;

        if (Object.keys(columns).length > 0) {
            params.search = { columns };
        }
        return params;
    }

    // 6. Load data
    function loadData() {
        createTable(
            '{feature}/query',
            buildParams(),
            ['ID', 'Name', 'Status', 'Actions'],
            ['id', 'name', 'status', 'actions'],
            false,   // withSelection
            'id',
            null,
            { status: statusRenderer, actions: actionsRenderer },
            null,
            getPaginationInfo
        );
    }

    function getPaginationInfo(pagination) {
        const { page = 1, per_page = 25, total = 0, filtered = total } = pagination;
        const displayCount = filtered || total;
        const startItem    = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
        const endItem      = Math.min(page * per_page, displayCount);
        let info = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;
        if (filtered && filtered !== total) {
            info += ` <span class="text-gray-500">(filtered from ${total} total)</span>`;
        }
        return { total: displayCount, info };
    }

    // 7. Filter events
    filterForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        currentPage = 1;
        loadData();
    });

    resetBtn?.addEventListener('click', () => {
        filterForm.reset();
        currentPage = 1;
        loadData();
    });

    // 8. Action events — event delegation
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.revoke-btn');
        if (!btn) return;
        const id = btn.getAttribute('data-{feature}-id');
        if (!id) return;
        // handle action...
        showAlert('s', 'Done');
        loadData();
    });

    // 9. Pagination — listen to tableAction from data_table.js
    document.addEventListener('tableAction', (e) => {
        const { action, value } = e.detail;
        if (action === 'pageChange')    { currentPage = value;    loadData(); }
        if (action === 'perPageChange') { currentPerPage = value; currentPage = 1; loadData(); }
    });

    // 10. Initial load
    loadData();
});
```

### Alert System
```javascript
// Pattern A uses showAlert() from callback_handler.js
showAlert('s', 'Success message');   // s = success
showAlert('w', 'Warning message');   // w = warning
showAlert('d', 'Error message');     // d = danger
```

### Script Loading (sessions.twig pattern)
```twig
{% block scripts %}
    <script src="{{ asset('assets/maatify/admin-kernel/js/callback_handler.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/Input_checker.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/data_table.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{feature}.js') }}"></script>
{% endblock %}
```

---

## Pattern B — Bridge-first v2 Modular (Default)

### When to Use
- Full CRUD (create / update / delete / toggle status)
- Modals required
- Multiple action buttons per row

### File Structure
```
{feature}-helpers-v2.js            — family/local bridge helpers
{feature}-core-v2.js or {feature}-with-components-v2.js
{feature}-modals-v2.js
{feature}-actions-v2.js
```

### Bridge-first orchestration defaults
- Pagination/state: `AdminPageBridge.Table.bindActionState(...)` (or family helper wrappers).
- Non-default table containers: `AdminPageBridge.Table.withTargetContainer(...)`.
- Mutation workflows: `AdminPageBridge.API.runMutation(...)` where behavior matches.

### {feature}-core-v2.js Template
```javascript
(function() {
    'use strict';

    // ====================================================================
    // 1. PREREQUISITES CHECK — must be first
    // ====================================================================
    if (typeof AdminUIComponents === 'undefined') {
        console.error('❌ AdminUIComponents library not found!');
        return;
    }
    if (typeof ApiHandler === 'undefined') {
        console.error('❌ ApiHandler not found!');
        return;
    }

    console.log('✅ Dependencies loaded');

    // ====================================================================
    // 2. STATE
    // ====================================================================
    let currentPage    = 1;
    let currentPerPage = 25;

    const headers      = ['ID', 'Name', 'Status', 'Actions'];
    const rows         = ['id', 'name', 'is_active', 'actions'];
    const capabilities = window.{feature}Capabilities || {};

    console.log('🔐 Capabilities:', capabilities);

    // ====================================================================
    // 3. UTILITY
    // ====================================================================
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g,  '&lt;')
            .replace(/>/g,  '&gt;')
            .replace(/"/g,  '&quot;')
            .replace(/'/g,  '&#039;');
    }

    // ====================================================================
    // 4. RENDERERS
    // ====================================================================
    const statusRenderer = (value, row) => {
        return AdminUIComponents.renderStatusBadge(value, {
            clickable:     capabilities.can_set_active,
            entityId:      row.id,
            activeText:    'Active',
            inactiveText:  'Inactive',
            buttonClass:   'toggle-active-btn',
            dataAttribute: 'data-{feature}-id'
        });
    };

    const actionsRenderer = (value, row) => {
        const actions = [];

        if (capabilities.can_update) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass:       'edit-btn',
                icon:           AdminUIComponents.SVGIcons.edit,
                text:           'Edit',
                color:          'blue',
                entityId:       row.id,
                title:          'Edit record',
                dataAttributes: { '{feature}-id': row.id }
            }));
        }

        if (actions.length === 0) {
            return '<span class="text-gray-400 text-xs">—</span>';
        }

        return `<div class="flex flex-wrap gap-2">${actions.join('')}</div>`;
    };

    // ====================================================================
    // 5. QUERY BUILDER
    // ====================================================================
    function buildQueryParams() {
        const params  = { page: currentPage, per_page: currentPerPage };
        const columns = {};

        const filterName = document.getElementById('filter-name')?.value?.trim();
        if (filterName) columns.name = filterName;

        const filterStatus = document.getElementById('filter-status')?.value;
        if (filterStatus) columns.is_active = filterStatus;

        if (Object.keys(columns).length > 0) {
            params.search = { columns };
        }

        return params;
    }

    // ====================================================================
    // 6. PAGINATION INFO
    // ====================================================================
    function getPaginationInfo(pagination) {
        const { page = 1, per_page = 25, total = 0, filtered = total } = pagination;
        const displayCount = filtered || total;
        const startItem    = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
        const endItem      = Math.min(page * per_page, displayCount);

        let info = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;
        if (filtered && filtered !== total) {
            info += ` <span class="text-gray-500 dark:text-gray-400">(filtered from ${total} total)</span>`;
        }
        return { total: displayCount, info };
    }

    // ====================================================================
    // 7. LOAD FUNCTION
    // ====================================================================
    async function load{Feature}(page = null, perPage = null) {
        if (page    !== null) currentPage    = page;
        if (perPage !== null) currentPerPage = perPage;

        const params = buildQueryParams();
        const result = await ApiHandler.call('{feature}/query', params, 'Query {Feature}');

        const container = document.getElementById('table-container');
        if (!container) { console.error('❌ #table-container not found'); return; }

        if (!result.success) {
            ApiHandler.showAlert('danger', 'Failed to load data');
            container.innerHTML = `
                <div class="p-8 text-center bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <p class="text-red-600 dark:text-red-400 font-semibold">Failed to load data</p>
                    <p class="text-gray-500 text-sm mt-1">${result.error || ''}</p>
                    <button class="retry-page-btn mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                        Retry
                    </button>
                </div>`;
            container.querySelector('.retry-page-btn')
                ?.addEventListener('click', () => location.reload());
            return;
        }

        const data       = result.data?.data       || [];
        const pagination = result.data?.pagination || { page: 1, per_page: 25, total: 0 };

        TableComponent(
            data,
            headers,
            rows,
            pagination,
            '',     // legacy actions param — always empty string
            false,  // withSelection
            'id',   // primaryKey
            null,   // onSelectionChange
            {
                is_active: statusRenderer,
                actions:   actionsRenderer
            },
            null,   // selectableIds
            getPaginationInfo
        );
    }

    // ====================================================================
    // 8. INIT
    // ====================================================================
    function init() {
        // Filter form
        document.getElementById('{feature}-filter-form')
            ?.addEventListener('submit', (e) => {
                e.preventDefault();
                currentPage = 1;
                load{Feature}();
            });

        // Reset filters
        document.getElementById('{feature}-reset-filters')
            ?.addEventListener('click', () => {
                document.getElementById('{feature}-filter-form').reset();
                currentPage = 1;
                load{Feature}();
            });

        // Pagination — tableAction is what data_table.js actually dispatches
        document.addEventListener('tableAction', (e) => {
            const { action, value } = e.detail;
            if (action === 'pageChange')    { currentPage = value;    load{Feature}(); }
            if (action === 'perPageChange') { currentPerPage = value; currentPage = 1; load{Feature}(); }
        });

        // Export reload function for use by actions module
        window.reload{Feature}TableV2 = () => load{Feature}(currentPage, currentPerPage);
        window.reload{Feature}Table = window.reload{Feature}TableV2; // compatibility alias if needed

        load{Feature}();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
```

### {feature}-actions-v2.js Template
```javascript
(function() {
    'use strict';

    if (typeof ApiHandler === 'undefined') {
        console.error('❌ ApiHandler not found!');
        return;
    }

    const capabilities = window.{feature}Capabilities || {};

    // ====================================================================
    // BUTTON HANDLER HELPER
    // ====================================================================
    function setupButtonHandler(selector, handler) {
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest(selector);
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();

            const entityId = btn.getAttribute('data-{feature}-id');
            if (!entityId) {
                console.error(`❌ No {feature} ID on button:`, btn);
                return;
            }

            try {
                await handler(entityId, btn);
            } catch (error) {
                console.error(`❌ Error in ${selector} handler:`, error);
                ApiHandler.showAlert('danger', 'An error occurred: ' + error.message);
            }
        });
    }

    // ====================================================================
    // ACTION HANDLERS
    // ====================================================================
    async function toggleActive(entityId, button) {
        const currentStatus = button.getAttribute('data-current-status');
        const newStatus     = !(currentStatus === '1' || currentStatus === 'true');

        const result = await ApiHandler.call(
            '{feature}/set-active',
            { id: parseInt(entityId), is_active: newStatus },
            'Toggle Active'
        );

        if (result.success) {
            ApiHandler.showAlert('success', `Status updated successfully`);
            window.reload{Feature}TableV2?.();
        }
    }

    async function openEditModal(entityId) {
        window.{Feature}Modals?.openEditModal?.(entityId)
            ?? ApiHandler.showAlert('danger', 'Modal system not loaded');
    }

    // ====================================================================
    // INIT
    // ====================================================================
    function init() {
        if (capabilities.can_set_active) {
            setupButtonHandler('.toggle-active-btn', toggleActive);
        }
        if (capabilities.can_update) {
            setupButtonHandler('.edit-btn', (id) => openEditModal(id));
        }

        window.{Feature}Actions = { toggleActive };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
```

### Alert System
```javascript
// Pattern B uses ApiHandler.showAlert() — api_handler.js must be loaded
ApiHandler.showAlert('success', 'Done!');
ApiHandler.showAlert('danger',  'Error!');
ApiHandler.showAlert('warning', 'Warning!');
ApiHandler.showAlert('info',    'Processing...');
```

### Script Loading (languages_list.twig pattern)
```twig
{% block scripts %}
    <script src="{{ asset('assets/maatify/admin-kernel/js/api_handler.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/callback_handler.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/Input_checker.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/data_table.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/select2.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/admin-ui-components.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/admin-page-bridge.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{path}/{feature}-helpers-v2.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{path}/{feature}-core-v2.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{path}/{feature}-modals-v2.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{path}/{feature}-actions-v2.js') }}"></script>
{% endblock %}
```

---

## Pattern C — GET Static List (Non-Paginated)

### When to Use
- Endpoint returns a flat array with no pagination
- Report or coverage page — no CRUD actions
- API method is GET, not POST

### Key Difference
No `TableComponent()` or `createTable()`. You build and inject HTML manually.

### Template
```javascript
(function() {
    'use strict';

    if (typeof ApiHandler === 'undefined') {
        console.error('❌ ApiHandler not found!');
        return;
    }
    // Validate required context
    if (typeof window.scopeDetailsId === 'undefined') {
        console.error('❌ Missing window.scopeDetailsId');
        return;
    }

    const scopeId     = window.scopeDetailsId;
    const containerId = 'scope-coverage-container';

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    async function loadCoverage() {
        const container = document.getElementById(containerId);
        if (!container) { console.error(`❌ #${containerId} not found`); return; }

        // GET method — 4th parameter
        const result = await ApiHandler.call(
            `i18n/scopes/${scopeId}/coverage`,
            {},
            'Load Scope Coverage',
            'GET'
        );

        if (result.success) {
            renderTable(container, result.data);
        } else {
            renderError(container, result.error);
        }
    }

    function renderTable(container, data) {
        if (!data || data.length === 0) {
            container.innerHTML = `
                <div class="text-gray-500 text-sm italic p-4 text-center">No data found.</div>`;
            return;
        }

        const rows = data.map(row => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-6 py-4 text-sm">${escapeHtml(row.name)}</td>
                <td class="px-6 py-4 text-sm">${row.count}</td>
            </tr>
        `).join('');

        container.innerHTML = `
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Count</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        ${rows}
                    </tbody>
                </table>
            </div>`;
    }

    function renderError(container, error) {
        container.innerHTML = `
            <div class="p-4 text-center border border-red-200 bg-red-50 rounded-lg dark:bg-red-900/20 dark:border-red-800">
                <p class="text-red-600 dark:text-red-400 text-sm font-semibold">Error loading data</p>
                <p class="text-gray-500 text-xs mt-1">${escapeHtml(error)}</p>
                <button class="retry-load-btn mt-3 px-4 py-1.5 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                    Retry
                </button>
            </div>`;
        container.querySelector('.retry-load-btn')
            ?.addEventListener('click', () => loadCoverage());
    }

    function init() {
        loadCoverage();
        window.reloadTable = loadCoverage;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
```

---

## Pattern D — Context-Driven (Nested Pages)

### When to Use
- Page needs parent IDs from the route (scope_id + domain_id, for example)
- Parent IDs are injected from Twig as `window.{feature}Context`

### Reading the Context
```javascript
document.addEventListener('DOMContentLoaded', () => {

    // 1. Validate context first
    if (!window.i18nScopeDomainTranslationsContext) {
        console.error('❌ Missing window.i18nScopeDomainTranslationsContext');
        return;
    }

    const context      = window.i18nScopeDomainTranslationsContext;
    const scopeId      = context.scope_id;
    const domainId     = context.domain_id;
    const languages    = context.languages || [];
    const capabilities = window.ScopeDomainTranslationsCapabilities || {};

    // 2. Build dynamic API URL from context
    const apiUrl = `i18n/scopes/${scopeId}/domains/${domainId}/translations/query`;

    // 3. Continue with Pattern A or B...
});
```

### Corresponding Twig Injection
```twig
<script>
    window.ScopeDomainTranslationsCapabilities = {
        can_upsert: {{ capabilities.can_upsert ?? false ? 'true' : 'false' }},
        can_delete: {{ capabilities.can_delete ?? false ? 'true' : 'false' }}
    };
    window.i18nScopeDomainTranslationsContext = {
        scope_id:  {{ scope.id }},
        domain_id: {{ domain.id }},
        languages: {{ languages|json_encode|raw }}
    };
</script>
```

---

## ApiHandler.call() — Complete Signature

```javascript
ApiHandler.call(endpoint, payload, operation, method)
//              ↑         ↑        ↑           ↑
//              string    object   string       string — optional, default: 'POST'

// Examples:
ApiHandler.call('sessions/query',              params,  'Query Sessions')          // POST
ApiHandler.call('sessions/revoke',             {id: 1}, 'Revoke Session')          // POST
ApiHandler.call(`scopes/${id}/coverage`,       {},      'Load Coverage', 'GET')    // GET
ApiHandler.call(`scopes/${id}/domains/query`,  params,  'Query Domains')           // POST
```

### Rules
- Never prepend `/api/` — the handler adds it automatically
- The `operation` string (3rd param) appears in console logs
- Result always returns: `{ success, data, error, status, rawBody }`

---

## Alert Systems — Which to Use

| Pattern | Function | Source |
|---------|----------|--------|
| A — Simple Monolith | `showAlert('s'/'w'/'d', message)` | `callback_handler.js` |
| B / C / D — ApiHandler loaded | `ApiHandler.showAlert('success'/'danger'/'warning'/'info', message)` | `api_handler.js` |

**Simple rule:** If `api_handler.js` is loaded in `{% block scripts %}`, use `ApiHandler.showAlert()`. Otherwise use `showAlert()`.

---

## data-{feature}-id — Naming Convention

```javascript
// CORRECT — use feature-specific attribute name
data-language-id="${row.id}"     // languages
data-domain-id="${row.id}"       // i18n domains
data-scope-id="${row.id}"        // i18n scopes
data-session-id="${row.id}"      // sessions

// WRONG — data-entity-id does not exist in the actual codebase
data-entity-id="${row.id}"
```

In the action handler:
```javascript
const entityId = btn.getAttribute('data-{feature}-id');
```

In `AdminUIComponents.buildActionButton()`:
```javascript
AdminUIComponents.buildActionButton({
    cssClass:       'edit-btn',
    icon:           AdminUIComponents.SVGIcons.edit,
    text:           'Edit',
    color:          'blue',
    entityId:       row.id,
    dataAttributes: { '{feature}-id': row.id }   // produces data-{feature}-id
});
```

---

## IIFE — Required in All Modern Modules

```javascript
// CORRECT — module wrapped in IIFE
(function() {
    'use strict';

    // ... module code ...

    function init() { ... }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

// WRONG — variables leak into global scope
let currentPage = 1;  // conflicts with other modules
document.addEventListener('DOMContentLoaded', () => { ... });
```

**Why IIFE:** Prevents global scope pollution. Variables stay isolated and cannot conflict with other loaded modules.

**Exception:** Pattern A (Simple Monolith) may use `document.addEventListener('DOMContentLoaded', ...)` directly since it is a single file.

---

## escapeHtml() — Required When Rendering User Data

```javascript
// Define once per module
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g,  '&lt;')
        .replace(/>/g,  '&gt;')
        .replace(/"/g,  '&quot;')
        .replace(/'/g,  '&#039;');
}

// Use in every renderer that outputs data from the API
const nameRenderer = (value, row) => `<span>${escapeHtml(value)}</span>`;  // CORRECT
const nameRenderer = (value, row) => `<span>${value}</span>`;              // WRONG — XSS risk
```

---

## window.reload{Feature}TableV2 — Export Pattern

```javascript
// In core module — export after init
window.reload{Feature}TableV2 = () => load{Feature}(currentPage, currentPerPage);
window.reload{Feature}Table = window.reload{Feature}TableV2; // compatibility alias

// In actions module — call after every successful mutation
if (result.success) {
    ApiHandler.showAlert('success', 'Done!');
    window.reload{Feature}TableV2?.();   // optional chaining for safety
}
```

---

## Select2 Usage

```javascript
// Initialize
let mySelect = null;

if (window.Select2) {
    const options = [
        { value: '', label: 'All', search: 'all' },
        ...items.map(item => ({
            value:  String(item.id),
            label:  item.name,
            search: item.code   // enables searching by code
        }))
    ];

    mySelect = Select2('#my-select-container', options, { defaultValue: '' });
}

// Read value
const selectedValue = mySelect?.getValue() || '';
```

```twig
{# Required HTML structure in Twig #}
<div id="my-select-container" class="relative">
    <div class="js-select-box border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 cursor-pointer bg-white dark:bg-gray-700 flex items-center justify-between">
        <input type="text" class="js-select-input bg-transparent outline-none w-full cursor-pointer" readonly placeholder="Select..."/>
        <svg class="js-arrow w-4 h-4 text-gray-500 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </div>
    <div class="js-dropdown hidden absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg">
        <div class="p-2">
            <input type="text" class="js-search-input w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded bg-white dark:bg-gray-700 outline-none" placeholder="Search..."/>
        </div>
        <ul class="js-select-list max-h-48 overflow-y-auto py-1"></ul>
    </div>
</div>
```

---

## Strict Rules

### Component Data Flow & Trace Validation
When utilizing component builders (e.g., `AdminUIComponents.buildActionButton()`) alongside delegated event handlers (e.g., `setupButtonHandler()`), you MUST trace the exact `data-*` attribute from generation to extraction. You MUST explicitly map the required identifier string in the builder's `dataAttributes` property to match the listener's expected extraction key. You MUST NOT rely on default attribute outputs if the listener contract expects a domain-specific key.
