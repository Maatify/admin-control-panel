# UI Execution Rules

> Last updated to reflect verified production behavior.
> Frontend canonical owner: this file defines the default frontend execution model.
> Default starting path is runtime-first: Bridge entry points → active v2 files → Twig mounts → docs alignment.

---

## 1. General Principles
- **Reference Cloning Priority:** Before generating UI code, the agent MUST explicitly state which physical file acts as the Source of Truth. Generative coding from scratch is forbidden; the agent must clone and mutate the verified reference.
- **3rd-Party Abstraction Rule:** Any 3rd-party UI library (WYSIWYG, DatePicker, Chart) MUST be wrapped in a global `AdminKernel.*` manager. Direct initialization (`new Plugin()`) inside page-specific logic is FORBIDDEN.

## 1.1 UI Controller Rules
- A UI Controller MUST use `__invoke(Request $request, Response $response): Response` for single-action views.
- A UI Controller MUST use specific action methods (e.g., `index`, `profile`) ONLY when grouping related read-only views for a single entity type.
- A UI Controller MUST ONLY extract route parameters, fetch basic parent context metadata, resolve capabilities, and render a Twig template.
- A UI Controller MUST NEVER fetch list data or iterate over domain entities for table rendering.
- A UI Controller MUST NEVER execute state-changing business logic or process form submissions natively.

---

## 2. Capability System Rules
- The `$capabilities` array MUST be explicitly assembled in the UI Controller using `UiPermissionService::hasPermission()`.
- The array keys MUST be boolean flags prefixed with `can_` (e.g., `can_create`, `can_update`, `can_delete`).
- The capabilities MUST ALWAYS be passed to the Twig template under the strict key name `capabilities`.
- The capability object in JavaScript MUST be strictly named `window.{feature}Capabilities` (e.g., `window.adminsCapabilities`, `window.languagesCapabilities`).
- `window.{feature}Capabilities` MUST be consumed on JavaScript initialization.
- UI MUST NOT render actions if capability is `false`.
- The Actions column MUST ALWAYS be conditionally excluded from the renderers object when the capability is `false` — not just visually hidden.

---

## 3. Twig Rules

### Capabilities Injection
- The capability object MUST be injected into the global window scope via an inline `<script>` block using the exact per-key syntax below. Using `const` or `let` is FORBIDDEN.
- Missing capabilities MUST ALWAYS default to `false` using the null coalescing operator.

**Required syntax — one key per line:**
```twig
window.{feature}Capabilities = {
    can_create: {{ capabilities.can_create ?? false ? 'true' : 'false' }},
    can_update: {{ capabilities.can_update ?? false ? 'true' : 'false' }},
};
```

**FORBIDDEN — using `json_encode` without per-key safety:**
```twig
window.{feature}Capabilities = {{ capabilities|json_encode|raw }};
```
This fails silently if `capabilities` is null or a PHP object without proper JSON encoding.

### Injection Placement
- The capabilities `<script>` block MUST be placed inside `{% block content %}` — before all HTML.
- Inline `<script>` tags (capabilities, context IDs) are FORBIDDEN inside `{% block scripts %}`.
- `{% block scripts %}` MUST contain only `<script src="...">` file tags.

```twig
{# CORRECT #}
{% block content %}
    <script>
        window.{feature}Capabilities = { ... };
        window.{feature}Id = {{ entity.id }};   {# if nested page #}
    </script>
    <!-- HTML content -->
{% endblock %}

{% block scripts %}
    <script src="{{ asset('...api_handler.js') }}"></script>
    <script src="{{ asset('...admin-page-bridge.js') }}"></script>
    <script src="{{ asset('...{feature}-core-v2.js') }}"></script>
{% endblock %}
```

### Context Injection for Nested Pages
- Single parent ID: `window.{feature}Id = {{ entity.id }};` — in the same `<script>` tag as capabilities.
- Multiple parent IDs: `window.{feature}Context = { scope_id: {{ scope.id }}, domain_id: {{ domain.id }} };`

