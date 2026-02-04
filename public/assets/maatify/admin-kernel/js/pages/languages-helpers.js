/**
 * 🛠️ Languages Management - Shared Helpers
 * =========================================
 * Reusable utilities for Languages UI
 *
 * Functions:
 * - buildQueryParams()     - Build canonical query params
 * - cleanParams()          - Remove empty/null values
 * - validateLanguageForm() - Form validation
 * - openModal()            - Open modal by ID
 * - closeAllModals()       - Close all modals
 */

// Use IIFE to create and export immediately
if (typeof window !== 'undefined') {
    window.LanguagesHelpers = (function() {
        'use strict';

        console.log('🛠️ Languages Helpers Module Loading...');

        // ========================================================================
        // Query Params Builder
        // ========================================================================

        /**
         * Build canonical LIST/QUERY params
         *
         * @param {object} options - Query options
         * @param {number} options.page - Page number
         * @param {number} options.perPage - Items per page
         * @param {string} options.globalSearch - Global search term
         * @param {object} options.columnFilters - Column-specific filters
         * @returns {object} Canonical query params
         */
        function buildQueryParams(options = {}) {
            const {
                page = 1,
                perPage = 25,
                globalSearch = '',
                columnFilters = {}
            } = options;

            const params = {
                page: page,
                per_page: perPage  // ✅ Canonical contract
            };

            // Build search object if needed
            const search = {};

            // Global search
            if (globalSearch && globalSearch.trim()) {
                search.global = globalSearch.trim();
            }

            // Column filters
            const columns = {};
            Object.entries(columnFilters).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    columns[key] = value;
                }
            });

            if (Object.keys(columns).length > 0) {
                search.columns = columns;
            }

            // Add search to params if not empty
            if (Object.keys(search).length > 0) {
                params.search = search;
            }

            return params;
        }

        // ========================================================================
        // Params Cleaner
        // ========================================================================

        /**
         * Clean params - remove null/undefined/empty values
         * Modifies params in-place
         *
         * @param {object} params - Parameters to clean
         */
        function cleanParams(params) {
            // Clean search.global
            if (params.search?.global !== undefined) {
                if (!params.search.global || !params.search.global.trim()) {
                    delete params.search.global;
                }
            }

            // Clean search.columns
            if (params.search?.columns) {
                Object.keys(params.search.columns).forEach(key => {
                    const value = params.search.columns[key];
                    if (value === null || value === undefined || value === '') {
                        delete params.search.columns[key];
                    }
                });

                // Remove columns if empty
                if (Object.keys(params.search.columns).length === 0) {
                    delete params.search.columns;
                }
            }

            // Remove search if empty
            if (params.search && Object.keys(params.search).length === 0) {
                delete params.search;
            }
        }

        // ========================================================================
        // Form Validation
        // ========================================================================

        /**
         * Validate language form data
         *
         * @param {object} formData - Form data to validate
         * @param {string} mode - 'create' or 'update'
         * @returns {object} { valid: boolean, errors: {} }
         */
        function validateLanguageForm(formData, mode = 'create') {
            const errors = {};

            // Name validation
            if (mode === 'create' || formData.name !== undefined) {
                if (!formData.name || !formData.name.trim()) {
                    errors.name = 'Name is required';
                } else if (formData.name.trim().length > 255) {
                    errors.name = 'Name must be 255 characters or less';
                }
            }

            // Code validation
            if (mode === 'create' || formData.code !== undefined) {
                if (!formData.code || !formData.code.trim()) {
                    errors.code = 'Code is required';
                } else if (!/^[a-z]{2,5}$/.test(formData.code.trim())) {
                    errors.code = 'Code must be 2-5 lowercase letters';
                }
            }

            // Direction validation
            if (mode === 'create' || formData.direction !== undefined) {
                if (!formData.direction) {
                    errors.direction = 'Direction is required';
                } else if (!['ltr', 'rtl'].includes(formData.direction)) {
                    errors.direction = 'Direction must be "ltr" or "rtl"';
                }
            }

            // Icon validation (optional, but if provided must be valid)
            if (formData.icon !== undefined && formData.icon !== '') {
                if (formData.icon.length > 4) {
                    errors.icon = 'Icon must be 4 characters or less';
                }
            }

            // Fallback validation (optional, but if provided must be positive int)
            if (formData.fallback_language_id !== undefined && formData.fallback_language_id !== '') {
                const fallbackId = parseInt(formData.fallback_language_id);
                if (isNaN(fallbackId) || fallbackId < 1) {
                    errors.fallback_language_id = 'Fallback language ID must be a positive number';
                }
            }

            return {
                valid: Object.keys(errors).length === 0,
                errors: errors
            };
        }

        // ========================================================================
        // Modal Management
        // ========================================================================

        /**
         * Open modal by ID
         *
         * @param {string} modalId - Modal element ID
         */
        function openModal(modalId) {
            console.log(`🎭 Opening modal: ${modalId}`);

            // Close all other modals first
            closeAllModals();

            const modal = document.getElementById(modalId);
            if (modal) {
                console.log(`🎭 Modal element found: ${modalId}`);
                console.log(`🎭 Classes before:`, modal.className);

                modal.classList.remove('hidden');

                console.log(`🎭 Classes after:`, modal.className);
            } else {
                console.error(`❌ Modal not found: ${modalId}`);
            }
        }

        /**
         * Close all modals
         */
        function closeAllModals() {
            document.querySelectorAll('[id$="-modal"]').forEach(modal => {
                modal.classList.add('hidden');
            });

            // Clear any field errors
            document.querySelectorAll('.field-error').forEach(el => el.remove());
            document.querySelectorAll('.border-red-500').forEach(el => {
                el.classList.remove('border-red-500');
            });
        }

        /**
         * Setup close handlers for modals
         */
        function setupModalCloseHandlers() {
            // Close button clicks
            document.querySelectorAll('.close-modal').forEach(btn => {
                btn.addEventListener('click', closeAllModals);
            });

            // Background clicks
            document.querySelectorAll('[id$="-modal"]').forEach(modal => {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        closeAllModals();
                    }
                });
            });

            // Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeAllModals();
                }
            });
        }

        // ========================================================================
        // Form Data Builder
        // ========================================================================

        /**
         * Build form data object from form element
         * Automatically omits empty optional fields
         *
         * @param {HTMLFormElement} form - Form element
         * @param {Array<string>} requiredFields - List of required field names
         * @returns {object} Form data
         */
        function buildFormData(form, requiredFields = []) {
            const formData = {};
            const formElements = form.elements;

            for (let i = 0; i < formElements.length; i++) {
                const element = formElements[i];

                // Skip buttons and fieldsets
                if (!element.name || element.type === 'submit' || element.type === 'button') {
                    continue;
                }

                let value;

                // Handle different input types
                switch (element.type) {
                    case 'checkbox':
                        value = element.checked;
                        break;
                    case 'number':
                        value = element.value ? parseInt(element.value) : '';
                        break;
                    case 'select-one':
                        value = element.value;
                        break;
                    default:
                        value = element.value.trim();
                }

                // Add to formData if:
                // 1. It's a required field, OR
                // 2. It has a non-empty value
                const isRequired = requiredFields.includes(element.name);
                const hasValue = value !== '' && value !== null && value !== undefined;

                if (isRequired || hasValue) {
                    formData[element.name] = value;
                }
            }

            return formData;
        }

        // ========================================================================
        // Language Code Formatter
        // ========================================================================

        /**
         * Format language code to lowercase
         *
         * @param {string} code - Language code
         * @returns {string} Formatted code
         */
        function formatLanguageCode(code) {
            return code.trim().toLowerCase();
        }

        // ========================================================================
        // Debounce Utility
        // ========================================================================

        /**
         * Debounce function calls
         *
         * @param {Function} func - Function to debounce
         * @param {number} wait - Wait time in ms
         * @returns {Function} Debounced function
         */
        function debounce(func, wait = 300) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // ========================================================================
        // Public API
        // ========================================================================

        return {
            buildQueryParams,
            cleanParams,
            validateLanguageForm,
            openModal,
            closeAllModals,
            setupModalCloseHandlers,
            buildFormData,
            formatLanguageCode,
            debounce
        };

    })(); // End IIFE and assign to window.LanguagesHelpers

    console.log('✅ LanguagesHelpers loaded and exported to window');
}