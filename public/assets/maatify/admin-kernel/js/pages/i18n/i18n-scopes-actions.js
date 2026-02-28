/**
 * 🌐 I18n Scopes Management - Actions Module
 * ===========================================
 * Features:
 * - Toggle Active Status
 * - Button event delegation
 */

(function() {
    'use strict';

    console.log('🎯 I18n Scopes Actions Module Loading...');

    // ========================================================================
    // PREREQUISITES CHECK
    // ========================================================================

    if (typeof ApiHandler === 'undefined') {
        console.error('❌ ApiHandler not found!');
        return;
    }

    console.log('✅ ApiHandler loaded');

    const capabilities = window.i18nScopesCapabilities || {};

    // ========================================================================
    // HELPER FUNCTION - Setup Button Handler
    // ========================================================================

    /**
     * Setup event delegation for buttons with a specific class
     * @param {string} selector - CSS selector for buttons
     * @param {function} handler - Async handler function(id, button)
     */
    function setupButtonHandler(selector, handler) {
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest(selector);
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();

            const scopeId = btn.getAttribute('data-scope-id');
            if (!scopeId) {
                console.error(`❌ No scope ID found on button:`, btn);
                return;
            }

            console.log(`🎯 ${selector} clicked for scope:`, scopeId);

            try {
                await handler(scopeId, btn);
            } catch (error) {
                console.error(`❌ Error in ${selector} handler:`, error);
                ApiHandler.showAlert('danger', 'An error occurred: ' + error.message);
            }
        });
    }

    // ========================================================================
    // TOGGLE ACTIVE STATUS
    // ========================================================================

    async function toggleActiveStatus(scopeId, button) {
        // Determine current status from button or table data
        let currentStatus = null;
        
        // Try to get from button data attribute
        const statusAttr = button.getAttribute('data-current-status');
        if (statusAttr !== null) {
            currentStatus = statusAttr === '1' || statusAttr === 'true';
        } else {
            // Try to infer from button text/class
            const buttonText = button.textContent.toLowerCase();
            if (buttonText.includes('active')) {
                currentStatus = true;
            } else if (buttonText.includes('inactive')) {
                currentStatus = false;
            }
        }

        console.log('🔄 Toggle active status:', { scopeId, currentStatus });

        // If we still can't determine status, fetch it from API
        if (currentStatus === null) {
            console.log('⚠️ Could not determine current status, fetching from API...');
            const result = await ApiHandler.call('i18n/scopes/query', {
                page: 1,
                per_page: 1,
                search: { columns: { id: scopeId } }
            }, 'Fetch Scope Status');

            if (result.success && result.data.data && result.data.data.length > 0) {
                currentStatus = parseInt(result.data.data[0].is_active) === 1;
            } else {
                ApiHandler.showAlert('danger', 'Failed to determine current status');
                return;
            }
        }

        const newStatus = !currentStatus;
        const action = newStatus ? 'activate' : 'deactivate';

        if (!confirm(`Are you sure you want to ${action} this scope?`)) {
            console.log('❌ User cancelled action');
            return;
        }

        const payload = {
            id: parseInt(scopeId),
            is_active: newStatus
        };

        console.log('📦 Payload:', payload);

        const result = await ApiHandler.call('i18n/scopes/set-active', payload, 'Toggle Active');

        if (result.success) {
            ApiHandler.showAlert('success', `✅ Scope ${newStatus ? 'activated' : 'deactivated'} successfully`);
            
            // Reload table
            if (typeof window.reloadScopesTable === 'function') {
                window.reloadScopesTable();
            }
        }
    }

    // ========================================================================
    // SETUP ALL ACTION HANDLERS
    // ========================================================================

    function setupAllActionHandlers() {
        console.log('🎯 Setting up action handlers...');

        // Toggle Active Status
        if (capabilities.can_set_active) {
            setupButtonHandler('.toggle-active-btn', toggleActiveStatus);
            console.log('✅ Toggle active handler registered');
        }

        // Change Code - opens modal
        if (capabilities.can_change_code) {
            setupButtonHandler('.change-code-btn', async (scopeId) => {
                if (typeof window.ScopesModals !== 'undefined' && window.ScopesModals.openChangeCodeModal) {
                    await window.ScopesModals.openChangeCodeModal(scopeId);
                } else {
                    console.error('❌ ScopesModals.openChangeCodeModal not found');
                    ApiHandler.showAlert('danger', 'Modal system not loaded');
                }
            });
            console.log('✅ Change code handler registered');
        }

        // Update Metadata - opens modal
        if (capabilities.can_update_meta) {
            setupButtonHandler('.update-metadata-btn', async (scopeId) => {
                if (typeof window.ScopesModals !== 'undefined' && window.ScopesModals.openUpdateMetadataModal) {
                    await window.ScopesModals.openUpdateMetadataModal(scopeId);
                } else {
                    console.error('❌ ScopesModals.openUpdateMetadataModal not found');
                    ApiHandler.showAlert('danger', 'Modal system not loaded');
                }
            });
            console.log('✅ Update metadata handler registered');
        }

        // Update Sort - opens modal
        if (capabilities.can_update_sort) {
            setupButtonHandler('.update-sort-btn', async (scopeId) => {
                if (typeof window.ScopesModals !== 'undefined' && window.ScopesModals.openUpdateSortModal) {
                    await window.ScopesModals.openUpdateSortModal(scopeId);
                } else {
                    console.error('❌ ScopesModals.openUpdateSortModal not found');
                    ApiHandler.showAlert('danger', 'Modal system not loaded');
                }
            });
            console.log('✅ Update sort handler registered');
        }

        console.log('✅ All action handlers setup complete');
    }

    // ========================================================================
    // INITIALIZATION
    // ========================================================================

    function initActionsModule() {
        console.log('🎬 Initializing I18n Scopes Actions Module...');

        setupAllActionHandlers();

        // Export functions for external use
        window.ScopesActions = {
            toggleActiveStatus
        };

        console.log('✅ I18n Scopes Actions Module initialized');
    }

    // Run initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initActionsModule);
    } else {
        initActionsModule();
    }

})();
