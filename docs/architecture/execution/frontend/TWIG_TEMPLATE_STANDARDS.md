# TWIG_TEMPLATE_STANDARDS.md
## Source of Truth — Twig Template Patterns
> Canonical owner for Twig mounting rules.
> Default frontend mounting model is bridge-first v2.

---

## 1. Base Layout

Every template MUST extend:
```twig
{% extends "layouts/base.twig" %}
```

### Available Blocks in base.twig

| Block | Location | Purpose |
|-------|----------|---------|
| `{% block title %}` | `<head>` | Page title |
| `{% block head_meta %}` | `<head>` | Additional meta tags |
| `{% block head_assets %}` | `<head>` | CSS/JS in head (rarely used in features) |
| `{% block content %}` | `<main>` | **Page content + capabilities injection** |
| `{% block scripts %}` | before `</body>` | **JS file tags only — no inline scripts here** |
| `{% block body_scripts %}` | wraps `scripts` block | Do not override directly |
| `{% block sidebar_header %}` | sidebar | Override sidebar logo |
| `{% block page_header %}` | fixed header | Override header |
| `{% block content_footer %}` | footer | Override footer |

### Critical Note
`error_normalizer.js` is auto-loaded in `head_assets` from `base.twig`.
`window.ErrorNormalizer` is available on every page. Do NOT load it again in feature templates.

---

## 2. Full Template Structure

```twig
{% extends "layouts/base.twig" %}

{% block title %}Page Title | {{ ui.appName }}{% endblock %}

{% block content %}

    {# ================================================================
       STEP 1: Capabilities Injection
       - MUST be the first thing inside {% block content %}
       - MUST use window. — const/let are FORBIDDEN
       - MUST use the exact syntax below
       ================================================================ #}
    <script>
        window.{feature}Capabilities = {
            can_create: {{ capabilities.can_create ?? false ? 'true' : 'false' }},
            can_update: {{ capabilities.can_update ?? false ? 'true' : 'false' }},
        };
        console.log('🔐 {Feature} Capabilities:', window.{feature}Capabilities);
    </script>

    {# ================================================================
       STEP 2: Page Header + Breadcrumb
       ================================================================ #}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Page Title</h2>
        <nav>
            <ol class="flex items-center gap-1.5">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                       href="{{ ui.adminUrl }}dashboard">
                        Home
                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366"
                                  stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800 dark:text-gray-200">Current Page</li>
            </ol>
        </nav>
    </div>

    {# ================================================================
       STEP 3: Filters Card
       ================================================================ #}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <form id="{feature}-filter-form" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- filter inputs here -->
            </div>
            <div class="flex flex-wrap gap-3 pt-4">
                <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    Search
                </button>
                <button type="button" id="{feature}-reset-filters"
                        class="px-6 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors font-medium">
                    Reset
                </button>
                {% if capabilities.can_create ?? false %}
                    <button type="button" id="btn-create-{feature}"
                            class="ml-auto px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                        + Create
                    </button>
                {% endif %}
            </div>
        </form>
    </div>

    {# ================================================================
       STEP 4: Table Container
       - Raw mode: id="table-container"
       - Bridge v2 mode: feature-specific container id is allowed
       ================================================================ #}
    <div id="{feature}-table-container" class="w-full"></div>

{% endblock %}

{% block scripts %}
    {# JS file <script src="..."> tags ONLY — no inline scripts allowed here #}
    <script src="{{ asset('assets/maatify/admin-kernel/js/api_handler.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/callback_handler.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/data_table.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/admin-ui-components.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/admin-page-bridge.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{path}/{feature}-helpers-v2.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{path}/{feature}-core-v2.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{path}/{feature}-modals-v2.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{path}/{feature}-actions-v2.js') }}"></script>
{% endblock %}
```

---

## 3. Capabilities Injection Rules

### The Only Valid Syntax
```twig
window.{feature}Capabilities = {
    can_something: {{ capabilities.can_something ?? false ? 'true' : 'false' }},
};
```

- `?? false` — safe fallback when the key is missing from the backend array
- `? 'true' : 'false'` — converts PHP boolean to a JS-safe boolean string
- `window.` is REQUIRED — `const` and `let` are FORBIDDEN

### Why Placement Matters
JS modules run after the DOM loads. Capabilities must exist in the DOM before any module reads them. Placing them in `{% block scripts %}` creates a race condition where the module may run before the inline script executes.

