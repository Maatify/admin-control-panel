/**
 * 🌍 Currencies Management - Core Module
 * ===========================================================
 * Main features:
 * - List currencies with pagination and filtering
 * - Custom renderers using AdminUIComponents
 */

(function() {
    'use strict';

    console.log('🌍 Currencies Module Initialized');

    // ========================================================================
    // PREREQUISITES CHECK
    // ========================================================================

    if (typeof AdminUIComponents === 'undefined') {
        console.error('❌ AdminUIComponents library not found! Please include admin-ui-components.js');
        return;
    }

    // ========================================================================
    // State
    // ========================================================================

    let currentPage = 1;
    let currentPerPage = 20;

    // ========================================================================
    // Custom Renderers (Using AdminUIComponents)
    // ========================================================================

    const idRenderer = (value, row) => {
        const canView = window.currenciesCapabilities?.can_view_currency_translations ?? false;

        if (!canView) {
            return `<span class="text-gray-900 dark:text-gray-200">${value}</span>`;
        }

        return `
        <a href="/currencies/${value}/translations"
           class="text-blue-600 dark:text-blue-400 hover:underline font-medium">
            ${value}
        </a>
    `;
    };

    const nameRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 dark:text-gray-100 italic">N/A</span>';
        return `<span class="font-medium text-gray-900 dark:text-gray-200">${value}</span>`;
    };

    const codeRenderer = (value, row) => {
        return AdminUIComponents.renderCodeBadge(value, {
            color: 'blue',
            uppercase: true
        });
    };

    const symbolRenderer = (value, row) => {
        return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">${value}</span>`;
    };

    const sortRenderer = (value, row) => {
        return AdminUIComponents.renderSortBadge(value, {
            size: 'md',
            color: 'indigo'
        });
    };

    const statusRenderer = (value, row) => {
        const canActive = window.currenciesCapabilities?.can_active ?? false;

        return AdminUIComponents.renderStatusBadge(value, {
            clickable: canActive,
            entityId: row.id,
            activeText: 'Active',
            inactiveText: 'Inactive',
            buttonClass: 'toggle-status-btn',
            dataAttribute: 'data-currency-id'
        }).replace('data-currency-id', `data-current-is-active="${value ? '1' : '0'}" data-currency-id`);
    };

    const actionsRenderer = (value, row) => {
        const canUpdate = window.currenciesCapabilities?.can_update ?? false;
        const canUpdateSort = window.currenciesCapabilities?.can_update_sort ?? false;
        const canViewTranslations = window.currenciesCapabilities?.can_view_currency_translations ?? false;

        const hasAnyAction = canUpdate || canUpdateSort || canViewTranslations;

        if (!hasAnyAction) {
            return '<span class="text-gray-400 text-xs">No actions</span>';
        }

        const actions = [];

        // Translations Button
        if (canViewTranslations) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'view-currency-translations-btn',
                icon: AdminUIComponents.SVGIcons.link,
                text: 'Translations',
                color: 'purple',
                entityId: row.id,
                title: 'View currency translations'
            }));
        }

        // Edit Button (combines multiple fields for Currency unlike Languages)
        if (canUpdate) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'edit-currency-btn',
                icon: AdminUIComponents.SVGIcons.edit,
                text: 'Edit',
                color: 'blue',
                entityId: row.id,
                title: 'Edit currency details',
                dataAttributes: {
                    'currency-id': row.id,
                    'current-name': row.name,
                    'current-code': row.code,
                    'current-symbol': row.symbol,
                    'current-is-active': row.is_active ? '1' : '0',
                    'current-display-order': row.display_order
                }
            }));
        }

        // Update Sort Button
        if (canUpdateSort) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'update-sort-btn',
                icon: AdminUIComponents.SVGIcons.sort,
                text: 'Sort',
                color: 'indigo',
                entityId: row.id,
                title: 'Update sort order',
                dataAttributes: {
                    'currency-id': row.id,
                    'current-sort': row.display_order
                }
            }));
        }

        return `<div class="flex flex-wrap gap-1">${actions.join('')}</div>`;
    };

    // ========================================================================
    // Table Configuration
    // ========================================================================

    const headers = ['ID', 'Name', 'Code', 'Symbol', 'Order', 'Status', 'Actions'];
    const rows = ['id', 'name', 'code', 'symbol', 'display_order', 'is_active', 'actions'];

    // ========================================================================
    // Pagination Info Callback
    // ========================================================================

    function getCurrenciesPaginationInfo(pagination, params) {
        console.log('🎯 getCurrenciesPaginationInfo called with:', pagination, params);

        const { page = 1, per_page = 20, total = 0, filtered = total } = pagination;

        const displayCount = filtered || total;
        const startItem = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
        const endItem = Math.min(page * per_page, displayCount);

        let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;

        if (filtered && filtered !== total) {
            infoText += ` <span class="text-gray-500">(filtered from ${total} total)</span>`;
        }

        const result = {
            total: displayCount,
            info: infoText
        };

        console.log('📊 Pagination info:', result);
        return result;
    }

    // ========================================================================
    // Data Loading
    // ========================================================================

    function buildQueryParams() {
        const params = {
            page: currentPage,
            per_page: currentPerPage
        };

        const globalSearch = document.getElementById('currencies-search')?.value?.trim();
        const columnFilters = {};

        const filterId = document.getElementById('filter-id')?.value?.trim();
        if (filterId) columnFilters.id = filterId;

        const filterName = document.getElementById('filter-name')?.value?.trim();
        if (filterName) columnFilters.name = filterName;

        const filterCode = document.getElementById('filter-code')?.value?.trim();
        if (filterCode) columnFilters.code = filterCode;

        const filterStatus = document.getElementById('filter-status')?.value;
        if (filterStatus) columnFilters.is_active = filterStatus;

        if (globalSearch || Object.keys(columnFilters).length > 0) {
            params.search = {};
            if (globalSearch) params.search.global = globalSearch;
            if (Object.keys(columnFilters).length > 0) params.search.columns = columnFilters;
        }

        return params;
    }

    async function loadCurrencies(pageNumber = null, perPageNumber = null) {
        if (pageNumber !== null) currentPage = pageNumber;
        if (perPageNumber !== null) currentPerPage = perPageNumber;

        const params = buildQueryParams();
        const result = await ApiHandler.call('currencies/query', params, 'Query Currencies');

        if (!result.success) {
            const container = document.getElementById('table-container');
            if (container) {
                container.innerHTML = `
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Error!</strong>
                        <span class="block sm:inline">${result.error || 'Failed to load currencies.'}</span>
                    </div>
                `;
            }
            return;
        }

        const data = result.data || {};
        const items = Array.isArray(data.data) ? data.data : [];
        const paginationInfo = data.pagination || {
            page: params.page,
            per_page: params.per_page,
            total: items.length
        };

        if (typeof TableComponent === 'function') {
            try {
                TableComponent(
                    items,
                    headers,
                    rows,
                    paginationInfo,
                    "",
                    false,
                    'id',
                    null,
                    {
                        id: idRenderer,
                        name: nameRenderer,
                        code: codeRenderer,
                        symbol: symbolRenderer,
                        display_order: sortRenderer,
                        is_active: statusRenderer,
                        actions: actionsRenderer
                    },
                    null,
                    getCurrenciesPaginationInfo
                );
            } catch (error) {
                console.error("❌ TABLE ERROR:", error);
                ApiHandler.showAlert('danger', 'Failed to render table: ' + error.message);
            }
        } else {
            console.error("❌ TableComponent not found");
        }
    }

    // ========================================================================
    // Initialization & Event Listeners
    // ========================================================================

    function setupActionDelegation() {
        if (typeof window.CurrenciesHelpers?.setupButtonHandler === 'function') {
            window.CurrenciesHelpers.setupButtonHandler(
                '.view-currency-translations-btn',
                async (currencyId) => {
                    window.location.assign(`/currencies/${currencyId}/translations`);
                }
            );
        } else {
            // Fallback delegation
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.view-currency-translations-btn');
                if (btn) {
                    e.preventDefault();
                    const currencyId = btn.getAttribute('data-entity-id');
                    if (currencyId) {
                        window.location.assign(`/currencies/${currencyId}/translations`);
                    }
                }
            });
        }
    }

    function setupSearchAndFilters() {
        const globalSearchInput = document.getElementById('currencies-search');
        const searchBtn = document.getElementById('currencies-search-btn');
        const clearBtn = document.getElementById('currencies-clear-search');
        const filterForm = document.getElementById('currencies-filter-form');
        const resetBtn = document.getElementById('currencies-reset-filters');

        let searchTimeout;
        if (globalSearchInput) {
            globalSearchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentPage = 1;
                    loadCurrencies();
                }, 500);
            });
            globalSearchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    currentPage = 1;
                    loadCurrencies();
                }
            });
        }

        if (searchBtn) {
            searchBtn.addEventListener('click', () => {
                currentPage = 1;
                loadCurrencies();
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                if (globalSearchInput) globalSearchInput.value = '';
                currentPage = 1;
                loadCurrencies();
            });
        }

        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                currentPage = 1;
                loadCurrencies();
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (filterForm) filterForm.reset();
                currentPage = 1;
                loadCurrencies();
            });
        }
    }

    function init() {
        setupSearchAndFilters();
        setupActionDelegation();
        loadCurrencies();

        const btnCreate = document.getElementById('btn-create-currency');
        if (btnCreate && window.currenciesCapabilities?.can_create) {
            btnCreate.addEventListener('click', () => {
                if (typeof window.openCreateCurrencyModal === 'function') {
                    window.openCreateCurrencyModal();
                }
            });
        }
    }

    // ========================================================================
    // Exports
    // ========================================================================

    window.changePage = function(page) {
        console.log('📄 changePage called:', page);
        loadCurrencies(page, null);
    };

    window.changePerPage = function(perPage) {
        console.log('📝 changePerPage called:', perPage);
        currentPage = 1;
        loadCurrencies(1, perPage);
    };

    window.reloadCurrenciesTable = () => loadCurrencies(currentPage, currentPerPage);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
