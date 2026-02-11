/**
 * 🔧 Universal API Handler - Enhanced Version
 * ============================================
 * Handles all API responses including:
 * - JSON responses
 * - Empty responses (200 OK with no body)
 * - HTML error pages
 * - Network errors
 *
 * Usage:
 * const result = await ApiHandler.call('languages/create', payload, 'Create Language');
 * if (!result.success) {
 *     ApiHandler.showAlert('danger', result.error);
 *     return;
 * }
 * // Success!
 */

// Use IIFE to create and export immediately
if (typeof window !== 'undefined') {
    window.ApiHandler = (function() {
        'use strict';

        // ========================================================================
        // Constants
        // ========================================================================

        const API_BASE = '/api';

        const ALERT_TYPES = {
            success: 'bg-green-100 border-green-400 text-green-700',
            danger: 'bg-red-100 border-red-400 text-red-700',
            warning: 'bg-yellow-100 border-yellow-400 text-yellow-700',
            info: 'bg-blue-100 border-blue-400 text-blue-700'
        };

        // ========================================================================
        // Response Parser
        // ========================================================================

        /**
         * Parse API response - handles JSON, empty body, and HTML
         *
         * @param {Response} response - Fetch API Response
         * @param {string} operation - Operation name for logging
         * @returns {Promise<Object>} { success, data?, error?, rawBody? }
         */
        async function parseResponse(response, operation) {
            console.group(`📡 [${operation}] Response Details`);
            console.log('Status:', response.status, response.statusText);
            console.log('OK:', response.ok);
            console.log('Type:', response.type);
            console.log('URL:', response.url);

            // Log all headers
            console.log('Headers:');
            const headers = {};
            response.headers.forEach((value, key) => {
                headers[key] = value;
            });
            console.table(headers);

            console.groupEnd();

            // Get raw response text
            const rawText = await response.text();

            // 🔍 DIRECT LOGS (always visible - NO SEPARATORS!)
            console.log(`📥 [${operation}] ======== RESPONSE ========`);
            console.log(`📊 [${operation}] STATUS:`, response.status, response.statusText);
            console.log(`📄 [${operation}] RAW BODY:`, rawText || '<EMPTY>');
            if (rawText && rawText.length > 500) {
                console.log(`📄 [${operation}] BODY (truncated):`, rawText.substring(0, 500) + '...');
            }

            // Always log raw response
            console.group(`📄 [${operation}] Raw Response Body`);
            if (!rawText || rawText.trim() === '') {
                console.log('Body: <EMPTY>');
                console.log('Length: 0');
            } else {
                console.log('Body:', rawText);
                console.log('Length:', rawText.length, 'characters');
                console.log('First 200 chars:', rawText.substring(0, 200));

                // Try to identify content type
                if (rawText.trim().startsWith('<!DOCTYPE') || rawText.trim().startsWith('<html')) {
                    console.warn('⚠️ Content appears to be HTML (possibly an error page)');
                } else if (rawText.trim().startsWith('{') || rawText.trim().startsWith('[')) {
                    console.log('✅ Content appears to be JSON');
                } else {
                    console.warn('⚠️ Content type unknown');
                }
            }
            console.groupEnd();

            // ✅ Handle 200 OK with empty body (mutation success)
            if (response.status === 200 && (!rawText || rawText.trim() === '')) {
                console.log(`✅ [${operation}] Empty response = Success (mutation completed)`);
                return { success: true, data: null };
            }

            // Try to parse as JSON
            let data = null;
            try {
                if (rawText && rawText.trim()) {
                    data = JSON.parse(rawText);

                    // 🔍 DIRECT LOGS (always visible)
                    console.log(`✅ [${operation}] PARSED DATA:`, data);
                    console.log(`✅ [${operation}] DATA (JSON):`, JSON.stringify(data, null, 2));

                    console.group(`✅ [${operation}] Parsed JSON`);
                    console.log('Data:', data);

                    // Pretty print if possible
                    try {
                        console.log('Pretty JSON:');
                        console.log(JSON.stringify(data, null, 2));
                    } catch (e) {
                        // Skip pretty print if circular reference
                    }
                    console.groupEnd();
                }
            } catch (parseError) {
                console.group(`❌ [${operation}] JSON Parse Failed`);
                console.error('Parse Error:', parseError.message);
                console.error('Error Stack:', parseError.stack);
                console.log('Raw text that failed to parse:', rawText);

                // Show where parsing failed
                if (parseError.message.includes('position')) {
                    const match = parseError.message.match(/position (\d+)/);
                    if (match) {
                        const pos = parseInt(match[1]);
                        console.log('Context around error position:');
                        console.log('...', rawText.substring(Math.max(0, pos - 50), pos + 50), '...');
                    }
                }
                console.groupEnd();

                return {
                    success: false,
                    error: `Server returned invalid JSON (Status: ${response.status}). Check console for details.`,
                    rawBody: rawText,
                    status: response.status
                };
            }

            // ✅ Success response (200 with JSON data)
            if (response.status === 200) {
                // Check for error object in 200 response (some APIs do this)
                if (data && data.error) {
                    console.group(`❌ [${operation}] Error Object in 200 Response`);
                    console.error('Error:', data.error);
                    console.log('Full Response:', data);
                    console.groupEnd();

                    return {
                        success: false,
                        error: extractErrorMessage(data),
                        data: data
                    };
                }

                console.log(`✅ [${operation}] Success`);
                return { success: true, data: data };
            }

            // ❌ Non-200 responses
            console.group(`❌ [${operation}] HTTP Error ${response.status}`);
            console.error('Status:', response.status, response.statusText);
            console.error('Data:', data);

            // If no data (JSON parse failed), show raw body
            if (!data && rawText) {
                console.error('Raw Body (HTML/Text):', rawText);
            }
            console.groupEnd();

            const result = {
                success: false,
                error: extractErrorMessage(data) || `HTTP ${response.status}: ${response.statusText}`,
                data: data,
                rawBody: data ? undefined : rawText,  // Include rawBody if JSON parse failed
                status: response.status
            };

            // Final summary
            console.group(`📊 [${operation}] Final Result`);
            console.log('Success:', result.success);
            console.log('Error:', result.error);
            console.log('Data:', result.data);
            if (result.rawBody) {
                console.log('Raw Body:', result.rawBody);
            }
            console.log('Status:', result.status);
            console.groupEnd();

            return result;
        }

        // ========================================================================
        // Error Message Extraction
        // ========================================================================

        /**
         * Extract user-friendly error message from response data
         * Handles multiple error formats:
         * - { error: { message: "..." } }
         * - { error: "..." }
         * - { message: "..." }
         * - { error: { message: "...", fields: {...} } }
         * - { error: "...", errors: {...} }
         */
        function extractErrorMessage(data) {
            if (!data) return 'An error occurred';

            // Format 1: { error: { message: "...", fields: {...} } }
            if (data.error && typeof data.error === 'object') {
                let message = data.error.message || 'Validation error';

                // Add field errors if present
                if (data.error.fields) {
                    const fieldErrors = Object.entries(data.error.fields)
                        .map(([field, msg]) => `${field}: ${msg}`)
                        .join(', ');
                    message += ` (${fieldErrors})`;
                }

                return message;
            }

            // Format 2: { error: "..." }
            if (data.error && typeof data.error === 'string') {
                return data.error;
            }

            // Format 3: { message: "..." }
            if (data.message) {
                return data.message;
            }

            // Format 4: { error: "...", errors: {...} } (422 validation)
            if (data.errors && typeof data.errors === 'object') {
                const fieldErrors = Object.entries(data.errors)
                    .map(([field, msg]) => `${field}: ${msg}`)
                    .join(', ');
                return `${data.error || 'Validation error'} (${fieldErrors})`;
            }

            return 'An error occurred';
        }

        // ========================================================================
        // API Call
        // ========================================================================

        /**
         * Make API call with automatic error handling
         *
         * @param {string} endpoint - API endpoint (e.g., 'languages/create')
         * @param {object} payload - Request body
         * @param {string} operation - Operation name for logging
         * @param {string} method - HTTP Method (default: POST)
         * @returns {Promise<Object>} { success, data?, error? }
         */
        async function call(endpoint, payload, operation = 'API Call', method = 'POST') {
            // Start timing
            const startTime = performance.now();

            // ========================================================================
            // Request Logging (BEFORE sending)
            // ========================================================================

            // 🔍 DIRECT LOGS (always visible - NO SEPARATORS!)
            console.log(`📤 [${operation}] ======== REQUEST ========`);
            console.log(`🌐 [${operation}] URL:`, `${API_BASE}/${endpoint.replace(/^\/+|\/+$/g, '').replace(/^api\//, '')}`);
            console.log(`🌐 [${operation}] METHOD:`, method);
            console.log(`📦 [${operation}] PAYLOAD:`, payload);
            console.log(`📋 [${operation}] PAYLOAD (formatted):`, JSON.stringify(payload, null, 2));

            console.group(`📤 [${operation}] Request Details`);
            console.log('Timestamp:', new Date().toISOString());
            console.log('Endpoint:', endpoint);
            console.log('Method:', method);
            console.log('Payload:', payload);

            // Pretty print payload
            try {
                console.log('Payload (Pretty JSON):');
                console.log(JSON.stringify(payload, null, 2));
            } catch (e) {
                console.log('Payload (Cannot stringify):', payload);
            }

            // Show payload size
            try {
                const payloadStr = JSON.stringify(payload);
                console.log('Payload Size:', payloadStr.length, 'characters');
            } catch (e) {
                // Skip
            }

            console.groupEnd();

            try {
                // Normalize endpoint
                let cleanEndpoint = endpoint.replace(/^\/+|\/+$/g, '');
                if (cleanEndpoint.startsWith('api/')) {
                    cleanEndpoint = cleanEndpoint.substring(4);
                }
                const url = `${API_BASE}/${cleanEndpoint}`;

                console.log(`🌐 [${operation}] Full URL:`, url);
                console.log(`🌐 [${operation}] Method: ${method}`);
                console.log(`🌐 [${operation}] Content-Type: application/json`);

                const options = {
                    method: method,
                    headers: { 'Content-Type': 'application/json' }
                };

                if (method !== 'GET' && method !== 'HEAD') {
                    options.body = JSON.stringify(payload);
                }

                // Make the actual API call
                const response = await fetch(url, options);

                const result = await parseResponse(response, operation);

                // Calculate and log duration
                const endTime = performance.now();
                const duration = endTime - startTime;

                console.log(`⏱️ [${operation}] Duration: ${duration.toFixed(2)}ms`);

                return result;

            } catch (error) {
                console.group(`❌ [${operation}] Network Error`);
                console.error('Error Type:', error.name);
                console.error('Error Message:', error.message);
                console.error('Error Stack:', error.stack);
                console.groupEnd();

                return {
                    success: false,
                    error: `Network error: ${error.message}`
                };
            }
        }

        // ========================================================================
        // User Alerts
        // ========================================================================

        /**
         * Show alert to user
         *
         * @param {string} type - 'success', 'danger', 'warning', 'info'
         * @param {string} message - Message to display
         * @param {number} duration - Auto-close duration (ms), 0 = no auto-close
         */
        function showAlert(type, message, duration = 5000) {
            const normalizedType = type === 'd' ? 'danger' :
                type === 's' ? 'success' :
                    type === 'w' ? 'warning' :
                        type === 'i' ? 'info' : type;

            const colorClass = ALERT_TYPES[normalizedType] || ALERT_TYPES.info;

            const alert = document.createElement('div');
            alert.className = `fixed top-4 right-4 z-50 ${colorClass} border px-4 py-3 rounded-lg shadow-lg max-w-md animate-slide-in`;
            alert.innerHTML = `
            <div class="flex items-center justify-between">
                <span>${message}</span>
                <button 
                    onclick="this.parentElement.parentElement.remove()" 
                    class="ml-4 text-lg font-bold hover:opacity-75 transition-opacity"
                >
                    &times;
                </button>
            </div>
        `;

            document.body.appendChild(alert);

            // Auto-remove after duration (if not 0)
            if (duration > 0) {
                setTimeout(() => {
                    if (alert.parentElement) {
                        alert.remove();
                    }
                }, duration);
            }
        }

        // ========================================================================
        // Capability Check
        // ========================================================================

        /**
         * Check if user has capability
         *
         * @param {string} capability - Capability name (e.g., 'can_create')
         * @param {object} capabilities - Capabilities object (defaults to window.languagesCapabilities)
         * @returns {boolean}
         */
        function hasCapability(capability, capabilities = null) {
            const caps = capabilities || window.languagesCapabilities || {};
            return caps[capability] === true;
        }

        // ========================================================================
        // Field Errors Display
        // ========================================================================

        /**
         * Show field-level errors in form
         *
         * @param {object} fields - Field errors { fieldName: "error message" }
         * @param {string} formId - Form ID to show errors in
         */
        function showFieldErrors(fields, formId) {
            if (!fields || typeof fields !== 'object') return;

            const form = document.getElementById(formId);
            if (!form) return;

            // Clear existing errors
            form.querySelectorAll('.field-error').forEach(el => el.remove());

            // Add new errors
            Object.entries(fields).forEach(([fieldName, errorMessage]) => {
                const input = form.querySelector(`[name="${fieldName}"], #${fieldName}`);
                if (!input) return;

                // Add error class to input
                input.classList.add('border-red-500');

                // Create error message element
                const errorEl = document.createElement('div');
                errorEl.className = 'field-error text-red-600 text-sm mt-1';
                errorEl.textContent = errorMessage;

                // Insert after input
                input.parentElement.appendChild(errorEl);
            });
        }

        // ========================================================================
        // Public API
        // ========================================================================

        return {
            call,
            parseResponse,
            showAlert,
            hasCapability,
            showFieldErrors,
            extractErrorMessage
        };

    })(); // End IIFE and assign to window.ApiHandler

    console.log('✅ ApiHandler loaded and exported to window');
}