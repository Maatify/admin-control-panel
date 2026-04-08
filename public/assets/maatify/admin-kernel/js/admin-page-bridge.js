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

    function safeClone(value) {
        if (value === null || value === undefined) return {};
        try {
            return JSON.parse(JSON.stringify(value));
        } catch (error) {
            warn('action:failure', {
                action: 'safe-clone',
                reason: 'json-clone-failed',
                error: error?.message
            });
            return Object.assign({}, value);
        }
    }

    function cleanObjectDeep(value, options = {}) {
        const trimStrings = options.trimStrings !== false;
        const removeEmptyObjects = options.removeEmptyObjects !== false;
        const removeEmptyArrays = options.removeEmptyArrays !== false;

        if (Array.isArray(value)) {
            const cleanedArray = value
                .map((item) => cleanObjectDeep(item, options))
                .filter((item) => item !== undefined);

            if (!cleanedArray.length && removeEmptyArrays) return undefined;
            return cleanedArray;
        }

        if (value && typeof value === 'object') {
            const out = {};
            Object.keys(value).forEach((key) => {
                const cleaned = cleanObjectDeep(value[key], options);
                if (cleaned !== undefined) {
                    out[key] = cleaned;
                }
            });

            if (!Object.keys(out).length && removeEmptyObjects) return undefined;
            return out;
        }

        if (value === null || value === undefined) return undefined;
        if (typeof value === 'string') {
            const next = trimStrings ? value.trim() : value;
            if (next === '') return undefined;
            return next;
        }

        return value;
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

    const Text = {
        escapeHtml(text) {
            if (text === null || text === undefined) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    };

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

        setValue(selectorOrElement, value = '', options = {}) {
            const element = this.el(selectorOrElement, false, options);
            if (!element) return false;

            if (element.type === 'checkbox') {
                element.checked = normalizeBool(value, false);
                return true;
            }

            if (element.tagName === 'SELECT' && element.multiple && Array.isArray(value)) {
                const selected = new Set(value.map((v) => String(v)));
                Array.from(element.options || []).forEach((option) => {
                    option.selected = selected.has(String(option.value));
                });
                return true;
            }

            if (value === null || value === undefined) {
                element.value = '';
                return true;
            }

            element.value = typeof value === 'string' ? value : String(value);
            return true;
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
        },

        async runMutation(config = {}) {
            const {
                endpoint,
                payload = {},
                operation = 'Mutation',
                method = 'POST',
                confirmMessage,
                confirmOptions = {},
                confirm,
                showErrorMessage = true,
                successMessage,
                reloadHandler,
                modal,
                modalOptions = {},
                onSuccess,
                onFailure,
                afterFinally
            } = config;

            log('mutation:start', {
                operation,
                endpoint,
                method,
                payload,
                hasConfirm: !!(confirmMessage || confirm),
                hasReloadHandler: !!reloadHandler,
                hasModal: !!modal
            });

            let shouldProceed = true;
            if (confirmMessage || confirm) {
                if (typeof confirm === 'function') {
                    shouldProceed = await confirm(config);
                } else {
                    shouldProceed = await UI.confirm(confirmMessage || 'Are you sure?', confirmOptions);
                }

                log('mutation:confirm', {
                    operation,
                    confirmed: !!shouldProceed
                });
            }

            if (!shouldProceed) {
                const cancelled = { success: false, cancelled: true, operation };
                log('mutation:failure', cancelled);
                if (typeof afterFinally === 'function') {
                    await afterFinally(cancelled);
                    log('mutation:finally', { operation, branch: 'cancelled' });
                }
                return cancelled;
            }

            try {
                const result = await this.execute({
                    endpoint,
                    payload,
                    operation,
                    method,
                    showSuccessMessage: successMessage,
                    showErrorMessage
                });

                if (result.success) {
                    if (modal) {
                        Modal.close(modal, modalOptions);
                    }

                    if (reloadHandler) {
                        Table.reload(reloadHandler);
                    }

                    if (typeof onSuccess === 'function') {
                        log('callback:received', { callback: 'onSuccess', action: operation });
                        onSuccess(result);
                    }

                    log('mutation:success', {
                        operation,
                        endpoint,
                        method,
                        result
                    });
                    return result;
                }

                if (typeof onFailure === 'function') {
                    log('callback:received', { callback: 'onFailure', action: operation });
                    onFailure(result);
                }

                log('mutation:failure', {
                    operation,
                    endpoint,
                    method,
                    result
                });
                return result;
            } finally {
                if (typeof afterFinally === 'function') {
                    await afterFinally();
                }
                log('mutation:finally', {
                    operation,
                    endpoint,
                    method
                });
            }
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
        applyActionParams(baseParams, actionInput, value, options = {}) {
            const {
                resetPageOnPerPageChange = true,
                cleanEmpty = true,
                actionHandlers = {}
            } = options;

            const params = safeClone(baseParams || {});
            const rawActions = Array.isArray(actionInput) ? actionInput : [actionInput];
            const actions = rawActions.map((item) => {
                if (typeof item === 'string') return { action: item, value };
                return item || {};
            });

            log('table:apply-action-params', {
                input: baseParams,
                actions,
                options: {
                    resetPageOnPerPageChange,
                    cleanEmpty,
                    actionHandlerKeys: Object.keys(actionHandlers || {})
                }
            });

            actions.forEach((item) => {
                const actionName = item.action;
                const actionValue = item.value;

                if (actionName === 'pageChange') {
                    params.page = actionValue;
                    return;
                }

                if (actionName === 'perPageChange') {
                    params.per_page = actionValue;
                    if (resetPageOnPerPageChange) params.page = 1;
                    return;
                }

                if (actionHandlers && typeof actionHandlers[actionName] === 'function') {
                    actionHandlers[actionName](params, actionValue, item);
                }
            });

            const output = cleanEmpty ? (cleanObjectDeep(params) || {}) : params;
            log('table:apply-action-params', { output });
            return output;
        },

        bindActionState(config = {}) {
            const {
                eventName = 'tableAction',
                root = document,
                sourceContainerId = null,
                sourceFilter = null,
                getState,
                setState,
                actionHandlers = {},
                applyOptions = {},
                reload,
                onBeforeReload
            } = config;

            const scope = root || document;
            if (!scope || typeof scope.addEventListener !== 'function') {
                warn('table:bind-action-state', { reason: 'invalid-scope', eventName });
                return function noop() {};
            }

            if (typeof setState !== 'function') {
                warn('table:bind-action-state', { reason: 'missing-set-state', eventName });
                return function noop() {};
            }

            log('bind', {
                action: 'table:bind-action-state',
                eventName
            });

            const listener = (event) => {
                const detail = (event && event.detail) || {};
                const sourceContainer = detail.tableContainerId ?? null;

                if (sourceContainerId !== null && sourceContainerId !== undefined) {
                    if (!sourceContainer || sourceContainer !== sourceContainerId) {
                        log('table:bind-action-state:skip', {
                            reason: 'source-container-mismatch',
                            expected: sourceContainerId,
                            received: sourceContainer,
                            eventName
                        });
                        return;
                    }
                }

                if (typeof sourceFilter === 'function') {
                    let allowed = false;
                    try {
                        allowed = !!sourceFilter(detail, event);
                    } catch (error) {
                        errorLog('table:bind-action-state:filter-error', {
                            eventName,
                            error: error?.message || error
                        });
                        return;
                    }

                    if (!allowed) {
                        log('table:bind-action-state:skip', {
                            reason: 'source-filter',
                            eventName,
                            detail
                        });
                        return;
                    }
                }

                const stateFromDetail = detail.currentParams;
                const sourceState = stateFromDetail !== undefined
                    ? stateFromDetail
                    : (typeof getState === 'function' ? getState(detail, event) : {});

                const nextState = Table.applyActionParams(
                    sourceState || {},
                    detail.action,
                    detail.value,
                    Object.assign({}, applyOptions, { actionHandlers })
                );

                setState(nextState, detail, event);
                if (typeof onBeforeReload === 'function') onBeforeReload(nextState, detail, event);
                Table.reload(reload);
            };

            scope.addEventListener(eventName, listener);

            return function unbind() {
                scope.removeEventListener(eventName, listener);
                log('bind', {
                    action: 'table:bind-action-state',
                    eventName,
                    state: 'removed'
                });
            };
        },

        withTargetContainer(containerId, run) {
            const targetId = normalizeEmpty(containerId, 'table-container');
            const callback = typeof run === 'function' ? run : function noop() {};
            const defaultContainer = document.getElementById('table-container');
            const targetContainer = document.getElementById(targetId);

            if (!targetContainer || targetId === 'table-container') {
                return callback();
            }

            const originalDefaultId = defaultContainer ? defaultContainer.id : null;
            const originalTargetId = targetContainer.id;
            const swappedPlaceholder = '__admin-page-bridge-table-container-original__';
            let restored = false;

            function restoreIds() {
                if (restored) return;
                restored = true;
                targetContainer.id = originalTargetId;
                if (defaultContainer && defaultContainer !== targetContainer) {
                    defaultContainer.id = originalDefaultId || 'table-container';
                }
            }

            if (defaultContainer && defaultContainer !== targetContainer) {
                defaultContainer.id = swappedPlaceholder;
            }
            targetContainer.id = 'table-container';

            let output;
            try {
                output = callback(targetContainer);
            } catch (error) {
                restoreIds();
                throw error;
            }

            if (output && typeof output.then === 'function' && typeof output.finally === 'function') {
                return output.finally(restoreIds);
            }

            restoreIds();
            return output;
        },

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
        },

        createResetReload(config = {}) {
            const {
                setPage,
                reload,
                resetPage = 1,
                preventDefault = true
            } = config;

            return function handleResetReload(event) {
                if (preventDefault && event && typeof event.preventDefault === 'function') {
                    event.preventDefault();
                }

                if (typeof setPage === 'function') {
                    setPage(resetPage, event);
                }

                Table.reload(reload);
            };
        },

        bindFilterForm(config = {}) {
            const {
                form,
                root,
                resetButton,
                omitEmpty = true,
                fields = null,
                collect,
                onSubmit,
                onReset,
                preventDefault = true
            } = config;

            const scope = root || document;
            const formEl = DOM.el(form, false, { root: scope }) || (form && form.nodeType === 1 ? form : null);
            if (!formEl) {
                warn('filters:bind', { reason: 'form-not-found', form });
                return { unbind: function noop() {} };
            }

            const collectPayload = function() {
                if (typeof collect === 'function') {
                    return collect(formEl);
                }

                // Explicit field map
                if (fields && typeof fields === 'object') {
                    return Form.collect(fields, { root: formEl, includeEmpty: !omitEmpty });
                }

                // Generic from form controls
                const payload = {};
                const formData = new FormData(formEl);

                formData.forEach((v, k) => {
                    if (payload[k] === undefined) {
                        payload[k] = v;
                        return;
                    }

                    if (!Array.isArray(payload[k])) {
                        payload[k] = [payload[k]];
                    }
                    payload[k].push(v);
                });

                // ensure unchecked checkboxes default false if name exists
                formEl.querySelectorAll('input[type="checkbox"][name]').forEach((input) => {
                    if (!Object.prototype.hasOwnProperty.call(payload, input.name)) {
                        payload[input.name] = false;
                    } else {
                        payload[input.name] = normalizeBool(payload[input.name], false);
                    }
                });

                if (!omitEmpty) return payload;
                return cleanObjectDeep(payload) || {};
            };

            log('filters:bind', {
                form: formEl.id || '(anonymous-form)',
                hasResetButton: !!resetButton,
                omitEmpty,
                hasCustomCollect: typeof collect === 'function'
            });

            const submitListener = function(event) {
                if (preventDefault && event && typeof event.preventDefault === 'function') {
                    event.preventDefault();
                }
                const payload = collectPayload();

                log('filters:submit', {
                    form: formEl.id || '(anonymous-form)',
                    payload
                });

                if (typeof onSubmit === 'function') {
                    onSubmit(payload, { event, form: formEl });
                }
            };

            formEl.addEventListener('submit', submitListener);

            let resetTarget = null;
            let resetListener = null;
            if (resetButton) {
                resetTarget = DOM.el(resetButton, false, { root: scope }) || resetButton;
                if (resetTarget && typeof resetTarget.addEventListener === 'function') {
                    resetListener = function(event) {
                        if (preventDefault && event && typeof event.preventDefault === 'function') {
                            event.preventDefault();
                        }

                        if (typeof formEl.reset === 'function') {
                            formEl.reset();
                        }

                        const payload = collectPayload();
                        log('filters:reset', {
                            form: formEl.id || '(anonymous-form)',
                            payload
                        });

                        if (typeof onReset === 'function') {
                            onReset(payload, { event, form: formEl });
                        }
                    };
                    resetTarget.addEventListener('click', resetListener);
                }
            }

            return {
                collect: collectPayload,
                unbind: function unbind() {
                    formEl.removeEventListener('submit', submitListener);
                    if (resetTarget && resetListener) {
                        resetTarget.removeEventListener('click', resetListener);
                    }
                }
            };
        },

        bindDebouncedInput(config = {}) {
            const {
                input,
                root,
                delay = 500,
                eventName = 'input',
                onFire,
                transform
            } = config;

            const scope = root || document;
            const inputEl = DOM.el(input, false, { root: scope }) || (input && input.nodeType === 1 ? input : null);
            if (!inputEl) {
                warn('input:debounced-bind', { reason: 'input-not-found', input });
                return { unbind: function noop() {} };
            }

            let timeoutId = null;
            log('input:debounced-bind', {
                input: inputEl.id || inputEl.name || '(anonymous-input)',
                delay,
                eventName
            });

            const listener = function(event) {
                const rawValue = event?.target?.value;
                const nextValue = typeof transform === 'function' ? transform(rawValue, event, inputEl) : rawValue;

                if (timeoutId) clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    log('input:debounced-fire', {
                        input: inputEl.id || inputEl.name || '(anonymous-input)',
                        delay,
                        value: nextValue
                    });

                    if (typeof onFire === 'function') {
                        onFire(nextValue, { event, input: inputEl, rawValue });
                    }
                }, delay);
            };

            inputEl.addEventListener(eventName, listener);

            return {
                unbind: function unbind() {
                    if (timeoutId) clearTimeout(timeoutId);
                    inputEl.removeEventListener(eventName, listener);
                }
            };
        },

        bindEnterAction(config = {}) {
            const {
                input,
                root,
                onEnter,
                eventName = 'keypress',
                preventDefault = true,
                ignoreInsideForm = true,
                stopPropagation = false,
                predicate
            } = config;

            const scope = root || document;
            const inputEl = DOM.el(input, false, { root: scope }) || (input && input.nodeType === 1 ? input : null);
            if (!inputEl) {
                warn('input:enter-bind', { reason: 'input-not-found', input });
                return { unbind: function noop() {} };
            }

            log('input:enter-bind', {
                input: inputEl.id || inputEl.name || '(anonymous-input)',
                eventName,
                preventDefault,
                ignoreInsideForm,
                stopPropagation
            });

            const listener = function(event) {
                if (!event || event.key !== 'Enter') return;
                if (ignoreInsideForm && event.target && event.target.closest('form')) return;

                if (typeof predicate === 'function') {
                    let passes = false;
                    try {
                        passes = !!predicate(event, inputEl);
                    } catch (error) {
                        errorLog('input:enter-bind:predicate-error', {
                            input: inputEl.id || inputEl.name || '(anonymous-input)',
                            error: error?.message || error
                        });
                        return;
                    }
                    if (!passes) return;
                }

                if (preventDefault && typeof event.preventDefault === 'function') event.preventDefault();
                if (stopPropagation && typeof event.stopPropagation === 'function') event.stopPropagation();

                log('input:enter-fire', {
                    input: inputEl.id || inputEl.name || '(anonymous-input)',
                    value: inputEl.value
                });

                if (typeof onEnter === 'function') {
                    onEnter(inputEl.value, { event, input: inputEl });
                }
            };

            inputEl.addEventListener(eventName, listener);

            return {
                unbind: function unbind() {
                    inputEl.removeEventListener(eventName, listener);
                }
            };
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
        Text,

        normalizeInt,
        normalizeBool,
        normalizeEmpty
    };

    log('init', {
        version: window.AdminPageBridge.version,
        namespaces: Object.keys(window.AdminPageBridge)
    });
})();
