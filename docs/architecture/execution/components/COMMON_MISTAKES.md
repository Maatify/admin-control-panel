# COMMON_MISTAKES.md
## Real Mistakes — Real Fixes
> Every mistake listed here occurred in actual production code.
> For new frontend implementation defaults, prefer bridge-first v2 runtime patterns; raw fixes here are compatibility-level unless noted.

---

## Quick Reference

| # | Mistake | Symptom |
|---|---------|---------|
| 1 | Capabilities in `{% block scripts %}` | All capabilities read as undefined |
| 2 | Wrong table container id | Table never renders, no visible error |
| 3 | Wrong alert system for the pattern | `showAlert is not a function` or silent alert |
| 4 | `data-entity-id` instead of `data-{feature}-id` | Buttons do nothing, `getAttribute` returns null |
| 5 | No IIFE in modular file | Variable conflicts across modules |
| 6 | No `tableAction` listener | Pagination buttons do nothing |
| 7 | `/api/` prefix on endpoint | 404 Not Found |
| 8 | `fetch()` directly instead of `ApiHandler.call()` | No logging, incomplete error handling |
| 9 | No prerequisites check | Silent failures, impossible to debug |
| 10 | No `escapeHtml()` on rendered data | XSS vulnerability |
| 11 | No `window.reload{Feature}Table` export | Table does not refresh after mutations |
| 12 | `createTable()` with a GET endpoint | 405 Method Not Allowed or 500 error |
| 13 | Applying HTML standard attributes to 3rd-party widgets | UI styling breaks / feature behaves unexpectedly |

---

## Mistake #13 — HTML vs Widget Assumption
Assuming standard HTML attributes (like `class` or `dir`) will map automatically to complex 3rd-party iframes/widgets. Always configure through the widget's JS wrapper (e.g., using `window.initWysiwyg(context)` instead of putting `dir="{{ language.direction }}"` on the raw textarea).

---

## Mistake #1 — Capabilities in {% block scripts %}

```twig
{# WRONG — placing capabilities in scripts block #}
{% block scripts %}
    <script>
        window.sessionsCapabilities = { can_revoke: true };
    </script>
    <script src="sessions.js"></script>
{% endblock %}
```

```twig
{# CORRECT — capabilities must be in {% block content %} first #}
{% block content %}
    <script>
        window.sessionsCapabilities = {
            can_revoke: {{ capabilities.can_revoke ?? false ? 'true' : 'false' }}
        };
    </script>
    <!-- rest of page HTML -->
{% endblock %}

{% block scripts %}
    <script src="sessions.js"></script>
{% endblock %}
```

**Why:** JS modules run after the DOM is loaded. If capabilities are placed in `{% block scripts %}`, the module may execute before the inline script tag runs and all values will be `undefined`.

---

## Mistake #2 — Wrong Table Container ID

```html
<!-- WRONG -->
<div id="sessions-table"></div>
<div id="my-container"></div>
<div id="table"></div>

<!-- CORRECT — raw data_table.js mode -->
<div id="table-container" class="w-full"></div>

<!-- CORRECT — bridge v2 mode -->
<div id="feature-table-container" class="w-full"></div>
```

`data_table.js` is hardcoded to `#table-container` in raw mode. Bridge-era pages can use non-default container ids only when rendering through `AdminPageBridge.Table.withTargetContainer(...)`.

---

## Mistake #3 — Wrong Alert System

```javascript
// WRONG — using showAlert() in a module where api_handler.js is loaded
showAlert('s', 'Done!');
// → ReferenceError or wrong function called

// WRONG — using ApiHandler.showAlert() when api_handler.js is NOT loaded
ApiHandler.showAlert('success', '...');
// → TypeError: Cannot read properties of undefined

// CORRECT — Pattern A (api_handler.js NOT in script list)
showAlert('s', 'Success');   // s = success
showAlert('w', 'Warning');   // w = warning
showAlert('d', 'Error');     // d = danger

// CORRECT — Pattern B/C/D (api_handler.js IS loaded)
ApiHandler.showAlert('success', 'Done!');
ApiHandler.showAlert('danger',  'Failed!');
ApiHandler.showAlert('warning', 'Warning!');
ApiHandler.showAlert('info',    'Note...');
```

**Rule:** Check whether `api_handler.js` is in the `{% block scripts %}` of the Twig template. If it is, use `ApiHandler.showAlert()`. If not, use `showAlert()`.

---

## Mistake #4 — data-entity-id Instead of data-{feature}-id

```javascript
// WRONG — data-entity-id does not exist in the real codebase
AdminUIComponents.buildActionButton({
    dataAttributes: { 'entity-id': row.id }   // produces data-entity-id
});
// later in handler:
const id = btn.getAttribute('data-entity-id');  // always returns null

// CORRECT — use the feature-specific name
AdminUIComponents.buildActionButton({
    dataAttributes: { 'domain-id': row.id }   // produces data-domain-id
});
// in handler:
const id = btn.getAttribute('data-domain-id');  // works correctly
```

Pattern used in production:
- `data-language-id` — languages
- `data-domain-id` — i18n domains
- `data-scope-id` — i18n scopes
- `data-session-id` — sessions
- `data-permission-id` — permissions

---

## Mistake #5 — No IIFE in a Modular File

