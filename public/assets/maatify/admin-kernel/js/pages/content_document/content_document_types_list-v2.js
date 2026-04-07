/**
 * Content Document Types List V2
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        if (!window.AdminPageBridge || !window.ContentDocumentHelpersV2 || typeof window.AdminUIComponents === 'undefined') {
            console.error('❌ Missing dependencies for content_document_types_list-v2');
            return;
        }

        const Bridge = window.AdminPageBridge;
        const Helpers = window.ContentDocumentHelpersV2;
        const capabilities = window.contentDocumentTypesCapabilities || {};
        const api = window.contentDocumentTypesApi || {};

        const tableContainerId = 'content-document-types-table-container';
        const filterFormSelector = '#content-document-types-filter-form';
        const searchSelector = '#content-document-types-search';

        let currentPage = 1;
        let currentPerPage = 25;

        const headers = ['ID', 'Key', 'Requires Acceptance', 'System Type', 'Created At', 'Actions'];
        const rowKeys = ['id', 'key', 'requires_acceptance_default', 'is_system', 'created_at', 'actions'];

        const customRenderers = {
            id: function(value, row) {
                if (capabilities.can_view_versions) {
                    return '<a href="/content-document-types/' + row.id + '/documents" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">' + value + '</a>';
                }
                return '<span class="text-gray-500">' + value + '</span>';
            },
            key: function(value) {
                return '<span class="font-mono text-sm text-blue-600 dark:text-blue-400">' + value + '</span>';
            },
            requires_acceptance_default: function(value) {
                return window.AdminUIComponents.renderStatusBadge(value, {
                    clickable: false,
                    activeText: 'Yes',
                    inactiveText: 'No',
                    activeColor: 'green',
                    inactiveColor: 'gray'
                });
            },
            is_system: function(value) {
                return window.AdminUIComponents.renderStatusBadge(value, {
                    clickable: false,
                    activeText: 'System',
                    inactiveText: 'Custom',
                    activeColor: 'purple',
                    inactiveColor: 'blue'
                });
            },
            created_at: function(value) {
                return '<span class="text-sm text-gray-500 dark:text-gray-400">' + value + '</span>';
            },
            actions: function(_, row) {
                if (!capabilities.can_update) return '';

                return window.AdminUIComponents.buildActionButton({
                    icon: window.AdminUIComponents.SVGIcons.edit,
                    text: 'Edit',
                    color: 'blue',
                    title: 'Edit Document Type',
                    dataAttributes: {
                        id: row.id,
                        key: encodeURIComponent(row.key),
                        requires_acceptance: row.requires_acceptance_default,
                        is_system: row.is_system
                    },
                    cssClass: 'btn-edit-document-type'
                });
            }
        };

        const getPaginationInfo = function(pagination) {
            const page = pagination.page || 1;
            const perPage = pagination.per_page || 25;
            const total = pagination.total || 0;
            const filtered = pagination.filtered === undefined ? total : pagination.filtered;
            const displayCount = filtered;
            const startItem = displayCount === 0 ? 0 : (page - 1) * perPage + 1;
            const endItem = Math.min(page * perPage, displayCount);

            let infoText = '<span>' + startItem + ' to ' + endItem + '</span> of <span>' + displayCount + '</span>';
            if (filtered !== total) {
                infoText += ' <span class="text-gray-500 dark:text-gray-400">(filtered from ' + total + ' total)</span>';
            }
            return { total: displayCount, info: infoText };
        };

        function buildParams() {
            const filters = Bridge.Form.omitEmpty({
                id: Bridge.DOM.value('#filter-id', '').trim(),
                key: Bridge.DOM.value('#filter-key', '').trim(),
                requires_acceptance_default: Bridge.DOM.value('#filter-requires-acceptance-default', ''),
                is_system: Bridge.DOM.value('#filter-is-system', '')
            });

            const globalSearch = Bridge.DOM.value(searchSelector, '').trim();
            const params = { page: currentPage, per_page: currentPerPage };
            const search = {};
            if (globalSearch) search.global = globalSearch;
            if (Object.keys(filters).length > 0) search.columns = filters;
            if (Object.keys(search).length > 0) params.search = search;
            return params;
        }

        function loadTable() {
            const params = buildParams();
            return Helpers.withTableContainerTarget(tableContainerId, function() {
                return createTable(
                    api.query,
                    params,
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
            reload: function() { return loadTable(); }
        });

        Bridge.Events.bindFilterForm({
            form: filterFormSelector,
            resetButton: '#content-document-types-reset-filters',
            onSubmit: resetPageAndReload,
            onReset: resetPageAndReload
        });

        const searchBtn = document.getElementById('content-document-types-search-btn');
        if (searchBtn) searchBtn.addEventListener('click', resetPageAndReload);

        const searchInput = document.getElementById('content-document-types-search');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key !== 'Enter') return;
                if (e.target && e.target.closest('form')) return;
                e.preventDefault();
                resetPageAndReload(e);
            });
        }

        const clearBtn = document.getElementById('content-document-types-clear-search');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (searchInput) searchInput.value = '';
                resetPageAndReload();
            });
        }

        Helpers.bindTableActionState({
            buildParams,
            getState: function() { return { page: currentPage, perPage: currentPerPage }; },
            setState: function(state) {
                currentPage = state.page ?? currentPage;
                currentPerPage = state.perPage ?? currentPerPage;
            },
            reload: function() { return loadTable(); }
        });

        function buildCreateModal(dropdownData) {
            const options = dropdownData.map(function(item) {
                return { value: item.key, label: item.label };
            });

            const modalId = 'create-document-type-modal-v2';
            const modalHtml = window.AdminUIComponents.buildModalTemplate({
                id: modalId,
                title: 'Create Content Document Type',
                content: '<div class="p-6"><form id="create-document-type-form-v2" class="space-y-4">' +
                    '<div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Document Type Key</label>' +
                    '<div id="create-key-container-v2" class="relative w-full"></div></div>' +
                    '<div class="flex items-center gap-2"><input type="checkbox" id="create-requires-acceptance-v2" class="rounded text-blue-600 w-4 h-4 border-gray-300 dark:border-gray-600 focus:ring-blue-500" checked><label for="create-requires-acceptance-v2" class="text-sm text-gray-700 dark:text-gray-300">Requires Acceptance (Default)</label></div>' +
                    '<div class="flex items-center gap-2"><input type="checkbox" id="create-is-system-v2" class="rounded text-blue-600 w-4 h-4 border-gray-300 dark:border-gray-600 focus:ring-blue-500"><label for="create-is-system-v2" class="text-sm text-gray-700 dark:text-gray-300">Is System Type</label></div>' +
                    '</form></div>',
                footer: window.AdminUIComponents.buildModalFooter({ submitText: 'Create', submitColor: 'green' }),
                icon: window.AdminUIComponents.SVGIcons.plus
            });

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modalEl = document.getElementById(modalId);
            const keyContainer = modalEl.querySelector('#create-key-container-v2');
            keyContainer.innerHTML = '<div class="js-select-box w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 cursor-pointer flex items-center justify-between hover:border-gray-400 transition-colors"><input type="text" class="js-select-input bg-transparent border-none outline-none w-full cursor-pointer text-gray-900 dark:text-gray-100" placeholder="Select Document Type" readonly><svg class="js-arrow w-4 h-4 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg></div><div class="js-dropdown hidden absolute z-50 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-y-auto"><div class="p-2 sticky top-0 bg-white dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600"><input type="text" class="js-search-input w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="Search..."></div><ul class="js-select-list py-1"></ul></div>';

            const keySelect = Select2('#create-key-container-v2', options);
            Helpers.wireModalDismiss(modalEl);
            Bridge.Modal.open(modalEl);

            const submitBtn = modalEl.querySelector('button[type="submit"]');
            submitBtn.addEventListener('click', function() {
                const key = keySelect.getValue();
                const payload = {
                    key: key,
                    requires_acceptance_default: Bridge.DOM.checked('#create-requires-acceptance-v2', true),
                    is_system: Bridge.DOM.checked('#create-is-system-v2', false)
                };

                if (!key) {
                    const keyBox = modalEl.querySelector('.js-select-box');
                    if (keyBox) keyBox.classList.add('border-red-500');
                    return;
                }

                Bridge.API.runMutation({
                    operation: 'Create Document Type',
                    endpoint: api.create,
                    method: 'POST',
                    payload,
                    successMessage: 'Document Type created successfully',
                    reloadHandler: window.reloadContentDocumentTypesTableV2,
                    onSuccess: function() { modalEl.remove(); }
                });
            });
        }

        const createBtn = document.getElementById('btn-create-content-document-type');
        if (createBtn) {
            createBtn.addEventListener('click', async function() {
                const dropdownResult = await Bridge.API.execute({
                    endpoint: api.dropdown,
                    payload: {},
                    operation: 'Fetch Document Types Dropdown',
                    showSuccessMessage: false
                });

                if (!dropdownResult.success) {
                    Bridge.UI.error('Failed to load available document types');
                    return;
                }

                const payload = dropdownResult.data;
                const dropdownData = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);

                if (!dropdownData.length) {
                    Bridge.UI.info('All document types are already registered.');
                    return;
                }

                buildCreateModal(dropdownData);
            });
        }

        function openEditModal(id, key, requiresAcceptance, isSystem) {
            const modalId = 'edit-document-type-modal-v2';
            const existing = document.getElementById(modalId);
            if (existing) existing.remove();

            const modalHtml = window.AdminUIComponents.buildModalTemplate({
                id: modalId,
                title: 'Edit Content Document Type',
                content: '<div class="p-6"><form id="edit-document-type-form-v2" class="space-y-4">' +
                    '<div><label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Key</label><input type="text" value="' + key + '" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-500 cursor-not-allowed" disabled><p class="text-xs text-gray-400 mt-1">Key cannot be modified.</p></div>' +
                    '<div class="flex items-center gap-2"><input type="checkbox" id="edit-requires-acceptance-v2" class="rounded text-blue-600 w-4 h-4 border-gray-300 dark:border-gray-600 focus:ring-blue-500" ' + (requiresAcceptance ? 'checked' : '') + '><label for="edit-requires-acceptance-v2" class="text-sm text-gray-700 dark:text-gray-300">Requires Acceptance (Default)</label></div>' +
                    '<div class="flex items-center gap-2"><input type="checkbox" id="edit-is-system-v2" class="rounded text-blue-600 w-4 h-4 border-gray-300 dark:border-gray-600 focus:ring-blue-500" ' + (isSystem ? 'checked' : '') + '><label for="edit-is-system-v2" class="text-sm text-gray-700 dark:text-gray-300">Is System Type</label></div>' +
                    '</form></div>',
                footer: window.AdminUIComponents.buildModalFooter({ submitText: 'Save Changes', submitColor: 'blue' }),
                icon: window.AdminUIComponents.SVGIcons.edit
            });

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modalEl = document.getElementById(modalId);
            Helpers.wireModalDismiss(modalEl);
            Bridge.Modal.open(modalEl);

            const submitBtn = modalEl.querySelector('button[type="submit"]');
            submitBtn.addEventListener('click', function() {
                const updateUrl = (api.update || '').replace('{type_id}', id);
                const payload = {
                    requires_acceptance_default: Bridge.DOM.checked('#edit-requires-acceptance-v2', false),
                    is_system: Bridge.DOM.checked('#edit-is-system-v2', false)
                };

                Bridge.API.runMutation({
                    operation: 'Update Document Type',
                    endpoint: updateUrl,
                    method: 'POST',
                    payload,
                    successMessage: 'Document Type updated successfully',
                    reloadHandler: window.reloadContentDocumentTypesTableV2,
                    onSuccess: function() { modalEl.remove(); }
                });
            });
        }

        Bridge.Events.onClick('.btn-edit-document-type', function(event, btn) {
            const id = btn.getAttribute('data-id');
            const key = decodeURIComponent(btn.getAttribute('data-key') || '');
            const requiresAcceptance = (btn.getAttribute('data-requires_acceptance') || '') === '1' || (btn.getAttribute('data-requires_acceptance') || '') === 'true';
            const isSystem = (btn.getAttribute('data-is_system') || '') === '1' || (btn.getAttribute('data-is_system') || '') === 'true';
            openEditModal(id, key, requiresAcceptance, isSystem);
        });

        window.reloadContentDocumentTypesTableV2 = function() {
            return loadTable();
        };

        window.ContentDocumentTypesV2 = {
            reload: window.reloadContentDocumentTypesTableV2,
            loadTable: loadTable,
            buildParams: buildParams
        };

        loadTable();
    });
})();
