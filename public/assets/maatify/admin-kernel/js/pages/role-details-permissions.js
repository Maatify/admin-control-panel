/**
 * Role Details – Permissions Module
 * Owns the Permissions tab: search form, table, toggle assign/unassign.
 *
 * API endpoints used:
 *   POST /api/roles/{roleId}/permissions/query
 *   POST /api/roles/{roleId}/permissions/assign     → 204
 *   POST /api/roles/{roleId}/permissions/unassign   → 204
 *
 * Capabilities consumed (from window.roleDetailsCapabilities):
 *   can_view_permissions     – tab is rendered at all (Twig gate)
 *   can_assign_permissions   – toggle ON  enabled
 *   can_unassign_permissions – toggle OFF enabled
 */

(function () {
    'use strict';

    console.log('🔑 Role Details Permissions – Initializing');
    console.log('═'.repeat(60));

    const capabilities = window.roleDetailsCapabilities || {};
    const roleId       = window.roleDetailsId;

    console.log('🔐 Permissions capabilities:');
    console.log('  ├─ can_view_permissions:    ', capabilities.can_view_permissions  ? '✅ YES' : '❌ NO');
    console.log('  ├─ can_assign_permissions:  ', capabilities.can_assign_permissions ? '✅ YES' : '❌ NO');
    console.log('  └─ can_unassign_permissions:', capabilities.can_unassign_permissions ? '✅ YES' : '❌ NO');

    // Guard – if the tab wasn't rendered, exit silently
    if (!capabilities.can_view_permissions) {
        console.log('⛔ Permissions tab not available – exiting module');
        return;
    }

    // ====================================================================
    // DOM References
    // ====================================================================
    const container       = document.getElementById('permissions-table-container');
    const searchForm      = document.getElementById('permissions-search-form');
    const resetBtn        = document.getElementById('perm-btn-reset');
    const inputId         = document.getElementById('perm-filter-id');
    const inputName       = document.getElementById('perm-filter-name');
    const inputGroup      = document.getElementById('perm-filter-group');
    const inputAssigned   = document.getElementById('perm-filter-assigned');

    // ====================================================================
    // State
    // ====================================================================
    let currentParams = {};  // last sent params – used by tableAction handler
    let currentGlobalSearch = '';  // tracks the active global search value across re-renders
    let currentAssignedFilter = 'all';  // tracks the active assigned pill filter across re-renders

    // ====================================================================
    // Custom Renderers
    // ====================================================================

    /** ID column – plain mono badge */
    const idRenderer = (value) => {
        if (!value && value !== 0) return '<span class="text-gray-400 dark:text-gray-500 italic">N/A</span>';
        return `<span class="font-mono text-sm text-gray-800 dark:text-gray-300 font-medium">#${value}</span>`;
    };

    /** Name column – code-style badge */
    const nameRenderer = (value) => {
        if (!value) return '<span class="text-gray-400 dark:text-gray-500 italic">N/A</span>';
        return `<code class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded text-xs font-mono border border-gray-200 dark:border-gray-600">${value}</code>`;
    };

    /** Group column – colored pill (same palette as roles list) */
    const groupRenderer = (value) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';

        const groupColors = {
            'admins':      'bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 border-blue-200 dark:border-blue-800',
            'sessions':    'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800',
            'permissions': 'bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-300 border-purple-200 dark:border-purple-800',
            'roles':       'bg-orange-100 dark:bg-orange-900/40 text-orange-800 dark:text-orange-300 border-orange-200 dark:border-orange-800'
        };
        const cls = groupColors[value.toLowerCase()] || 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 border-gray-200 dark:border-gray-600';

        return `<span class="${cls} px-3 py-1 rounded-full text-xs font-medium border">${value}</span>`;
    };

    /** Display Name column */
    const displayNameRenderer = (value) => {
        if (!value || !value.trim()) return '<span class="text-gray-400 dark:text-gray-500 italic text-xs">Not set</span>';
        return `<span class="text-sm text-gray-800 dark:text-gray-200">${value}</span>`;
    };

    /** Description column – truncated at 60 chars */
    const descriptionRenderer = (value) => {
        if (!value || !value.trim()) return '<span class="text-gray-400 dark:text-gray-500 italic text-xs">No description</span>';
        if (value.length > 60) {
            return `<span class="text-sm text-gray-600 dark:text-gray-400" title="${value}">${value.substring(0, 60)}…</span>`;
        }
        return `<span class="text-sm text-gray-600 dark:text-gray-400">${value}</span>`;
    };

    /**
     * Assigned toggle column
     * Shows a toggle switch.  Enabled / disabled state depends on capabilities
     * and current assigned value.
     */
    const assignedRenderer = (value, row) => {
        const isAssigned = value === true || value === 1 || value === '1';
        const permId     = row.id;

        // Can the user change the state?
        const canOn  = capabilities.can_assign_permissions;
        const canOff = capabilities.can_unassign_permissions;
        // Toggle is interactive only if the matching capability exists
        const canToggle = isAssigned ? canOff : canOn;

        const trackBg  = isAssigned ? 'bg-blue-600 dark:bg-blue-500' : 'bg-gray-300 dark:bg-gray-600';
        const knobPos  = isAssigned ? 'translate-x-5' : 'translate-x-0';
        const cursor   = canToggle  ? 'cursor-pointer' : 'cursor-not-allowed opacity-60';

        return `
            <button
                class="perm-toggle-btn relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none ${trackBg} ${cursor}"
                data-permission-id="${permId}"
                data-assigned="${isAssigned ? '1' : '0'}"
                ${canToggle ? '' : 'disabled'}
                title="${isAssigned ? 'Unassign permission' : 'Assign permission'}">
                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-200 ${knobPos}"></span>
            </button>
        `;
    };

    // ====================================================================
    // Table Config
    // ====================================================================
    const headers  = ['ID', 'Name', 'Group', 'Display Name', 'Description', 'Assigned'];
    const rowKeys  = ['id', 'name', 'group', 'display_name', 'description', 'assigned'];

    const renderers = {
        id:           idRenderer,
        name:         nameRenderer,
        group:        groupRenderer,
        display_name: displayNameRenderer,
        description:  descriptionRenderer,
        assigned:     assignedRenderer
    };

    // ====================================================================
    // Params Builder
    // ====================================================================
    function buildParams(page = 1, perPage = 25) {
        console.log('📦 [Permissions] Building params');

        const params = { page, per_page: perPage };
        const columns = {};

        if (inputId   && inputId.value.trim())       columns.id       = inputId.value.trim();
        if (inputName && inputName.value.trim())     columns.name     = inputName.value.trim();
        if (inputGroup && inputGroup.value.trim())   columns.group    = inputGroup.value.trim();
        if (inputAssigned && inputAssigned.value !== '') columns.assigned = inputAssigned.value;

        if (Object.keys(columns).length > 0) {
            params.search = { columns };
            console.log('  └─ Search columns:', columns);
        }

        console.log('  └─ Final params:', JSON.stringify(params));
        return params;
    }

    // ====================================================================
    // Pagination Info Callback
    // ====================================================================
    function getPermissionsPaginationInfo(pagination, params) {
        console.log('🎯 [Permissions] getPermissionsPaginationInfo:', pagination);

        const { page = 1, per_page = 25, total = 0, filtered = total } = pagination;

        const hasFilter = params.search &&
            (params.search.global ||
                (params.search.columns && Object.keys(params.search.columns).length > 0));
        const isFiltered = hasFilter && filtered !== total;

        const displayCount = isFiltered ? filtered : total;
        const startItem    = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
        const endItem      = Math.min(page * per_page, displayCount);

        let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;
        if (isFiltered) {
            infoText += ` <span class="text-gray-500 dark:text-gray-500 text-xs">(filtered from ${total} total)</span>`;
        }

        return { total: displayCount, info: infoText };
    }

    // ====================================================================
    // Load Permissions
    // ====================================================================
    async function loadPermissions(page = 1) {
        const params = buildParams(page, 25);
        await loadPermissionsWithParams(params);
    }

    async function loadPermissionsWithParams(params) {
        console.log('═'.repeat(60));
        console.log('🚀 [Permissions] API Request');
        console.log('📤 Endpoint: /api/roles/' + roleId + '/permissions/query');
        console.log('📦 Payload:', JSON.stringify(params, null, 2));

        currentParams = params;   // save for tableAction handler

        claimTableTarget();   // ← our container becomes #table-container

        if (typeof createTable !== 'function') {
            console.error('❌ createTable not found – data_table.js not loaded');
            releaseTableTarget();
            return;
        }

        try {
            const result = await createTable(
                `roles/${roleId}/permissions/query`,
                params,
                headers,
                rowKeys,
                false,          // no bulk selection
                'id',
                null,
                renderers,
                null,           // selectableIds
                getPermissionsPaginationInfo
            );

            releaseTableTarget();   // ← restore original ids

            if (result && result.success) {
                console.log('✅ [Permissions] Loaded:', result.data.length, 'rows');
                console.log('📊 Pagination:', result.pagination);
                setupTableFiltersAfterRender();
            } else {
                console.error('❌ [Permissions] Load failed', result);
            }
        } catch (error) {
            releaseTableTarget();   // ← restore even on error
            console.error('❌ [Permissions] Exception:', error);
            showAlert('d', 'Failed to load permissions');
        }
    }

    // ====================================================================
    // Table Target Swap
    // data_table.js always renders into #table-container.
    // Before every createTable call we rename OUR container to that id.
    // After the call we restore it so only one #table-container exists at a time.
    // ====================================================================
    const OWN_ID = 'permissions-table-container';

    function claimTableTarget() {
        // Hide any other element that currently holds the id
        const other = document.getElementById('table-container');
        if (other && other !== container) {
            other.setAttribute('data-saved-id', 'table-container');
            other.id = '';
        }
        container.id = 'table-container';
    }

    function releaseTableTarget() {
        container.id = OWN_ID;
        // Restore any element that was displaced
        const saved = document.querySelector('[data-saved-id="table-container"]');
        if (saved) {
            saved.id = 'table-container';
            saved.removeAttribute('data-saved-id');
        }
    }

    // ====================================================================
    // Table Filters – Global Search (injected into table-custom-filters)
    // ====================================================================
    function setupTableFiltersAfterRender() {
        setTimeout(() => setupTableFilters(), 100);
    }

    function setupTableFilters() {
        const filterContainer = document.getElementById('table-custom-filters');
        if (!filterContainer) return;

        filterContainer.innerHTML = `
            <div class="flex gap-4 items-center flex-wrap">
                <div class="w-64">
                    <input id="perm-global-search"
                        class="w-full border dark:border-gray-600 rounded-lg px-3 py-1 text-sm transition-colors duration-200 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400 outline-none"
                        placeholder="Search permissions..."
                        value="${currentGlobalSearch}" />
                </div>

                <div class="flex gap-2">
                    <span data-assigned="all" class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 dark:hover:bg-blue-500 hover:text-white transition-colors duration-200 ${currentAssignedFilter === 'all' ? 'bg-blue-600 dark:bg-blue-500 text-white' : ''}">All</span>
                    <span data-assigned="1"   class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 dark:hover:bg-blue-500 hover:text-white transition-colors duration-200 ${currentAssignedFilter === '1'   ? 'bg-blue-600 dark:bg-blue-500 text-white' : ''}">Assigned</span>
                    <span data-assigned="0"   class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 dark:hover:bg-blue-500 hover:text-white transition-colors duration-200 ${currentAssignedFilter === '0'   ? 'bg-blue-600 dark:bg-blue-500 text-white' : ''}">Not Assigned</span>
                </div>
            </div>
        `;

        // Global search input – 1000ms debounce + visual feedback
        const globalSearch = document.getElementById('perm-global-search');
        if (globalSearch) {
            globalSearch.addEventListener('keyup', (e) => {
                clearTimeout(globalSearch.searchTimeout);
                globalSearch.searchTimeout = setTimeout(() => {
                    handleGlobalSearch(e.target.value.trim());
                }, 1000);
            });

            globalSearch.addEventListener('input', (e) => {
                const value = e.target.value.trim();
                if (value.length > 0) {
                    globalSearch.classList.add('border-blue-300', 'bg-blue-50', 'dark:bg-blue-900/20', 'dark:border-blue-800');
                } else {
                    globalSearch.classList.remove('border-blue-300', 'bg-blue-50', 'dark:bg-blue-900/20', 'dark:border-blue-800');
                }
            });
        }

        // Assigned filter pills
        const assignedBtns = filterContainer.querySelectorAll('[data-assigned]');
        assignedBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const value = btn.getAttribute('data-assigned');
                console.log('🏷️  [Permissions] Assigned filter clicked:', value);

                currentAssignedFilter = value;

                // Update active state visually
                assignedBtns.forEach(b => {
                    b.classList.remove('bg-blue-600', 'text-white');
                    b.classList.add('hover:bg-blue-400', 'hover:text-white');
                });
                btn.classList.add('bg-blue-600', 'text-white');
                btn.classList.remove('hover:bg-blue-400', 'hover:text-white');

                handleAssignedFilter(value);
            });
        });
    }

    function handleGlobalSearch(searchValue) {
        console.log('🔎 [Permissions] Global search:', searchValue);
        currentGlobalSearch = searchValue;

        const params = buildParams(1, 25);
        if (searchValue) {
            if (!params.search) params.search = {};
            params.search.global = searchValue;
        }

        loadPermissionsWithParams(params);
    }

    function handleAssignedFilter(value) {
        console.log('🏷️  [Permissions] Filtering by assigned:', value);

        const params = buildParams(1, 25);

        // Carry forward any active global search
        if (currentGlobalSearch) {
            if (!params.search) params.search = {};
            params.search.global = currentGlobalSearch;
        }

        // Override the assigned column filter from the pill
        if (value !== 'all') {
            if (!params.search)           params.search = {};
            if (!params.search.columns)   params.search.columns = {};
            params.search.columns.assigned = value;
        }

        loadPermissionsWithParams(params);
    }

    // ====================================================================
    // Toggle Assign / Unassign
    // ====================================================================
    async function handleToggle(btn) {
        const permId    = btn.dataset.permissionId;
        const assigned  = btn.dataset.assigned === '1';

        console.log('🔄 [Permissions] Toggle clicked – id:', permId, 'currently assigned:', assigned);

        const action   = assigned ? 'unassign' : 'assign';
        const endpoint = `/api/roles/${roleId}/permissions/${action}`;
        const body     = { permission_id: Number(permId) };

        // Disable the button while request is in flight
        btn.disabled = true;
        btn.classList.add('opacity-50');

        try {
            const response = await fetch(endpoint, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(body)
            });

            console.log('📥 [Permissions] Toggle response – status:', response.status);

            // Step-Up 2FA required
            if (response.status === 403) {
                const data = await response.json().catch(() => null);
                if (data && data.code === 'STEP_UP_REQUIRED') {
                    const scope    = encodeURIComponent(data.scope || `roles.permissions.${action}`);
                    const returnTo = encodeURIComponent(window.location.pathname);
                    window.location.href = `/2fa/verify?scope=${scope}&return_to=${returnTo}`;
                    return;
                }
            }

            if (response.ok || response.status === 204) {
                console.log('✅ [Permissions] Permission ' + action + 'ed – reloading table');
                showAlert('s', `Permission ${action === 'assign' ? 'assigned' : 'unassigned'} successfully`);
                // Reload with same params to reflect new state
                await loadPermissionsWithParams(currentParams);
            } else {
                const data = await response.json().catch(() => null);
                console.error('❌ [Permissions] Toggle failed:', data);
                showAlert('w', data?.message || `Failed to ${action} permission`);
                // Re-enable on error
                btn.disabled = false;
                btn.classList.remove('opacity-50');
            }
        } catch (err) {
            console.error('❌ [Permissions] Network error:', err);
            showAlert('d', 'Network error');
            btn.disabled = false;
            btn.classList.remove('opacity-50');
        }
    }

    // ====================================================================
    // Event Listeners
    // ====================================================================

    // Search form submit
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            console.log('🔍 [Permissions] Search form submitted');
            loadPermissions();
        });
    }

    // Reset button
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            console.log('🔄 [Permissions] Resetting filters');
            if (inputId)       inputId.value       = '';
            if (inputName)     inputName.value     = '';
            if (inputGroup)    inputGroup.value    = '';
            if (inputAssigned) inputAssigned.value = '';
            currentGlobalSearch   = '';
            currentAssignedFilter = 'all';
            loadPermissions();
        });
    }

    // Delegated click – toggle buttons
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.perm-toggle-btn');
        if (btn && !btn.disabled) {
            handleToggle(btn);
        }
    });

    // tableAction events (pagination / per-page) – only while this tab is visible
    document.addEventListener('tableAction', async (e) => {
        // Only respond when the permissions panel is visible
        const panel = document.getElementById('tab-permissions');
        if (!panel || panel.classList.contains('hidden')) return;

        const { action, value, currentParams: tableParams } = e.detail;
        console.log('📨 [Permissions] tableAction:', action, value);

        let newParams = JSON.parse(JSON.stringify(tableParams));

        switch (action) {
            case 'pageChange':
                newParams.page = value;
                break;
            case 'perPageChange':
                newParams.per_page = value;
                newParams.page     = 1;
                break;
        }

        // Clean empty search values
        if (newParams.search) {
            if (!newParams.search.global?.trim()) delete newParams.search.global;
            if (newParams.search.columns) {
                Object.keys(newParams.search.columns).forEach(key => {
                    if (!newParams.search.columns[key]?.toString().trim()) {
                        delete newParams.search.columns[key];
                    }
                });
                if (Object.keys(newParams.search.columns).length === 0) delete newParams.search.columns;
            }
            if (Object.keys(newParams.search).length === 0) delete newParams.search;
        }

        await loadPermissionsWithParams(newParams);
    });

    // ====================================================================
    // Listen for tab activation (lazy load on first switch)
    // ====================================================================
    document.addEventListener('roleTabLoaded', (e) => {
        if (e.detail.tab === 'permissions') {
            console.log('📢 [Permissions] Tab activated – loading data');
            loadPermissions();
        }
    });

    // ====================================================================
    // Helpers
    // ====================================================================
    function showAlert(type, message) {
        if (typeof window.showAlert === 'function') {
            window.showAlert(type, message);
        } else {
            console.log(`[${type}] ${message}`);
        }
    }

    // ====================================================================
    // Public API
    // ====================================================================
    window.RoleDetailsPermissions = {
        loadPermissions,
        loadPermissionsWithParams
    };

    console.log('✅ Role Details Permissions – Ready');
    console.log('═'.repeat(60));
})();
