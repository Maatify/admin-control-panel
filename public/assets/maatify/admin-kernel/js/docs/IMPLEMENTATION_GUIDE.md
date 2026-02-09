# 🚀 Feature Implementation Guide

**Project:** `maatify/admin-control-panel`  
**Purpose:** Step-by-step guide to implement a new admin feature correctly from the first time  
**Based On:** Languages & Scopes implementations (proven patterns)

---

## 📖 Overview

This guide walks you through creating a **complete admin CRUD feature** following established best practices. By following these steps, you'll create code that:

✅ Matches the project's architecture  
✅ Works correctly on first try  
✅ Is easy to maintain and extend  
✅ Passes code review quickly  

**Estimated time:** 4-6 hours for a complete feature

---

## 🎯 What You'll Build

By the end of this guide, you'll have:

```
✅ Twig template with proper layout
✅ Core JavaScript module (table + data loading)
✅ Modals JavaScript module (all forms)
✅ Actions JavaScript module (button handlers)
✅ Proper error handling
✅ Dark mode support
✅ Capability-based UI
```

---

## 📋 Prerequisites

Before starting:

- [ ] Read **DEVELOPMENT_STANDARDS.md**
- [ ] Read **COMMON_MISTAKES.md** (especially #1-#6)
- [ ] Have **languages_list.twig** open for reference
- [ ] Know your API endpoints (from API.md or backend docs)
- [ ] Know your capabilities (permissions)

---

## 🗺️ Implementation Roadmap

### Phase 1: Planning (30 min)
1. Define feature name & entities
2. List API endpoints
3. List capabilities
4. Identify columns for table

### Phase 2: Twig Template (1 hour)
1. Create base structure
2. Add capabilities injection
3. Add filters section
4. Add search bar
5. Add table container

### Phase 3: Core JavaScript (1.5 hours)
1. Setup module structure
2. Create custom renderers
3. Implement data loading
4. Add search & filters
5. Export functions

### Phase 4: Modals JavaScript (1.5 hours)
1. Create modal HTML
2. Inject modals into DOM
3. Setup form handlers
4. Add validation & errors

### Phase 5: Actions JavaScript (1 hour)
1. Setup event delegation
2. Implement action handlers
3. Connect to modals

### Phase 6: Testing & Polish (30 min)
1. Test all buttons
2. Test error cases
3. Verify dark mode
4. Code review checklist

---

## 📝 Phase 1: Planning

### Step 1.1: Define Your Feature

**Example:** Let's say we're building "User Roles" management

```
Feature Name: User Roles
Entity: Role
Endpoint Prefix: /api/roles/
Twig File: roles_list.twig
JS Files: 
  - roles-core.js
  - roles-modals.js
  - roles-actions.js
```

### Step 1.2: List API Endpoints

Document all endpoints you'll need:

```
POST /api/roles/query
  ├─ List roles with pagination & filters
  └─ Returns: { data: [...], pagination: {...} }

POST /api/roles/create
  ├─ Create new role
  └─ Payload: { name, description, is_active }

POST /api/roles/update
  ├─ Update role
  └─ Payload: { id, name, description }

POST /api/roles/set-active
  ├─ Toggle active status
  └─ Payload: { id, is_active }

POST /api/roles/delete
  ├─ Delete role
  └─ Payload: { id }
```

### Step 1.3: List Capabilities

From backend authorization:

```javascript
window.rolesCapabilities = {
    can_create: true/false,
    can_update: true/false,
    can_delete: true/false,
    can_set_active: true/false
};
```

### Step 1.4: Define Table Columns

```javascript
const headers = ['ID', 'Name', 'Description', 'Active', 'Actions'];
const rowNames = ['id', 'name', 'description', 'is_active', 'actions'];
```

**Planning Checklist:**
- [ ] Feature name decided
- [ ] API endpoints documented
- [ ] Capabilities listed
- [ ] Table columns defined
- [ ] File names chosen

---

## 🎨 Phase 2: Twig Template

### Step 2.1: Create File Structure

**File:** `views/roles/roles_list.twig`

```twig
{% extends "layouts/base.twig" %}

{% block title %}Roles | {{ ui.appName }}{% endblock %}

{% block content %}
    {# Content goes here #}
{% endblock %}

{% block scripts %}
    {# Scripts go here #}
{% endblock %}
```

### Step 2.2: Add Capabilities Injection

**⚠️ CRITICAL:** This must be the FIRST thing in content block

```twig
{% block content %}
    {# ===================================================================
       Capabilities Injection
       =================================================================== #}
    <script>
        window.rolesCapabilities = {
            can_create: {{ capabilities.can_create ?? false ? 'true' : 'false' }},
            can_update: {{ capabilities.can_update ?? false ? 'true' : 'false' }},
            can_delete: {{ capabilities.can_delete ?? false ? 'true' : 'false' }},
            can_set_active: {{ capabilities.can_set_active ?? false ? 'true' : 'false' }}
        };
    </script>
```

**✅ Copy exactly from languages_list.twig and modify capability names**

### Step 2.3: Add Page Header

```twig
    {# ===================================================================
       Page Header (breadcrumb + title)
       =================================================================== #}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
                🔐 User Roles
            </h2>
            <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                Management
            </span>
        </div>

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
                <li class="text-sm text-gray-800 dark:text-gray-200">
                    User Roles
                </li>
            </ol>
        </nav>
    </div>
```

### Step 2.4: Add Main Container

**⚠️ EXACT structure - don't change!**

```twig
    {# ===================================================================
       Main Content Container
       =================================================================== #}
    <div class="px-0 py-2">
        <div class="container mt-6">
```

### Step 2.5: Add Filters Section

```twig
            {# ============================================================
               Filters Section
               ============================================================ #}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <form id="roles-filter-form" class="space-y-4">
                    {# Column Filters #}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                        {# ID Filter #}
                        <div>
                            <label for="filter-id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                🆔 Role ID
                            </label>
                            <input
                                type="number"
                                id="filter-id"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all dark:placeholder-gray-400"
                                placeholder="e.g., 1, 2, 3..."
                                min="1"
                            />
                        </div>

                        {# Name Filter #}
                        <div>
                            <label for="filter-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                📝 Role Name
                            </label>
                            <input
                                type="text"
                                id="filter-name"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all dark:placeholder-gray-400"
                                placeholder="e.g., Admin, Editor..."
                            />
                        </div>

                        {# Status Filter #}
                        <div>
                            <label for="filter-status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                📊 Status
                            </label>
                            <select
                                id="filter-status"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                            >
                                <option value="">All Statuses</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                    </div>

                    {# Action Buttons #}
                    <div class="flex flex-wrap gap-3 pt-4">
                        <button
                            type="submit"
                            class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center gap-2"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Search
                        </button>

                        <button
                            type="button"
                            id="roles-reset-filters"
                            class="px-6 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors font-medium flex items-center gap-2"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Reset
                        </button>

                        {# Create Button (ml-auto pushes to right) #}
                        {% if capabilities.can_create ?? false %}
                            <button
                                type="button"
                                id="btn-create-role"
                                class="ml-auto px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium flex items-center gap-2"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Create Role
                            </button>
                        {% endif %}
                    </div>
                </form>
            </div>
```

**⚠️ Important Details:**
- Form ID: `{feature}-filter-form`
- Button IDs: `{feature}-reset-filters`, `btn-create-{entity}`
- Create button has `ml-auto` class
- Grid is `lg:grid-cols-4` for 4 filters

### Step 2.6: Add Global Search Bar

```twig
            {# ============================================================
               Global Search Bar (Above Table)
               ============================================================ #}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-4">
                <div class="flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        type="text"
                        id="roles-search"
                        class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all dark:placeholder-gray-400"
                        placeholder="🔍 Quick search by name or description..."
                    />
                    <button
                        type="button"
                        id="roles-search-btn"
                        class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
                    >
                        Search
                    </button>
                    <button
                        type="button"
                        id="roles-clear-search"
                        class="px-6 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors font-medium"
                    >
                        Clear
                    </button>
                </div>
            </div>
```

**⚠️ Important:**
- Input ID: `{feature}-search`
- Button IDs: `{feature}-search-btn`, `{feature}-clear-search`

### Step 2.7: Add Table Container

```twig
            {# Data Table Container #}
            <div id="table-container" class="w-full"></div>
        </div>
    </div>
{% endblock %}
```

### Step 2.8: Add Scripts Section

```twig
{% block scripts %}
    {# Shared infrastructure scripts #}
    <script src="{{ asset('assets/maatify/admin-kernel/js/api_handler.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/callback_handler.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/Input_checker.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/data_table.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/select2.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/admin-ui-components.js') }}"></script>

    {# Feature-specific scripts - ORDER MATTERS! #}
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/roles-core.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/roles-modals.js') }}"></script>
    <script src="{{ asset('assets/maatify/admin-kernel/js/pages/roles-actions.js') }}"></script>
{% endblock %}
```

**Twig Phase Checklist:**
- [ ] File created with correct name
- [ ] Capabilities injected
- [ ] Page header added
- [ ] Container structure correct (`px-0 py-2` → `container mt-6`)
- [ ] Filters card: `p-6 mb-6`
- [ ] Search card: `p-4 mb-4`
- [ ] Create button has `ml-auto`
- [ ] All IDs use `{feature}-` prefix
- [ ] Dark mode classes on all cards
- [ ] Scripts in correct order

---

## 💻 Phase 3: Core JavaScript

### Step 3.1: Create File Structure

**File:** `assets/js/pages/roles-core.js`

```javascript
/**
 * 🔐 User Roles Management - Core Module
 * =======================================
 * Features:
 * - List roles with pagination
 * - Custom renderers for columns
 * - Search & filter functionality
 */

(function() {
    'use strict';

    console.log('🔐 Roles Core Module Loading...');

    // ===================================================================
    // PREREQUISITES CHECK
    // ===================================================================

    if (typeof AdminUIComponents === 'undefined') {
        console.error('❌ AdminUIComponents library not found!');
        return;
    }

    if (typeof ApiHandler === 'undefined') {
        console.error('❌ ApiHandler not found!');
        return;
    }

    console.log('✅ Dependencies loaded');

    // ===================================================================
    // STATE & CONFIGURATION
    // ===================================================================

    let currentPage = 1;
    let currentPerPage = 25;

    const headers = ['ID', 'Name', 'Description', 'Active', 'Actions'];
    const rows = ['id', 'name', 'description', 'is_active', 'actions'];
    const capabilities = window.rolesCapabilities || {};

    console.log('🔐 Capabilities:', capabilities);

    // Module code continues...
})();
```

### Step 3.2: Add Utility Functions

```javascript
    // ===================================================================
    // UTILITY FUNCTIONS
    // ===================================================================

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
```

### Step 3.3: Create Custom Renderers

**⚠️ CRITICAL: This is where most mistakes happen!**

```javascript
    // ===================================================================
    // CUSTOM RENDERERS
    // ===================================================================

    /**
     * ✅ ID Renderer
     */
    const idRenderer = (value, row) => {
        return `<span class="font-mono text-gray-600 dark:text-gray-400">${value}</span>`;
    };

    /**
     * ✅ Name Renderer
     */
    const nameRenderer = (value, row) => {
        return `<span class="font-medium text-gray-900 dark:text-gray-100">${escapeHtml(value)}</span>`;
    };

    /**
     * ✅ Description Renderer
     */
    const descriptionRenderer = (value, row) => {
        if (!value || value === '') {
            return `<span class="text-gray-400 dark:text-gray-500 italic text-sm">No description</span>`;
        }
        const truncated = value.length > 50 ? value.substring(0, 50) + '...' : value;
        return `<span class="text-gray-700 dark:text-gray-300 text-sm" title="${escapeHtml(value)}">${escapeHtml(truncated)}</span>`;
    };

    /**
     * ✅ Status Renderer - CRITICAL: Pass actual value, not string!
     */
    const statusRenderer = (value, row) => {
        // ⚠️ Pass VALUE directly (1 or 0), NOT "Active"/"Inactive" string!
        return AdminUIComponents.renderStatusBadge(value, {
            clickable: capabilities.can_set_active,
            entityId: row.id,
            activeText: 'Active',
            inactiveText: 'Inactive',
            buttonClass: 'toggle-active-btn',
            dataAttribute: 'data-role-id'
        });
    };

    /**
     * ✅ Actions Renderer
     */
    const actionsRenderer = (value, row) => {
        const actions = [];

        // Update button
        if (capabilities.can_update) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'update-role-btn',
                icon: AdminUIComponents.SVGIcons.edit,
                text: 'Edit',
                color: 'blue',
                entityId: row.id,
                title: 'Edit role',
                dataAttributes: { 'role-id': row.id }
            }));
        }

        // Delete button
        if (capabilities.can_delete) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'delete-role-btn',
                icon: AdminUIComponents.SVGIcons.trash,
                text: 'Delete',
                color: 'red',
                entityId: row.id,
                title: 'Delete role',
                dataAttributes: { 'role-id': row.id }
            }));
        }

        if (actions.length === 0) {
            return '<span class="text-gray-400 dark:text-gray-500 text-sm">No actions</span>';
        }

        return `<div class="flex flex-wrap gap-2">${actions.join('')}</div>`;
    };
```

**✅ Renderer Rules:**
1. Always use `AdminUIComponents` for badges/buttons
2. Pass actual VALUES to components, not UI text
3. Use `escapeHtml()` for user-provided text
4. Check capabilities before showing actions
5. Use consistent data attributes: `data-{entity}-id`

### Step 3.4: Query Building

```javascript
    // ===================================================================
    // QUERY BUILDING
    // ===================================================================

    function buildQueryParams() {
        const params = {
            page: currentPage,
            per_page: currentPerPage
        };

        // Global search
        const globalSearch = document.getElementById('roles-search')?.value?.trim();
        
        // Column filters
        const columnFilters = {};

        const filterId = document.getElementById('filter-id')?.value?.trim();
        if (filterId) columnFilters.id = filterId;

        const filterName = document.getElementById('filter-name')?.value?.trim();
        if (filterName) columnFilters.name = filterName;

        const filterStatus = document.getElementById('filter-status')?.value;
        if (filterStatus) columnFilters.is_active = filterStatus;

        // Build search object if needed
        if (globalSearch || Object.keys(columnFilters).length > 0) {
            params.search = {};
            if (globalSearch) params.search.global = globalSearch;
            if (Object.keys(columnFilters).length > 0) params.search.columns = columnFilters;
        }

        return params;
    }
```

### Step 3.5: Data Loading

```javascript
    // ===================================================================
    // DATA LOADING
    // ===================================================================

    async function loadRoles(pageNumber = null, perPageNumber = null) {
        // Update pagination state
        if (pageNumber !== null) currentPage = pageNumber;
        if (perPageNumber !== null) currentPerPage = perPageNumber;

        console.log('📊 Loading roles...', { page: currentPage, perPage: currentPerPage });

        const params = buildQueryParams();
        const result = await ApiHandler.call('roles/query', params, 'Query Roles');

        if (!result.success) {
            const container = document.getElementById('table-container');
            if (container) {
                container.innerHTML = `
                    <div class="bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 rounded-lg p-8 text-center m-4">
                        <div class="text-red-600 dark:text-red-400 text-xl font-semibold mb-2">
                            ❌ Failed to Load Roles
                        </div>
                        <p class="text-red-700 dark:text-red-300 mb-4">
                            ${result.error || 'Unknown error occurred'}
                        </p>
                        <button onclick="location.reload()" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            🔄 Retry
                        </button>
                    </div>
                `;
            }
            return;
        }

        const data = result.data || {};
        const roles = Array.isArray(data.data) ? data.data : [];
        const paginationInfo = data.pagination || {
            page: params.page || 1,
            per_page: params.per_page || 25,
            total: roles.length
        };

        // Render table
        if (typeof TableComponent === 'function') {
            TableComponent(
                roles,
                headers,
                rows,
                paginationInfo,
                "",
                false,
                'id',
                null,
                {
                    id: idRenderer,
                    name: nameRenderer,
                    description: descriptionRenderer,
                    is_active: statusRenderer,
                    actions: actionsRenderer
                },
                null,
                (pagination) => ({
                    start: (pagination.page - 1) * pagination.per_page + 1,
                    end: Math.min(pagination.page * pagination.per_page, pagination.total),
                    total: pagination.total,
                    filtered: pagination.filtered || pagination.total
                })
            );
        } else {
            console.error('❌ TableComponent not found');
        }
    }
```

### Step 3.6: Search & Filters Setup

```javascript
    // ===================================================================
    // SEARCH & FILTERS SETUP
    // ===================================================================

    function setupSearchAndFilters() {
        // Global search
        const searchBtn = document.getElementById('roles-search-btn');
        const clearSearchBtn = document.getElementById('roles-clear-search');
        const searchInput = document.getElementById('roles-search');

        if (searchBtn) {
            searchBtn.addEventListener('click', () => loadRoles(1));
        }

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', () => {
                if (searchInput) searchInput.value = '';
                loadRoles(1);
            });
        }

        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    loadRoles(1);
                }
            });
        }

        // Filter form
        const filterForm = document.getElementById('roles-filter-form');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                loadRoles(1);
            });
        }

        // Reset filters
        const resetBtn = document.getElementById('roles-reset-filters');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (filterForm) filterForm.reset();
                if (searchInput) searchInput.value = '';
                loadRoles(1);
            });
        }
    }
```

### Step 3.7: Initialization & Exports

```javascript
    // ===================================================================
    // INITIALIZATION
    // ===================================================================

    function init() {
        console.log('🎬 Initializing Roles Core Module...');
        setupSearchAndFilters();
        loadRoles();
        console.log('✅ Roles Core Module initialized');
    }

    // ===================================================================
    // EXPORTS
    // ===================================================================

    // Global functions for pagination (called by data_table.js)
    window.changePage = function(page) {
        loadRoles(page, null);
    };

    window.changePerPage = function(perPage) {
        currentPage = 1; // Reset to first page
        loadRoles(1, perPage);
    };

    // Export reload function for modals/actions
    window.reloadRolesTable = function() {
        loadRoles(currentPage, currentPerPage);
    };

    // Run initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
```

**Core Module Checklist:**
- [ ] Dependencies checked at start
- [ ] State variables defined
- [ ] Renderers pass correct data types
- [ ] Query building handles empty filters
- [ ] Error handling shows user-friendly messages
- [ ] Search & filters properly connected
- [ ] `changePage`, `changePerPage`, `reload{Feature}Table` exported
- [ ] Init runs on DOMContentLoaded

---

## 📝 Phase 4: Modals JavaScript

### Step 4.1: File Structure

**File:** `assets/js/pages/roles-modals.js`

```javascript
/**
 * 🔐 User Roles Management - Modals Module
 * =========================================
 * Features:
 * - Create Role Modal
 * - Update Role Modal
 * - All form handlers
 */

(function() {
    'use strict';

    console.log('📝 Roles Modals Module Loading...');

    if (typeof ApiHandler === 'undefined') {
        console.error('❌ ApiHandler not found!');
        return;
    }

    const capabilities = window.rolesCapabilities || {};

    // Modal HTML definitions go here...
})();
```

### Step 4.2: Create Modal HTML

```javascript
    // ===================================================================
    // MODAL HTML DEFINITIONS
    // ===================================================================

    const createRoleModalHTML = `
        <div id="create-role-modal" class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 z-50 flex items-center justify-center hidden">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full mx-4 border border-transparent dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">➕ Create New Role</h3>
                    <button class="close-modal text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="create-role-form" class="px-6 py-4 space-y-4">
                    <div>
                        <label for="create-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Role Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="create-name"
                            name="name"
                            required
                            maxlength="100"
                            placeholder="e.g., Admin, Editor..."
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        />
                    </div>

                    <div>
                        <label for="create-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Description
                        </label>
                        <textarea
                            id="create-description"
                            name="description"
                            rows="3"
                            maxlength="255"
                            placeholder="Brief description..."
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        ></textarea>
                    </div>

                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            id="create-active"
                            name="is_active"
                            checked
                            class="w-4 h-4 text-blue-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500"
                        />
                        <label for="create-active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" class="close-modal px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                            Create Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;
```

**⚠️ Modal HTML Rules:**
- Modal ID: `{action}-{entity}-modal`
- Form ID: `{action}-{entity}-form`
- Input IDs: `{action}-{fieldname}`
- Must have `.close-modal` buttons
- Dark mode classes on ALL elements

### Step 4.3: Setup Form Handler

```javascript
    // ===================================================================
    // CREATE ROLE MODAL
    // ===================================================================

    function setupCreateRoleModal() {
        const btnCreate = document.getElementById('btn-create-role');
        if (btnCreate && capabilities.can_create) {
            btnCreate.addEventListener('click', () => {
                document.getElementById('create-role-modal').classList.remove('hidden');
            });
        }

        const form = document.getElementById('create-role-form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Clear previous errors
            form.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));
            form.querySelectorAll('.field-error').forEach(el => el.remove());

            const payload = {
                name: document.getElementById('create-name').value.trim(),
                description: document.getElementById('create-description').value.trim(),
                is_active: document.getElementById('create-active').checked
            };

            const result = await ApiHandler.call('roles/create', payload, 'Create Role');

            if (result.success) {
                ApiHandler.showAlert('success', '✅ Role created successfully');
                document.getElementById('create-role-modal').classList.add('hidden');
                form.reset();
                
                // Reload table
                if (typeof window.reloadRolesTable === 'function') {
                    window.reloadRolesTable();
                }
            } else {
                // ⚠️ CRITICAL: Handle BOTH validation AND general errors!
                if (result.data && result.data.errors) {
                    ApiHandler.showFieldErrors(result.data.errors, 'create-role-form');
                } else {
                    ApiHandler.showAlert('danger', result.error || 'Failed to create role');
                }
            }
        });
    }
