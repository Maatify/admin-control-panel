/**
 * 🎨 Website UI Themes Management Core V2
 */

(function() {
    'use strict';

    if (typeof AdminUIComponents === 'undefined' || !window.AdminPageBridge) {
        console.error('❌ Missing dependencies for website-ui-themes-with-components-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.WebsiteUiThemesHelpersV2;

    let currentPage = 1;
    let currentPerPage = 20;

    const headers = ['ID', 'Entity Type', 'Theme File', 'Display Name', 'Actions'];
    const rows = ['id', 'entity_type', 'theme_file', 'display_name', 'actions'];

    const entityTypeRenderer = function(value) {
        return AdminUIComponents.renderCodeBadge(value || '-', { color: 'blue', uppercase: false });
    };

    const themeFileRenderer = function(value) {
        return '<span class="text-xs text-gray-800 dark:text-gray-100">' + (value || '-') + '</span>';
    };

    const actionsRenderer = function(_, row) {
        const canUpdate = window.websiteUiThemesCapabilities?.can_update ?? false;
        const canDelete = window.websiteUiThemesCapabilities?.can_delete ?? false;
        if (!canUpdate && !canDelete) return '<span class="text-gray-400 text-xs">No actions</span>';

        let actions = '<div class="flex flex-wrap gap-1">';
        if (canUpdate) {
            actions += AdminUIComponents.buildActionButton({
                cssClass: 'edit-theme-btn',
                icon: AdminUIComponents.SVGIcons.edit,
                text: 'Edit',
                color: 'blue',
                entityId: row.id,
                title: 'Edit website UI theme',
                dataAttributes: { 'theme-id': row.id }
            });
        }
        if (canDelete) {
            actions += AdminUIComponents.buildActionButton({
                cssClass: 'delete-theme-btn',
                icon: AdminUIComponents.SVGIcons.trash,
                text: 'Delete',
                color: 'red',
                entityId: row.id,
                title: 'Delete website UI theme',
                dataAttributes: { 'theme-id': row.id }
            });
        }

        return actions + '</div>';
    };

    function getPaginationInfo(pagination) {
        const page = pagination.page || 1;
        const perPage = pagination.per_page || 20;
        const total = pagination.total || 0;
        const filtered = pagination.filtered === undefined ? total : pagination.filtered;
        const displayCount = filtered || total;
        const startItem = displayCount === 0 ? 0 : (page - 1) * perPage + 1;
        const endItem = Math.min(page * perPage, displayCount);

        return {
            total: displayCount,
            info: '<span>' + startItem + ' to ' + endItem + '</span> of <span>' + displayCount + '</span>'
        };
    }

    function buildQueryParams() {
        const params = { page: currentPage, per_page: currentPerPage };
        const globalSearch = Bridge.DOM.value('#website-ui-themes-search', '').trim();

        const columnFilters = Bridge.Form.omitEmpty({
            id: Bridge.DOM.value('#filter-id', '').trim(),
            entity_type: Bridge.DOM.value('#filter-entity-type', '').trim(),
            theme_file: Bridge.DOM.value('#filter-theme-file', '').trim()
        });

        if (globalSearch || Object.keys(columnFilters).length > 0) {
            params.search = {};
            if (globalSearch) params.search.global = globalSearch;
            if (Object.keys(columnFilters).length > 0) params.search.columns = columnFilters;
        }

        return params;
    }

    async function loadThemes(pageNumber, perPageNumber) {
        if (pageNumber !== null && pageNumber !== undefined) currentPage = pageNumber;
        if (perPageNumber !== null && perPageNumber !== undefined) currentPerPage = perPageNumber;

        const params = buildQueryParams();
        const result = await Bridge.API.execute({
            endpoint: 'website-ui-themes/query',
            payload: params,
            operation: 'Query Website UI Themes',
            method: 'POST',
            showErrorMessage: false
        });

        if (!result.success) {
            Bridge.UI.error(result.error || 'Failed to load website UI themes.');
            return;
        }

        const data = result.data || {};
        const items = Array.isArray(data.data) ? data.data : [];
        const paginationInfo = data.pagination || { page: params.page, per_page: params.per_page, total: items.length, filtered: items.length };
        currentPage = Bridge.normalizeInt(paginationInfo.page, currentPage) || currentPage;
        currentPerPage = Bridge.normalizeInt(paginationInfo.per_page, currentPerPage) || currentPerPage;

        TableComponent(items, headers, rows, paginationInfo, '', false, 'id', null, {
            entity_type: entityTypeRenderer,
            theme_file: themeFileRenderer,
            actions: actionsRenderer
        }, null, getPaginationInfo);
    }

    function setupSearchAndFilters() {
        const resetPageAndReload = Helpers.bindResetPageReload({
            setPage: function(page) { currentPage = page; },
            reload: function() { return loadThemes(); }
        });

        Bridge.Events.bindDebouncedInput({
            input: '#website-ui-themes-search',
            delay: 500,
            eventName: 'input',
            onFire: resetPageAndReload
        });

        const searchBtn = document.getElementById('website-ui-themes-search-btn');
        if (searchBtn) searchBtn.addEventListener('click', resetPageAndReload);

        const clearBtn = document.getElementById('website-ui-themes-clear-search');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                const input = document.getElementById('website-ui-themes-search');
                if (input) input.value = '';
                resetPageAndReload();
            });
        }

        Bridge.Events.bindFilterForm({
            form: '#website-ui-themes-filter-form',
            resetButton: '#website-ui-themes-reset-filters',
            onSubmit: resetPageAndReload,
            onReset: resetPageAndReload
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
                reload: function() { return loadThemes(currentPage, currentPerPage); }
            });
        }
    }

    async function init() {
        setupSearchAndFilters();
        await loadThemes();

        window.loadWebsiteUiThemesV2 = loadThemes;
        window.reloadWebsiteUiThemesTableV2 = function() {
            return loadThemes(currentPage, currentPerPage);
        };

        Bridge.Events.onClick('.edit-theme-btn', function(event, btn) {
            event.preventDefault();
            const themeId = btn.getAttribute('data-theme-id') || btn.getAttribute('data-entity-id');
            if (!themeId) return;

            const modals = window.WebsiteUiThemesModalsV2;
            if (modals && typeof modals.openEditModal === 'function') {
                modals.openEditModal(themeId);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
