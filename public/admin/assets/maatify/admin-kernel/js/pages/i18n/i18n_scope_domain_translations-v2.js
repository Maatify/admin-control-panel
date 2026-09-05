/**
 * I18n Scope+Domain Translations Page V2
 *
 * Behavior-parity migration of legacy i18n_scope_domain_translations.js
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        if (!window.AdminPageBridge || !window.I18nHelpersV2) {
            console.error('❌ Missing AdminPageBridge/I18nHelpersV2 for i18n_scope_domain_translations-v2');
            return;
        }

        if (!window.i18nScopeDomainTranslationsContext) {
            console.error('❌ Missing window.i18nScopeDomainTranslationsContext');
            return;
        }

        const Bridge = window.AdminPageBridge;
        const Helpers = window.I18nHelpersV2;
        const context = window.i18nScopeDomainTranslationsContext;
        const capabilities = window.ScopeDomainTranslationsCapabilities || {};

        const scopeId = context.scope_id;
        const domainId = context.domain_id;
        const languages = context.languages || [];

        const tableContainerId = window.scopeDomainTranslationsTableContainerId || 'translations-table-container';
        const apiUrl = `/api/i18n/scopes/${scopeId}/domains/${domainId}/translations/query`;

        let currentPage = 1;
        let currentPerPage = 25;
        let languageSelect = null;

        // Select2 init + URL preselect parity
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

            const urlParams = new URLSearchParams(window.location.search);
            const preselectedLanguageId = urlParams.get('language_id') || '';

            languageSelect = Select2('#translation-filter-language-id', languageOptions, {
                defaultValue: preselectedLanguageId
            });
        }

        const headers = ['ID', 'Key ID', 'Key Part', 'Key Description', 'Language', 'Value', 'Actions'];
        const rowKeys = ['id', 'key_id', 'key_part', 'description', 'language_name', 'value', 'actions'];

        const customRenderers = {
            id: function(data) {
                return data ? `<span class="text-gray-500 text-xs font-mono">#${data}</span>` : '<span class="text-gray-300 text-xs italic">null</span>';
            },
            key_id: function(data) {
                return `<span class="text-gray-500 text-xs font-mono">#${data}</span>`;
            },
            key_part: function(data) {
                return `<code class="text-sm font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-blue-600 dark:text-blue-400">${data}</code>`;
            },
            description: function(data) {
                return data ? `<span class="text-sm text-gray-600 dark:text-gray-400">${data}</span>` : '<span class="text-gray-400 italic text-xs">—</span>';
            },
            language_name: function(data, row) {
                const icon = row.language_icon || '';
                return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"><span class="mr-1.5 text-sm">${icon}</span>${data} (${row.language_code})</span>`;
            },
            value: function(data, row) {
                const val = data || '';
                const displayVal = val ? val : '<span class="text-gray-400 italic">Empty</span>';
                const dir = row.language_direction || 'ltr';
                return `<span class="translation-value block" dir="${dir}" data-key-id="${row.key_id}" data-language-id="${row.language_id}">${displayVal}</span>`;
            },
            actions: function(_, row) {
                const actions = [];

                if (capabilities.can_upsert) {
                    const safeValue = (row.value || '').replace(/"/g, '&quot;');
                    actions.push(`
                        <button class="btn-edit-translation text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 mr-2"
                                title="Edit"
                                data-key-id="${row.key_id}"
                                data-language-id="${row.language_id}"
                                data-value="${safeValue}"
                                data-key-part="${row.key_part}"
                                data-language-name="${row.language_name}"
                                data-language-code="${row.language_code}"
                                data-language-direction="${row.language_direction || 'ltr'}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                    `);
                }

                if (capabilities.can_delete && row.id) {
                    actions.push(`
                        <button class="btn-delete-translation text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                title="Delete"
                                data-key-id="${row.key_id}"
                                data-language-id="${row.language_id}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    `);
                }

                return `<div class="flex items-center justify-center">${actions.join('')}</div>`;
            }
        };

        function getFilters() {
            const filters = {};

            const keyId = Bridge.DOM.value('#filter-key-id', '').trim();
            if (keyId) filters.key_id = keyId;

            const keyPart = Bridge.DOM.value('#filter-key-part', '').trim();
            if (keyPart) filters.key_part = keyPart;

            let languageId = '';
            if (languageSelect && typeof languageSelect.getValue === 'function') {
                languageId = languageSelect.getValue();
            } else {
                const el = document.getElementById('translation-filter-language-id');
                if (el && el.tagName === 'SELECT') languageId = el.value;
            }
            if (languageId) filters.language_id = languageId;

            const value = Bridge.DOM.value('#filter-value', '').trim();
            if (value) filters.value = value;

            return filters;
        }

        function buildParams() {
            const params = {
                page: currentPage,
                per_page: currentPerPage
            };

            const filters = getFilters();
            const globalSearch = Bridge.DOM.value('#translations-search-global', '').trim();
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
            const displayCount = filtered;
            const startItem = displayCount === 0 ? 0 : (page - 1) * perPage + 1;
            const endItem = Math.min(page * perPage, displayCount);

            let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;
            if (filtered !== total) {
                infoText += ` <span class="text-gray-500 dark:text-gray-400">(filtered from ${total} total)</span>`;
            }

            return { total: displayCount, info: infoText };
        }

        function loadTable() {
            return Helpers.withTableContainerTarget(tableContainerId, function() {
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
                ).catch(function(err) {
                    console.error('Table creation failed', err);
                });
            });
        }

        const resetPageAndReload = Helpers.createResetPageReload({
            setPage: function(page) { currentPage = page; },
            resetPage: 1,
            reload: loadTable
        });

        function bindEvents() {
            Bridge.Events.bindFilterForm({
                form: '#translations-filter-form',
                resetButton: '#btn-reset-filters',
                onSubmit: resetPageAndReload,
                onReset: function() {
                    if (languageSelect) {
                        Bridge.DOM.setValue('#translation-filter-language-id .js-select-input', 'All Languages');
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
                    Bridge.DOM.setValue('#translations-search-global', '');
                    resetPageAndReload();
                });
            }

            Bridge.Events.bindEnterAction({
                input: '#translations-search-global',
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
                sourceContainerId: tableContainerId,
                reload: loadTable
            });
        }

        const modal = document.getElementById('edit-translation-modal');
        const modalKeyPart = document.getElementById('edit-key-part-display');
        const modalLanguageName = document.getElementById('edit-language-name-display');
        const modalValue = document.getElementById('edit-translation-value');
        const modalKeyId = document.getElementById('edit-key-id');
        const modalLanguageId = document.getElementById('edit-language-id');
        const btnSave = document.getElementById('btn-save-translation');

        function openEditModal(keyId, languageId, currentValue, keyPart, languageName, languageCode, languageDirection) {
            if (!modal) return;

            Bridge.DOM.setValue(modalKeyId, keyId);
            Bridge.DOM.setValue(modalLanguageId, languageId);
            Bridge.DOM.setValue(modalValue, currentValue || '');
            if (modalKeyPart) modalKeyPart.textContent = keyPart;
            if (modalLanguageName) modalLanguageName.textContent = `${languageName} (${languageCode})`;

            if (modalValue) {
                modalValue.setAttribute('dir', languageDirection || 'ltr');
            }

            Bridge.Modal.open(modal);
            if (modalValue && typeof modalValue.focus === 'function') modalValue.focus();
        }

        function closeEditModal() {
            if (!modal) return;
            Bridge.Modal.close(modal);
        }

        if (modal) {
            Helpers.wireModalDismiss(modal);

            if (btnSave) {
                btnSave.addEventListener('click', function() {
                    const keyId = modalKeyId ? modalKeyId.value : '';
                    const languageId = modalLanguageId ? modalLanguageId.value : '';
                    const newValue = modalValue ? modalValue.value : '';

                    if (!newValue || newValue.trim() === '') {
                        ApiHandler.showAlert('warning', 'Value cannot be empty. Use delete to remove translation.');
                        return;
                    }

                    upsertTranslation(keyId, languageId, newValue);
                });
            }
        }

        function handleDelete(keyId, languageId) {
            if (confirm('Are you sure you want to delete this translation?')) {
                deleteTranslation(keyId, languageId);
            }
        }

        async function upsertTranslation(keyId, languageId, value) {
            const endpoint = `languages/${languageId}/translations/upsert`;
            const payload = {
                key_id: keyId,
                value: value
            };

            try {
                const result = await ApiHandler.call(endpoint, payload, 'Upsert Translation');
                if (result.success) {
                    ApiHandler.showAlert('success', 'Translation saved successfully');
                    closeEditModal();
                    loadTable();
                } else {
                    ApiHandler.showAlert('danger', result.error || 'Failed to save translation');
                }
            } catch (error) {
                console.error('Upsert error:', error);
                ApiHandler.showAlert('danger', 'An error occurred while saving');
            }
        }

        async function deleteTranslation(keyId, languageId) {
            const endpoint = `languages/${languageId}/translations/delete`;
            const payload = {
                key_id: keyId
            };

            try {
                const result = await ApiHandler.call(endpoint, payload, 'Delete Translation');
                if (result.success) {
                    ApiHandler.showAlert('success', 'Translation deleted successfully');
                    loadTable();
                } else {
                    ApiHandler.showAlert('danger', result.error || 'Failed to delete translation');
                }
            } catch (error) {
                console.error('Delete error:', error);
                ApiHandler.showAlert('danger', 'An error occurred while deleting');
            }
        }

        document.addEventListener('click', function(e) {
            const editBtn = e.target.closest('.btn-edit-translation');
            if (editBtn) {
                openEditModal(
                    editBtn.dataset.keyId,
                    editBtn.dataset.languageId,
                    editBtn.dataset.value,
                    editBtn.dataset.keyPart,
                    editBtn.dataset.languageName,
                    editBtn.dataset.languageCode,
                    editBtn.dataset.languageDirection
                );
                return;
            }

            const deleteBtn = e.target.closest('.btn-delete-translation');
            if (deleteBtn) {
                handleDelete(deleteBtn.dataset.keyId, deleteBtn.dataset.languageId);
            }
        });

        window.reloadScopeDomainTranslationsTableV2 = function() {
            return loadTable();
        };

        loadTable();
        bindEvents();
    });
})();
