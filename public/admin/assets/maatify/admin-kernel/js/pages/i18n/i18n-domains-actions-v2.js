/**
 * 🌐 I18n Domains Actions V2
 */
(function() {
    'use strict';

    console.log('🎯 I18n Domains Actions V2 loading...');

    if (!window.AdminPageBridge || !window.I18nHelpersV2) {
        console.error('❌ Missing dependencies for i18n-domains-actions-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const capabilities = window.i18nDomainsCapabilities || {};

    const reloadDomainsTableV2 = function() {
        if (typeof window.reloadDomainsTableV2 === 'function') {
            return window.reloadDomainsTableV2();
        }
    };

    async function resolveCurrentStatus(domainId, btn) {
        const statusAttr = btn.getAttribute('data-current-status');
        if (statusAttr !== null) {
            return statusAttr === '1' || statusAttr === 'true';
        }

        const query = await Bridge.API.execute({
            endpoint: 'i18n/domains/query',
            payload: { page: 1, per_page: 1, search: { columns: { id: domainId } } },
            operation: 'Fetch Domain Status',
            showErrorMessage: false
        });

        if (!query.success || !query.data?.data?.length) {
            Bridge.UI.error(query.error || 'Failed to determine current status');
            return null;
        }

        return parseInt(query.data.data[0].is_active, 10) === 1;
    }

    async function toggleActiveStatus(domainId, btn) {
        const currentStatus = await resolveCurrentStatus(domainId, btn);
        if (currentStatus === null) return;

        const newStatus = !currentStatus;
        return Bridge.API.runMutation({
            operation: 'Toggle Domain Active',
            endpoint: 'i18n/domains/set-active',
            method: 'POST',
            payload: { id: Bridge.normalizeInt(domainId, 0), is_active: newStatus },
            confirmMessage: 'Are you sure you want to ' + (newStatus ? 'activate' : 'deactivate') + ' this domain?',
            successMessage: 'Domain ' + (newStatus ? 'activated' : 'deactivated') + ' successfully',
            reloadHandler: reloadDomainsTableV2
        });
    }

    function init() {
        if (capabilities.can_set_active) {
            Bridge.Events.onClick('.toggle-active-btn', function(event, btn) {
                const domainId = btn.getAttribute('data-domain-id');
                if (!domainId) return;
                toggleActiveStatus(domainId, btn);
            });
        }

        if (capabilities.can_change_code) {
            Bridge.Events.onClick('.change-code-btn', function(event, btn) {
                const domainId = btn.getAttribute('data-domain-id');
                if (!domainId) return;
                if (window.DomainsModalsV2?.openChangeCodeModal) {
                    window.DomainsModalsV2.openChangeCodeModal(domainId);
                } else {
                    Bridge.UI.error('Modal system not loaded');
                }
            });
        }

        if (capabilities.can_update_meta) {
            Bridge.Events.onClick('.update-metadata-btn', function(event, btn) {
                const domainId = btn.getAttribute('data-domain-id');
                if (!domainId) return;
                if (window.DomainsModalsV2?.openUpdateMetadataModal) {
                    window.DomainsModalsV2.openUpdateMetadataModal(domainId);
                } else {
                    Bridge.UI.error('Modal system not loaded');
                }
            });
        }

        if (capabilities.can_update_sort) {
            Bridge.Events.onClick('.update-sort-btn', function(event, btn) {
                const domainId = btn.getAttribute('data-domain-id');
                if (!domainId) return;
                if (window.DomainsModalsV2?.openUpdateSortModal) {
                    window.DomainsModalsV2.openUpdateSortModal(domainId);
                } else {
                    Bridge.UI.error('Modal system not loaded');
                }
            });
        }

        window.DomainsActionsV2 = {
            toggleActiveStatus: toggleActiveStatus
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
