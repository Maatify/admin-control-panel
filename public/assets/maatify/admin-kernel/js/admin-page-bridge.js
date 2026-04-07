/**
 * 🧩 Admin Page Bridge
 * ====================
 * Intermediate facade above shared admin kernel utilities.
 *
 * Goals:
 * - Reduce repetitive page-level boilerplate
 * - Keep a stable page-facing API
 * - Wrap (not replace) existing global shared utilities
 */

(function() {
    'use strict';

    const ApiHandler = window.ApiHandler || null;
    const ErrorNormalizer = window.ErrorNormalizer || null;

    const TYPE_BY_PREFIX = {
        s: 'success',
        d: 'danger',
        w: 'warning',
        i: 'info'
    };

    function toArray(value) {
        if (Array.isArray(value)) return value;
        if (value === null || value === undefined) return [];
        return [value];
    }

    function normalizeBool(value, defaultValue) {
        if (value === null || value === undefined || value === '') {
            return defaultValue === undefined ? false : !!defaultValue;
        }
        if (typeof value === 'boolean') return value;
        if (typeof value === 'number') return value === 1;

        const text = String(value).trim().toLowerCase();
        if (['1', 'true', 'yes', 'on', 'y'].includes(text)) return true;
        if (['0', 'false', 'no', 'off', 'n'].includes(text)) return false;

        return defaultValue === undefined ? false : !!defaultValue;
    }

    function normalizeInt(value, defaultValue) {
        if (value === null || value === undefined || value === '') return defaultValue;
        const parsed = Number.parseInt(value, 10);
        return Number.isNaN(parsed) ? defaultValue : parsed;
    }

    function normalizeEmpty(value, fallback) {
        if (value === null || value === undefined) return fallback;
        if (typeof value === 'string' && value.trim() === '') return fallback;
        return value;
    }

    function resolveAlertType(type) {
        if (!type) return 'info';
        if (TYPE_BY_PREFIX[type]) return TYPE_BY_PREFIX[type];
        return type;
    }

    function showAlert(type, message, duration) {
        const normalizedType = resolveAlertType(type);
        if (ApiHandler && typeof ApiHandler.showAlert === 'function') {
            ApiHandler.showAlert(normalizedType, message, duration || 5000);
            return;
        }

        // Fallback to callback_handler-style global if present
        if (typeof window.showAlert === 'function') {
            const compactType = normalizedType[0];
            window.showAlert(compactType, message, duration || 5000);
            return;
        }

        console[normalizedType === 'danger' ? 'error' : 'log']('[AdminPageBridge alert]', message);
    }

    const DOM = {
        el(selectorOrElement, required = false, root) {
            let found = null;

            if (typeof selectorOrElement === 'string') {
                const scope = root || document;
                found = scope.querySelector(selectorOrElement);
            } else if (selectorOrElement && selectorOrElement.nodeType === 1) {
                found = selectorOrElement;
            }

            if (!found && required) {
                throw new Error(`Element not found: ${selectorOrElement}`);
            }

            return found;
        },

        value(selectorOrElement, defaultValue = '') {
            const element = this.el(selectorOrElement, false);
            if (!element) return defaultValue;

            if (element.type === 'checkbox') {
                return element.checked;
            }

            return normalizeEmpty(element.value, defaultValue);
        },

        checked(selectorOrElement, defaultValue = false) {
            const element = this.el(selectorOrElement, false);
            if (!element) return defaultValue;
            return !!element.checked;
        },

        int(selectorOrElement, defaultValue = null) {
            const value = this.value(selectorOrElement, null);
            return normalizeInt(value, defaultValue);
        },

        bool(selectorOrElement, defaultValue = false) {
            const value = this.value(selectorOrElement, '');
            return normalizeBool(value, defaultValue);
        }
    };

    const UI = {
        success(message, duration) {
            showAlert('success', message, duration);
        },

        error(message, duration) {
            showAlert('danger', message, duration);
        },

        warning(message, duration) {
            showAlert('warning', message, duration);
        },

        info(message, duration) {
            showAlert('info', message, duration);
        },

        confirm(message, options = {}) {
            const okText = options.okText || 'OK';
            const cancelText = options.cancelText || 'Cancel';
            const result = window.confirm(`${message}\n\n[${okText}] / [${cancelText}]`);
            return Promise.resolve(result);
        },

        async runAction(config) {
            const {
                action,
                onSuccess,
                onError,
                successMessage,
                errorMessage
            } = config || {};

            if (typeof action !== 'function') {
                throw new Error('UI.runAction requires action function');
            }

            try {
                const result = await action();
                if (successMessage) this.success(successMessage);
                if (typeof onSuccess === 'function') onSuccess(result);
                return result;
            } catch (error) {
                const safeMessage = errorMessage || error?.message || 'Action failed';
                this.error(safeMessage);
                if (typeof onError === 'function') onError(error);
                return { success: false, error: safeMessage, rawError: error };
            }
        }
    };

    const API = {
        normalizeError(data, fallbackMessage) {
            if (ErrorNormalizer && typeof ErrorNormalizer.normalize === 'function') {
                const normalized = ErrorNormalizer.normalize(data);
                return {
                    message: normalized.message || fallbackMessage || 'Request failed',
                    normalized
                };
            }

            if (ApiHandler && typeof ApiHandler.extractErrorMessage === 'function') {
                return {
                    message: ApiHandler.extractErrorMessage(data) || fallbackMessage || 'Request failed',
                    normalized: null
                };
            }

            return {
                message: fallbackMessage || 'Request failed',
                normalized: null
            };
        },

        async execute(config) {
            const {
                endpoint,
                payload = {},
                operation = 'API Call',
                method = 'POST',
                showSuccessMessage,
                showErrorMessage = true,
                onSuccess,
                onError,
                afterSuccess
            } = config || {};

            if (!ApiHandler || typeof ApiHandler.call !== 'function') {
                const missingResult = { success: false, error: 'ApiHandler is not available globally' };
                if (showErrorMessage) UI.error(missingResult.error);
                if (typeof onError === 'function') onError(missingResult);
                return missingResult;
            }

            const result = await ApiHandler.call(endpoint, payload, operation, method);

            if (!result || result.success !== true) {
                const normalizedError = this.normalizeError(result ? result.data : null, result?.error);
                const failed = {
                    success: false,
                    error: normalizedError.message,
                    data: result?.data,
                    raw: result
                };

                if (showErrorMessage) UI.error(failed.error);
                if (typeof onError === 'function') onError(failed);
                return failed;
            }

            if (showSuccessMessage) UI.success(showSuccessMessage);
            if (typeof onSuccess === 'function') onSuccess(result);
            if (typeof afterSuccess === 'function') await afterSuccess(result);

            return {
                success: true,
                data: result.data,
                raw: result
            };
        }
    };

    const Modal = {
        open(modalSelectorOrElement) {
            const modal = DOM.el(modalSelectorOrElement, false);
            if (!modal) return false;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            return true;
        },

        close(modalSelectorOrElement, options = {}) {
            const modal = DOM.el(modalSelectorOrElement, false);
            if (!modal) return false;

            modal.classList.add('hidden');
            if (!document.querySelector('[id$="-modal"]:not(.hidden)')) {
                document.body.style.overflow = '';
            }

            if (options.resetForm) {
                const form = modal.querySelector('form');
                if (form && typeof form.reset === 'function') {
                    form.reset();
                }
            }

            return true;
        }
    };

    const Table = {
        reload(handler) {
            const candidates = toArray(handler);

            if (!candidates.length) {
                candidates.push(window.reloadTableData, window.refreshTable, window.reloadTable);
            }

            for (let i = 0; i < candidates.length; i += 1) {
                const item = candidates[i];
                if (typeof item === 'function') {
                    try {
                        return item();
                    } catch (error) {
                        console.error('AdminPageBridge.Table.reload failed:', error);
                    }
                }

                if (typeof item === 'string' && typeof window[item] === 'function') {
                    try {
                        return window[item]();
                    } catch (error) {
                        console.error('AdminPageBridge.Table.reload failed:', error);
                    }
                }
            }

            return false;
        }
    };

    const Form = {
        collect(fieldsConfig = {}, options = {}) {
            const payload = {};
            const includeEmpty = !!options.includeEmpty;

            Object.keys(fieldsConfig).forEach((key) => {
                const cfg = fieldsConfig[key];
                const normalizedCfg = typeof cfg === 'string' ? { selector: cfg } : (cfg || {});
                const type = normalizedCfg.type || 'value';
                const selector = normalizedCfg.selector;
                const fallback = normalizedCfg.default;

                let value;
                if (type === 'int') value = DOM.int(selector, fallback);
                else if (type === 'bool') value = DOM.bool(selector, fallback);
                else if (type === 'checked') value = DOM.checked(selector, fallback);
                else value = DOM.value(selector, fallback);

                value = normalizeEmpty(value, null);

                if (includeEmpty || value !== null) {
                    payload[key] = value;
                }
            });

            return payload;
        },

        omitEmpty(payload) {
            const clean = {};
            Object.keys(payload || {}).forEach((key) => {
                const value = payload[key];
                if (value === null || value === undefined) return;
                if (typeof value === 'string' && value.trim() === '') return;
                clean[key] = value;
            });
            return clean;
        }
    };

    const Events = {
        on(eventName, selector, handler, root) {
            const scope = root || document;
            if (!scope || typeof scope.addEventListener !== 'function') return function noop() {};

            const listener = function(event) {
                const target = event.target.closest(selector);
                if (!target) return;
                handler(event, target);
            };

            scope.addEventListener(eventName, listener);

            return function unbind() {
                scope.removeEventListener(eventName, listener);
            };
        },

        onClick(selector, handler, root) {
            return this.on('click', selector, handler, root);
        }
    };

    window.AdminPageBridge = {
        version: '1.0.0',
        DOM,
        API,
        UI,
        Modal,
        Table,
        Form,
        Events,

        // shared low-level normalizers for optional direct use
        normalizeInt,
        normalizeBool,
        normalizeEmpty
    };

    console.log('✅ AdminPageBridge loaded');
})();
