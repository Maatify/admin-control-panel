/**
 * Content Document Helpers V2
 * Local helpers shared by content_document list pages.
 */
(function() {
    'use strict';

    console.log('📄 ContentDocumentHelpersV2 loading...');

    if (!window.AdminPageBridge) {
        console.error('❌ AdminPageBridge not found for ContentDocumentHelpersV2');
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
        const setPage = typeof cfg.setPage === 'function' ? cfg.setPage : function() {};
        const reload = typeof cfg.reload === 'function' ? cfg.reload : function() {};
        const resetPage = cfg.resetPage ?? 1;

        return function(event) {
            if (event && typeof event.preventDefault === 'function' && cfg.preventDefault) {
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

    function wireModalDismiss(modalEl) {
        if (!modalEl) return;

        modalEl.querySelectorAll('.close-modal').forEach(function(btn) {
            btn.addEventListener('click', function() {
                modalEl.remove();
            });
        });

        modalEl.addEventListener('click', function(event) {
            if (event.target === modalEl) modalEl.remove();
        });
    }

    window.ContentDocumentHelpersV2 = {
        withTableContainerTarget,
        createResetPageReload,
        bindTableActionState,
        wireModalDismiss
    };

    console.log('✅ ContentDocumentHelpersV2 loaded');
})();
