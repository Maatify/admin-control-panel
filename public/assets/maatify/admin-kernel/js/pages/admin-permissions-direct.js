/**
 * Admin Permissions — Direct Permissions Module
 * Manage explicit allow / deny overrides for a specific admin.
 *
 * API endpoints:
 *   POST /api/admins/{adminId}/permissions/direct/query              → paginated list
 *   POST /api/admins/{adminId}/permissions/direct/assignable/query  → paginated assignable list (modal)
 *   POST /api/admins/{adminId}/permissions/direct/assign            → 204
 *   POST /api/admins/{adminId}/permissions/direct/revoke            → 204
 *
 * Capabilities consumed (from window.adminPermissionsCapabilities):
 *   can_view_admin_direct_permissions   — tab rendered at all (Twig gate)
 *   can_assign_admin_direct_permissions — Assign button + assignable modal visible
 *   can_revoke_admin_direct_permissions — Revoke button in each row visible
 */

(function () {
    'use strict';

    console.log('🔑 Admin Permissions Direct — Initializing');
    console.log('─'.repeat(60));

    const capabilities = window.adminPermissionsCapabilities || {};
    const adminId      = window.adminPermissionsAdminId;

    console.log('🔑 Direct capabilities:');
    console.log('  ├─ can_view_admin_direct_permissions:   ', capabilities.can_view_admin_direct_permissions   ? '✅ YES' : '❌ NO');
    console.log('  ├─ can_assign_admin_direct_permissions: ', capabilities.can_assign_admin_direct_permissions ? '✅ YES' : '❌ NO');
    console.log('  └─ can_revoke_admin_direct_permissions: ', capabilities.can_revoke_admin_direct_permissions ? '✅ YES' : '❌ NO');

    // Guard
    if (!capabilities.can_view_admin_direct_permissions) {
        console.log('⛔ Direct tab not available — exiting module');
        return;
    }

    const canAssign = capabilities.can_assign_admin_direct_permissions || false;
    const canRevoke = capabilities.can_revoke_admin_direct_permissions || false;

    // ====================================================================
    // DOM References — Direct Tab Search Form
    // ====================================================================
    const container       = document.getElementById('direct-table-container');
    const searchForm      = document.getElementById('direct-search-form');
    const resetBtn        = document.getElementById('dir-btn-reset');
    const inputId         = document.getElementById('dir-filter-id');
    const inputName       = document.getElementById('dir-filter-name');
    const inputGroup      = document.getElementById('dir-filter-group');
    const inputAllowed    = document.getElementById('dir-filter-allowed');
    const btnAssignDirect = document.getElementById('btn-assign-direct');

    // ====================================================================
    // DOM References — Assignable Modal
    // ====================================================================
    const assignModal              = document.getElementById('assign-direct-modal');
    const closeAssignModalBtn      = document.getElementById('close-assign-modal-btn');
    const assignModalMessage       = document.getElementById('assign-modal-message');
    const assignableContainer      = document.getElementById('assignable-table-container');
    const assignableGlobalSearch   = document.getElementById('assignable-global-search');
    const assignableFilterAssigned = document.getElementById('assignable-filter-assigned');
    const assignableBtnReset       = document.getElementById('assignable-btn-reset');

    // ====================================================================
    // State — Direct Tab
    // ====================================================================
    let currentParams        = {};
    let currentGlobalSearch  = '';
    let currentAllowedFilter = 'all';

    // ====================================================================
    // State — Assignable Modal
    // ====================================================================
    let assignableParams        = {};
    let assignableGlobalValue   = '';
    let assignableAssignedValue = '';

    // ====================================================================
    // Custom Renderers — Direct Table
    // ====================================================================

    const idRenderer = (value) => {
        if (!value && value !== 0) return '<span class="text-gray-400 italic">N/A</span>';
        return `<span class="font-mono text-sm text-gray-800 font-medium">#${value}</span>`;
    };

    const nameRenderer = (value) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';
        return `<code class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-mono border border-gray-200">${value}</code>`;
    };

    const groupRenderer = (value) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';
        const groupColors = {
            'admins':      'bg-blue-100 text-blue-800 border-blue-200',
            'sessions':    'bg-green-100 text-green-800 border-green-200',
            'permissions': 'bg-purple-100 text-purple-800 border-purple-200',
            'roles':       'bg-orange-100 text-orange-800 border-orange-200'
        };
        const cls = groupColors[value.toLowerCase()] || 'bg-gray-100 text-gray-800 border-gray-200';
        return `<span class="${cls} px-3 py-1 rounded-full text-xs font-medium border">${value}</span>`;
    };

    const displayNameRenderer = (value) => {
        if (!value || !value.trim()) return '<span class="text-gray-400 italic text-xs">Not set</span>';
        return `<span class="text-sm text-gray-800">${value}</span>`;
    };

    const isAllowedRenderer = (value) => {
        const isAllowed = value === true || value === 1 || value === '1';
        if (isAllowed) {
            return `<span class="bg-green-100 text-green-800 border border-green-200 px-3 py-1 rounded-full text-xs font-medium">Allowed</span>`;
        }
        return `<span class="bg-red-100 text-red-800 border border-red-200 px-3 py-1 rounded-full text-xs font-medium">Denied</span>`;
    };

    const expiresAtRenderer = (value) => {
        if (!value) return '<span class="text-gray-400 text-xs">Never</span>';
        const date      = new Date(value.replace(' ', 'T'));
        const now       = new Date();
        const formatted = date.toLocaleString();
        if (date < now) {
            return `<span class="text-red-600 text-xs font-medium">${formatted} <span class="text-red-400">(expired)</span></span>`;
        }
        return `<span class="text-sm text-gray-600">${formatted}</span>`;
    };

    const grantedAtRenderer = (value) => {
        if (!value) return '<span class="text-gray-400 text-xs">—</span>';
        const date = new Date(value.replace(' ', 'T'));
        return `<span class="text-sm text-gray-600">${date.toLocaleString()}</span>`;
    };

    /** Actions column — Revoke button (only if can_revoke) */
    const actionsRenderer = (value, row) => {
        if (!canRevoke) return '<span class="text-gray-400">—</span>';
        const permId = row.id;
        if (!permId) return '<span class="text-gray-400">—</span>';
        return `
            <button class="direct-revoke-btn text-xs px-3 py-1 bg-red-600 text-white rounded-md hover:bg-red-700 transition-all duration-200"
                    data-permission-id="${permId}"
                    title="Revoke this direct permission">
                Revoke
            </button>
        `;
    };

    // ====================================================================
    // Table Config — Direct Tab
    // ====================================================================
    let headers, rowKeys;
    if (canRevoke) {
        headers  = ['ID', 'Name', 'Group', 'Display Name', 'Type', 'Expires At', 'Granted At', 'Actions'];
        rowKeys  = ['id', 'name', 'group', 'display_name', 'is_allowed', 'expires_at', 'granted_at', 'actions'];
    } else {
        headers  = ['ID', 'Name', 'Group', 'Display Name', 'Type', 'Expires At', 'Granted At'];
        rowKeys  = ['id', 'name', 'group', 'display_name', 'is_allowed', 'expires_at', 'granted_at'];
    }

    const renderers = {
        id:           idRenderer,
        name:         nameRenderer,
        group:        groupRenderer,
        display_name: displayNameRenderer,
        is_allowed:   isAllowedRenderer,
        expires_at:   expiresAtRenderer,
        granted_at:   grantedAtRenderer,
        actions:      actionsRenderer
    };

    // ====================================================================
    // Params Builder — Direct Tab
    // ====================================================================
    function buildParams(page = 1, perPage = 20) {
        console.log('📦 [Direct] Building params');
        const params  = { page, per_page: perPage };
        const columns = {};

        if (inputId      && inputId.value.trim())      columns.id         = inputId.value.trim();
        if (inputName    && inputName.value.trim())    columns.name       = inputName.value.trim();
        if (inputGroup   && inputGroup.value.trim())   columns.group      = inputGroup.value.trim();
        if (inputAllowed && inputAllowed.value !== '') columns.is_allowed = inputAllowed.value;

        if (Object.keys(columns).length > 0) {
            params.search = { columns };
            console.log('  └─ Search columns:', columns);
        }

        console.log('  └─ Final params:', JSON.stringify(params));
        return params;
    }

    // ====================================================================
    // Pagination Info Callback — Direct Tab
    // ====================================================================
    function getDirectPaginationInfo(pagination, params) {
        console.log('🎯 [Direct] getDirectPaginationInfo:', pagination);
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
    // Load Direct Permissions
    // ====================================================================
    async function loadDirect(page = 1) {
        const params = buildParams(page, 20);
        await loadDirectWithParams(params);
    }

    async function loadDirectWithParams(params) {
        console.log('─'.repeat(60));
        console.log('🚀 [Direct] API Request');
        console.log('📤 Endpoint: /api/admins/' + adminId + '/permissions/direct/query');
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
                `admins/${adminId}/permissions/direct/query`,
                params,
                headers,
                rowKeys,
                false,
                'id',
                null,
                renderers,
                null,
                getDirectPaginationInfo
            );

            releaseTableTarget();

            if (result && result.success) {
                console.log('✅ [Direct] Loaded:', result.data.length, 'rows');
                console.log('📊 Pagination:', result.pagination);
                setupTableFiltersAfterRender();
            } else {
                console.error('❌ [Direct] Load failed', result);
            }
        } catch (error) {
            releaseTableTarget();
            console.error('❌ [Direct] Exception:', error);
            showAlert('d', 'Failed to load direct permissions');
        }
    }

    // ====================================================================
    // Table Target Swap — Direct Tab
    // ====================================================================
    const OWN_ID = 'direct-table-container';

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
    // Table Filters — Global Search + Allowed Pills (Direct Tab)
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
                    <input id="dir-global-search"
                        class="w-full border rounded-lg px-3 py-1 text-sm transition-colors duration-200"
                        placeholder="Search direct permissions..."
                        value="${currentGlobalSearch}" />
                </div>
                <div class="flex gap-2">
                    <span data-allowed="all" class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 hover:text-white transition-colors duration-200 ${currentAllowedFilter === 'all' ? 'bg-blue-600 text-white' : ''}">All</span>
                    <span data-allowed="1"   class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 hover:text-white transition-colors duration-200 ${currentAllowedFilter === '1'   ? 'bg-blue-600 text-white' : ''}">Allowed</span>
                    <span data-allowed="0"   class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 hover:text-white transition-colors duration-200 ${currentAllowedFilter === '0'   ? 'bg-blue-600 text-white' : ''}">Denied</span>
                </div>
            </div>
        `;

        // Global search — 1000ms debounce
        const globalSearch = document.getElementById('dir-global-search');
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
                    globalSearch.classList.add('border-blue-300', 'bg-blue-50');
                } else {
                    globalSearch.classList.remove('border-blue-300', 'bg-blue-50');
                }
            });
        }

        // Allowed filter pills
        const allowedBtns = filterContainer.querySelectorAll('[data-allowed]');
        allowedBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const value = btn.getAttribute('data-allowed');
                console.log('🏷️  [Direct] Allowed filter clicked:', value);

                currentAllowedFilter = value;

                allowedBtns.forEach(b => {
                    b.classList.remove('bg-blue-600', 'text-white');
                    b.classList.add('hover:bg-blue-400', 'hover:text-white');
                });
                btn.classList.add('bg-blue-600', 'text-white');
                btn.classList.remove('hover:bg-blue-400', 'hover:text-white');

                handleAllowedFilter(value);
            });
        });
    }

    function handleGlobalSearch(searchValue) {
        console.log('🔍 [Direct] Global search:', searchValue);
        currentGlobalSearch = searchValue;

        const params = buildParams(1, 20);
        if (searchValue) {
            if (!params.search) params.search = {};
            params.search.global = searchValue;
        }

        if (currentAllowedFilter !== 'all') {
            if (!params.search)           params.search = {};
            if (!params.search.columns)   params.search.columns = {};
            params.search.columns.is_allowed = currentAllowedFilter;
        }

        loadDirectWithParams(params);
    }

    function handleAllowedFilter(value) {
        console.log('🏷️  [Direct] Filtering by is_allowed:', value);

        const params = buildParams(1, 20);

        if (currentGlobalSearch) {
            if (!params.search) params.search = {};
            params.search.global = currentGlobalSearch;
        }

        if (value !== 'all') {
            if (!params.search)           params.search = {};
            if (!params.search.columns)   params.search.columns = {};
            params.search.columns.is_allowed = value;
        }

        loadDirectWithParams(params);
    }

    // ====================================================================
    // Revoke Handler — Direct Tab Row
    // ====================================================================
    async function handleRevoke(btn) {
        const permId = btn.dataset.permissionId;
        console.log('🗑️  [Direct] Revoke clicked — permission_id:', permId);

        if (!confirm('Revoke this direct permission? This action cannot be undone.')) return;

        btn.disabled = true;
        btn.classList.add('opacity-50');

        try {
            const response = await fetch(`/api/admins/${adminId}/permissions/direct/revoke`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ permission_id: Number(permId) })
            });

            console.log('📥 [Direct] Revoke response — status:', response.status);

            // Step-Up 2FA
            if (response.status === 403) {
                const data = await response.json().catch(() => null);
                if (data && data.code === 'STEP_UP_REQUIRED') {
                    const scope    = encodeURIComponent(data.scope || 'admin.permissions.direct.revoke');
                    const returnTo = encodeURIComponent(window.location.pathname);
                    window.location.href = `/2fa/verify?scope=${scope}&return_to=${returnTo}`;
                    return;
                }
            }

            if (response.ok || response.status === 204) {
                console.log('✅ [Direct] Permission revoked — reloading table');
                showAlert('s', 'Direct permission revoked successfully');
                await loadDirectWithParams(currentParams);
            } else {
                const data = await response.json().catch(() => null);
                console.error('❌ [Direct] Revoke failed:', data);
                showAlert('w', data?.message || 'Failed to revoke permission');
                btn.disabled = false;
                btn.classList.remove('opacity-50');
            }
        } catch (err) {
            console.error('❌ [Direct] Network error:', err);
            showAlert('d', 'Network error');
            btn.disabled = false;
            btn.classList.remove('opacity-50');
        }
    }

    // ====================================================================
    // Assignable Modal — Open / Close
    // ====================================================================
    // Tracks whether a mutation (assign/revoke) happened while the modal was open,
    // so we know to reload the direct tab after close.
    let assignModalDirty = false;

    function openAssignModal() {
        if (!assignModal) return;

        // Reset modal search state
        assignableGlobalValue   = '';
        assignableAssignedValue = '';
        assignModalDirty        = false;
        if (assignableGlobalSearch)   {
            assignableGlobalSearch.value = '';
            assignableGlobalSearch.classList.remove('border-blue-300', 'bg-blue-50');
        }
        if (assignableFilterAssigned) assignableFilterAssigned.value = '';

        hideAssignModalMessage();

        // Claim #table-container ONCE here — stays claimed until closeAssignModal.
        // This hides the direct tab's container from data_table.js so that
        // #pagination, .form-group-select, and showLoadingIndicator all resolve
        // exclusively to the modal's elements for the modal's entire lifetime.
        claimAssignableTarget();

        assignModal.classList.remove('hidden');

        console.log('📢 [Assignable] Modal opened — loading assignable permissions');
        loadAssignable();
    }

    function closeAssignModal() {
        if (assignModal) assignModal.classList.add('hidden');
        hideAssignModalMessage();
        collapseAllInlineForms();

        // Release #table-container back to the direct tab — the only place we release.
        releaseAssignableTarget();

        // Always reload the direct tab: its HTML was cleared when the modal claimed
        // the target, so it needs to re-render regardless of mutations.
        assignModalDirty = false;
        loadDirectWithParams(currentParams);
    }

    // ====================================================================
    // Assignable Modal — Params Builder
    // ====================================================================
    function buildAssignableParams(page = 1, perPage = 25) {
        console.log('📦 [Assignable] Building params');
        const params  = { page, per_page: perPage };
        const columns = {};

        if (assignableAssignedValue !== '') {
            columns.assigned = assignableAssignedValue;
        }

        if (Object.keys(columns).length > 0) {
            params.search = { columns };
        }

        if (assignableGlobalValue) {
            if (!params.search) params.search = {};
            params.search.global = assignableGlobalValue;
        }

        console.log('  └─ Final params:', JSON.stringify(params));
        return params;
    }

    // ====================================================================
    // Assignable Modal — Pagination Info
    // ====================================================================
    function getAssignablePaginationInfo(pagination, params) {
        console.log('🎯 [Assignable] Pagination:', pagination);
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
            infoText += ` <span class="text-gray-500 text-xs">(filtered from ${total} total)</span>`;
        }
        return { total: displayCount, info: infoText };
    }

    // ====================================================================
    // Assignable Modal — Custom Renderers
    // ====================================================================

    /** Assigned state badge */
    const assignedRenderer = (value) => {
        const isAssigned = value === true || value === 1 || value === '1';
        if (isAssigned) {
            return `<span class="bg-blue-100 text-blue-800 border border-blue-200 px-3 py-1 rounded-full text-xs font-medium">Assigned</span>`;
        }
        return `<span class="bg-gray-100 text-gray-600 border border-gray-200 px-3 py-1 rounded-full text-xs font-medium">—</span>`;
    };

    /** is_allowed inside assignable — only meaningful if assigned */
    const assignableIsAllowedRenderer = (value, row) => {
        const isAssigned = row.assigned === true || row.assigned === 1 || row.assigned === '1';
        if (!isAssigned) return '<span class="text-gray-400 text-xs">—</span>';
        return isAllowedRenderer(value);
    };

    /** expires_at inside assignable — only meaningful if assigned */
    const assignableExpiresAtRenderer = (value, row) => {
        const isAssigned = row.assigned === true || row.assigned === 1 || row.assigned === '1';
        if (!isAssigned) return '<span class="text-gray-400 text-xs">—</span>';
        return expiresAtRenderer(value);
    };

    /**
     * Actions column for the assignable table.
     * - Not assigned → "Assign" button
     * - Assigned     → "Edit" + "Revoke" buttons
     */
    const assignableActionsRenderer = (value, row) => {
        const permId     = row.id;
        const isAssigned = row.assigned === true || row.assigned === 1 || row.assigned === '1';

        if (!isAssigned) {
            return `
                <button class="assignable-assign-btn text-xs px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-all duration-200"
                        data-permission-id="${permId}">
                    Assign
                </button>
            `;
        }

        const currentAllowed = row.is_allowed === true || row.is_allowed === 1 || row.is_allowed === '1' ? '1' : '0';
        const currentExpires = row.expires_at || '';

        return `
            <div class="flex gap-1.5">
                <button class="assignable-edit-btn text-xs px-3 py-1 bg-gray-100 text-gray-700 border border-gray-300 rounded-md hover:bg-gray-200 transition-all duration-200"
                        data-permission-id="${permId}"
                        data-current-allowed="${currentAllowed}"
                        data-current-expires="${currentExpires}">
                    Edit
                </button>
                <button class="assignable-revoke-btn text-xs px-3 py-1 bg-red-600 text-white rounded-md hover:bg-red-700 transition-all duration-200"
                        data-permission-id="${permId}">
                    Revoke
                </button>
            </div>
        `;
    };

    // ====================================================================
    // Assignable Modal — Table Target Swap
    // ====================================================================
    const ASSIGNABLE_OWN_ID = 'assignable-table-container';

    function claimAssignableTarget() {
        // The direct tab already released its claim after its own createTable,
        // so its id is "direct-table-container" now — getElementById("table-container")
        // would miss it.  Use the captured `container` ref directly to clear its
        // rendered HTML (removes duplicate #pagination / .form-group-select from DOM).
        // The direct tab re-renders cleanly on modal close via loadDirectWithParams.
        if (container) {
            container.innerHTML = '';
        }
        if (assignableContainer) assignableContainer.id = 'table-container';
    }

    function releaseAssignableTarget() {
        if (assignableContainer) assignableContainer.id = ASSIGNABLE_OWN_ID;
    }

    // ====================================================================
    // Assignable Modal — Load Table
    // ====================================================================
    async function loadAssignable(page = 1) {
        const params = buildAssignableParams(page, 25);
        await loadAssignableWithParams(params);
    }

    async function loadAssignableWithParams(params) {
        console.log('─'.repeat(60));
        console.log('🚀 [Assignable] API Request');
        console.log('📤 Endpoint: /api/admins/' + adminId + '/permissions/direct/assignable/query');
        console.log('📦 Payload:', JSON.stringify(params, null, 2));

        assignableParams = params;

        // No claim/release here — the target is claimed for the modal's
        // entire lifetime (openAssignModal → closeAssignModal).

        if (typeof createTable !== 'function') {
            console.error('❌ createTable not found — data_table.js not loaded');
            return;
        }

        const aHeaders  = ['ID', 'Name', 'Group', 'Display Name', 'Assigned', 'Type', 'Expires At', 'Actions'];
        const aRowKeys  = ['id', 'name', 'group', 'display_name', 'assigned', 'is_allowed', 'expires_at', 'actions'];
        const aRenderers = {
            id:           idRenderer,
            name:         nameRenderer,
            group:        groupRenderer,
            display_name: displayNameRenderer,
            assigned:     assignedRenderer,
            is_allowed:   assignableIsAllowedRenderer,
            expires_at:   assignableExpiresAtRenderer,
            actions:      assignableActionsRenderer
        };

        try {
            const result = await createTable(
                `admins/${adminId}/permissions/direct/assignable/query`,
                params,
                aHeaders,
                aRowKeys,
                false,
                'id',
                null,
                aRenderers,
                null,
                getAssignablePaginationInfo
            );

            if (result && result.success) {
                console.log('✅ [Assignable] Loaded:', result.data.length, 'rows');
                console.log('📊 Pagination:', result.pagination);
            } else {
                console.error('❌ [Assignable] Load failed', result);
            }
        } catch (error) {
            console.error('❌ [Assignable] Exception:', error);
            showAssignModalMessage('Failed to load assignable permissions.', 'error');
        }
    }

    // ====================================================================
    // Assignable Modal — Search (1000ms debounce) + Filter
    // ====================================================================
    if (assignableGlobalSearch) {
        assignableGlobalSearch.addEventListener('keyup', () => {
            clearTimeout(assignableGlobalSearch._debounce);
            assignableGlobalSearch._debounce = setTimeout(() => {
                assignableGlobalValue = assignableGlobalSearch.value.trim();
                console.log('🔍 [Assignable] Global search:', assignableGlobalValue);
                loadAssignable(1);
            }, 1000);
        });

        assignableGlobalSearch.addEventListener('input', (e) => {
            if (e.target.value.trim().length > 0) {
                assignableGlobalSearch.classList.add('border-blue-300', 'bg-blue-50');
            } else {
                assignableGlobalSearch.classList.remove('border-blue-300', 'bg-blue-50');
            }
        });
    }

    if (assignableFilterAssigned) {
        assignableFilterAssigned.addEventListener('change', () => {
            assignableAssignedValue = assignableFilterAssigned.value;
            console.log('🏷️  [Assignable] Assigned filter:', assignableAssignedValue);
            loadAssignable(1);
        });
    }

    if (assignableBtnReset) {
        assignableBtnReset.addEventListener('click', () => {
            console.log('🔄 [Assignable] Resetting filters');
            assignableGlobalValue   = '';
            assignableAssignedValue = '';
            if (assignableGlobalSearch)   {
                assignableGlobalSearch.value = '';
                assignableGlobalSearch.classList.remove('border-blue-300', 'bg-blue-50');
            }
            if (assignableFilterAssigned) assignableFilterAssigned.value = '';
            loadAssignable(1);
        });
    }

    // ====================================================================
    // Assignable Modal — Inline Form Helpers
    // ====================================================================

    /** Collapse any currently open inline form in the assignable table */
    function collapseAllInlineForms() {
        document.querySelectorAll('.assignable-inline-form').forEach(form => form.remove());
    }

    /**
     * Render the inline assign/edit sub-form into a new row after the clicked row.
     * @param {HTMLElement} btn        – the clicked Assign or Edit button
     * @param {number}      permId     – permission_id
     * @param {boolean}     preAllowed – pre-selected is_allowed (default true for new assign)
     * @param {string}      preExpires – pre-filled expires_at in "Y-m-d H:i:s" (empty for new assign)
     */
    function showInlineForm(btn, permId, preAllowed = true, preExpires = '') {
        collapseAllInlineForms();

        const row = btn.closest('tr');
        if (!row) return;

        // Convert "Y-m-d H:i:s" → "YYYY-MM-DDTHH:MM" for datetime-local input
        let preExpiresLocal = '';
        if (preExpires) {
            preExpiresLocal = preExpires.replace(' ', 'T').substring(0, 16);
        }

        const formHtml = `
            <tr class="assignable-inline-form bg-blue-50 border-t border-blue-200">
                <td colspan="8" class="px-4 py-3">
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Permission Type</label>
                            <div class="flex gap-2">
                                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 border rounded-md cursor-pointer transition-colors duration-200
                                    ${preAllowed ? 'border-green-400 bg-green-50' : 'border-gray-300'}
                                    inline-assign-type" data-type="allow">
                                    <input type="radio" name="inline-assign-type-${permId}" value="allow" class="sr-only" ${preAllowed ? 'checked' : ''}>
                                    <span class="text-xs font-medium ${preAllowed ? 'text-green-700' : 'text-gray-600'}">Allow</span>
                                </label>
                                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 border rounded-md cursor-pointer transition-colors duration-200
                                    ${!preAllowed ? 'border-red-400 bg-red-50' : 'border-gray-300'}
                                    inline-assign-type" data-type="deny">
                                    <input type="radio" name="inline-assign-type-${permId}" value="deny" class="sr-only" ${!preAllowed ? 'checked' : ''}>
                                    <span class="text-xs font-medium ${!preAllowed ? 'text-red-700' : 'text-gray-600'}">Deny</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Expiration <span class="text-gray-400 font-normal">(Optional)</span></label>
                            <input type="datetime-local" class="inline-assign-expires px-3 py-1.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   value="${preExpiresLocal}">
                        </div>
                        <button type="button" class="inline-assign-save-btn text-xs px-4 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                data-permission-id="${permId}">
                            Save
                        </button>
                        <button type="button" class="inline-assign-cancel-btn text-xs px-3 py-1.5 bg-white text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200">
                            Cancel
                        </button>
                    </div>
                </td>
            </tr>
        `;

        row.insertAdjacentHTML('afterend', formHtml);

        // Wire up Allow / Deny label toggle styling
        const formRow    = row.nextElementSibling;
        const typeLabels = formRow.querySelectorAll('.inline-assign-type');
        typeLabels.forEach(label => {
            label.addEventListener('click', () => {
                const type  = label.dataset.type;
                const radio = label.querySelector('input[type=radio]');
                if (radio) radio.checked = true;

                typeLabels.forEach(l => {
                    const isSelected = l.dataset.type === type;
                    const isAllow    = l.dataset.type === 'allow';
                    l.classList.remove('border-green-400', 'bg-green-50', 'border-red-400', 'bg-red-50', 'border-gray-300');
                    if (isSelected) {
                        l.classList.add(isAllow ? 'border-green-400' : 'border-red-400');
                        l.classList.add(isAllow ? 'bg-green-50'      : 'bg-red-50');
                    } else {
                        l.classList.add('border-gray-300');
                    }
                    const span = l.querySelector('span');
                    if (span) {
                        span.className = `text-xs font-medium ${isSelected ? (isAllow ? 'text-green-700' : 'text-red-700') : 'text-gray-600'}`;
                    }
                });
            });
        });
    }

    // ====================================================================
    // Assignable Modal — Inline Save (POST assign)
    // ====================================================================
    async function handleInlineSave(saveBtn) {
        const permId  = Number(saveBtn.dataset.permissionId);
        const formRow = saveBtn.closest('.assignable-inline-form');
        if (!formRow) return;

        // Read selected type
        const checkedRadio = formRow.querySelector(`input[name="inline-assign-type-${permId}"]:checked`);
        const isAllowed    = checkedRadio ? checkedRadio.value === 'allow' : true;

        // Read expiration
        const expiresInput = formRow.querySelector('.inline-assign-expires');
        const expiresRaw   = expiresInput ? expiresInput.value : '';

        let formattedExpires = null;
        if (expiresRaw) {
            const dt  = new Date(expiresRaw);
            const pad = (n) => String(n).padStart(2, '0');
            formattedExpires = `${dt.getFullYear()}-${pad(dt.getMonth()+1)}-${pad(dt.getDate())} ${pad(dt.getHours())}:${pad(dt.getMinutes())}:${pad(dt.getSeconds())}`;
        }

        const body = { permission_id: permId, is_allowed: isAllowed };
        if (formattedExpires) body.expires_at = formattedExpires;

        console.log('💾 [Assignable] Saving permission:', JSON.stringify(body));

        // Disable save + show spinner
        saveBtn.disabled = true;
        saveBtn.innerHTML = `
            <svg class="animate-spin w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        `;

        try {
            const response = await fetch(`/api/admins/${adminId}/permissions/direct/assign`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(body)
            });

            console.log('📥 [Assignable] Assign response — status:', response.status);

            // Step-Up 2FA
            if (response.status === 403) {
                const data = await response.json().catch(() => null);
                if (data && data.code === 'STEP_UP_REQUIRED') {
                    const scope    = encodeURIComponent(data.scope || 'admin.permissions.direct.assign');
                    const returnTo = encodeURIComponent(window.location.pathname);
                    window.location.href = `/2fa/verify?scope=${scope}&return_to=${returnTo}`;
                    return;
                }
            }

            if (response.ok || response.status === 204) {
                console.log('✅ [Assignable] Permission assigned — refreshing');
                showAlert('s', 'Permission assigned successfully');

                // Refresh assignable table; mark dirty so direct reloads on modal close
                formRow.remove();
                await loadAssignableWithParams(assignableParams);
                assignModalDirty = true;
            } else {
                const data = await response.json().catch(() => null);
                console.error('❌ [Assignable] Assign failed:', data);
                showAssignModalMessage(data?.message || 'Failed to assign permission.', 'error');
                saveBtn.disabled  = false;
                saveBtn.textContent = 'Save';
            }
        } catch (err) {
            console.error('❌ [Assignable] Network error:', err);
            showAssignModalMessage('Network error. Please try again.', 'error');
            saveBtn.disabled  = false;
            saveBtn.textContent = 'Save';
        }
    }

    // ====================================================================
    // Assignable Modal — Inline Revoke
    // ====================================================================
    async function handleAssignableRevoke(btn) {
        const permId = Number(btn.dataset.permissionId);
        console.log('🗑️  [Assignable] Revoke clicked — permission_id:', permId);

        if (!confirm('Revoke this direct permission? This action cannot be undone.')) return;

        btn.disabled = true;
        btn.classList.add('opacity-50');

        try {
            const response = await fetch(`/api/admins/${adminId}/permissions/direct/revoke`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ permission_id: permId })
            });

            console.log('📥 [Assignable] Revoke response — status:', response.status);

            // Step-Up 2FA
            if (response.status === 403) {
                const data = await response.json().catch(() => null);
                if (data && data.code === 'STEP_UP_REQUIRED') {
                    const scope    = encodeURIComponent(data.scope || 'admin.permissions.direct.revoke');
                    const returnTo = encodeURIComponent(window.location.pathname);
                    window.location.href = `/2fa/verify?scope=${scope}&return_to=${returnTo}`;
                    return;
                }
            }

            if (response.ok || response.status === 204) {
                console.log('✅ [Assignable] Permission revoked — refreshing');
                showAlert('s', 'Direct permission revoked successfully');

                // Refresh assignable table; mark dirty so direct reloads on modal close
                await loadAssignableWithParams(assignableParams);
                assignModalDirty = true;
            } else {
                const data = await response.json().catch(() => null);
                console.error('❌ [Assignable] Revoke failed:', data);
                showAssignModalMessage(data?.message || 'Failed to revoke permission.', 'error');
                btn.disabled = false;
                btn.classList.remove('opacity-50');
            }
        } catch (err) {
            console.error('❌ [Assignable] Network error:', err);
            showAssignModalMessage('Network error. Please try again.', 'error');
            btn.disabled = false;
            btn.classList.remove('opacity-50');
        }
    }

    // ====================================================================
    // Assignable Modal — Message Helpers
    // ====================================================================
    function showAssignModalMessage(message, type = 'error') {
        if (!assignModalMessage) return;

        assignModalMessage.className = 'mb-4 p-4 rounded-lg flex items-start gap-3';

        const styles = {
            error:   { bg: 'bg-red-50 border border-red-200',     icon: 'text-red-600',   text: 'text-red-800',   path: 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z' },
            success: { bg: 'bg-green-50 border border-green-200', icon: 'text-green-600', text: 'text-green-800', path: 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' },
            info:    { bg: 'bg-blue-50 border border-blue-200',   icon: 'text-blue-600',  text: 'text-blue-800',  path: 'm11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z' }
        };
        const s = styles[type] || styles.error;

        assignModalMessage.classList.add(...s.bg.split(' '));
        assignModalMessage.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 ${s.icon} flex-shrink-0 mt-0.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="${s.path}" />
            </svg>
            <p class="text-sm ${s.text}">${message}</p>
        `;
        assignModalMessage.classList.remove('hidden');
    }

    function hideAssignModalMessage() {
        if (assignModalMessage) {
            assignModalMessage.classList.add('hidden');
            assignModalMessage.innerHTML = '';
        }
    }

    // ====================================================================
    // Event Listeners — Direct Tab
    // ====================================================================

    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            console.log('🔍 [Direct] Search form submitted');
            loadDirect();
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            console.log('🔄 [Direct] Resetting filters');
            if (inputId)      inputId.value      = '';
            if (inputName)    inputName.value    = '';
            if (inputGroup)   inputGroup.value   = '';
            if (inputAllowed) inputAllowed.value = '';
            currentGlobalSearch  = '';
            currentAllowedFilter = 'all';
            loadDirect();
        });
    }

    // Assign button → opens assignable modal
    if (btnAssignDirect && canAssign) {
        btnAssignDirect.addEventListener('click', openAssignModal);
    }

    // Modal close
    if (closeAssignModalBtn) closeAssignModalBtn.addEventListener('click', closeAssignModal);

    if (assignModal) {
        assignModal.addEventListener('click', (e) => {
            if (e.target === assignModal) closeAssignModal();
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && assignModal && !assignModal.classList.contains('hidden')) {
            closeAssignModal();
        }
    });

    // ====================================================================
    // Delegated Click Handlers — All Row Actions
    // ====================================================================
    document.addEventListener('click', (e) => {
        // Direct tab — Revoke
        const directRevokeBtn = e.target.closest('.direct-revoke-btn');
        if (directRevokeBtn && !directRevokeBtn.disabled) {
            handleRevoke(directRevokeBtn);
            return;
        }

        // Assignable modal — Assign → open inline form (new)
        const assignableAssignBtn = e.target.closest('.assignable-assign-btn');
        if (assignableAssignBtn && !assignableAssignBtn.disabled) {
            console.log('➕ [Assignable] Assign clicked — permission_id:', assignableAssignBtn.dataset.permissionId);
            showInlineForm(assignableAssignBtn, assignableAssignBtn.dataset.permissionId, true, '');
            return;
        }

        // Assignable modal — Edit → open inline form (pre-filled)
        const assignableEditBtn = e.target.closest('.assignable-edit-btn');
        if (assignableEditBtn && !assignableEditBtn.disabled) {
            const preAllowed = assignableEditBtn.dataset.currentAllowed === '1';
            const preExpires = assignableEditBtn.dataset.currentExpires || '';
            console.log('✏️  [Assignable] Edit clicked — permission_id:', assignableEditBtn.dataset.permissionId, 'allowed:', preAllowed, 'expires:', preExpires);
            showInlineForm(assignableEditBtn, assignableEditBtn.dataset.permissionId, preAllowed, preExpires);
            return;
        }

        // Assignable modal — Revoke
        const assignableRevokeBtn = e.target.closest('.assignable-revoke-btn');
        if (assignableRevokeBtn && !assignableRevokeBtn.disabled) {
            handleAssignableRevoke(assignableRevokeBtn);
            return;
        }

        // Assignable modal — Inline Save
        const inlineSaveBtn = e.target.closest('.inline-assign-save-btn');
        if (inlineSaveBtn && !inlineSaveBtn.disabled) {
            handleInlineSave(inlineSaveBtn);
            return;
        }

        // Assignable modal — Inline Cancel
        const inlineCancelBtn = e.target.closest('.inline-assign-cancel-btn');
        if (inlineCancelBtn) {
            const formRow = inlineCancelBtn.closest('.assignable-inline-form');
            if (formRow) formRow.remove();
            return;
        }
    });

    // ====================================================================
    // tableAction — Routes to correct table based on modal state
    // ====================================================================
    document.addEventListener('tableAction', async (e) => {
        const panel = document.getElementById('tab-direct');
        if (!panel || panel.classList.contains('hidden')) return;

        const { action, value, currentParams: tableParams } = e.detail;

        // If modal is open → tableAction belongs to assignable table
        if (assignModal && !assignModal.classList.contains('hidden')) {
            console.log('📨 [Assignable] tableAction:', action, value);

            let newParams = JSON.parse(JSON.stringify(tableParams));
            switch (action) {
                case 'pageChange':    newParams.page     = value; break;
                case 'perPageChange': newParams.per_page = value; newParams.page = 1; break;
            }

            if (newParams.search) {
                if (!newParams.search.global?.trim()) delete newParams.search.global;
                if (newParams.search.columns) {
                    Object.keys(newParams.search.columns).forEach(key => {
                        if (!newParams.search.columns[key]?.toString().trim()) delete newParams.search.columns[key];
                    });
                    if (Object.keys(newParams.search.columns).length === 0) delete newParams.search.columns;
                }
                if (Object.keys(newParams.search).length === 0) delete newParams.search;
            }

            await loadAssignableWithParams(newParams);
            return;
        }

        // Modal closed → tableAction belongs to direct tab table
        console.log('📨 [Direct] tableAction:', action, value);

        let newParams = JSON.parse(JSON.stringify(tableParams));
        switch (action) {
            case 'pageChange':    newParams.page     = value; break;
            case 'perPageChange': newParams.per_page = value; newParams.page = 1; break;
        }

        if (newParams.search) {
            if (!newParams.search.global?.trim()) delete newParams.search.global;
            if (newParams.search.columns) {
                Object.keys(newParams.search.columns).forEach(key => {
                    if (!newParams.search.columns[key]?.toString().trim()) delete newParams.search.columns[key];
                });
                if (Object.keys(newParams.search.columns).length === 0) delete newParams.search.columns;
            }
            if (Object.keys(newParams.search).length === 0) delete newParams.search;
        }

        await loadDirectWithParams(newParams);
    });

    // ====================================================================
    // Listen for tab activation
    // ====================================================================
    document.addEventListener('adminPermTabLoaded', (e) => {
        if (e.detail.tab === 'direct') {
            console.log('📢 [Direct] Tab activated — loading data');
            loadDirect();
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
    window.AdminPermissionsDirect = {
        loadDirect,
        loadDirectWithParams
    };

    console.log('✅ Admin Permissions Direct — Ready');
    console.log('─'.repeat(60));
})();
