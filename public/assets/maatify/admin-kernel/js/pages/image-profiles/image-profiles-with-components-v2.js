/**
 * 🖼️ Image Profiles Management Core V2
 */

(function() {
    'use strict';

    if (typeof AdminUIComponents === 'undefined' || !window.AdminPageBridge) {
        console.error('❌ Missing dependencies for image-profiles-with-components-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.ImageProfilesHelpersV2;

    let currentPage = 1;
    let currentPerPage = 20;

    const headers = ['ID', 'Code', 'Display Name', 'Dimensions', 'Max Size', 'Transparency', 'Status', 'Actions'];
    const rows = ['id', 'code', 'display_name', 'dimensions', 'max_size_bytes', 'requires_transparency', 'is_active', 'actions'];

    const codeRenderer = function(value) {
        return AdminUIComponents.renderCodeBadge(value || '-', { color: 'blue', uppercase: false });
    };

    const dimensionsRenderer = function(_, row) {
        const minW = row.min_width ?? '-';
        const minH = row.min_height ?? '-';
        const maxW = row.max_width ?? '-';
        const maxH = row.max_height ?? '-';
        return '<span class="text-xs text-gray-700 dark:text-gray-200">Min: ' + minW + '×' + minH + '<br>Max: ' + maxW + '×' + maxH + '</span>';
    };

    const maxSizeRenderer = function(value) {
        if (value === null || value === undefined || value === '') {
            return '<span class="text-gray-400 dark:text-gray-500 italic">N/A</span>';
        }
        return '<span class="text-xs text-gray-800 dark:text-gray-100">' + value + ' B</span>';
    };

    const transparencyRenderer = function(value) {
        return value
            ? '<span class="inline-flex px-2 py-1 rounded-full text-xs bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">Required</span>'
            : '<span class="inline-flex px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">Optional</span>';
    };

    const statusRenderer = function(value, row) {
        const canActive = window.imageProfilesCapabilities?.can_active ?? false;
        return AdminUIComponents.renderStatusBadge(value, {
            clickable: canActive,
            entityId: row.id,
            activeText: 'Active',
            inactiveText: 'Inactive',
            buttonClass: 'toggle-status-btn',
            dataAttribute: 'data-profile-id'
        }).replace('data-profile-id', 'data-current-is-active="' + (value ? '1' : '0') + '" data-profile-id');
    };

    const actionsRenderer = function(_, row) {
        const canUpdate = window.imageProfilesCapabilities?.can_update ?? false;
        if (!canUpdate) return '<span class="text-gray-400 text-xs">No actions</span>';

        return '<div class="flex flex-wrap gap-1">' + AdminUIComponents.buildActionButton({
            cssClass: 'edit-profile-btn',
            icon: AdminUIComponents.SVGIcons.edit,
            text: 'Edit',
            color: 'blue',
            entityId: row.id,
            title: 'Edit image profile',
            dataAttributes: { 'profile-id': row.id }
        }) + '</div>';
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
        const globalSearch = Bridge.DOM.value('#image-profiles-search', '').trim();

        const columnFilters = Bridge.Form.omitEmpty({
            id: Bridge.DOM.value('#filter-id', '').trim(),
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

    async function loadProfiles(pageNumber, perPageNumber) {
        if (pageNumber !== null && pageNumber !== undefined) currentPage = pageNumber;
        if (perPageNumber !== null && perPageNumber !== undefined) currentPerPage = perPageNumber;

        const params = buildQueryParams();
        const result = await Bridge.API.execute({
            endpoint: 'image-profiles/query',
            payload: params,
            operation: 'Query Image Profiles',
            method: 'POST',
            showErrorMessage: false
        });

        if (!result.success) {
            const container = document.getElementById('table-container');
            if (container) {
                container.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative"><strong class="font-bold">Error!</strong><span class="block sm:inline">' + (result.error || 'Failed to load image profiles.') + '</span></div>';
            }
            Bridge.UI.error(result.error || 'Failed to load image profiles.');
            return;
        }

        const data = result.data || {};
        let items = Array.isArray(data.data) ? data.data : [];

        const transparencyFilter = Bridge.DOM.value('#filter-transparency', '');
        if (transparencyFilter !== '') {
            const boolValue = transparencyFilter === '1';
            items = items.filter(function(item) {
                return !!item.requires_transparency === boolValue;
            });
        }

        const paginationInfo = data.pagination || { page: params.page, per_page: params.per_page, total: items.length, filtered: items.length };
        currentPage = Bridge.normalizeInt(paginationInfo.page, currentPage) || currentPage;
        currentPerPage = Bridge.normalizeInt(paginationInfo.per_page, currentPerPage) || currentPerPage;

        TableComponent(items, headers, rows, paginationInfo, '', false, 'id', null, {
            code: codeRenderer,
            dimensions: dimensionsRenderer,
            max_size_bytes: maxSizeRenderer,
            requires_transparency: transparencyRenderer,
            is_active: statusRenderer,
            actions: actionsRenderer
        }, null, getPaginationInfo);
    }

    function setupSearchAndFilters() {
        const resetPageAndReload = Helpers?.bindResetPageReload
            ? Helpers.bindResetPageReload({
                setPage: function(page) { currentPage = page; },
                reload: function() { return loadProfiles(); }
            })
            : function() {
                currentPage = 1;
                return loadProfiles();
            };

        Bridge.Events.bindDebouncedInput({
            input: '#image-profiles-search',
            delay: 500,
            eventName: 'input',
            onFire: resetPageAndReload
        });

        const searchBtn = document.getElementById('image-profiles-search-btn');
        if (searchBtn) searchBtn.addEventListener('click', resetPageAndReload);

        const clearBtn = document.getElementById('image-profiles-clear-search');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                const input = document.getElementById('image-profiles-search');
                if (input) input.value = '';
                resetPageAndReload();
            });
        }

        Bridge.Events.bindFilterForm({
            form: '#image-profiles-filter-form',
            resetButton: '#image-profiles-reset-filters',
            onSubmit: resetPageAndReload,
            onReset: resetPageAndReload
        });

        const filterFormSelects = document.querySelectorAll('#image-profiles-filter-form select');
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
                reload: function() { return loadProfiles(currentPage, currentPerPage); }
            });
        }
    }

    async function init() {
        setupSearchAndFilters();
        await loadProfiles();

        window.loadImageProfilesV2 = loadProfiles;
        window.reloadImageProfilesTableV2 = function() {
            return loadProfiles(currentPage, currentPerPage);
        };

        // Delegated click handler for Edit button — works on dynamically rendered rows
        Bridge.Events.onClick('.edit-profile-btn', function(event, btn) {
            event.preventDefault();
            const profileId = btn.getAttribute('data-profile-id') || btn.getAttribute('data-entity-id');
            if (!profileId) return;

            const modals = window.ImageProfilesModalsV2;
            if (modals && typeof modals.openEditModal === 'function') {
                modals.openEditModal(profileId);
            } else {
                console.warn('[ImageProfiles] ImageProfilesModalsV2 not ready yet.');
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
