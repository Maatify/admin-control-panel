/**
 * Content Document Translation List V2
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        if (!window.AdminPageBridge || !window.ContentDocumentHelpersV2 || typeof window.AdminUIComponents === 'undefined') {
            console.error('❌ Missing dependencies for content_document_translation_list-v2');
            return;
        }

        const Bridge = window.AdminPageBridge;
        const Helpers = window.ContentDocumentHelpersV2;

        const capabilities = window.contentDocumentTranslationsCapabilities || {};
        const apiEndpoints = window.contentDocumentTranslationsApi || {};
        const typeId = window.typeId;
        const documentId = window.documentId;
        const tableContainerId = window.contentDocumentTranslationsTableContainerId || 'content-document-versions-table-container';

        if (!typeId || !documentId) {
            console.error('❌ Missing required context (typeId/documentId).');
            return;
        }

        const headers = ['Language Name', 'Code', 'Direction', 'Has Translation', 'Updated At', 'Actions'];
        const rows = ['language_name', 'language_code', 'language_direction', 'has_translation', 'updated_at', 'actions'];

        let currentPage = 1;
        let currentPerPage = 25;
        let languageSelect;

        const renderers = {
            language_name: function(value, row) {
                const icon = row.language_icon || '';
                return '<div class="flex items-center"><span class="mr-2 text-lg">' + icon + '</span><span class="font-medium text-gray-900 dark:text-gray-100">' + value + '</span></div>';
            },
            language_code: function(value) {
                return window.AdminUIComponents.renderCodeBadge(value, { color: 'blue' });
            },
            language_direction: function(value) {
                return window.AdminUIComponents.renderDirectionBadge(value);
            },
            has_translation: function(value) {
                return window.AdminUIComponents.renderStatusBadge(value, {
                    activeText: 'Yes',
                    inactiveText: 'No',
                    clickable: false
                });
            },
            updated_at: function(value) {
                if (!value) return '<span class="text-gray-400 dark:text-gray-500 italic text-xs">-</span>';
                return '<span class="text-xs text-gray-600 dark:text-gray-400">' + value + '</span>';
            },
            actions: function(_, row) {
                const buttons = [];
                const translationId = row.translation_id;
                const hasTranslation = row.has_translation;

                if (capabilities.can_view_translation_details) {
                    const actionText = hasTranslation ? 'Edit' : 'Create';
                    const actionIcon = hasTranslation ? window.AdminUIComponents.SVGIcons.edit : window.AdminUIComponents.SVGIcons.plus;
                    const actionColor = hasTranslation ? 'blue' : 'green';

                    const uiUrl = (apiEndpoints.translation_details || '')
                        .replace('{type_id}', typeId)
                        .replace('{document_id}', documentId)
                        .replace('{language_id}', row.language_id);

                    buttons.push(window.AdminUIComponents.buildActionButton({
                        cssClass: '',
                        icon: actionIcon,
                        text: actionText,
                        color: actionColor,
                        entityId: translationId || ('new-' + row.language_id),
                        title: actionText + ' Translation',
                        dataAttributes: { href: uiUrl }
                    }).replace('<button', '<a href="' + uiUrl + '"').replace('</button>', '</a>'));
                }

                return '<div class="flex items-center gap-2 justify-end">' + buttons.join('') + '</div>';
            }
        };

        function buildParams() {
            const params = { page: currentPage, per_page: currentPerPage };
            const search = {};
            const columns = Bridge.Form.omitEmpty({
                has_translation: Bridge.DOM.value('#filter-has-translation', '')
            });

            if (languageSelect) {
                const selectedLang = languageSelect.getValue();
                if (selectedLang) columns.language_id = selectedLang;
            }

            const globalSearch = Bridge.DOM.value('#content-document-versions-search', '').trim();
            if (globalSearch) search.global = globalSearch;
            if (Object.keys(columns).length > 0) search.columns = columns;
            if (Object.keys(search).length > 0) params.search = search;

            return params;
        }

        function loadTranslations() {
            const endpoint = (apiEndpoints.query || '')
                .replace('{type_id}', typeId)
                .replace('{document_id}', documentId);

            return Helpers.withTableContainerTarget(tableContainerId, function() {
                return createTable(endpoint, buildParams(), headers, rows, false, 'language_id', null, renderers, null, null)
                    .catch(function(err) {
                        console.error('Table creation failed', err);
                    });
            });
        }

        function initLanguageDropdown() {
            const context = window.contentDocumentTranslationsContext || {};
            const languages = context.languages || [];
            if (!languages.length) {
                console.warn('⚠️ No languages found in context.');
                return;
            }

            const options = languages.map(function(lang) {
                return { value: String(lang.id), label: lang.name + ' (' + lang.code + ')', search: lang.code };
            });

            languageSelect = Select2('#translation-filter-language-id', options, {
                defaultValue: null,
                onChange: function() {
                    currentPage = 1;
                    loadTranslations();
                }
            });
        }

        const resetPageAndReload = Helpers.createResetPageReload({
            setPage: function(page) { currentPage = page; },
            reload: function() { return loadTranslations(); }
        });

        Bridge.Events.bindFilterForm({
            form: '#content-document-versions-filter-form',
            resetButton: '#content-document-versions-reset-filters',
            onSubmit: resetPageAndReload,
            onReset: function() {
                if (languageSelect) {
                    languageSelect.destroy();
                    initLanguageDropdown();
                }

                const searchInput = document.getElementById('content-document-versions-search');
                if (searchInput) searchInput.value = '';
                resetPageAndReload();
            }
        });

        const searchBtn = document.getElementById('content-document-versions-search-btn');
        if (searchBtn) searchBtn.addEventListener('click', resetPageAndReload);

        Bridge.Events.bindEnterAction({
            input: '#content-document-versions-search',
            onEnter: function(_, ctx) {
                resetPageAndReload(ctx.event);
            },
            ignoreInsideForm: true,
            preventDefault: true
        });

        const searchInput = document.getElementById('content-document-versions-search');

        const clearBtn = document.getElementById('content-document-versions-clear-search');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (searchInput) searchInput.value = '';
                resetPageAndReload();
            });
        }

        Helpers.bindTableActionState({
            buildParams,
            sourceContainerId: tableContainerId,
            getState: function() { return { page: currentPage, perPage: currentPerPage }; },
            setState: function(state) {
                currentPage = state.page ?? currentPage;
                currentPerPage = state.perPage ?? currentPerPage;
            },
            reload: function() { return loadTranslations(); }
        });

        window.reloadContentDocumentTranslationsTableV2 = function() {
            return loadTranslations();
        };

        window.ContentDocumentTranslationListV2 = {
            reload: window.reloadContentDocumentTranslationsTableV2,
            loadTranslations: loadTranslations,
            buildParams: buildParams
        };

        initLanguageDropdown();
        loadTranslations();
    });
})();
