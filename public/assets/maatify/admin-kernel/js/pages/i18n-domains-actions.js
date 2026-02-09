/**
 * 🌐 I18n Domains Management - Actions Module
 * ===========================================
 * Features:
 * - Toggle Active Status
 * - Button event delegation
 */

(function() {
    'use strict';

    console.log('🎯 I18n Domains Actions Module Loading...');

    if (typeof ApiHandler === 'undefined') {
        console.error('❌ ApiHandler not found!');
        return;
    }

    console.log('✅ ApiHandler loaded');

    const capabilities = window.i18nDomainsCapabilities || {};

    function setupButtonHandler(selector, handler) {
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest(selector);
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();

            const domainId = btn.getAttribute('data-domain-id');
            if (!domainId) {
                console.error(`❌ No domain ID found on button:`, btn);
                return;
            }

            console.log(`🎯 ${selector} clicked for domain:`, domainId);

            try {
                await handler(domainId, btn);
            } catch (error) {
                console.error(`❌ Error in ${selector} handler:`, error);
                ApiHandler.showAlert('danger', 'An error occurred: ' + error.message);
            }
        });
    }

    async function toggleActiveStatus(domainId, button) {
        let currentStatus = null;

        const statusAttr = button.getAttribute('data-current-status');
        if (statusAttr !== null) {
            currentStatus = statusAttr === '1' || statusAttr === 'true';
        } else {
            const buttonText = button.textContent.toLowerCase();
            if (buttonText.includes('active')) currentStatus = true;
            else if (buttonText.includes('inactive')) currentStatus = false;
        }

        if (currentStatus === null) {
            const result = await ApiHandler.call('i18n/domains/query', {
                page: 1,
                per_page: 1,
                search: { columns: { id: domainId } }
            }, 'Fetch Domain Status');

            if (result.success && result.data.data && result.data.data.length > 0) {
                currentStatus = parseInt(result.data.data[0].is_active) === 1;
            } else {
                ApiHandler.showAlert('danger', 'Failed to determine current status');
                return;
            }
        }

        const newStatus = !currentStatus;
        const action = newStatus ? 'activate' : 'deactivate';

        if (!confirm(`Are you sure you want to ${action} this domain?`)) return;

        const payload = {
            id: parseInt(domainId),
            is_active: newStatus
        };

        const result = await ApiHandler.call('i18n/domains/set-active', payload, 'Toggle Active');

        if (result.success) {
            ApiHandler.showAlert('success', `✅ Domain ${newStatus ? 'activated' : 'deactivated'} successfully`);
            window.reloadDomainsTable?.();
        }
    }

    function setupAllActionHandlers() {
        if (capabilities.can_set_active) {
            setupButtonHandler('.toggle-active-btn', toggleActiveStatus);
        }

        if (capabilities.can_change_code) {
            setupButtonHandler('.change-code-btn', async (domainId) => {
                if (typeof window.DomainsModals !== 'undefined' && window.DomainsModals.openChangeCodeModal) {
                    await window.DomainsModals.openChangeCodeModal(domainId);
                } else {
                    ApiHandler.showAlert('danger', 'Modal system not loaded');
                }
            });
        }

        if (capabilities.can_update_meta) {
            setupButtonHandler('.update-metadata-btn', async (domainId) => {
                if (typeof window.DomainsModals !== 'undefined' && window.DomainsModals.openUpdateMetadataModal) {
                    await window.DomainsModals.openUpdateMetadataModal(domainId);
                } else {
                    ApiHandler.showAlert('danger', 'Modal system not loaded');
                }
            });
        }

        if (capabilities.can_update_sort) {
            setupButtonHandler('.update-sort-btn', async (domainId) => {
                if (typeof window.DomainsModals !== 'undefined' && window.DomainsModals.openUpdateSortModal) {
                    await window.DomainsModals.openUpdateSortModal(domainId);
                } else {
                    ApiHandler.showAlert('danger', 'Modal system not loaded');
                }
            });
        }

        console.log('✅ All action handlers setup complete');
    }

    function initActionsModule() {
        setupAllActionHandlers();

        window.DomainsActions = {
            toggleActiveStatus
        };

        console.log('✅ I18n Domains Actions Module initialized');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initActionsModule);
    } else {
        initActionsModule();
    }

})();
