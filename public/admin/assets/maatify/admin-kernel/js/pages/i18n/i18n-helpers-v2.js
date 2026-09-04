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
        return Bridge.Table.withTargetContainer(containerId, function() {
            return Promise.resolve(typeof run === 'function' ? run() : undefined);
        });
    }

    function createResetPageReload(config) {
        const cfg = config || {};
        const reload = typeof cfg.reload === 'function' ? cfg.reload : function() {};

        return Bridge.Events.createResetReload({
            setPage: cfg.setPage,
            resetPage: cfg.resetPage ?? 1,
            preventDefault: !!cfg.preventDefault,
            reload: function() {
                return reload.apply(null, arguments);
            }
        });
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
        const unbind = Bridge.Table.bindActionState({
            root: cfg.target || document,
            eventName: cfg.eventName || 'tableAction',
            sourceContainerId: cfg.sourceContainerId,
            sourceFilter: cfg.sourceFilter,
            getState: getParams,
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

        return unbind;
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
        bindResetPageReload: createResetPageReload,
        bindTableActionState,
        wireModalDismiss
    };

    console.log('✅ I18nHelpersV2 loaded');
})();
