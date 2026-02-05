/**
 * Admin Permissions — Roles (Context) Module
 * Read-only display of roles assigned to this admin.
 * No role assignment or removal is allowed here — informational only.
 *
 * API endpoint:
 *   POST /api/admins/{adminId}/roles/query
 *
 * Capabilities consumed (from window.adminPermissionsCapabilities):
 *   can_view_admin_roles — tab rendered at all (Twig gate)
 */

(function () {
    'use strict';

    console.log('🔑 Admin Permissions Roles — Initializing');
    console.log('─'.repeat(60));

    const capabilities = window.adminPermissionsCapabilities || {};
    const adminId      = window.adminPermissionsAdminId;

    // Guard
    if (!capabilities.can_view_admin_roles) {
        console.log('⛔ Roles tab not available — exiting module');
        return;
    }

    // ====================================================================
    // DOM References
    // ====================================================================
    const container   = document.getElementById('roles-table-container');
    const searchForm  = document.getElementById('roles-search-form');
    const resetBtn    = document.getElementById('role-btn-reset');
    const inputId     = document.getElementById('role-filter-id');
    const inputName   = document.getElementById('role-filter-name');
    const inputGroup  = document.getElementById('role-filter-group');
    const inputActive = document.getElementById('role-filter-active');

    // ====================================================================
    // State
    // ====================================================================
    let currentParams       = {};
    let currentGlobalSearch = '';
    let currentActiveFilter = 'all';

    // ====================================================================
    // Custom Renderers
    // ====================================================================

    /** ID column */
    const idRenderer = (value) => {
        if (!value && value !== 0) return '<span class="text-gray-400 dark:text-gray-500 italic">N/A</span>';
        return `<span class="font-mono text-sm text-gray-800 dark:text-gray-300 font-medium">#${value}</span>`;
    };

    /** Name column — code badge */
    const nameRenderer = (value) => {
        if (!value) return '<span class="text-gray-400 dark:text-gray-500 italic">N/A</span>';
        return `<code class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded text-xs font-mono border border-gray-200 dark:border-gray-600">${value}</code>`;
    };

    /** Group column — colored pill */
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

    /** Display Name */
    const displayNameRenderer = (value) => {
        if (!value || !value.trim()) return '<span class="text-gray-400 dark:text-gray-500 italic text-xs">Not set</span>';
        return `<span class="text-sm text-gray-800 dark:text-gray-200">${value}</span>`;
    };

    /** Description — truncated at 55 chars */
    const descriptionRenderer = (value) => {
        if (!value || !value.trim()) return '<span class="text-gray-400 dark:text-gray-500 italic text-xs">No description</span>';
        if (value.length > 55) {
            return `<span class="text-sm text-gray-600 dark:text-gray-400" title="${value}">${value.substring(0, 55)}…</span>`;
        }
        return `<span class="text-sm text-gray-600 dark:text-gray-400">${value}</span>`;
    };

    /**
     * is_active column — Active / Inactive badge
     */
    const isActiveRenderer = (value) => {
        const isActive = value === true || value === 1 || value === '1';

        if (isActive) {
            return `<span class="bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800 px-3 py-1 rounded-full text-xs font-medium">Active</span>`;
        }
        return `<span class="bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800 px-3 py-1 rounded-full text-xs font-medium">Inactive</span>`;
    };

    // ====================================================================
    // Table Config
    // ====================================================================
    const headers  = ['ID', 'Name', 'Group', 'Display Name', 'Description', 'Status'];
    const rowKeys  = ['id', 'name', 'group', 'display_name', 'description', 'is_active'];

    const renderers = {
        id:           idRenderer,
        name:         nameRenderer,
        group:        groupRenderer,
        display_name: displayNameRenderer,
        description:  descriptionRenderer,
        is_active:    isActiveRenderer
    };

    // ====================================================================
    // Params Builder
    // ====================================================================
    function buildParams(page = 1, perPage = 20) {
        console.log('📦 [Roles] Building params');

        const params  = { page, per_page: perPage };
        const columns = {};

        if (inputId     && inputId.value.trim())     columns.id     = inputId.value.trim();
        if (inputName   && inputName.value.trim())   columns.name   = inputName.value.trim();
        if (inputGroup  && inputGroup.value.trim())  columns.group  = inputGroup.value.trim();
        if (inputActive && inputActive.value !== '') columns.is_active = inputActive.value;

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
    function getRolesPaginationInfo(pagination, params) {
        console.log('🎯 [Roles] getRolesPaginationInfo:', pagination);

        const { page = 1, per_page = 20, total = 0, filtered = total } = pagination;

        const hasFilter = params.search &&
            (params.search.global ||
                (params.search.columns && Object.keys(params.search.columns).length > 0));
        const isFiltered = hasFilter && filtered !== total;

        const displayCount = isFiltered ? filtered : total;
        const startItem    = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
        const endItem      = Math.min(page * per_page, displayCount);

        let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;
        if (isFiltered) {
            infoText += ` <span class="text-gray-500 text-xs">(filtered from ${total} total)</span>`;
        }

        return { total: displayCount, info: infoText };
    }

    // ====================================================================
    // Load Roles
    // ====================================================================
    async function loadRoles(page = 1) {
        const params = buildParams(page, 20);
        await loadRolesWithParams(params);
    }

    async function loadRolesWithParams(params) {
        console.log('─'.repeat(60));
        console.log('🚀 [Roles] API Request');
        console.log('📤 Endpoint: /api/admins/' + adminId + '/roles/query');
        console.log('📦 Payload:', JSON.stringify(params, null, 2));

        currentParams = params;

        claimTableTarget();

        if (typeof createTable !== 'function') {
            console.error('❌ createTable not found — data_table.js not loaded');
            releaseTableTarget();
            return;
        }

        try {
            const result = await createTable(
                `admins/${adminId}/roles/query`,
                params,
                headers,
                rowKeys,
                false,          // no bulk selection (read-only)
                'id',
                null,
                renderers,
                null,           // no selectableIds
                getRolesPaginationInfo
            );

            releaseTableTarget();

            if (result && result.success) {
                console.log('✅ [Roles] Loaded:', result.data.length, 'rows');
                console.log('📊 Pagination:', result.pagination);
                setupTableFiltersAfterRender();
            } else {
                console.error('❌ [Roles] Load failed', result);
            }
        } catch (error) {
            releaseTableTarget();
            console.error('❌ [Roles] Exception:', error);
            showAlert('d', 'Failed to load roles');
        }
    }

    // ====================================================================
    // Table Target Swap
    // ====================================================================
    const OWN_ID = 'roles-table-container';

    function claimTableTarget() {
        const other = document.getElementById('table-container');
        if (other && other !== container) {
            other.setAttribute('data-saved-id', 'table-container');
            other.id = '';
        }
        container.id = 'table-container';
    }

    function releaseTableTarget() {
        container.id = OWN_ID;
        const saved = document.querySelector('[data-saved-id="table-container"]');
        if (saved) {
            saved.id = 'table-container';
            saved.removeAttribute('data-saved-id');
        }
    }

    // ====================================================================
    // Table Filters — Global Search + Active Pills
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
                    <input id="roles-global-search"
                        class="w-full border dark:border-gray-600 rounded-lg px-3 py-1 text-sm transition-colors duration-200 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400 outline-none"
                        placeholder="Search roles by name or group..."
                        value="${currentGlobalSearch}" />
                </div>

                <div class="flex gap-2">
                    <span data-active="all" class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 dark:hover:bg-blue-500 hover:text-white transition-colors duration-200 ${currentActiveFilter === 'all' ? 'bg-blue-600 dark:bg-blue-500 text-white' : ''}">All</span>
                    <span data-active="1"   class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 dark:hover:bg-blue-500 hover:text-white transition-colors duration-200 ${currentActiveFilter === '1'   ? 'bg-blue-600 dark:bg-blue-500 text-white' : ''}">Active</span>
                    <span data-active="0"   class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 dark:hover:bg-blue-500 hover:text-white transition-colors duration-200 ${currentActiveFilter === '0'   ? 'bg-blue-600 dark:bg-blue-500 text-white' : ''}">Inactive</span>
                </div>
            </div>
        `;

        // Global search — 1000ms debounce
        const globalSearch = document.getElementById('roles-global-search');
        if (globalSearch) {
            globalSearch.addEventListener('keyup', () => {
                clearTimeout(globalSearch.searchTimeout);
                globalSearch.searchTimeout = setTimeout(() => {
                    handleGlobalSearch(globalSearch.value.trim());
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

        // Active filter pills
        const activeBtns = filterContainer.querySelectorAll('[data-active]');
        activeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const value = btn.getAttribute('data-active');
                console.log('🏷️  [Roles] Active filter clicked:', value);

                currentActiveFilter = value;

                activeBtns.forEach(b => {
                    b.classList.remove('bg-blue-600', 'text-white');
                    b.classList.add('hover:bg-blue-400', 'hover:text-white');
                });
                btn.classList.add('bg-blue-600', 'text-white');
                btn.classList.remove('hover:bg-blue-400', 'hover:text-white');

                handleActiveFilter(value);
            });
        });
    }

    function handleGlobalSearch(searchValue) {
        console.log('🔍 [Roles] Global search:', searchValue);
        currentGlobalSearch = searchValue;

        const params = buildParams(1, 20);
        if (searchValue) {
            if (!params.search) params.search = {};
            params.search.global = searchValue;
        }

        // Carry forward active pill filter
        if (currentActiveFilter !== 'all') {
            if (!params.search)           params.search = {};
            if (!params.search.columns)   params.search.columns = {};
            params.search.columns.is_active = currentActiveFilter;
        }

        loadRolesWithParams(params);
    }

    function handleActiveFilter(value) {
        console.log('🏷️  [Roles] Filtering by is_active:', value);

        const params = buildParams(1, 20);

        // Carry forward global search
        if (currentGlobalSearch) {
            if (!params.search) params.search = {};
            params.search.global = currentGlobalSearch;
        }

        if (value !== 'all') {
            if (!params.search)           params.search = {};
            if (!params.search.columns)   params.search.columns = {};
            params.search.columns.is_active = value;
        }

        loadRolesWithParams(params);
    }

    // ====================================================================
    // Event Listeners
    // ====================================================================

    // Search form
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            console.log('🔍 [Roles] Search form submitted');
            loadRoles();
        });
    }

    // Reset button
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            console.log('🔄 [Roles] Resetting filters');
            if (inputId)     inputId.value     = '';
            if (inputName)   inputName.value   = '';
            if (inputGroup)  inputGroup.value  = '';
            if (inputActive) inputActive.value = '';
            currentGlobalSearch  = '';
            currentActiveFilter  = 'all';
            loadRoles();
        });
    }

    // tableAction events (pagination / per-page) — only while this tab is visible
    document.addEventListener('tableAction', async (e) => {
        const panel = document.getElementById('tab-roles');
        if (!panel || panel.classList.contains('hidden')) return;

        const { action, value, currentParams: tableParams } = e.detail;
        console.log('📨 [Roles] tableAction:', action, value);

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

        await loadRolesWithParams(newParams);
    });

    // ====================================================================
    // Listen for tab activation
    // ====================================================================
    document.addEventListener('adminPermTabLoaded', (e) => {
        if (e.detail.tab === 'roles') {
            console.log('📢 [Roles] Tab activated — loading data');
            loadRoles();
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
    window.AdminPermissionsRoles = {
        loadRoles,
        loadRolesWithParams
    };

    console.log('✅ Admin Permissions Roles — Ready');
    console.log('─'.repeat(60));
})();
