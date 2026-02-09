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

    if (typeof AdminUIComponents === 'undefined') {
        console.error('❌ AdminUIComponents library not found!');
        return;
    }

    if (typeof ApiHandler === 'undefined') {
        console.error('❌ ApiHandler not found!');
        return;
    }

    console.log('✅ Dependencies loaded: AdminUIComponents, ApiHandler');

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
        const globalSearch = document.getElementById('scopes-search')?.value?.trim();

        // Column filters
        const columnFilters = {};

        const filterId = document.getElementById('filter-id')?.value?.trim();
        if (filterId) columnFilters.id = filterId;

        const filterName = document.getElementById('filter-name')?.value?.trim();
        if (filterName) columnFilters.name = filterName;

        const filterCode = document.getElementById('filter-code')?.value?.trim();
        if (filterCode) columnFilters.code = filterCode;

        const filterStatus = document.getElementById('filter-status')?.value;
        if (filterStatus) columnFilters.is_active = filterStatus;

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

        const result = await ApiHandler.call('i18n/scopes/query', params, 'Query Scopes');

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
                ApiHandler.showAlert('danger', 'Failed to render table: ' + error.message);
            }
        } else {
            console.error('❌ TableComponent not found');
        }
    }

    /**
     * Custom pagination info formatter
     */
    function getScopesPaginationInfo(pagination) {
        return {
            start: (pagination.page - 1) * pagination.per_page + 1,
            end: Math.min(pagination.page * pagination.per_page, pagination.total),
            total: pagination.total,
            filtered: pagination.filtered || pagination.total
        };
    }

    // ========================================================================
    // SEARCH & FILTERS SETUP
    // ========================================================================

    function setupSearchAndFilters() {
        // Global search
        const searchBtn = document.getElementById('scopes-search-btn');
        const clearSearchBtn = document.getElementById('scopes-clear-search');
        const searchInput = document.getElementById('scopes-search');

        if (searchBtn) {
            searchBtn.addEventListener('click', () => {
                console.log('🔍 Search button clicked');
                loadScopes(1);
            });
        }

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', () => {
                console.log('🗑️ Clear search clicked');
                if (searchInput) searchInput.value = '';
                loadScopes(1);
            });
        }

        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    console.log('⏎ Enter pressed in search');
                    e.preventDefault();
                    loadScopes(1);
                }
            });
        }

        // Filter form
        const filterForm = document.getElementById('scopes-filter-form');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                console.log('🔍 Filter form submitted');
                loadScopes(1);
            });
        }

        // Reset filters
        const resetBtn = document.getElementById('scopes-reset-filters');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                console.log('🔄 Reset filters clicked');
                if (filterForm) filterForm.reset();
                if (searchInput) searchInput.value = '';
                loadScopes(1);
            });
        }

        console.log('✅ Search and filters setup complete');
    }

    // ========================================================================
    // INITIALIZATION
    // ========================================================================

    function init() {
        console.log('🎬 Initializing I18n Scopes Core Module...');

        setupSearchAndFilters();
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
    window.reloadScopesTable = function() {
        console.log('🔄 Reloading scopes table');
        loadScopes(currentPage, currentPerPage);
    };

    // Debug exports
    window.scopesDebug = {
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
