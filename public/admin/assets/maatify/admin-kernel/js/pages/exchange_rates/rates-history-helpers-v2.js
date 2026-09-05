/**
 * 🛠️ Rate History Helpers V2
 */

(function() {
    'use strict';

    console.log('🛠️ Rates History Helpers V2 Loading...');

    if (!window.AdminPageBridge) {
        console.error('❌ AdminPageBridge not found');
        return;
    }

    const Bridge = window.AdminPageBridge;

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
        const getParams = typeof cfg.getParams === 'function' ? cfg.getParams : function() { return {}; };
        const getState = typeof cfg.getState === 'function' ? cfg.getState : function() { return {}; };
        const setState = typeof cfg.setState === 'function' ? cfg.setState : function() {};
        const reload = typeof cfg.reload === 'function' ? cfg.reload : function() {};
        let lastContext = null;

        return Bridge.Table.bindActionState({
            root: cfg.target || document,
            eventName: cfg.eventName || 'tableAction',
            sourceContainerId: cfg.sourceContainerId,
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
    }

    window.RatesHistoryHelpersV2 = {
        bindResetPageReload,
        bindTableActionState
    };

    console.log('✅ RatesHistoryHelpersV2 loaded');
})();
