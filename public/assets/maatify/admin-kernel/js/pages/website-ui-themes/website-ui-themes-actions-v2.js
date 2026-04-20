/**
 * 🛠️ Website UI Themes Actions V2
 */

(function() {
    'use strict';

    if (!window.AdminPageBridge || !window.WebsiteUiThemesHelpersV2) {
        console.error('❌ Missing dependencies for website-ui-themes-actions-v2');
        return;
    }

    const createBtn = document.getElementById('btn-create-website-ui-theme');
    if (createBtn) {
        createBtn.addEventListener('click', function(event) {
            event.preventDefault();
            if (window.WebsiteUiThemesModalsV2 && typeof window.WebsiteUiThemesModalsV2.openCreateModal === 'function') {
                window.WebsiteUiThemesModalsV2.openCreateModal();
            }
        });
    }
})();
