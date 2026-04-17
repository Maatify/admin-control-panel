/**
 * 🛠️ Image Profiles Actions V2
 */

(function() {
    'use strict';

    if (!window.AdminPageBridge || !window.ImageProfilesHelpersV2) {
        console.error('❌ Missing dependencies for image-profiles-actions-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.ImageProfilesHelpersV2;
    const reloadProfilesTable = function() {
        if (typeof window.reloadImageProfilesTableV2 === 'function') {
            return window.reloadImageProfilesTableV2();
        }
    };

    async function toggleStatus(profileId) {
        const btn = document.querySelector('.toggle-status-btn[data-profile-id="' + profileId + '"]');
        if (!btn) return;

        const isActive = btn.getAttribute('data-current-is-active') === '1' || btn.getAttribute('data-current-is-active') === 'true';

        return Bridge.API.runMutation({
            operation: 'Toggle Image Profile Status',
            endpoint: 'image-profiles/set-active',
            method: 'POST',
            payload: {
                id: Bridge.normalizeInt(profileId, 0),
                is_active: !isActive
            },
            successMessage: 'Image profile status updated successfully.',
            reloadHandler: reloadProfilesTable
        });
    }

    Helpers.setupButtonHandler('.toggle-status-btn', function(id) {
        return toggleStatus(id);
    });

    const createBtn = document.getElementById('btn-create-image-profile');
    if (createBtn) {
        createBtn.addEventListener('click', function(event) {
            event.preventDefault();
            if (window.ImageProfilesModalsV2 && typeof window.ImageProfilesModalsV2.openCreateModal === 'function') {
                window.ImageProfilesModalsV2.openCreateModal();
            }
        });
    }

    window.ImageProfilesActionsV2 = {
        toggleStatus
    };
})();
