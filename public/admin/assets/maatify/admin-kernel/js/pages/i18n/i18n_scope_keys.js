/**
 * I18n Scope Keys Management
 * ========================================
 * Manages the "Scope Keys" table on the Scope Keys page.
 *
 * Features:
 * - List keys assigned to this scope
 * - Create new keys (if capable)
 * - Rename keys (if capable)
 * - Update key metadata (if capable)
 * - Delete keys (if capable)
 * - Filter by ID, domain, key part
 *
 * API Endpoints:
 * - POST /api/i18n/scopes/{scope_id}/keys/query
 * - POST /api/i18n/scopes/{scope_id}/keys/create
 * - POST /api/i18n/scopes/{scope_id}/keys/update-name
 * - POST /api/i18n/scopes/{scope_id}/keys/update_metadata
 * - POST /api/i18n/scopes/{scope_id}/keys/delete (Assumed)
 * - GET /api/i18n/scopes/{scope_id}/domains/dropdown
 */

(function() {
    'use strict';

    console.log('🌍 I18n Scope Keys Module Loading...');

    // ========================================================================
    // PREREQUISITES CHECK
    // ========================================================================

    if (typeof AdminUIComponents === 'undefined') {
        console.error('❌ AdminUIComponents library not found!');
        return;
    }

    if (typeof ApiHandler === 'undefined') {
        console.error('❌ ApiHandler not found!');
        return;
    }

    if (typeof window.scopeId === 'undefined') {
        console.error('❌ Scope ID not found (window.scopeId)!');
        return;
    }

    console.log('✅ Dependencies loaded: AdminUIComponents, ApiHandler');

    // ========================================================================
    // STATE & CONFIGURATION
    // ========================================================================

    let currentPage = 1;
    let currentPerPage = 25;
    const scopeId = window.scopeId;

    const headers = ['ID', 'Domain', 'Key Part', 'Description', 'Actions'];
    const rows = ['id', 'domain', 'key_part', 'description', 'actions'];
    const capabilities = window.scopeKeysCapabilities || {};

    let domainSelect2 = null;
    let domainsLoaded = false;

    console.log('🔐 Capabilities:', capabilities);
    console.log('🎯 Scope ID:', scopeId);

    // ========================================================================
    // UTILITY FUNCTIONS
    // ========================================================================

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // ========================================================================
    // CUSTOM RENDERERS
    // ========================================================================

    const idRenderer = (value) => {
        return `<span class="font-mono text-gray-600 dark:text-gray-400">${escapeHtml(value)}</span>`;
    };

    const domainRenderer = (value) => {
        return `<span class="font-medium text-gray-900 dark:text-gray-100">${escapeHtml(value)}</span>`;
    };

    const keyPartRenderer = (value) => {
        return `<span class="font-mono text-sm text-gray-800 dark:text-gray-200">${escapeHtml(value)}</span>`;
    };

    const descriptionRenderer = (value) => {
        if (!value || value === '') {
            return `<span class="text-gray-400 dark:text-gray-500 italic text-sm">No description</span>`;
        }
        const truncated = value.length > 50 ? value.substring(0, 50) + '...' : value;
        return `<span class="text-gray-700 dark:text-gray-300 text-sm" title="${escapeHtml(value)}">${escapeHtml(truncated)}</span>`;
    };

    const actionsRenderer = (value, row) => {
        const actions = [];

        if (capabilities.can_rename) {
            actions.push(AdminUIComponents.buildActionButton({
                cssClass: 'btn-rename-key',
                icon: AdminUIComponents.SVGIcons?.edit || '✎',
                text: 'Rename',
                color: 'blue',
                entityId: row.id,
                title: 'Rename Key',
                dataAttributes: {
                    'name': row.key_part
                }
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
                dataAttributes: {
                    'desc': row.description || ''
                }
            }));
        }

        if (actions.length === 0) {
            return '<span class="text-gray-400 dark:text-gray-500 text-xs">No actions</span>';
        }

        return `<div class="flex items-center gap-2">${actions.join('')}</div>`;
    };

    // ========================================================================
    // QUERY BUILDING
    // ========================================================================

    function buildQueryParams() {
        const params = {
            page: currentPage,
            per_page: currentPerPage
        };

        const columnFilters = {};

        const filterId = document.getElementById('key-filter-id')?.value?.trim();
        if (filterId) columnFilters.id = filterId;

        const filterDomain = document.getElementById('key-filter-domain')?.value?.trim();
        if (filterDomain) columnFilters.domain = filterDomain;

        const filterPart = document.getElementById('key-filter-part')?.value?.trim();
        if (filterPart) columnFilters.key_part = filterPart;

        // Global search
        const globalSearch = document.getElementById('key-search-global')?.value?.trim();

        if (globalSearch || Object.keys(columnFilters).length > 0) {
            params.search = {};
            if (globalSearch) params.search.global = globalSearch;
            if (Object.keys(columnFilters).length > 0) params.search.columns = columnFilters;
        }

        return params;
    }

    // ========================================================================
    // DATA LOADING
    // ========================================================================

    async function loadKeys(page = null, perPage = null) {
        if (page !== null) currentPage = page;
        if (perPage !== null) currentPerPage = perPage;

        console.log('📡 Loading scope keys...', { currentPage, currentPerPage });

        const params = buildQueryParams();
        const endpoint = `i18n/scopes/${scopeId}/keys/query`;

        const result = await ApiHandler.call(endpoint, params, 'Query Scope Keys');

        const container = document.getElementById('keys-table-container');
        if (!container) {
            console.error('❌ keys-table-container not found');
            return;
        }

        if (!result.success) {
            console.error('❌ Query failed:', result);
            renderErrorState(container, result);
            return;
        }

        const data = result.data || {};
        const keys = Array.isArray(data.data) ? data.data : [];
        const paginationInfo = data.pagination || {
            page: params.page || 1,
            per_page: params.per_page || 25,
            total: keys.length
        };

        // Render table
        if (typeof TableComponent === 'function') {
            // Hijack the global table-container ID temporarily for TableComponent
            const originalTableContainer = document.getElementById('table-container');
            const tempId = 'table-container-original-' + Date.now();
            if (originalTableContainer && originalTableContainer !== container) {
                originalTableContainer.id = tempId;
            }

            // Ensure our container has the right ID for TableComponent
            const originalContainerId = container.id;
            container.id = 'table-container';

            try {
                TableComponent(
                    keys,
                    headers,
                    rows,
                    paginationInfo,
                    "",
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
            } catch (error) {
                console.error('❌ TABLE ERROR:', error);
                ApiHandler.showAlert('danger', 'Failed to render table: ' + error.message);
            } finally {
                // Restore IDs
                container.id = originalContainerId;
                if (originalTableContainer && originalTableContainer !== container) {
                    originalTableContainer.id = 'table-container';
                }
            }
        } else {
            console.error('❌ TableComponent not found');
        }
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

    function getPaginationInfo(pagination) {
        const { page = 1, per_page = 25, total = 0, filtered = total } = pagination;
        const displayCount = filtered || total;
        const startItem = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
        const endItem = Math.min(page * per_page, displayCount);

        let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;
        if (filtered && filtered !== total) {
            infoText += ` <span class="text-gray-500 dark:text-gray-400">(filtered from ${total} total)</span>`;
        }

        return { total: displayCount, info: infoText };
    }

    // ========================================================================
    // DOMAIN DROPDOWN LOADING
    // ========================================================================

    async function loadDomainsDropdown() {
        if (domainsLoaded) return;

        const loadingMsg = document.getElementById('domain-loading-msg');
        const errorMsg = document.getElementById('domain-error-msg');
        const selectContainer = document.getElementById('create-domain-select');

        if (loadingMsg) loadingMsg.classList.remove('hidden');
        if (errorMsg) errorMsg.classList.add('hidden');

        const endpoint = `i18n/scopes/${scopeId}/domains/dropdown`;
        const result = await ApiHandler.call(endpoint, {}, 'Load Domains Dropdown', 'GET');

        if (loadingMsg) loadingMsg.classList.add('hidden');

        if (result.success) {
            const domains = result.data.data || [];
            
            if (domains.length === 0) {
                if (errorMsg) {
                    errorMsg.textContent = 'No domains assigned to this scope.';
                    errorMsg.classList.remove('hidden');
                }
                return;
            }

            const selectData = domains.map(d => ({
                value: d.code,
                label: `${d.name} (${d.code})`
            }));

            if (typeof Select2 === 'function') {
                if (domainSelect2) {
                    domainSelect2.destroy();
                }
                domainSelect2 = Select2('#create-domain-select', selectData);
                domainsLoaded = true;
            } else {
                console.error('❌ Select2 library not found');
                if (errorMsg) {
                    errorMsg.textContent = 'UI Error: Select2 library missing.';
                    errorMsg.classList.remove('hidden');
                }
            }
        } else {
            console.error('❌ Failed to load domains:', result);
            if (errorMsg) {
                errorMsg.textContent = result.error || 'Failed to load domains.';
                errorMsg.classList.remove('hidden');
            }
        }
    }

    // ========================================================================
    // ACTION HANDLERS
    // ========================================================================

    // --- Create Key ---

    function openCreateModal() {
        // Reset fields
        if (domainSelect2) {
            // Reset Select2 if needed, though Select2 doesn't have a clear reset method exposed in the snippet
            // We can manually clear the input and data attribute
            const container = document.querySelector('#create-domain-select');
            const input = container.querySelector('.js-select-input');
            if (input) input.value = '';
            container.dataset.value = '';
        } else {
            // Try to load domains if not loaded yet
            loadDomainsDropdown();
        }
        
        document.getElementById('create-key-name').value = '';
        document.getElementById('create-description').value = '';
        document.getElementById('modal-create-key').classList.remove('hidden');
    }

    function closeCreateModal() {
        document.getElementById('modal-create-key').classList.add('hidden');
    }

    async function handleCreateKey() {
        let domainCode = '';
        
        if (domainSelect2) {
            domainCode = domainSelect2.getValue();
        } else {
            // Fallback if Select2 failed but user manually entered something (unlikely with this UI)
             const container = document.querySelector('#create-domain-select');
             domainCode = container ? container.dataset.value : '';
        }

        const keyName = document.getElementById('create-key-name').value.trim();
        const description = document.getElementById('create-description').value.trim();

        if (!domainCode) {
            alert('Please select a domain.');
            return;
        }

        if (!keyName) {
            alert('Key Name is required.');
            return;
        }

        const endpoint = `i18n/scopes/${scopeId}/keys/create`;
        const payload = {
            domain_code: domainCode,
            key_name: keyName,
            description: description
        };

        const result = await ApiHandler.call(endpoint, payload, 'Create Key');

        if (result.success) {
            closeCreateModal();
            ApiHandler.showAlert('success', 'Key created successfully');
            loadKeys();
        } else {
            ApiHandler.showAlert('danger', result.error || 'Failed to create key');
        }
    }

    // --- Rename Key ---

    function openRenameModal(id, currentName) {
        document.getElementById('rename-key-id').value = id;
        document.getElementById('rename-key-name').value = currentName;
        document.getElementById('modal-rename-key').classList.remove('hidden');
    }

    function closeRenameModal() {
        document.getElementById('modal-rename-key').classList.add('hidden');
    }

    async function handleRenameKey() {
        const keyId = document.getElementById('rename-key-id').value;
        const newName = document.getElementById('rename-key-name').value.trim();

        if (!newName) {
            alert('Key Name is required.');
            return;
        }

        const endpoint = `i18n/scopes/${scopeId}/keys/update-name`;
        const payload = {
            key_id: keyId,
            key_name: newName
        };

        const result = await ApiHandler.call(endpoint, payload, 'Rename Key');

        if (result.success) {
            closeRenameModal();
            ApiHandler.showAlert('success', 'Key renamed successfully');
            loadKeys();
        } else {
            ApiHandler.showAlert('danger', result.error || 'Failed to rename key');
        }
    }

    // --- Update Metadata ---

    function openMetaModal(id, currentDesc) {
        document.getElementById('meta-key-id').value = id;
        document.getElementById('meta-description').value = currentDesc;
        document.getElementById('modal-update-meta').classList.remove('hidden');
    }

    function closeMetaModal() {
        document.getElementById('modal-update-meta').classList.add('hidden');
    }

    async function handleUpdateMeta() {
        const keyId = document.getElementById('meta-key-id').value;
        const description = document.getElementById('meta-description').value.trim();

        const endpoint = `i18n/scopes/${scopeId}/keys/update_metadata`;
        const payload = {
            key_id: keyId,
            description: description
        };

        const result = await ApiHandler.call(endpoint, payload, 'Update Metadata');

        if (result.success) {
            closeMetaModal();
            ApiHandler.showAlert('success', 'Metadata updated successfully');
            loadKeys();
        } else {
            ApiHandler.showAlert('danger', result.error || 'Failed to update metadata');
        }
    }

    // ========================================================================
    // EVENT DELEGATION
    // ========================================================================

    function setupEventDelegation() {
        // Create Key Button
        const btnCreate = document.getElementById('btn-create-key');
        if (btnCreate) {
            btnCreate.addEventListener('click', openCreateModal);
        }

        // Modal Buttons
        document.getElementById('btn-confirm-create')?.addEventListener('click', handleCreateKey);
        document.getElementById('btn-cancel-create')?.addEventListener('click', closeCreateModal);

        document.getElementById('btn-confirm-rename')?.addEventListener('click', handleRenameKey);
        document.getElementById('btn-cancel-rename')?.addEventListener('click', closeRenameModal);

        document.getElementById('btn-confirm-meta')?.addEventListener('click', handleUpdateMeta);
        document.getElementById('btn-cancel-meta')?.addEventListener('click', closeMetaModal);

        // Table Actions Delegation
        document.addEventListener('click', (e) => {
            // Rename Button
            const renameBtn = e.target.closest('.btn-rename-key');
            if (renameBtn) {
                const id = renameBtn.dataset.entityId; // AdminUIComponents uses data-entity-id
                const name = renameBtn.dataset.name;
                if (id) openRenameModal(id, name);
                return;
            }

            // Update Meta Button
            const metaBtn = e.target.closest('.btn-update-meta');
            if (metaBtn) {
                const id = metaBtn.dataset.entityId;
                const desc = metaBtn.dataset.desc;
                if (id) openMetaModal(id, desc);
                return;
            }
        });

        // Listen for table events (pagination)
        document.addEventListener('tableAction', (e) => {
            const { action, value } = e.detail;
            if (action === 'pageChange') {
                loadKeys(value);
            } else if (action === 'perPageChange') {
                loadKeys(1, value);
            }
        });

        // Filter Form Listeners
        const filterForm = document.getElementById('scope-keys-filter-form');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                loadKeys(1);
            });
        }

        // Filter Search Button Listener
        const filterSearchBtn = document.getElementById('btn-filter-search');
        if (filterSearchBtn) {
            filterSearchBtn.addEventListener('click', (e) => {
                e.preventDefault();
                loadKeys(1);
            });
        }

        const resetBtn = document.getElementById('btn-reset-filters');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (filterForm) filterForm.reset();
                loadKeys(1);
            });
        }

        // Global Search Listeners
        const searchBtn = document.getElementById('btn-search-global');
        if (searchBtn) {
            searchBtn.addEventListener('click', () => {
                loadKeys(1);
            });
        }

        const clearSearchBtn = document.getElementById('btn-clear-search');
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', () => {
                const searchInput = document.getElementById('key-search-global');
                if (searchInput) searchInput.value = '';
                loadKeys(1);
            });
        }

        const searchInput = document.getElementById('key-search-global');
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    loadKeys(1);
                }
            });
        }
    }

    // ========================================================================
    // INITIALIZATION
    // ========================================================================

    function init() {
        console.log('🎬 Initializing I18n Scope Keys Module...');

        setupEventDelegation();
        loadKeys();
        
        // Preload domains if create capability exists
        if (capabilities.can_create) {
            loadDomainsDropdown();
        }

        // Export reload function
        window.reloadScopeKeysTable = function() {
            loadKeys(currentPage, currentPerPage);
        };

        console.log('✅ I18n Scope Keys Module initialized');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
