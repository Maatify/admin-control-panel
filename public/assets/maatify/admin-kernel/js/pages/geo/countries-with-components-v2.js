/**
 * 🌍 Countries Management Core V2 (Bridge-first)
 */

(function() {
    'use strict';

    const queryString = window.location.search.replaceAll("_", "-");
    const urlParams = new URLSearchParams(queryString);

    const firstValue = urlParams.keys().next().value
    const filterValue = urlParams.get(firstValue) || '';
    filterValue ? document.getElementById("filter-" + firstValue).value = filterValue : '';

    console.log('🌍 Countries Module V2 Initialized');

    if (typeof AdminUIComponents === 'undefined' || !window.AdminPageBridge) {
        console.error('❌ Missing dependencies for countries-with-components-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.CountriesHelpersV2;

    let currentPage = 1;
    let currentPerPage = 20;

    const headers = ['ID', 'Name', 'Code', 'currency', 'phone code', 'Order', 'Status', 'Actions'];
    const rows = ['id', 'name', 'code', 'currency', 'phone_code', 'display_order', 'is_active', 'actions'];

    const idRenderer = function(value) {
        const canViewCities = window.countriesCapabilities?.can_view_cities ?? true;
        if (!canViewCities) return '<span class="text-gray-900 dark:text-gray-200">' + value + '</span>';
        return '<a href="/geo/countries/' + value + '/cities" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">' + value + '</a>';
    };

    const nameRenderer = function(value) {
        if (!value) return '<span class="text-gray-400 dark:text-gray-100 italic">N/A</span>';
        return '<span class="font-medium text-gray-900 dark:text-gray-200">' + value + '</span>';
    };

    const codeRenderer = function(value) {
        return AdminUIComponents.renderCodeBadge(value, { color: 'blue', uppercase: true });
    };

    const currencyRenderer = function(value) {
        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">' + value + '</span>';
    };
    const phoneCodeRenderer = function(value) {
        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-700 dark:text-emerald-200">' + value + '</span>';
    };

    const sortRenderer = function(value) {
        return AdminUIComponents.renderSortBadge(value, { size: 'md', color: 'indigo' });
    };

    const statusRenderer = function(value, row) {
        const canActive = window.countriesCapabilities?.can_active ?? false;
        return AdminUIComponents.renderStatusBadge(value, {
            clickable: canActive,
            entityId: row.id,
            activeText: 'Active',
            inactiveText: 'Inactive',
            buttonClass: 'toggle-status-btn',
            dataAttribute: 'data-country-id'
        }).replace('data-country-id', 'data-current-is-active="' + (value ? '1' : '0') + '" data-country-id');
    };

    const actionsRenderer = function(value, row) {
        const canUpdate = window.countriesCapabilities?.can_update ?? false;
        const canUpdateSort = window.countriesCapabilities?.can_update_sort ?? false;
        const canViewTranslations = window.countriesCapabilities?.can_view_country_translations ?? false;
        const canViewCities = window.countriesCapabilities?.can_view_cities ?? true; // Assuming true or check capability

        if (!canUpdate && !canUpdateSort && !canViewTranslations && !canViewCities) {
            return '<span class="text-gray-400 text-xs">No actions</span>';
        }

        const actions = [];

        // 🏙️ Cities Link
        if (canViewCities) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'view-cities-btn',
                icon: AdminUIComponents.SVGIcons.list,
                text: 'Cities',
                color: 'indigo',
                entityId: row.id,
                title: 'View cities in ' + row.name,
                dataAttributes: { 'country-id': row.id }
            }));
        }

        // 🌍 Translations Link
        if (canViewTranslations) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'view-country-translations-btn',
                icon: AdminUIComponents.SVGIcons.link,
                text: 'Translations',
                color: 'purple',
                entityId: row.id,
                title: 'View country translations',
                dataAttributes: { 'country-id': row.id }
            }));
        }

        if (canUpdate) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'edit-country-btn',
                icon: AdminUIComponents.SVGIcons.edit,
                text: 'Edit',
                color: 'blue',
                entityId: row.id,
                title: 'Edit country details',
                dataAttributes: {
                    'country-id': row.id,
                    'current-name': row.name,
                    'current-code': row.code,
                    'current-currency': row.currency || '',
                    'current-phone-code': row.phone_code || '',
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
                dataAttributes: { 'country-id': row.id, 'current-sort': row.display_order }
            }));
        }

        return '<div class="flex flex-wrap gap-1">' + actions.join('') + '</div>';
    };

    function getCountriesPaginationInfo(pagination) {
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
        const globalSearch = Bridge.DOM.value('#countries-search', '').trim();

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

    async function loadCountries(pageNumber, perPageNumber) {
        if (pageNumber !== null && pageNumber !== undefined) currentPage = pageNumber;
        if (perPageNumber !== null && perPageNumber !== undefined) currentPerPage = perPageNumber;

        const params = buildQueryParams();
        const result = await Bridge.API.execute({
            endpoint: 'geo/countries/query',
            payload: params,
            operation: 'Query Countries',
            method: 'POST',
            showErrorMessage: false
        });

        if (!result.success) {
            const container = document.getElementById('table-container');
            if (container) {
                container.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative"><strong class="font-bold">Error!</strong><span class="block sm:inline">' + (result.error || 'Failed to load countries.') + '</span></div>';
            }
            Bridge.UI.error(result.error || 'Failed to load countries.');
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
                currency: currencyRenderer,
                phone_code: phoneCodeRenderer,
                display_order: sortRenderer,
                is_active: statusRenderer,
                actions: actionsRenderer
            }, null, getCountriesPaginationInfo);
        } catch (error) {
            Bridge.UI.error('Failed to render table: ' + error.message);
        }
    }

    function setupActionDelegation() {
        // Cities Link
        Bridge.Events.onClick('.view-cities-btn', function(event, btn) {
            event.preventDefault();
            const countryId = btn.getAttribute('data-country-id');
            if (countryId) window.location.assign('/geo/countries/' + countryId + '/cities');
        });

        // Translations Link
        Bridge.Events.onClick('.view-country-translations-btn', function(event, btn) {
            event.preventDefault();
            const countryId = btn.getAttribute('data-entity-id') || btn.getAttribute('data-country-id');
            if (countryId) window.location.assign('/geo/countries/' + countryId + '/translations');
        });
    }

    function setupSearchAndFilters() {
        const resetPageAndReload = Helpers?.bindResetPageReload
            ? Helpers.bindResetPageReload({
                setPage: function(page) { currentPage = page; },
                reload: function() { return loadCountries(); }
            })
            : function() {
                currentPage = 1;
                return loadCountries();
            };

        Bridge.Events.bindDebouncedInput({
            input: '#countries-search',
            delay: 500,
            eventName: 'input',
            onFire: resetPageAndReload
        });

        Bridge.Events.bindEnterAction({
            input: '#countries-search',
            onEnter: function(_, ctx) {
                resetPageAndReload(ctx.event);
            },
            ignoreInsideForm: false,
            preventDefault: true
        });

        const globalSearchInput = document.getElementById('countries-search');

        const searchBtn = document.getElementById('countries-search-btn');
        if (searchBtn) searchBtn.addEventListener('click', resetPageAndReload);

        const clearBtn = document.getElementById('countries-clear-search');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (globalSearchInput) globalSearchInput.value = '';
                resetPageAndReload();
            });
        }

        Bridge.Events.bindFilterForm({
            form: '#countries-filter-form',
            resetButton: '#countries-reset-filters',
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
                reload: function() { return loadCountries(currentPage, currentPerPage); }
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
                return loadCountries(currentPage, currentPerPage);
            }
        });
    }

    function init() {
        setupSearchAndFilters();
        setupActionDelegation();
        loadCountries();

        const btnCreate = document.getElementById('btn-create-country');
        if (btnCreate && window.countriesCapabilities?.can_create) {
            btnCreate.addEventListener('click', function() {
                if (window.CountriesModalsV2?.openCreateCountryModal) {
                    window.CountriesModalsV2.openCreateCountryModal();
                } else if (typeof window.openCreateCountryModalV2 === 'function') {
                    window.openCreateCountryModalV2();
                }
            });
        }
    }

    window.reloadCountriesTableV2 = function() {
        return loadCountries(currentPage, currentPerPage);
    };

    window.CountriesCoreV2 = {
        loadCountries,
        buildQueryParams
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
