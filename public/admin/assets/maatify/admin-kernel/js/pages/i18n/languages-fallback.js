/**
 * 🔗 Languages Management - Fallback Module
 * ==========================================
 * Handles fallback language management:
 * - Set Fallback (modal with dropdown)
 * - Clear Fallback (direct action with confirmation)
 *
 * Fallback Concept:
 * - Only ONE fallback language in the system at any time
 * - Setting a new fallback automatically unsets the previous one
 * - Fallback language cannot be deactivated
 * - Used when translation key is missing in user's language
 *
 * Features:
 * - ✅ Uses ApiHandler for all API calls
 * - ✅ Respects capabilities from server
 * - ✅ Modal for setting fallback with Select2 dropdown
 * - ✅ Search functionality for easy language selection
 * - ✅ Inline action for clearing fallback
 * - ✅ Validation: language cannot be its own fallback
 * - ✅ Auto-reload table on success
 *
 * Dependencies:
 * - ApiHandler (api_handler.js)
 * - Select2 (select2.js)
 * - LanguagesHelpers (languages-helpers.js)
 * - window.languagesCapabilities (injected by server)
 */

(function() {
    'use strict';

    console.log('🔗 Languages Fallback Module Loading...');

    // Check dependencies
    if (typeof ApiHandler === 'undefined') {
        console.error('❌ ApiHandler not found! Make sure api_handler.js is loaded first.');
        return;
    }

    if (typeof Select2 === 'undefined') {
        console.error('❌ Select2 not found! Make sure select2.js is loaded first.');
        return;
    }

    if (typeof LanguagesHelpers === 'undefined') {
        console.error('❌ LanguagesHelpers not found! Make sure languages-helpers.js is loaded first.');
        return;
    }

    console.log('✅ Dependencies loaded: ApiHandler, Select2, LanguagesHelpers');

    // ========================================================================
    // Select2 Instance Tracking
    // ========================================================================

    let fallbackSelect2Instance = null;

    // ========================================================================
    // Set Fallback Modal HTML
    // ========================================================================

    /**
     * Set Fallback Modal Template
     * Allows selecting a fallback language from dropdown
     */
    const setFallbackModalHTML = `
        <div id="set-fallback-modal" class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 z-50 flex items-center justify-center hidden">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 border border-transparent dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">🔗 Set Fallback Language</h3>
                    <button class="close-modal text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="set-fallback-form" class="px-6 py-4 space-y-4">
                    <input type="hidden" id="fallback-language-id" name="language_id" />

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Language
                        </label>
                        <div id="fallback-current-language" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-200 font-medium">
                        </div>
                    </div>

                    <div id="fallback-current-status-container">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Current Fallback
                        </label>
                        <div id="fallback-current-status" class="px-4 py-2 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-600 dark:text-gray-400">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Set Fallback To <span class="text-red-500">*</span>
                        </label>
                        
                        <div id="fallback-target-language" class="w-full relative">
                            <div class="js-select-box relative flex items-center justify-between px-4 py-2 border border-gray-300 dark:border-gray-700 dark:text-gray-200 rounded-lg cursor-pointer hover:border-gray-400 dark:hover:border-gray-500 transition-colors bg-white dark:bg-gray-800">
                                <input type="text" 
                                       class="js-select-input pointer-events-none bg-transparent flex-1 outline-none text-gray-700 dark:text-gray-200" 
                                       placeholder="-- Select Fallback Language --" 
                                       readonly>
                                <span class="js-arrow ml-2 transition-transform duration-200 text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </span>
                            </div>
                            
                            <div class="js-dropdown hidden absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg shadow-lg max-h-64 dark:text-gray-200">
                                <div class="p-2 border-b border-gray-200 dark:border-gray-700">
                                    <input type="text" 
                                           class="js-search-input w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm dark:text-gray-100" 
                                           placeholder="🔍 Search languages...">
                                </div>
                                <ul class="js-select-list max-h-48 overflow-y-auto dark:text-gray-200"></ul>
                            </div>
                        </div>
                        
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            💡 This language will be used when translations are missing
                        </p>
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 dark:text-gray-200">
                        <p class="text-sm text-blue-800 dark:text-blue-300">
                            <strong>Note:</strong> Only ONE language can be the system fallback. Setting a new fallback automatically unsets the previous one.
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button
                            type="button"
                            class="close-modal px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm"
                        >
                            Set as Fallback
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    // ========================================================================
    // Initialize Module
    // ========================================================================

    function initFallbackModule() {
        console.log('🎬 Initializing Languages Fallback Module...');

        // Inject modal HTML
        document.body.insertAdjacentHTML('beforeend', setFallbackModalHTML);
        console.log('✅ Set Fallback modal injected');

        // Setup modal close handlers (uses LanguagesHelpers)
        LanguagesHelpers.setupModalCloseHandlers();

        // Setup form handlers
        setupSetFallbackForm();

        // Setup event delegation for fallback actions
        setupFallbackActionHandlers();

        console.log('✅ Languages Fallback Module initialized');
    }

    // ========================================================================
    // Event Delegation
    // ========================================================================

    /**
     * Setup event delegation for fallback actions
     * Handles both set and clear fallback buttons
     */
    function setupFallbackActionHandlers() {
        document.addEventListener('click', async (e) => {
            // Set Fallback Button - use closest() to handle nested elements
            const setBtn = e.target.closest('.set-fallback-btn');
            if (setBtn) {
                e.preventDefault();
                const languageId = setBtn.getAttribute('data-language-id');
                openSetFallbackModal(languageId);
                return;
            }

            // Clear Fallback Button - use closest() to handle nested elements
            const clearBtn = e.target.closest('.clear-fallback-btn');
            if (clearBtn) {
                e.preventDefault();
                const languageId = clearBtn.getAttribute('data-language-id');
                await clearFallback(languageId);
                return;
            }
        });

        console.log('✅ Fallback action handlers setup complete');
    }

    // ========================================================================
    // Set Fallback Modal
    // ========================================================================

    /**
     * Open Set Fallback Modal
     * Fetches language details and populates dropdown with available languages
     */
    async function openSetFallbackModal(languageId) {
        console.log('🔗 Opening Set Fallback Modal for language:', languageId);

        // Build query to fetch current language details
        const queryPayload = {
            page: 1,
            per_page: 1,
            search: {
                columns: {
                    id: languageId
                }
            }
        };

        // Use ApiHandler to fetch language
        const result = await ApiHandler.call('languages/query', queryPayload, 'Query Language for Fallback');

        if (!result.success) {
            ApiHandler.showAlert('danger', result.error || 'Failed to load language details');
            return;
        }

        // Check if language found
        if (!result.data || !result.data.data || result.data.data.length === 0) {
            ApiHandler.showAlert('danger', 'Language not found');
            return;
        }

        const language = result.data.data[0];

        // Populate current language info
        document.getElementById('fallback-language-id').value = language.id;
        document.getElementById('fallback-current-language').textContent = language.name;

        // Show current fallback status
        const statusContainer = document.getElementById('fallback-current-status-container');
        const statusDiv = document.getElementById('fallback-current-status');

        if (language.fallback_language_id) {
            statusDiv.innerHTML = `Currently falls back to Language ID: <strong>${language.fallback_language_id}</strong>`;
            statusContainer.style.display = 'block';
        } else {
            statusDiv.textContent = 'No fallback currently set';
            statusContainer.style.display = 'block';
        }

        // Load available languages for dropdown
        await loadAvailableLanguagesForFallback(language.id);

        LanguagesHelpers.openModal('set-fallback-modal');
    }

    /**
     * Load all available languages for fallback dropdown
     * Excludes the current language (can't be fallback to itself)
     * Now uses Select2 for better UX with search
     */
    async function loadAvailableLanguagesForFallback(currentLanguageId) {
        console.log('📋 Loading available languages for fallback dropdown...');

        // Query all languages
        const queryPayload = {
            page: 1,
            per_page: 100  // Get all languages
        };

        const result = await ApiHandler.call('languages/query', queryPayload, 'Query All Languages');

        if (!result.success) {
            ApiHandler.showAlert('danger', 'Failed to load languages list');
            return;
        }

        const languages = result.data?.data || [];

        // Prepare Select2 data (exclude current language)
        const select2Data = languages
            .filter(lang => lang.id !== parseInt(currentLanguageId))
            .map(lang => ({
                value: lang.id,
                label: `${lang.icon || '🌍'} ${lang.name} (${lang.code})`
            }));

        console.log(`📊 Loaded ${select2Data.length} languages for Select2`);

        // Destroy previous instance if exists
        if (fallbackSelect2Instance) {
            fallbackSelect2Instance.destroy();
            fallbackSelect2Instance = null;
        }

        // Initialize Select2
        fallbackSelect2Instance = Select2('#fallback-target-language', select2Data, {
            defaultValue: null
        });

        console.log('✅ Select2 initialized for fallback selection');
    }

    /**
     * Setup Set Fallback Form Handler
     * ✅ Uses ApiHandler
     */
    function setupSetFallbackForm() {
        const form = document.getElementById('set-fallback-form');
        if (!form) {
            console.warn('⚠️ Set Fallback form not found');
            return;
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const languageId = parseInt(document.getElementById('fallback-language-id').value);

            // Get value from Select2 instance
            const fallbackLanguageId = fallbackSelect2Instance ? parseInt(fallbackSelect2Instance.getValue()) : null;

            // Validate
            if (!languageId || !fallbackLanguageId) {
                ApiHandler.showAlert('warning', 'Please select a fallback language');
                return;
            }

            // Additional validation: can't be same language
            if (languageId === fallbackLanguageId) {
                ApiHandler.showAlert('warning', 'A language cannot be its own fallback');
                return;
            }

            // Build payload
            const payload = {
                language_id: languageId,
                fallback_language_id: fallbackLanguageId
            };

            console.log('📤 Setting fallback:', payload);

            // Use ApiHandler
            const result = await ApiHandler.call('languages/set-fallback', payload, 'Set Fallback');

            if (!result.success) {
                // Error already shown by ApiHandler
                return;
            }

            // Success!
            ApiHandler.showAlert('success', 'Fallback language set successfully');
            LanguagesHelpers.closeAllModals();

            // Reload table
            reloadLanguagesTable();
        });

        console.log('✅ Set Fallback form handler setup complete');
    }

    // ========================================================================
    // Clear Fallback Action
    // ========================================================================

    /**
     * Clear fallback language
     * Direct action with confirmation
     * ✅ Uses ApiHandler
     */
    async function clearFallback(languageId) {
        console.log('🗑️ Clearing fallback for language:', languageId);

        // Confirmation
        const confirmed = confirm('Are you sure you want to clear the fallback for this language?\n\nAfter this, missing translations may return keys instead of text.');

        if (!confirmed) {
            console.log('❌ Clear fallback cancelled by user');
            return;
        }

        // Build payload
        const payload = {
            language_id: parseInt(languageId)
        };

        console.log('📤 Clearing fallback:', payload);

        // Use ApiHandler
        const result = await ApiHandler.call('languages/clear-fallback', payload, 'Clear Fallback');

        if (!result.success) {
            // Error already shown by ApiHandler
            return;
        }

        // Success!
        ApiHandler.showAlert('success', 'Fallback cleared successfully');

        // Reload table
        reloadLanguagesTable();
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    /**
     * Reload languages table
     * Calls the global loadLanguages function if available
     */
    function reloadLanguagesTable() {
        if (window.languagesDebug && typeof window.languagesDebug.loadLanguages === 'function') {
            console.log('🔄 Reloading languages table...');
            window.languagesDebug.loadLanguages();
        } else {
            console.warn('⚠️ loadLanguages function not found - table will not auto-reload');
            ApiHandler.showAlert('info', 'Please refresh the page to see changes');
        }
    }

    // ========================================================================
    // Export Functions to Window
    // ========================================================================

    // Export functions for use by other modules
    window.LanguagesFallback = {
        openSetFallbackModal,
        clearFallback
    };

    // Export as openSetFallbackModal for backward compatibility
    window.openSetFallbackModal = openSetFallbackModal;

    console.log('✅ LanguagesFallback exported to window');

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFallbackModule);
    } else {
        initFallbackModule();
    }

})();