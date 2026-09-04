/**
 * 🛠️ Image Profiles Helpers V2
 */

(function() {
    'use strict';

    console.log('🛠️ Image Profiles Helpers V2 Loading...');

    if (!window.AdminPageBridge) {
        console.error('❌ AdminPageBridge not found');
        return;
    }

    const Bridge = window.AdminPageBridge;

    function setupButtonHandler(selector, callback, options) {
        const cfg = options || {};
        const dataAttribute = cfg.dataAttribute || 'data-profile-id';

        return Bridge.Events.onClick(selector, async function(event, btn) {
            if (cfg.preventDefault !== false) event.preventDefault();
            if (cfg.stopPropagation) event.stopPropagation();

            const profileId = btn.getAttribute(dataAttribute);
            if (cfg.requireData !== false && !profileId) {
                console.warn('[ImageProfilesHelpersV2] Missing data attribute', { selector, dataAttribute });
                return;
            }

            await callback(profileId, btn, event);
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
            if (event.key !== 'Escape') return;
            document.querySelectorAll('[id$="-modal"]').forEach(function(modal) {
                Bridge.Modal.close(modal);
            });
        });
    }

    function clearFormInputs(formId) {
        const form = document.getElementById(formId);
        if (form && typeof form.reset === 'function') form.reset();
    }

    function bindResetPageReload(config) {
        const cfg = config || {};
        return Bridge.Events.createResetReload({
            setPage: cfg.setPage,
            resetPage: cfg.resetPage || 1,
            preventDefault: !!cfg.preventDefault,
            reload: cfg.reload || function() {}
        });
    }

    function bindTableActionState(config) {
        const cfg = config || {};
        const getState = cfg.getState || function() { return {}; };
        const setState = cfg.setState || function() {};
        const reload = cfg.reload || function() {};
        let lastContext = null;

        return Bridge.Table.bindActionState({
            root: cfg.target || document,
            eventName: cfg.eventName || 'tableAction',
            sourceContainerId: cfg.sourceContainerId,
            getState: cfg.getParams || function() { return {}; },
            setState: function(next, detail, event) {
                const state = getState() || {};
                setState({ page: next.page ?? state.page, perPage: next.per_page ?? state.perPage }, detail, next, event);
                lastContext = { detail, next, event };
            },
            reload: function() {
                if (!lastContext) return reload({}, {}, null);
                return reload(lastContext.detail, lastContext.next, lastContext.event);
            }
        });
    }

    window.ImageProfilesHelpersV2 = {
        setupButtonHandler,
        setupModalCloseHandlers,
        clearFormInputs,
        bindResetPageReload,
        bindTableActionState
    };

    console.log('✅ ImageProfilesHelpersV2 loaded');
})();