### Capabilities Check Inside Twig HTML
```twig
{# CORRECT — use capabilities to conditionally render HTML elements #}
{% if capabilities.can_create ?? false %}
    <button id="btn-create">Create</button>
{% endif %}

{# WRONG — never check permission names directly #}
{% if is_granted('feature.create') %}
```

---

## 4. Context Injection for Nested Pages

### Scenario 1 — Single Parent ID
```twig
{# Real example: scope_details.twig #}
<script>
    window.scopeDetailsCapabilities = {
        can_assign:   {{ capabilities.can_assign   ?? false ? 'true' : 'false' }},
        can_unassign: {{ capabilities.can_unassign ?? false ? 'true' : 'false' }}
    };
    window.scopeDetailsId = {{ scope.id }};
</script>
```

### Scenario 2 — Multiple Parent IDs (Context Object)
```twig
{# Real example: scope_domain_translations.twig — scope_id + domain_id #}
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

### Decision: Which Pattern to Use

| Parent ID Count | Pattern |
|-----------------|---------|
| 0 — flat list | capabilities object only |
| 1 | `window.{feature}Id = {{ entity.id }};` in the same script tag as capabilities |
| 2+ | `window.{feature}Context = { scope_id, domain_id, ... }` object |

All context injection MUST be in the same `<script>` tag as capabilities, inside `{% block content %}`.

---

## 5. Script Loading Order

### Pattern A — Simple (read-only or minimal actions)
```twig
{% block scripts %}
    <script src="{{ asset('assets/maatify/admin-kernel/js/callback_handler.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/Input_checker.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/data_table.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{feature}.js') }}"></script>
{% endblock %}
```

### Pattern B — Full Modular (CRUD with modals and multiple actions)
```twig
{% block scripts %}
    {# 1. Core infrastructure — must be in this exact order #}
    <script src="{{ asset('assets/maatify/admin-kernel/js/api_handler.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/callback_handler.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/Input_checker.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/data_table.js') }}"></script>

    {# 2. Optional UI libraries #}
    <script src="{{ asset('assets/maatify/admin-kernel/js/select2.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/admin-ui-components.js') }}"></script>

    {# 3. Bridge runtime + feature modules (default order) #}
    <script src="{{ asset('assets/maatify/admin-kernel/js/admin-page-bridge.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{path}/{feature}-helpers-v2.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{path}/{feature}-core-v2.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{path}/{feature}-modals-v2.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{path}/{feature}-actions-v2.js') }}"></script>
{% endblock %}
```

### Dependency Map

| Script | Requires |
|--------|----------|
| `api_handler.js` | nothing |
| `callback_handler.js` | nothing |
| `data_table.js` | `callback_handler.js` (alert fallback) |
| `admin-ui-components.js` | nothing |
| `{feature}-core-v2.js` | `admin-page-bridge.js` + infra scripts |
| `{feature}-modals-v2.js` | `admin-page-bridge.js` + `{feature}-helpers-v2.js` |
| `{feature}-actions-v2.js` | `admin-page-bridge.js` + `{feature}-modals-v2.js` |

---

## 6. Table Container Rule (Raw vs Bridge)

```html
<!-- RAW data_table.js mode -->
<div id="table-container" class="w-full"></div>

<!-- Bridge v2 mode -->
<div id="feature-table-container" class="w-full"></div>

<!-- WRONG without bridge targeting -->
<div id="sessions-table"></div>
<div id="my-container"></div>
<div id="table"></div>
```

`data_table.js` is hardcoded to `#table-container` in raw mode. Bridge-era pages with non-default ids must render through `AdminPageBridge.Table.withTargetContainer(...)`.

---

## 7. Real File References

| Scenario | Reference File |
|----------|---------------|
| Flat list — simple | `sessions.twig` |
| Flat list — full modular | `languages_list.twig` |
| Nested Level 2 — single parent ID | `scope_details.twig` |
| Nested Level 3 — context object | `scope_domain_translations.twig` |
| Report / coverage page | `scope_language_coverage.twig` |

---

## 8. Strict Rules

### Strict DOM Visibility Control
You MUST NOT use inline CSS `style="display: none;"` (or any other inline display manipulation) to control the visibility state of UI components or modals. You MUST manage visibility strictly through the addition or removal of the framework's semantic utility classes (e.g., Tailwind's `hidden`) via JavaScript `classList` manipulation.
