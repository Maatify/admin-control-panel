/**
 * 💱 Exchange Rates Management Core V2 (Bridge-first)
 */

(function() {
    'use strict';

    console.log('💱 Rates Module V2 Initialized');

    if (typeof AdminUIComponents === 'undefined' || !window.AdminPageBridge) {
        console.error('❌ Missing dependencies for rates-with-components-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.RatesHelpersV2;

    let currentPage = 1;
    let currentPerPage = 20;

    const headers = ['ID', 'Provider', 'Base', 'Target', 'Rate', 'Order', 'Status', 'Actions'];
    const rows = ['id', 'provider_name', 'base_currency_code', 'target_currency_code', 'rate', 'display_order', 'is_active', 'actions'];

    const idRenderer = function(value) {
        return '<span class="text-gray-900 dark:text-gray-200">' + value + '</span>';
    };

    const providerRenderer = function(value) {
        if (!value) return '<span class="text-gray-400 dark:text-gray-500 italic">N/A</span>';
        return '<span class="font-medium text-gray-900 dark:text-gray-200">' + value + '</span>';
    };

    const baseCurrencyRenderer = function(value) {
        return AdminUIComponents.renderCodeBadge(value || '-', { color: 'blue', uppercase: true });
    };

    const targetCurrencyRenderer = function(value) {
        return AdminUIComponents.renderCodeBadge(value || '-', { color: 'purple', uppercase: true });
    };

    const rateRenderer = function(value) {
        if (value === null || value === undefined || value === '') {
            return '<span class="text-gray-400 dark:text-gray-500 italic">N/A</span>';
        }
        return '<span class="font-mono text-sm text-gray-800 dark:text-gray-100 bg-gray-50 dark:bg-gray-700/50 px-2 py-0.5 rounded">' + value + '</span>';
    };

    const sortRenderer = function(value) {
        return AdminUIComponents.renderSortBadge(value, { size: 'md', color: 'indigo' });
    };

    const statusRenderer = function(value, row) {
        const canActive = window.ratesCapabilities?.can_active ?? false;
        return AdminUIComponents.renderStatusBadge(value, {
            clickable: canActive,
            entityId: row.id,
            activeText: 'Active',
            inactiveText: 'Inactive',
            buttonClass: 'toggle-status-btn',
            dataAttribute: 'data-rate-id'
        }).replace('data-rate-id', 'data-current-is-active="' + (value ? '1' : '0') + '" data-rate-id');
    };

    const actionsRenderer = function(_, row) {
        const canUpdate = window.ratesCapabilities?.can_update ?? false;
        const canUpdateSort = window.ratesCapabilities?.can_update_sort ?? false;
        const canDelete = window.ratesCapabilities?.can_delete ?? false;
        const canViewHistory = window.ratesCapabilities?.can_view_history ?? false;

        if (!canUpdate && !canUpdateSort && !canDelete && !canViewHistory) {
            return '<span class="text-gray-400 text-xs">No actions</span>';
        }

        const actions = [];

        if (canUpdate) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'edit-rate-btn',
                icon: AdminUIComponents.SVGIcons.edit,
                text: 'Edit',
                color: 'blue',
                entityId: row.id,
                title: 'Edit rate',
                dataAttributes: {
                    'rate-id': row.id,
                    'current-provider-id': row.provider_id || '',
                    'current-provider-name': row.provider_name || '',
                    'current-base': row.base_currency_code || '',
                    'current-target': row.target_currency_code || '',
                    'current-rate': row.rate || '',
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
                dataAttributes: { 'rate-id': row.id, 'current-sort': row.display_order }
            }));
        }

        if (canViewHistory) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'view-history-btn',
                icon: AdminUIComponents.SVGIcons.view,
                text: 'History',
                color: 'purple',
                entityId: row.id,
                title: 'View rate history',
                link: '/exchange-rates/rates/' + row.id + '/history',
                dataAttributes: {
                    'rate-id': row.id,
                    'rate-label': (row.base_currency_code || '') + '/' + (row.target_currency_code || '')
                }
            }));
        }

        if (canDelete) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'delete-rate-btn',
                icon: AdminUIComponents.SVGIcons.delete,
                text: 'Delete',
                color: 'red',
                entityId: row.id,
                title: 'Delete rate',
                dataAttributes: { 'rate-id': row.id }
            }));
        }

        return '<div class="flex flex-wrap gap-1">' + actions.join('') + '</div>';
    };

    function getPaginationInfo(pagination) {
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
        const globalSearch = Bridge.DOM.value('#rates-search', '').trim();

        const columnFilters = Bridge.Form.omitEmpty({
            // id: Bridge.DOM.value('#filter-id', '').trim(),
            provider_id: Bridge.DOM.value('#filter-provider', ''),
            base_currency_code: Bridge.DOM.value('#filter-base', '').trim(),
            target_currency_code: Bridge.DOM.value('#filter-target', '').trim(),
            is_active: Bridge.DOM.value('#filter-status', '')
        });

        if (globalSearch || Object.keys(columnFilters).length > 0) {
            params.search = {};
            if (globalSearch) params.search.global = globalSearch;
            if (Object.keys(columnFilters).length > 0) params.search.columns = columnFilters;
        }

        return params;
    }

    async function loadRates(pageNumber, perPageNumber) {
        if (pageNumber !== null && pageNumber !== undefined) currentPage = pageNumber;
        if (perPageNumber !== null && perPageNumber !== undefined) currentPerPage = perPageNumber;

        const params = buildQueryParams();
        const result = await Bridge.API.execute({
            endpoint: 'exchange-rates/rates/query',
            payload: params,
            operation: 'Query Exchange Rates',
            method: 'POST',
            showErrorMessage: false
        });

        if (!result.success) {
            const container = document.getElementById('table-container');
            if (container) {
                container.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative"><strong class="font-bold">Error!</strong><span class="block sm:inline">' + (result.error || 'Failed to load exchange rates.') + '</span></div>';
            }
            Bridge.UI.error(result.error || 'Failed to load exchange rates.');
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
                provider_name: providerRenderer,
                base_currency_code: baseCurrencyRenderer,
                target_currency_code: targetCurrencyRenderer,
                rate: rateRenderer,
                display_order: sortRenderer,
                is_active: statusRenderer,
                actions: actionsRenderer
            }, null, getPaginationInfo);
        } catch (error) {
            Bridge.UI.error('Failed to render table: ' + error.message);
        }
    }

    async function loadProvidersDropdown() {
        const selectEl = document.getElementById('filter-provider');
        if (!selectEl) return;

        const result = await Bridge.API.execute({
            endpoint: 'exchange-rates/providers/dropdown',
            payload: {},
            operation: 'Load Providers Dropdown',
            method: 'POST',
            showErrorMessage: false
        });

        if (result.success && result.data && result.data.data) {
            const currentHTML = selectEl.innerHTML;
            selectEl.innerHTML = currentHTML + result.data.data.map(function(p) {
                return '<option value="' + p.id + '">' + Bridge.Text.escapeHtml(p.name) + ' (' + Bridge.Text.escapeHtml(p.code) + ')</option>';
            }).join('');
        }
    }

    function setupSearchAndFilters() {
        const resetPageAndReload = Helpers?.bindResetPageReload
            ? Helpers.bindResetPageReload({
                setPage: function(page) { currentPage = page; },
                reload: function() { return loadRates(); }
            })
            : function() {
                currentPage = 1;
                return loadRates();
            };

        Bridge.Events.bindDebouncedInput({
            input: '#rates-search',
            delay: 500,
            eventName: 'input',
            onFire: resetPageAndReload
        });

        const searchBtn = document.getElementById('rates-search-btn');
        if (searchBtn) searchBtn.addEventListener('click', resetPageAndReload);

        const clearBtn = document.getElementById('rates-clear-search');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                const input = document.getElementById('rates-search');
                if (input) input.value = '';
                resetPageAndReload();
            });
        }

        Bridge.Events.bindFilterForm({
            form: '#rates-filter-form',
            resetButton: '#rates-reset-filters',
            onSubmit: resetPageAndReload,
            onReset: resetPageAndReload
        });

        const filterFormSelects = document.querySelectorAll('#rates-filter-form select');
        filterFormSelects.forEach(function(select) {
            select.addEventListener('change', resetPageAndReload);
        });

        if (Helpers?.bindTableActionState) {
            Helpers.bindTableActionState({
                getParams: buildQueryParams,
                sourceContainerId: 'table-container',
                getState: function() { return { page: currentPage, perPage: currentPerPage }; },
                setState: function(state) {
                    currentPage = state.page ?? currentPage;
                    currentPerPage = state.perPage ?? currentPerPage;
                },
                reload: function() { return loadRates(currentPage, currentPerPage); }
            });
        }
    }

    function init() {
        setupSearchAndFilters();
        loadProvidersDropdown();
        loadRates();

        window.loadRatesV2 = loadRates;
        window.reloadRatesTableV2 = function() {
            return loadRates(currentPage, currentPerPage);
        };

        // Delegated click handler for Edit button
        Bridge.Events.onClick('.edit-rate-btn', function(event, btn) {
            event.preventDefault();
            const rateId = btn.getAttribute('data-rate-id') || btn.getAttribute('data-entity-id');
            if (!rateId) return;

            const modals = window.RatesModalsV2;
            if (modals && typeof modals.openEditRateModal === 'function') {
                modals.openEditRateModal(rateId, btn);
            } else {
                console.warn('[Rates] RatesModalsV2 not ready yet.');
            }
        });
    }

    window.RatesCoreV2 = {
        loadRates,
        buildQueryParams
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
