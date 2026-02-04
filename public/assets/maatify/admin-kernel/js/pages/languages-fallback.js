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
 * - ✅ Modal for setting fallback
 * - ✅ Inline action for clearing fallback
 * - ✅ Validation: language cannot be its own fallback
 * - ✅ Auto-reload table on success
 *
 * Dependencies:
 * - ApiHandler (api_handler.js)
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

    if (typeof LanguagesHelpers === 'undefined') {
        console.error('❌ LanguagesHelpers not found! Make sure languages-helpers.js is loaded first.');
        return;
    }

    console.log('✅ Dependencies loaded: ApiHandler, LanguagesHelpers');

    // ========================================================================
    // Set Fallback Modal HTML
    // ========================================================================

    /**
     * Set Fallback Modal Template
     * Allows selecting a fallback language from dropdown
     */
    const setFallbackModalHTML = `
        <div id="set-fallback-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">🔗 Set Fallback Language</h3>
                    <button class="close-modal text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="set-fallback-form" class="px-6 py-4 space-y-4">
                    <input type="hidden" id="fallback-language-id" name="language_id" />

                    <!-- Current Language (Display Only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Language
                        </label>
                        <div id="fallback-current-language" class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-700 font-medium">
                            <!-- Populated dynamically -->
                        </div>
                    </div>

                    <!-- Current Fallback Status (Display Only) -->
                    <div id="fallback-current-status-container">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Current Fallback
                        </label>
                        <div id="fallback-current-status" class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-600">
                            <!-- Populated dynamically -->
                        </div>
                    </div>

                    <!-- Select New Fallback -->
                    <div>
                        <label for="fallback-target-language" class="block text-sm font-medium text-gray-700 mb-2">
                            Set Fallback To <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="fallback-target-language"
                            name="fallback_language_id"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">-- Select Fallback Language --</option>
                            <!-- Options populated dynamically -->
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            💡 This language will be used when translations are missing
                        </p>
                    </div>

                    <!-- Info Box -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <p class="text-sm text-blue-800">
                            <strong>Note:</strong> Only ONE language can be the system fallback. Setting a new fallback automatically unsets the previous one.
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                        <button
                            type="button"
                            class="close-modal px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
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
        const dropdown = document.getElementById('fallback-target-language');

        // Clear existing options (except first "select" option)
        dropdown.innerHTML = '<option value="">-- Select Fallback Language --</option>';

        // Add languages (exclude current language)
        languages.forEach(lang => {
            if (lang.id !== parseInt(currentLanguageId)) {
                const option = document.createElement('option');
                option.value = lang.id;
                option.textContent = `${lang.name} (${lang.code})`;
                dropdown.appendChild(option);
            }
        });

        console.log(`✅ Loaded ${languages.length - 1} available languages for fallback`);
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
            const fallbackLanguageId = parseInt(document.getElementById('fallback-target-language').value);

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