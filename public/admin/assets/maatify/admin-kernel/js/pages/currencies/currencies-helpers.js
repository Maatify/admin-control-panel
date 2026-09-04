/**
 * 🛠️ Currencies Management - Optimized Helpers Module
 * ===================================================
 * Shared utilities and helper functions for Currencies Management UI
 */

(function() {
    'use strict';

    console.log('🛠️ Currencies Helpers Module Loading...');

    // ========================================================================
    // Reusable Event Delegation Helper
    // ========================================================================

    /**
     * Setup button click handler with automatic closest() lookup
     * Eliminates duplicate event delegation code across modules
     *
     * @param {string} selector - Button CSS selector (e.g., '.my-btn')
     * @param {function} callback - Callback function (receives currencyId, buttonElement)
     * @param {object} options - Additional options
     * @returns {void}
     */
    function setupButtonHandler(selector, callback, options = {}) {
        const {
            preventDefault = true,
            stopPropagation = false,
            dataAttribute = 'data-currency-id',
            requireData = true
        } = options;

        document.addEventListener('click', async (e) => {
            const btn = e.target.closest(selector);
            if (!btn) return;

            if (preventDefault) e.preventDefault();
            if (stopPropagation) e.stopPropagation();

            const currencyId = btn.getAttribute(dataAttribute);

            if (requireData && !currencyId) {
                console.warn(`⚠️ Button ${selector} clicked but no ${dataAttribute} found`);
                return;
            }

            try {
                await callback(currencyId, btn, e);
            } catch (error) {
                console.error(`❌ Error in ${selector} handler:`, error);
            }
        });
    }

    // ========================================================================
    // Modal Management
    // ========================================================================

    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            console.error(`❌ Modal not found: ${modalId}`);
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    function closeAllModals() {
        const modals = document.querySelectorAll('[id$="-modal"]');
        modals.forEach(modal => {
            modal.classList.add('hidden');
        });
        document.body.style.overflow = '';
    }

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

    function clearFormInputs(formId) {
        const form = document.getElementById(formId);
        if (form) {
            form.reset();
        }
    }

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

    function isValidCurrencyCode(code) {
        return /^[A-Z]{3}$/i.test(code);
    }

    function isNonEmpty(value) {
        return value && value.trim().length > 0;
    }

    // ========================================================================
    // Export to Window
    // ========================================================================

    window.CurrenciesHelpers = {
        setupButtonHandler,
        openModal,
        closeModal,
        closeAllModals,
        setupModalCloseHandlers,
        clearFormInputs,
        setFormDisabled,
        isValidCurrencyCode,
        isNonEmpty
    };

    console.log('✅ CurrenciesHelpers loaded and exported to window');

})();
