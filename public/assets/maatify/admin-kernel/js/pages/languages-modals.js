/**
 * 🌍 Languages Management - Modals Module
 * ========================================
 * Handles all modal dialogs for language management:
 * - Create Language
 * - Update Settings (direction + icon only)
 *
 * Uses ApiHandler for all API calls
 * Respects capabilities injected from server
 */

(function() {
    'use strict';

    console.log('🎨 Languages Modals Module Loading...');

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
    // Modal HTML Templates
    // ========================================================================

    /**
     * Create Language Modal Template
     */
    const createLanguageModalHTML = `
        <div id="create-language-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">🆕 Create New Language</h3>
                    <button class="close-modal text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="create-language-form" class="px-6 py-4 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Language Name -->
                        <div class="md:col-span-2">
                            <label for="create-name" class="block text-sm font-medium text-gray-700 mb-2">
                                Language Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="create-name"
                                name="name"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="e.g., English, العربية, Français"
                            />
                        </div>

                        <!-- Language Code -->
                        <div>
                            <label for="create-code" class="block text-sm font-medium text-gray-700 mb-2">
                                Language Code <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="create-code"
                                name="code"
                                required
                                maxlength="5"
                                pattern="[a-z]{2,5}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 uppercase"
                                placeholder="e.g., en, ar, fr"
                            />
                            <p class="mt-1 text-xs text-gray-500">ISO 639-1 code (2-5 lowercase letters)</p>
                        </div>

                        <!-- Direction -->
                        <div>
                            <label for="create-direction" class="block text-sm font-medium text-gray-700 mb-2">
                                Text Direction <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="create-direction"
                                name="direction"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="ltr">← LTR (Left to Right)</option>
                                <option value="rtl">→ RTL (Right to Left)</option>
                            </select>
                        </div>

                        <!-- Icon (Emoji) -->
                        <div>
                            <label for="create-icon" class="block text-sm font-medium text-gray-700 mb-2">
                                Icon (Emoji)
                            </label>
                            <input
                                type="text"
                                id="create-icon"
                                name="icon"
                                maxlength="4"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-2xl"
                                placeholder="🇬🇧"
                            />
                        </div>

                        <!-- Active Status -->
                        <div class="flex items-center md:col-span-2">
                            <input
                                type="checkbox"
                                id="create-active"
                                name="is_active"
                                checked
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            />
                            <label for="create-active" class="ml-2 text-sm text-gray-700">
                                Set as active immediately
                            </label>
                        </div>

                        <!-- Fallback Language -->
                        <div class="md:col-span-2">
                            <label for="create-fallback" class="block text-sm font-medium text-gray-700 mb-2">
                                Fallback Language (Optional)
                            </label>
                            <select
                                id="create-fallback"
                                name="fallback_language_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">No fallback</option>
                                <!-- Will be populated dynamically -->
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Used when translation is missing</p>
                        </div>
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
                            class="px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Create Language
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    /**
     * Edit Settings Modal Template
     * ✅ Per API Contract: Only direction + icon can be edited here
     * Name, code, sort_order have dedicated endpoints in Phase 3
     */
    const editSettingsModalHTML = `
        <div id="edit-settings-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg shadow-xl max-w-xl w-full mx-4">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">⚙️ Edit Language Settings</h3>
                    <button class="close-modal text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="edit-settings-form" class="px-6 py-4 space-y-4">
                    <input type="hidden" id="edit-language-id" name="language_id" />

                    <!-- Language Name (Display Only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Language
                        </label>
                        <div id="edit-language-name" class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-700 font-medium">
                            <!-- Populated dynamically -->
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Name, Code, and Sort Order can be edited separately via inline actions</p>
                    </div>

                    <!-- Direction -->
                    <div>
                        <label for="edit-direction" class="block text-sm font-medium text-gray-700 mb-2">
                            Text Direction
                        </label>
                        <select
                            id="edit-direction"
                            name="direction"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="ltr">← LTR (Left to Right)</option>
                            <option value="rtl">→ RTL (Right to Left)</option>
                        </select>
                    </div>

                    <!-- Icon (Emoji) -->
                    <div>
                        <label for="edit-icon" class="block text-sm font-medium text-gray-700 mb-2">
                            Icon (Emoji)
                        </label>
                        <input
                            type="text"
                            id="edit-icon"
                            name="icon"
                            maxlength="4"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-2xl"
                            placeholder="🇬🇧"
                        />
                        <p class="mt-1 text-xs text-gray-500">Leave empty to clear icon, or enter emoji to set/update</p>
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
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    // ========================================================================
    // NOTE: Set Fallback Modal removed - will be recreated in Phase 4
    // with proper ApiHandler integration and correct API contract usage
    // ========================================================================


    // ========================================================================
    // Initialize Modals
    // ========================================================================

    function initModals() {
        console.log('🎬 Initializing modals...');

        // Inject modal HTML into body
        document.body.insertAdjacentHTML('beforeend', createLanguageModalHTML);
        console.log('✅ Create Language modal injected');

        document.body.insertAdjacentHTML('beforeend', editSettingsModalHTML);
        console.log('✅ Edit Settings modal injected');

        // NOTE: Set Fallback modal removed - will be recreated in Phase 4

        // Verify modals exist
        console.log('🔍 Verifying modals in DOM:');
        console.log('  - create-language-modal:', !!document.getElementById('create-language-modal'));
        console.log('  - edit-settings-modal:', !!document.getElementById('edit-settings-modal'));

        // Setup close handlers - use LanguagesHelpers
        LanguagesHelpers.setupModalCloseHandlers();

        // Setup form handlers
        setupCreateLanguageForm();
        setupEditSettingsForm();
        // NOTE: setupSetFallbackForm removed - will be added in Phase 4

        // Setup event delegation for edit-settings button
        setupEditSettingsEventHandler();

        console.log('✅ Modals initialized');
    }

    /**
     * Setup event delegation for edit-settings button
     */
    function setupEditSettingsEventHandler() {
        document.addEventListener('click', (e) => {
            // Edit settings button - use closest() to handle nested elements
            const settingsBtn = e.target.closest('.edit-settings-btn');
            if (settingsBtn) {
                e.preventDefault();
                const languageId = settingsBtn.getAttribute('data-language-id');
                openEditSettingsModal(languageId);
            }
        });
        console.log('✅ Edit Settings event handler setup');
    }

    // ========================================================================
    // Modal Controls (deprecated - use LanguagesHelpers instead)
    // ========================================================================

    /**
     * @deprecated Use LanguagesHelpers.closeAllModals() instead
     */
    function closeAllModals() {
        LanguagesHelpers.closeAllModals();
    }

    /**
     * @deprecated Use LanguagesHelpers.openModal() instead
     */
    function openModal(modalId) {
        LanguagesHelpers.openModal(modalId);
    }

    // ========================================================================
    // Create Language Modal
    // ========================================================================

    async function openCreateLanguageModal() {
        console.log('🆕 Opening Create Language Modal');
        console.log('🆕 Modal element exists:', !!document.getElementById('create-language-modal'));

        // Load available languages for fallback dropdown
        await loadLanguagesForFallback('create-fallback');

        openModal('create-language-modal');
    }

    function setupCreateLanguageForm() {
        const form = document.getElementById('create-language-form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Build form data per contract
            const formData = {
                name: document.getElementById('create-name').value.trim(),
                code: document.getElementById('create-code').value.trim().toLowerCase(),
                direction: document.getElementById('create-direction').value,
                is_active: document.getElementById('create-active').checked
            };

            // ✅ Validate required fields
            if (!formData.name) {
                ApiHandler.showAlert('warning', 'Language name is required');
                return;
            }

            if (!formData.code) {
                ApiHandler.showAlert('warning', 'Language code is required');
                return;
            }

            if (!formData.direction) {
                ApiHandler.showAlert('warning', 'Direction is required');
                return;
            }

            // ✅ Only add icon if provided
            const iconValue = document.getElementById('create-icon').value.trim();
            if (iconValue) {
                formData.icon = iconValue;
            }

            // ✅ Only add fallback_language_id if selected (not empty)
            const fallbackValue = document.getElementById('create-fallback').value;
            if (fallbackValue && fallbackValue !== '') {
                formData.fallback_language_id = parseInt(fallbackValue);
            }

            // ❌ Do NOT send sort_order - backend assigns automatically
            // Per contract: "sort_order is NOT accepted here"

            console.log('📤 Creating language:', formData);

            // ✅ Use ApiHandler
            const result = await ApiHandler.call('languages/create', formData, 'Create Language');

            if (!result.success) {
                ApiHandler.showAlert('danger', result.error);

                // Show field errors if present
                if (result.data && result.data.error && result.data.error.fields) {
                    ApiHandler.showFieldErrors(result.data.error.fields, 'create-language-form');
                }
                return;
            }

            // ✅ Success (empty response or JSON)
            ApiHandler.showAlert('success', 'Language created successfully');
            LanguagesHelpers.closeAllModals();
            form.reset();

            // Reload languages table
            if (typeof loadLanguages === 'function') {
                loadLanguages();
            } else if (window.languagesDebug && window.languagesDebug.loadLanguages) {
                window.languagesDebug.loadLanguages();
            }
        });
    }

    // ========================================================================
    // Edit Settings Modal
    // ========================================================================

    /**
     * Open Edit Settings Modal
     * ✅ Uses ApiHandler for query
     * ✅ Populates direction + icon only (per contract)
     */
    async function openEditSettingsModal(languageId) {
        console.log('⚙️ Opening Edit Settings Modal for language:', languageId);
        console.log('⚙️ Modal element exists:', !!document.getElementById('edit-settings-modal'));

        // Build query to fetch language details
        const queryPayload = {
            page: 1,
            per_page: 1,
            search: {
                columns: {
                    id: languageId  // Keep as string - backend will handle filtering
                }
            }
        };

        // Use ApiHandler to fetch language
        const result = await ApiHandler.call('languages/query', queryPayload, 'Query Language for Edit');

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

        // Populate form
        document.getElementById('edit-language-id').value = language.id;
        document.getElementById('edit-language-name').textContent = language.name;
        document.getElementById('edit-direction').value = language.direction || 'ltr';
        document.getElementById('edit-icon').value = language.icon || '';

        LanguagesHelpers.openModal('edit-settings-modal');
    }

    /**
     * Setup Edit Settings Form Handler
     * ✅ Uses ApiHandler
     * ✅ Sends only direction + icon (per contract)
     * ✅ Icon can be empty string to clear
     */
    function setupEditSettingsForm() {
        const form = document.getElementById('edit-settings-form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const languageId = parseInt(document.getElementById('edit-language-id').value);
            const direction = document.getElementById('edit-direction').value;
            const icon = document.getElementById('edit-icon').value;

            // Build payload per API contract
            const payload = {
                language_id: languageId
            };

            // Only add direction if changed (optional field per contract)
            if (direction) {
                payload.direction = direction;
            }

            // Add icon field:
            // - If user entered value: send it
            // - If empty: send empty string to clear icon
            // - Per contract: icon is optional, but we always send it here
            payload.icon = icon;  // Can be empty string to clear

            console.log('📤 Updating language settings:', payload);

            // Use ApiHandler
            const result = await ApiHandler.call('languages/update-settings', payload, 'Update Settings');

            if (!result.success) {
                // ApiHandler already showed error via showAlert
                // But we can add field-specific errors if needed
                if (result.data && result.data.errors) {
                    console.error('❌ Field errors:', result.data.errors);
                }
                return;
            }

            // Success!
            ApiHandler.showAlert('success', 'Settings updated successfully');
            LanguagesHelpers.closeAllModals();

            // Reload languages table
            if (window.languagesDebug && window.languagesDebug.loadLanguages) {
                window.languagesDebug.loadLanguages();
            }
        });
    }


    // ========================================================================
    // NOTE: Set Fallback Modal functions removed
    // Will be recreated in Phase 4 with:
    // - ApiHandler integration
    // - Proper API contract (set-fallback vs clear-fallback endpoints)
    // - Better UX and error handling
    // ========================================================================


    // ========================================================================
    // Helpers
    // ========================================================================

    /**
     * Load all languages for fallback dropdown
     * ✅ Uses ApiHandler
     */
    async function loadLanguagesForFallback(selectId, excludeId = null, currentFallbackId = null) {
        // Build query
        const queryPayload = {
            page: 1,
            per_page: 100  // Load all for dropdown
        };

        // Use ApiHandler
        const result = await ApiHandler.call('languages/query', queryPayload, 'Load Languages for Fallback');

        if (!result.success) {
            ApiHandler.showAlert('warning', 'Failed to load available languages for fallback');
            return;
        }

        if (!result.data || !result.data.data) {
            console.warn('⚠️ No languages data in response');
            return;
        }

        const select = document.getElementById(selectId);
        if (!select) {
            console.warn(`⚠️ Select element not found: ${selectId}`);
            return;
        }

        // Clear existing options (except first "No fallback" option)
        const firstOption = select.querySelector('option[value=""]');
        select.innerHTML = '';
        if (firstOption) select.appendChild(firstOption);

        // Add language options
        result.data.data.forEach(lang => {
            // Exclude current language from its own fallback list
            if (excludeId && lang.id === parseInt(excludeId)) return;

            const option = document.createElement('option');
            option.value = lang.id;
            option.textContent = `${lang.icon || ''} ${lang.name} (${lang.code})`.trim();

            // Pre-select current fallback
            if (currentFallbackId && lang.id === currentFallbackId) {
                option.selected = true;
            }

            select.appendChild(option);
        });

        console.log(`✅ Loaded ${result.data.data.length} languages for fallback dropdown`);
    }

    /**
     * @deprecated Use ApiHandler.showAlert() instead
     */
    function showAlert(type, message) {
        ApiHandler.showAlert(type, message);
    }

    /**
     * @deprecated Use ApiHandler.parseResponse() instead
     */
    async function parseApiResponse(response, operation) {
        console.warn('⚠️ parseApiResponse is deprecated - use ApiHandler.call() directly');
        // This function is no longer used since we moved to ApiHandler
        return null;
    }

    // ========================================================================
    // Export Functions to Window
    // ========================================================================

    window.openCreateLanguageModal = openCreateLanguageModal;
    window.openEditSettingsModal = openEditSettingsModal;
    // NOTE: openSetFallbackModal removed - will be added in Phase 4

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModals);
    } else {
        initModals();
    }

})();