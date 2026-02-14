/**
 * I18n Scope+Domain Translations Page
 *
 * Handles the Translations List table logic.
 *
 * Contract:
 * - Reads context from window.i18nScopeDomainTranslationsContext
 * - Uses DataTable for listing
 * - Supports filtering by key, language, value
 * - Supports inline upsert and delete actions
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Validate Context
    if (!window.i18nScopeDomainTranslationsContext) {
        console.error('❌ Missing window.i18nScopeDomainTranslationsContext');
        return;
    }

    const context = window.i18nScopeDomainTranslationsContext;
    const capabilities = window.ScopeDomainTranslationsCapabilities || {};
    const scopeId = context.scope_id;
    const domainId = context.domain_id;
    const languages = context.languages || [];

    console.log('🚀 Initializing Translations for Scope:', scopeId, 'Domain:', domainId);

    // 2. Initialize Select2 for Language Filter
    let languageSelect = null;
    if (window.Select2) {
        const languageOptions = [
            { value: '', label: 'All Languages', search: 'all' },
            ...languages.map(lang => ({
                value: String(lang.id),
                label: `${lang.name} (${lang.code})`,
                search: lang.code // Allow searching by code (e.g., 'ar', 'en')
            }))
        ];

        languageSelect = Select2('#translation-filter-language-id', languageOptions, {
            defaultValue: ''
        });
    }

    // 3. Define Table Columns
    const headers = ['ID', 'Key ID', 'Key Part', 'Key Description', 'Language', 'Value', 'Actions'];
    const rowKeys = ['id', 'key_id', 'key_part', 'description', 'language_name', 'value', 'actions'];

    // Custom Renderers for specific columns
    const customRenderers = {
        id: (data) => data ? `<span class="text-gray-500 text-xs font-mono">#${data}</span>` : '<span class="text-gray-300 text-xs italic">null</span>',
        key_id: (data) => `<span class="text-gray-500 text-xs font-mono">#${data}</span>`,
        key_part: (data) => `<code class="text-sm font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-blue-600 dark:text-blue-400">${data}</code>`,
        description: (data) => data ? `<span class="text-sm text-gray-600 dark:text-gray-400">${data}</span>` : '<span class="text-gray-400 italic text-xs">—</span>',
        language_name: (data, row) => {
            // Icon is an emoji, not an image URL
            const icon = row.language_icon || '';
            
            return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                        <span class="mr-1.5 text-sm">${icon}</span>
                        ${data} (${row.language_code})
                    </span>`;
        },
        value: (data, row) => {
            const val = data || '';
            const displayVal = val ? val : '<span class="text-gray-400 italic">Empty</span>';
            const dir = row.language_direction || 'ltr';
            return `<span class="translation-value block" dir="${dir}" data-key-id="${row.key_id}" data-language-id="${row.language_id}">${displayVal}</span>`;
        },
        actions: (data, row) => {
            const actions = [];
            
            // Edit / Upsert Button
            if (capabilities.can_upsert) {
                // Escape value for attribute
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

            // Delete Button (only if translation exists, i.e., id is not null)
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

    // 4. Initialize DataTable
    const containerId = 'translations-table-container';
    const apiUrl = `/api/i18n/scopes/${scopeId}/domains/${domainId}/translations/query`;

    // Helper to read filters
    const getFilters = () => {
        const filters = {};

        // Key ID
        const keyId = document.getElementById('filter-key-id')?.value?.trim();
        if (keyId) filters.key_id = keyId;

        // Key Part
        const keyPart = document.getElementById('filter-key-part')?.value?.trim();
        if (keyPart) filters.key_part = keyPart;

        // Language ID
        let languageId = '';
        if (languageSelect) {
            languageId = languageSelect.getValue();
        } else {
            const el = document.getElementById('translation-filter-language-id');
            if (el && el.tagName === 'SELECT') languageId = el.value;
        }
        if (languageId) filters.language_id = languageId;

        // Value
        const value = document.getElementById('filter-value')?.value?.trim();
        if (value) filters.value = value;

        return filters;
    };

    // Pagination Info Callback
    const getPaginationInfo = (pagination) => {
        const { page = 1, per_page = 25, total = 0, filtered = total } = pagination;
        const displayCount = filtered !== undefined ? filtered : total;
        const startItem = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
        const endItem = Math.min(page * per_page, displayCount);

        let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;
        
        if (typeof filtered !== 'undefined' && filtered !== total) {
            infoText += ` <span class="text-gray-500 dark:text-gray-400">(filtered from ${total} total)</span>`;
        }

        return { total: displayCount, info: infoText };
    };

    // Initialize DataTable using the global createTable function
    let currentPage = 1;
    let currentPerPage = 25;

    const loadTable = () => {
        const filters = getFilters();
        const globalSearch = document.getElementById('translations-search-global')?.value?.trim() || '';
        
        console.log('🔍 Loading Table. Global:', globalSearch, 'Filters:', filters);

        const params = {
            page: currentPage,
            per_page: currentPerPage
        };

        const search = {};
        if (globalSearch) search.global = globalSearch;
        if (Object.keys(filters).length > 0) search.columns = filters;
        
        if (Object.keys(search).length > 0) {
            params.search = search;
        }

        // Hijack container ID for TableComponent
        const container = document.getElementById(containerId);
        if (!container) return;

        const originalTableContainer = document.getElementById('table-container');
        let tempId = null;
        
        if (originalTableContainer && originalTableContainer !== container) {
            tempId = 'table-container-original-' + Date.now();
            originalTableContainer.id = tempId;
        }
        
        const originalContainerId = container.id;
        container.id = 'table-container';

        createTable(
            apiUrl,
            params,
            headers,
            rowKeys,
            false, // withSelection
            'id', // primaryKey
            null, // onSelectionChange
            customRenderers,
            null, // selectableIds
            getPaginationInfo
        ).then(() => {
             container.id = originalContainerId;
             if (tempId && originalTableContainer) originalTableContainer.id = 'table-container';
             // No need to bind buttons here anymore, we use delegation
        }).catch(err => {
             console.error("Table creation failed", err);
             container.id = originalContainerId;
             if (tempId && originalTableContainer) originalTableContainer.id = 'table-container';
        });
    };

    // Initial Load
    loadTable();

    // 5. Bind Events
    
    // Filter Form
    const filterForm = document.getElementById('translations-filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            currentPage = 1;
            loadTable();
        });

        document.getElementById('btn-reset-filters')?.addEventListener('click', () => {
            filterForm.reset();
            if (languageSelect) {
                const input = document.querySelector('#translation-filter-language-id .js-select-input');
                if (input) input.value = 'All Languages';
                const container = document.querySelector('#translation-filter-language-id');
                if (container) container.dataset.value = '';
            }
            currentPage = 1;
            loadTable();
        });
    }

    // Global Search
    document.getElementById('btn-search-global')?.addEventListener('click', () => {
        currentPage = 1;
        loadTable();
    });

    document.getElementById('btn-clear-search')?.addEventListener('click', () => {
        const searchInput = document.getElementById('translations-search-global');
        if (searchInput) searchInput.value = '';
        currentPage = 1;
        loadTable();
    });

    // Allow Enter key for Global Search
    const globalSearchInput = document.getElementById('translations-search-global');
    if (globalSearchInput) {
        globalSearchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                currentPage = 1;
                loadTable();
            }
        });
    }

    // Table Events
    document.addEventListener('tableAction', (e) => {
        const { action, value } = e.detail;
        if (action === 'pageChange') {
            currentPage = value;
            loadTable();
        } else if (action === 'perPageChange') {
            currentPerPage = value;
            currentPage = 1;
            loadTable();
        }
    });

    // 6. Action Handlers (Upsert / Delete) - Using Event Delegation

    // Modal Elements
    const modal = document.getElementById('edit-translation-modal');
    const modalKeyPart = document.getElementById('edit-key-part-display');
    const modalLanguageName = document.getElementById('edit-language-name-display');
    const modalValue = document.getElementById('edit-translation-value');
    const modalKeyId = document.getElementById('edit-key-id');
    const modalLanguageId = document.getElementById('edit-language-id');
    const btnSave = document.getElementById('btn-save-translation');

    // Setup Event Delegation for Table Actions
    document.addEventListener('click', (e) => {
        // Edit Button
        const editBtn = e.target.closest('.btn-edit-translation');
        if (editBtn) {
            const keyId = editBtn.dataset.keyId;
            const languageId = editBtn.dataset.languageId;
            const currentValue = editBtn.dataset.value;
            const keyPart = editBtn.dataset.keyPart;
            const languageName = editBtn.dataset.languageName;
            const languageCode = editBtn.dataset.languageCode;
            const languageDirection = editBtn.dataset.languageDirection;

            openEditModal(keyId, languageId, currentValue, keyPart, languageName, languageCode, languageDirection);
            return;
        }

        // Delete Button
        const deleteBtn = e.target.closest('.btn-delete-translation');
        if (deleteBtn) {
            const keyId = deleteBtn.dataset.keyId;
            const languageId = deleteBtn.dataset.languageId;
            handleDelete(keyId, languageId);
            return;
        }
    });

    // Modal Logic
    function openEditModal(keyId, languageId, currentValue, keyPart, languageName, languageCode, languageDirection) {
        if (!modal) return;

        modalKeyId.value = keyId;
        modalLanguageId.value = languageId;
        modalValue.value = currentValue;
        modalKeyPart.textContent = keyPart;
        modalLanguageName.textContent = `${languageName} (${languageCode})`;
        
        // Set direction for textarea
        modalValue.setAttribute('dir', languageDirection || 'ltr');

        modal.classList.remove('hidden');
        modalValue.focus();
    }

    function closeEditModal() {
        if (!modal) return;
        modal.classList.add('hidden');
    }

    // Close modal on click outside or close button
    if (modal) {
        modal.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', closeEditModal);
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeEditModal();
        });

        // Save Button
        if (btnSave) {
            btnSave.addEventListener('click', () => {
                const keyId = modalKeyId.value;
                const languageId = modalLanguageId.value;
                const newValue = modalValue.value;

                if (newValue.trim() === '') {
                    ApiHandler.showAlert('warning', "Value cannot be empty. Use delete to remove translation.");
                    return;
                }

                upsertTranslation(keyId, languageId, newValue);
            });
        }
    }

    function handleDelete(keyId, languageId) {
        if (confirm("Are you sure you want to delete this translation?")) {
            deleteTranslation(keyId, languageId);
        }
    }

    async function upsertTranslation(keyId, languageId, value) {
        // Updated endpoint based on new contract: /languages/{language_id}/translations/upsert
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
                loadTable(); // Refresh table
            } else {
                ApiHandler.showAlert('danger', result.error || 'Failed to save translation');
            }
        } catch (error) {
            console.error('Upsert error:', error);
            ApiHandler.showAlert('danger', 'An error occurred while saving');
        }
    }

    async function deleteTranslation(keyId, languageId) {
        // Updated endpoint based on new contract: /languages/{language_id}/translations/delete
        const endpoint = `languages/${languageId}/translations/delete`;
        const payload = {
            key_id: keyId
        };

        try {
            const result = await ApiHandler.call(endpoint, payload, 'Delete Translation');
            if (result.success) {
                ApiHandler.showAlert('success', 'Translation deleted successfully');
                loadTable(); // Refresh table
            } else {
                ApiHandler.showAlert('danger', result.error || 'Failed to delete translation');
            }
        } catch (error) {
            console.error('Delete error:', error);
            ApiHandler.showAlert('danger', 'An error occurred while deleting');
        }
    }
});
