/**
 * I18n Scope+Domain Keys Coverage Page V2
 * =======================================
 * Report-only DataTable + filter/search bindings.
 */
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    if (!window.AdminPageBridge || !window.I18nHelpersV2) {
        console.error('❌ Missing bridge/helpers for i18n_scope_domain_keys_coverage-v2');
        return;
    }

    if (!window.i18nScopeDomainKeysContext) {
        console.error('❌ Missing window.i18nScopeDomainKeysContext');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.I18nHelpersV2;
    const context = window.i18nScopeDomainKeysContext;

    const scopeId = context.scope_id;
    const domainId = context.domain_id;
    const languages = context.languages || [];

    const containerId = 'keys-coverage-table-container';
    const apiUrl = `/api/i18n/scopes/${scopeId}/domains/${domainId}/keys/query`;

    let currentPage = 1;
    let currentPerPage = 25;
    let languageSelect = null;

    if (window.Select2) {
        const languageOptions = [
            { value: '', label: 'All Languages', search: 'all' },
            ...languages.map(function(lang) {
                return {
                    value: String(lang.id),
                    label: `${lang.name} (${lang.code})`,
                    search: lang.code
                };
            })
        ];

        languageSelect = Select2('#translation-filter-language-id', languageOptions, { defaultValue: '' });
    }

    const headers = ['ID', 'Key Part', 'Description', 'Total Languages', 'Missing'];
    const rowKeys = ['id', 'key_part', 'description', 'total_languages', 'missing_count'];

    const customRenderers = {
        key_part: function(data) {
            return `<code class="text-sm font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-blue-600 dark:text-blue-400">${data}</code>`;
        },
        description: function(data) {
            return data || '<span class="text-gray-400 italic">—</span>';
        },
        total_languages: function(data) {
            return `<span class="font-semibold">${data}</span>`;
        },
        missing_count: function(data) {
            if (data > 0) {
                return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">${data} Missing</span>`;
            }
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Complete</span>';
        }
    };

    function getFilters() {
        const filters = {};

        const keyPart = Bridge.DOM.value('#filter-key-part', '');
        if (keyPart) filters.key_part = keyPart;

        let languageId = '';
        if (languageSelect && typeof languageSelect.getValue === 'function') {
            languageId = languageSelect.getValue();
        } else {
            languageId = Bridge.DOM.value('#translation-filter-language-id', '');
        }
        if (languageId) filters.language_id = languageId;

        const missing = Bridge.DOM.value('#filter-missing', '');
        if (missing) filters.missing = missing;

        const langActive = Bridge.DOM.value('#filter-language-is-active', '');
        if (langActive) filters.language_is_active = langActive;

        return filters;
    }

    function getPaginationInfo(pagination) {
        const page = pagination.page || 1;
        const perPage = pagination.per_page || 25;
        const total = pagination.total || 0;
        const filtered = pagination.filtered === undefined ? total : pagination.filtered;

        const startItem = filtered === 0 ? 0 : (page - 1) * perPage + 1;
        const endItem = Math.min(page * perPage, filtered);
        let info = `<span>${startItem} to ${endItem}</span> of <span>${filtered}</span>`;

        if (filtered !== total) {
            info += ` <span class="text-gray-500 dark:text-gray-400">(filtered from ${total} total)</span>`;
        }

        return { total: filtered, info: info };
    }

    function buildParams() {
        const filters = getFilters();
        const globalSearch = Bridge.DOM.value('#keys-search-global', '');
        const params = { page: currentPage, per_page: currentPerPage };
        const search = {};

        if (globalSearch) search.global = globalSearch;
        if (Object.keys(filters).length) search.columns = filters;
        if (Object.keys(search).length) params.search = search;

        return params;
    }

    function loadTable() {
        return Helpers.withTableContainerTarget(containerId, function() {
            return createTable(
                apiUrl,
                buildParams(),
                headers,
                rowKeys,
                false,
                'id',
                null,
                customRenderers,
                null,
                getPaginationInfo
            );
        });
    }

    const resetPageAndReload = Helpers.createResetPageReload({
        setPage: function(page) { currentPage = page; },
        resetPage: 1,
        reload: loadTable
    });

    function bindEvents() {
        Bridge.Events.bindFilterForm({
            form: '#keys-coverage-filter-form',
            resetButton: '#btn-reset-filters',
            onSubmit: resetPageAndReload,
            onReset: function() {
                if (languageSelect) {
                    const input = document.querySelector('#translation-filter-language-id .js-select-input');
                    if (input) input.value = 'All Languages';
                    const wrapper = document.querySelector('#translation-filter-language-id');
                    if (wrapper) wrapper.dataset.value = '';
                }
                resetPageAndReload();
            }
        });

        const searchBtn = document.getElementById('btn-search-global');
        if (searchBtn) searchBtn.addEventListener('click', resetPageAndReload);

        const clearSearchBtn = document.getElementById('btn-clear-search');
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                const searchInputEl = document.getElementById('keys-search-global');
                if (searchInputEl) searchInputEl.value = '';
                resetPageAndReload();
            });
        }

        Bridge.Events.bindEnterAction({
            input: '#keys-search-global',
            onEnter: function(_, ctx) {
                resetPageAndReload(ctx.event);
            },
            ignoreInsideForm: true,
            preventDefault: true
        });

        Helpers.bindTableActionState({
            getState: function() { return { page: currentPage, perPage: currentPerPage }; },
            setState: function(state) {
                currentPage = state.page ?? currentPage;
                currentPerPage = state.perPage ?? currentPerPage;
            },
            buildParams: buildParams,
            sourceContainerId: containerId,
            reload: loadTable
        });
    }

    window.reloadScopeDomainKeysCoverageTableV2 = function() {
        return loadTable();
    };

    bindEvents();
    loadTable();
});
