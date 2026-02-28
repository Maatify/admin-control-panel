/**
 * 🛡️ Error Normalizer - Compatibility Layer
 * =========================================
 * Provides a unified interface for handling API errors, regardless of format.
 * Supports:
 * - Unified Standard: { success: false, error: { ... } }
 * - Legacy Simple: { error: "..." }
 * - Legacy Message: { message: "..." }
 * - Legacy Step-Up: { code: "STEP_UP_REQUIRED", ... }
 * - Callback format: { data: { response: <int> } }
 *
 * Usage:
 * const err = ErrorNormalizer.normalize(data);
 * if (ErrorNormalizer.isStepUpRequired(data)) { ... }
 */

(function() {
    'use strict';

    console.log('🛡️ Error Normalizer - Initializing');

    const ErrorNormalizer = {

        /**
         * Normalize any error response into a consistent object
         * @param {object|null} data - The JSON body from the response
         * @returns {object} Normalized error object
         */
        normalize(data) {
            // Default shape
            const normalized = {
                message: 'An unknown error occurred.',
                code: 'UNKNOWN_ERROR',
                category: 'SYSTEM',
                meta: {},
                retryable: false,
                is_step_up: false,
                raw: data
            };

            if (!data || typeof data !== 'object') {
                return normalized;
            }

            // 1. Unified Standard: { success: false, error: { ... } }
            if (data.error && typeof data.error === 'object' && data.error.code) {
                const e = data.error;
                normalized.message = e.message || normalized.message;
                normalized.code = e.code || normalized.code;
                normalized.category = e.category || normalized.category;
                normalized.meta = e.meta || {};
                normalized.retryable = !!e.retryable;

                if (e.code === 'STEP_UP_REQUIRED') {
                    normalized.is_step_up = true;
                    // Ensure scope is available at top level for convenience if in meta
                    if (e.meta && e.meta.scope) {
                        normalized.scope = e.meta.scope;
                    }
                }
                return normalized;
            }

            // 2. Legacy Step-Up: { code: "STEP_UP_REQUIRED", scope: "..." }
            if (data.code === 'STEP_UP_REQUIRED') {
                normalized.message = 'Additional authentication required.';
                normalized.code = 'STEP_UP_REQUIRED';
                normalized.category = 'AUTHENTICATION';
                normalized.is_step_up = true;
                normalized.scope = data.scope;
                return normalized;
            }

            // 3. Legacy Simple: { error: "..." }
            if (typeof data.error === 'string') {
                normalized.message = data.error;
                return normalized;
            }

            // 4. Legacy Message: { message: "..." }
            if (typeof data.message === 'string') {
                normalized.message = data.message;
                return normalized;
            }

            // 5. Callback Format: { data: { response: <int>, more_info: "..." } }
            if (data.data && typeof data.data.response === 'number') {
                normalized.code = `LEGACY_${data.data.response}`;
                normalized.message = data.data.more_info || data.data.var || `Legacy Error ${data.data.response}`;
                return normalized;
            }

            // 6. Validation Errors: { errors: { ... } }
             if (data.errors && typeof data.errors === 'object') {
                normalized.message = data.message || 'Validation failed';
                normalized.code = 'VALIDATION_FAILED';
                normalized.category = 'VALIDATION';
                normalized.meta = { errors: data.errors };
                return normalized;
            }

            return normalized;
        },

        /**
         * Check if Step-Up Authentication is required
         * Handles both legacy and unified formats
         * @param {object|null} data
         * @returns {boolean}
         */
        isStepUpRequired(data) {
            if (!data || typeof data !== 'object') return false;

            // Legacy: Top-level code
            if (data.code === 'STEP_UP_REQUIRED') return true;

            // Unified: Nested error code
            if (data.error && data.error.code === 'STEP_UP_REQUIRED') return true;

            return false;
        },

        /**
         * Get legacy-compatible Step-Up view { code, scope }
         * useful for existing redirect logic
         * @param {object|null} data
         * @returns {object|null}
         */
        getLegacyStepUpView(data) {
            if (!this.isStepUpRequired(data)) return null;

            let scope = '';

            // Legacy
            if (data.scope) {
                scope = data.scope;
            }
            // Unified
            else if (data.error && data.error.meta && data.error.meta.scope) {
                scope = data.error.meta.scope;
            }

            return {
                code: 'STEP_UP_REQUIRED',
                scope: scope
            };
        },

        /**
         * Safe message extractor
         * @param {object|null} data
         * @returns {string}
         */
        getMessage(data) {
            const norm = this.normalize(data);
            return norm.message;
        }
    };

    // Expose globally
    window.ErrorNormalizer = ErrorNormalizer;
    console.log('✅ ErrorNormalizer loaded globally');

})();
