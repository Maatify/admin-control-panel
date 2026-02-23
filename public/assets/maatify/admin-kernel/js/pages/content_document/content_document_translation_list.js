/**
 * Content Document Translations List
 *
 * Manages the UI for listing content document translations.
 *
 * 🔐 AUTHORIZATION:
 * - Uses `window.contentDocumentTranslationsCapabilities` for permission checks.
 * - Uses `window.contentDocumentTranslationsApi` for API endpoints.
 * - Uses `window.typeId` and `window.documentId` for scoping.
 */

document.addEventListener('DOMContentLoaded', () => {
    // ========================================================================
    // 1. Initialization & Capabilities
    // ========================================================================

    const capabilities = window.contentDocumentTranslationsCapabilities || {};
    const apiEndpoints = window.contentDocumentTranslationsApi || {};
    const typeId = window.typeId;
    const documentId = window.documentId;
    const tableContainerId = 'content-document-versions-table-container';

    if (!typeId || !documentId) {
        console.error("❌ Missing typeId or documentId in window context.");
        return;
    }

    console.log("🚀 Content Document Translations Initialized", {
        typeId,
        documentId,
        capabilities,
        apiEndpoints
    });

    // ========================================================================
    // 2. Table Configuration
    // ========================================================================

    // Define headers and rows
    const headers = [
        "Language",
        "Code",
        "Direction",
        "Has Translation",
        "Updated At",
        "Actions"
    ];

    const rows = [
        "language_name",
        "language_code",
        "language_direction",
        "has_translation",
        "updated_at",
        "actions"
    ];

    // ========================================================================
    // 3. Custom Renderers
    // ========================================================================

    const renderers = {
        // Language Name Renderer
        language_name: (value, row) => {
            // Icon is an emoji, not an image URL
            const icon = row.language_icon || '';
            return `<div class="flex items-center"><span class="mr-2 text-lg">${icon}</span><span class="font-medium text-gray-900 dark:text-gray-100">${value}</span></div>`;
        },

        // Language Code Renderer
        language_code: (value, row) => {
            return window.AdminUIComponents.renderCodeBadge(value, { color: 'blue' });
        },

        // Language Direction Renderer
        language_direction: (value, row) => {
            return window.AdminUIComponents.renderDirectionBadge(value);
        },

        // Has Translation Renderer
        has_translation: (value, row) => {
            return window.AdminUIComponents.renderStatusBadge(value, {
                activeText: 'Yes',
                inactiveText: 'No',
                clickable: false
            });
        },

        // Updated At Renderer
        updated_at: (value, row) => {
            if (!value) return '<span class="text-gray-400 dark:text-gray-500 italic text-xs">-</span>';
            return `<span class="text-xs text-gray-600 dark:text-gray-400">${value}</span>`;
        },

        // Actions Renderer
        actions: (value, row) => {
            const buttons = [];
            const translationId = row.translation_id;
            const hasTranslation = row.has_translation;

            if (capabilities.can_view_translation_details) {
                // Determine action: Create or Edit
                const actionText = hasTranslation ? 'Edit' : 'Create';
                const actionIcon = hasTranslation ? window.AdminUIComponents.SVGIcons.edit : window.AdminUIComponents.SVGIcons.plus;
                const actionColor = hasTranslation ? 'blue' : 'green';
                
                let uiUrl = apiEndpoints.translation_details
                    .replace('{type_id}', typeId)
                    .replace('{document_id}', documentId)
                    .replace('{translation_id}', translationId || 'new');
                
                if (!translationId) {
                    uiUrl += `?language_id=${row.language_id}`;
                }

                buttons.push(window.AdminUIComponents.buildActionButton({
                    cssClass: '',
                    icon: actionIcon,
                    text: actionText,
                    color: actionColor,
                    entityId: translationId || `new-${row.language_id}`,
                    title: `${actionText} Translation`,
                    dataAttributes: { href: uiUrl }
                }).replace('<button', `<a href="${uiUrl}"`).replace('</button>', '</a>'));
            }

            return `<div class="flex items-center gap-2 justify-end">${buttons.join('')}</div>`;
        }
    };

    // ========================================================================
    // 4. Filter & Search Logic
    // ========================================================================

    const filterForm = document.getElementById('content-document-versions-filter-form');
    const resetBtn = document.getElementById('content-document-versions-reset-filters');
    const searchInput = document.getElementById('content-document-versions-search');
    const searchBtn = document.getElementById('content-document-versions-search-btn');
    const clearSearchBtn = document.getElementById('content-document-versions-clear-search');

    // Filter Inputs
    const inputHasTranslation = document.getElementById('filter-has-translation');
    // Language Select2 will be initialized later
    let languageSelect;

    let currentPage = 1;
    let currentPerPage = 25;

    function buildParams(page = 1, perPage = 25) {
        const params = {
            page: page,
            per_page: perPage,
            search: {
                columns: {}
            }
        };

        // Global Search
        if (searchInput && searchInput.value.trim()) {
            params.search.global = searchInput.value.trim();
        }

        // Column Filters
        if (inputHasTranslation && inputHasTranslation.value !== "") {
            params.search.columns.has_translation = inputHasTranslation.value;
        }
        
        if (languageSelect) {
            const selectedLang = languageSelect.getValue();
            if (selectedLang) {
                params.search.columns.language_id = selectedLang;
            }
        }

        // Cleanup empty objects
        if (Object.keys(params.search.columns).length === 0) {
            delete params.search.columns;
        }
        if (Object.keys(params.search).length === 0) {
            delete params.search;
        }

        return params;
    }

    async function loadTranslations(page = 1) {
        currentPage = page;
        const params = buildParams(page, currentPerPage);
        
        // Construct query URL
        const endpoint = apiEndpoints.query
            .replace('{type_id}', typeId)
            .replace('{document_id}', documentId);

        // Hijack container ID for TableComponent
        const container = document.getElementById(tableContainerId);
        if (!container) return;

        const originalTableContainer = document.getElementById('table-container');
        let tempId = null;

        if (originalTableContainer && originalTableContainer !== container) {
            tempId = 'table-container-original-' + Date.now();
            originalTableContainer.id = tempId;
        }

        const originalContainerId = container.id;
        container.id = 'table-container';

        if (typeof createTable === 'function') {
            await createTable(
                endpoint,
                params,
                headers,
                rows,
                false, // no checkboxes
                'language_id', // primaryKey
                null,
                renderers,
                null,
                null // default pagination info
            ).then(() => {
                container.id = originalContainerId;
                if (tempId && originalTableContainer) originalTableContainer.id = 'table-container';
            }).catch(err => {
                console.error("Table creation failed", err);
                container.id = originalContainerId;
                if (tempId && originalTableContainer) originalTableContainer.id = 'table-container';
            });
        } else {
            console.error("❌ createTable function not found.");
            container.id = originalContainerId;
            if (tempId && originalTableContainer) originalTableContainer.id = 'table-container';
        }
    }

    // ========================================================================
    // 5. Language Dropdown Initialization
    // ========================================================================

    function initLanguageDropdown() {
        // Use injected languages list if available
        // The contract says: "Languages list is injected at page render time."
        // window.contentDocumentTranslationsContext.languages
        
        const context = window.contentDocumentTranslationsContext || {};
        const languages = context.languages || [];

        if (languages.length > 0) {
            const options = languages.map(lang => ({
                value: String(lang.id),
                label: `${lang.name} (${lang.code})`,
                search: lang.code // Allow searching by code
            }));

            languageSelect = Select2('#translation-filter-language-id', options, {
                defaultValue: null,
                onChange: (value) => {
                    // Optional: auto-reload on change
                    // loadTranslations(1); 
                }
            });
        } else {
            console.warn("⚠️ No languages found in context.");
        }
    }

    // ========================================================================
    // 6. Event Listeners
    // ========================================================================

    if (filterForm) {
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            loadTranslations(1);
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            filterForm.reset();
            
            // Reset Select2 manually since it doesn't have a reset method in the current version
            // We need to clear the input and data attribute
            const selectContainer = document.querySelector('#translation-filter-language-id');
            if (selectContainer) {
                const input = selectContainer.querySelector('.js-select-input');
                if (input) {
                    input.value = '';
                    // Dispatch change event if needed, though Select2 internal state might not update perfectly without destroy/re-init
                    // But based on Select2 implementation, it reads from container.dataset.value or internal state.
                    // Since we can't access internal state easily without the instance exposing a reset method (which it doesn't seem to fully support based on provided code),
                    // we will try to re-initialize it or just clear the visual part and let the next buildParams pick up empty value.
                    
                    // Actually, Select2 implementation has:
                    // container.dataset.value = item.value;
                    // So clearing dataset.value should work for our buildParams logic if we read from it?
                    // But buildParams uses languageSelect.getValue() which uses internal 'selected' variable.
                    
                    // Since we cannot modify Select2.js, we have to destroy and re-init to clear selection properly
                    if (languageSelect) {
                        languageSelect.destroy();
                        initLanguageDropdown(); // Re-init with default null
                    }
                }
            }

            if (searchInput) searchInput.value = '';
            loadTranslations(1);
        });
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', () => loadTranslations(1));
    }

    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                loadTranslations(1);
            }
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            loadTranslations(1);
        });
    }

    // Table Events
    document.addEventListener('tableAction', (e) => {
        const { action, value } = e.detail;
        if (action === 'pageChange') {
            currentPage = value;
            loadTranslations(currentPage);
        } else if (action === 'perPageChange') {
            currentPerPage = value;
            currentPage = 1;
            loadTranslations(1);
        }
    });

    // ========================================================================
    // 7. Initial Load
    // ========================================================================

    initLanguageDropdown();
    loadTranslations();
});
