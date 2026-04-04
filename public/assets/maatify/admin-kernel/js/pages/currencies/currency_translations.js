/**
 * 🌍 Currency Translations Page - Context-Driven Monolith
 * ===========================================================
 * Handles the Translations List table logic for a specific Currency.
 *
 * Pattern D: Context-Driven
 * - Relies on window.currencyTranslationsContext
 */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    console.log('🌍 Currency Translations Module Initialized');

    // ========================================================================
    // 1. Validate Context & Capabilities
    // ========================================================================

    if (!window.currencyTranslationsContext) {
        console.error('❌ Missing window.currencyTranslationsContext');
        return;
    }

    const context = window.currencyTranslationsContext;
    const capabilities = window.currencyTranslationsCapabilities || {};

    const currencyId = context.currency_id;
    const languages = context.languages || [];

    console.log('🚀 Context Currency ID:', currencyId);

    // ========================================================================
    // 2. Initialize DataTable Configuration
    // ========================================================================

    const headers = ['ID', 'Language Code', 'Language Name', 'Translated Name', 'Status', 'Actions'];
    const rowKeys = ['id', 'language_code', 'language_name', 'translated_name', 'has_translation', 'actions'];

    const customRenderers = {
        id: (value) => value ? `<span class="text-gray-500 text-xs font-mono">#${value}</span>` : '<span class="text-gray-300 text-xs italic">N/A</span>',
        language_code: (value) => AdminUIComponents.renderCodeBadge(value, { color: 'blue', uppercase: true }),
        language_name: (value, row) => `<span class="font-medium text-gray-900 dark:text-gray-200">${value}</span>`,
        translated_name: (value) => value ? `<span class="font-medium text-gray-900 dark:text-gray-200">${value}</span>` : '<span class="text-gray-400 italic text-sm">Base name fallback</span>',
        has_translation: (value) => {
            if (value) {
                return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Translated</span>`;
            }
            return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Untranslated</span>`;
        },
        actions: (value, row) => {
            const actions = [];

            if (capabilities.can_upsert) {
                actions.push(AdminUIComponents.buildActionButton({
                    cssClass: 'btn-edit-translation',
                    icon: AdminUIComponents.SVGIcons.edit,
                    text: 'Edit',
                    color: 'blue',
                    entityId: row.language_id,
                    title: 'Edit translation',
                    dataAttributes: {
                        'language-id': row.language_id,
                        'language-name': row.language_name,
                        'language-code': row.language_code,
                        'translated-name': row.translated_name || ''
                    }
                }));
            }

            if (capabilities.can_delete && row.has_translation) {
                actions.push(AdminUIComponents.buildActionButton({
                    cssClass: 'btn-delete-translation',
                    icon: AdminUIComponents.SVGIcons.x,
                    text: 'Delete',
                    color: 'red',
                    entityId: row.language_id,
                    title: 'Delete translation',
                    dataAttributes: {
                        'language-id': row.language_id
                    }
                }));
            }

            if (actions.length === 0) {
                return '<span class="text-gray-400 text-xs">No actions</span>';
            }

            return `<div class="flex items-center gap-1">${actions.join('')}</div>`;
        }
    };

    // ========================================================================
    // 3. Data Loading Logic
    // ========================================================================

    let currentPage = 1;
    let currentPerPage = 20;

    function buildQueryParams() {
        const params = {
            page: currentPage,
            per_page: currentPerPage
        };

        const globalSearch = document.getElementById('translations-search-global')?.value?.trim();
        const columnFilters = {};

        const filterLanguageName = document.getElementById('filter-language-name')?.value?.trim();
        if (filterLanguageName) columnFilters.language_name = filterLanguageName;

        const filterLanguageCode = document.getElementById('filter-language-code')?.value?.trim();
        if (filterLanguageCode) columnFilters.language_code = filterLanguageCode;

        const filterTranslatedName = document.getElementById('filter-translated-name')?.value?.trim();
        if (filterTranslatedName) columnFilters.name = filterTranslatedName;

        if (globalSearch || Object.keys(columnFilters).length > 0) {
            params.search = {};
            if (globalSearch) params.search.global = globalSearch;
            if (Object.keys(columnFilters).length > 0) params.search.columns = columnFilters;
        }

        return params;
    }

    async function loadTable(pageNumber = null, perPageNumber = null) {
        if (pageNumber !== null) currentPage = pageNumber;
        if (perPageNumber !== null) currentPerPage = perPageNumber;

        const params = buildQueryParams();
        const endpoint = `/api/currencies/${currencyId}/translations/query`;

        const result = await ApiHandler.call(endpoint, params, 'Query Translations');

        if (!result.success) {
            const container = document.getElementById('translations-table-container');
            if (container) {
                container.innerHTML = `
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Error!</strong>
                        <span class="block sm:inline">${result.error || 'Failed to load translations.'}</span>
                    </div>
                `;
            }
            return;
        }

        // Handle array response (since translations query is not paginated natively, backend returns array directly, but our query controller returns data envelope)
        const data = result.data || {};
        // If data is an array directly (as per contract `[ {...} ]`)
        const items = Array.isArray(data) ? data : (Array.isArray(data.data) ? data.data : []);
        const paginationInfo = data.pagination || {
            page: currentPage,
            per_page: currentPerPage,
            total: items.length
        };

        // Render Table inside 'translations-table-container'
        const container = document.getElementById('translations-table-container');
        if (!container) return;

        // Temporary ID switch for createTable
        const originalId = container.id;
        container.id = 'table-container';

        try {
            if (typeof createTable === 'function') {
                createTable(
                    items,
                    headers,
                    rowKeys,
                    paginationInfo,
                    "",
                    false,
                    'language_id',
                    null,
                    customRenderers
                );
            } else {
                console.error("❌ createTable function not found");
            }
        } finally {
            // Restore ID
            container.id = originalId;
        }
    }

    // ========================================================================
    // 4. Setup Filters & Events
    // ========================================================================

    function setupSearchAndFilters() {
        const globalSearchInput = document.getElementById('translations-search-global');
        const searchBtn = document.getElementById('btn-search-global');
        const clearBtn = document.getElementById('btn-clear-search');
        const filterForm = document.getElementById('translations-filter-form');
        const resetBtn = document.getElementById('btn-reset-filters');

        let searchTimeout;
        if (globalSearchInput) {
            globalSearchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentPage = 1;
                    loadTable();
                }, 500);
            });
            globalSearchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    currentPage = 1;
                    loadTable();
                }
            });
        }

        if (searchBtn) {
            searchBtn.addEventListener('click', () => {
                currentPage = 1;
                loadTable();
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                if (globalSearchInput) globalSearchInput.value = '';
                currentPage = 1;
                loadTable();
            });
        }

        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                currentPage = 1;
                loadTable();
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (filterForm) filterForm.reset();
                currentPage = 1;
                loadTable();
            });
        }
    }

    // ========================================================================
    // 5. Actions & Modals (Delegation)
    // ========================================================================

    const modal = document.getElementById('edit-translation-modal');
    const modalLanguageId = document.getElementById('edit-language-id');
    const modalCurrencyDisplay = document.getElementById('edit-currency-display');
    const modalLanguageNameDisplay = document.getElementById('edit-language-name-display');
    const modalTranslationValue = document.getElementById('edit-translation-value');
    const btnSaveTranslation = document.getElementById('btn-save-translation');

    function openEditModal(languageId, languageName, languageCode, translatedName) {
        if (!modal) return;

        modalLanguageId.value = languageId;
        modalCurrencyDisplay.textContent = context.currency_code;
        modalLanguageNameDisplay.textContent = `${languageName} (${languageCode})`;
        modalTranslationValue.value = translatedName;

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Close handlers
    if (modal) {
        modal.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', closeEditModal);
        });
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeEditModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeEditModal();
            }
        });
    }

    // Save Action
    if (btnSaveTranslation) {
        btnSaveTranslation.addEventListener('click', async () => {
            const languageId = parseInt(modalLanguageId.value, 10);
            const value = modalTranslationValue.value.trim();

            if (!value) {
                ApiHandler.showAlert('warning', 'Translation value is required.');
                return;
            }

            const payload = {
                language_id: languageId,
                translated_name: value
            };

            const endpoint = `/api/currencies/${currencyId}/translations/upsert`;
            const result = await ApiHandler.call(endpoint, payload, 'Upsert Translation');

            if (result.success) {
                ApiHandler.showAlert('success', 'Translation saved successfully.');
                closeEditModal();
                loadTable();
            }
        });
    }

    // Delete Action
    async function deleteTranslation(languageId) {
        if (!confirm('Are you sure you want to delete this translation?')) return;

        const payload = {
            language_id: parseInt(languageId, 10)
        };

        const endpoint = `/api/currencies/${currencyId}/translations/delete`;
        const result = await ApiHandler.call(endpoint, payload, 'Delete Translation');

        if (result.success) {
            ApiHandler.showAlert('success', 'Translation deleted successfully.');
            loadTable();
        }
    }

    // Event Delegation
    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.btn-edit-translation');
        if (editBtn) {
            openEditModal(
                editBtn.getAttribute('data-language-id'),
                editBtn.getAttribute('data-language-name'),
                editBtn.getAttribute('data-language-code'),
                editBtn.getAttribute('data-translated-name')
            );
            return;
        }

        const deleteBtn = e.target.closest('.btn-delete-translation');
        if (deleteBtn) {
            deleteTranslation(deleteBtn.getAttribute('data-language-id'));
            return;
        }
    });

    // ========================================================================
    // 6. Global Exposes & Init
    // ========================================================================

    document.addEventListener('tableAction', (e) => {
        const { action, value } = e.detail;
        if (action === 'changePage') {
            currentPage = value;
            loadTable();
        } else if (action === 'changePerPage') {
            currentPerPage = value;
            currentPage = 1;
            loadTable();
        }
    });

    window.reloadCurrenciesTable = () => loadTable(currentPage, currentPerPage);

    // Run
    setupSearchAndFilters();
    loadTable();

});
