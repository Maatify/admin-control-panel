/**
 * 🛠️ Image Profiles Modals V2
 */

(function() {
    'use strict';

    if (!window.AdminPageBridge || !window.ImageProfilesHelpersV2) {
        console.error('❌ Missing dependencies for image-profiles-modals-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.ImageProfilesHelpersV2;
    const reloadProfilesTable = function() {
        if (typeof window.reloadImageProfilesTableV2 === 'function') {
            return window.reloadImageProfilesTableV2();
        }
    };

    Helpers.setupModalCloseHandlers();

    function nullableString(value) {
        if (value === null || value === undefined) return null;
        const normalized = String(value).trim();
        return normalized === '' ? null : normalized;
    }

    function nullableInt(value) {
        if (value === null || value === undefined || value === '') return null;
        const parsed = parseInt(value, 10);
        return Number.isNaN(parsed) ? null : parsed;
    }

    function collectProfilePayload() {
        const payload = Bridge.Form.collect({
            id: { selector: '#profile-id', type: 'int' },
            code: '#profile-code',
            display_name: '#profile-display-name',
            min_width: '#profile-min-width',
            min_height: '#profile-min-height',
            max_width: '#profile-max-width',
            max_height: '#profile-max-height',
            max_size_bytes: '#profile-max-size-bytes',
            allowed_extensions: '#profile-allowed-extensions',
            allowed_mime_types: '#profile-allowed-mime-types',
            is_active: { selector: '#profile-active', type: 'checked' },
            notes: '#profile-notes',
            min_aspect_ratio: '#profile-min-aspect-ratio',
            max_aspect_ratio: '#profile-max-aspect-ratio',
            requires_transparency: { selector: '#profile-requires-transparency', type: 'checked' },
            preferred_format: '#profile-preferred-format',
            preferred_quality: '#profile-preferred-quality',
            variants: '#profile-variants'
        }, { includeEmpty: true });

        payload.code = (payload.code || '').trim();
        payload.display_name = nullableString(payload.display_name);
        payload.min_width = nullableInt(payload.min_width);
        payload.min_height = nullableInt(payload.min_height);
        payload.max_width = nullableInt(payload.max_width);
        payload.max_height = nullableInt(payload.max_height);
        payload.max_size_bytes = nullableInt(payload.max_size_bytes);
        payload.allowed_extensions = nullableString(payload.allowed_extensions);
        payload.allowed_mime_types = nullableString(payload.allowed_mime_types);
        payload.notes = nullableString(payload.notes);
        payload.min_aspect_ratio = nullableString(payload.min_aspect_ratio);
        payload.max_aspect_ratio = nullableString(payload.max_aspect_ratio);
        payload.preferred_format = nullableString(payload.preferred_format);
        payload.preferred_quality = nullableInt(payload.preferred_quality);
        payload.variants = nullableString(payload.variants);

        return payload;
    }

    function bindSaveAction(handler) {
        const saveBtn = document.getElementById('btn-save-image-profile');
        if (!saveBtn || !saveBtn.parentNode) return;

        const newBtn = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newBtn, saveBtn);
        newBtn.addEventListener('click', handler);
    }

    function applyToForm(data) {
        Bridge.Form.fill({
            '#profile-id': data.id ?? '',
            '#profile-code': data.code ?? '',
            '#profile-display-name': data.display_name ?? '',
            '#profile-min-width': data.min_width ?? '',
            '#profile-min-height': data.min_height ?? '',
            '#profile-max-width': data.max_width ?? '',
            '#profile-max-height': data.max_height ?? '',
            '#profile-max-size-bytes': data.max_size_bytes ?? '',
            '#profile-allowed-extensions': data.allowed_extensions ?? '',
            '#profile-allowed-mime-types': data.allowed_mime_types ?? '',
            '#profile-notes': data.notes ?? '',
            '#profile-min-aspect-ratio': data.min_aspect_ratio ?? '',
            '#profile-max-aspect-ratio': data.max_aspect_ratio ?? '',
            '#profile-preferred-format': data.preferred_format ?? '',
            '#profile-preferred-quality': data.preferred_quality ?? '',
            '#profile-variants': data.variants ?? ''
        });

        const active = document.getElementById('profile-active');
        if (active) active.checked = !!data.is_active;

        const transparency = document.getElementById('profile-requires-transparency');
        if (transparency) transparency.checked = !!data.requires_transparency;
    }

    function openCreateModal() {
        Helpers.clearFormInputs('image-profile-form');

        const titleEl = document.getElementById('image-profile-modal-title');
        const idEl = document.getElementById('profile-id');
        const activeEl = document.getElementById('profile-active');
        const transparencyEl = document.getElementById('profile-requires-transparency');

        if (titleEl) titleEl.textContent = 'Create Image Profile';
        if (idEl) idEl.value = '';
        if (activeEl) activeEl.checked = true;
        if (transparencyEl) transparencyEl.checked = false;

        bindSaveAction(async function() {
            const payload = collectProfilePayload();
            delete payload.id;

            await Bridge.API.runMutation({
                operation: 'Create Image Profile',
                endpoint: 'image-profiles/create',
                method: 'POST',
                payload,
                successMessage: 'Image profile created successfully.',
                modal: '#image-profile-modal',
                modalOptions: { resetForm: true },
                reloadHandler: reloadProfilesTable
            });
        });

        Bridge.Modal.open('#image-profile-modal');
    }

    async function openEditModal(profileId) {
        const id = Bridge.normalizeInt(profileId, 0);
        if (!id) return;

        const details = await Bridge.API.execute({
            operation: 'Get Image Profile Details',
            endpoint: 'image-profiles/details',
            method: 'POST',
            payload: { id }
        });

        if (!details.success) {
            Bridge.UI.error(details.error || 'Failed to load image profile details.');
            return;
        }

        const titleEl = document.getElementById('image-profile-modal-title');
        if (titleEl) titleEl.textContent = 'Edit Image Profile';

        applyToForm(details.data || {});

        bindSaveAction(async function() {
            const payload = collectProfilePayload();
            payload.id = id;

            await Bridge.API.runMutation({
                operation: 'Update Image Profile',
                endpoint: 'image-profiles/update',
                method: 'POST',
                payload,
                successMessage: 'Image profile updated successfully.',
                modal: '#image-profile-modal',
                modalOptions: { resetForm: true },
                reloadHandler: reloadProfilesTable
            });
        });

        Bridge.Modal.open('#image-profile-modal');
    }

    Helpers.setupButtonHandler('.edit-profile-btn', function(id) {
        return openEditModal(id);
    });

    window.ImageProfilesModalsV2 = {
        openCreateModal,
        openEditModal
    };

    window.openCreateImageProfileModalV2 = openCreateModal;
})();