### Table Container
- Raw `data_table.js` writes to `#table-container`.
- For bridge-era pages with feature-specific containers, use `AdminPageBridge.Table.withTargetContainer(...)`.
- Do NOT implement manual DOM id swapping when bridge helper is available.

---

## 4. JavaScript Architecture Rules

### Pattern A — Simple Monolith
Valid and supported for read-only features or features with at most 2 actions. Not deprecated.
- Single file: `{feature}.js`
- Real examples: `sessions.js`, `permissions.js`

### Pattern B — Modular
Required for features with CRUD, modals, or multiple action buttons per row.
- `{feature}-helpers-v2.js` — utilities + bridge-aware helper wrappers
- `{feature}-core-v2.js` or `{feature}-with-components-v2.js` — table rendering + data loading
- `{feature}-modals-v2.js` — modal HTML + open/close
- `{feature}-actions-v2.js` — API calls + button event handlers

Every modular file MUST be wrapped in an IIFE:
```javascript
(function() {
    'use strict';
    // ...
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
```

### Prerequisites Check
Every module MUST validate its dependencies at the top before any code runs:
```javascript
if (typeof AdminUIComponents === 'undefined') {
    console.error('❌ AdminUIComponents not found!');
    return;
}
if (typeof ApiHandler === 'undefined') {
    console.error('❌ ApiHandler not found!');
    return;
}
// For context-driven pages:
if (typeof window.scopeDetailsId === 'undefined') {
    console.error('❌ Missing window.scopeDetailsId');
    return;
}
```

---

## 5. JavaScript Table Rendering Rules

### createTable() — Paginated POST Endpoints
Use when the endpoint expects `page`/`per_page` and returns `{ data, pagination }`:
```javascript
createTable(apiUrl, params, headers, rows, false, 'id', null, renderers, null, getPaginationInfo);
```
- Sends POST automatically — cannot be changed
- Hardcoded to `#table-container`

### ApiHandler.call() + TableComponent() — Paginated POST with Custom Error Handling
Use when you need custom error display or data transformation:
```javascript
const result = await ApiHandler.call('{feature}/query', params, 'Query');
if (result.success) {
    TableComponent(result.data.data, headers, rows, result.data.pagination, '', false, 'id', null, renderers, null, getPaginationInfo);
}
```

### Pattern C — GET Non-Paginated Endpoints
Use when the endpoint returns a flat array with no pagination, via GET:
```javascript
const result = await ApiHandler.call('i18n/scopes/1/coverage', {}, 'Load Coverage', 'GET');
if (result.success) { renderTable(container, result.data); }
```
`createTable()` and `TableComponent()` are FORBIDDEN for GET endpoints — they always POST.

### Pagination Event — tableAction
`data_table.js` dispatches a `CustomEvent` named `tableAction`. Features MUST listen to this event:
```javascript
document.addEventListener('tableAction', (e) => {
    const { action, value } = e.detail;
    if (action === 'pageChange')    { currentPage = value;    loadData(); }
    if (action === 'perPageChange') { currentPerPage = value; currentPage = 1; loadData(); }
});
```

`window.changePage()` and `window.changePerPage()` are NOT called by `data_table.js` and MUST NOT be relied on for pagination. Export them only for backward compatibility if an existing integration requires them.

**Bridge-first default:** use `AdminPageBridge.Table.bindActionState(...)` (directly or via family helpers) as the primary pattern for tableAction state wiring. Direct raw listeners remain compatibility-only.

### Custom Renderers
MUST be passed as an object of functions:
```javascript
{ columnKey: (value, row) => html }
```
Inline HTML generation inside table configuration is FORBIDDEN. Raw HTML strings for action buttons are FORBIDDEN — use `AdminUIComponents.buildActionButton(...)`.

---

## 6. Shared UI Components Rules
- UI rendering MUST use `AdminUIComponents` utilities (e.g., `renderStatusBadge`, `renderCodeBadge`).
- Inspect real usage in `languages-with-components-v2.js`, `i18n-domains-core-v2.js`, `i18n-scopes-core-v2.js` to verify exact function signatures before use. DO NOT assume parameters.
- The agent MUST NOT invoke any function or object property from a shared JS or PHP library without first inspecting the actual file contents before usage to definitively verify its exact filename, object name, and method signature.
- `AdminUIComponents.buildActionButton(...)` and `AdminUIComponents.SVGIcons` MUST be used for all action buttons. Raw HTML string concatenation for buttons is FORBIDDEN.

