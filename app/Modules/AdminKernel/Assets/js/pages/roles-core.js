/**
 * Roles Page - Core Module
 * Handles initialization, capabilities, and main table logic
 */

(function() {
    'use strict';

    console.log('🎭 Roles Management - Initializing');
    console.log('═'.repeat(60));

    // ========================================================================
    // Capabilities Check
    // ========================================================================
    const capabilities = window.rolesCapabilities || {
        can_create: false,
        can_update_meta: false,
        can_rename: false,
        can_toggle: false,
        can_view_role: false
    };

    console.log('🔐 UI Capabilities:', capabilities);
    console.log('  ├─ can_view_role:', capabilities.can_view_role ? '✅ YES' : '❌ NO');
    console.log('  ├─ can_create:', capabilities.can_create ? '✅ YES' : '❌ NO');
    console.log('  ├─ can_update_meta:', capabilities.can_update_meta ? '✅ YES' : '❌ NO');
    console.log('  ├─ can_rename:', capabilities.can_rename ? '✅ YES' : '❌ NO');
    console.log('  └─ can_toggle:', capabilities.can_toggle ? '✅ YES' : '❌ NO');
    console.log('═'.repeat(60));

    // ========================================================================
    // Table Configuration
    // ========================================================================
    const headers = ["ID", "Name", "Group", "Display Name", "Description", "Actions"];
    const rows = ["id", "name", "group", "display_name", "description", "actions"];

    // ========================================================================
    // DOM Elements - Search Form
    // ========================================================================
    const searchForm = document.getElementById('roles-search-form');
    const resetBtn = document.getElementById('btn-reset');
    const inputRoleId = document.getElementById('filter-role-id');
    const inputRoleName = document.getElementById('filter-role-name');
    const inputGroup = document.getElementById('filter-group');

    // ========================================================================
    // Custom Renderers
    // ========================================================================

    /**
     * Custom renderer for ID column
     * Clickable to view role details if user has permission
     */
    const idRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';

        // ✅ Make clickable if user has view permission
        if (capabilities.can_view_role) {
            return `
                <a href="/roles/${value}" 
                   class="font-mono text-sm text-blue-600 hover:text-blue-800 hover:underline cursor-pointer font-medium"
                   title="View role details">
                    #${value}
                </a>
            `;
        }

        // ❌ Read-only if no view permission
        return `<span class="font-mono text-sm text-gray-800 font-medium">#${value}</span>`;
    };

    /**
     * Custom renderer for name column (immutable technical key)
     * Clickable to view role details if user has permission
     */
    const nameRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';

        const roleId = row.id;

        // ✅ Make clickable if user has view permission
        if (capabilities.can_view_role && roleId) {
            return `
                <a href="/roles/${roleId}" 
                   class="inline-block px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-mono border border-gray-200 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 transition-all cursor-pointer"
                   title="View role details">
                    ${value}
                </a>
            `;
        }

        // ❌ Read-only if no view permission
        return `
            <code class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-mono border border-gray-200">
                ${value}
            </code>
        `;
    };

    /**
     * Custom renderer for group column (derived from name)
     */
    const groupRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';

        // Different colors for different groups
        const groupColors = {
            'admins': 'bg-blue-100 text-blue-800 border-blue-200',
            'sessions': 'bg-green-100 text-green-800 border-green-200',
            'permissions': 'bg-purple-100 text-purple-800 border-purple-200',
            'roles': 'bg-orange-100 text-orange-800 border-orange-200',
            'default': 'bg-gray-100 text-gray-800 border-gray-200'
        };

        const colorClass = groupColors[value.toLowerCase()] || groupColors['default'];

        return `
            <span class="${colorClass} px-3 py-1 rounded-full text-xs font-medium border">
                ${value}
            </span>
        `;
    };

    /**
     * Custom renderer for display_name column
     */
    const displayNameRenderer = (value, row) => {
        if (!value || value.trim() === '') {
            return '<span class="text-gray-400 italic text-xs">Not set</span>';
        }
        return `<span class="text-sm text-gray-800">${value}</span>`;
    };

    /**
     * Custom renderer for description column
     */
    const descriptionRenderer = (value, row) => {
        if (!value || value.trim() === '') {
            return '<span class="text-gray-400 italic text-xs">No description</span>';
        }

        // Truncate long descriptions
        const maxLength = 60;
        if (value.length > maxLength) {
            const truncated = value.substring(0, maxLength) + '...';
            return `<span class="text-sm text-gray-600" title="${value}">${truncated}</span>`;
        }

        return `<span class="text-sm text-gray-600">${value}</span>`;
    };

    /**
     * Custom renderer for actions column
     * Shows available actions based on capabilities
     */
    const actionsRenderer = (value, row) => {
        const roleId = row.id;
        if (!roleId) return '<span class="text-gray-400 italic">—</span>';

        const actions = [];

        // ✅ View Details (if user has permission)
        if (capabilities.can_view_role) {
            actions.push(`
                <a href="/roles/${roleId}"
                    class="inline-flex items-center gap-1 text-xs px-3 py-1 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors duration-200"
                    title="View role details, permissions, and assigned admins">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    View
                </a>
            `);
        }

        // ✅ Edit Metadata (if user has permission)
        if (capabilities.can_update_meta) {
            actions.push(`
                <button 
                    class="edit-metadata-btn inline-flex items-center gap-1 text-xs px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-200"
                    data-role-id="${roleId}"
                    data-role-name="${row.name || ''}"
                    data-role-group="${row.group || ''}"
                    data-display-name="${row.display_name || ''}"
                    data-description="${row.description || ''}"
                    title="Edit metadata (display name & description)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                    Edit
                </button>
            `);
        }

        // ✅ Rename (if user has permission)
        if (capabilities.can_rename) {
            actions.push(`
                <button 
                    class="rename-role-btn inline-flex items-center gap-1 text-xs px-3 py-1 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors duration-200"
                    data-role-id="${roleId}"
                    data-role-name="${row.name || ''}"
                    title="Rename role technical key (HIGH-IMPACT)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                    </svg>
                    Rename
                </button>
            `);
        }

        // ✅ Toggle (if user has permission)
        if (capabilities.can_toggle) {
            const isActive = row.is_active === 1 || row.is_active === true || row.is_active === '1';
            const toggleClass = isActive ? 'bg-orange-600 hover:bg-orange-700' : 'bg-green-600 hover:bg-green-700';
            const toggleText = isActive ? 'Disable' : 'Enable';
            const toggleIcon = isActive
                ? '<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />'
                : '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />';

            actions.push(`
                <button 
                    class="toggle-role-btn inline-flex items-center gap-1 text-xs px-3 py-1 ${toggleClass} text-white rounded-md transition-colors duration-200"
                    data-role-id="${roleId}"
                    data-role-name="${row.name || ''}"
                    data-is-active="${isActive ? '1' : '0'}"
                    title="Toggle role activation">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                        ${toggleIcon}
                    </svg>
                    ${toggleText}
                </button>
            `);
        }

        // If no actions available
        if (actions.length === 0) {
            return '<span class="text-gray-400 italic text-xs">No actions</span>';
        }

        return `<div class="flex items-center gap-2 flex-wrap">${actions.join('')}</div>`;
    };

    // ========================================================================
    // Params Builder
    // ========================================================================
    function buildParams(pageNumber = 1, perPage = 25) {
        console.log('📦 Building API params');
        console.log('  ├─ Page:', pageNumber);
        console.log('  └─ Per page:', perPage);

        const params = {
            page: pageNumber,
            per_page: perPage
        };

        const searchColumns = {};

        // Build column search
        if (inputRoleId && inputRoleId.value.trim()) {
            searchColumns.id = inputRoleId.value.trim();
            console.log('  ├─ Filter: id =', searchColumns.id);
        }
        if (inputRoleName && inputRoleName.value.trim()) {
            searchColumns.name = inputRoleName.value.trim();
            console.log('  ├─ Filter: name =', searchColumns.name);
        }
        if (inputGroup && inputGroup.value.trim()) {
            searchColumns.group = inputGroup.value.trim();
            console.log('  ├─ Filter: group =', searchColumns.group);
        }

        if (Object.keys(searchColumns).length > 0) {
            params.search = { columns: searchColumns };
            console.log('  └─ Search columns:', Object.keys(searchColumns).length);
        } else {
            console.log('  └─ No search filters');
        }

        return params;
    }

    // ========================================================================
    // Pagination Info Callback
    // ========================================================================
    function getRolesPaginationInfo(pagination, params) {
        console.log("🎯 getRolesPaginationInfo called with:", pagination);

        const { page = 1, per_page = 25, total = 0, filtered = total } = pagination;

        // Check if we're filtering
        const hasFilter = params.search &&
            (params.search.global ||
                (params.search.columns && Object.keys(params.search.columns).length > 0));

        const isFiltered = hasFilter && filtered !== total;

        console.log("🔍 Filter status - hasFilter:", hasFilter, "isFiltered:", isFiltered);

        // Calculate based on filtered when applicable
        const displayCount = isFiltered ? filtered : total;
        const startItem = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
        const endItem = Math.min(page * per_page, displayCount);

        // Build info text
        let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;
        if (isFiltered) {
            infoText += ` <span class="text-gray-500 text-xs">(filtered from ${total} total)</span>`;
        }

        console.log("📤 Returning:", { total: displayCount, info: infoText });

        return {
            total: displayCount,
            info: infoText
        };
    }

    // ========================================================================
    // Load Roles
    // ========================================================================
    async function loadRoles(pageNumber = 1) {
        const params = buildParams(pageNumber, 25);
        await loadRolesWithParams(params);
    }

    async function loadRolesWithParams(params) {
        console.log('━'.repeat(60));
        console.log('🚀 API Request - Roles Query');
        console.log('━'.repeat(60));
        console.log('📤 Sending to: /api/roles/query');
        console.log('📦 Payload:', JSON.stringify(params, null, 2));

        if (typeof createTable === 'function') {
            try {
                const result = await createTable(
                    "roles/query",
                    params,
                    headers,
                    rows,
                    false,
                    'id',
                    null,
                    {
                        id: idRenderer,
                        name: nameRenderer,
                        group: groupRenderer,
                        display_name: displayNameRenderer,
                        description: descriptionRenderer,
                        actions: actionsRenderer
                    },
                    null,
                    getRolesPaginationInfo
                );

                if (result && result.success) {
                    console.log('✅ API Response - Success');
                    console.log('📊 Roles loaded:', result.data.length);
                    console.log('📄 Pagination:', result.pagination);
                    console.log('  ├─ Page:', result.pagination.page);
                    console.log('  ├─ Per page:', result.pagination.per_page);
                    console.log('  ├─ Total:', result.pagination.total);
                    console.log('  └─ Filtered:', result.pagination.filtered);
                    console.log('━'.repeat(60));
                    setupTableFiltersAfterRender();
                } else {
                    console.error('❌ API Response - Failed');
                    console.error('Result:', result);
                    console.log('━'.repeat(60));
                }
            } catch (error) {
                console.error('━'.repeat(60));
                console.error('❌ API Error - Exception thrown');
                console.error('Error:', error);
                console.error('Stack:', error.stack);
                console.error('━'.repeat(60));
                showAlert('d', 'Failed to load roles');
            }
        } else {
            console.error('❌ Critical Error: createTable function not found');
            console.error('Make sure data_table.js is loaded before roles-core.js');
        }
    }

    // ========================================================================
    // Table Filters
    // ========================================================================
    function setupTableFiltersAfterRender() {
        setTimeout(() => setupTableFilters(), 100);
    }

    function setupTableFilters() {
        const filterContainer = document.getElementById('table-custom-filters');
        if (!filterContainer) return;

        filterContainer.innerHTML = `
            <div class="flex gap-4 items-center flex-wrap">
                <div class="w-64">
                    <input id="roles-global-search" 
                        class="w-full border rounded-lg px-3 py-1 text-sm transition-colors duration-200" 
                        placeholder="Search roles..." />
                </div>
            </div>
        `;

        const globalSearch = document.getElementById('roles-global-search');
        if (globalSearch) {
            globalSearch.addEventListener('keyup', (e) => {
                const value = e.target.value.trim();
                clearTimeout(globalSearch.searchTimeout);
                globalSearch.searchTimeout = setTimeout(() => {
                    handleGlobalSearch(value);
                }, 1000);
            });

            globalSearch.addEventListener('input', (e) => {
                const value = e.target.value.trim();
                if (value.length > 0) {
                    globalSearch.classList.add('border-blue-300', 'bg-blue-50');
                } else {
                    globalSearch.classList.remove('border-blue-300', 'bg-blue-50');
                }
            });
        }
    }

    function handleGlobalSearch(searchValue) {
        console.log("🔎 Global search:", searchValue);
        const params = buildParams(1, 25);

        if (searchValue && searchValue.trim()) {
            if (!params.search) {
                params.search = {};
            }
            params.search.global = searchValue.trim();
        }

        loadRolesWithParams(params);
    }

    // ========================================================================
    // Helpers
    // ========================================================================
    function showAlert(type, message) {
        if (typeof window.showAlert === 'function') {
            window.showAlert(type, message);
        } else {
            console.log(`[${type}] ${message}`);
        }
    }

    // ========================================================================
    // Expose Public API
    // ========================================================================
    window.RolesCore = {
        capabilities,
        loadRoles,
        loadRolesWithParams,
        showAlert
    };

    console.log('✅ Roles Core Module - Ready');

})();