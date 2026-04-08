# IMPLEMENTATION_CHECKLIST.md
## Decision Guide + Pre-Delivery Self-Check
> Use this before starting and before submitting any feature.

---

## Step -1 — Runtime-First Pre-Read Gate

Complete before Step 0:

- [ ] Read `public/assets/maatify/admin-kernel/js/admin-page-bridge.js`
- [ ] Read `public/assets/maatify/admin-kernel/js/ADMIN_PAGE_BRIDGE_USAGE.md`
- [ ] Read target feature-family `*-v2.js` files under `public/assets/maatify/admin-kernel/js/pages/**`
- [ ] Read mounted Twig page contract under `app/Modules/AdminKernel/Templates/pages/**`
- [ ] Confirm default-priority path and classification from `UI_EXECUTION_RULES.md`

If this gate is incomplete, do not continue.

---

## Step 0 — Reference File Selection & Matrix Check

- [ ] Selected Canonical Reference File: `{filepath}`
- [ ] Path classification selected: Official / Transitional / Compatibility-Only / Legacy
- [ ] Performed Conflict Matrix against System Rules. Result: `{PASS / STOP}`
- [ ] Confirmed 3rd-party dependencies (if any) are abstracted to AdminKernel.

---

## Step 1 — Choose Your JS Pattern

```
Does the feature need full CRUD, modals, or multiple action buttons per row?
├── Yes → Pattern B (Modular + ApiHandler)
└── No  → Is it read-only with filtering?
           ├── Yes + POST paginated endpoint    → Pattern A (Simple Monolith)
           ├── Yes + GET flat array endpoint    → Pattern C (GET Static List)
           └── Yes + needs parent IDs from URL  → Pattern D (Context-Driven)
```

---

## Step 2 — Choose Your Twig Template Type

```
Is the page nested (requires a parent ID from the URL)?
├── No  → Flat Template  (sessions.twig / languages_list.twig style)
└── Yes → How many parent IDs?
           ├── 1  → window.{feature}Id = {{ entity.id }};
           └── 2+ → window.{feature}Context = { scope_id, domain_id, ... };
```

---

## Step 3 — Choose Your Alert Function

```
Is api_handler.js loaded in {% block scripts %} for this page?
├── Yes → ApiHandler.showAlert('success' / 'danger' / 'warning' / 'info', message)
└── No  → showAlert('s' / 'w' / 'd', message)
```

---

## Pre-Delivery Self-Check

Run through every item before submitting. A single unchecked item can cause a silent failure.

### Twig Checks

- [ ] Template starts with `{% extends "layouts/base.twig" %}`
- [ ] `window.{feature}Capabilities` is declared as the **first element** inside `{% block content %}`
- [ ] Capabilities syntax: `{{ capabilities.X ?? false ? 'true' : 'false' }}`
- [ ] Capabilities use `window.` — `const` and `let` are not used
- [ ] Inline `<script>` tags (capabilities, context) are inside `{% block content %}`, not `{% block scripts %}`
- [ ] `{% block scripts %}` contains only `<script src="...">` file tags
- [ ] `<div id="table-container" class="w-full"></div>` is present in the HTML
- [ ] Script loading order is correct: `api_handler → callback_handler → data_table → feature files`
- [ ] `error_normalizer.js` is NOT loaded in the template (already in base)

### JavaScript Checks — Pattern B

- [ ] Every modular file is wrapped in an IIFE: `(function() { 'use strict'; ... })()`
- [ ] Prerequisites check (`AdminUIComponents`, `ApiHandler`, context object) is the first thing in every module
- [ ] `*-v2.js` presence was reviewed, but default priority was not assumed from filename alone
- [ ] `escapeHtml()` is defined and used in every renderer that outputs API data
- [ ] `ApiHandler.showAlert()` is used — not the bare `showAlert()`
- [ ] `ApiHandler.call(endpoint, payload, 'Descriptive Name', method?)` — 3rd param is a readable label
- [ ] Endpoint string does not start with `/api/`
- [ ] Button data attributes use `data-{feature}-id`, not `data-entity-id`
- [ ] `document.addEventListener('tableAction', ...)` handles both `pageChange` and `perPageChange`
- [ ] `window.reload{Feature}Table` is exported from the core module
- [ ] Actions module calls `window.reload{Feature}Table?.()` after every successful mutation
- [ ] DOMContentLoaded check uses `if (document.readyState === 'loading') { ... } else { init(); }`

