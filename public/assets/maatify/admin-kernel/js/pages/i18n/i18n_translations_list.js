/**
 * I18n Translations List Management
 * ========================================
 * Manages the "Translations" table on the Language Translations page.
 *
 * Features:
 * - List translations for a specific language
 * - Upsert translation (edit value)
 * - Delete translation (clear value)
 * - Filter by ID, scope, domain, key_part, value
 *
 * API Endpoints:
 * - POST /api/languages/{language_id}/translations/query
 * - POST /api/languages/{language_id}/translations/upsert
 * - POST /api/languages/{language_id}/translations/delete
 */

(function() {
    'use strict';

    console.log('🌍 I18n Translations List Module Loading...');

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

    // Extract language ID from URL
    // URL pattern: /languages/{id}/translations
    const pathSegments = window.location.pathname.split('/');
    const languageIdIndex = pathSegments.indexOf('languages') + 1;
    const languageId = pathSegments[languageIdIndex];

    if (!languageId || isNaN(languageId)) {
        console.error('❌ Language ID not found in URL!');
        return;
    }

    console.log('✅ Dependencies loaded: AdminUIComponents, ApiHandler');
    console.log('🎯 Language ID:', languageId);

    // ========================================================================
    // STATE & CONFIGURATION
    // ========================================================================

    let currentPage = 1;
    let currentPerPage = 25;

    const headers = ['ID', 'Scope', 'Domain', 'Key Segment', 'Value', 'Last Updated', 'Actions'];
    const rows = ['key_id', 'scope', 'domain', 'key_part', 'value', 'updated_at', 'actions'];
    const capabilities = window.languageTranslationsCapabilities || {};

    console.log('🔐 Capabilities:', capabilities);

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

    const scopeRenderer = (value) => {
        return AdminUIComponents.renderCodeBadge(escapeHtml(value), {
            color: 'purple',
            size: 'sm'
        });
    };

    const domainRenderer = (value) => {
        return AdminUIComponents.renderCodeBadge(escapeHtml(value), {
            color: 'indigo',
            size: 'sm'
        });
    };

    const keyPartRenderer = (value) => {
        return `<span class="font-mono text-sm text-blue-600 dark:text-blue-400 break-all">${escapeHtml(value)}</span>`;
    };

    const valueRenderer = (value) => {
        if (!value || value === '') {
            return `<span class="text-gray-400 dark:text-gray-500 italic text-sm">Not translated</span>`;
        }
        const truncated = value.length > 100 ? value.substring(0, 100) + '...' : value;
        return `<span class="text-gray-800 dark:text-gray-200 text-sm whitespace-pre-wrap" title="${escapeHtml(value)}">${escapeHtml(truncated)}</span>`;
    };

    const dateRenderer = (value) => {
        return AdminUIComponents.formatDate(value, { format: 'relative' });
    };

    const actionsRenderer = (value, row) => {
        const actions = [];

        // Edit Button (Upsert)
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
                    'scope': row.scope,
                    'domain': row.domain,
                    'current-value': row.value || ''
                }
            }));
        }

        // Delete Button (Clear)
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

        if (actions.length === 0) {
            return '<span class="text-gray-400 dark:text-gray-500 text-xs">No actions</span>';
        }

        return `<div class="flex flex-wrap gap-2">${actions.join('')}</div>`;
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

        const filterId = document.getElementById('translation-filter-id')?.value?.trim();
        if (filterId) columnFilters.id = filterId;

        const filterScope = document.getElementById('translation-filter-scope')?.value?.trim();
        if (filterScope) columnFilters.scope = filterScope;

        const filterDomain = document.getElementById('translation-filter-domain')?.value?.trim();
        if (filterDomain) columnFilters.domain = filterDomain;

        const filterKeyPart = document.getElementById('translation-filter-key-part')?.value?.trim();
        if (filterKeyPart) columnFilters.key_part = filterKeyPart;

        const filterValue = document.getElementById('translation-filter-value')?.value?.trim();
        if (filterValue) columnFilters.value = filterValue;

        // Global search
        const globalSearch = document.getElementById('translation-search-global')?.value?.trim();

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

    async function loadTranslations(page = null, perPage = null) {
        if (page !== null) currentPage = page;
        if (perPage !== null) currentPerPage = perPage;

        console.log('📡 Loading translations...', { currentPage, currentPerPage });

        const params = buildQueryParams();
        const endpoint = `languages/${languageId}/translations/query`;

        const result = await ApiHandler.call(endpoint, params, 'Query Translations');

        const container = document.getElementById('translations-table-container');
        if (!container) {
            console.error('❌ translations-table-container not found');
            return;
        }

        if (!result.success) {
            console.error('❌ Query failed:', result);
            renderErrorState(container, result);
            return;
        }

        const data = result.data || {};
        const translations = Array.isArray(data.data) ? data.data : [];
        const paginationInfo = data.pagination || {
            page: params.page || 1,
            per_page: params.per_page || 25,
            total: translations.length
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
                    translations,
                    headers,
                    rows,
                    paginationInfo,
                    "",
                    false,
                    'key_id',
                    null,
                    {
                        key_id: idRenderer,
                        scope: scopeRenderer,
                        domain: domainRenderer,
                        key_part: keyPartRenderer,
                        value: valueRenderer,
                        updated_at: dateRenderer,
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
    // MODAL MANAGEMENT
    // ========================================================================

    function openEditModal(keyId, keyPart, scope, domain, currentValue) {
        const modalId = 'edit-translation-modal';
        
        // Remove existing modal if any
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
            content: content,
            footer: footer,
            icon: AdminUIComponents.SVGIcons?.edit || '✎'
        });

        document.body.insertAdjacentHTML('beforeend', modalHtml);

        const modal = document.getElementById(modalId);
        modal.classList.remove('hidden');

        // Setup close handlers
        modal.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', () => modal.remove());
        });

        // Setup form submission
        const form = document.getElementById('edit-translation-form');
        const submitBtn = modal.querySelector('button[type="submit"]');

        submitBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            const value = formData.get('value')?.trim();
            
            if (!value) {
                ApiHandler.showAlert('danger', 'Translation value is required');
                return;
            }

            const payload = {
                key_id: parseInt(formData.get('key_id')),
                value: value
            };

            const endpoint = `languages/${languageId}/translations/upsert`;
            const result = await ApiHandler.call(endpoint, payload, 'Upsert Translation');

            if (result.success) {
                ApiHandler.showAlert('success', 'Translation saved successfully');
                modal.remove();
                loadTranslations(); // Reload table
            } else {
                ApiHandler.showAlert('danger', result.error || 'Failed to save translation');
            }
        });
    }

    // ========================================================================
    // ACTION HANDLERS
    // ========================================================================

    async function handleDelete(keyId, keyPart) {
        if (!confirm(`Are you sure you want to clear the translation for "${keyPart}"?`)) return;

        const endpoint = `languages/${languageId}/translations/delete`;
        const payload = { key_id: parseInt(keyId) };

        const result = await ApiHandler.call(endpoint, payload, 'Delete Translation');

        if (result.success) {
            ApiHandler.showAlert('success', 'Translation cleared successfully');
            loadTranslations(); // Reload table
        } else {
            ApiHandler.showAlert('danger', result.error || 'Failed to clear translation');
        }
    }

    // ========================================================================
    // EVENT DELEGATION
    // ========================================================================

    function setupEventDelegation() {
        document.addEventListener('click', (e) => {
            // Edit Button
            const editBtn = e.target.closest('.edit-translation-btn');
            if (editBtn) {
                const keyId = editBtn.dataset.entityId;
                const keyPart = editBtn.dataset.keyPart;
                const scope = editBtn.dataset.scope;
                const domain = editBtn.dataset.domain;
                const currentValue = editBtn.dataset.currentValue;
                if (keyId) openEditModal(keyId, keyPart, scope, domain, currentValue);
                return;
            }

            // Delete Button
            const deleteBtn = e.target.closest('.delete-translation-btn');
            if (deleteBtn) {
                const keyId = deleteBtn.dataset.entityId;
                const keyPart = deleteBtn.dataset.keyPart;
                if (keyId) handleDelete(keyId, keyPart);
                return;
            }
        });

        // Listen for table events (pagination)
        document.addEventListener('tableAction', (e) => {
            const { action, value } = e.detail;
            if (action === 'pageChange') {
                loadTranslations(value);
            } else if (action === 'perPageChange') {
                loadTranslations(1, value);
            }
        });

        // Filter Form Listeners
        const filterForm = document.getElementById('translations-filter-form');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                loadTranslations(1);
            });
        }

        // Filter Search Button Listener
        const filterSearchBtn = document.getElementById('btn-filter-search');
        if (filterSearchBtn) {
            filterSearchBtn.addEventListener('click', (e) => {
                e.preventDefault();
                loadTranslations(1);
            });
        }

        const resetBtn = document.getElementById('btn-reset-filters');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (filterForm) filterForm.reset();
                loadTranslations(1);
            });
        }

        // Global Search Listeners
        const searchBtn = document.getElementById('btn-search-global');
        if (searchBtn) {
            searchBtn.addEventListener('click', () => {
                loadTranslations(1);
            });
        }

        const clearSearchBtn = document.getElementById('btn-clear-search');
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', () => {
                const searchInput = document.getElementById('translation-search-global');
                if (searchInput) searchInput.value = '';
                loadTranslations(1);
            });
        }

        const searchInput = document.getElementById('translation-search-global');
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    loadTranslations(1);
                }
            });
        }
    }

    // ========================================================================
    // INITIALIZATION
    // ========================================================================

    function init() {
        console.log('🎬 Initializing I18n Translations List Module...');

        setupEventDelegation();
        loadTranslations();

        // Export reload function
        window.reloadTranslationsTable = function() {
            loadTranslations(currentPage, currentPerPage);
        };

        console.log('✅ I18n Translations List Module initialized');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
