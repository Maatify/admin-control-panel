/**
 * 🛠️ Currencies Helpers V2
 * Bridge-first helper utilities for currencies pages.
 */

(function() {
    'use strict';

    console.log('🛠️ Currencies Helpers V2 Loading...');

    if (!window.AdminPageBridge) {
        console.error('❌ AdminPageBridge not found');
        return;
    }

    const Bridge = window.AdminPageBridge;

    function setupButtonHandler(selector, callback, options) {
        const cfg = options || {};
        const dataAttribute = cfg.dataAttribute || 'data-currency-id';
        const preventDefault = cfg.preventDefault !== false;
        const stopPropagation = !!cfg.stopPropagation;
        const requireData = cfg.requireData !== false;

        return Bridge.Events.onClick(selector, async function(event, btn) {
            if (preventDefault) event.preventDefault();
            if (stopPropagation) event.stopPropagation();

            const currencyId = btn.getAttribute(dataAttribute);
            if (requireData && !currencyId) {
                console.warn('[CurrenciesHelpersV2] Missing data attribute', { selector, dataAttribute });
                return;
            }

            await callback(currencyId, btn, event);
        });
    }

    function openModal(modalId) {
        return Bridge.Modal.open('#' + modalId);
    }

    function closeModal(modalId) {
        return Bridge.Modal.close('#' + modalId);
    }

    function closeAllModals() {
        document.querySelectorAll('[id$="-modal"]').forEach(function(modal) {
            Bridge.Modal.close(modal);
        });
    }

    function setupModalCloseHandlers() {
        Bridge.Events.onClick('.close-modal', function(event, btn) {
            const modal = btn.closest('[id$="-modal"]');
            if (modal) Bridge.Modal.close(modal, { resetForm: true });
        });

        document.addEventListener('click', function(event) {
            if (event.target && event.target.id && event.target.id.endsWith('-modal')) {
                Bridge.Modal.close('#' + event.target.id);
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') closeAllModals();
        });
    }

    function clearFormInputs(formId) {
        const form = document.getElementById(formId);
        if (form && typeof form.reset === 'function') form.reset();
    }

    function setFormDisabled(formId, disabled) {
        const form = document.getElementById(formId);
        if (!form) return;
        form.querySelectorAll('input, select, textarea, button').forEach(function(el) {
            el.disabled = !!disabled;
        });
    }

    function bindResetPageReload(config) {
        const cfg = config || {};
        const reload = typeof cfg.reload === 'function' ? cfg.reload : function() {};
        const beforeReload = typeof cfg.beforeReload === 'function' ? cfg.beforeReload : null;

        const trigger = Bridge.Events.createResetReload({
            setPage: cfg.setPage,
            resetPage: cfg.resetPage || 1,
            preventDefault: !!cfg.preventDefault,
            reload: function(event) {
                if (beforeReload) beforeReload(event);
                return reload(event);
            }
        });

        if (typeof cfg.bind === 'function') cfg.bind(trigger);
        return trigger;
    }

    function bindTableActionState(config) {
        const cfg = config || {};
        const getParams = typeof cfg.getParams === 'function'
            ? cfg.getParams
            : (typeof cfg.buildParams === 'function' ? cfg.buildParams : function() { return {}; });
        const getState = typeof cfg.getState === 'function' ? cfg.getState : function() { return {}; };
        const setState = typeof cfg.setState === 'function' ? cfg.setState : function() {};
        const reload = typeof cfg.reload === 'function' ? cfg.reload : function() {};
        let lastContext = null;

        return Bridge.Table.bindActionState({
            root: cfg.target || document,
            eventName: cfg.eventName || 'tableAction',
            sourceContainerId: cfg.sourceContainerId,
            sourceFilter: cfg.sourceFilter,
            getState: getParams,
            applyOptions: cfg.applyOptions || {},
            setState: function(next, detail, event) {
                const state = getState() || {};
                const normalized = {
                    page: next.page ?? state.page,
                    perPage: next.per_page ?? state.perPage
                };
                lastContext = { detail, next, event };
                setState(normalized, detail, next, event);
            },
            reload: function() {
                if (!lastContext) return reload({}, {}, null);
                return reload(lastContext.detail, lastContext.next, lastContext.event);
            }
        });
    }

    function isValidCurrencyCode(code) {
        return /^[A-Z]{3}$/i.test(code || '');
    }

    function isNonEmpty(value) {
        return typeof value === 'string' ? value.trim().length > 0 : !!value;
    }

    window.CurrenciesHelpersV2 = {
        setupButtonHandler,
        openModal,
        closeModal,
        closeAllModals,
        setupModalCloseHandlers,
        clearFormInputs,
        setFormDisabled,
        bindResetPageReload,
        bindTableActionState,
        isValidCurrencyCode,
        isNonEmpty
    };

    console.log('✅ CurrenciesHelpersV2 loaded');
})();
