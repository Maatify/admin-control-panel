/**
 * 💱 Exchange Rate Providers Management Core V2 (Bridge-first)
 */

(function() {
    'use strict';

    console.log('💱 Providers Module V2 Initialized');

    if (typeof AdminUIComponents === 'undefined' || !window.AdminPageBridge) {
        console.error('❌ Missing dependencies for providers-with-components-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.ProvidersHelpersV2;

    let currentPage = 1;
    let currentPerPage = 20;

    const headers = ['ID', 'Code', 'Name', 'Description', 'Order', 'Status', 'Actions'];
    const rows = ['id', 'code', 'name', 'description', 'display_order', 'is_active', 'actions'];

    const idRenderer = function(value) {
        return '<span class="text-gray-900 dark:text-gray-200">' + value + '</span>';
    };

    const codeRenderer = function(value) {
        return AdminUIComponents.renderCodeBadge(value || '-', { color: 'blue', uppercase: true });
    };

    const nameRenderer = function(value) {
        if (!value) return '<span class="text-gray-400 dark:text-gray-500 italic">N/A</span>';
        return '<span class="font-medium text-gray-900 dark:text-gray-200">' + value + '</span>';
    };

    const descriptionRenderer = function(value) {
        if (!value) return '<span class="text-gray-400 dark:text-gray-500 italic">—</span>';
        const truncated = value.length > 60 ? value.substring(0, 60) + '…' : value;
        return '<span class="text-xs text-gray-600 dark:text-gray-300" title="' + Bridge.Text.escapeHtml(value) + '">' + Bridge.Text.escapeHtml(truncated) + '</span>';
    };

    const sortRenderer = function(value) {
        return AdminUIComponents.renderSortBadge(value, { size: 'md', color: 'indigo' });
    };

    const statusRenderer = function(value, row) {
        const canActive = window.providersCapabilities?.can_active ?? false;
        return AdminUIComponents.renderStatusBadge(value, {
            clickable: canActive,
            entityId: row.id,
            activeText: 'Active',
            inactiveText: 'Inactive',
            buttonClass: 'toggle-status-btn',
            dataAttribute: 'data-provider-id'
        }).replace('data-provider-id', 'data-current-is-active="' + (value ? '1' : '0') + '" data-provider-id');
    };

    const actionsRenderer = function(_, row) {
        const canUpdate = window.providersCapabilities?.can_update ?? false;
        const canUpdateSort = window.providersCapabilities?.can_update_sort ?? false;
        const canDelete = window.providersCapabilities?.can_delete ?? false;

        if (!canUpdate && !canUpdateSort && !canDelete) {
            return '<span class="text-gray-400 text-xs">No actions</span>';
        }

        const actions = [];

        if (canUpdate) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'edit-provider-btn',
                icon: AdminUIComponents.SVGIcons.edit,
                text: 'Edit',
                color: 'blue',
                entityId: row.id,
                title: 'Edit provider',
                dataAttributes: {
                    'provider-id': row.id,
                    'current-code': row.code || '',
                    'current-name': row.name || '',
                    'current-description': row.description || '',
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
                dataAttributes: { 'provider-id': row.id, 'current-sort': row.display_order }
            }));
        }

        if (canDelete) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'delete-provider-btn',
                icon: AdminUIComponents.SVGIcons.delete,
                text: 'Delete',
                color: 'red',
                entityId: row.id,
                title: 'Delete provider',
                dataAttributes: { 'provider-id': row.id }
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
        const globalSearch = Bridge.DOM.value('#providers-search', '').trim();

        const columnFilters = Bridge.Form.omitEmpty({
            // id: Bridge.DOM.value('#filter-id', '').trim(),
            code: Bridge.DOM.value('#filter-code', '').trim(),
            name: Bridge.DOM.value('#filter-name', '').trim(),
            is_active: Bridge.DOM.value('#filter-status', '')
        });

        if (globalSearch || Object.keys(columnFilters).length > 0) {
            params.search = {};
            if (globalSearch) params.search.global = globalSearch;
            if (Object.keys(columnFilters).length > 0) params.search.columns = columnFilters;
        }

        return params;
    }

    async function loadProviders(pageNumber, perPageNumber) {
        if (pageNumber !== null && pageNumber !== undefined) currentPage = pageNumber;
        if (perPageNumber !== null && perPageNumber !== undefined) currentPerPage = perPageNumber;

        const params = buildQueryParams();
        const result = await Bridge.API.execute({
            endpoint: 'exchange-rates/providers/query',
            payload: params,
            operation: 'Query Providers',
            method: 'POST',
            showErrorMessage: false
        });

        if (!result.success) {
            const container = document.getElementById('table-container');
            if (container) {
                container.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative"><strong class="font-bold">Error!</strong><span class="block sm:inline">' + (result.error || 'Failed to load providers.') + '</span></div>';
            }
            Bridge.UI.error(result.error || 'Failed to load providers.');
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
                code: codeRenderer,
                name: nameRenderer,
                description: descriptionRenderer,
                display_order: sortRenderer,
                is_active: statusRenderer,
                actions: actionsRenderer
            }, null, getPaginationInfo);
        } catch (error) {
            Bridge.UI.error('Failed to render table: ' + error.message);
        }
    }

    function setupSearchAndFilters() {
        const resetPageAndReload = Helpers?.bindResetPageReload
            ? Helpers.bindResetPageReload({
                setPage: function(page) { currentPage = page; },
                reload: function() { return loadProviders(); }
            })
            : function() {
                currentPage = 1;
                return loadProviders();
            };

        Bridge.Events.bindDebouncedInput({
            input: '#providers-search',
            delay: 500,
            eventName: 'input',
            onFire: resetPageAndReload
        });

        const searchBtn = document.getElementById('providers-search-btn');
        if (searchBtn) searchBtn.addEventListener('click', resetPageAndReload);

        const clearBtn = document.getElementById('providers-clear-search');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                const input = document.getElementById('providers-search');
                if (input) input.value = '';
                resetPageAndReload();
            });
        }

        Bridge.Events.bindFilterForm({
            form: '#providers-filter-form',
            resetButton: '#providers-reset-filters',
            onSubmit: resetPageAndReload,
            onReset: resetPageAndReload
        });

        const filterFormSelects = document.querySelectorAll('#providers-filter-form select');
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
                reload: function() { return loadProviders(currentPage, currentPerPage); }
            });
        }
    }

    function init() {
        setupSearchAndFilters();
        loadProviders();

        window.loadProvidersV2 = loadProviders;
        window.reloadProvidersTableV2 = function() {
            return loadProviders(currentPage, currentPerPage);
        };

        // Delegated click handler for Edit button
        Bridge.Events.onClick('.edit-provider-btn', function(event, btn) {
            event.preventDefault();
            const providerId = btn.getAttribute('data-provider-id') || btn.getAttribute('data-entity-id');
            if (!providerId) return;

            const modals = window.ProvidersModalsV2;
            if (modals && typeof modals.openEditProviderModal === 'function') {
                modals.openEditProviderModal(providerId, btn);
            } else {
                console.warn('[Providers] ProvidersModalsV2 not ready yet.');
            }
        });
    }

    window.ProvidersCoreV2 = {
        loadProviders,
        buildQueryParams
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
