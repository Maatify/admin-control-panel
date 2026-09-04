/**
 * Content Document Versions List V2
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        if (!window.AdminPageBridge || !window.ContentDocumentHelpersV2 || typeof window.AdminUIComponents === 'undefined') {
            console.error('❌ Missing dependencies for content_document_versions_list-v2');
            return;
        }

        const Bridge = window.AdminPageBridge;
        const Helpers = window.ContentDocumentHelpersV2;
        const capabilities = window.contentDocumentVersionsCapabilities || {};
        const api = window.contentDocumentVersionsApi || {};
        const typeId = window.typeId;

        if (!typeId) {
            console.error('❌ Missing typeId in window context.');
            return;
        }

        const tableContainerId = window.contentDocumentVersionsTableContainerId || 'content-document-versions-table-container';
        let currentPage = 1;
        let currentPerPage = 25;

        const headers = ['ID', 'Version', 'Status', 'Requires Acceptance', 'Published At', 'Created At', 'Actions'];
        const rows = ['id', 'version', 'status', 'requires_acceptance', 'published_at', 'created_at', 'actions'];

        const renderers = {
            id: function(value) {
                return '<span class="font-mono text-sm text-gray-800 dark:text-gray-300 font-medium">#' + value + '</span>';
            },
            version: function(value) {
                return '<span class="font-mono text-sm font-semibold text-blue-600 dark:text-blue-400">' + value + '</span>';
            },
            status: function(_, row) {
                let status = 'draft';
                if (row.archived_at) status = 'archived';
                else if (row.is_active) status = 'active';
                else if (row.published_at) status = 'inactive';

                const styles = {
                    draft: 'bg-gray-100 text-gray-800 border-gray-200',
                    inactive: 'bg-blue-100 text-blue-800 border-blue-200',
                    active: 'bg-green-100 text-green-800 border-green-200',
                    archived: 'bg-red-100 text-red-800 border-red-200'
                };

                const label = status.charAt(0).toUpperCase() + status.slice(1);
                return '<span class="px-2.5 py-0.5 rounded-full text-xs font-medium border ' + styles[status] + '">' + label + '</span>';
            },
            requires_acceptance: function(value) {
                return value
                    ? '<span class="text-green-600 dark:text-green-400 font-medium text-xs">Yes</span>'
                    : '<span class="text-gray-400 dark:text-gray-500 text-xs">No</span>';
            },
            published_at: function(value) {
                if (!value) return '<span class="text-gray-400 dark:text-gray-500 italic text-xs">-</span>';
                return '<span class="text-xs text-gray-600 dark:text-gray-400">' + value + '</span>';
            },
            created_at: function(value) {
                if (!value) return '<span class="text-gray-400 dark:text-gray-500 italic text-xs">-</span>';
                return '<span class="text-xs text-gray-600 dark:text-gray-400">' + value + '</span>';
            },
            actions: function(_, row) {
                const buttons = [];
                const id = row.id;
                let status = 'draft';
                if (row.archived_at) status = 'archived';
                else if (row.is_active) status = 'active';
                else if (row.published_at) status = 'inactive';

                if (capabilities.can_view_translations) {
                    const uiUrl = '/content-document-types/' + typeId + '/documents/' + id + '/translations';
                    buttons.push(window.AdminUIComponents.buildActionButton({
                        cssClass: '',
                        icon: window.AdminUIComponents.SVGIcons.view,
                        text: 'Translations',
                        color: 'gray',
                        entityId: id,
                        title: 'Manage Translations',
                        dataAttributes: { href: uiUrl }
                    }).replace('<button', '<a href="' + uiUrl + '"').replace('</button>', '</a>'));
                }

                if (capabilities.can_publish && status === 'draft') {
                    buttons.push(window.AdminUIComponents.buildActionButton({
                        cssClass: 'btn-publish-v2',
                        icon: window.AdminUIComponents.SVGIcons.check,
                        text: 'Publish',
                        color: 'blue',
                        entityId: id,
                        title: 'Publish',
                        dataAttributes: { version: row.version, action: 'publish' }
                    }));
                }

                if (capabilities.can_activate && status === 'inactive') {
                    buttons.push(window.AdminUIComponents.buildActionButton({
                        cssClass: 'btn-activate-v2',
                        icon: window.AdminUIComponents.SVGIcons.check,
                        text: 'Activate',
                        color: 'green',
                        entityId: id,
                        title: 'Activate',
                        dataAttributes: { version: row.version, action: 'activate' }
                    }));
                }

                if (capabilities.can_deactivate && status === 'active') {
                    buttons.push(window.AdminUIComponents.buildActionButton({
                        cssClass: 'btn-deactivate-v2',
                        icon: window.AdminUIComponents.SVGIcons.x,
                        text: 'Deactivate',
                        color: 'amber',
                        entityId: id,
                        title: 'Deactivate',
                        dataAttributes: { version: row.version, action: 'deactivate' }
                    }));
                }

                if (capabilities.can_archive && status !== 'archived') {
                    buttons.push(window.AdminUIComponents.buildActionButton({
                        cssClass: 'btn-archive-v2',
                        icon: window.AdminUIComponents.SVGIcons.delete,
                        text: 'Archive',
                        color: 'red',
                        entityId: id,
                        title: 'Archive',
                        dataAttributes: { version: row.version, action: 'archive' }
                    }));
                }

                return '<div class="flex items-center gap-2 justify-end">' + buttons.join('') + '</div>';
            }
        };

        function buildParams() {
            const columns = Bridge.Form.omitEmpty({
                document_id: Bridge.DOM.value('#filter-document-id', '').trim(),
                version: Bridge.DOM.value('#filter-version', '').trim(),
                status: Bridge.DOM.value('#filter-status', ''),
                requires_acceptance: Bridge.DOM.value('#filter-requires-acceptance', ''),
                created_from: Bridge.DOM.value('#filter-created-from', ''),
                created_to: Bridge.DOM.value('#filter-created-to', '')
            });

            const params = { page: currentPage, per_page: currentPerPage };
            const globalSearch = Bridge.DOM.value('#content-document-versions-search', '').trim();
            if (globalSearch || Object.keys(columns).length > 0) {
                params.search = {};
                if (globalSearch) params.search.global = globalSearch;
                if (Object.keys(columns).length > 0) params.search.columns = columns;
            }
            return params;
        }

        async function loadVersions() {
            const params = buildParams();
            const endpoint = (api.query || '').replace('{type_id}', typeId);
            await Helpers.withTableContainerTarget(tableContainerId, function() {
                return createTable(endpoint, params, headers, rows, false, 'id', null, renderers, null, null)
                    .catch(function(err) {
                        console.error('Table creation failed', err);
                    });
            });
        }

        const resetPageAndReload = Helpers.createResetPageReload({
            setPage: function(page) { currentPage = page; },
            reload: function() { return loadVersions(); }
        });

        Bridge.Events.bindFilterForm({
            form: '#content-document-versions-filter-form',
            resetButton: '#content-document-versions-reset-filters',
            onSubmit: resetPageAndReload,
            onReset: function() {
                const from = document.getElementById('filter-created-from');
                const to = document.getElementById('filter-created-to');
                if (from && from._flatpickr) from._flatpickr.clear();
                if (to && to._flatpickr) to._flatpickr.clear();
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
            reload: function() { return loadVersions(); }
        });

        function openCreateModal() {
            const modalId = 'create-version-modal-v2';
            const existing = document.getElementById(modalId);
            if (existing) existing.remove();

            const content = '<form id="create-version-form-v2" class="space-y-4">' +
                '<div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Version Number <span class="text-red-500">*</span></label>' +
                '<input type="text" name="version" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500" placeholder="e.g., 1.0.0">' +
                '<p class="text-xs text-gray-500 mt-1">Must be unique for this document type.</p></div>' +
                '<div class="flex items-center gap-2"><input type="checkbox" id="create-requires-acceptance-v2" name="requires_acceptance" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">' +
                '<label for="create-requires-acceptance-v2" class="text-sm font-medium text-gray-700 dark:text-gray-300">Requires Acceptance</label></div>' +
                '</form>';

            const modalHtml = window.AdminUIComponents.buildModalTemplate({
                id: modalId,
                title: 'Create New Version',
                icon: window.AdminUIComponents.SVGIcons.plus,
                content: content,
                footer: window.AdminUIComponents.buildModalFooter({ submitText: 'Create Version', submitColor: 'green' })
            });

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = document.getElementById(modalId);
            Helpers.wireModalDismiss(modal);
            Bridge.Modal.open(modal);

            const submitBtn = modal.querySelector('button[type="submit"]');
            const submitCreate = function(e) {
                e.preventDefault();

                const version = Bridge.DOM.value(modal.querySelector('input[name="version"]'), '').trim();
                if (!version) {
                    Bridge.UI.warning('Version is required');
                    return;
                }

                const payload = {
                    version: version,
                    requires_acceptance: Bridge.DOM.checked('#create-requires-acceptance-v2', false)
                };

                const endpoint = (api.create || '').replace('{type_id}', typeId);
                Bridge.API.runMutation({
                    operation: 'Create Version',
                    endpoint: endpoint,
                    method: 'POST',
                    payload,
                    successMessage: 'Version created successfully',
                    reloadHandler: function() {
                        currentPage = 1;
                        return loadVersions();
                    },
                    onSuccess: function() { modal.remove(); }
                });
            };

            const formEl = modal.querySelector('#create-version-form-v2');
            if (formEl) formEl.addEventListener('submit', submitCreate);
            submitBtn.addEventListener('click', submitCreate);
        }

        const createBtn = document.getElementById('btn-create-content-document-version');
        if (createBtn) createBtn.addEventListener('click', openCreateModal);

        function handleAction(action, id, version, actionName) {
            const endpointTemplate = api[action] || '';
            const endpoint = endpointTemplate.replace('{type_id}', typeId).replace('{document_id}', id);

            Bridge.API.runMutation({
                operation: actionName + ' Version',
                endpoint: endpoint,
                method: 'POST',
                payload: {},
                confirmMessage: 'Are you sure you want to ' + actionName.toLowerCase() + ' version "' + version + '"?',
                successMessage: 'Version ' + actionName.toLowerCase() + 'd successfully',
                reloadHandler: function() { return loadVersions(); }
            });
        }

        Bridge.Events.onClick('.btn-publish-v2', function(event, btn) {
            handleAction('publish', btn.getAttribute('data-entity-id'), btn.getAttribute('data-version'), 'Publish');
        });

        Bridge.Events.onClick('.btn-activate-v2', function(event, btn) {
            handleAction('activate', btn.getAttribute('data-entity-id'), btn.getAttribute('data-version'), 'Activate');
        });

        Bridge.Events.onClick('.btn-deactivate-v2', function(event, btn) {
            handleAction('deactivate', btn.getAttribute('data-entity-id'), btn.getAttribute('data-version'), 'Deactivate');
        });

        Bridge.Events.onClick('.btn-archive-v2', function(event, btn) {
            handleAction('archive', btn.getAttribute('data-entity-id'), btn.getAttribute('data-version'), 'Archive');
        });

        window.reloadContentDocumentVersionsTableV2 = function() {
            return loadVersions();
        };

        window.ContentDocumentVersionsV2 = {
            reload: window.reloadContentDocumentVersionsTableV2,
            loadVersions: loadVersions,
            buildParams: buildParams
        };

        loadVersions();
    });
})();
