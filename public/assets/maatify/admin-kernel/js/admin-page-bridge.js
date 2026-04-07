/**
 * 🧩 Admin Page Bridge
 * ====================
 * Intermediate facade above shared admin kernel utilities.
 */

(function() {
    'use strict';

    const LOG_PREFIX = '[AdminPageBridge]';

    function log(event, details) {
        if (details !== undefined) {
            console.log(`${LOG_PREFIX} ${event}`, details);
            return;
        }
        console.log(`${LOG_PREFIX} ${event}`);
    }

    function warn(event, details) {
        console.warn(`${LOG_PREFIX} ${event}`, details || '');
    }

    function errorLog(event, details) {
        console.error(`${LOG_PREFIX} ${event}`, details || '');
    }

    const ApiHandler = window.ApiHandler || null;
    const ErrorNormalizer = window.ErrorNormalizer || null;

    log('loaded', {
        hasApiHandler: !!ApiHandler,
        hasErrorNormalizer: !!ErrorNormalizer
    });

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

    function detectContentTypeFromRaw(rawBody) {
        if (typeof rawBody !== 'string') return 'unknown';
        const trimmed = rawBody.trim();
        if (!trimmed) return 'empty';
        if (trimmed.startsWith('{') || trimmed.startsWith('[')) return 'json-like';
        if (trimmed.startsWith('<!DOCTYPE') || trimmed.startsWith('<html') || trimmed.startsWith('<')) return 'html-like';
        return 'text-like';
    }

    function resolveRoot(options) {
        if (!options || typeof options !== 'object') return document;
        return options.root || options.scope || document;
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
        log('action:start', {
            action: 'ui:alert',
            type: normalizedType,
            message,
            duration: duration || 5000
        });

        if (ApiHandler && typeof ApiHandler.showAlert === 'function') {
            ApiHandler.showAlert(normalizedType, message, duration || 5000);
            log('action:success', { action: 'ui:alert', via: 'ApiHandler.showAlert' });
            return;
        }

        if (typeof window.showAlert === 'function') {
            const compactType = normalizedType[0];
            window.showAlert(compactType, message, duration || 5000);
            log('action:success', { action: 'ui:alert', via: 'window.showAlert' });
            return;
        }

        console[normalizedType === 'danger' ? 'error' : 'log'](`${LOG_PREFIX} ui:alert:fallback`, message);
    }

    const DOM = {
        el(selectorOrElement, required = false, options = {}) {
            const root = resolveRoot(options);
            let found = null;

            if (typeof selectorOrElement === 'string') {
                found = root.querySelector(selectorOrElement);
            } else if (selectorOrElement && selectorOrElement.nodeType === 1) {
                found = selectorOrElement;
            }

            log('action:start', {
                action: 'dom:el',
                selectorOrElement,
                required,
                found: !!found,
                rootTag: root && root.tagName ? root.tagName : 'document'
            });

            if (!found && required) {
                const err = new Error(`Element not found: ${selectorOrElement}`);
                errorLog('action:failure', { action: 'dom:el', error: err.message });
                throw err;
            }

            return found;
        },

        value(selectorOrElement, defaultValue = '', options = {}) {
            const element = this.el(selectorOrElement, false, options);
            if (!element) return defaultValue;

            if (element.type === 'checkbox') {
                return element.checked;
            }

            return normalizeEmpty(element.value, defaultValue);
        },

        checked(selectorOrElement, defaultValue = false, options = {}) {
            const element = this.el(selectorOrElement, false, options);
            if (!element) return defaultValue;
            return !!element.checked;
        },

        int(selectorOrElement, defaultValue = null, options = {}) {
            const value = this.value(selectorOrElement, null, options);
            return normalizeInt(value, defaultValue);
        },

        bool(selectorOrElement, defaultValue = false, options = {}) {
            const value = this.value(selectorOrElement, '', options);
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

            log('action:start', {
                action: 'ui:confirm',
                message,
                okText,
                cancelText
            });

            const result = window.confirm(`${message}\n\n[${okText}] / [${cancelText}]`);

            log('action:success', {
                action: 'ui:confirm',
                result
            });

            return Promise.resolve(result);
        },

        async runAction(config) {
            const {
                action,
                actionName = 'ui:runAction',
                onSuccess,
                onError,
                successMessage,
                errorMessage
            } = config || {};

            if (typeof action !== 'function') {
                throw new Error('UI.runAction requires action function');
            }

            log('action:start', { action: actionName });

            try {
                const result = await action();
                log('action:success', { action: actionName, result });

                if (successMessage) this.success(successMessage);

                if (typeof onSuccess === 'function') {
                    log('callback:received', { callback: 'onSuccess', action: actionName });
                    onSuccess(result);
                }

                return result;
            } catch (error) {
                const safeMessage = errorMessage || error?.message || 'Action failed';

                errorLog('action:failure', {
                    action: actionName,
                    error: safeMessage,
                    rawError: error
                });

                this.error(safeMessage);

                if (typeof onError === 'function') {
                    log('callback:received', { callback: 'onError', action: actionName });
                    onError(error);
                }

                return { success: false, error: safeMessage, rawError: error };
            }
        }
    };

    const API = {
        normalizeError(data, fallbackMessage) {
            log('action:start', {
                action: 'api:normalize-error',
                hasData: !!data,
                fallbackMessage
            });

            if (ErrorNormalizer && typeof ErrorNormalizer.normalize === 'function') {
                const normalized = ErrorNormalizer.normalize(data);
                log('action:success', {
                    action: 'api:normalize-error',
                    via: 'ErrorNormalizer.normalize',
                    normalized
                });
                return {
                    message: normalized.message || fallbackMessage || 'Request failed',
                    normalized
                };
            }

            if (ApiHandler && typeof ApiHandler.extractErrorMessage === 'function') {
                const extracted = ApiHandler.extractErrorMessage(data) || fallbackMessage || 'Request failed';
                log('action:success', {
                    action: 'api:normalize-error',
                    via: 'ApiHandler.extractErrorMessage',
                    message: extracted
                });
                return {
                    message: extracted,
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

            log('api:start', {
                action: operation,
                endpoint,
                method,
                payload
            });

            if (!ApiHandler || typeof ApiHandler.call !== 'function') {
                const missingResult = { success: false, error: 'ApiHandler is not available globally' };

                errorLog('action:failure', {
                    action: operation,
                    reason: 'missing-api-handler',
                    result: missingResult
                });

                if (showErrorMessage) UI.error(missingResult.error);
                if (typeof onError === 'function') {
                    log('callback:received', { callback: 'onError', action: operation });
                    onError(missingResult);
                }

                return missingResult;
            }

            const result = await ApiHandler.call(endpoint, payload, operation, method);

            const rawBody = result ? result.rawBody : undefined;
            const contentTypeGuess = detectContentTypeFromRaw(rawBody);

            log('api:response', {
                action: operation,
                endpoint,
                method,
                success: !!result?.success,
                error: result?.error,
                status: result?.status,
                contentType: contentTypeGuess,
                rawResponse: rawBody === undefined ? '[not exposed by ApiHandler.call result]' : rawBody,
                parsedJson: result?.data
            });

            // ApiHandler.call details based on real shared contract:
            // - success + data:null => 200 with empty body
            // - success:false + rawBody + no data => parse failure / non-json branch
            // - success:false + error only => network branch
            if (result?.success === true && result?.data === null) {
                log('action:success', {
                    action: operation,
                    branch: 'empty-body-success'
                });
            }

            if (result?.success !== true && rawBody !== undefined && (result?.data === null || result?.data === undefined)) {
                errorLog('api:parse-failed', {
                    action: operation,
                    endpoint,
                    method,
                    contentType: contentTypeGuess,
                    rawResponse: rawBody,
                    error: result?.error,
                    status: result?.status
                });
            }

            if (!result || result.success !== true) {
                const normalizedError = this.normalizeError(result ? result.data : null, result?.error);
                const failed = {
                    success: false,
                    error: normalizedError.message,
                    data: result?.data,
                    raw: result
                };

                errorLog('action:failure', {
                    action: operation,
                    endpoint,
                    method,
                    failed
                });

                if (showErrorMessage) UI.error(failed.error);
                if (typeof onError === 'function') {
                    log('callback:received', { callback: 'onError', action: operation });
                    onError(failed);
                }

                return failed;
            }

            if (showSuccessMessage) UI.success(showSuccessMessage);

            if (typeof onSuccess === 'function') {
                log('callback:received', { callback: 'onSuccess', action: operation });
                onSuccess(result);
            }

            if (typeof afterSuccess === 'function') {
                log('callback:received', { callback: 'afterSuccess', action: operation });
                await afterSuccess(result);
            }

            log('action:success', {
                action: operation,
                endpoint,
                method,
                response: result
            });

            return {
                success: true,
                data: result.data,
                raw: result
            };
        }
    };

    const Modal = {
        open(modalSelectorOrElement, options = {}) {
            const modal = DOM.el(modalSelectorOrElement, false, options);
            if (!modal) {
                warn('action:failure', { action: 'modal:open', modal: modalSelectorOrElement, reason: 'not-found' });
                return false;
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            log('action:success', { action: 'modal:open', modal: modalSelectorOrElement });
            return true;
        },

        close(modalSelectorOrElement, options = {}) {
            const modal = DOM.el(modalSelectorOrElement, false, options);
            if (!modal) {
                warn('action:failure', { action: 'modal:close', modal: modalSelectorOrElement, reason: 'not-found' });
                return false;
            }

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

            log('action:success', {
                action: 'modal:close',
                modal: modalSelectorOrElement,
                resetForm: !!options.resetForm
            });

            return true;
        }
    };

    const Table = {
        reload(handler) {
            const explicitCandidates = toArray(handler);

            log('table:reload', {
                mode: explicitCandidates.length ? 'explicit-handler' : 'fallback-heuristic',
                handler
            });

            if (explicitCandidates.length) {
                for (let i = 0; i < explicitCandidates.length; i += 1) {
                    const item = explicitCandidates[i];

                    if (typeof item === 'function') {
                        try {
                            const output = item();
                            log('action:success', { action: 'table:reload', via: 'function-handler' });
                            return output;
                        } catch (err) {
                            errorLog('action:failure', { action: 'table:reload', via: 'function-handler', error: err });
                        }
                    }

                    if (typeof item === 'string' && typeof window[item] === 'function') {
                        try {
                            const output = window[item]();
                            log('action:success', { action: 'table:reload', via: `window.${item}` });
                            return output;
                        } catch (err) {
                            errorLog('action:failure', { action: 'table:reload', via: `window.${item}`, error: err });
                        }
                    }
                }
            }

            const fallbackCandidates = [window.reloadTableData, window.refreshTable, window.reloadTable];
            for (let j = 0; j < fallbackCandidates.length; j += 1) {
                const fallback = fallbackCandidates[j];
                if (typeof fallback === 'function') {
                    try {
                        const output = fallback();
                        log('action:success', {
                            action: 'table:reload',
                            via: 'heuristic-fallback',
                            fallbackName: fallback.name || '(anonymous)'
                        });
                        return output;
                    } catch (err) {
                        errorLog('action:failure', { action: 'table:reload', via: 'heuristic-fallback', error: err });
                    }
                }
            }

            warn('action:failure', {
                action: 'table:reload',
                reason: 'no-reload-handler-found'
            });

            return false;
        }
    };

    const Form = {
        collect(fieldsConfig = {}, options = {}) {
            const payload = {};
            const includeEmpty = !!options.includeEmpty;
            const root = resolveRoot(options);

            log('action:start', {
                action: 'form:collect',
                keys: Object.keys(fieldsConfig),
                includeEmpty,
                rootTag: root && root.tagName ? root.tagName : 'document'
            });

            Object.keys(fieldsConfig).forEach((key) => {
                const cfg = fieldsConfig[key];
                const normalizedCfg = typeof cfg === 'string' ? { selector: cfg } : (cfg || {});
                const type = normalizedCfg.type || 'value';
                const selector = normalizedCfg.selector;
                const fallback = normalizedCfg.default;
                const fieldRoot = resolveRoot(normalizedCfg.root || normalizedCfg.scope ? normalizedCfg : options);

                let value;
                if (type === 'int') value = DOM.int(selector, fallback, { root: fieldRoot });
                else if (type === 'bool') value = DOM.bool(selector, fallback, { root: fieldRoot });
                else if (type === 'checked') value = DOM.checked(selector, fallback, { root: fieldRoot });
                else value = DOM.value(selector, fallback, { root: fieldRoot });

                value = normalizeEmpty(value, null);

                if (includeEmpty || value !== null) {
                    payload[key] = value;
                }
            });

            log('action:success', {
                action: 'form:collect',
                payload
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

            log('action:success', {
                action: 'form:omit-empty',
                input: payload,
                output: clean
            });

            return clean;
        }
    };

    const Events = {
        on(eventName, selector, handler, root) {
            const scope = root || document;
            if (!scope || typeof scope.addEventListener !== 'function') {
                warn('bind', {
                    eventName,
                    selector,
                    reason: 'invalid-scope'
                });
                return function noop() {};
            }

            log('bind', {
                eventName,
                selector,
                scopeTag: scope && scope.tagName ? scope.tagName : 'document'
            });

            const listener = function(event) {
                const target = event.target.closest(selector);
                if (!target) return;

                log('callback:received', {
                    callback: 'event-handler',
                    eventName,
                    selector
                });

                handler(event, target);
            };

            scope.addEventListener(eventName, listener);

            return function unbind() {
                scope.removeEventListener(eventName, listener);
                log('bind', { eventName, selector, state: 'removed' });
            };
        },

        onClick(selector, handler, root) {
            return this.on('click', selector, handler, root);
        }
    };

    window.AdminPageBridge = {
        version: '1.1.0',
        DOM,
        API,
        UI,
        Modal,
        Table,
        Form,
        Events,

        normalizeInt,
        normalizeBool,
        normalizeEmpty
    };

    log('init', {
        version: window.AdminPageBridge.version,
        namespaces: Object.keys(window.AdminPageBridge)
    });
})();