```

**⚠️ Form Handler Rules:**
1. Always `e.preventDefault()`
2. Clear previous errors
3. Build payload from form inputs
4. Call API with proper endpoint
5. Handle BOTH success AND failure
6. For errors: Check validation errors first, then show general error
7. On success: Show alert, close modal, reset form, reload table

### Step 4.4: Modal Close Handlers

```javascript
    // ===================================================================
    // MODAL CLOSE HANDLERS
    // ===================================================================

    function setupModalCloseHandlers() {
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modal = e.target.closest('.fixed');
                if (modal) {
                    modal.classList.add('hidden');
                    const form = modal.querySelector('form');
                    if (form) form.reset();
                }
            });
        });
    }
```

### Step 4.5: Module Initialization

```javascript
    // ===================================================================
    // INITIALIZATION
    // ===================================================================

    function initModalsModule() {
        console.log('🎬 Initializing Roles Modals Module...');

        // Inject modals into DOM
        document.body.insertAdjacentHTML('beforeend', createRoleModalHTML);
        // Add other modals here

        // Setup all modals
        setupCreateRoleModal();
        // Setup other modals here
        
        setupModalCloseHandlers();

        // Export modal opener functions
        window.RolesModals = {
            // openUpdateRoleModal,
            // etc.
        };

        console.log('✅ Roles Modals Module initialized');
    }

    // Run initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModalsModule);
    } else {
        initModalsModule();
    }

})();
```

**Modals Module Checklist:**
- [ ] Modal HTML includes dark mode classes
- [ ] Form handler clears previous errors
- [ ] Both validation AND general errors handled
- [ ] Success path: alert → close → reset → reload
- [ ] Modal close handlers setup
- [ ] Modal openers exported to `window.{Feature}Modals`
- [ ] Modals injected into DOM on init

---

## 🎬 Phase 5: Actions JavaScript

### Step 5.1: File Structure

**File:** `assets/js/pages/roles-actions.js`

```javascript
/**
 * 🔐 User Roles Management - Actions Module
 * ==========================================
 * Features:
 * - Toggle Active Status
 * - Delete Role
 * - Button event delegation
 */

