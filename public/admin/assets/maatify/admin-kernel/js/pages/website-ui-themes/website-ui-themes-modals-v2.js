/**
 * 🛠️ Website UI Themes Modals V2
 */

(function() {
    'use strict';

    if (!window.AdminPageBridge || !window.WebsiteUiThemesHelpersV2) {
        console.error('❌ Missing dependencies for website-ui-themes-modals-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.WebsiteUiThemesHelpersV2;
    const reloadThemesTable = function() {
        if (typeof window.reloadWebsiteUiThemesTableV2 === 'function') {
            return window.reloadWebsiteUiThemesTableV2();
        }
    };

    Helpers.setupModalCloseHandlers();

    function nullableString(value) {
        if (value === null || value === undefined) return null;
        const normalized = String(value).trim();
        return normalized === '' ? null : normalized;
    }

    function collectThemePayload() {
        const payload = Bridge.Form.collect({
            id: { selector: '#theme-id', type: 'int' },
            entity_type: '#theme-entity-type',
            theme_file: '#theme-theme-file',
            display_name: '#theme-display-name'
        }, { includeEmpty: true });

        payload.entity_type = nullableString(payload.entity_type) || '';
        payload.theme_file = nullableString(payload.theme_file) || '';
        payload.display_name = nullableString(payload.display_name) || '';

        return payload;
    }

    function bindSaveAction(handler) {
        const saveBtn = document.getElementById('btn-save-website-ui-theme');
        if (!saveBtn || !saveBtn.parentNode) return;

        const newBtn = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newBtn, saveBtn);
        newBtn.addEventListener('click', handler);
    }

    function setField(id, value) {
        const el = document.getElementById(id);
        if (el) el.value = (value === null || value === undefined) ? '' : value;
    }

    function applyToForm(data) {
        setField('theme-id', data.id);
        setField('theme-entity-type', data.entity_type);
        setField('theme-theme-file', data.theme_file);
        setField('theme-display-name', data.display_name);
    }

    function openCreateModal() {
        Helpers.clearFormInputs('website-ui-theme-form');

        const titleEl = document.getElementById('website-ui-theme-modal-title');
        const idEl = document.getElementById('theme-id');

        if (titleEl) titleEl.textContent = 'Create Website UI Theme';
        if (idEl) idEl.value = '';

        bindSaveAction(async function() {
            const payload = collectThemePayload();
            delete payload.id;

            await Bridge.API.runMutation({
                operation: 'Create Website UI Theme',
                endpoint: 'website-ui-themes/create',
                method: 'POST',
                payload,
                successMessage: 'Website UI theme created successfully.',
                modal: '#website-ui-theme-modal',
                modalOptions: { resetForm: true },
                reloadHandler: reloadThemesTable
            });
        });

        Bridge.Modal.open('#website-ui-theme-modal');
    }

    async function openEditModal(themeId) {
        const id = Bridge.normalizeInt(themeId, 0);
        if (!id) return;

        const details = await Bridge.API.execute({
            operation: 'Get Website UI Theme Details',
            endpoint: 'website-ui-themes/details',
            method: 'POST',
            payload: { id }
        });

        if (!details.success) {
            Bridge.UI.error(details.error || 'Failed to load website UI theme details.');
            return;
        }

        const titleEl = document.getElementById('website-ui-theme-modal-title');
        if (titleEl) titleEl.textContent = 'Edit Website UI Theme';

        applyToForm(details.data || {});

        bindSaveAction(async function() {
            const payload = collectThemePayload();
            payload.id = id;

            await Bridge.API.runMutation({
                operation: 'Update Website UI Theme',
                endpoint: 'website-ui-themes/update',
                method: 'POST',
                payload,
                successMessage: 'Website UI theme updated successfully.',
                modal: '#website-ui-theme-modal',
                modalOptions: { resetForm: true },
                reloadHandler: reloadThemesTable
            });
        });

        Bridge.Modal.open('#website-ui-theme-modal');
    }

    Helpers.setupButtonHandler('.edit-theme-btn', function(id) {
        return openEditModal(id);
    });

    window.WebsiteUiThemesModalsV2 = {
        openCreateModal,
        openEditModal
    };
})();
