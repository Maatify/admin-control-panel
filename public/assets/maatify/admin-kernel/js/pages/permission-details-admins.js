/**
 * Permission Details — Admin Overrides Module
 * Owns the Admin Overrides tab: search form, table display.
 * Shows admins who have direct allow/deny overrides for this permission.
 *
 * API endpoint used:
 *   POST /api/permissions/{permissionId}/admins/query
 *
 * Capabilities consumed (from window.permissionDetailsCapabilities):
 *   can_view_admins_tab     — tab is rendered at all (Twig gate)
 *   can_view_admin_profile  — admin ID/name becomes a clickable link
 */

(function () {
    'use strict';

    console.log('👤 Permission Details Admins — Initializing');
    console.log('═'.repeat(60));

    const capabilities = window.permissionDetailsCapabilities || {};
    const permissionId = window.permissionDetailsId;

    console.log('📋 Admins capabilities:');
    console.log('  ├─ can_view_admins_tab:    ', capabilities.can_view_admins_tab    ? '✅ YES' : '❌ NO');
    console.log('  └─ can_view_admin_profile: ', capabilities.can_view_admin_profile ? '✅ YES' : '❌ NO');

    // Guard — if the tab wasn't rendered, exit silently
    if (!capabilities.can_view_admins_tab) {
        console.log('⛔ Admins tab not available — exiting module');
        return;
    }

    // ====================================================================
    // DOM References
    // ====================================================================
    const container      = document.getElementById('admins-table-container');
    const searchForm     = document.getElementById('admins-search-form');
    const resetBtn       = document.getElementById('admin-btn-reset');
    const inputId        = document.getElementById('admin-filter-id');
    const inputOverride  = document.getElementById('admin-filter-override');

    // ====================================================================
    // State
    // ====================================================================
    let currentParams = {};
    let debounceTimer = null;

    // ====================================================================
    // Custom Renderers
    // ====================================================================

    /** Admin ID column — plain mono badge with optional link */
    const adminIdRenderer = (value, row) => {
        if (!value && value !== 0) return '<span class="text-gray-400 dark:text-gray-500 italic">N/A</span>';

        const idText = `<span class="font-mono text-sm text-gray-800 dark:text-gray-300 font-medium">#${value}</span>`;

        if (capabilities.can_view_admin_profile) {
            return `<a href="/admins/${value}/profile" 
                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline"
                       title="View admin profile">
                       ${idText}
                    </a>`;
        }

        return idText;
    };

    /**
     * Admin Display Name column
     * If can_view_admin_profile → clickable link to /admins/{id}/profile
     * Otherwise plain text
     */
    const displayNameRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 dark:text-gray-500 italic">N/A</span>';

        if (capabilities.can_view_admin_profile && row.admin_id) {
            return `<a href="/admins/${row.admin_id}/profile" 
                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline font-medium text-sm"
                       title="View admin profile">
                       ${value}
                    </a>`;
        }

        return `<span class="text-sm text-gray-800 dark:text-gray-200 font-medium">${value}</span>`;
    };

    /**
     * Override Type column — Allow or Deny badge
     */
    const overrideTypeRenderer = (value) => {
        const isAllowed = value === true || value === 1 || value === '1';

        const cls = isAllowed ? 'bg-green-600' : 'bg-red-600';
        const text = isAllowed ? 'ALLOW' : 'DENY';

        return `<span class="${cls} text-white px-3 py-1 rounded-lg text-xs font-medium uppercase tracking-wide">${text}</span>`;
    };

    /**
     * Expires At column
     */
    const expiresAtRenderer = (value) => {
        if (!value) return '<span class="text-gray-500 dark:text-gray-400 text-sm">Never</span>';

        // Parse the date and format it nicely
        try {
            const date = new Date(value);
            const now = new Date();
            const isExpired = date < now;

            const formatted = date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            if (isExpired) {
                return `<span class="text-red-600 dark:text-red-400 text-sm font-medium" title="Expired">${formatted} ⚠️</span>`;
            }

            return `<span class="text-gray-700 dark:text-gray-300 text-sm">${formatted}</span>`;
        } catch (e) {
            return `<span class="text-gray-500 text-sm">${value}</span>`;
        }
    };

    /**
     * Granted At column
     */
    const grantedAtRenderer = (value) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';

        try {
            const date = new Date(value);
            const formatted = date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            return `<span class="text-gray-600 dark:text-gray-400 text-sm">${formatted}</span>`;
        } catch (e) {
            return `<span class="text-gray-500 text-sm">${value}</span>`;
        }
    };

    // ====================================================================
    // Table Config
    // ====================================================================
    const headers  = ['Admin ID', 'Display Name', 'Override Type', 'Granted At', 'Expires At'];
    const rowKeys  = ['admin_id', 'admin_display_name', 'is_allowed', 'granted_at', 'expires_at'];

    const renderers = {
        admin_id:            adminIdRenderer,
        admin_display_name:  displayNameRenderer,
        is_allowed:          overrideTypeRenderer,
        granted_at:          grantedAtRenderer,
        expires_at:          expiresAtRenderer
    };

    // ====================================================================
    // Params Builder
    // ====================================================================
    function buildParams(page = 1, perPage = 20) {
        console.log('📦 [Admins] Building params');

        const params = { page, per_page: perPage };
        const columns = {};

        if (inputId && inputId.value.trim()) {
            columns.admin_id = inputId.value.trim();
        }

        if (inputOverride && inputOverride.value !== '') {
            columns.is_allowed = inputOverride.value;
        }

        if (Object.keys(columns).length > 0) {
            params.search = { columns };
        }

        // Clean up empty search
        if (params.search && Object.keys(params.search.columns || {}).length === 0) {
            delete params.search;
        }

        console.log('📤 [Admins] Final params:', JSON.stringify(params, null, 2));
        return params;
    }

    // ====================================================================
    // Pagination Info Callback
    // ====================================================================
    function getAdminsPaginationInfo(pagination, params) {
        console.log('🎯 getAdminsPaginationInfo called with:', pagination);

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
        const ourContainer = document.getElementById('admins-table-container');

        if (!ourContainer) {
            console.error('❌ [Admins] Container not found: admins-table-container');
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
            ourContainer.id = 'admins-table-container';

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
    // Load Admins
    // ====================================================================
    async function loadAdmins(params = null) {
        if (!params) {
            params = buildParams(1, 20);
        }

        currentParams = params;
        console.log('🚀 [Admins] Loading with params:', JSON.stringify(params, null, 2));

        if (typeof createTable !== 'function') {
            console.error('❌ createTable function not found');
            return;
        }

        try {
            const result = await withContainer(async () => {
                return await createTable(
                    `permissions/${permissionId}/admins/query`,
                    params,
                    headers,
                    rowKeys,
                    false,  // no checkboxes
                    null,   // no idKey
                    null,   // no selectionCallback
                    renderers,
                    null,   // selectableIds
                    getAdminsPaginationInfo
                );
            });

            if (result && result.success) {
                console.log('✅ [Admins] Loaded:', result.data.length, 'admins with overrides');
                console.log('📊 [Admins] Pagination:', result.pagination);
            }
        } catch (error) {
            console.error('❌ [Admins] Error:', error);
            showAlert('d', 'Failed to load admin overrides');
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
                loadAdmins();
            });
        }

        // Reset button
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (inputId)       inputId.value = '';
                if (inputOverride) inputOverride.value = '';
                loadAdmins();
            });
        }

        // Debounced input change for Admin ID
        if (inputId) {
            inputId.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    loadAdmins();
                }, 1000); // 1 second debounce
            });
        }

        // Immediate change for select dropdown
        if (inputOverride) {
            inputOverride.addEventListener('change', () => {
                loadAdmins();
            });
        }

        // Table pagination events
        document.addEventListener('tableAction', async (e) => {
            const { action, value, currentParams: tableParams } = e.detail;

            console.log('🔨 [Admins] Table event received:', action, value);

            // Check if this event is for our table by checking if event originated from our container
            const eventTarget = e.target;
            if (!container || !container.contains(eventTarget)) {
                console.log('⏭️ [Admins] Event not for this table, skipping');
                return;
            }

            console.log('✅ [Admins] Event is for our table, processing');

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

            await loadAdmins(newParams);
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
        if (e.detail.tab === 'admins') {
            console.log('🎬 [Admins] Tab activated — loading data');
            loadAdmins();
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

    console.log('✅ Permission Details Admins — Ready');
})();
