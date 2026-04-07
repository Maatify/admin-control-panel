/**
 * 🌍 Currencies Management Core V2 (Bridge-first)
 */

(function() {
    'use strict';

    console.log('🌍 Currencies Module V2 Initialized');

    if (typeof AdminUIComponents === 'undefined' || !window.AdminPageBridge) {
        console.error('❌ Missing dependencies for currencies-with-components-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;

    let currentPage = 1;
    let currentPerPage = 20;

    const headers = ['ID', 'Name', 'Code', 'Symbol', 'Order', 'Status', 'Actions'];
    const rows = ['id', 'name', 'code', 'symbol', 'display_order', 'is_active', 'actions'];

    const idRenderer = function(value) {
        const canView = window.currenciesCapabilities?.can_view_currency_translations ?? false;
        if (!canView) return '<span class="text-gray-900 dark:text-gray-200">' + value + '</span>';
        return '<a href="/currencies/' + value + '/translations" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">' + value + '</a>';
    };

    const nameRenderer = function(value) {
        if (!value) return '<span class="text-gray-400 dark:text-gray-100 italic">N/A</span>';
        return '<span class="font-medium text-gray-900 dark:text-gray-200">' + value + '</span>';
    };

    const codeRenderer = function(value) {
        return AdminUIComponents.renderCodeBadge(value, { color: 'blue', uppercase: true });
    };

    const symbolRenderer = function(value) {
        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">' + value + '</span>';
    };

    const sortRenderer = function(value) {
        return AdminUIComponents.renderSortBadge(value, { size: 'md', color: 'indigo' });
    };

    const statusRenderer = function(value, row) {
        const canActive = window.currenciesCapabilities?.can_active ?? false;
        return AdminUIComponents.renderStatusBadge(value, {
            clickable: canActive,
            entityId: row.id,
            activeText: 'Active',
            inactiveText: 'Inactive',
            buttonClass: 'toggle-status-btn',
            dataAttribute: 'data-currency-id'
        }).replace('data-currency-id', 'data-current-is-active="' + (value ? '1' : '0') + '" data-currency-id');
    };

    const actionsRenderer = function(value, row) {
        const canUpdate = window.currenciesCapabilities?.can_update ?? false;
        const canUpdateSort = window.currenciesCapabilities?.can_update_sort ?? false;
        const canViewTranslations = window.currenciesCapabilities?.can_view_currency_translations ?? false;

        if (!canUpdate && !canUpdateSort && !canViewTranslations) {
            return '<span class="text-gray-400 text-xs">No actions</span>';
        }

        const actions = [];
        if (canViewTranslations) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'view-currency-translations-btn',
                icon: AdminUIComponents.SVGIcons.link,
                text: 'Translations',
                color: 'purple',
                entityId: row.id,
                title: 'View currency translations',
                dataAttributes: { 'currency-id': row.id }
            }));
        }

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

        if (canUpdateSort) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'update-sort-btn',
                icon: AdminUIComponents.SVGIcons.sort,
                text: 'Sort',
                color: 'indigo',
                entityId: row.id,
                title: 'Update sort order',
                dataAttributes: { 'currency-id': row.id, 'current-sort': row.display_order }
            }));
        }

        return '<div class="flex flex-wrap gap-1">' + actions.join('') + '</div>';
    };

    function getCurrenciesPaginationInfo(pagination) {
        const page = pagination.page || 1;
        const perPage = pagination.per_page || 20;
        const total = pagination.total || 0;
        const filtered = pagination.filtered === undefined ? total : pagination.filtered;
        const displayCount = filtered || total;
        const startItem = displayCount === 0 ? 0 : (page - 1) * perPage + 1;
        const endItem = Math.min(page * perPage, displayCount);

        let infoText = '<span>' + startItem + ' to ' + endItem + '</span> of <span>' + displayCount + '</span>';
        if (filtered && filtered !== total) infoText += ' <span class="text-gray-500">(filtered from ' + total + ' total)</span>';

        return { total: displayCount, info: infoText };
    }

    function buildQueryParams() {
        const params = { page: currentPage, per_page: currentPerPage };
        const globalSearch = Bridge.DOM.value('#currencies-search', '').trim();

        const columnFilters = Bridge.Form.omitEmpty({
            id: Bridge.DOM.value('#filter-id', '').trim(),
            name: Bridge.DOM.value('#filter-name', '').trim(),
            code: Bridge.DOM.value('#filter-code', '').trim(),
            is_active: Bridge.DOM.value('#filter-status', '')
        });

        if (globalSearch || Object.keys(columnFilters).length > 0) {
            params.search = {};
            if (globalSearch) params.search.global = globalSearch;
            if (Object.keys(columnFilters).length > 0) params.search.columns = columnFilters;
        }

        return params;
    }

    async function loadCurrencies(pageNumber, perPageNumber) {
        if (pageNumber !== null && pageNumber !== undefined) currentPage = pageNumber;
        if (perPageNumber !== null && perPageNumber !== undefined) currentPerPage = perPageNumber;

        const params = buildQueryParams();
        const result = await Bridge.API.execute({
            endpoint: 'currencies/query',
            payload: params,
            operation: 'Query Currencies',
            method: 'POST',
            showErrorMessage: false
        });

        if (!result.success) {
            const container = document.getElementById('table-container');
            if (container) {
                container.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative"><strong class="font-bold">Error!</strong><span class="block sm:inline">' + (result.error || 'Failed to load currencies.') + '</span></div>';
            }
            Bridge.UI.error(result.error || 'Failed to load currencies.');
            return;
        }

        const data = result.data || {};
        const items = Array.isArray(data.data) ? data.data : [];
        const paginationInfo = data.pagination || { page: params.page, per_page: params.per_page, total: items.length };
        currentPage = Bridge.normalizeInt(paginationInfo.page, currentPage) || currentPage;
        currentPerPage = Bridge.normalizeInt(paginationInfo.per_page, currentPerPage) || currentPerPage;

        try {
            TableComponent(items, headers, rows, paginationInfo, '', false, 'id', null, {
                id: idRenderer,
                name: nameRenderer,
                code: codeRenderer,
                symbol: symbolRenderer,
                display_order: sortRenderer,
                is_active: statusRenderer,
                actions: actionsRenderer
            }, null, getCurrenciesPaginationInfo);
        } catch (error) {
            Bridge.UI.error('Failed to render table: ' + error.message);
        }
    }

    function setupActionDelegation() {
        Bridge.Events.onClick('.view-currency-translations-btn', function(event, btn) {
            event.preventDefault();
            const currencyId = btn.getAttribute('data-entity-id') || btn.getAttribute('data-currency-id');
            if (currencyId) window.location.assign('/currencies/' + currencyId + '/translations');
        });
    }

    function setupSearchAndFilters() {
        Bridge.Events.bindDebouncedInput({
            input: '#currencies-search',
            delay: 500,
            eventName: 'input',
            onFire: function() {
                currentPage = 1;
                loadCurrencies();
            }
        });

        const globalSearchInput = document.getElementById('currencies-search');
        if (globalSearchInput) {
            globalSearchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    currentPage = 1;
                    loadCurrencies();
                }
            });
        }

        const searchBtn = document.getElementById('currencies-search-btn');
        if (searchBtn) searchBtn.addEventListener('click', function() { currentPage = 1; loadCurrencies(); });

        const clearBtn = document.getElementById('currencies-clear-search');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (globalSearchInput) globalSearchInput.value = '';
                currentPage = 1;
                loadCurrencies();
            });
        }

        Bridge.Events.bindFilterForm({
            form: '#currencies-filter-form',
            resetButton: '#currencies-reset-filters',
            onSubmit: function() {
                currentPage = 1;
                loadCurrencies();
            },
            onReset: function() {
                currentPage = 1;
                loadCurrencies();
            }
        });

        document.addEventListener('tableAction', function(e) {
            const detail = e.detail || {};
            const next = Bridge.Table.applyActionParams(buildQueryParams(), { action: detail.action, value: detail.value });
            currentPage = next.page ?? currentPage;
            currentPerPage = next.per_page ?? currentPerPage;
            loadCurrencies(currentPage, currentPerPage);
        });
    }

    function init() {
        setupSearchAndFilters();
        setupActionDelegation();
        loadCurrencies();

        const btnCreate = document.getElementById('btn-create-currency');
        if (btnCreate && window.currenciesCapabilities?.can_create) {
            btnCreate.addEventListener('click', function() {
                if (window.CurrenciesModalsV2?.openCreateCurrencyModal) {
                    window.CurrenciesModalsV2.openCreateCurrencyModal();
                } else if (typeof window.openCreateCurrencyModalV2 === 'function') {
                    window.openCreateCurrencyModalV2();
                }
            });
        }
    }

    window.reloadCurrenciesTableV2 = function() {
        return loadCurrencies(currentPage, currentPerPage);
    };

    window.CurrenciesCoreV2 = {
        loadCurrencies,
        buildQueryParams
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