```javascript
// WRONG — all variables are in global scope
let currentPage = 1;    // conflicts with any other module using the same name
let capabilities = {};  // overwritten by the next module that loads

async function loadData() { ... }   // pollutes window

document.addEventListener('DOMContentLoaded', () => { loadData(); });

// CORRECT — everything is isolated
(function() {
    'use strict';

    let currentPage = 1;    // private to this module
    let capabilities = {};  // private to this module

    async function loadData() { ... }

    function init() { loadData(); }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
```

**Exception:** Pattern A (Simple Monolith) is a single file and may use `document.addEventListener('DOMContentLoaded', ...)` directly.

---

## Mistake #6 — No tableAction Listener

Preferred fix (bridge-first):
```javascript
AdminPageBridge.Table.bindActionState({
  sourceContainerId: 'feature-table-container',
  getState: () => params,
  setState: (next) => { params = next; },
  reload: reloadTableData
});
```

Compatibility fix (raw):

```javascript
// WRONG — exporting window.changePage has no effect
// data_table.js never calls window.changePage()
window.changePage = function(page) { loadData(page); };      // never called
window.changePerPage = function(pp) { loadData(1, pp); };    // never called

// CORRECT — listen to the CustomEvent that data_table.js actually dispatches
document.addEventListener('tableAction', (e) => {
    const { action, value } = e.detail;
    if (action === 'pageChange')    { currentPage = value;    loadData(); }
    if (action === 'perPageChange') { currentPerPage = value; currentPage = 1; loadData(); }
});
```

`data_table.js` dispatches `new CustomEvent('tableAction', { detail: { action, value } })` when the user clicks a page number or changes the per-page select. It does not call any `window.*` function.

---

## Mistake #7 — /api/ Prefix on Endpoint

```javascript
// WRONG
ApiHandler.call('/api/sessions/query', params, 'Query');
ApiHandler.call('api/sessions/query',  params, 'Query');
createTable('/api/sessions/query', params, ...);

// CORRECT — ApiHandler and createTable add /api/ automatically
ApiHandler.call('sessions/query', params, 'Query Sessions');
createTable('sessions/query', params, ...);
```

---

## Mistake #8 — Direct fetch() Instead of ApiHandler.call()

```javascript
// WRONG — in new code
const response = await fetch('/api/sessions/revoke', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id })
});
const data = await response.json();

// CORRECT
const result = await ApiHandler.call('sessions/revoke', { id }, 'Revoke Session');
if (result.success) {
    ApiHandler.showAlert('success', 'Session revoked');
    window.reloadSessionsTable?.();
} else {
    // result.rawBody contains full HTML if the server returned a 500
    ApiHandler.showAlert('danger', result.error);
}
```

**Only accepted exception:** File uploads using `multipart/form-data` must use `fetch()` directly with `FormData`. The response must then be processed through `ApiHandler.parseResponse()`.

---

## Mistake #9 — No Prerequisites Check

```javascript
// WRONG — silent failure when a dependency is missing
(function() {
    'use strict';
    const capabilities = window.i18nDomainsCapabilities;
    // If capabilities is undefined, all can_* checks silently return undefined
    // No error is thrown — the UI just behaves unexpectedly
})();

// CORRECT
(function() {
    'use strict';

    if (typeof AdminUIComponents === 'undefined') {
        console.error('❌ AdminUIComponents not found! Check script loading order.');
        return;
    }
    if (typeof ApiHandler === 'undefined') {
        console.error('❌ ApiHandler not found! Check script loading order.');
        return;
    }
    // For context-driven pages, also validate the context object
    if (typeof window.scopeDetailsId === 'undefined') {
        console.error('❌ Missing window.scopeDetailsId — check Twig template.');
        return;
    }

    const capabilities = window.i18nDomainsCapabilities || {};
    // safe to continue
})();
```

---

## Mistake #10 — No escapeHtml() on Rendered Data

```javascript
// WRONG — XSS vulnerability
const nameRenderer = (value, row) => `<span>${value}</span>`;
// If value = <script>alert(1)</script>, it executes in the browser

// CORRECT — always escape user-sourced data
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

const nameRenderer = (value, row) => `<span>${escapeHtml(value)}</span>`;
```

---

## Mistake #11 — No window.reload{Feature}Table Export

```javascript
// WRONG — actions module cannot trigger a table refresh
async function toggleActive(id) {
    const result = await ApiHandler.call('{feature}/set-active', { id }, 'Toggle');
    if (result.success) {
        ApiHandler.showAlert('success', 'Done');
        // table still shows stale data
    }
}

// CORRECT — core module exports the reload function
// In {feature}-core.js:
window.reload{Feature}Table = () => load{Feature}(currentPage, currentPerPage);

// In {feature}-actions.js:
async function toggleActive(id) {
    const result = await ApiHandler.call('{feature}/set-active', { id }, 'Toggle');
    if (result.success) {
        ApiHandler.showAlert('success', 'Done');
        window.reload{Feature}Table?.();   // optional chaining prevents error if not yet defined
    }
}
```

---

## Mistake #12 — createTable() with a GET Endpoint

```javascript
// WRONG — createTable() always sends POST
createTable('i18n/scopes/1/coverage', {}, headers, rows, ...);
// → The endpoint expects GET → 405 Method Not Allowed or 500 server error

// CORRECT — GET endpoints must use ApiHandler.call() + manual render
const result = await ApiHandler.call(
    'i18n/scopes/1/coverage',
    {},
    'Load Coverage',
    'GET'
);
if (result.success) {
    renderTable(container, result.data);
}
```
