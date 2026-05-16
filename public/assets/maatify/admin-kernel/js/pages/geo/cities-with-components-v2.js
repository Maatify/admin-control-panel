/**
 * 🌍 Cities Management Core V2 (Bridge-first)
 */

(function() {
    'use strict';

    console.log('🌍 Cities Module V2 Initialized');

    if (typeof AdminUIComponents === 'undefined' || !window.AdminPageBridge) {
        console.error('❌ Missing dependencies for cities-with-components-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.CitiesHelpersV2;

    let currentPage = 1;
    let currentPerPage = 20;

    const headers = ['ID','country ID', 'Name', 'Code',  'Order', 'Status', 'Actions'];
    const rows = ['id','country_id', 'name', 'code',  'display_order', 'is_active', 'actions'];

    const idRenderer = function(value, row) {
        const canView = window.citiesCapabilities?.can_view_city_translations ?? false;
        if (!canView) return '<span class="text-gray-900 dark:text-gray-200">' + value + '</span>';
        return '<a href="/geo/countries/' + row.country_id  + '/cities/' + value + '/translations" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">' + value + '</a>';
    };
    const countryIdRenderer = function(value, row) {
        const canView = window.citiesCapabilities?.can_view_city_translations ?? false;
        if (!canView) return '<span class="text-gray-900 dark:text-gray-200">' + value + '</span>';
        return '<a href="/geo/countries?id=' + row.country_id +'  " class="text-blue-600 dark:text-blue-400 hover:underline font-medium">' + value + '</a>';
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
        const canActive = window.citiesCapabilities?.can_active ?? false;
        return AdminUIComponents.renderStatusBadge(value, {
            clickable: canActive,
            entityId: row.id,
            activeText: 'Active',
            inactiveText: 'Inactive',
            buttonClass: 'toggle-status-btn',
            dataAttribute: 'data-city-id'
        }).replace('data-city-id', 'data-current-is-active="' + (value ? '1' : '0') + '" data-city-id');
    };

    const actionsRenderer = function(value, row) {
        const canUpdate = window.citiesCapabilities?.can_update ?? false;
        const canUpdateSort = window.citiesCapabilities?.can_update_sort ?? false;
        const canViewTranslations = window.citiesCapabilities?.can_view_city_translations ?? false;

        if (!canUpdate && !canUpdateSort && !canViewTranslations) {
            return '<span class="text-gray-400 text-xs">No actions</span>';
        }

        const actions = [];
        if (canViewTranslations) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'view-city-translations-btn',
                icon: AdminUIComponents.SVGIcons.link,
                text: 'Translations',
                color: 'purple',
                entityId: row.id,
                title: 'View city translations',
                dataAttributes: { 'city-id': row.id }
            }));
        }

        if (canUpdate) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'edit-city-btn',
                icon: AdminUIComponents.SVGIcons.edit,
                text: 'Edit',
                color: 'blue',
                entityId: row.id,
                title: 'Edit city details',
                dataAttributes: {
                    'city-id': row.id,
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
                dataAttributes: { 'city-id': row.id, 'current-sort': row.display_order }
            }));
        }

        return '<div class="flex flex-wrap gap-1">' + actions.join('') + '</div>';
    };

    function getCitiesPaginationInfo(pagination) {
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
        const globalSearch = Bridge.DOM.value('#cities-search', '').trim();

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

    async function loadCities(pageNumber, perPageNumber) {
        if (pageNumber !== null && pageNumber !== undefined) currentPage = pageNumber;
        if (perPageNumber !== null && perPageNumber !== undefined) currentPerPage = perPageNumber;

        const params = buildQueryParams();
        const result = await Bridge.API.execute({
            endpoint: 'geo/cities/query',
            payload: params,
            operation: 'Query Cities',
            method: 'POST',
            showErrorMessage: false
        });

        if (!result.success) {
            const container = document.getElementById('table-container');
            if (container) {
                container.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative"><strong class="font-bold">Error!</strong><span class="block sm:inline">' + (result.error || 'Failed to load cities.') + '</span></div>';
            }
            Bridge.UI.error(result.error || 'Failed to load cities.');
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
                country_id: countryIdRenderer,
                name: nameRenderer,
                code: codeRenderer,
                symbol: symbolRenderer,
                display_order: sortRenderer,
                is_active: statusRenderer,
                actions: actionsRenderer
            }, null, getCitiesPaginationInfo);
        } catch (error) {
            Bridge.UI.error('Failed to render table: ' + error.message);
        }
    }

    function setupActionDelegation() {
        Bridge.Events.onClick('.view-city-translations-btn', function(event, btn) {
            event.preventDefault();
            const cityId = btn.getAttribute('data-entity-id') || btn.getAttribute('data-city-id');
            if (cityId) window.location.assign('/geo/countries/' + (window.geoCitiesContext?.country_id || '') + '/cities/' + cityId + '/translations');
        });
    }

    function setupSearchAndFilters() {
        const resetPageAndReload = Helpers?.bindResetPageReload
            ? Helpers.bindResetPageReload({
                setPage: function(page) { currentPage = page; },
                reload: function() { return loadCities(); }
            })
            : function() {
                currentPage = 1;
                return loadCities();
            };

        Bridge.Events.bindDebouncedInput({
            input: '#cities-search',
            delay: 500,
            eventName: 'input',
            onFire: resetPageAndReload
        });

        Bridge.Events.bindEnterAction({
            input: '#cities-search',
            onEnter: function(_, ctx) {
                resetPageAndReload(ctx.event);
            },
            ignoreInsideForm: false,
            preventDefault: true
        });

        const globalSearchInput = document.getElementById('cities-search');

        const searchBtn = document.getElementById('cities-search-btn');
        if (searchBtn) searchBtn.addEventListener('click', resetPageAndReload);

        const clearBtn = document.getElementById('cities-clear-search');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (globalSearchInput) globalSearchInput.value = '';
                resetPageAndReload();
            });
        }

        Bridge.Events.bindFilterForm({
            form: '#cities-filter-form',
            resetButton: '#cities-reset-filters',
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
                reload: function() { return loadCities(currentPage, currentPerPage); }
            });
            return;
        }

        Bridge.Table.bindActionState({
            sourceContainerId: 'table-container',
            getState: function() {
                return buildQueryParams();
            },
            setState: function(next) {
                currentPage = next.page ?? currentPage;
                currentPerPage = next.per_page ?? currentPerPage;
            },
            reload: function() {
                return loadCities(currentPage, currentPerPage);
            }
        });
    }

    function init() {
        setupSearchAndFilters();
        setupActionDelegation();
        loadCities();

        const btnCreate = document.getElementById('btn-create-city');
        if (btnCreate && window.citiesCapabilities?.can_create) {
            btnCreate.addEventListener('click', function() {
                if (window.CitiesModalsV2?.openCreateCityModal) {
                    window.CitiesModalsV2.openCreateCityModal();
                } else if (typeof window.openCreateCityModalV2 === 'function') {
                    window.openCreateCityModalV2();
                }
            });
        }
    }

    window.reloadCitiesTableV2 = function() {
        return loadCities(currentPage, currentPerPage);
    };

    window.CitiesCoreV2 = {
        loadCities,
        buildQueryParams
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
