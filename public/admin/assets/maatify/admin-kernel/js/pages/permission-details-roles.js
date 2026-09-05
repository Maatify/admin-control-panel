/**
 * Permission Details — Roles Module
 * Owns the Roles tab: search form, table display.
 * Shows roles that use this permission.
 *
 * API endpoint used:
 *   POST /api/permissions/{permissionId}/roles/query
 *
 * Capabilities consumed (from window.permissionDetailsCapabilities):
 *   can_view_roles_tab      — tab is rendered at all (Twig gate)
 *   can_view_role_details   — role ID/name becomes a clickable link
 */

(function () {
    'use strict';

    console.log('👥 Permission Details Roles — Initializing');
    console.log('═'.repeat(60));

    const capabilities = window.permissionDetailsCapabilities || {};
    const permissionId = window.permissionDetailsId;

    console.log('📋 Roles capabilities:');
    console.log('  ├─ can_view_roles_tab:     ', capabilities.can_view_roles_tab     ? '✅ YES' : '❌ NO');
    console.log('  └─ can_view_role_details:  ', capabilities.can_view_role_details  ? '✅ YES' : '❌ NO');

    // Guard — if the tab wasn't rendered, exit silently
    if (!capabilities.can_view_roles_tab) {
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

    // ====================================================================
    // State
    // ====================================================================
    let currentParams = {};
    let debounceTimer = null;

    // ====================================================================
    // Custom Renderers
    // ====================================================================

    /** Role ID column — plain mono badge with optional link */
    const roleIdRenderer = (value, row) => {
        if (!value && value !== 0) return '<span class="text-gray-400 italic">N/A</span>';

        const idText = `<span class="font-mono text-sm text-gray-800 font-medium">#${value}</span>`;

        if (capabilities.can_view_role_details) {
            return `<a href="/roles/${value}" 
                       class="text-blue-600 hover:text-blue-800 hover:underline"
                       title="View role details">
                       ${idText}
                    </a>`;
        }

        return idText;
    };

    /**
     * Role Name column
     * If can_view_role_details → clickable link to /roles/{id}
     * Otherwise plain text with code styling
     */
    const roleNameRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';

        const nameText = `<code class="text-sm font-mono bg-gray-100 border border-gray-200 px-2 py-1 rounded">${value}</code>`;

        if (capabilities.can_view_role_details && row.role_id) {
            return `<a href="/roles/${row.role_id}" 
                       class="hover:opacity-80 transition-opacity"
                       title="View role details">
                       ${nameText}
                    </a>`;
        }

        return nameText;
    };

    /**
     * Display Name column
     */
    const displayNameRenderer = (value) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';
        return `<span class="text-sm text-gray-800">${value}</span>`;
    };

    /**
     * Group column (derived from role name prefix)
     */
    const groupRenderer = (value) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';
        return `<span class="text-sm text-gray-600 font-medium">${value}</span>`;
    };

    /**
     * Status column — color-coded badge
     */
    const statusRenderer = (value) => {
        const isActive = value === true || value === 1 || value === '1';

        const cls = isActive ? 'bg-green-600' : 'bg-red-600';
        const text = isActive ? 'ACTIVE' : 'INACTIVE';

        return `<span class="${cls} text-white px-3 py-1 rounded-lg text-xs font-medium uppercase tracking-wide">${text}</span>`;
    };

    // ====================================================================
    // Table Config
    // ====================================================================
    const headers  = ['Role ID', 'Role Name', 'Display Name', 'Group', 'Status'];
    const rowKeys  = ['role_id', 'role_name', 'display_name', 'group', 'is_active'];

    const renderers = {
        role_id:      roleIdRenderer,
        role_name:    roleNameRenderer,
        display_name: displayNameRenderer,
        group:        groupRenderer,
        is_active:    statusRenderer
    };

    // ====================================================================
    // Params Builder
    // ====================================================================
    function buildParams(page = 1, perPage = 20) {
        console.log('📦 [Roles] Building params');

        const params = { page, per_page: perPage };
        const columns = {};

        if (inputId    && inputId.value.trim())    columns.id   = inputId.value.trim();
        if (inputName  && inputName.value.trim())  columns.name = inputName.value.trim();
        if (inputGroup && inputGroup.value.trim()) columns.group = inputGroup.value.trim();

        if (Object.keys(columns).length > 0) {
            params.search = { columns };
        }

        // Clean up empty search
        if (params.search && Object.keys(params.search.columns || {}).length === 0) {
            delete params.search;
        }

        console.log('📤 [Roles] Final params:', JSON.stringify(params, null, 2));
        return params;
    }

    // ====================================================================
    // Pagination Info Callback
    // ====================================================================
    function getRolesPaginationInfo(pagination, params) {
        console.log('🎯 getRolesPaginationInfo called with:', pagination);

        const { page = 1, per_page = 10, total = 0, filtered = total } = pagination;

        // Check if we're filtering
        const hasFilter = params.search &&
            (params.search.global ||
                (params.search.columns && Object.keys(params.search.columns).length > 0));
        const isFiltered = hasFilter && filtered !== total;

        console.log('🔍 Filter status - hasFilter:', hasFilter, 'isFiltered:', isFiltered);

        // Calculate based on filtered when applicable
        const displayCount = isFiltered ? filtered : total;
        const startItem = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
        const endItem = Math.min(page * per_page, displayCount);

        // Build info text
        let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;
        if (isFiltered) {
            infoText += ` <span class="text-gray-500 text-xs">(filtered from ${total} total)</span>`;
        }

        console.log('📤 Returning:', { total: displayCount, info: infoText });

        return {
            total: displayCount,
            info: infoText
        };
    }

    // ====================================================================
    // Container Management Helper
    // ====================================================================
    /**
     * Temporarily switches #table-container to our specific container
     * This is needed because data_table.js hardcodes #table-container
     */
    async function withContainer(callback) {
        const ourContainer = document.getElementById('roles-table-container');

        if (!ourContainer) {
            console.error('❌ [Roles] Container not found: roles-table-container');
            return;
        }

        // Store original ID if any element has it
        const existingTableContainer = document.getElementById('table-container');
        const tempId = existingTableContainer ? 'table-container-temp-' + Date.now() : null;

        if (existingTableContainer && existingTableContainer !== ourContainer) {
            existingTableContainer.id = tempId;
        }

        // Temporarily give our container the standard ID
        ourContainer.id = 'table-container';

        try {
            return await callback();
        } finally {
            // Restore original ID
            ourContainer.id = 'roles-table-container';

            // Restore any previous table-container
            if (tempId) {
                const tempElement = document.getElementById(tempId);
                if (tempElement) {
                    tempElement.id = 'table-container';
                }
            }
        }
    }

    // ====================================================================
    // Load Roles
    // ====================================================================
    async function loadRoles(params = null) {
        if (!params) {
            params = buildParams(1, 20);
        }

        currentParams = params;
        console.log('🚀 [Roles] Loading with params:', JSON.stringify(params, null, 2));

        if (typeof createTable !== 'function') {
            console.error('❌ createTable function not found');
            return;
        }

        try {
            const result = await withContainer(async () => {
                return await createTable(
                    `permissions/${permissionId}/roles/query`,
                    params,
                    headers,
                    rowKeys,
                    false,  // no checkboxes
                    null,   // no idKey
                    null,   // no selectionCallback
                    renderers,
                    null,   // selectableIds
                    getRolesPaginationInfo
                );
            });

            if (result && result.success) {
                console.log('✅ [Roles] Loaded:', result.data.length, 'roles');
                console.log('📊 [Roles] Pagination:', result.pagination);
            }
        } catch (error) {
            console.error('❌ [Roles] Error:', error);
            showAlert('d', 'Failed to load roles');
        }
    }

    // ====================================================================
    // Event Listeners
    // ====================================================================
    function setupEventListeners() {
        // Search form submit
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                loadRoles();
            });
        }

        // Reset button
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (inputId)    inputId.value = '';
                if (inputName)  inputName.value = '';
                if (inputGroup) inputGroup.value = '';
                loadRoles();
            });
        }

        // Debounced input changes
        [inputId, inputName, inputGroup].forEach(input => {
            if (input) {
                input.addEventListener('input', () => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        loadRoles();
                    }, 1000); // 1 second debounce
                });
            }
        });

        // Table pagination events
        document.addEventListener('tableAction', async (e) => {
            const { action, value, currentParams: tableParams } = e.detail;

            console.log('🔨 [Roles] Table event received:', action, value);

            // Check if this event is for our table by checking if event originated from our container
            const eventTarget = e.target;
            if (!container || !container.contains(eventTarget)) {
                console.log('⏭️ [Roles] Event not for this table, skipping');
                return;
            }

            console.log('✅ [Roles] Event is for our table, processing');

            let newParams = JSON.parse(JSON.stringify(tableParams));

            switch (action) {
                case 'pageChange':
                    newParams.page = value;
                    break;

                case 'perPageChange':
                    newParams.per_page = value;
                    newParams.page = 1;
                    break;
            }

            await loadRoles(newParams);
        });
    }

    // ====================================================================
    // Initialize - Setup listeners immediately
    // ====================================================================
    setupEventListeners();

    // ====================================================================
    // Tab Load Event
    // ====================================================================
    document.addEventListener('permissionTabLoaded', (e) => {
        if (e.detail.tab === 'roles') {
            console.log('🎬 [Roles] Tab activated — loading data');
            loadRoles();
        }
    });

    // ====================================================================
    // Helper
    // ====================================================================
    function showAlert(type, message) {
        if (typeof window.showAlert === 'function') {
            window.showAlert(type, message);
        } else {
            console.log(`[${type}] ${message}`);
        }
    }

    console.log('✅ Permission Details Roles — Ready');
})();
