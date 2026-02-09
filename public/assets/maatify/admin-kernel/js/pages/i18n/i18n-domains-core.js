/**
 * 🌐 I18n Domains Management - Core Module
 * ========================================
 * Main features:
 * - List domains with pagination and filtering
 * - Custom renderers for each column
 * - Capability-based UI rendering
 */

(function() {
    'use strict';

    console.log('🌐 I18n Domains Core Module Loading...');

    // ==========================================================================
    // PREREQUISITES CHECK
    // ==========================================================================

    if (typeof AdminUIComponents === 'undefined') {
        console.error('❌ AdminUIComponents library not found!');
        return;
    }

    if (typeof ApiHandler === 'undefined') {
        console.error('❌ ApiHandler not found!');
        return;
    }

    console.log('✅ Dependencies loaded: AdminUIComponents, ApiHandler');

    // ==========================================================================
    // STATE & CONFIGURATION
    // ==========================================================================

    let currentPage = 1;
    let currentPerPage = 25;

    const headers = ['ID', 'Code', 'Name', 'Description', 'Active', 'Sort Order', 'Actions'];
    const rows = ['id', 'code', 'name', 'description', 'is_active', 'sort_order', 'actions'];
    const capabilities = window.i18nDomainsCapabilities || {};

    console.log('🔐 Capabilities:', capabilities);

    // ==========================================================================
    // UTILITY FUNCTIONS
    // ==========================================================================

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // ==========================================================================
    // CUSTOM RENDERERS
    // ==========================================================================

    const idRenderer = (value, row) => {
        // Domain Contract: no "details" navigation capability for Domains.
        return `<span class="font-mono text-gray-600 dark:text-gray-400">${value}</span>`;
    };

    const codeRenderer = (value, row) => {
        return AdminUIComponents.renderCodeBadge(escapeHtml(value), {
            color: 'blue',
            size: 'sm'
        });
    };

    const nameRenderer = (value, row) => {
        return `<span class="font-medium text-gray-900 dark:text-gray-100">${escapeHtml(value)}</span>`;
    };

    const descriptionRenderer = (value, row) => {
        if (!value || value === '') {
            return `<span class="text-gray-400 dark:text-gray-500 italic text-sm">No description</span>`;
        }
        const truncated = value.length > 50 ? value.substring(0, 50) + '...' : value;
        return `<span class="text-gray-700 dark:text-gray-300 text-sm" title="${escapeHtml(value)}">${escapeHtml(truncated)}</span>`;
    };

    const statusRenderer = (value, row) => {
        return AdminUIComponents.renderStatusBadge(value, {
            clickable: capabilities.can_set_active,
            entityId: row.id,
            activeText: 'Active',
            inactiveText: 'Inactive',
            buttonClass: 'toggle-active-btn',
            dataAttribute: 'data-domain-id'
        });
    };

    const sortRenderer = (value, row) => {
        return AdminUIComponents.renderSortBadge(value, {
            size: 'md',
            color: 'indigo'
        });
    };

    const actionsRenderer = (value, row) => {
        const actions = [];

        if (capabilities.can_change_code) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'change-code-btn',
                icon: AdminUIComponents.SVGIcons.tag,
                text: 'Code',
                color: 'amber',
                entityId: row.id,
                title: 'Change domain code',
                dataAttributes: {
                    'domain-id': row.id,
                    'current-code': row.code
                }
            }));
        }

        if (capabilities.can_update_meta) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'update-metadata-btn',
                icon: AdminUIComponents.SVGIcons.edit,
                text: 'Meta',
                color: 'blue',
                entityId: row.id,
                title: 'Update name and description',
                dataAttributes: {
                    'domain-id': row.id
                }
            }));
        }

        if (capabilities.can_update_sort) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'update-sort-btn',
                icon: AdminUIComponents.SVGIcons.sort,
                text: 'Sort',
                color: 'indigo',
                entityId: row.id,
                title: 'Update sort order',
                dataAttributes: {
                    'domain-id': row.id,
                    'current-sort': row.sort_order
                }
            }));
        }

        if (actions.length === 0) {
            return '<span class="text-gray-400 dark:text-gray-500 text-xs">No actions</span>';
        }

        return `<div class="flex flex-wrap gap-2">${actions.join('')}</div>`;
    };

    // ==========================================================================
    // QUERY BUILDING
    // ==========================================================================

    function buildQueryParams() {
        const params = {
            page: currentPage,
            per_page: currentPerPage
        };

        const globalSearch = document.getElementById('domains-search')?.value?.trim();

        const columnFilters = {};

        const filterId = document.getElementById('filter-id')?.value?.trim();
        if (filterId) columnFilters.id = filterId;

        const filterName = document.getElementById('filter-name')?.value?.trim();
        if (filterName) columnFilters.name = filterName;

        const filterCode = document.getElementById('filter-code')?.value?.trim();
        if (filterCode) columnFilters.code = filterCode;

        const filterStatus = document.getElementById('filter-status')?.value;
        if (filterStatus) columnFilters.is_active = filterStatus;

        console.log('🔍 Filter Values:', { globalSearch, columnFilters });

        if (globalSearch || Object.keys(columnFilters).length > 0) {
            params.search = {};
            if (globalSearch) params.search.global = globalSearch;
            if (Object.keys(columnFilters).length > 0) params.search.columns = columnFilters;
        }

        return params;
    }

    // ==========================================================================
    // MAIN LOADER FUNCTION
    // ==========================================================================

    async function loadDomains(page = null, perPage = null) {
        if (page !== null) currentPage = page;
        if (perPage !== null) currentPerPage = perPage;

        console.log('📡 Loading domains...', { currentPage, currentPerPage });

        const params = buildQueryParams();

        const result = await ApiHandler.call('i18n/domains/query', params, 'Query Domains');

        const container = document.getElementById('table-container');
        if (!container) {
            console.error('❌ table-container not found');
            return;
        }

        if (!result.success) {
            console.error('❌ Query failed:', result);

            if (result.data && result.data.errors) {
                ApiHandler.showAlert('danger', 'Failed to load domains');
            } else {
                let errorHtml = `
                    <div class="p-6 text-center">
                        <div class="text-red-600 dark:text-red-400 text-lg font-semibold mb-2">⚠️ Error Loading Data</div>
                        <div class="text-gray-600 dark:text-gray-300 mb-4">Failed to load domains. Please try again.</div>
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
        const domains = Array.isArray(data.data) ? data.data : [];
        const paginationInfo = data.pagination || {
            page: params.page || 1,
            per_page: params.per_page || 25,
            total: domains.length
        };

        console.log('📊 Domains data:', domains);
        console.log('📊 Pagination:', paginationInfo);

        if (typeof TableComponent === 'function') {
            try {
                TableComponent(
                    domains,
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
                    getDomainsPaginationInfo
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
     * Returns format expected by data_table.js: { total, info }
     * Shows filtered message when filters are active
     */
    function getDomainsPaginationInfo(pagination) {
        console.log('🎯 getDomainsPaginationInfo called with:', pagination);

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

    // ==========================================================================
    // INITIALIZATION
    // ==========================================================================

    function init() {
        console.log('🎬 Initializing I18n Domains Core Module...');

        document.getElementById('domains-search-btn')?.addEventListener('click', () => loadDomains(1));
        document.getElementById('domains-clear-search')?.addEventListener('click', () => {
            document.getElementById('domains-search').value = '';
            loadDomains(1);
        });
        document.getElementById('domains-search')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') loadDomains(1);
        });

        document.getElementById('domains-filter-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            loadDomains(1);
        });

        document.getElementById('domains-reset-filters')?.addEventListener('click', () => {
            document.getElementById('domains-filter-form').reset();
            loadDomains(1);
        });

        // Export changePage/changePerPage hooks (TableComponent uses these)
        window.changePage = function(page) {
            console.log('📄 changePage called:', page);
            loadDomains(page, null);
        };

        window.changePerPage = function(perPage) {
            console.log('🔢 changePerPage called:', perPage);
            currentPage = 1;
            loadDomains(1, perPage);
        };

        window.reloadDomainsTable = function() {
            console.log('🔄 Reloading domains table');
            loadDomains(currentPage, currentPerPage);
        };

        window.domainsDebug = {
            loadDomains: loadDomains,
            buildQueryParams: buildQueryParams,
            currentPage: () => currentPage,
            currentPerPage: () => currentPerPage
        };

        loadDomains();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
