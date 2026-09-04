/**
 * 🛠️ Languages Management - Optimized Helpers Module
 * ===================================================
 * Shared utilities and helper functions for Languages Management UI
 *
 * ✅ OPTIMIZATION: Added reusable event delegation helper
 * to reduce code duplication across all modules
 */

(function() {
    'use strict';

    console.log('🛠️ Languages Helpers Module Loading...');

    // ========================================================================
    // NEW: Reusable Event Delegation Helper
    // ========================================================================

    /**
     * Setup button click handler with automatic closest() lookup
     * Eliminates duplicate event delegation code across modules
     *
     * @param {string} selector - Button CSS selector (e.g., '.my-btn')
     * @param {function} callback - Callback function (receives languageId, buttonElement)
     * @param {object} options - Additional options
     * @returns {void}
     *
     * Usage:
     *   setupButtonHandler('.toggle-status-btn', async (id, btn) => {
     *       await toggleStatus(id);
     *   });
     */
    function setupButtonHandler(selector, callback, options = {}) {
        const {
            preventDefault = true,
            stopPropagation = false,
            dataAttribute = 'data-language-id',
            requireData = true
        } = options;

        document.addEventListener('click', async (e) => {
            const btn = e.target.closest(selector);
            if (!btn) return;

            if (preventDefault) e.preventDefault();
            if (stopPropagation) e.stopPropagation();

            const languageId = btn.getAttribute(dataAttribute);

            if (requireData && !languageId) {
                console.warn(`⚠️ Button ${selector} clicked but no ${dataAttribute} found`);
                return;
            }

            try {
                await callback(languageId, btn, e);
            } catch (error) {
                console.error(`❌ Error in ${selector} handler:`, error);
            }
        });
    }

    // ========================================================================
    // Modal Management
    // ========================================================================

    /**
     * Open modal by ID
     */
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            console.error(`❌ Modal not found: ${modalId}`);
        }
    }

    /**
     * Close modal by ID
     */
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    /**
     * Close all modals
     */
    function closeAllModals() {
        const modals = document.querySelectorAll('[id$="-modal"]');
        modals.forEach(modal => {
            modal.classList.add('hidden');
        });
        document.body.style.overflow = '';
    }

    /**
     * Setup close button handlers for all modals
     */
    function setupModalCloseHandlers() {
        // Close on backdrop click
        document.addEventListener('click', (e) => {
            if (e.target.id && e.target.id.endsWith('-modal')) {
                closeModal(e.target.id);
            }
        });

        // Close on X button click
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('close-modal')) {
                const modal = e.target.closest('[id$="-modal"]');
                if (modal) {
                    closeModal(modal.id);
                }
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeAllModals();
            }
        });
    }

    // ========================================================================
    // Input Utilities
    // ========================================================================

    /**
     * Clear form inputs
     */
    function clearFormInputs(formId) {
        const form = document.getElementById(formId);
        if (form) {
            form.reset();
        }
    }

    /**
     * Disable/enable form
     */
    function setFormDisabled(formId, disabled) {
        const form = document.getElementById(formId);
        if (form) {
            const inputs = form.querySelectorAll('input, select, textarea, button');
            inputs.forEach(input => {
                input.disabled = disabled;
            });
        }
    }

    // ========================================================================
    // Validation Utilities
    // ========================================================================

    /**
     * Validate language code format
     */
    function isValidLanguageCode(code) {
        return /^[a-z]{2,5}$/.test(code);
    }

    /**
     * Validate non-empty string
     */
    function isNonEmpty(value) {
        return value && value.trim().length > 0;
    }

    // ========================================================================
    // DOM Utilities
    // ========================================================================

    /**
     * Show element
     */
    function showElement(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.classList.remove('hidden');
        }
    }

    /**
     * Hide element
     */
    function hideElement(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.classList.add('hidden');
        }
    }

    /**
     * Toggle element visibility
     */
    function toggleElement(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.classList.toggle('hidden');
        }
    }

    // ========================================================================
    // Export to Window
    // ========================================================================

    window.LanguagesHelpers = {
        // NEW: Event delegation helper
        setupButtonHandler,

        // Modal functions
        openModal,
        closeModal,
        closeAllModals,
        setupModalCloseHandlers,

        // Form utilities
        clearFormInputs,
        setFormDisabled,

        // Validation
        isValidLanguageCode,
        isNonEmpty,

        // DOM utilities
        showElement,
        hideElement,
        toggleElement
    };

    console.log('✅ LanguagesHelpers loaded and exported to window');
    console.log('   ↳ NEW: setupButtonHandler() added for code reusability');

})();