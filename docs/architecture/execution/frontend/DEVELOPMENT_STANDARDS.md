# 📘 Development Standards Guide
## Professional Development Standards for Admin Dashboard System

> **Last Updated:** February 2026
> **Default Model:** Bridge-first v2 runtime entry path
> **Canonical Rule Owner:** `UI_EXECUTION_RULES.md` (this file provides standards context and references)

---

## 🎯 **Purpose**

This document defines **mandatory standards** for any new feature in the system, based on best practices from the `languages_list` implementation.

---

## 📋 **Table of Contents**

1. [File Structure Standards](#1-file-structure-standards)
2. [Twig Template Standards](#2-twig-template-standards)
3. [JavaScript Architecture Standards](#3-javascript-architecture-standards)
4. [UI/UX Standards](#4-uiux-standards)
5. [Dark Mode Standards](#5-dark-mode-standards)
6. [Security Standards](#6-security-standards)
7. [API Integration Standards](#7-api-integration-standards)
8. [Code Quality Standards](#8-code-quality-standards)
9. [Decision Framework](#9-decision-framework)

---

## 1️⃣ **File Structure Standards**

### ✅ **Pattern B — Bridge-first v2 Modular (Full CRUD features)**

Use when the feature has CRUD operations, modals, or multiple action buttons per row.

```
📁 Feature Name (e.g., languages)
├── 📄 languages_list.twig               # UI template (mounts bridge first)
└── 📁 Modular JavaScript Files:
    ├── 📄 languages-helpers-v2.js           # Bridge/family helpers
    ├── 📄 languages-with-components-v2.js   # Main: table rendering + data loading
    ├── 📄 languages-modals-v2.js            # Modal HTML + open/close logic
    ├── 📄 languages-actions-v2.js           # API calls + button event handlers
    └── 📄 languages-fallback-v2.js          # Feature-specific extra logic (optional)
```

### ✅ **Pattern A — Simple Monolith (Read-only or minimal actions)**

Use when the feature is read-only with filtering, or has at most 1–2 simple actions. This is a valid and supported pattern — not deprecated.

```
📁 Feature Name (e.g., sessions)
├── 📄 sessions.twig    # UI template
└── 📄 sessions.js      # Single file — valid for simple features
```

**Real examples of Pattern A:** `sessions.js`, `permissions.js`

---

### 🔑 **File Naming Conventions**

| Pattern | File Type | Naming Pattern | Example |
|---------|-----------|----------------|---------|
| A — Simple | Template + JS | `{feature}.twig` + `{feature}.js` | `sessions.twig` + `sessions.js` |
| B — Modular | Template | `{feature}_list.twig` | `languages_list.twig` |
| B — Modular | Core JS | `{feature}-core-v2.js` or `{feature}-with-components-v2.js` | `languages-with-components-v2.js` |
| B — Modular | Modals | `{feature}-modals-v2.js` | `languages-modals-v2.js` |
| B — Modular | Actions | `{feature}-actions-v2.js` | `languages-actions-v2.js` |
| B — Modular | Helpers | `{feature}-helpers-v2.js` | `languages-helpers-v2.js` |

---

### 📦 **Module Responsibilities**

#### **1. Main File (`{feature}-with-components.js`)**
```javascript
// ✅ Responsibilities:
- DOMContentLoaded initialization
- DataTable setup with createTable()
- Filter form handling
- Pagination event listeners
- Calling other modules' functions

// ❌ Should NOT contain:
- Modal HTML/logic
- API calls (use actions module)
- Complex business logic
```

#### **2. Modals File (`{feature}-modals.js`)**
```javascript
// ✅ Responsibilities:
- showCreateModal()
- showEditModal()
- showDeleteConfirmModal()
- Modal DOM generation
- Form validation UI

// ❌ Should NOT contain:
- API calls
- Data processing
- Table rendering
```

#### **3. Actions File (`{feature}-actions.js`)**
```javascript
// ✅ Responsibilities:
- createLanguage(data)
- updateLanguage(id, data)
- deleteLanguage(id)
- API error handling
- Success callbacks

// ❌ Should NOT contain:
- DOM manipulation
- Modal logic
```

#### **4. Helpers File (`{feature}-helpers.js`)**
```javascript
// ✅ Responsibilities:
- validateForm(formData)
- formatDate(timestamp)
- buildQueryParams(filters)
- Utility functions

// ❌ Should NOT contain:
- Feature-specific logic
- API calls
```

---

## 2️⃣ **Twig Template Standards**

### ✅ **Required Structure**

```twig
{% extends "layouts/base.twig" %}

{% block title %}
    {Feature Name} | {{ ui.appName }}
{% endblock %}

{% block content %}
    {# ============================================================
       1️⃣ Page Header + Breadcrumb
       ============================================================ #}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h2 class="text-xl font-semibold text-gray-800">
            🌍 {Feature Name} Management  {# ✅ Use emoji for visual identity #}
        </h2>
        
        <nav>
            <ol class="flex items-center gap-1.5">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500"
                       href="{{ ui.adminUrl }}dashboard">
                        Home
                        <svg>...</svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800">{Feature Name}</li>
            </ol>
        </nav>
    </div>

    {# ============================================================
       2️⃣ Capabilities Injection (MANDATORY)
       ============================================================ #}
    {# 
       📌 CRITICAL RULES:
       - Twig MUST NOT check permissions by name
       - JavaScript MUST NOT infer authorization
       - Backend capabilities are the single UI contract
       - API authorization is always enforced server-side
    #}
    <script>
        window.{feature}Capabilities = {
            can_create        : {{ capabilities.can_create         ?? false ? 'true' : 'false' }},
            can_update        : {{ capabilities.can_update         ?? false ? 'true' : 'false' }},
            can_delete        : {{ capabilities.can_delete         ?? false ? 'true' : 'false' }},
            // Add all relevant capabilities here
        };
        
        console.log('🔐 {Feature} Capabilities Injected:', window.{feature}Capabilities);
    </script>

    {# ============================================================
       3️⃣ Search & Filters Section
       ============================================================ #}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <form id="{feature}-filter-form" class="space-y-4">
            {# Column Filters #}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {# ✅ Each filter with emoji label #}
                <div>
                    <label for="filter-id" class="block text-sm font-medium text-gray-700 mb-2">
                        🆔 ID
                    </label>
                    <input type="number" id="filter-id"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                           placeholder="e.g., 1, 2, 3..." />
                </div>
                
                {# Add more filters... #}
            </div>

            {# Action Buttons #}
            <div class="flex flex-wrap gap-3 pt-4">
                <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center gap-2">
                    <svg>...</svg>
                    Search
                </button>

                <button type="button" id="btn-reset"
                        class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium flex items-center gap-2">
                    <svg>...</svg>
                    Reset
                </button>

                {# ✅ Capability-based button visibility #}
                {% if capabilities.can_create %}
                    <button type="button" id="btn-create-{feature}"
                            class="ml-auto px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium flex items-center gap-2">
                        <svg>...</svg>
                        Create {Feature}
                    </button>
                {% endif %}
            </div>
        </form>
    </div>

    {# ============================================================
       4️⃣ Global Search Bar (Above Table)
       ============================================================ #}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
        <div class="flex items-center gap-3">
            <svg>...</svg>
            <input type="text" id="{feature}-search"
                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                   placeholder="🔍 Quick search..." />
            <button type="button" id="{feature}-search-btn"
                    class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                Search
            </button>
            <button type="button" id="{feature}-clear-search"
                    class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                Clear
            </button>
        </div>
    </div>

    {# ============================================================
       5️⃣ Data Table Container
       ============================================================ #}
    <div id="table-container" class="w-full"></div>
{% endblock %}

{% block scripts %}
    {# ✅ Shared infrastructure scripts (Order matters!) #}
    <script src="{{ asset('assets/maatify/admin-kernel/js/api_handler.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/callback_handler.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/Input_checker.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/data_table.js') }}"></script>

    {# ✅ Reusable components #}
    <script src="{{ asset('assets/maatify/admin-kernel/js/select2.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/admin-ui-components.js') }}"></script>

    {# ✅ Feature-specific scripts (Load in dependency order) #}
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{feature}-helpers.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{feature}-with-components.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{feature}-modals.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{feature}-actions.js') }}"></script>
{% endblock %}
```

---

### 🎨 **UI Component Standards**

#### **Color Palette**
```css
/* Primary Actions */
bg-blue-600     → Search, View, Primary buttons
bg-green-600    → Create, Success actions
bg-red-600      → Delete, Revoke, Danger actions
bg-orange-600   → Warning states
bg-gray-600     → Secondary, Disabled states

/* Status Badges */
bg-green-600    → Active, Current, Success
bg-blue-600     → Active (non-current)
bg-orange-600   → Expired, Warning
bg-red-600      → Revoked, Error, Deleted
bg-gray-600     → Inactive, Unknown
```

#### **Spacing**
```css
/* Form Elements */
px-4 py-2       → Input fields
px-6 py-2.5     → Buttons
p-6             → Card padding
gap-4           → Grid gaps
mb-6            → Section margins

/* Layout */
rounded-lg      → Cards, buttons, inputs
shadow-sm       → Cards
border          → All bordered elements
```

---

## 3️⃣ **JavaScript Architecture Standards**

### ✅ **Main File Structure**

```javascript
/**
 * 📄 {Feature}-with-components.js
 * Main initialization and table management
 */

document.addEventListener('DOMContentLoaded', () => {
    // ============================================================
    // 1️⃣ Constants & Configuration
    // ============================================================
    const CONFIG = {
        apiEndpoint: '{feature}/query',
        debounceDelay: 1000,
        defaultPerPage: 10
    };

    const headers = ["ID", "Name", "Status", "Actions"];
    const rows = ["id", "name", "status", "actions"];

    // ============================================================
    // 2️⃣ DOM Element References
    // ============================================================
    const filterForm = document.getElementById('{feature}-filter-form');
    const resetBtn = document.getElementById('btn-reset');
    const createBtn = document.getElementById('btn-create-{feature}');
    const searchInput = document.getElementById('{feature}-search');
    const searchBtn = document.getElementById('{feature}-search-btn');
    const clearSearchBtn = document.getElementById('{feature}-clear-search');

    // ============================================================
    // 3️⃣ State Management
    // ============================================================
    let currentFilters = {};
    let currentSearchTerm = '';
    let debounceTimer = null;

    // ============================================================
    // 4️⃣ Custom Renderers (Define ONCE at top)
    // ============================================================
    const statusRenderer = (value, row) => {
        const status = value?.toLowerCase();
        let statusClass = "bg-gray-600";
        let statusText = value || 'Unknown';

        if (status === 'active') {
            statusClass = "bg-green-600";
            statusText = "Active";
        } else if (status === 'inactive') {
            statusClass = "bg-red-600";
            statusText = "Inactive";
        }

        return `<span class="${statusClass} text-white px-3 py-1 rounded-lg text-xs font-medium uppercase tracking-wide">${statusText}</span>`;
    };

    const actionsRenderer = (value, row) => {
        const capabilities = window.{feature}Capabilities || {};
        let html = '<div class="flex gap-2">';

        if (capabilities.can_update) {
            html += `
                <button class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition edit-btn"
                        data-id="${row.id}">
                    Edit
                </button>
            `;
        }

        if (capabilities.can_delete) {
            html += `
                <button class="px-3 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700 transition delete-btn"
                        data-id="${row.id}">
                    Delete
                </button>
            `;
        }

        html += '</div>';
        return html;
    };

    // ============================================================
    // 5️⃣ Initialization
    // ============================================================
    init();

    function init() {
        console.log('🚀 {Feature} Module Initialized');
        loadData();
        setupEventListeners();
    }

    // ============================================================
    // 6️⃣ Event Listeners
    // ============================================================
    function setupEventListeners() {
        // Filter form submission
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                loadData();
            });
        }

        // Reset button
        if (resetBtn) {
            resetBtn.addEventListener('click', resetFilters);
        }

        // Create button
        if (createBtn) {
            createBtn.addEventListener('click', () => {
                window.{Feature}Modals?.openCreateModal?.();
            });
        }

        // Global search with debounce
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    currentSearchTerm = e.target.value.trim();
                    loadData();
                }, CONFIG.debounceDelay);
            });
        }

        if (searchBtn) {
            searchBtn.addEventListener('click', () => {
                currentSearchTerm = searchInput.value.trim();
                loadData();
            });
        }

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', () => {
                searchInput.value = '';
                currentSearchTerm = '';
                loadData();
            });
        }

        // Table action listeners (using event delegation)
        document.addEventListener('click', handleTableActions);

        // Pagination events
        document.addEventListener('tableAction', handlePaginationEvents);
    }

    // ============================================================
    // 7️⃣ Event Handlers
    // ============================================================
    function handleTableActions(e) {
        const btn = e.target.closest('.edit-btn, .delete-btn');
        if (!btn) return;

        const id = btn.getAttribute('data-{feature}-id');
        if (!id) return;

        if (btn.classList.contains('edit-btn')) {
            window.{Feature}Modals?.openEditModal?.(id);
        }

        if (btn.classList.contains('delete-btn')) {
            window.{Feature}Modals?.openDeleteModal?.(id);
        }
    }

    function handlePaginationEvents(e) {
        const { action, value, currentParams } = e.detail;
        let newParams = { ...currentParams };

        switch (action) {
            case 'pageChange':
                newParams.page = value;
                break;
            case 'perPageChange':
                newParams.per_page = value;
                newParams.page = 1;
                break;
        }

        loadDataWithParams(newParams);
    }

    // ============================================================
    // 8️⃣ Data Loading
    // ============================================================
    async function loadData(page = 1) {
        const params = buildParams(page);
        await loadDataWithParams(params);
    }

    async function loadDataWithParams(params) {
        console.log('📤 Sending params:', JSON.stringify(params, null, 2));

        if (typeof createTable !== 'function') {
            console.error('❌ createTable not found');
            return;
        }

        try {
            const result = await createTable(
                CONFIG.apiEndpoint,
                params,
                headers,
                rows,
                false, // showCheckboxes
                'id',
                null, // selectionCallback
                {
                    status: statusRenderer,
                    actions: actionsRenderer
                },
                null, // selectableIds
                null  // paginationInfoCallback
            );

            if (result?.success) {
                console.log('✅ Data loaded:', result.data.length);
            }
        } catch (error) {
            console.error('❌ Load error:', error);
            showAlert('danger', 'Failed to load data');
        }
    }

    // ============================================================
    // 9️⃣ Helper Functions
    // ============================================================
    function buildParams(page = 1, perPage = CONFIG.defaultPerPage) {
        const params = {
            page: page,
            per_page: perPage,
            search: {}
        };

        // Global search
        if (currentSearchTerm) {
            params.search.global = currentSearchTerm;
        }

        // Column filters
        const filters = {};

        const filterId = document.getElementById('filter-id')?.value.trim();
        if (filterId) filters.id = filterId;

        const filterName = document.getElementById('filter-name')?.value.trim();
        if (filterName) filters.name = filterName;

        const filterStatus = document.getElementById('filter-status')?.value;
        if (filterStatus) filters.status = filterStatus;

        if (Object.keys(filters).length > 0) {
            params.search.columns = filters;
        }

        // Clean empty search object
        if (!params.search.global && !params.search.columns) {
            delete params.search;
        }

        return params;
    }

    function resetFilters() {
        // Reset form inputs
        document.getElementById('filter-id').value = '';
        document.getElementById('filter-name').value = '';
        document.getElementById('filter-status').value = '';

        // Reset search
        if (searchInput) searchInput.value = '';
        currentSearchTerm = '';

        // Reload data
        loadData();
    }

    // ============================================================
    // 🔄 Export functions for other modules
    // ============================================================
    window.reload{Feature}Table = () => loadData(currentPage, currentPerPage);
});
```

---

### ✅ **Modals File Structure**

> See `JS_PATTERNS_REFERENCE.md` for the full copy-ready template.
> Key rules summarized below.

```javascript
/**
 * {feature}-modals.js
 * Modal HTML + open/close logic
 * Requires: api_handler.js, {feature}-helpers.js
 */

(function() {
    'use strict';

    const capabilities = window.{feature}Capabilities || {};

    // ================================================================
    // Modal HTML — inject ONCE into DOM on init, toggle with 'hidden'
    // Never recreate on every button click
    // ================================================================
    const createModalHTML = `
        <div id="create-{feature}-modal"
             class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 z-50 flex items-center justify-center hidden">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full mx-4 border border-transparent dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Create {Feature}</h3>
                    <button class="close-modal text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form id="create-{feature}-form" class="px-6 py-4 space-y-4">
                    <div>
                        <label for="create-name"
                               class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="create-name" required
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                               placeholder="Enter name..." />
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" class="close-modal px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            Create
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    // ================================================================
    // Open / Close helpers
    // ================================================================
    function openCreateModal() {
        if (!capabilities.can_create) {
            ApiHandler.showAlert('warning', 'You do not have permission to create');
            return;
        }
        document.getElementById('create-{feature}-modal').classList.remove('hidden');
        document.getElementById('create-{feature}-form').reset();
    }

    function closeCreateModal() {
        document.getElementById('create-{feature}-modal').classList.add('hidden');
    }

    // ================================================================
    // Form submit handler
    // ================================================================
    function setupCreateForm() {
        const form = document.getElementById('create-{feature}-form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const payload = {
                name: document.getElementById('create-name').value.trim(),
            };

            const result = await ApiHandler.call('{feature}/create', payload, 'Create {Feature}');

            if (result.success) {
                ApiHandler.showAlert('success', '{Feature} created successfully');
                closeCreateModal();
                window.reload{Feature}Table?.();
            } else {
                if (result.data?.errors) {
                    Object.values(result.data.errors).flat()
                        .forEach(msg => ApiHandler.showAlert('danger', msg));
                } else {
                    ApiHandler.showAlert('danger', result.error || 'Failed to create');
                }
            }
        });
    }

    // ================================================================
    // Close on backdrop or .close-modal button — no inline onclick
    // ================================================================
    function setupCloseHandlers() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('close-modal')) {
                const modal = e.target.closest('[id$="-modal"]');
                if (modal) modal.classList.add('hidden');
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('[id$="-modal"]')
                    .forEach(m => m.classList.add('hidden'));
            }
        });
    }

    // ================================================================
    // Init — inject modals once, export openers
    // ================================================================
    function init() {
        document.body.insertAdjacentHTML('beforeend', createModalHTML);
        setupCreateForm();
        setupCloseHandlers();

        window.{Feature}Modals = {
            openCreateModal,
            closeCreateModal,
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

> **Note:** The edit and delete modal patterns follow the same IIFE structure shown above.
> All modals MUST be injected once into the DOM on init and toggled with `hidden` class.
> Inline `onclick` handlers are FORBIDDEN — use `.close-modal` class + event delegation.
> Use `ApiHandler.showAlert()` for all notifications. Use `window.reload{Feature}Table?.()` after mutations.
> See `JS_PATTERNS_REFERENCE.md` Pattern B for the complete copy-ready template.



/**
 * Helper: Fetch single item for edit modal
 */
async function fetch{Feature}Data(id) {
    const result = await ApiHandler.call(`{feature}/query`, {
        page: 1, per_page: 1,
        search: { columns: { id: String(id) } }
    }, 'Fetch {Feature} Data');

    if (result.success && result.data?.data?.length > 0) {
        return result.data.data[0];
    }
    ApiHandler.showAlert('danger', 'Failed to load data');
    return null;
}
```

---

### ✅ **Actions File Structure**

```javascript
/**
 * {feature}-actions.js
 * Button event handlers + API calls for {Feature}
 * Requires: api_handler.js
 */

(function() {
    'use strict';

    if (typeof ApiHandler === 'undefined') {
        console.error('❌ ApiHandler not found!');
        return;
    }

    const capabilities = window.{feature}Capabilities || {};

    // ================================================================
    // Button handler helper — one per module
    // ================================================================
    function setupButtonHandler(selector, handler) {
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest(selector);
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();

            const entityId = btn.getAttribute('data-{feature}-id');
            if (!entityId) { console.error(`❌ No {feature} ID on:`, btn); return; }

            try {
                await handler(entityId, btn);
            } catch (error) {
                ApiHandler.showAlert('danger', 'An error occurred: ' + error.message);
            }
        });
    }

    // ================================================================
    // Action handlers — use ApiHandler.call(), never fetch() directly
    // ================================================================
    async function create{Feature}(data) {
        const result = await ApiHandler.call('{feature}/create', data, 'Create {Feature}');
        if (result.success) {
            ApiHandler.showAlert('success', '{Feature} created successfully');
            window.reload{Feature}Table?.();
            return true;
        }
        return false;
    }

    async function update{Feature}(id, data) {
        const result = await ApiHandler.call('{feature}/update', { id: parseInt(id), ...data }, 'Update {Feature}');
        if (result.success) {
            ApiHandler.showAlert('success', '{Feature} updated successfully');
            window.reload{Feature}Table?.();
            return true;
        }
        return false;
    }

    async function delete{Feature}(id) {
        const result = await ApiHandler.call('{feature}/delete', { id: parseInt(id) }, 'Delete {Feature}');
        if (result.success) {
            ApiHandler.showAlert('success', '{Feature} deleted successfully');
            window.reload{Feature}Table?.();
            return true;
        }
        return false;
    }

    async function toggle{Feature}Status(entityId, button) {
        const currentStatus = button.getAttribute('data-current-status');
        const newStatus = !(currentStatus === '1' || currentStatus === 'true');

        const result = await ApiHandler.call(
            '{feature}/set-active',
            { id: parseInt(entityId), is_active: newStatus },
            'Toggle Active'
        );
        if (result.success) {
            ApiHandler.showAlert('success', 'Status updated successfully');
            window.reload{Feature}Table?.();
        }
    }

    // ================================================================
    // Init — register handlers based on capabilities
    // ================================================================
    function init() {
        if (capabilities.can_set_active) {
            setupButtonHandler('.toggle-active-btn', toggle{Feature}Status);
        }
        if (capabilities.can_update) {
            setupButtonHandler('.edit-btn', (id) => {
                window.{Feature}Modals?.openEditModal?.(id);
            });
        }
        if (capabilities.can_delete) {
            setupButtonHandler('.delete-btn', (id) => {
                if (confirm('Are you sure?')) delete{Feature}(id);
            });
        }

        window.{Feature}Actions = { create{Feature}, update{Feature}, delete{Feature} };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

/**
 * Toggle status (activate/deactivate)
 * Note: use inside actions IIFE, not as a global window function
 */
async function toggle{Feature}Status(id, currentStatus) {
    const newStatus = !(currentStatus === 1 || currentStatus === '1' || currentStatus === true);

    const result = await ApiHandler.call(
        '{feature}/set-active',
        { id: parseInt(id), is_active: newStatus },
        'Toggle Active'
    );

    if (result.success) {
        ApiHandler.showAlert('success', 'Status updated successfully');
        window.reload{Feature}Table?.();
        return true;
    }
    return false;
}
```

---

### ✅ **Helpers File Structure**

```javascript
/**
 * 📄 {feature}-helpers.js
 * Utility functions for {Feature}
 */

/**
 * Validate form data
 */
window.validate{Feature}Form = function(data) {
    const errors = [];

    // Required fields
    if (!data.name || !data.name.trim()) {
        errors.push('Name is required');
    }

    // Length validation
    if (data.name && data.name.length > 255) {
        errors.push('Name must not exceed 255 characters');
    }

    // Format validation
    if (data.code && !/^[a-z]{2}$/.test(data.code)) {
        errors.push('Code must be 2 lowercase letters');
    }

    if (errors.length > 0) {
        errors.forEach(err => ApiHandler.showAlert('warning', err));
        return false;
    }

    return true;
};

/**
 * Format date/time
 */
window.format{Feature}DateTime = function(timestamp) {
    if (!timestamp) return 'N/A';

    const date = new Date(timestamp);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

/**
 * Truncate long text
 */
window.truncate{Feature}Text = function(text, maxLength = 50) {
    if (!text) return '';
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
};

/**
 * Sanitize HTML to prevent XSS
 */
window.sanitize{Feature}HTML = function(html) {
    const temp = document.createElement('div');
    temp.textContent = html;
    return temp.innerHTML;
};

/**
 * Build query string from object
 */
window.build{Feature}QueryString = function(params) {
    const searchParams = new URLSearchParams();

    for (const [key, value] of Object.entries(params)) {
        if (value !== null && value !== undefined && value !== '') {
            if (typeof value === 'object') {
                searchParams.append(key, JSON.stringify(value));
            } else {
                searchParams.append(key, value);
            }
        }
    }

    return searchParams.toString();
};

/**
 * Deep clone object
 */
window.clone{Feature}Object = function(obj) {
    return JSON.parse(JSON.stringify(obj));
};

/**
 * Debounce function
 */
window.debounce{Feature} = function(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

/**
 * Copy to clipboard
 */
window.copy{Feature}ToClipboard = async function(text, showNotification = true) {
    try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(text);
        } else {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }

        if (showNotification) {
            ApiHandler.showAlert('success', 'Copied to clipboard');
        }

        return true;
    } catch (error) {
        console.error('❌ Copy failed:', error);
        if (showNotification) {
            ApiHandler.showAlert('danger', 'Failed to copy');
        }
        return false;
    }
};
```

---

## 4️⃣ **UI/UX Standards**

### ✅ **Visual Design Principles**

1. **Consistent Spacing**
   - Use Tailwind's spacing scale consistently
   - `gap-3` or `gap-4` for button groups
   - `mb-6` for section separation
   - `p-6` for card padding

2. **Color Usage**
   ```
   Blue (Primary):   Actions, links, info
   Green (Success):  Create, active states
   Red (Danger):     Delete, revoke, errors
   Orange (Warning): Expired, warnings
   Gray (Neutral):   Secondary, disabled
   ```

3. **Typography**
   - Page titles: `text-xl font-semibold`
   - Section headers: `text-lg font-semibold`
   - Labels: `text-sm font-medium`
   - Body text: `text-sm` or `text-base`

4. **Interactive Elements**
   - All buttons must have hover states
   - Use `transition-all duration-300` or `transition-colors`
   - Add visual feedback (loading states, disabled states)

5. **Status Indicators**
   - Always use colored badges with uppercase text
   - Include icon or emoji when appropriate
   - Use consistent color coding

---

### ✅ **Emoji Usage Guidelines**

**When to use emojis:**
- Page titles (e.g., "🌍 Languages Management")
- Filter labels (e.g., "🆔 ID", "📊 Status")
- Success/error messages
- Modal titles
- Search placeholders

**Best practices:**
- One emoji per label/title
- Choose relevant, professional emojis
- Be consistent across similar features

---

### ✅ **Form Design Standards**

```html
<!-- Standard Input Field -->
<div>
    <label for="input-id" class="block text-sm font-medium text-gray-700 mb-2">
        🏷️ Field Name <span class="text-red-500">*</span>
    </label>
    <input
        type="text"
        id="input-id"
        required
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
        placeholder="Enter value..."
    />
    <p class="text-xs text-gray-500 mt-1">Helper text here</p>
</div>

<!-- Standard Select -->
<div>
    <label for="select-id" class="block text-sm font-medium text-gray-700 mb-2">
        📋 Select Option
    </label>
    <select
        id="select-id"
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white"
    >
        <option value="">All Options</option>
        <option value="1">Option 1</option>
    </select>
</div>
```

---

## 5️⃣ **Dark Mode Standards**

> **⚠️ MANDATORY:** All new features MUST support dark mode from day one.

### 🎯 **Core Principles**

1. ✅ **Never use hardcoded colors** - Always use Tailwind `dark:` variants
2. ✅ **Test in both modes** - Every UI element must work in light AND dark
3. ✅ **Use CSS variables** - For complex components (defined in `style.css`)
4. ✅ **Maintain contrast** - Ensure WCAG-compliant readability

---

### 📋 **Standard Dark Mode Patterns**

#### **Backgrounds**
```html
bg-white dark:bg-gray-800              <!-- Primary container -->
bg-gray-50 dark:bg-gray-900            <!-- Secondary container -->
bg-gray-100 dark:bg-gray-700           <!-- Tertiary container -->
hover:bg-gray-100 dark:hover:bg-gray-700  <!-- Hover states -->
```

#### **Text Colors**
```html
text-gray-900 dark:text-gray-100       <!-- Primary text -->
text-gray-800 dark:text-gray-200       <!-- Headings -->
text-gray-700 dark:text-gray-300       <!-- Secondary text -->
text-gray-600 dark:text-gray-400       <!-- Tertiary text -->
```

#### **Borders & Shadows**
```html
border-gray-200 dark:border-gray-700   <!-- Standard border -->
border-gray-300 dark:border-gray-600   <!-- Input border -->
shadow-sm dark:shadow-gray-900/50      <!-- Light shadow -->
```

---

### 📝 **Component Templates**

#### **Card/Container**
```html
<div class="bg-white dark:bg-gray-800
            rounded-lg shadow-sm dark:shadow-gray-900/50
            border border-gray-200 dark:border-gray-700 p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
        Title
    </h3>
    <p class="text-sm text-gray-700 dark:text-gray-300">
        Content
    </p>
</div>
```

#### **Form Input**
```html
<input
    type="text"
    class="w-full px-4 py-2
           bg-white dark:bg-gray-700
           text-gray-900 dark:text-gray-100
           border border-gray-300 dark:border-gray-600
           placeholder-gray-400 dark:placeholder-gray-500
           rounded-lg focus:ring-2 focus:ring-blue-500
           transition-all"
/>
```

#### **Buttons**
```html
<!-- Primary -->
<button class="px-6 py-2.5 bg-blue-600 dark:bg-blue-500 text-white
               hover:bg-blue-700 dark:hover:bg-blue-600
               rounded-lg transition-colors">Primary</button>

<!-- Secondary -->
<button class="px-6 py-2.5 bg-gray-200 dark:bg-gray-700
               text-gray-700 dark:text-gray-200
               hover:bg-gray-300 dark:hover:bg-gray-600
               rounded-lg transition-colors">Secondary</button>
```

---

### 🔧 **JavaScript Dynamic HTML**

```javascript
// ❌ WRONG: Missing dark mode
const html = `<button class="bg-blue-600 text-white">Click</button>`;

// ✅ CORRECT: Complete dark mode support
const html = `
    <button class="bg-blue-600 dark:bg-blue-500 text-white
                   hover:bg-blue-700 dark:hover:bg-blue-600
                   transition-colors">
        Click
    </button>
`;
```

---

### 🧪 **Testing Checklist**

- [ ] Toggle between light/dark mode multiple times
- [ ] All backgrounds visible in both modes
- [ ] All text readable with proper contrast
- [ ] Borders visible in both modes
- [ ] Hover/focus states work correctly
- [ ] No hardcoded hex colors in HTML/Twig
- [ ] No console warnings about missing variants

---

### ⚠️ **Common Mistakes**

```html
<!-- ❌ Missing hover dark variant -->
<button class="hover:bg-gray-100">Button</button>

<!-- ✅ Complete -->
<button class="hover:bg-gray-100 dark:hover:bg-gray-700">Button</button>

<!-- ❌ Hardcoded color -->
<div style="background: #ffffff">Content</div>

<!-- ✅ Utility classes -->
<div class="bg-white dark:bg-gray-800">Content</div>

<!-- ❌ Poor contrast -->
<p class="text-gray-500 dark:text-gray-600">Text</p>

<!-- ✅ Good contrast -->
<p class="text-gray-700 dark:text-gray-300">Text</p>
```

---

### 📚 **Quick Reference**

| Element | Light | Dark |
|---------|-------|------|
| Primary BG | `bg-white` | `dark:bg-gray-800` |
| Secondary BG | `bg-gray-50` | `dark:bg-gray-900` |
| Hover BG | `hover:bg-gray-100` | `dark:hover:bg-gray-700` |
| Primary Text | `text-gray-900` | `dark:text-gray-100` |
| Secondary Text | `text-gray-700` | `dark:text-gray-300` |
| Border | `border-gray-200` | `dark:border-gray-700` |
| Shadow | `shadow-sm` | `dark:shadow-gray-900/50` |
| Input BG | `bg-white` | `dark:bg-gray-700` |

---

### 🚨 **Enforcement**

- Code reviews MUST check for dark mode support
- PRs without dark mode will be rejected
- New features must have dark mode from day one

---


## 6️⃣ **Security Standards**

### ✅ **Capability-Based Access Control**

#### **Server-Side (PHP/Twig)**
```php
// AuthorizationService
$capabilities = [
    'can_create'  => $this->hasPermission('feature.create'),
    'can_update'  => $this->hasPermission('feature.update'),
    'can_delete'  => $this->hasPermission('feature.delete'),
];

return $twig->render('feature_list.twig', [
    'capabilities' => $capabilities
]);
```

#### **Client-Side (Twig Template)**
```twig
{# ✅ CORRECT: Inject capabilities as JavaScript object #}
<script>
    window.{feature}Capabilities = {
        can_create: {{ capabilities.can_create ?? false ? 'true' : 'false' }},
        can_update: {{ capabilities.can_update ?? false ? 'true' : 'false' }},
        can_delete: {{ capabilities.can_delete ?? false ? 'true' : 'false' }}
    };
</script>

{# ✅ CORRECT: Show/hide UI based on capability #}
{% if capabilities.can_create %}
    <button id="btn-create">Create</button>
{% endif %}

{# ❌ WRONG: Don't check permission names #}
{% if user.hasPermission('feature.create') %}  <!-- DON'T DO THIS -->
```

#### **JavaScript**
```javascript
// ✅ CORRECT: Check capabilities before action
const capabilities = window.{feature}Capabilities || {};

if (capabilities.can_create) {
    // Show create button
}

// ❌ WRONG: Don't infer authorization
if (user.role === 'admin') {  // DON'T DO THIS
```

---

### ✅ **XSS Prevention**

```javascript
// ✅ CORRECT: Sanitize user input before rendering
function sanitizeHTML(str) {
    const temp = document.createElement('div');
    temp.textContent = str;
    return temp.innerHTML;
}

const userName = sanitizeHTML(userData.name);
element.innerHTML = `<span>${userName}</span>`;

// ❌ WRONG: Direct HTML injection
element.innerHTML = userData.name; // DANGEROUS!
```

---

### ✅ **CSRF Protection**

```javascript
// ✅ Include CSRF token in requests
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify(data)
});
```

---

## 7️⃣ **API Integration Standards**

### ✅ **Query Parameters Structure**

```javascript
const params = {
    page: 1,
    per_page: 10,
    search: {
        global: "search term",      // Global search across all columns
        columns: {                   // Column-specific filters
            id: 123,
            name: "John",
            status: "active"
        }
    },
    sort: {
        column: "created_at",
        direction: "desc"
    }
};
```

---

### ✅ **API Response Format**

```json
{
    "success": true,
    "message": "Data retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "Item 1",
            "status": "active"
        }
    ],
    "pagination": {
        "page": 1,
        "per_page": 10,
        "total": 100,
        "filtered": 50,
        "total_pages": 10
    }
}
```

---

### ✅ **Error Handling**

```javascript
// CORRECT — always use ApiHandler.call(), never fetch() directly
const result = await ApiHandler.call('{feature}/create', payload, 'Create {Feature}');

if (result.success) {
    ApiHandler.showAlert('success', 'Created successfully');
    window.reload{Feature}Table?.();
} else {
    // Validation errors (422)
    if (result.data?.errors) {
        Object.entries(result.data.errors).forEach(([field, messages]) => {
            ApiHandler.showAlert('danger', messages.join(', '));
        });
    } else {
        ApiHandler.showAlert('danger', result.error || 'Operation failed');
    }
}
```

---

## 8️⃣ **Code Quality Standards**

### ✅ **Console Logging Guidelines**

```javascript
// ✅ Use emoji prefixes for quick visual scanning
console.log('🚀 Initializing module...');
console.log('📤 Sending request:', data);
console.log('📥 Received response:', result);
console.log('✅ Success:', message);
console.log('❌ Error:', error);
console.log('⚠️ Warning:', warning);
console.log('🔍 Debug:', debugData);
console.log('🎯 Target:', target);
console.log('🔄 Reloading:', context);
console.log('📊 Stats:', statistics);

// ❌ Avoid generic logs
console.log('data'); // What data?
console.log(result); // What result?
```

---

### ✅ **Code Comments**

```javascript
// ✅ GOOD: Section dividers with context
// ============================================================
// 🚀 Initialization
// ============================================================

// ✅ GOOD: Explain WHY, not WHAT
// Fix: Global queries fail in multi-table contexts
const container = document.querySelector('.modal-body');

// ❌ BAD: States the obvious
// Get the button
const button = document.getElementById('btn-submit');
```

---

### ✅ **Function Organization**

```javascript
// ✅ Organize functions logically:

// 1️⃣ Configuration & Constants
// 2️⃣ State Management
// 3️⃣ Initialization
// 4️⃣ Event Listeners Setup
// 5️⃣ Event Handlers
// 6️⃣ Data Loading Functions
// 7️⃣ API Calls
// 8️⃣ Helper/Utility Functions
// 9️⃣ Export Functions
```

---

### ✅ **Naming Conventions**

```javascript
// Variables: camelCase
const userName = 'John';
const isActive = true;
const maxRetries = 3;

// Constants: UPPER_SNAKE_CASE
const API_ENDPOINT = '/api/users';
const DEFAULT_TIMEOUT = 5000;

// Functions: camelCase with verb prefix
function loadUserData() {}
function handleFormSubmit() {}
function validateEmail() {}

// Classes: PascalCase
class UserManager {}
class DataTable {}

// Boolean variables: is/has/can prefix
const isLoading = false;
const hasPermission = true;
const canEdit = false;

// Event handlers: handle prefix
function handleButtonClick(e) {}
function handleFormSubmit(e) {}
```

---

## 9️⃣ **Decision Framework**

### 📊 **When to Use Which Pattern**

```
Does the feature need CRUD, modals, or multiple action buttons per row?
├── Yes → Pattern B (Modular)
└── No  → What type of data display?
           ├── POST paginated list  → Pattern A (Simple Monolith)
           ├── GET flat array       → Pattern C (GET Static List)
           └── Needs parent IDs     → Pattern D (Context-Driven)
```

| Pattern | When | JS Files | Example |
|---------|------|----------|---------|
| A — Simple Monolith | Read-only, filter, 1–2 actions | 1 file | `sessions.js` |
| B — Modular | Full CRUD, modals, multiple actions | 4 files | `languages-*.js` |
| C — GET Static | Non-paginated flat array | 1 IIFE file | `i18n-scope-coverage.js` |
| D — Context-Driven | Needs scope_id / domain_id from URL | 1 file + context | `i18n_scope_domain_translations.js` |

### 🎯 **Complexity Threshold**

```
Lines of Code          Pattern
──────────────────────────────────────────────
< 400 lines            Pattern A acceptable
> 400 lines            Consider Pattern B
Has modals             Pattern B required
Has CRUD               Pattern B required
```

---

### ⚖️ **Trade-offs Comparison**

| Aspect           | Monolith    | Modular     |
|------------------|-------------|-------------|
| Initial Dev Time | ✅ Faster    | ❌ Slower    |
| Maintenance      | ❌ Harder    | ✅ Easier    |
| Debugging        | ❌ Difficult | ✅ Easy      |
| Collaboration    | ❌ Conflicts | ✅ Parallel  |
| Scalability      | ❌ Limited   | ✅ Excellent |
| Learning Curve   | ✅ Low       | ⚠️ Medium   |
| Code Reuse       | ❌ Poor      | ✅ Excellent |

---

## 9️⃣ **Select2 Component Standards**

### 📦 **What is Select2?**

Select2 is a custom dropdown component with search functionality - a lightweight alternative to native `<select>` elements with better UX.

**File:** `select2.js` (197 lines)
**Dependencies:** Zero (vanilla JavaScript)
**CSS:** Tailwind CSS

---

### ✅ **When to Use Select2**

**✅ USE Select2 when:**
- Dropdown has 10+ options
- Search/filter functionality is needed
- Better UX than native `<select>` required
- Custom styling needed
- Programmatic control needed
- Language selection, country selection, category selection, etc.

**❌ DON'T USE Select2 when:**
- Only 2-3 options (native `<select>` is simpler)
- Multi-select needed (not supported)
- Option groups needed (not supported)
- Strict accessibility requirements (use native)

---

### 🏗️ **HTML Structure**

```html
<!-- Container must have 'relative' positioning -->
<div id="my-select" class="w-full relative">
   <!-- Select Box (Trigger) -->
   <div class="js-select-box relative flex items-center justify-between px-4 py-2 border border-gray-300 rounded-lg cursor-pointer hover:border-gray-400 transition-colors bg-white">
      <input type="text"
             class="js-select-input pointer-events-none bg-transparent flex-1 outline-none text-gray-700"
             placeholder="Select an option..."
             readonly>
      <span class="js-arrow ml-2 transition-transform duration-200 text-gray-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </span>
   </div>

   <!-- Dropdown (Hidden by default) -->
   <div class="js-dropdown hidden absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-64">
      <!-- Search Input -->
      <div class="p-2 border-b border-gray-200">
         <input type="text"
                class="js-search-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                placeholder="🔍 Search...">
      </div>
      <!-- Options List -->
      <ul class="js-select-list max-h-48 overflow-y-auto"></ul>
   </div>
</div>
```

---

### 🔑 **Required CSS Classes**

| Class              | Element   | Purpose                           | Required |
|--------------------|-----------|-----------------------------------|----------|
| `.js-select-box`   | `<div>`   | Clickable trigger area            | ✅ Yes    |
| `.js-select-input` | `<input>` | Display selected value (readonly) | ✅ Yes    |
| `.js-arrow`        | `<span>`  | Arrow icon (rotates when open)    | ✅ Yes    |
| `.js-dropdown`     | `<div>`   | Dropdown container (hidden/shown) | ✅ Yes    |
| `.js-search-input` | `<input>` | Search/filter input               | ✅ Yes    |
| `.js-select-list`  | `<ul>`    | Options list container            | ✅ Yes    |

**⚠️ CRITICAL:** These classes are mandatory - the component won't work without them!

---

### 📊 **Data Format**

```javascript
// Required structure
const data = [
   {
      value: 'unique-id',    // Any type: string, number, etc.
      label: 'Display Text'  // String: shown to user
   }
];
```

**Examples:**

```javascript
// Simple options
const colors = [
   { value: 'red', label: 'Red' },
   { value: 'blue', label: 'Blue' }
];

// With IDs
const users = [
   { value: 1, label: 'John Doe' },
   { value: 2, label: 'Jane Smith' }
];

// With emojis/icons (recommended for better UX)
const languages = [
   { value: 1, label: '🇬🇧 English (en)' },
   { value: 2, label: '🇪🇬 العربية (ar)' },
   { value: 3, label: '🇫🇷 Français (fr)' }
];
```

---

### 🚀 **Initialization**

```javascript
// Basic initialization
const mySelect = Select2('#my-select', data);

// With options
const mySelect = Select2('#my-select', data, {
   defaultValue: 1,              // Pre-select this value
   onChange: (value) => {        // Callback when selection changes
      console.log('Selected:', value);
      // Handle selection change
   }
});

// Always check if initialization succeeded
if (!mySelect) {
   console.error('❌ Select2 initialization failed');
   return;
}
```

---

### 🔧 **Public API Methods**

```javascript
// Open dropdown programmatically
mySelect.open();

// Close dropdown programmatically
mySelect.close();

// Get selected value (returns the 'value' property)
const selectedValue = mySelect.getValue();
console.log(selectedValue); // Returns: 1, 'red', etc., or null

// Get full selected object
const selectedObject = mySelect.getSelected();
console.log(selectedObject); // Returns: {value: 1, label: 'English'} or null

// Clean up before removing from DOM
mySelect.destroy();
```

---

### 💡 **Usage Patterns**

#### **Pattern 1: Form Integration**

```javascript
// In modals.js or main file
const countrySelect = Select2('#country-select', countries);

document.getElementById('my-form').addEventListener('submit', (e) => {
   e.preventDefault();

   const selectedCountry = countrySelect.getValue();

   if (!selectedCountry) {
      ApiHandler.showAlert('warning', 'Please select a country');
      return;
   }

   const formData = {
      country: selectedCountry,
      // ... other fields
   };

   // Submit form
   submitForm(formData);
});
```

---

#### **Pattern 2: Dependent Dropdowns**

```javascript
// In main initialization file
let subcategorySelect = null;

const categorySelect = Select2('#category-select', categories, {
   onChange: async (categoryId) => {
      console.log('🔄 Category changed:', categoryId);

      // Load subcategories for selected category
      const subcategories = await fetchSubcategories(categoryId);

      // Destroy old subcategory select if exists
      if (subcategorySelect) {
         subcategorySelect.destroy();
      }

      // Create new subcategory select with fresh data
      subcategorySelect = Select2('#subcategory-select', subcategories);
   }
});
```

---

#### **Pattern 3: Dynamic API Data**

```javascript
// In modals.js - when opening modal
async function showEditModal(id) {
   // ... modal HTML insertion ...

   // Load options from API using ApiHandler — never fetch() directly
   const result = await ApiHandler.call('languages/list', {}, 'Load Languages', 'GET');

   if (!result.success) {
      ApiHandler.showAlert('danger', 'Failed to load languages');
      return;
   }

   // Transform API data to Select2 format
   const languageOptions = result.data.map(lang => ({
      value: String(lang.id),
      label: `${lang.icon || '🌍'} ${lang.name} (${lang.code})`,
      search: lang.code
   }));

   // Initialize Select2 with API data
   const languageSelect = Select2('#edit-language', languageOptions, {
      defaultValue: currentLanguageId,  // Pre-select current value
      onChange: (value) => {
         console.log('📝 Language changed to:', value);
      }
   });

   // Store instance for form submission
   window.currentLanguageSelect = languageSelect;
}
```

---

#### **Pattern 4: Multiple Independent Instances**

```javascript
// In main file - multiple Select2 instances on same page
let countrySelect = null;
let languageSelect = null;
let timezoneSelect = null;

function initializeFilters() {
   // Country dropdown
   countrySelect = Select2('#country-select', countries, {
      onChange: (value) => console.log('Country:', value)
   });

   // Language dropdown
   languageSelect = Select2('#language-select', languages, {
      onChange: (value) => console.log('Language:', value)
   });

   // Timezone dropdown
   timezoneSelect = Select2('#timezone-select', timezones, {
      onChange: (value) => console.log('Timezone:', value)
   });
}

// Each instance is completely independent
function getFormData() {
   return {
      country: countrySelect.getValue(),
      language: languageSelect.getValue(),
      timezone: timezoneSelect.getValue()
   };
}
```

---

### ⚠️ **Critical Notes**

#### **1. Container Positioning**

```html
<!-- ✅ CORRECT: Container must have 'relative' -->
<div id="my-select" class="w-full relative">
   ...
</div>

<!-- ❌ WRONG: Dropdown won't position correctly -->
<div id="my-select" class="w-full">
   ...
</div>
```

---

#### **2. Z-Index in Modals**

```html
<!-- Default z-index may be covered by modals -->
<div class="js-dropdown ... z-50">

   <!-- ✅ Increase z-index if inside modal -->
   <div class="js-dropdown ... z-[60]">
```

**Fix if Select2 appears behind modal:**
- Modal typically uses `z-50`
- Set dropdown to `z-[60]` or higher

---

#### **3. Read-Only Input**

```html
<!-- ✅ CORRECT: Must be readonly and pointer-events-none -->
<input class="js-select-input pointer-events-none bg-transparent" readonly>

<!-- ❌ WRONG: Never remove readonly or pointer-events-none -->
<input class="js-select-input bg-transparent">
```

**Why?**
- `readonly`: Prevents typing
- `pointer-events-none`: Click passes through to container

---

#### **4. Memory Leaks Prevention**

```javascript
// ❌ BAD: Select2 event listeners remain after modal is hidden
// (destroy Select2 before hiding the modal)

// ✅ GOOD: Destroy Select2 first, then hide modal with class toggle
if (mySelect) {
   mySelect.destroy();  // Remove Select2 event listeners
   mySelect = null;
}
document.getElementById('my-modal').classList.add('hidden');
// Never use modal.remove() — modals must be toggled, not destroyed
```

---

#### **5. Initialization Check**

```javascript
// ❌ BAD: Assumes initialization succeeded
const mySelect = Select2('#my-select', data);
mySelect.open(); // May fail if container not found

// ✅ GOOD: Always check return value
const mySelect = Select2('#my-select', data);

if (!mySelect) {
   console.error('❌ Select2 initialization failed');
   console.error('   - Check if container #my-select exists');
   console.error('   - Check if HTML structure has required classes');
   return;
}

// Safe to use
mySelect.open();
```

---

### 🎯 **Integration with Modular Architecture**

```javascript
/**
 * Example: Language Fallback Modal with Select2
 * Part of languages-modals.js IIFE
 */

const fallbackModalHTML = `
    <div id="fallback-modal"
         class="fixed inset-0 bg-gray-900 bg-opacity-50 dark:bg-opacity-70 flex items-center justify-center z-50 hidden">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 border dark:border-gray-700">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Set Fallback Language</h3>

                <div id="fallback-language-select" class="w-full relative">
                    <div class="js-select-box flex items-center justify-between px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-white dark:bg-gray-700">
                        <input type="text"
                               class="js-select-input pointer-events-none bg-transparent flex-1 outline-none text-gray-700 dark:text-gray-200"
                               placeholder="Select fallback language..." readonly>
                        <svg class="js-arrow w-4 h-4 text-gray-500 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                    <div class="js-dropdown hidden absolute z-[60] mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg shadow-lg">
                        <div class="p-2">
                            <input type="text" class="js-search-input w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded bg-white dark:bg-gray-700 outline-none" placeholder="Search...">
                        </div>
                        <ul class="js-select-list max-h-48 overflow-y-auto py-1"></ul>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" class="close-modal px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                    <button type="button" id="btn-confirm-fallback"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Set Fallback
                    </button>
                </div>
            </div>
        </div>
    </div>
`;

let fallbackSelect2 = null;
let currentFallbackLanguageId = null;

async function openFallbackModal(languageId) {
    currentFallbackLanguageId = languageId;

    // Fetch available languages via ApiHandler — never use fetch() directly
    const result = await ApiHandler.call(
        `languages/available-for-fallback/${languageId}`,
        {},
        'Load Fallback Languages',
        'GET'
    );

    if (!result.success) {
        ApiHandler.showAlert('danger', 'Failed to load languages');
        return;
    }

    // Initialize Select2 with API data
    const options = result.data.map(lang => ({
        value: String(lang.id),
        label: `${lang.icon || '🌍'} ${lang.name} (${lang.code})`,
        search: lang.code
    }));

    fallbackSelect2 = Select2('#fallback-language-select', options, { defaultValue: '' });

    document.getElementById('fallback-modal').classList.remove('hidden');
}

function closeFallbackModal() {
    fallbackSelect2?.destroy();
    fallbackSelect2 = null;
    document.getElementById('fallback-modal').classList.add('hidden');
}

// Confirm button — no inline onclick
document.getElementById('btn-confirm-fallback')?.addEventListener('click', async () => {
    const fallbackId = fallbackSelect2?.getValue();

    if (!fallbackId) {
        ApiHandler.showAlert('warning', 'Please select a fallback language');
        return;
    }

    const result = await ApiHandler.call(
        'languages/set-fallback',
        { id: parseInt(currentFallbackLanguageId), fallback_language_id: parseInt(fallbackId) },
        'Set Fallback Language'
    );

    if (result.success) {
        ApiHandler.showAlert('success', 'Fallback language set successfully');
        closeFallbackModal();
        window.reloadLanguagesTable?.();
    }
});
```

---

### 🧪 **Testing Checklist**

Before committing code with Select2:

- [ ] Dropdown opens on click
- [ ] Dropdown closes on outside click
- [ ] Search filters items correctly (case-insensitive)
- [ ] Selected item shows checkmark
- [ ] Arrow rotates 180° when open
- [ ] `onChange` callback fires when selection changes
- [ ] `getValue()` returns correct value
- [ ] `getSelected()` returns full object
- [ ] `defaultValue` pre-selects correct item
- [ ] `destroy()` cleans up event listeners
- [ ] Works with multiple instances on same page
- [ ] "No results found" shows when search has no matches
- [ ] Z-index correct when inside modals
- [ ] Mobile touch works properly
- [ ] No console errors on init/destroy

---

### 📚 **Reference Documentation**

For complete API reference and more examples, see:
- `/mnt/project/SELECT2_DOCUMENTATION.md`

---

## 📚 **Quick Reference Checklist**

Before starting a new feature, check:

### ✅ **Planning Phase**
- [ ] Read this standards document
- [ ] Identify if feature needs modular architecture
- [ ] List all required capabilities
- [ ] Design API contract first
- [ ] Review similar existing features

### ✅ **Development Phase**
- [ ] Create file structure following standards
- [ ] Inject capabilities in Twig template
- [ ] Use emoji prefixes in UI labels
- [ ] Implement custom renderers for status/actions
- [ ] Add comprehensive console logging
- [ ] Handle errors properly in all API calls
- [ ] Use event delegation for dynamic elements

### ✅ **Testing Phase**
- [ ] Test with/without each capability
- [ ] Test pagination and filters
- [ ] Test global + column search together
- [ ] Test error scenarios (network, 500, 403, 404)
- [ ] Test on different screen sizes
- [ ] Verify XSS protection

### ✅ **Code Review Phase**
- [ ] No hardcoded permissions in frontend
- [ ] All user input sanitized
- [ ] Console logs have emoji prefixes
- [ ] Files properly separated (if modular)
- [ ] Functions in logical order
- [ ] Comments explain WHY not WHAT

---

## 🆘 **Common Mistakes to Avoid**

### ❌ **Security Mistakes**

```javascript
// ❌ DON'T: Check permissions by name
if (user.role === 'admin') {
   showDeleteButton();
}

// ✅ DO: Use injected capabilities
if (window.{feature}Capabilities.can_delete) {
   showDeleteButton();
}
```

```javascript
// ❌ DON'T: Direct HTML injection
element.innerHTML = userData.name;

// ✅ DO: Sanitize first
element.textContent = userData.name;
// OR
element.innerHTML = sanitizeHTML(userData.name);
```

---

### ❌ **Architecture Mistakes**

```javascript
// ❌ DON'T: Mix concerns
function createLanguage(data) {
   // Show modal
   const modal = showModal();
   // Validate form
   if (!validate(data)) return;
   // Make API call
   await fetch('/api/create', {...});
   // Update table
   reloadTable();
}

// ✅ DO: Separate concerns
// In modals.js
function showCreateModal() { ... }

// In actions.js
function createLanguage(data) { ... }

// In main.js
function reloadTable() { ... }
```

---

### ❌ **UI/UX Mistakes**

```html
<!-- ❌ DON'T: Inconsistent spacing -->
<button class="px-3 py-1">Button 1</button>
<button class="px-6 py-2.5">Button 2</button>

<!-- ✅ DO: Use consistent spacing -->
<button class="px-6 py-2.5">Button 1</button>
<button class="px-6 py-2.5">Button 2</button>
```

```javascript
// ❌ DON'T: Generic or native alerts
alert('Error');

// ✅ DO: Pattern A (api_handler.js NOT loaded)
showAlert('d', 'Failed to create. Name already exists.');

// ✅ DO: Pattern B/C/D (api_handler.js IS loaded)
ApiHandler.showAlert('danger', 'Failed to create. Name already exists.');
```

---

## 🎓 **Learning Resources**

### **Internal Documentation**
- `TWIG_TEMPLATE_STANDARDS.md` — Twig block structure, capabilities injection, script loading order
- `JS_PATTERNS_REFERENCE.md` — All 4 JS patterns with copy-ready templates
- `IMPLEMENTATION_CHECKLIST.md` — Decision tree + pre-delivery self-check
- `DATA_TABLE_DOCUMENTATION.md` — createTable() vs TableComponent(), tableAction events
- `API_HANDLER_DOCUMENTATION.md` — ApiHandler.call() complete reference
- `COMMON_MISTAKES.md` — 12 real mistakes with wrong and correct code
- `SELECT2_DOCUMENTATION.md` — Select2 dropdown component
- `REUSABLE_COMPONENTS_GUIDE.md` — AdminUIComponents reference

### **Example Implementations**
- `sessions.twig` + `sessions.js` — ✅ Pattern A reference (simple monolith)
- `languages_list.twig` + `languages-*.js` — ✅ Pattern B reference (full modular)
- `i18n-scope-coverage.js` — ✅ Pattern C reference (GET static list)
- `scope_domain_translations.twig` + `i18n_scope_domain_translations.js` — ✅ Pattern D reference (context-driven)

---

## 🔄 **Version History**

| Version | Date     | Changes                                                           |
|---------|----------|-------------------------------------------------------------------|
| 1.0     | Feb 2026 | Initial standards document based on languages_list implementation |

---

## 📞 **Questions?**

If you're unsure about any standard:

1. ✅ Check if `languages_list` implementation handles it
2. ✅ Review similar features in the codebase
3. ✅ Ask for architectural review before starting
4. ✅ When in doubt, choose modular over monolith

---

## 🎯 **Final Reminder**

> **"Write code that your future self will thank you for."**

The extra 2-3 hours spent on proper architecture will save you 20-30 hours in maintenance. Always choose the modular approach for production features. ✅
