/**
 * Admin Permissions — Effective Permissions Module
 * Read-only snapshot of final RBAC resolution for this admin.
 *
 * API endpoint:
 *   POST /api/admins/{adminId}/permissions/effective
 *
 * Capabilities consumed (from window.adminPermissionsCapabilities):
 *   can_view_permissions_effective — tab rendered at all (Twig gate)
 *
 * Columns returned by API:
 *   id, name, group, display_name, description, source, is_allowed, role_name, expires_at
 */

(function () {
    'use strict';

    console.log('🔑 Admin Permissions Effective — Initializing');
    console.log('─'.repeat(60));

    const capabilities = window.adminPermissionsCapabilities || {};
    const adminId      = window.adminPermissionsAdminId;

    // Guard — if the tab wasn't rendered, exit silently
    if (!capabilities.can_view_permissions_effective) {
        console.log('⛔ Effective tab not available — exiting module');
        return;
    }

    // ====================================================================
    // DOM References
    // ====================================================================
    const container   = document.getElementById('effective-table-container');
    const searchForm  = document.getElementById('effective-search-form');
    const resetBtn    = document.getElementById('eff-btn-reset');
    const inputId     = document.getElementById('eff-filter-id');
    const inputName   = document.getElementById('eff-filter-name');
    const inputGroup  = document.getElementById('eff-filter-group');

    // ====================================================================
    // State
    // ====================================================================
    let currentParams       = {};
    let currentGlobalSearch = '';

    // ====================================================================
    // Custom Renderers
    // ====================================================================

    /** ID column */
    const idRenderer = (value) => {
        if (!value && value !== 0) return '<span class="text-gray-400 italic">N/A</span>';
        return `<span class="font-mono text-sm text-gray-800 font-medium">#${value}</span>`;
    };

    /** Name column — code badge */
    const nameRenderer = (value) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';
        return `<code class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-mono border border-gray-200">${value}</code>`;
    };

    /** Group column — colored pill */
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

    /** Display Name */
    const displayNameRenderer = (value) => {
        if (!value || !value.trim()) return '<span class="text-gray-400 italic text-xs">Not set</span>';
        return `<span class="text-sm text-gray-800">${value}</span>`;
    };

    /** Description — truncated at 55 chars */
    const descriptionRenderer = (value) => {
        if (!value || !value.trim()) return '<span class="text-gray-400 italic text-xs">No description</span>';
        if (value.length > 55) {
            return `<span class="text-sm text-gray-600" title="${value}">${value.substring(0, 55)}…</span>`;
        }
        return `<span class="text-sm text-gray-600">${value}</span>`;
    };

    /**
     * Source column — badge showing where the permission came from.
     * Also encodes is_allowed as Allow / Deny indicator.
     *
     * source values: "role" | "direct_allow" | "direct_deny"
     */
    const sourceRenderer = (value, row) => {
        const isAllowed = row.is_allowed === true || row.is_allowed === 1 || row.is_allowed === '1';
        const source    = (value || '').toLowerCase();

        // Determine badge style based on source + allowed state
        let label, cls;

        if (source === 'direct_allow') {
            label = 'Direct Allow';
            cls   = 'bg-green-100 text-green-800 border-green-200';
        } else if (source === 'direct_deny') {
            label = 'Direct Deny';
            cls   = 'bg-red-100 text-red-800 border-red-200';
        } else if (source === 'role') {
            label = isAllowed ? 'Role' : 'Role (Denied)';
            cls   = isAllowed
                ? 'bg-blue-100 text-blue-800 border-blue-200'
                : 'bg-red-100 text-red-800 border-red-200';
        } else {
            label = value || 'Unknown';
            cls   = 'bg-gray-100 text-gray-800 border-gray-200';
        }

        // Append role_name if source is "role"
        const roleName = row.role_name
            ? `<span class="text-xs text-gray-500 ml-1.5">(${row.role_name})</span>`
            : '';

        return `<span class="${cls} px-3 py-1 rounded-full text-xs font-medium border">${label}</span>${roleName}`;
    };

    /** Expires At — formatted or "Never" */
    const expiresAtRenderer = (value) => {
        if (!value) return '<span class="text-gray-400 text-xs">Never</span>';

        const date = new Date(value.replace(' ', 'T'));
        const now  = new Date();
        const formatted = date.toLocaleString();

        // Highlight if already expired (shouldn't appear per API contract, but defensive)
        if (date < now) {
            return `<span class="text-red-600 text-xs font-medium">${formatted} <span class="text-red-400">(expired)</span></span>`;
        }
        return `<span class="text-sm text-gray-600">${formatted}</span>`;
    };

    // ====================================================================
    // Table Config
    // ====================================================================
    const headers  = ['ID', 'Name', 'Group', 'Display Name', 'Description', 'Source', 'Expires At'];
    const rowKeys  = ['id', 'name', 'group', 'display_name', 'description', 'source', 'expires_at'];

    const renderers = {
        id:           idRenderer,
        name:         nameRenderer,
        group:        groupRenderer,
        display_name: displayNameRenderer,
        description:  descriptionRenderer,
        source:       sourceRenderer,
        expires_at:   expiresAtRenderer
    };

    // ====================================================================
    // Params Builder
    // ====================================================================
    function buildParams(page = 1, perPage = 25) {
        console.log('📦 [Effective] Building params');

        const params  = { page, per_page: perPage };
        const columns = {};

        if (inputId    && inputId.value.trim())    columns.id    = inputId.value.trim();
        if (inputName  && inputName.value.trim())  columns.name  = inputName.value.trim();
        if (inputGroup && inputGroup.value.trim()) columns.group = inputGroup.value.trim();

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
    function getEffectivePaginationInfo(pagination, params) {
        console.log('🎯 [Effective] getEffectivePaginationInfo:', pagination);

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
    // Load Effective Permissions
    // ====================================================================
    async function loadEffective(page = 1) {
        const params = buildParams(page, 25);
        await loadEffectiveWithParams(params);
    }

    async function loadEffectiveWithParams(params) {
        console.log('─'.repeat(60));
        console.log('🚀 [Effective] API Request');
        console.log('📤 Endpoint: /api/admins/' + adminId + '/permissions/effective');
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
                `admins/${adminId}/permissions/effective`,
                params,
                headers,
                rowKeys,
                false,          // no bulk selection (read-only)
                'id',
                null,
                renderers,
                null,           // no selectableIds
                getEffectivePaginationInfo
            );

            releaseTableTarget();

            if (result && result.success) {
                console.log('✅ [Effective] Loaded:', result.data.length, 'rows');
                console.log('📊 Pagination:', result.pagination);
                setupTableFiltersAfterRender();
            } else {
                console.error('❌ [Effective] Load failed', result);
            }
        } catch (error) {
            releaseTableTarget();
            console.error('❌ [Effective] Exception:', error);
            showAlert('d', 'Failed to load effective permissions');
        }
    }

    // ====================================================================
    // Table Target Swap
    // data_table.js renders into #table-container.
    // We temporarily rename our container to claim that id.
    // ====================================================================
    const OWN_ID = 'effective-table-container';

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
    // Table Filters — Global Search (injected into table-custom-filters)
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
                    <input id="eff-global-search"
                        class="w-full border rounded-lg px-3 py-1 text-sm transition-colors duration-200"
                        placeholder="Search by name, group, or description..."
                        value="${currentGlobalSearch}" />
                </div>
            </div>
        `;

        const globalSearch = document.getElementById('eff-global-search');
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
    }

    function handleGlobalSearch(searchValue) {
        console.log('🔍 [Effective] Global search:', searchValue);
        currentGlobalSearch = searchValue;

        const params = buildParams(1, 25);
        if (searchValue) {
            if (!params.search) params.search = {};
            params.search.global = searchValue;
        }

        loadEffectiveWithParams(params);
    }

    // ====================================================================
    // Event Listeners
    // ====================================================================

    // Search form submit
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            console.log('🔍 [Effective] Search form submitted');
            loadEffective();
        });
    }

    // Reset button
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            console.log('🔄 [Effective] Resetting filters');
            if (inputId)    inputId.value    = '';
            if (inputName)  inputName.value  = '';
            if (inputGroup) inputGroup.value = '';
            currentGlobalSearch = '';
            loadEffective();
        });
    }

    // tableAction events (pagination / per-page) — only while this tab is visible
    document.addEventListener('tableAction', async (e) => {
        const panel = document.getElementById('tab-effective');
        if (!panel || panel.classList.contains('hidden')) return;

        const { action, value, currentParams: tableParams } = e.detail;
        console.log('📨 [Effective] tableAction:', action, value);

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

        await loadEffectiveWithParams(newParams);
    });

    // ====================================================================
    // Listen for tab activation (lazy load on first switch)
    // ====================================================================
    document.addEventListener('adminPermTabLoaded', (e) => {
        if (e.detail.tab === 'effective') {
            console.log('📢 [Effective] Tab activated — loading data');
            loadEffective();
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
    window.AdminPermissionsEffective = {
        loadEffective,
        loadEffectiveWithParams
    };

    console.log('✅ Admin Permissions Effective — Ready');
    console.log('─'.repeat(60));
})();
