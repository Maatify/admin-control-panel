/**
 * I18n Translations List Management V2
 * Behavior-parity migration of i18n_translations_list.js
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        if (!window.AdminPageBridge || !window.I18nHelpersV2) {
            console.error('❌ Missing AdminPageBridge/I18nHelpersV2 for i18n_translations_list-v2');
            return;
        }

        if (!window.languageTranslationsContext || !window.languageTranslationsContext.language_id) {
            console.error('❌ Missing window.languageTranslationsContext.language_id');
            return;
        }

        if (typeof window.AdminUIComponents === 'undefined') {
            console.error('❌ AdminUIComponents library not found!');
            return;
        }

        const Bridge = window.AdminPageBridge;
        const Helpers = window.I18nHelpersV2;
        const context = window.languageTranslationsContext;
        const capabilities = window.languageTranslationsCapabilities || {};

        const languageId = String(context.language_id);
        const tableContainerId = window.languageTranslationsTableContainerId || 'translations-table-container';

        let currentPage = 1;
        let currentPerPage = 25;

        const headers = ['ID', 'Scope', 'Domain', 'Key Segment', 'Value', 'Last Updated', 'Actions'];
        const rows = ['key_id', 'scope', 'domain', 'key_part', 'value', 'updated_at', 'actions'];

        const escapeHtml = Bridge.Text.escapeHtml;

        const customRenderers = {
            key_id: function(value) {
                return `<span class="font-mono text-gray-600 dark:text-gray-400">${escapeHtml(value)}</span>`;
            },
            scope: function(value) {
                return AdminUIComponents.renderCodeBadge(escapeHtml(value), {
                    color: 'purple',
                    size: 'sm'
                });
            },
            domain: function(value) {
                return AdminUIComponents.renderCodeBadge(escapeHtml(value), {
                    color: 'indigo',
                    size: 'sm'
                });
            },
            key_part: function(value) {
                return `<span class="font-mono text-sm text-blue-600 dark:text-blue-400 break-all">${escapeHtml(value)}</span>`;
            },
            value: function(value) {
                if (!value || value === '') {
                    return '<span class="text-gray-400 dark:text-gray-500 italic text-sm">Not translated</span>';
                }
                const truncated = value.length > 100 ? `${value.substring(0, 100)}...` : value;
                return `<span class="text-gray-800 dark:text-gray-200 text-sm whitespace-pre-wrap" title="${escapeHtml(value)}">${escapeHtml(truncated)}</span>`;
            },
            updated_at: function(value) {
                return AdminUIComponents.formatDate(value, { format: 'relative' });
            },
            actions: function(_, row) {
                const actions = [];

                if (capabilities.can_upsert) {
                    actions.push(AdminUIComponents.buildActionButton({
                        cssClass: 'edit-translation-btn',
                        icon: AdminUIComponents.SVGIcons?.edit || '✎',
                        text: 'Edit',
                        color: 'blue',
                        entityId: row.key_id,
                        title: 'Edit translation',
                        dataAttributes: {
                            'key-part': row.key_part,
                            scope: row.scope,
                            domain: row.domain,
                            'current-value': row.value || ''
                        }
                    }));
                }

                if (capabilities.can_delete && row.value) {
                    actions.push(AdminUIComponents.buildActionButton({
                        cssClass: 'delete-translation-btn',
                        icon: AdminUIComponents.SVGIcons?.delete || '🗑',
                        text: 'Clear',
                        color: 'red',
                        entityId: row.key_id,
                        title: 'Clear translation',
                        dataAttributes: {
                            'key-part': row.key_part
                        }
                    }));
                }

                if (!actions.length) {
                    return '<span class="text-gray-400 dark:text-gray-500 text-xs">No actions</span>';
                }

                return `<div class="flex flex-wrap gap-2">${actions.join('')}</div>`;
            }
        };

        function getFilters() {
            const filters = {};

            const filterId = Bridge.DOM.value('#translation-filter-id', '').trim();
            if (filterId) filters.id = filterId;

            const filterScope = Bridge.DOM.value('#translation-filter-scope', '').trim();
            if (filterScope) filters.scope = filterScope;

            const filterDomain = Bridge.DOM.value('#translation-filter-domain', '').trim();
            if (filterDomain) filters.domain = filterDomain;

            const filterKeyPart = Bridge.DOM.value('#translation-filter-key-part', '').trim();
            if (filterKeyPart) filters.key_part = filterKeyPart;

            const filterValue = Bridge.DOM.value('#translation-filter-value', '').trim();
            if (filterValue) filters.value = filterValue;

            return filters;
        }

        function buildParams() {
            const params = {
                page: currentPage,
                per_page: currentPerPage
            };

            const filters = getFilters();
            const globalSearch = Bridge.DOM.value('#translation-search-global', '').trim();
            const search = {};

            if (globalSearch) search.global = globalSearch;
            if (Object.keys(filters).length > 0) search.columns = filters;
            if (Object.keys(search).length > 0) params.search = search;

            return params;
        }

        function getPaginationInfo(pagination) {
            const page = pagination.page || 1;
            const perPage = pagination.per_page || 25;
            const total = pagination.total || 0;
            const filtered = pagination.filtered !== undefined ? pagination.filtered : total;
            const displayCount = filtered || total;
            const startItem = displayCount === 0 ? 0 : (page - 1) * perPage + 1;
            const endItem = Math.min(page * perPage, displayCount);

            let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;
            if (filtered && filtered !== total) {
                infoText += ` <span class="text-gray-500 dark:text-gray-400">(filtered from ${total} total)</span>`;
            }

            return { total: displayCount, info: infoText };
        }

        function renderErrorState(container, result) {
            let errorHtml = `
                <div class="p-6 text-center border border-red-200 bg-red-50 rounded-lg dark:bg-red-900/20 dark:border-red-800">
                    <div class="text-red-600 dark:text-red-400 text-lg font-semibold mb-2">⚠️ Error Loading Data</div>
                    <div class="text-gray-600 dark:text-gray-300 mb-4">${escapeHtml(result.error || 'Failed to load translations')}</div>
            `;

            if (result.rawBody) {
                errorHtml += `
                    <details class="mt-4 text-left">
                        <summary class="cursor-pointer text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                            📄 Show Raw Response
                        </summary>
                        <pre class="mt-2 p-4 bg-gray-100 dark:bg-gray-800 rounded text-xs overflow-auto max-h-96 text-left">${escapeHtml(result.rawBody)}</pre>
                    </details>
                `;
            }

            errorHtml += `
                    <button onclick="window.reloadTranslationsTable()" class="mt-6 px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        🔄 Retry
                    </button>
                </div>
            `;

            container.innerHTML = errorHtml;
        }

        async function loadTranslations() {
            const result = await Bridge.API.execute({
                endpoint: `languages/${languageId}/translations/query`,
                payload: buildParams(),
                operation: 'Query Translations',
                method: 'POST',
                showErrorMessage: false
            });

            const container = document.getElementById(tableContainerId);
            if (!container) return;

            if (!result.success) {
                renderErrorState(container, result.raw || result);
                return;
            }

            const data = result.data || {};
            const translations = Array.isArray(data.data) ? data.data : [];
            const paginationInfo = data.pagination || {
                page: currentPage,
                per_page: currentPerPage,
                total: translations.length
            };

            return Helpers.withTableContainerTarget(tableContainerId, function() {
                return TableComponent(
                    translations,
                    headers,
                    rows,
                    paginationInfo,
                    '',
                    false,
                    'key_id',
                    null,
                    customRenderers,
                    null,
                    getPaginationInfo
                );
            });
        }

        function openEditModal(keyId, keyPart, scope, domain, currentValue) {
            const modalId = 'edit-translation-modal';
            const existingModal = document.getElementById(modalId);
            if (existingModal) existingModal.remove();

            const content = `
                <form id="edit-translation-form" class="space-y-4">
                    <input type="hidden" name="key_id" value="${keyId}">

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Scope</label>
                            <div class="px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm font-mono text-gray-800 dark:text-gray-200">
                                ${escapeHtml(scope)}
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Domain</label>
                            <div class="px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm font-mono text-gray-800 dark:text-gray-200">
                                ${escapeHtml(domain)}
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Key Segment</label>
                        <input type="text" value="${escapeHtml(keyPart)}" disabled
                               class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-500 dark:text-gray-400 cursor-not-allowed font-mono text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Translation Value <span class="text-red-500">*</span></label>
                        <textarea name="value" rows="4" required
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Enter translation...">${escapeHtml(currentValue)}</textarea>
                    </div>
                </form>
            `;

            const footer = AdminUIComponents.buildModalFooter({
                submitText: 'Save Translation',
                submitColor: 'blue'
            });

            const modalHtml = AdminUIComponents.buildModalTemplate({
                id: modalId,
                title: 'Edit Translation',
                content,
                footer,
                icon: AdminUIComponents.SVGIcons?.edit || '✎'
            });

            document.body.insertAdjacentHTML('beforeend', modalHtml);

            const modal = document.getElementById(modalId);
            modal.classList.remove('hidden');
            Helpers.wireModalDismiss(modal, { removeOnClose: true });

            const form = document.getElementById('edit-translation-form');
            const submitBtn = modal.querySelector('button[type="submit"]');
            if (!submitBtn) return;

            submitBtn.addEventListener('click', async function(event) {
                event.preventDefault();

                const formData = new FormData(form);
                const value = String(formData.get('value') || '').trim();
                if (!value) {
                    Bridge.UI.error('Translation value is required');
                    return;
                }

                const payload = {
                    key_id: parseInt(formData.get('key_id'), 10),
                    value
                };

                const result = await Bridge.API.execute({
                    endpoint: `languages/${languageId}/translations/upsert`,
                    payload,
                    operation: 'Upsert Translation',
                    method: 'POST',
                    showErrorMessage: false
                });

                if (!result.success) {
                    Bridge.UI.error(result.error || 'Failed to save translation');
                    return;
                }

                Bridge.UI.success('Translation saved successfully');
                modal.remove();
                loadTranslations();
            });
        }

        async function handleDelete(keyId, keyPart) {
            if (!window.confirm(`Are you sure you want to clear the translation for "${keyPart}"?`)) return;

            const result = await Bridge.API.execute({
                endpoint: `languages/${languageId}/translations/delete`,
                payload: { key_id: parseInt(keyId, 10) },
                operation: 'Delete Translation',
                method: 'POST',
                showErrorMessage: false
            });

            if (!result.success) {
                Bridge.UI.error(result.error || 'Failed to clear translation');
                return;
            }

            Bridge.UI.success('Translation cleared successfully');
            loadTranslations();
        }

        const resetPageAndReload = Helpers.createResetPageReload({
            setPage: function(page) { currentPage = page; },
            resetPage: 1,
            reload: loadTranslations
        });

        function bindEvents() {
            Bridge.Events.onClick('.edit-translation-btn', function(_, target) {
                const keyId = target.dataset.entityId;
                const keyPart = target.dataset.keyPart;
                const scope = target.dataset.scope;
                const domain = target.dataset.domain;
                const currentValue = target.dataset.currentValue;
                if (keyId) openEditModal(keyId, keyPart, scope, domain, currentValue);
            });

            Bridge.Events.onClick('.delete-translation-btn', function(_, target) {
                const keyId = target.dataset.entityId;
                const keyPart = target.dataset.keyPart;
                if (keyId) handleDelete(keyId, keyPart);
            });

            Bridge.Events.bindFilterForm({
                form: '#translations-filter-form',
                resetButton: '#btn-reset-filters',
                onSubmit: resetPageAndReload,
                onReset: resetPageAndReload
            });

            const filterSearchBtn = document.getElementById('btn-filter-search');
            if (filterSearchBtn) filterSearchBtn.addEventListener('click', resetPageAndReload);

            const searchBtn = document.getElementById('btn-search-global');
            if (searchBtn) searchBtn.addEventListener('click', resetPageAndReload);

            const clearSearchBtn = document.getElementById('btn-clear-search');
            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', function() {
                    Bridge.DOM.setValue('#translation-search-global', '');
                    resetPageAndReload();
                });
            }

            Bridge.Events.bindEnterAction({
                input: '#translation-search-global',
                onEnter: function(_, ctx) { resetPageAndReload(ctx.event); },
                ignoreInsideForm: true,
                preventDefault: true
            });

            Helpers.bindTableActionState({
                sourceContainerId: tableContainerId,
                getParams: function() {
                    return {
                        page: currentPage,
                        per_page: currentPerPage
                    };
                },
                getState: function() {
                    return {
                        page: currentPage,
                        perPage: currentPerPage
                    };
                },
                setState: function(state) {
                    currentPage = state.page ?? currentPage;
                    currentPerPage = state.perPage ?? currentPerPage;
                },
                reload: loadTranslations
            });
        }

        bindEvents();
        loadTranslations();

        window.reloadLanguageTranslationsTableV2 = function() {
            return loadTranslations();
        };
        window.reloadTranslationsTable = window.reloadLanguageTranslationsTableV2;
    });
})();