### JavaScript Checks — Pattern A

- [ ] `showAlert('s'/'w'/'d', message)` is used for alerts
- [ ] `createTable()` is called with matching `id="table-container"` in HTML
- [ ] `document.addEventListener('tableAction', ...)` is present for pagination
- [ ] Event delegation uses `e.target.closest(selector)` — no inline `onclick` attributes

### API Integration Checks

- [ ] POST endpoints use `ApiHandler.call(url, payload, 'op')` or `createTable()`
- [ ] GET endpoints use `ApiHandler.call(url, {}, 'op', 'GET')`
- [ ] No direct `fetch()` calls in new code (exception: file uploads only)
- [ ] Every API call result is checked: `if (result.success) { ... } else { ... }`
- [ ] `result.rawBody` is logged or displayed when `result.status === 500`

---

## Debugging Guide

### Table does not render
```
1. Confirm <div id="table-container"> exists in the HTML
2. Confirm api_handler.js or callback_handler.js loads before data_table.js
3. Open browser console — look for red errors
4. Check Network tab — does the API return 200?
5. Add console.log(result) immediately after ApiHandler.call()
```

### All capabilities are false / undefined
```
1. Confirm window.{feature}Capabilities script tag is inside {% block content %}
2. Confirm it is NOT inside {% block scripts %}
3. In browser console: console.log(window.{feature}Capabilities)
4. Confirm backend passes capabilities array to the Twig template
```

### Buttons do nothing when clicked
```
1. Confirm setupButtonHandler uses the correct selector (.my-btn)
2. Confirm the button's data attribute matches what getAttribute() reads
   — button has data-domain-id and handler reads getAttribute('data-domain-id')
3. Add console.log inside the click handler to confirm it fires
4. Confirm e.target.closest(selector) is not returning null
```

### Pagination buttons do nothing
```
1. Confirm document.addEventListener('tableAction', ...) exists in the module
2. Confirm the handler checks action === 'pageChange' (exact string)
3. Confirm the load function is called inside the handler
4. Confirm currentPage is updated before calling the load function
```

### API returns 500
```
1. Open browser console
2. Find the line: RAW BODY: <!DOCTYPE html>...
3. Copy the full raw body string
4. Save it as error.html and open in browser
5. Read the PHP error message, file path, and line number
6. Fix the backend issue — do not guess from the frontend
```

### error_normalizer is not defined
```
This should never happen.
error_normalizer.js is loaded by base.twig in head_assets.
window.ErrorNormalizer is always available.
If it is undefined, check that your template extends layouts/base.twig.
```

---

## Real File References

| You want to see | Reference file |
|-----------------|---------------|
| Flat list, simple monolith | `sessions.twig` |
| Flat list, full modular | `languages_list.twig` + `languages-with-components.js` |
| Modular actions module | `i18n-scopes-actions.js`, `i18n-domains-actions.js` |
| Nested page — single parent ID | `scope_details.twig` + `i18n-scope-coverage.js` |
| Nested page — context object | `scope_domain_translations.twig` + `i18n_scope_domain_translations.js` |
| GET static list render | `i18n-scope-coverage.js`, `i18n-scope-language-coverage.js` |
| Helpers module | `languages-helpers.js` |
| Modals module | `languages-modals.js`, `i18n-scopes-modals.js` |
| Select2 usage | `i18n_scope_domain_translations.js`, `i18n_scope_domain_keys_coverage.js` |
