/**
 * 🌐 I18n Domains Core V2
 */
(function() {
    'use strict';

    console.log('🌐 I18n Domains Core V2 loading...');

    if (typeof AdminUIComponents === 'undefined' || !window.AdminPageBridge || !window.I18nHelpersV2) {
        console.error('❌ Missing dependencies for i18n-domains-core-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.I18nHelpersV2;

    let currentPage = 1;
    let currentPerPage = 25;

    const headers = ['ID', 'Code', 'Name', 'Description', 'Active', 'Sort Order', 'Actions'];
    const rows = ['id', 'code', 'name', 'description', 'is_active', 'sort_order', 'actions'];
    const capabilities = window.i18nDomainsCapabilities || {};

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    const renderers = {
        id: function(value) {
            return '<span class="font-mono text-gray-600 dark:text-gray-400">' + value + '</span>';
        },
        code: function(value) {
            return AdminUIComponents.renderCodeBadge(escapeHtml(value), { color: 'blue', size: 'sm' });
        },
        name: function(value) {
            return '<span class="font-medium text-gray-900 dark:text-gray-100">' + escapeHtml(value) + '</span>';
        },
        description: function(value) {
            if (!value) return '<span class="text-gray-400 dark:text-gray-500 italic text-sm">No description</span>';
            const truncated = value.length > 50 ? value.substring(0, 50) + '...' : value;
            return '<span class="text-gray-700 dark:text-gray-300 text-sm" title="' + escapeHtml(value) + '">' + escapeHtml(truncated) + '</span>';
        },
        is_active: function(value, row) {
            return AdminUIComponents.renderStatusBadge(value, {
                clickable: capabilities.can_set_active,
                entityId: row.id,
                activeText: 'Active',
                inactiveText: 'Inactive',
                buttonClass: 'toggle-active-btn',
                dataAttribute: 'data-domain-id'
            }).replace('data-domain-id', 'data-current-status="' + (value ? '1' : '0') + '" data-domain-id');
        },
        sort_order: function(value) {
            return AdminUIComponents.renderSortBadge(value, { size: 'md', color: 'indigo' });
        },
        actions: function(_, row) {
            const actions = [];

            if (capabilities.can_change_code) {
                actions.push(AdminUIComponents.buildActionButton({
                    cssClass: 'change-code-btn',
                    icon: AdminUIComponents.SVGIcons.tag,
                    text: 'Code',
                    color: 'amber',
                    entityId: row.id,
                    title: 'Change domain code',
                    dataAttributes: { 'domain-id': row.id, 'current-code': row.code }
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
                    dataAttributes: { 'domain-id': row.id }
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
                    dataAttributes: { 'domain-id': row.id, 'current-sort': row.sort_order }
                }));
            }

            if (!actions.length) return '<span class="text-gray-400 dark:text-gray-500 text-xs">No actions</span>';
            return '<div class="flex flex-wrap gap-2">' + actions.join('') + '</div>';
        }
    };

    function getDomainsPaginationInfo(pagination) {
        const page = pagination.page || 1;
        const perPage = pagination.per_page || 25;
        const total = pagination.total || 0;
        const filtered = pagination.filtered === undefined ? total : pagination.filtered;
        const displayCount = filtered || total;
        const startItem = displayCount === 0 ? 0 : (page - 1) * perPage + 1;
        const endItem = Math.min(page * perPage, displayCount);

        let infoText = '<span>' + startItem + ' to ' + endItem + '</span> of <span>' + displayCount + '</span>';
        if (filtered && filtered !== total) {
            infoText += ' <span class="text-gray-500 dark:text-gray-400">(filtered from ' + total + ' total)</span>';
        }

        return { total: displayCount, info: infoText };
    }

    function buildQueryParams() {
        const params = { page: currentPage, per_page: currentPerPage };
        const globalSearch = Bridge.DOM.value('#domains-search', '').trim();

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

    async function loadDomains(pageNumber, perPageNumber) {
        if (pageNumber !== null && pageNumber !== undefined) currentPage = pageNumber;
        if (perPageNumber !== null && perPageNumber !== undefined) currentPerPage = perPageNumber;

        const params = buildQueryParams();
        const result = await Bridge.API.execute({
            endpoint: 'i18n/domains/query',
            payload: params,
            operation: 'Query Domains',
            showErrorMessage: false
        });

        const container = document.getElementById('table-container');
        if (!container) return;

        if (!result.success) {
            Bridge.UI.error(result.error || 'Failed to load domains');
            container.innerHTML = '<div class="p-6 text-center"><div class="text-red-600 dark:text-red-400 text-lg font-semibold mb-2">⚠️ Error Loading Data</div><div class="text-gray-600 dark:text-gray-300 mb-4">Failed to load domains. Please try again.</div></div>';
            return;
        }

        const data = result.data || {};
        const domains = Array.isArray(data.data) ? data.data : [];
        const paginationInfo = data.pagination || { page: params.page, per_page: params.per_page, total: domains.length };

        currentPage = Bridge.normalizeInt(paginationInfo.page, currentPage) || currentPage;
        currentPerPage = Bridge.normalizeInt(paginationInfo.per_page, currentPerPage) || currentPerPage;

        if (typeof TableComponent !== 'function') {
            console.error('❌ TableComponent not found');
            return;
        }

        try {
            TableComponent(domains, headers, rows, paginationInfo, '', false, 'id', null, renderers, null, getDomainsPaginationInfo);
        } catch (error) {
            Bridge.UI.error('Failed to render table: ' + error.message);
        }
    }

    function setupSearchAndFilters() {
        const resetPageAndReload = Helpers.createResetPageReload({
            setPage: function(page) { currentPage = page; },
            reload: function() { return loadDomains(); }
        });

        const searchBtn = document.getElementById('domains-search-btn');
        if (searchBtn) searchBtn.addEventListener('click', resetPageAndReload);

        const clearBtn = document.getElementById('domains-clear-search');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                Bridge.DOM.setValue('#domains-search', '');
                resetPageAndReload();
            });
        }

        Bridge.Events.bindEnterAction({
            input: '#domains-search',
            onEnter: function(_, ctx) {
                resetPageAndReload(ctx.event);
            },
            ignoreInsideForm: true,
            preventDefault: true
        });

        Bridge.Events.bindFilterForm({
            form: '#domains-filter-form',
            resetButton: '#domains-reset-filters',
            onSubmit: resetPageAndReload,
            onReset: resetPageAndReload
        });

        Helpers.bindTableActionState({
            buildParams: buildQueryParams,
            sourceContainerId: 'table-container',
            getState: function() { return { page: currentPage, perPage: currentPerPage }; },
            setState: function(state) {
                currentPage = state.page ?? currentPage;
                currentPerPage = state.perPage ?? currentPerPage;
            },
            reload: function() { return loadDomains(currentPage, currentPerPage); }
        });
    }

    function init() {
        setupSearchAndFilters();

        window.changePage = function(page) {
            loadDomains(page, null);
        };

        window.changePerPage = function(perPage) {
            currentPage = 1;
            loadDomains(1, perPage);
        };

        window.reloadDomainsTableV2 = function() {
            return loadDomains(currentPage, currentPerPage);
        };

        window.DomainsCoreV2 = {
            loadDomains: loadDomains,
            buildQueryParams: buildQueryParams
        };

        loadDomains();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
