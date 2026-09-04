/**
 * I18n Scope Keys Management V2
 * Parity migration of i18n_scope_keys.js using AdminPageBridge + I18nHelpersV2.
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        if (!window.AdminPageBridge || !window.I18nHelpersV2) {
            console.error('❌ Missing AdminPageBridge/I18nHelpersV2 for i18n_scope_keys-v2');
            return;
        }

        if (typeof window.scopeId === 'undefined') {
            console.error('❌ Scope ID not found (window.scopeId)');
            return;
        }

        const Bridge = window.AdminPageBridge;
        const Helpers = window.I18nHelpersV2;
        const scopeId = window.scopeId;
        const capabilities = window.scopeKeysCapabilities || {};

        let currentPage = 1;
        let currentPerPage = 25;
        let domainSelect2 = null;
        let domainsLoaded = false;

        const tableContainerId = window.scopeKeysTableContainerId || 'keys-table-container';
        const headers = ['ID', 'Domain', 'Key Part', 'Description', 'Actions'];
        const rows = ['id', 'domain', 'key_part', 'description', 'actions'];

        const escapeHtml = Bridge.Text.escapeHtml;

        function idRenderer(value) {
            return `<span class="font-mono text-gray-600 dark:text-gray-400">${escapeHtml(value)}</span>`;
        }

        function domainRenderer(value) {
            return `<span class="font-medium text-gray-900 dark:text-gray-100">${escapeHtml(value)}</span>`;
        }

        function keyPartRenderer(value) {
            return `<span class="font-mono text-sm text-gray-800 dark:text-gray-200">${escapeHtml(value)}</span>`;
        }

        function descriptionRenderer(value) {
            if (!value || value === '') {
                return '<span class="text-gray-400 dark:text-gray-500 italic text-sm">No description</span>';
            }
            const truncated = value.length > 50 ? `${value.substring(0, 50)}...` : value;
            return `<span class="text-gray-700 dark:text-gray-300 text-sm" title="${escapeHtml(value)}">${escapeHtml(truncated)}</span>`;
        }

        function actionsRenderer(_, row) {
            const actions = [];

            if (capabilities.can_rename) {
                actions.push(AdminUIComponents.buildActionButton({
                    cssClass: 'btn-rename-key',
                    icon: AdminUIComponents.SVGIcons?.edit || '✎',
                    text: 'Rename',
                    color: 'blue',
                    entityId: row.id,
                    title: 'Rename Key',
                    dataAttributes: { name: row.key_part }
                }));
            }

            if (capabilities.can_update_meta) {
                actions.push(AdminUIComponents.buildActionButton({
                    cssClass: 'btn-update-meta',
                    icon: AdminUIComponents.SVGIcons?.settings || '⚙',
                    text: 'Meta',
                    color: 'gray',
                    entityId: row.id,
                    title: 'Update Description',
                    dataAttributes: { desc: row.description || '' }
                }));
            }

            if (!actions.length) {
                return '<span class="text-gray-400 dark:text-gray-500 text-xs">No actions</span>';
            }

            return `<div class="flex items-center gap-2">${actions.join('')}</div>`;
        }

        function getFilters() {
            const filters = {};
            const filterId = Bridge.DOM.value('#key-filter-id', '').trim();
            const filterDomain = Bridge.DOM.value('#key-filter-domain', '').trim();
            const filterPart = Bridge.DOM.value('#key-filter-part', '').trim();

            if (filterId) filters.id = filterId;
            if (filterDomain) filters.domain = filterDomain;
            if (filterPart) filters.key_part = filterPart;

            return filters;
        }

        function buildParams() {
            const params = {
                page: currentPage,
                per_page: currentPerPage
            };

            const filters = getFilters();
            const globalSearch = Bridge.DOM.value('#key-search-global', '').trim();
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
                    <div class="text-gray-600 dark:text-gray-300 mb-4">${escapeHtml(result.error || 'Failed to load keys')}</div>
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
                    <button onclick="window.reloadScopeKeysTable()" class="mt-6 px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        🔄 Retry
                    </button>
                </div>
            `;

            container.innerHTML = errorHtml;
        }

        async function loadKeys() {
            const params = buildParams();
            const endpoint = `i18n/scopes/${scopeId}/keys/query`;

            const result = await Bridge.API.execute({
                endpoint,
                payload: params,
                operation: 'Query Scope Keys',
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
            const keyRows = Array.isArray(data.data) ? data.data : [];
            const paginationInfo = data.pagination || {
                page: currentPage,
                per_page: currentPerPage,
                total: keyRows.length
            };

            return Helpers.withTableContainerTarget(tableContainerId, function() {
                return TableComponent(
                    keyRows,
                    headers,
                    rows,
                    paginationInfo,
                    '',
                    false,
                    'id',
                    null,
                    {
                        id: idRenderer,
                        domain: domainRenderer,
                        key_part: keyPartRenderer,
                        description: descriptionRenderer,
                        actions: actionsRenderer
                    },
                    null,
                    getPaginationInfo
                );
            });
        }

        async function loadDomainsDropdown() {
            if (domainsLoaded) return;

            const loadingMsg = document.getElementById('domain-loading-msg');
            const errorMsg = document.getElementById('domain-error-msg');

            if (loadingMsg) loadingMsg.classList.remove('hidden');
            if (errorMsg) errorMsg.classList.add('hidden');

            const result = await Bridge.API.execute({
                endpoint: `i18n/scopes/${scopeId}/domains/dropdown`,
                payload: {},
                method: 'GET',
                operation: 'Load Domains Dropdown',
                showErrorMessage: false
            });

            if (loadingMsg) loadingMsg.classList.add('hidden');

            if (!result.success) {
                if (errorMsg) {
                    errorMsg.textContent = result.error || 'Failed to load domains.';
                    errorMsg.classList.remove('hidden');
                }
                return;
            }

            const domains = (result.data && result.data.data) || [];
            if (!domains.length) {
                if (errorMsg) {
                    errorMsg.textContent = 'No domains assigned to this scope.';
                    errorMsg.classList.remove('hidden');
                }
                return;
            }

            const selectData = domains.map(function(domain) {
                return { value: domain.code, label: `${domain.name} (${domain.code})` };
            });

            if (typeof window.Select2 === 'function') {
                if (domainSelect2 && typeof domainSelect2.destroy === 'function') {
                    domainSelect2.destroy();
                }
                domainSelect2 = Select2('#create-domain-select', selectData);
                domainsLoaded = true;
                return;
            }

            if (errorMsg) {
                errorMsg.textContent = 'UI Error: Select2 library missing.';
                errorMsg.classList.remove('hidden');
            }
        }

        function openCreateModal() {
            if (domainSelect2) {
                const container = document.querySelector('#create-domain-select');
                const input = container ? container.querySelector('.js-select-input') : null;
                if (input) input.value = '';
                if (container) container.dataset.value = '';
            } else {
                loadDomainsDropdown();
            }

            Bridge.DOM.setValue('#create-key-name', '');
            Bridge.DOM.setValue('#create-description', '');
            Bridge.Modal.open('#modal-create-key');
        }

        function closeCreateModal() {
            Bridge.Modal.close('#modal-create-key');
        }

        function closeRenameModal() {
            Bridge.Modal.close('#modal-rename-key');
        }

        function closeMetaModal() {
            Bridge.Modal.close('#modal-update-meta');
        }

        async function handleCreateKey() {
            let domainCode = '';
            if (domainSelect2 && typeof domainSelect2.getValue === 'function') {
                domainCode = domainSelect2.getValue();
            } else {
                const container = document.querySelector('#create-domain-select');
                domainCode = container ? container.dataset.value : '';
            }

            const keyName = Bridge.DOM.value('#create-key-name', '').trim();
            const description = Bridge.DOM.value('#create-description', '').trim();

            if (!domainCode) {
                window.alert('Please select a domain.');
                return;
            }
            if (!keyName) {
                window.alert('Key Name is required.');
                return;
            }

            const result = await Bridge.API.execute({
                endpoint: `i18n/scopes/${scopeId}/keys/create`,
                payload: {
                    domain_code: domainCode,
                    key_name: keyName,
                    description
                },
                operation: 'Create Key',
                method: 'POST',
                showErrorMessage: false
            });

            if (!result.success) {
                Bridge.UI.error(result.error || 'Failed to create key');
                return;
            }

            closeCreateModal();
            Bridge.UI.success('Key created successfully');
            loadKeys();
        }

        function openRenameModal(id, name) {
            Bridge.DOM.setValue('#rename-key-id', id || '');
            Bridge.DOM.setValue('#rename-key-name', name || '');
            Bridge.Modal.open('#modal-rename-key');
        }

        async function handleRenameKey() {
            const keyId = Bridge.DOM.value('#rename-key-id', '').trim();
            const newName = Bridge.DOM.value('#rename-key-name', '').trim();

            if (!newName) {
                window.alert('Key Name is required.');
                return;
            }

            const result = await Bridge.API.execute({
                endpoint: `i18n/scopes/${scopeId}/keys/update-name`,
                payload: { key_id: keyId, key_name: newName },
                operation: 'Rename Key',
                method: 'POST',
                showErrorMessage: false
            });

            if (!result.success) {
                Bridge.UI.error(result.error || 'Failed to rename key');
                return;
            }

            closeRenameModal();
            Bridge.UI.success('Key renamed successfully');
            loadKeys();
        }

        function openMetaModal(id, description) {
            Bridge.DOM.setValue('#meta-key-id', id || '');
            Bridge.DOM.setValue('#meta-description', description || '');
            Bridge.Modal.open('#modal-update-meta');
        }

        async function handleUpdateMeta() {
            const keyId = Bridge.DOM.value('#meta-key-id', '').trim();
            const description = Bridge.DOM.value('#meta-description', '').trim();

            const result = await Bridge.API.execute({
                endpoint: `i18n/scopes/${scopeId}/keys/update_metadata`,
                payload: { key_id: keyId, description },
                operation: 'Update Metadata',
                method: 'POST',
                showErrorMessage: false
            });

            if (!result.success) {
                Bridge.UI.error(result.error || 'Failed to update metadata');
                return;
            }

            closeMetaModal();
            Bridge.UI.success('Metadata updated successfully');
            loadKeys();
        }

        const resetPageAndReload = Helpers.createResetPageReload({
            setPage: function(page) { currentPage = page; },
            resetPage: 1,
            reload: loadKeys
        });

        function bindEvents() {
            Bridge.Events.bindFilterForm({
                form: '#scope-keys-filter-form',
                resetButton: '#btn-reset-filters',
                onSubmit: resetPageAndReload,
                onReset: resetPageAndReload
            });

            const filterBtn = document.getElementById('btn-filter-search');
            if (filterBtn) filterBtn.addEventListener('click', resetPageAndReload);

            const searchBtn = document.getElementById('btn-search-global');
            if (searchBtn) searchBtn.addEventListener('click', resetPageAndReload);

            const clearSearchBtn = document.getElementById('btn-clear-search');
            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', function() {
                    Bridge.DOM.setValue('#key-search-global', '');
                    resetPageAndReload();
                });
            }

            Bridge.Events.bindEnterAction({
                input: '#key-search-global',
                onEnter: function(_, ctx) { resetPageAndReload(ctx.event); },
                ignoreInsideForm: true,
                preventDefault: true
            });

            Helpers.bindTableActionState({
                sourceContainerId: tableContainerId,
                getState: function() {
                    return {
                        page: currentPage,
                        per_page: currentPerPage
                    };
                },
                getParams: function() {
                    return {
                        page: currentPage,
                        per_page: currentPerPage
                    };
                },
                setState: function(next) {
                    currentPage = next.page ?? currentPage;
                    currentPerPage = next.perPage ?? currentPerPage;
                },
                reload: loadKeys
            });

            Bridge.Events.onClick('#btn-create-key', function() {
                openCreateModal();
            });

            Bridge.Events.onClick('#btn-confirm-create', function() { handleCreateKey(); });
            Bridge.Events.onClick('#btn-cancel-create', function() { closeCreateModal(); });

            Bridge.Events.onClick('#btn-confirm-rename', function() { handleRenameKey(); });
            Bridge.Events.onClick('#btn-cancel-rename', function() { closeRenameModal(); });

            Bridge.Events.onClick('#btn-confirm-meta', function() { handleUpdateMeta(); });
            Bridge.Events.onClick('#btn-cancel-meta', function() { closeMetaModal(); });

            Bridge.Events.onClick('.btn-rename-key', function(_, target) {
                const id = target.dataset.entityId;
                const name = target.dataset.name || '';
                if (id) openRenameModal(id, name);
            });

            Bridge.Events.onClick('.btn-update-meta', function(_, target) {
                const id = target.dataset.entityId;
                const desc = target.dataset.desc || '';
                if (id) openMetaModal(id, desc);
            });

            document.addEventListener('click', function(event) {
                if (event.target && event.target.id === 'modal-create-key') closeCreateModal();
                if (event.target && event.target.id === 'modal-rename-key') closeRenameModal();
                if (event.target && event.target.id === 'modal-update-meta') closeMetaModal();
            });
        }

        bindEvents();
        loadKeys();

        if (capabilities.can_create) {
            loadDomainsDropdown();
        }

        window.reloadScopeKeysTableV2 = function() {
            return loadKeys();
        };
        window.reloadScopeKeysTable = window.reloadScopeKeysTableV2;
    });
})();