---

## 7. JavaScript Event, Modal & Form Rules
- Inline `onclick` handlers are STRICTLY FORBIDDEN.
- All DOM event delegation MUST use bridge-aware handlers (`Bridge.Events.onClick(...)` or family `setupButtonHandler(...)`) following v2 module patterns.
- **Data Attribute Standard:** All action buttons referencing a database record MUST use `data-{feature}-id="{id}"`. The attribute name MUST match the feature name exactly:
  - `data-language-id` for languages
  - `data-domain-id` for i18n domains
  - `data-scope-id` for i18n scopes
  - `data-{feature}-id` for any new feature
  - `data-entity-id` is NOT a valid attribute name — it does not exist in the codebase.
- Event handlers MUST read the ID via `btn.getAttribute('data-{feature}-id')`.
- Modals MUST be injected exactly once into `document.body` via `insertAdjacentHTML` during module initialization. They MUST be toggled via CSS classes (`hidden`), not recreated on every click.
- Twig MUST NOT contain modal HTML for new features.
- Forms MUST NOT call the API directly. Form submission MUST delegate to the actions module.

---

## 8. API Interaction Contract Rules (Frontend)
- ALL API calls MUST go through `ApiHandler.call(endpoint, payload, operation, method?)`.
- Full signature: `ApiHandler.call(endpoint, payload, 'Descriptive Label', 'POST'|'GET')`
  - `endpoint` — path without `/api/` prefix
  - `payload` — request data (`{}` for GET)
  - `operation` — human-readable label shown in console logs
  - `method` — optional, defaults to `'POST'`
- Direct `fetch()` / `axios` usage is STRICTLY FORBIDDEN. Exception: file uploads via `multipart/form-data` — response MUST be processed through `ApiHandler.parseResponse()`.
- When using `fetch()` directly for file uploads, manually prepend `/api/` to the endpoint URL.
- For **Paginated CRUD Lists**: call `.query` endpoints via POST. Payload MUST conform to `{ page, per_page, search? }`.
- For **Non-Paginated/Static Lists**: call endpoints via GET using the 4th parameter: `ApiHandler.call(endpoint, {}, 'op', 'GET')`.
- Endpoint strings MUST NOT start with `/api/` — the handler adds this automatically.
- Sorting is server-controlled. The UI MUST NOT send `sort` or `sort_order` parameters unless explicitly supported by the endpoint.

---

## 9. JS Docs as Dynamic Source of Truth
- The directory `public/assets/maatify/admin-kernel/js/docs/` is a **dynamic source of truth**.
- Before implementing any frontend component, consult all documentation files in `js/docs/`.
- IGNORE the subdirectory `Admin_CRUD_Builder`.
- If any rule in this document conflicts with documented behavior in `js/docs/`, the `js/docs/` implementation takes precedence.

---

## 10. UI State Synchronization & Lifecycle Rules
- UI MUST NOT simulate state locally. UI MUST rely ONLY on API responses.
- After ANY successful mutation (create/update/delete/toggle):
  - Call canonical `window.reload{Feature}TableV2?.()` to refresh the table.
  - Keep `window.reload{Feature}Table` alias only when compatibility requires it.
- Manual DOM patching is FORBIDDEN.
- UI interaction route names MUST end with `.ui` or `.view`.
- Data query endpoint paths MUST end with `/query`.

---

## 11. Nested UI & Routing Rules (Frontend)
- Parent IDs from the route MUST be captured in the UI Controller and passed to the Twig template as scalar variables.
- The Twig template MUST expose them in the same `<script>` tag as capabilities, inside `{% block content %}`:
  - Single ID: `window.{feature}Id = {{ entity.id }};`
  - Multiple IDs: `window.{feature}Context = { scope_id: {{ scope.id }}, domain_id: {{ domain.id }} };`
