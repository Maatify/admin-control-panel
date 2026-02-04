/**
 * 🌍 Languages Management - Core Module
 * ========================================
 * Handles API integration, data loading, and orchestration
 *
 * Pattern: Following sessions.js architecture
 * - Core logic here
 * - Table rendering delegated to data_table.js
 * - UI interactions in languages-modals.js
 *
 * Capabilities respected:
 * - can_create   → Create new language
 * - can_update   → Update language settings
 * - can_active   → Activate/deactivate language
 * - can_fallback → Set fallback language
 */

document.addEventListener('DOMContentLoaded', () => {
    console.log('🌍 Languages Module Initialized');
    console.log('🔐 Capabilities:', window.languagesCapabilities);

    // ========================================================================
    // Table Configuration
    // ========================================================================

    const headers = ["ID", "Name", "Code", "Direction", "Sort", "Status", "Fallback", "Actions"];
    const rows = ["id", "name", "code", "direction", "sort_order", "is_active", "fallback_language_id", "actions"];

    // ========================================================================
    // DOM Elements
    // ========================================================================

    const searchForm = document.getElementById('languages-search-form');
    const resetBtn = document.getElementById('btn-reset');
    const btnCreateLanguage = document.getElementById('btn-create-language');

    // Global search (above table)
    const globalSearchInput = document.getElementById('global-search');
    const btnGlobalSearch = document.getElementById('btn-global-search');
    const btnClearGlobalSearch = document.getElementById('btn-clear-global-search');

    // Search inputs (in filters form)
    const inputId = document.getElementById('filter-id');
    const inputSearch = document.getElementById('filter-search');
    const inputCode = document.getElementById('filter-code');
    const inputDirection = document.getElementById('filter-direction');
    const inputStatus = document.getElementById('filter-status');

    // ========================================================================
    // Custom Renderers
    // ========================================================================

    /**
     * ✅ Render language name with icon (if exists)
     * Phase 5: Enhanced icon presentation
     * Phase 6: Added data-field wrapper for inline editing
     */
    const nameRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';

        // Enhanced icon with better spacing and size
        const icon = row.icon
            ? `<span class="flex items-center justify-center w-7 h-7 rounded-md bg-gray-50 border border-gray-200 text-base mr-2">${row.icon}</span>`
            : `<span class="flex items-center justify-center w-7 h-7 rounded-md bg-gray-100 border border-gray-300 text-gray-400 text-xs mr-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                </svg>
            </span>`;

        // Wrap in div with data-field for inline editing
        return `<div class="flex items-center" data-field="name">${icon}<span class="font-medium text-gray-900">${value}</span></div>`;
    };

    /**
     * ✅ Render language code as badge
     * Phase 6: Added data-field wrapper for inline editing
     */
    const codeRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';

        // Wrap in div with data-field for inline editing
        return `<div data-field="code"><span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-mono font-medium bg-blue-100 text-blue-800 uppercase">${value}</span></div>`;
    };

    /**
     * ✅ Render direction with visual indicator
     */
    const directionRenderer = (value, row) => {
        const isRTL = value?.toLowerCase() === 'rtl';
        const arrow = isRTL ? '←' : '→';
        const bgColor = isRTL ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800';

        return `<span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium ${bgColor}">
            <span class="mr-1">${arrow}</span>
            ${value?.toUpperCase() || 'N/A'}
        </span>`;
    };

    /**
     * ✅ Render sort order as enhanced number badge
     * Phase 5: Better visual design with gradient
     */
    const sortRenderer = (value, row) => {
        if (value === null || value === undefined) {
            return '<span class="text-gray-400 italic">N/A</span>';
        }

        return `<span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-200 text-indigo-700 text-sm font-bold shadow-sm">${value}</span>`;
    };

    /**
     * ✅ Render status with enhanced badge (icons + colors)
     * Phase 5: Added icons and improved visual design
     */
    const statusRenderer = (value, row) => {
        const isActive = value === true || value === 1 || value === "1";
        const canActive = window.languagesCapabilities?.can_active ?? false;

        // Enhanced colors and icons
        if (isActive) {
            const badge = `
                <span class="inline-flex items-center gap-1 bg-green-100 text-green-800 border border-green-300 px-3 py-1 rounded-lg text-xs font-semibold">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Active
                </span>
            `;

            if (canActive) {
                return `<button class="toggle-status-btn hover:opacity-75 transition-opacity cursor-pointer"
                            data-language-id="${row.id}"
                            data-current-status="1"
                            title="Click to deactivate">
                    ${badge}
                </button>`;
            }
            return badge;
        } else {
            const badge = `
                <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 border border-gray-300 px-3 py-1 rounded-lg text-xs font-semibold">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    Inactive
                </span>
            `;

            if (canActive) {
                return `<button class="toggle-status-btn hover:opacity-75 transition-opacity cursor-pointer"
                            data-language-id="${row.id}"
                            data-current-status="0"
                            title="Click to activate">
                    ${badge}
                </button>`;
            }
            return badge;
        }
    };

    /**
     * ✅ Render fallback language info
     */
    /**
     * ✅ Render fallback with enhanced badge
     * Phase 5: Added visual indicators and better design
     */
    const fallbackRenderer = (value, row) => {
        if (!value) {
            return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-gray-50 text-gray-500 border border-gray-200">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
                None
            </span>`;
        }

        // Value is fallback_language_id - show with link icon
        return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-purple-100 text-purple-800 border border-purple-200" title="Falls back to Language ID ${value}">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
            </svg>
            → ID ${value}
        </span>`;
    };

    /**
     * ✅ Render actions buttons (respecting capabilities)
     * Phase 5: Enhanced button design with better icons and layout
     */
    const actionsRenderer = (value, row) => {
        // Get all capabilities
        const canUpdate = window.languagesCapabilities?.can_update ?? false;
        const canUpdateName = window.languagesCapabilities?.can_update_name ?? false;
        const canUpdateCode = window.languagesCapabilities?.can_update_code ?? false;
        const canUpdateSort = window.languagesCapabilities?.can_update_sort ?? false;
        const canFallbackSet = window.languagesCapabilities?.can_fallback_set ?? false;
        const canFallbackClear = window.languagesCapabilities?.can_fallback_clear ?? false;

        // Check if any actions available
        const hasAnyAction = canUpdate || canUpdateName || canUpdateCode || canUpdateSort || canFallbackSet || canFallbackClear;

        if (!hasAnyAction) {
            return '<span class="text-gray-400 text-xs">No actions</span>';
        }

        const actions = [];

        // Edit Settings (direction + icon) - Modal
        if (canUpdate) {
            actions.push(`
                <button class="edit-settings-btn inline-flex items-center gap-1 px-2 py-1 text-blue-600 hover:bg-blue-50 rounded transition-colors text-xs font-medium border border-transparent hover:border-blue-200" 
                        data-language-id="${row.id}" 
                        title="Edit direction and icon">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Settings
                </button>
            `);
        }

        // Edit Name - Inline editing
        if (canUpdateName) {
            actions.push(`
                <button class="edit-name-btn inline-flex items-center gap-1 px-2 py-1 text-green-600 hover:bg-green-50 rounded transition-colors text-xs font-medium border border-transparent hover:border-green-200" 
                        data-language-id="${row.id}" 
                        data-current-name="${row.name}" 
                        title="Edit language name">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Name
                </button>
            `);
        }

        // Edit Code - Inline editing
        if (canUpdateCode) {
            actions.push(`
                <button class="edit-code-btn inline-flex items-center gap-1 px-2 py-1 text-amber-600 hover:bg-amber-50 rounded transition-colors text-xs font-medium border border-transparent hover:border-amber-200" 
                        data-language-id="${row.id}" 
                        data-current-code="${row.code}" 
                        title="Edit language code">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    Code
                </button>
            `);
        }

        // Update Sort Order - Modal
        if (canUpdateSort) {
            actions.push(`
                <button class="update-sort-btn inline-flex items-center gap-1 px-2 py-1 text-indigo-600 hover:bg-indigo-50 rounded transition-colors text-xs font-medium border border-transparent hover:border-indigo-200" 
                        data-language-id="${row.id}" 
                        title="Update sort order">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                    </svg>
                    Sort
                </button>
            `);
        }

        // Set/Clear Fallback - Context-aware button
        if (canFallbackSet || canFallbackClear) {
            // Check if this language has a fallback set
            const hasFallback = row.fallback_language_id !== null && row.fallback_language_id !== undefined;

            if (hasFallback && canFallbackClear) {
                // Show Clear Fallback button
                actions.push(`
                    <button class="clear-fallback-btn inline-flex items-center gap-1 px-2 py-1 text-red-600 hover:bg-red-50 rounded transition-colors text-xs font-medium border border-transparent hover:border-red-200" 
                            data-language-id="${row.id}" 
                            title="Clear fallback language">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                        Clear Fallback
                    </button>
                `);
            } else if (canFallbackSet) {
                // Show Set Fallback button
                actions.push(`
                    <button class="set-fallback-btn inline-flex items-center gap-1 px-2 py-1 text-purple-600 hover:bg-purple-50 rounded transition-colors text-xs font-medium border border-transparent hover:border-purple-200" 
                            data-language-id="${row.id}" 
                            title="Set fallback language">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        Set Fallback
                    </button>
                `);
            }
        }

        if (actions.length === 0) {
            return '<span class="text-gray-400 text-xs">No actions</span>';
        }

        // Return actions in a flex container with better spacing
        return `<div class="flex items-center gap-1 flex-wrap">${actions.join('')}</div>`;
    };

    // ========================================================================
    // Initialize
    // ========================================================================

    init();

    function init() {
        loadLanguages();
        setupEventListeners();
        setupTableEventListeners();
    }

    // ========================================================================
    // Event Listeners
    // ========================================================================

    function setupEventListeners() {
        // Search form submit
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                loadLanguages();
            });
        }

        // Reset button
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (inputId) inputId.value = '';
                if (inputSearch) inputSearch.value = '';
                if (inputCode) inputCode.value = '';
                if (inputDirection) inputDirection.value = '';
                if (inputStatus) inputStatus.value = '';
                loadLanguages();
            });
        }

        // Global search (above table)
        if (btnGlobalSearch && globalSearchInput) {
            btnGlobalSearch.addEventListener('click', () => {
                loadLanguages();
            });

            // Enter key in global search
            globalSearchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    loadLanguages();
                }
            });
        }

        // Clear global search
        if (btnClearGlobalSearch && globalSearchInput) {
            btnClearGlobalSearch.addEventListener('click', () => {
                globalSearchInput.value = '';
                loadLanguages();
            });
        }

        // Create language button
        if (btnCreateLanguage) {
            const canCreate = window.languagesCapabilities?.can_create ?? false;
            if (canCreate) {
                btnCreateLanguage.addEventListener('click', () => {
                    if (typeof window.openCreateLanguageModal === 'function') {
                        window.openCreateLanguageModal();
                    } else {
                        console.error('❌ openCreateLanguageModal not found. Check if languages-modals.js is loaded.');
                        showAlert('danger', 'Modal system not loaded. Please refresh the page.');
                    }
                });
            } else {
                btnCreateLanguage.style.display = 'none'; // Hide if no permission
            }
        }

        // Delegate click events for dynamic buttons
        document.addEventListener('click', (e) => {
            // NOTE: All action button handlers have been moved to their respective modules:
            // - Toggle status → languages-actions.js
            // - Edit name → languages-actions.js
            // - Edit code → languages-actions.js
            // - Update sort → languages-actions.js
            // - Edit settings → languages-modals.js (handled there)
            // - Set/Clear fallback → languages-fallback.js

            // This event listener is kept for backward compatibility
            // but most handlers are now in dedicated modules
        });
    }

    /**
     * Setup table event listeners (pagination, per_page changes)
     */
    function setupTableEventListeners() {
        document.addEventListener('tableAction', async (e) => {
            const { action, value, currentParams } = e.detail;
            console.log("🔨 Table event:", action, value);

            let newParams = JSON.parse(JSON.stringify(currentParams));

            switch(action) {
                case 'pageChange':
                    newParams.page = value;
                    break;

                case 'perPageChange':
                    newParams.per_page = value;  // ✅ Use per_page, not limit
                    newParams.page = 1;
                    break;
            }

            // Clean empty values
            cleanParams(newParams);

            await loadLanguagesWithParams(newParams);
        });
    }

    // ========================================================================
    // Build Params (Canonical CONTRACT)
    // ========================================================================

    /**
     * Build API params following the Canonical LIST/QUERY Contract
     * This is the ONLY place where params structure is defined
     *
     * ✅ Canonical contract requires:
     * - page (int)
     * - per_page (int) - NOT "limit"
     * - search.global (string, optional)
     * - search.columns (object, optional) - NOT "filters"
     *
     * ❌ Forbidden:
     * - limit (use per_page)
     * - filters (use search.columns)
     * - sort (may not be supported by endpoint)
     */
    function buildParams(pageNumber = 1, perPageNumber = 25) {
        const params = {
            page: pageNumber,
            per_page: perPageNumber  // ✅ Canonical contract requires "per_page"
        };

        // ✅ Global search (above table) - searches in name OR code
        const globalSearchValue = globalSearchInput?.value?.trim();

        if (globalSearchValue) {
            params.search = {
                global: globalSearchValue  // ✅ Backend searches: name OR code
            };
        }

        // ✅ Column filters (in filters form) - exact/partial match per column
        const columns = {};

        // ID filter (exact match) - NEW!
        if (inputId?.value?.trim()) {
            columns.id = inputId.value.trim();  // Backend expects string for exact match
        }

        // Name filter (partial match - LIKE %value%)
        if (inputSearch?.value?.trim()) {
            columns.name = inputSearch.value.trim();
        }

        // Code filter (exact match)
        if (inputCode?.value?.trim()) {
            columns.code = inputCode.value.trim();
        }

        // Direction filter
        if (inputDirection?.value && inputDirection.value !== '') {
            columns.direction = inputDirection.value;
        }

        // Status filter
        if (inputStatus?.value && inputStatus.value !== '') {
            // Convert to string "1" or "0" as backend expects
            columns.is_active = inputStatus.value === 'active' ? '1' : '0';
        }

        // Add search.columns if we have any filters
        if (Object.keys(columns).length > 0) {
            if (!params.search) {
                params.search = {};
            }
            params.search.columns = columns;
        }

        // ❌ Do NOT send sort - endpoint does not support it
        // Backend uses server-controlled sorting: ORDER BY sort_order ASC, id ASC

        return params;
    }

    /**
     * Clean params - remove empty values
     * ✅ Canonical contract: never send null values
     */
    function cleanParams(params) {
        // Clean search.global
        if (params.search?.global && !params.search.global.trim()) {
            delete params.search.global;
        }

        // Clean search.columns
        if (params.search?.columns) {
            Object.keys(params.search.columns).forEach(key => {
                if (params.search.columns[key] === null ||
                    params.search.columns[key] === undefined ||
                    params.search.columns[key] === '') {
                    delete params.search.columns[key];
                }
            });

            if (Object.keys(params.search.columns).length === 0) {
                delete params.search.columns;
            }
        }

        // Remove search object if empty
        if (params.search && Object.keys(params.search).length === 0) {
            delete params.search;
        }
    }

    // ========================================================================
    // Pagination Info Callback
    // ========================================================================

    /**
     * Custom pagination info - Languages page business logic
     * Returns what should be displayed based on filtered/total
     * Pattern: Same as sessions.js
     */
    function getLanguagesPaginationInfo(pagination, params) {
        console.log("🎯 getLanguagesPaginationInfo called with:", pagination);

        const { page = 1, per_page = 25, total = 0, filtered = total } = pagination;

        // Check if we're filtering
        const hasFilter = params.search &&
            (params.search.global ||
                (params.search.columns && Object.keys(params.search.columns).length > 0));

        // ✅ Show filtered indicator if filter is applied (regardless of results)
        const isFiltered = hasFilter;

        console.log("🔍 Filter status - hasFilter:", hasFilter, "isFiltered:", isFiltered);
        console.log("🔍 Counts - total:", total, "filtered:", filtered);

        // Calculate based on filtered when applicable
        const displayCount = hasFilter ? filtered : total;
        const startItem = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
        const endItem = Math.min(page * per_page, displayCount);

        // Build info text
        let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;

        // ✅ Show "(filtered from X total)" if filter is applied
        if (isFiltered && filtered !== total) {
            infoText += ` <span class="text-gray-500 text-xs">(filtered from ${total} total)</span>`;
        }

        console.log("📤 Returning:", { total: displayCount, info: infoText });

        return {
            total: displayCount,  // Use filtered count for pagination calculations
            info: infoText
        };
    }

    // ========================================================================
    // Load Languages
    // ========================================================================

    async function loadLanguages(pageNumber = 1) {
        const params = buildParams(pageNumber, 25);
        await loadLanguagesWithParams(params);
    }

    async function loadLanguagesWithParams(params) {
        console.log("🚀 Languages Query Params:", JSON.stringify(params, null, 2));

        // Show loading indicator
        const container = document.querySelector("#table-container");
        if (container) {
            container.innerHTML = `
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    <span class="ml-3 text-gray-600">Loading languages...</span>
                </div>
            `;
        }

        // ✅ Use ApiHandler for proper error logging (including 500 HTML errors)
        const result = await ApiHandler.call('languages/query', params, 'Query Languages');

        if (!result.success) {
            console.error("❌ Query failed:", result.error);

            // Show error to user
            ApiHandler.showAlert('danger', result.error);

            // Show error in table container with raw response if available
            if (container) {
                let errorHtml = `
                    <div class="bg-white rounded-lg p-8 shadow-lg text-center">
                        <div class="text-red-600 text-6xl mb-4">⚠️</div>
                        <h3 class="text-lg font-medium text-gray-900">Error Loading Languages</h3>
                        <p class="text-sm text-gray-500 mt-2">${result.error}</p>
                `;

                // If we have raw HTML error (500 error), show it
                if (result.rawBody) {
                    errorHtml += `
                        <details class="mt-4 text-left">
                            <summary class="cursor-pointer text-blue-600 hover:text-blue-800">
                                📄 Show Raw Response (${result.rawBody.length} chars)
                            </summary>
                            <pre class="mt-2 p-4 bg-gray-100 rounded text-xs overflow-auto max-h-96 text-left">${result.rawBody.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</pre>
                        </details>
                    `;
                }

                errorHtml += `
                        <button onclick="location.reload()" class="mt-6 px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            🔄 Retry
                        </button>
                    </div>
                `;

                container.innerHTML = errorHtml;
            }

            return;
        }

        // ✅ Success - render table using data_table.js
        console.log("✅ Query successful, data received:", result.data);

        // Extract data and pagination
        const data = result.data || {};
        const languages = Array.isArray(data.data) ? data.data : [];
        const paginationInfo = data.pagination || {
            page: params.page || 1,
            per_page: params.per_page || 25,
            total: languages.length
        };

        console.log("📊 Languages data:", languages);
        console.log("📊 Pagination:", paginationInfo);

        // Use createTable for rendering only (data already fetched)
        if (typeof createTable === 'function') {
            try {
                // We need to trick createTable to use our pre-fetched data
                // Since we can't modify data_table.js, we'll call it with the data
                // but it will try to fetch again...

                // Actually, let's render manually using TableComponent directly
                if (typeof TableComponent === 'function') {
                    TableComponent(
                        languages,
                        headers,
                        rows,
                        paginationInfo,
                        "", // tableTitle
                        false, // withSelection
                        'id', // primaryKey
                        null, // onSelectionChange
                        {
                            name: nameRenderer,
                            code: codeRenderer,
                            direction: directionRenderer,
                            sort_order: sortRenderer,
                            is_active: statusRenderer,
                            fallback_language_id: fallbackRenderer,
                            actions: actionsRenderer
                        },
                        null, // selectableIds
                        getLanguagesPaginationInfo
                    );
                } else {
                    console.error("❌ TableComponent not found");
                }
            } catch (error) {
                console.error("❌ TABLE ERROR:", error);
                ApiHandler.showAlert('danger', 'Failed to render table: ' + error.message);
            }
        } else {
            console.error("❌ createTable function not found");
        }
    }

    // ========================================================================
    // API Actions
    // ========================================================================

    // NOTE: Toggle language status has been moved to languages-actions.js
    // with ApiHandler integration for better error handling and consistency

    // ========================================================================
    // Modal Functions
    // ========================================================================
    // Note: Modal functions (openCreateLanguageModal, openEditSettingsModal,
    // openSetFallbackModal) are defined in languages-modals.js
    // They are available via window.openCreateLanguageModal, etc.

    // ========================================================================
    // Helpers
    // ========================================================================

    /**
     * Show alert message
     * @deprecated Use ApiHandler.showAlert() instead for consistency
     */
    function showAlert(type, message) {
        ApiHandler.showAlert(type, message);
    }

    // Export for debugging
    window.languagesDebug = {
        loadLanguages,
        buildParams,
        headers,
        rows
    };
});