(function() {
    'use strict';

    console.log('🎯 Roles Actions Module Loading...');

    if (typeof ApiHandler === 'undefined') {
        console.error('❌ ApiHandler not found!');
        return;
    }

    const capabilities = window.rolesCapabilities || {};

    // Action implementations...
})();
```

### Step 5.2: Event Delegation Helper

```javascript
    // ===================================================================
    // HELPER FUNCTION - Setup Button Handler
    // ===================================================================

    function setupButtonHandler(selector, handler) {
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest(selector);
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();

            const roleId = btn.getAttribute('data-role-id');
            if (!roleId) {
                console.error(`❌ No role ID found on button:`, btn);
                return;
            }

            try {
                await handler(roleId, btn);
            } catch (error) {
                console.error(`❌ Error in ${selector} handler:`, error);
                ApiHandler.showAlert('danger', 'An error occurred: ' + error.message);
            }
        });
    }
```

### Step 5.3: Implement Actions

```javascript
    // ===================================================================
    // TOGGLE ACTIVE STATUS
    // ===================================================================

    async function toggleActiveStatus(roleId, button) {
        // Get current status
        const currentStatus = button.getAttribute('data-current-status') === '1';
        const newStatus = !currentStatus;
        const action = newStatus ? 'activate' : 'deactivate';

        if (!confirm(`Are you sure you want to ${action} this role?`)) {
            return;
        }

        const payload = {
            id: parseInt(roleId),
            is_active: newStatus
        };

        const result = await ApiHandler.call('roles/set-active', payload, 'Toggle Active');

        if (result.success) {
            ApiHandler.showAlert('success', `✅ Role ${newStatus ? 'activated' : 'deactivated'} successfully`);
            
            // Reload table
            if (typeof window.reloadRolesTable === 'function') {
                window.reloadRolesTable();
            }
        } else {
            // ⚠️ Show error
            ApiHandler.showAlert('danger', result.error || 'Failed to toggle status');
        }
    }

    // ===================================================================
    // DELETE ROLE
    // ===================================================================

    async function deleteRole(roleId, button) {
        if (!confirm('⚠️ Are you sure you want to delete this role? This action cannot be undone.')) {
            return;
        }

        const payload = { id: parseInt(roleId) };
        const result = await ApiHandler.call('roles/delete', payload, 'Delete Role');

        if (result.success) {
            ApiHandler.showAlert('success', '✅ Role deleted successfully');
            
            if (typeof window.reloadRolesTable === 'function') {
                window.reloadRolesTable();
            }
        } else {
            ApiHandler.showAlert('danger', result.error || 'Failed to delete role');
        }
    }
