/**
 * Role Details – Admins Module
 * Owns the Admins tab: search form, table, toggle assign/unassign.
 *
 * API endpoints used:
 *   POST /api/roles/{roleId}/admins/query
 *   POST /api/roles/{roleId}/admins/assign     → 204
 *   POST /api/roles/{roleId}/admins/unassign   → 204
 *
 * Capabilities consumed (from window.roleDetailsCapabilities):
 *   can_view_admins         – tab is rendered at all (Twig gate)
 *   can_assign_admins       – toggle ON  enabled
 *   can_unassign_admins     – toggle OFF enabled
 *   can_view_admin_profile  – admin name becomes a clickable link
 */

(function () {
    'use strict';

    console.log('👥 Role Details Admins – Initializing');
    console.log('═'.repeat(60));

    const capabilities = window.roleDetailsCapabilities || {};
    const roleId       = window.roleDetailsId;

    console.log('🔐 Admins capabilities:');
    console.log('  ├─ can_view_admins:         ', capabilities.can_view_admins         ? '✅ YES' : '❌ NO');
    console.log('  ├─ can_assign_admins:       ', capabilities.can_assign_admins       ? '✅ YES' : '❌ NO');
    console.log('  ├─ can_unassign_admins:     ', capabilities.can_unassign_admins     ? '✅ YES' : '❌ NO');
    console.log('  └─ can_view_admin_profile:  ', capabilities.can_view_admin_profile  ? '✅ YES' : '❌ NO');

    // Guard – if the tab wasn't rendered, exit silently
    if (!capabilities.can_view_admins) {
        console.log('⛔ Admins tab not available – exiting module');
        return;
    }

    // ====================================================================
    // DOM References
    // ====================================================================
    const container      = document.getElementById('admins-table-container');
    const searchForm     = document.getElementById('admins-search-form');
    const resetBtn       = document.getElementById('admin-btn-reset');
    const inputId        = document.getElementById('admin-filter-id');
    const inputStatus    = document.getElementById('admin-filter-status');
    const inputAssigned  = document.getElementById('admin-filter-assigned');

    // ====================================================================
    // State
    // ====================================================================
    let currentParams = {};
    let currentGlobalSearch  = '';   // persists global search value across re-renders
    let currentStatusFilter  = 'all'; // persists status pill across re-renders

    // ====================================================================
    // Custom Renderers
    // ====================================================================

    /** ID column – plain mono badge */
    const idRenderer = (value) => {
        if (!value && value !== 0) return '<span class="text-gray-400 italic">N/A</span>';
        return `<span class="font-mono text-sm text-gray-800 dark:text-gray-200 font-medium">#${value}</span>`;
    };

    /**
     * Display Name column
     * If can_view_admin_profile → clickable link to /admins/{id}/profile
     * Otherwise plain text.
     */
    const displayNameRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';

        if (capabilities.can_view_admin_profile && row.id) {
            return `<a href="/admins/${row.id}/profile"
                       class="text-blue-600 hover:text-blue-800 hover:underline font-medium text-sm"
                       title="View admin profile">
                       ${value}
                    </a>`;
        }

        return `<span class="text-sm text-gray-800 font-medium">${value}</span>`;
    };

    /** Status column – color-coded badge */
    const statusRenderer = (value) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';

        const status = value.toUpperCase();
        let cls = 'bg-gray-600';   // fallback

        if      (status === 'ACTIVE')    cls = 'bg-green-600';
        else if (status === 'SUSPENDED') cls = 'bg-orange-600';
        else if (status === 'DISABLED')  cls = 'bg-red-600';

        return `<span class="${cls} text-white px-3 py-1 rounded-lg text-xs font-medium uppercase tracking-wide">${status}</span>`;
    };

    /**
     * Assigned toggle column
     * Same toggle-switch pattern as the Permissions tab.
     * Enabled / disabled depends on capabilities.
     */
    const assignedRenderer = (value, row) => {
        const isAssigned = value === true || value === 1 || value === '1';
        const adminId    = row.id;

        const canOn  = capabilities.can_assign_admins;
        const canOff = capabilities.can_unassign_admins;
        const canToggle = isAssigned ? canOff : canOn;

        const trackBg = isAssigned ? 'bg-blue-600' : 'bg-gray-300';
        const knobPos = isAssigned ? 'translate-x-5' : 'translate-x-0';
        const cursor  = canToggle  ? 'cursor-pointer' : 'cursor-not-allowed opacity-60';

        return `
            <button
                class="admin-toggle-btn relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none ${trackBg} ${cursor}"
                data-admin-id="${adminId}"
                data-assigned="${isAssigned ? '1' : '0'}"
                ${canToggle ? '' : 'disabled'}
                title="${isAssigned ? 'Unassign admin from role' : 'Assign admin to role'}">
                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-200 ${knobPos}"></span>
            </button>
        `;
    };

    // ====================================================================
    // Table Config
    // ====================================================================
    const headers  = ['ID', 'Display Name', 'Status', 'Assigned'];
    const rowKeys  = ['id', 'display_name', 'status', 'assigned'];

    const renderers = {
        id:           idRenderer,
        display_name: displayNameRenderer,
        status:       statusRenderer,
        assigned:     assignedRenderer
    };

    // ====================================================================
    // Params Builder
    // ====================================================================
    function buildParams(page = 1, perPage = 20) {
        console.log('📦 [Admins] Building params');

        const params = { page, per_page: perPage };
        const columns = {};

        if (inputId       && inputId.value.trim())            columns.id       = inputId.value.trim();
        if (inputStatus   && inputStatus.value)               columns.status   = inputStatus.value;
        if (inputAssigned && inputAssigned.value !== '')      columns.assigned = inputAssigned.value;

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
    function getAdminsPaginationInfo(pagination, params) {
        console.log('🎯 [Admins] getAdminsPaginationInfo:', pagination);

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
    // Load Admins
    // ====================================================================
    async function loadAdmins(page = 1) {
        const params = buildParams(page, 20);
        await loadAdminsWithParams(params);
    }

    async function loadAdminsWithParams(params) {
        console.log('═'.repeat(60));
        console.log('🚀 [Admins] API Request');
        console.log('📤 Endpoint: /api/roles/' + roleId + '/admins/query');
        console.log('📦 Payload:', JSON.stringify(params, null, 2));

        currentParams = params;

        claimTableTarget();   // ← our container becomes #table-container

        if (typeof createTable !== 'function') {
            console.error('❌ createTable not found – data_table.js not loaded');
            releaseTableTarget();
            return;
        }

        try {
            const result = await createTable(
                `roles/${roleId}/admins/query`,
                params,
                headers,
                rowKeys,
                false,          // no bulk selection
                'id',
                null,
                renderers,
                null,           // selectableIds
                getAdminsPaginationInfo
            );

            releaseTableTarget();   // ← restore original ids

            if (result && result.success) {
                console.log('✅ [Admins] Loaded:', result.data.length, 'rows');
                console.log('📊 Pagination:', result.pagination);
                setupTableFiltersAfterRender();
            } else {
                console.error('❌ [Admins] Load failed', result);
            }
        } catch (error) {
            releaseTableTarget();   // ← restore even on error
            console.error('❌ [Admins] Exception:', error);
            showAlert('d', 'Failed to load admins');
        }
    }

    // ====================================================================
    // Table Target Swap
    // data_table.js always renders into #table-container.
    // Before every createTable call we rename OUR container to that id.
    // After the call we restore it so only one #table-container exists at a time.
    // ====================================================================
    const OWN_ID = 'admins-table-container';

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
                    <input id="admin-global-search"
                        class="w-full border rounded-lg px-3 py-1 text-sm transition-colors duration-200"
                        placeholder="Search admins..."
                        value="${currentGlobalSearch}" />
                </div>

                <div class="flex gap-2">
                    <span data-status="all"       class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 hover:text-white transition-colors duration-200 ${currentStatusFilter === 'all'       ? 'bg-blue-600 text-white' : ''}">All</span>
                    <span data-status="ACTIVE"    class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 hover:text-white transition-colors duration-200 ${currentStatusFilter === 'ACTIVE'    ? 'bg-blue-600 text-white' : ''}">Active</span>
                    <span data-status="SUSPENDED" class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 hover:text-white transition-colors duration-200 ${currentStatusFilter === 'SUSPENDED' ? 'bg-blue-600 text-white' : ''}">Suspended</span>
                    <span data-status="DISABLED"  class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 hover:text-white transition-colors duration-200 ${currentStatusFilter === 'DISABLED'  ? 'bg-blue-600 text-white' : ''}">Disabled</span>
                </div>
            </div>
        `;

        // Global search input – 1000ms debounce + visual feedback
        const globalSearch = document.getElementById('admin-global-search');
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
                    globalSearch.classList.add('border-blue-300', 'bg-blue-50');
                } else {
                    globalSearch.classList.remove('border-blue-300', 'bg-blue-50');
                }
            });
        }

        // Status filter pills
        const statusBtns = filterContainer.querySelectorAll('[data-status]');
        statusBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const value = btn.getAttribute('data-status');
                console.log('🏷️  [Admins] Status filter clicked:', value);

                currentStatusFilter = value;

                statusBtns.forEach(b => {
                    b.classList.remove('bg-blue-600', 'text-white');
                    b.classList.add('hover:bg-blue-400', 'hover:text-white');
                });
                btn.classList.add('bg-blue-600', 'text-white');
                btn.classList.remove('hover:bg-blue-400', 'hover:text-white');

                handleStatusFilter(value);
            });
        });
    }

    function handleGlobalSearch(searchValue) {
        console.log('🔎 [Admins] Global search:', searchValue);
        currentGlobalSearch = searchValue;

        const params = buildParams(1, 20);
        if (searchValue) {
            if (!params.search) params.search = {};
            params.search.global = searchValue;
        }

        loadAdminsWithParams(params);
    }

    function handleStatusFilter(value) {
        console.log('🏷️  [Admins] Filtering by status:', value);

        const params = buildParams(1, 20);

        // Carry forward any active global search
        if (currentGlobalSearch) {
            if (!params.search) params.search = {};
            params.search.global = currentGlobalSearch;
        }

        // Override the status column filter from the pill
        if (value !== 'all') {
            if (!params.search)           params.search = {};
            if (!params.search.columns)   params.search.columns = {};
            params.search.columns.status = value;
        }

        loadAdminsWithParams(params);
    }

    // ====================================================================
    // Toggle Assign / Unassign
    // ====================================================================
    async function handleToggle(btn) {
        const adminId   = btn.dataset.adminId;
        const assigned  = btn.dataset.assigned === '1';

        console.log('🔄 [Admins] Toggle clicked – id:', adminId, 'currently assigned:', assigned);

        const action   = assigned ? 'unassign' : 'assign';
        const endpoint = `/api/roles/${roleId}/admins/${action}`;
        const body     = { admin_id: Number(adminId) };

        btn.disabled = true;
        btn.classList.add('opacity-50');

        try {
            const response = await fetch(endpoint, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(body)
            });

            console.log('📥 [Admins] Toggle response – status:', response.status);

            // Step-Up 2FA required
            if (response.status === 403) {
                const data = await response.json().catch(() => null);

                // ✅ Use ErrorNormalizer Bridge
                const stepUp = window.ErrorNormalizer.getLegacyStepUpView(data);
                if (stepUp) {
                    const scope    = encodeURIComponent(stepUp.scope || `roles.admins.${action}`);
                    const returnTo = encodeURIComponent(window.location.pathname);
                    window.location.href = `/2fa/verify?scope=${scope}&return_to=${returnTo}`;
                    return;
                }
            }

            if (response.ok || response.status === 204) {
                console.log('✅ [Admins] Admin ' + action + 'ed – reloading table');
                showAlert('s', `Admin ${action === 'assign' ? 'assigned to' : 'unassigned from'} role`);
                await loadAdminsWithParams(currentParams);
            } else {
                const data = await response.json().catch(() => null);
                console.error('❌ [Admins] Toggle failed:', data);
                showAlert('w', data?.message || `Failed to ${action} admin`);
                btn.disabled = false;
                btn.classList.remove('opacity-50');
            }
        } catch (err) {
            console.error('❌ [Admins] Network error:', err);
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
            console.log('🔍 [Admins] Search form submitted');
            loadAdmins();
        });
    }

    // Reset button
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            console.log('🔄 [Admins] Resetting filters');
            if (inputId)       inputId.value       = '';
            if (inputStatus)   inputStatus.value   = '';
            if (inputAssigned) inputAssigned.value = '';
            currentGlobalSearch  = '';
            currentStatusFilter  = 'all';
            loadAdmins();
        });
    }

    // Delegated click – toggle buttons
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.admin-toggle-btn');
        if (btn && !btn.disabled) {
            handleToggle(btn);
        }
    });

    // tableAction events (pagination / per-page) – only while this tab is visible
    document.addEventListener('tableAction', async (e) => {
        const panel = document.getElementById('tab-admins');
        if (!panel || panel.classList.contains('hidden')) return;

        const { action, value, currentParams: tableParams } = e.detail;
        console.log('📨 [Admins] tableAction:', action, value);

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

        await loadAdminsWithParams(newParams);
    });

    // ====================================================================
    // Listen for tab activation (lazy load on first switch)
    // ====================================================================
    document.addEventListener('roleTabLoaded', (e) => {
        if (e.detail.tab === 'admins') {
            console.log('📢 [Admins] Tab activated – loading data');
            loadAdmins();
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
    window.RoleDetailsAdmins = {
        loadAdmins,
        loadAdminsWithParams
    };

    console.log('✅ Role Details Admins – Ready');
    console.log('═'.repeat(60));
})();
