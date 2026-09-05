/**
 * 💱 Rate History Management Core V2
 */

(function() {
    'use strict';

    console.log('💱 Rates History Module V2 Initialized');

    if (typeof AdminUIComponents === 'undefined' || !window.AdminPageBridge) {
        console.error('❌ Missing dependencies for rates-history-with-components-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.RatesHistoryHelpersV2;

    let currentPage = 1;
    let currentPerPage = 10;

    const headers = ['ID', 'Rate ID', 'Provider', 'Base', 'Target', 'Old Rate', 'New Rate', 'Changed By', 'Date'];
    const rows = ['id', 'rate_id', 'provider_name', 'base_currency_code', 'target_currency_code', 'old_rate', 'new_rate', 'admin_name', 'created_at'];

    const monoRenderer = function(value) {
        if (!value) return '<span class="text-gray-400 italic">—</span>';
        return '<span class="font-mono text-sm text-gray-800 dark:text-gray-100 bg-gray-50 dark:bg-gray-700/50 px-2 py-0.5 rounded">' + value + '</span>';
    };

    const dateRenderer = function(value) {
        return '<span class="text-xs text-gray-600 dark:text-gray-400">' + AdminUIComponents.formatDate(value, { format: 'full' }) + '</span>';
    };

    const codeRenderer = function(value) {
        return AdminUIComponents.renderCodeBadge(value || '-', { color: 'blue', uppercase: true });
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
        const globalSearch = Bridge.DOM.value('#history-search', '').trim();

        const columnFilters = Bridge.Form.omitEmpty({
            id: Bridge.DOM.value('#filter-id', '').trim(),
            rate_id: `${window.rateId}`,
            provider_id: Bridge.DOM.value('#filter-provider', ''),
            base_currency_code: Bridge.DOM.value('#filter-base', '').trim(),
            target_currency_code: Bridge.DOM.value('#filter-target', '').trim()
        });

        if (globalSearch || Object.keys(columnFilters).length > 0) {
            params.search = {};
            if (globalSearch) params.search.global = globalSearch;
            if (Object.keys(columnFilters).length > 0) params.search.columns = columnFilters;
        }

        return params;
    }

    async function loadHistory(pageNumber, perPageNumber) {
        if (pageNumber !== null && pageNumber !== undefined) currentPage = pageNumber;
        if (perPageNumber !== null && perPageNumber !== undefined) currentPerPage = perPageNumber;

        const params = buildQueryParams();
        const result = await Bridge.API.execute({
            endpoint: 'exchange-rates/rates/history/query',
            payload: params,
            operation: 'Query Rate History',
            method: 'POST',
            showErrorMessage: false
        });

        if (!result.success) {
            const container = document.getElementById('table-container');
            if (container) {
                container.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative"><strong class="font-bold">Error!</strong><span class="block sm:inline">' + (result.error || 'Failed to load history.') + '</span></div>';
            }
            return;
        }

        const data = result.data || {};
        const items = Array.isArray(data.data) ? data.data : [];
        const paginationInfo = data.pagination || { page: params.page, per_page: params.per_page, total: items.length };
        currentPage = Bridge.normalizeInt(paginationInfo.page, currentPage) || currentPage;
        currentPerPage = Bridge.normalizeInt(paginationInfo.per_page, currentPerPage) || currentPerPage;

        try {
            TableComponent(items, headers, rows, paginationInfo, '', false, 'id', null, {
                old_rate: monoRenderer,
                new_rate: monoRenderer,
                created_at: dateRenderer,
                base_currency_code: codeRenderer,
                target_currency_code: codeRenderer
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
        const resetPageAndReload = Helpers.bindResetPageReload({
            setPage: function(page) { currentPage = page; },
            reload: function() { return loadHistory(); }
        });

        Bridge.Events.bindDebouncedInput({
            input: '#history-search',
            delay: 500,
            eventName: 'input',
            onFire: resetPageAndReload
        });

        Bridge.Events.bindFilterForm({
            form: '#history-filter-form',
            resetButton: '#history-reset-filters',
            onSubmit: resetPageAndReload,
            onReset: resetPageAndReload
        });

        const filterFormSelects = document.querySelectorAll('#history-filter-form select');
        filterFormSelects.forEach(function(select) {
            select.addEventListener('change', resetPageAndReload);
        });

        Helpers.bindTableActionState({
            getParams: buildQueryParams,
            sourceContainerId: 'table-container',
            getState: function() { return { page: currentPage, perPage: currentPerPage }; },
            setState: function(state) {
                currentPage = state.page ?? currentPage;
                currentPerPage = state.perPage ?? currentPerPage;
            },
            reload: function() { return loadHistory(currentPage, currentPerPage); }
        });
    }

    function init() {
        setupSearchAndFilters();
        loadProvidersDropdown();
        loadHistory();

        window.reloadHistoryTableV2 = function() {
            return loadHistory(currentPage, currentPerPage);
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
