/**
 * I18n Scope+Domain Keys Coverage Page
 *
 * Handles the Keys Coverage Report table logic.
 *
 * Contract:
 * - Reads context from window.i18nScopeDomainKeysContext
 * - Uses DataTable for listing
 * - Supports filtering by language, missing status, etc.
 * - REPORT ONLY: No upsert/delete actions.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Validate Context
    if (!window.i18nScopeDomainKeysContext) {
        console.error('❌ Missing window.i18nScopeDomainKeysContext');
        return;
    }

    const context = window.i18nScopeDomainKeysContext;
    const scopeId = context.scope_id;
    const domainId = context.domain_id;
    const languages = context.languages || [];

    console.log('🚀 Initializing Keys Coverage for Scope:', scopeId, 'Domain:', domainId);

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
    const headers = ['ID', 'Key Part', 'Description', 'Total Languages', 'Missing'];
    const rowKeys = ['id', 'key_part', 'description', 'total_languages', 'missing_count'];

    // Custom Renderers for specific columns
    const customRenderers = {
        key_part: (data) => `<code class="text-sm font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-blue-600 dark:text-blue-400">${data}</code>`,
        description: (data) => data || '<span class="text-gray-400 italic">—</span>',
        total_languages: (data) => `<span class="font-semibold">${data}</span>`,
        missing_count: (data) => {
            if (data > 0) {
                return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            ${data} Missing
                        </span>`;
            }
            return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        Complete
                    </span>`;
        }
    };

    // 4. Initialize DataTable
    const containerId = 'keys-coverage-table-container';
    const apiUrl = `/api/i18n/scopes/${scopeId}/domains/${domainId}/keys/query`;

    // Helper to read filters
    const getFilters = () => {
        const filters = {};

        // Key Part
        const keyPart = document.getElementById('filter-key-part')?.value;
        if (keyPart) filters.key_part = keyPart;

        // Language ID
        let languageId = '';
        if (languageSelect) {
            languageId = languageSelect.getValue();
        } else {
            // Fallback if Select2 failed or not used
            const el = document.getElementById('translation-filter-language-id');
            if (el && el.tagName === 'SELECT') languageId = el.value;
        }
        
        if (languageId) filters.language_id = languageId;

        // Missing Status
        const missing = document.getElementById('filter-missing')?.value;
        if (missing) filters.missing = missing;

        // Language Active Status
        const langActive = document.getElementById('filter-language-is-active')?.value;
        if (langActive) filters.language_is_active = langActive;

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

    // Initialize DataTable using the global createTable function (from data_table.js)
    
    let currentPage = 1;
    let currentPerPage = 25;

    const loadTable = () => {
        const filters = getFilters();
        const globalSearch = document.getElementById('keys-search-global')?.value || '';
        
        const params = {
            page: currentPage,
            per_page: currentPerPage
        };

        // Construct search object only if needed
        const search = {};
        
        if (globalSearch) {
            search.global = globalSearch;
        }
        
        if (Object.keys(filters).length > 0) {
            search.columns = filters;
        }
        
        if (Object.keys(search).length > 0) {
            params.search = search;
        }

        // IMPORTANT: The TableComponent in data_table.js expects the container to have id="table-container"
        // We need to temporarily hijack the ID or ensure our container has that ID.
        
        const container = document.getElementById(containerId);
        if (!container) {
            console.error(`❌ Container #${containerId} not found`);
            return;
        }

        // Check if we need to swap IDs to satisfy TableComponent hardcoded selector
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
            getPaginationInfo // getPaginationInfo callback
        ).then(() => {
             // Restore IDs after table is created
             container.id = originalContainerId;
             if (tempId && originalTableContainer) {
                 originalTableContainer.id = 'table-container';
             }
        }).catch(err => {
             console.error("Table creation failed", err);
             // Restore IDs even on error
             container.id = originalContainerId;
             if (tempId && originalTableContainer) {
                 originalTableContainer.id = 'table-container';
             }
        });
    };

    // Initial Load
    loadTable();

    // 5. Bind Events
    
    // Filter Form Submit
    const filterForm = document.getElementById('keys-coverage-filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            currentPage = 1; // Reset to page 1 on filter
            loadTable();
        });

        const resetBtn = document.getElementById('btn-reset-filters');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                filterForm.reset();
                // Reset Select2 if used - Re-initialize with default
                if (languageSelect) {
                    // Manually reset Select2 to default (empty)
                    const input = document.querySelector('#translation-filter-language-id .js-select-input');
                    if (input) input.value = 'All Languages';
                    const container = document.querySelector('#translation-filter-language-id');
                    if (container) container.dataset.value = '';
                }
                currentPage = 1;
                loadTable();
            });
        }
    }

    // Global Search
    const searchBtn = document.getElementById('btn-search-global');
    if (searchBtn) {
        searchBtn.addEventListener('click', () => {
            currentPage = 1;
            loadTable();
        });
    }

    const clearSearchBtn = document.getElementById('btn-clear-search');
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', () => {
            const searchInput = document.getElementById('keys-search-global');
            if (searchInput) searchInput.value = '';
            currentPage = 1;
            loadTable();
        });
    }

    // Listen for Table Events (Pagination, Per Page)
    document.addEventListener('tableAction', (e) => {
        const { action, value } = e.detail;
        if (action === 'pageChange') {
            currentPage = value;
            loadTable();
        } else if (action === 'perPageChange') {
            currentPerPage = value;
            currentPage = 1; // Reset to page 1 on per page change
            loadTable();
        }
    });
});
