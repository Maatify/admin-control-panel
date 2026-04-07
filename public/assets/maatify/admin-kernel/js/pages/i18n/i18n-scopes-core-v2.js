/**
 * 🌐 I18n Scopes Management - Core Module
 * ========================================
 * Main features:
 * - List scopes with pagination and filtering
 * - Custom renderers for each column
 * - Capability-based UI rendering
 */

(function() {
    'use strict';

    console.log('🌐 I18n Scopes Core Module Loading...');

    // ========================================================================
    // PREREQUISITES CHECK
    // ========================================================================

    if (typeof AdminUIComponents === 'undefined' || !window.AdminPageBridge || !window.I18nHelpersV2) {
        console.error('❌ Missing dependencies for i18n-scopes-core-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.I18nHelpersV2;

    console.log('✅ Dependencies loaded: AdminUIComponents, AdminPageBridge, I18nHelpersV2');

    // ========================================================================
    // STATE & CONFIGURATION
    // ========================================================================

    let currentPage = 1;
    let currentPerPage = 25;

    const headers = ['ID', 'Code', 'Name', 'Description', 'Active', 'Sort Order', 'Actions'];
    const rows = ['id', 'code', 'name', 'description', 'is_active', 'sort_order', 'actions'];
    const capabilities = window.i18nScopesCapabilities || {};

    console.log('🔐 Capabilities:', capabilities);

    // ========================================================================
    // UTILITY FUNCTIONS
    // ========================================================================

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // ========================================================================
    // CUSTOM RENDERERS
    // ========================================================================

    /**
     * ✅ ID Renderer - with optional navigation capability
     */
    const idRenderer = (value, row) => {
        if (capabilities.can_view_scope_details) {
            return `<a href="/i18n/scopes/${value}" 
                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline font-mono transition-colors">
                ${value}
            </a>`;
        }
        return `<span class="font-mono text-gray-600 dark:text-gray-400">${value}</span>`;
    };

    /**
     * ✅ Code Renderer - using AdminUIComponents
     */
    const codeRenderer = (value, row) => {
        return AdminUIComponents.renderCodeBadge(escapeHtml(value), {
            color: 'blue',
            size: 'sm'
        });
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
     * ✅ Status Renderer - using AdminUIComponents
     */
    const statusRenderer = (value, row) => {
        // Pass the actual value (1 or 0), not the string
        return AdminUIComponents.renderStatusBadge(value, {
            clickable: capabilities.can_set_active,
            entityId: row.id,
            activeText: 'Active',
            inactiveText: 'Inactive',
            buttonClass: 'toggle-active-btn',
            dataAttribute: 'data-scope-id'
        });
    };

    /**
     * ✅ Sort Order Renderer - using AdminUIComponents
     */
    const sortRenderer = (value, row) => {
        return AdminUIComponents.renderSortBadge(value, {
            size: 'md',
            color: 'indigo'
        });
    };

    /**
     * ✅ Actions Renderer - capability-based action buttons
     */
    const actionsRenderer = (value, row) => {
        const actions = [];

        // View Keys Button
        if (capabilities.can_view_scope_keys) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'view-scope-keys-btn',
                icon: AdminUIComponents.SVGIcons.link,
                text: 'Keys',
                color: 'purple',
                entityId: row.id,
                title: 'View scope keys'
            }));
        }

        // Change Code Button
        if (capabilities.can_change_code) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'change-code-btn',
                icon: AdminUIComponents.SVGIcons.tag,
                text: 'Code',
                color: 'amber',
                entityId: row.id,
                title: 'Change scope code',
                dataAttributes: {
                    'scope-id': row.id,
                    'current-code': row.code
                }
            }));
        }

        // Update Metadata Button
        if (capabilities.can_update_meta) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'update-metadata-btn',
                icon: AdminUIComponents.SVGIcons.edit,
                text: 'Meta',
                color: 'blue',
                entityId: row.id,
                title: 'Update name and description',
                dataAttributes: {
                    'scope-id': row.id
                }
            }));
        }

        // Update Sort Button
        if (capabilities.can_update_sort) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'update-sort-btn',
                icon: AdminUIComponents.SVGIcons.sort,
                text: 'Sort',
                color: 'indigo',
                entityId: row.id,
                title: 'Update sort order',
                dataAttributes: {
                    'scope-id': row.id,
                    'current-sort': row.sort_order
                }
            }));
        }

        if (actions.length === 0) {
            return '<span class="text-gray-400 dark:text-gray-500 text-xs">No actions</span>';
        }

        return `<div class="flex flex-wrap gap-2">${actions.join('')}</div>`;
    };

    // ========================================================================
    // QUERY BUILDING
    // ========================================================================

    function buildQueryParams() {
        const params = {
            page: currentPage,
            per_page: currentPerPage
        };

        // Global search
        const globalSearch = Bridge.DOM.value('#scopes-search', '').trim();

        // Column filters
        const columnFilters = Bridge.Form.omitEmpty({
            id: Bridge.DOM.value('#filter-id', '').trim(),
            name: Bridge.DOM.value('#filter-name', '').trim(),
            code: Bridge.DOM.value('#filter-code', '').trim(),
            is_active: Bridge.DOM.value('#filter-status', '')
        });

        console.log('🔍 Filter Values:', {
            globalSearch,
            columnFilters
        });

        // Build search object if needed
        if (globalSearch || Object.keys(columnFilters).length > 0) {
            params.search = {};
            if (globalSearch) params.search.global = globalSearch;
            if (Object.keys(columnFilters).length > 0) params.search.columns = columnFilters;
        }

        return params;
    }

    // ========================================================================
    // DATA LOADING
    // ========================================================================

    async function loadScopes(pageNumber = null, perPageNumber = null) {
        // Update pagination state
        if (pageNumber !== null) currentPage = pageNumber;
        if (perPageNumber !== null) currentPerPage = perPageNumber;

        console.log('📊 Loading scopes...', { page: currentPage, perPage: currentPerPage });

        const params = buildQueryParams();
        console.log('🔍 Query params:', params);

        const result = await Bridge.API.execute({
            endpoint: 'i18n/scopes/query',
            payload: params,
            operation: 'Query Scopes',
            showErrorMessage: false
        });

        if (!result.success) {
            const container = document.getElementById('table-container');
            if (container) {
                let errorHtml = `
                    <div class="bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 rounded-lg p-8 text-center m-4">
                        <div class="text-red-600 dark:text-red-400 text-xl font-semibold mb-2">
                            ❌ Failed to Load Scopes
                        </div>
                        <p class="text-red-700 dark:text-red-300 mb-4">
                            ${result.error || 'Unknown error occurred'}
                        </p>
                `;

                if (result.rawBody) {
                    errorHtml += `
                        <details class="mt-4 text-left">
                            <summary class="cursor-pointer text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                📄 Show Raw Response
                            </summary>
                            <pre class="mt-2 p-4 bg-gray-100 dark:bg-gray-800 rounded text-xs overflow-auto max-h-96 text-left">${result.rawBody.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</pre>
                        </details>
                    `;
                }

                errorHtml += `
                        <button onclick="location.reload()" class="mt-6 px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            🔄 Retry
                        </button>
                    </div>
                `;

                container.innerHTML = errorHtml;
            }
            return;
        }

        console.log('✅ Query successful, data received:', result.data);

        const data = result.data || {};
        const scopes = Array.isArray(data.data) ? data.data : [];
        const paginationInfo = data.pagination || {
            page: params.page || 1,
            per_page: params.per_page || 25,
            total: scopes.length
        };

        console.log('📊 Scopes data:', scopes);
        console.log('📊 Pagination:', paginationInfo);

        // Render table
        if (typeof TableComponent === 'function') {
            try {
                TableComponent(
                    scopes,
                    headers,
                    rows,
                    paginationInfo,
                    "",
                    false,
                    'id',
                    null,
                    {
                        id: idRenderer,
                        code: codeRenderer,
                        name: nameRenderer,
                        description: descriptionRenderer,
                        is_active: statusRenderer,
                        sort_order: sortRenderer,
                        actions: actionsRenderer
                    },
                    null,
                    getScopesPaginationInfo
                );
            } catch (error) {
                console.error('❌ TABLE ERROR:', error);
                Bridge.UI.error('Failed to render table: ' + error.message);
            }
        } else {
            console.error('❌ TableComponent not found');
        }
    }

    /**
     * Custom pagination info formatter
     * Returns format expected by data_table.js: { total, info }
     * Shows filtered message when filters are active
     */
    function getScopesPaginationInfo(pagination, params) {
        console.log('🎯 getScopesPaginationInfo called with:', pagination, params);

        const { page = 1, per_page = 25, total = 0, filtered = total } = pagination;

        // Use filtered count if available, otherwise use total
        const displayCount = filtered || total;
        const startItem = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
        const endItem = Math.min(page * per_page, displayCount);

        // Build info text
        let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;

        // Show filtered message if filtered count is different from total
        if (filtered && filtered !== total) {
            infoText += ` <span class="text-gray-500 dark:text-gray-400">(filtered from ${total} total)</span>`;
        }

        return {
            total: displayCount,  // Use filtered count for pagination calculations
            info: infoText        // HTML string for display
        };
    }

    // ========================================================================
    // SEARCH & FILTERS SETUP
    // ========================================================================

    function setupSearchAndFilters() {
        const resetPageAndReload = Helpers.createResetPageReload({
            setPage: function(page) { currentPage = page; },
            reload: function() { return loadScopes(); }
        });

        Bridge.Events.bindDebouncedInput({
            input: '#scopes-search',
            delay: 500,
            onFire: resetPageAndReload
        });

        const searchBtn = document.getElementById('scopes-search-btn');
        if (searchBtn) searchBtn.addEventListener('click', resetPageAndReload);

        const clearSearchBtn = document.getElementById('scopes-clear-search');
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                Bridge.DOM.setValue('#scopes-search', '');
                resetPageAndReload();
            });
        }

        const searchInput = document.getElementById('scopes-search');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key !== 'Enter') return;
                if (e.target && e.target.closest('form')) return;
                e.preventDefault();
                resetPageAndReload(e);
            });
        }

        Bridge.Events.bindFilterForm({
            form: '#scopes-filter-form',
            resetButton: '#scopes-reset-filters',
            onSubmit: resetPageAndReload,
            onReset: function() {
                Bridge.DOM.setValue('#scopes-search', '');
                resetPageAndReload();
            }
        });

        Helpers.bindTableActionState({
            buildParams: buildQueryParams,
            getState: function() { return { page: currentPage, perPage: currentPerPage }; },
            setState: function(state) {
                currentPage = state.page ?? currentPage;
                currentPerPage = state.perPage ?? currentPerPage;
            },
            reload: function() { return loadScopes(currentPage, currentPerPage); }
        });

        console.log('✅ Search and filters setup complete');
    }

    // ========================================================================
    // INITIALIZATION
    // ========================================================================

    function init() {
        console.log('🎬 Initializing I18n Scopes Core Module...');

        setupSearchAndFilters();
        AdminUIComponents.setupButtonHandler(
            '.view-scope-keys-btn',
            async (scopeId) => {
                window.location.assign(`/i18n/scopes/${scopeId}/keys`);
            }
        );
        loadScopes();

        console.log('✅ I18n Scopes Core Module initialized');
    }

    // ========================================================================
    // EXPORTS
    // ========================================================================

    // Global functions for pagination (called by data_table.js)
    window.changePage = function(page) {
        console.log('📄 changePage called:', page);
        loadScopes(page, null);
    };

    window.changePerPage = function(perPage) {
        console.log('🔢 changePerPage called:', perPage);
        currentPage = 1; // Reset to first page
        loadScopes(1, perPage);
    };

    // Export reload function for modals/actions
    window.reloadScopesTableV2 = function() {
        console.log('🔄 Reloading scopes table');
        loadScopes(currentPage, currentPerPage);
    };

    // Debug exports
    window.scopesDebugV2 = {
        loadScopes: loadScopes,
        buildQueryParams: buildQueryParams,
        currentPage: () => currentPage,
        currentPerPage: () => currentPerPage
    };

    // Run initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
