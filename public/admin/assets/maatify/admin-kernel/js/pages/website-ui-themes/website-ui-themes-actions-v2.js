/**
 * 🛠️ Website UI Themes Actions V2
 */

(function() {
    'use strict';

    if (!window.AdminPageBridge || !window.WebsiteUiThemesHelpersV2) {
        console.error('❌ Missing dependencies for website-ui-themes-actions-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;

    const createBtn = document.getElementById('btn-create-website-ui-theme');
    if (createBtn) {
        createBtn.addEventListener('click', function(event) {
            event.preventDefault();
            if (window.WebsiteUiThemesModalsV2 && typeof window.WebsiteUiThemesModalsV2.openCreateModal === 'function') {
                window.WebsiteUiThemesModalsV2.openCreateModal();
            }
        });
    }

    async function deleteTheme(themeId) {
        return Bridge.API.runMutation({
            operation: 'Delete Website UI Theme',
            endpoint: 'website-ui-themes/delete',
            method: 'POST',
            payload: {
                id: Bridge.normalizeInt(themeId, 0)
            },
            confirmMessage: 'Are you sure you want to delete this website UI theme?',
            successMessage: 'Website UI theme deleted successfully.',
            reloadHandler: function() {
                if (typeof window.reloadWebsiteUiThemesTableV2 === 'function') {
                    return window.reloadWebsiteUiThemesTableV2();
                }
            }
        });
    }

    window.WebsiteUiThemesHelpersV2.setupButtonHandler('.delete-theme-btn', function(id) {
        return deleteTheme(id);
    });

    window.WebsiteUiThemesActionsV2 = {
        deleteTheme
    };
})();
