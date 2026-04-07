/**
 * 🌐 I18n Helpers V2
 * Shared local helpers for i18n v2 list/modal modules.
 */
(function() {
    'use strict';

    console.log('🛠️ I18nHelpersV2 loading...');

    if (!window.AdminPageBridge) {
        console.error('❌ AdminPageBridge not found for I18nHelpersV2');
        return;
    }

    const Bridge = window.AdminPageBridge;

    function withTableContainerTarget(containerId, run) {
        const container = document.getElementById(containerId);
        if (!container) return Promise.resolve();

        const original = document.getElementById('table-container');
        let tempId = null;

        if (original && original !== container) {
            tempId = 'table-container-original-' + Date.now();
            original.id = tempId;
        }

        const originalContainerId = container.id;
        container.id = 'table-container';

        const finish = function() {
            container.id = originalContainerId;
            if (tempId && original) original.id = 'table-container';
        };

        return Promise.resolve(run()).finally(finish);
    }

    function createResetPageReload(config) {
        const cfg = config || {};
        const resetPage = cfg.resetPage ?? 1;
        const setPage = typeof cfg.setPage === 'function' ? cfg.setPage : function() {};
        const reload = typeof cfg.reload === 'function' ? cfg.reload : function() {};

        return function(event) {
            if (event && cfg.preventDefault && typeof event.preventDefault === 'function') {
                event.preventDefault();
            }
            setPage(resetPage, event);
            return reload(event);
        };
    }

    function bindTableActionState(config) {
        const cfg = config || {};
        const target = cfg.target || document;
        const eventName = cfg.eventName || 'tableAction';
        const buildParams = typeof cfg.buildParams === 'function' ? cfg.buildParams : function() { return {}; };
        const getState = typeof cfg.getState === 'function' ? cfg.getState : function() { return {}; };
        const setState = typeof cfg.setState === 'function' ? cfg.setState : function() {};
        const reload = typeof cfg.reload === 'function' ? cfg.reload : function() {};

        const handler = function(event) {
            const detail = (event && event.detail) || {};
            const next = Bridge.Table.applyActionParams(buildParams(), { action: detail.action, value: detail.value });
            const state = getState() || {};

            setState({
                page: next.page ?? state.page,
                perPage: next.per_page ?? state.perPage
            }, detail, next, event);

            return reload(detail, next, event);
        };

        target.addEventListener(eventName, handler);
        return handler;
    }

    function wireModalDismiss(modalOrSelector, options) {
        const opts = options || {};
        const modal = Bridge.DOM.el(modalOrSelector, false) || modalOrSelector;
        if (!modal || !modal.querySelectorAll) return;

        modal.querySelectorAll('.close-modal').forEach(function(btn) {
            btn.addEventListener('click', function() {
                Bridge.Modal.close(modal);
                if (opts.removeOnClose) modal.remove();
            });
        });

        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                Bridge.Modal.close(modal);
                if (opts.removeOnClose) modal.remove();
            }
        });
    }

    window.I18nHelpersV2 = {
        withTableContainerTarget,
        createResetPageReload,
        bindTableActionState,
        wireModalDismiss
    };

    console.log('✅ I18nHelpersV2 loaded');
})();
