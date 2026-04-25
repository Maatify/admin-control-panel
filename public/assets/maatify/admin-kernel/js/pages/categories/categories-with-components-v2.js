/**
 * 📦 Categories Management Core V2 (Bridge-first)
 */

(function() {
    'use strict';

    console.log('📦 Categories Module V2 Initialized');

    if (typeof AdminUIComponents === 'undefined' || !window.AdminPageBridge) {
        console.error('❌ Missing dependencies for categories-with-components-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.CategoriesHelpersV2;

    let currentPage = 1;
    let currentPerPage = 20;

    const headers = ['ID', 'Name', 'Slug', 'Order', 'Status', 'Actions'];
    const rows    = ['id', 'name', 'slug', 'display_order', 'is_active', 'actions'];

    // ── Renderers ──────────────────────────────────────────────────────────

    const idRenderer = function(value) {
        const canView = window.categoriesCapabilities?.can_view_detail ?? false;
        if (!canView) return '<span class="text-gray-900 dark:text-gray-200">' + value + '</span>';
        return '<a href="/categories/' + value + '" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">' + value + '</a>';
    };

    const nameRenderer = function(value) {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';
        return '<span class="font-medium text-gray-900 dark:text-gray-200">' + value + '</span>';
    };

    const slugRenderer_col = function(value) {
        if (!value) return '<span class="text-gray-400 italic">—</span>';
        return '<code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-gray-700 dark:text-gray-300">' + value + '</code>';
    };

    const sortRenderer = function(value) {
        return AdminUIComponents.renderSortBadge(value, { size: 'md', color: 'indigo' });
    };

    const statusRenderer = function(value, row) {
        const canActive = window.categoriesCapabilities?.can_active ?? false;
        return AdminUIComponents.renderStatusBadge(value, {
            clickable: canActive,
            entityId: row.id,
            activeText: 'Active',
            inactiveText: 'Inactive',
            buttonClass: 'toggle-status-btn',
            dataAttribute: 'data-category-id'
        }).replace('data-category-id', 'data-current-is-active="' + (value ? '1' : '0') + '" data-category-id');
    };

    const actionsRenderer = function(value, row) {
        const canUpdate     = window.categoriesCapabilities?.can_update     ?? false;
        const canUpdateSort = window.categoriesCapabilities?.can_update_sort ?? false;
        const canViewDetail = window.categoriesCapabilities?.can_view_detail ?? false;

        if (!canUpdate && !canUpdateSort && !canViewDetail) {
            return '<span class="text-gray-400 text-xs">No actions</span>';
        }

        const actions = [];

        // Details button — navigates to the category detail page (sub-categories, settings, images)
        // Mirrors the currencies "Translations" button pattern
        if (canViewDetail) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'view-category-detail-btn',
                icon: AdminUIComponents.SVGIcons.view,
                text: 'Details',
                color: 'green',
                entityId: row.id,
                title: 'View category details (sub-categories, settings, images)',
                dataAttributes: { 'category-id': row.id }
            }));
        }

        if (canUpdate) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'edit-category-btn',
                icon: AdminUIComponents.SVGIcons.edit,
                text: 'Edit',
                color: 'blue',
                entityId: row.id,
                title: 'Edit category',
                dataAttributes: {
                    'category-id': row.id,
                    'current-name': row.name,
                    'current-slug': row.slug || '',
                    'current-is-active': row.is_active ? '1' : '0',
                    'current-display-order': row.display_order,
                    'current-parent-id': row.parent_id || '',
                    'current-description': row.description || '',
                    'current-notes': row.notes || ''
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
                dataAttributes: { 'category-id': row.id, 'current-sort': row.display_order, 'parent-id': row.parent_id != null ? row.parent_id : '' }
            }));
        }

        return '<div class="flex flex-wrap gap-1">' + actions.join('') + '</div>';
    };

    // ── Pagination ─────────────────────────────────────────────────────────

    function getCategoriesPaginationInfo(pagination) {
        const page        = pagination.page || 1;
        const perPage     = pagination.per_page || 20;
        const total       = pagination.total || 0;
        const filtered    = pagination.filtered === undefined ? total : pagination.filtered;
        const displayCount = filtered || total;
        const startItem   = displayCount === 0 ? 0 : (page - 1) * perPage + 1;
        const endItem     = Math.min(page * perPage, displayCount);

        let infoText = '<span>' + startItem + ' to ' + endItem + '</span> of <span>' + displayCount + '</span>';
        if (filtered && filtered !== total) {
            infoText += ' <span class="text-gray-500">(filtered from ' + total + ' total)</span>';
        }
        return { total: displayCount, info: infoText };
    }

    // ── Query params ───────────────────────────────────────────────────────

    function buildQueryParams() {
        const params = { page: currentPage, per_page: currentPerPage };
        const globalSearch = Bridge.DOM.value('#categories-search', '').trim();

        const columnFilters = Bridge.Form.omitEmpty({
            id:        Bridge.DOM.value('#filter-id', '').trim(),
            name:      Bridge.DOM.value('#filter-name', '').trim(),
            is_active: Bridge.DOM.value('#filter-status', '')
        });

        if (globalSearch || Object.keys(columnFilters).length > 0) {
            params.search = {};
            if (globalSearch) params.search.global = globalSearch;
            if (Object.keys(columnFilters).length > 0) params.search.columns = columnFilters;
        }

        return params;
    }

    // ── Load table ─────────────────────────────────────────────────────────

    async function loadCategories(pageNumber, perPageNumber) {
        if (pageNumber != null) currentPage = pageNumber;
        if (perPageNumber != null) currentPerPage = perPageNumber;

        const params = buildQueryParams();
        const result = await Bridge.API.execute({
            endpoint: 'categories/query',
            payload: params,
            operation: 'Query Categories',
            method: 'POST',
            showErrorMessage: false
        });

        if (!result.success) {
            const container = document.getElementById('table-container');
            if (container) {
                container.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded"><strong class="font-bold">Error!</strong> ' + (result.error || 'Failed to load categories.') + '</div>';
            }
            Bridge.UI.error(result.error || 'Failed to load categories.');
            return;
        }

        const data       = result.data || {};
        const items      = Array.isArray(data.data) ? data.data : [];
        const pagination = data.pagination || { page: params.page, per_page: params.per_page, total: items.length };

        currentPage    = Bridge.normalizeInt(pagination.page, currentPage)    || currentPage;
        currentPerPage = Bridge.normalizeInt(pagination.per_page, currentPerPage) || currentPerPage;

        try {
            TableComponent(items, headers, rows, pagination, '', false, 'id', null, {
                id:            idRenderer,
                name:          nameRenderer,
                slug:          slugRenderer_col,
                display_order: sortRenderer,
                is_active:     statusRenderer,
                actions:       actionsRenderer
            }, null, getCategoriesPaginationInfo);
        } catch (error) {
            Bridge.UI.error('Failed to render table: ' + error.message);
        }
    }

    // ── Search & filters ───────────────────────────────────────────────────

    function setupSearchAndFilters() {
        const resetPageAndReload = Helpers?.bindResetPageReload
            ? Helpers.bindResetPageReload({
                setPage: function(page) { currentPage = page; },
                reload: function() { return loadCategories(); }
            })
            : function() { currentPage = 1; return loadCategories(); };

        Bridge.Events.bindDebouncedInput({
            input: '#categories-search',
            delay: 500,
            eventName: 'input',
            onFire: resetPageAndReload
        });

        Bridge.Events.bindEnterAction({
            input: '#categories-search',
            onEnter: function(_, ctx) { resetPageAndReload(ctx.event); },
            ignoreInsideForm: false,
            preventDefault: true
        });

        const searchBtn = document.getElementById('categories-search-btn');
        if (searchBtn) searchBtn.addEventListener('click', resetPageAndReload);

        const clearBtn = document.getElementById('categories-clear-search');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                const el = document.getElementById('categories-search');
                if (el) el.value = '';
                resetPageAndReload();
            });
        }

        Bridge.Events.bindFilterForm({
            form: '#categories-filter-form',
            resetButton: '#categories-reset-filters',
            onSubmit: resetPageAndReload,
            onReset: resetPageAndReload
        });

        if (Helpers?.bindTableActionState) {
            Helpers.bindTableActionState({
                getParams: buildQueryParams,
                sourceContainerId: 'table-container',
                getState: function() { return { page: currentPage, perPage: currentPerPage }; },
                setState: function(state) {
                    currentPage    = state.page    ?? currentPage;
                    currentPerPage = state.perPage ?? currentPerPage;
                },
                reload: function() { return loadCategories(currentPage, currentPerPage); }
            });
            return;
        }

        Bridge.Table.bindActionState({
            sourceContainerId: 'table-container',
            getState: function() { return buildQueryParams(); },
            setState: function(next) {
                currentPage    = next.page     ?? currentPage;
                currentPerPage = next.per_page ?? currentPerPage;
            },
            reload: function() { return loadCategories(currentPage, currentPerPage); }
        });
    }

    // ── Action delegation (Detail navigation) ─────────────────────────────

    function setupActionDelegation() {
        Bridge.Events.onClick('.view-category-detail-btn', function(event, btn) {
            event.preventDefault();
            const categoryId = btn.getAttribute('data-entity-id') || btn.getAttribute('data-category-id');
            if (categoryId) window.location.assign('/categories/' + categoryId);
        });
    }

    // ── Init ───────────────────────────────────────────────────────────────

    function init() {
        setupSearchAndFilters();
        setupActionDelegation();
        loadCategories();

        const btnCreate = document.getElementById('btn-create-category');
        if (btnCreate && window.categoriesCapabilities?.can_create) {
            btnCreate.addEventListener('click', function() {
                if (window.CategoriesModalsV2?.openCreateCategoryModal) {
                    window.CategoriesModalsV2.openCreateCategoryModal();
                }
            });
        }
    }

    // ── Public API ─────────────────────────────────────────────────────────

    window.reloadCategoriesTableV2 = function() {
        return loadCategories(currentPage, currentPerPage);
    };

    window.CategoriesCoreV2 = { loadCategories, buildQueryParams };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();