- Feature JS MUST validate that the context exists as the first operation, before any other code runs.
- Parent IDs MUST be propagated through: init → query builder → API URL construction.
- IDs MUST NOT be hardcoded or lost across module boundaries.
- When modifying UI URL structures, the agent MUST alter the `window.{feature}Api` configuration payload injected via Twig. The agent MUST NOT hardcode or dynamically construct base URLs (like `window.ui.adminUrl`) inside the Javascript feature files.

---

## 12. Failure & Error Handling Rules (Frontend)
- ALL API call results MUST be checked. Silent failures are STRICTLY FORBIDDEN.
- **Alert system:**
  - Pattern B/C/D (api_handler.js loaded): use `ApiHandler.showAlert('success'|'danger'|'warning'|'info', message)`
  - Pattern A (api_handler.js NOT loaded): use `showAlert('s'|'w'|'d', message)` from `callback_handler.js`
  - Native `alert()` and custom notification HTML are FORBIDDEN.
- When `result.status === 500`, log `result.rawBody` and display a user-friendly error message.
- The UI Controller MUST throw a specific domain exception (e.g., `EntityNotFoundException`) if requested parent context metadata does not exist.

---

## 12.1 Debugging Flow (500_ERROR_DEBUGGING)
- If an endpoint returns HTTP 500 (HTML body), do NOT blind-fix the UI.
- Extract the exact PHP error string from `result.rawBody` in the console.
- Fix the backend issue first. Then verify the UI payload matches the backend schema.
- Validate endpoint shape (paginated POST vs non-paginated GET) before assuming a UI bug.

---

## 13. Step-Up / 2FA Handling Rules
- HTTP 403 responses indicating Step-Up authentication MUST be handled via the `ErrorNormalizer` bridge.
- UI MUST redirect to the 2FA flow: `/2fa/verify?scope={scope}&return_to={path}`.
- Ignoring Step-Up flows is FORBIDDEN.

---

## 14. UI vs API Boundary Rules
- UI routes (GET, returning HTML) MUST NEVER be called programmatically.
- `ApiHandler` and `fetch` MUST ONLY target `/api/*` endpoints.

---

## 15. API Response Shape Rules (Frontend Contract)
- Paginated query responses: `{ data: [...], pagination: { page, per_page, total, filtered? } }`
- Non-paginated GET responses: `{ data: [...] }` — no pagination object
- Command (mutation) responses: success-only acknowledgement — do not assume returned data unless explicitly documented.

---

## 16. Route-Scoped Context Rules
- Parent IDs from the route (e.g., `scope_id`, `domain_id`) MUST NOT be sent in the request payload.
- They MUST be embedded in the API URL path: `i18n/scopes/${scopeId}/domains/${domainId}/translations/query`

---

## 17. No-Assumption Rules
- If a field or behavior is not explicitly defined in the API contract, treat it as unsupported.
- UI MUST NOT infer hidden fields, fallback values, or implicit behavior.

---

## 18. Idempotent Actions Handling Rules
- State-changing endpoints (activate, deactivate, publish, archive) MUST be treated as idempotent.
- UI MUST handle success responses even when no actual state change occurred (no-op 200 OK).

---

## 19. File Upload Architecture Rules
- Base64 encoding MUST NEVER be embedded within general JSON update payloads.
- File uploads MUST use dedicated API endpoints expecting `multipart/form-data`.
- Uploads MUST use native `fetch()` with `FormData` — and MUST manually prepend `/api/` to the endpoint.
- The response from `fetch()` MUST be processed through `ApiHandler.parseResponse()`.

---

## 20. escapeHtml Requirement
- Every module that renders API data into HTML MUST define and use `escapeHtml()`:
```javascript
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
```
- Rendering raw API values directly into HTML is a FORBIDDEN XSS vulnerability.
- The `escapeHtml` function MUST be explicitly written as a local `function escapeHtml(text) { ... }` block inside your specific JS module file. Do NOT assume it exists on `AdminUIComponents` or any other global object.