```

### Step 5.4: Setup All Handlers

```javascript
    // ===================================================================
    // SETUP ALL ACTION HANDLERS
    // ===================================================================

    function setupAllActionHandlers() {
        console.log('🎯 Setting up action handlers...');

        // Toggle Active Status
        if (capabilities.can_set_active) {
            setupButtonHandler('.toggle-active-btn', toggleActiveStatus);
        }

        // Update Role (opens modal)
        if (capabilities.can_update) {
            setupButtonHandler('.update-role-btn', async (roleId) => {
                if (typeof window.RolesModals !== 'undefined' && window.RolesModals.openUpdateRoleModal) {
                    await window.RolesModals.openUpdateRoleModal(roleId);
                } else {
                    console.error('❌ RolesModals.openUpdateRoleModal not found');
                }
            });
        }

        // Delete Role
        if (capabilities.can_delete) {
            setupButtonHandler('.delete-role-btn', deleteRole);
        }

        console.log('✅ All action handlers setup complete');
    }
```

### Step 5.5: Module Initialization

```javascript
    // ===================================================================
    // INITIALIZATION
    // ===================================================================

    function initActionsModule() {
        console.log('🎬 Initializing Roles Actions Module...');
        setupAllActionHandlers();
        console.log('✅ Roles Actions Module initialized');
    }

    // Run initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initActionsModule);
    } else {
        initActionsModule();
    }

})();
```

**Actions Module Checklist:**
- [ ] Event delegation used (not inline onclick)
- [ ] Entity ID extracted from data attribute
- [ ] Confirmation dialogs for destructive actions
- [ ] Error handling shows alerts
- [ ] Table reload after successful action
- [ ] Capability checks before setting up handlers

---

## ✅ Phase 6: Testing & Polish

### Testing Checklist

**Functionality Tests:**
- [ ] Page loads without console errors
- [ ] Table displays data correctly
- [ ] Filters work (individual and combined)
- [ ] Global search works
- [ ] Pagination works
- [ ] Per-page selector works
- [ ] Create button opens modal
- [ ] Create form submits successfully
- [ ] Edit button opens modal (if implemented)
- [ ] Delete button works with confirmation
- [ ] Toggle status works

**Error Handling Tests:**
- [ ] Try creating duplicate (409 error)
- [ ] Try invalid data (422 error)
- [ ] Validation errors show under fields
- [ ] General errors show as alerts
- [ ] Network errors handled gracefully

**UI/UX Tests:**
- [ ] Dark mode works on all elements
- [ ] Layout matches languages page
- [ ] Create button is on the right (ml-auto)
- [ ] All hover states work
- [ ] All icons display correctly
- [ ] Status badges show correct colors
- [ ] Action buttons have proper spacing

**Code Quality Checks:**
- [ ] No console errors
- [ ] No console.log leftovers (except structured logs)
- [ ] Consistent naming (IDs, classes, functions)
- [ ] Comments are helpful
- [ ] No TODOs left unfixed

---

## 📚 Final Checklist

Before submitting for review:

**Files Created:**
- [ ] `{feature}_list.twig`
- [ ] `{feature}-core.js`
- [ ] `{feature}-modals.js`
- [ ] `{feature}-actions.js`

**Code Quality:**
- [ ] Follows languages implementation pattern
- [ ] No mistakes from COMMON_MISTAKES.md
- [ ] All capabilities properly checked
- [ ] Dark mode fully supported
- [ ] Error handling complete

**Testing:**
- [ ] All features work
- [ ] All error cases handled
- [ ] Tested in light and dark mode
- [ ] Tested on different screen sizes

**Documentation:**
- [ ] Comments explain WHY not WHAT
- [ ] Complex logic is documented
- [ ] API endpoints listed in comments

---

## 🆘 Troubleshooting

### "My buttons don't work!"
→ See COMMON_MISTAKES.md #1

### "Status always shows inactive!"
→ See COMMON_MISTAKES.md #2

### "No error message when API fails!"
→ See COMMON_MISTAKES.md #3

### "Layout looks different!"
→ See COMMON_MISTAKES.md #4

### "Getting console errors about missing functions!"
→ Check script load order in Twig

### "Modal doesn't open!"
→ Check if modal HTML is injected (`document.getElementById('modal-id')`)

### "Table doesn't refresh after action!"
→ Check if `window.reload{Feature}Table` is exported and called

---

## 🎓 What You Learned

By completing this guide, you now know how to:

✅ Structure a feature following project patterns  
✅ Create modular, maintainable JavaScript  
✅ Implement proper error handling  
✅ Use AdminUIComponents effectively  
✅ Build capability-based UI  
✅ Support dark mode from day one  
✅ Avoid common implementation mistakes  

---

## 🔄 Next Steps

1. **Review your code** against this guide
2. **Test thoroughly** using the testing checklist
3. **Submit for code review** with confidence
4. **Document any deviations** from this guide (if approved)

---

**Remember:** This guide is based on proven, working implementations. Follow it closely and you'll save hours of debugging! 🚀

**Questions?** Check:
1. This guide
2. COMMON_MISTAKES.md
3. DEVELOPMENT_STANDARDS.md
4. Reference implementation (languages_list.twig)
