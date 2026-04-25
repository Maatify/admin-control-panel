/**
 * 🛠️ Categories Modals V2
 */

(function() {
    'use strict';


    if (!window.AdminPageBridge || !window.CategoriesHelpersV2) {
        console.error('❌ Missing dependencies for categories-modals-v2');
        return;
    }

    const Bridge   = window.AdminPageBridge;
    const Helpers  = window.CategoriesHelpersV2;
    const reload   = function() {
        if (typeof window.reloadCategoriesTableV2 === 'function') {
            return window.reloadCategoriesTableV2();
        }
    };

    Helpers.setupModalCloseHandlers();

    // ── Form collector ─────────────────────────────────────────────────────

    function collectPayload() {
        return Bridge.Form.collect({
            id:            { selector: '#category-id', type: 'int' },
            name:          '#category-name',
            slug:          '#category-slug',
            description:   '#category-description',
            parent_id:     { selector: '#category-parent-id', type: 'int' },
            is_active:     { selector: '#category-active', type: 'checked' },
            display_order: { selector: '#category-sort', type: 'int', default: 0 },
            notes:         '#category-notes'
        }, { includeEmpty: true });
    }

    function bindSaveAction(handler) {
        const saveBtn = document.getElementById('btn-save-category');
        if (!saveBtn || !saveBtn.parentNode) return;
        const newBtn = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newBtn, saveBtn);
        newBtn.addEventListener('click', handler);
    }

    // ── Load parent categories dropdown ──────────────────────────────────
    async function loadParentDropdown(excludeId) {
        const select = document.getElementById('category-parent-id');
        if (!select) return;
        select.innerHTML = '<option value="">— None (Top Level) —</option>';

        const result = await Bridge.API.execute({
            endpoint: 'categories/dropdown',
            payload: {},
            operation: 'Load Parent Categories',
            method: 'POST',
            showErrorMessage: false
        });

        if (result.success) {
            const items = Array.isArray(result.data?.data) ? result.data.data : (Array.isArray(result.data) ? result.data : []);
            items.forEach(function(cat) {
                if (excludeId && cat.id === excludeId) return;
                const opt = document.createElement('option');
                opt.value = cat.id;
                opt.textContent = cat.name;
                select.appendChild(opt);
            });
        }
    }

    // ── Auto-generate slug from name ─────────────────────────────────────
    (function() {
        const nameEl = document.getElementById('category-name');
        const slugEl = document.getElementById('category-slug');
        if (nameEl && slugEl) {
            let autoSlug = true;
            slugEl.addEventListener('input', function() { autoSlug = false; });
            nameEl.addEventListener('input', function() {
                if (!autoSlug) return;
                slugEl.value = nameEl.value
                    .toLowerCase()
                    .trim()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_]+/g, '-')
                    .replace(/-+/g, '-');
            });
            window._resetCategoryAutoSlug = function() { autoSlug = true; };
        }
    })();

    // ── Image section toggle ─────────────────────────────────────────────
    (function() {
        const checkbox = document.getElementById('category-include-image');
        const fields   = document.getElementById('category-image-fields');
        if (checkbox && fields) {
            checkbox.addEventListener('change', function() {
                fields.classList.toggle('hidden', !checkbox.checked);
            });
        }
    })();

    function resetImageSection(show) {
        const section  = document.getElementById('category-image-section');
        const checkbox = document.getElementById('category-include-image');
        const fields   = document.getElementById('category-image-fields');
        const typeEl   = document.getElementById('category-image-type');
        const langEl   = document.getElementById('category-image-language');
        const pathEl   = document.getElementById('category-image-path');

        if (checkbox) checkbox.checked = false;
        if (fields) fields.classList.add('hidden');
        if (typeEl) typeEl.value = 'image';
        if (langEl) langEl.selectedIndex = 0;
        if (pathEl) pathEl.value = '';
        if (section) section.style.display = show ? '' : 'none';
    }

    function getImagePayload() {
        const checkbox = document.getElementById('category-include-image');
        if (!checkbox || !checkbox.checked) return null;

        const imageType  = (document.getElementById('category-image-type') || {}).value;
        const languageId = parseInt((document.getElementById('category-image-language') || {}).value, 10);
        const path       = (document.getElementById('category-image-path') || {}).value.trim();

        if (!imageType || !languageId || !path) return null;
        return { image_type: imageType, language_id: languageId, path: path };
    }

    // ── Create ─────────────────────────────────────────────────────────────

    function openCreateCategoryModal() {
        Helpers.clearFormInputs('category-form');

        const idEl       = document.getElementById('category-id');
        const activeEl   = document.getElementById('category-active');
        const sortEl     = document.getElementById('category-sort');
        const slugEl     = document.getElementById('category-slug');
        const notesEl    = document.getElementById('category-notes');
        const descEl     = document.getElementById('category-description');
        const titleEl    = document.getElementById('category-modal-title');

        if (idEl)     idEl.value          = '';
        if (activeEl) activeEl.checked    = true;
        if (sortEl)   sortEl.value        = '0';
        if (slugEl)   slugEl.value        = '';
        if (notesEl)  notesEl.value       = '';
        if (descEl)   descEl.value        = '';
        if (titleEl)  titleEl.textContent = 'Create New Category';

        if (window._resetCategoryAutoSlug) window._resetCategoryAutoSlug();
        loadParentDropdown(null);
        resetImageSection(true);

        bindSaveAction(async function() {
            const payload = Bridge.Form.omitEmpty(collectPayload());
            delete payload.id;

            const imageData = getImagePayload();

            // Uses Bridge.API.execute instead of runMutation because we need
            // the response data (new category ID) to chain an optional image
            // upsert immediately after creation — runMutation closes the modal
            // before we can act on the result.
            const result = await Bridge.API.execute({
                operation: 'Create Category',
                endpoint: 'categories/create',
                method: 'POST',
                payload: payload,
                showErrorMessage: true
            });

            if (!result.success) return;

            // If image was provided, upsert it on the newly created category
            if (imageData && result.data) {
                const newId = result.data.id || (result.data.data && result.data.data.id);
                if (newId) {
                    await Bridge.API.execute({
                        operation: 'Attach Image',
                        endpoint: 'categories/' + newId + '/images/upsert',
                        method: 'POST',
                        payload: imageData,
                        showErrorMessage: true
                    });
                }
            }

            Bridge.UI.success('Category created successfully.');
            Bridge.Modal.close('#category-modal', { resetForm: true });
            reload();
        });

        Bridge.Modal.open('#category-modal');
    }

    // ── Edit ───────────────────────────────────────────────────────────────

    function openEditCategoryModal(id, btn) {
        const titleEl   = document.getElementById('category-modal-title');
        const idEl      = document.getElementById('category-id');
        const nameEl    = document.getElementById('category-name');
        const slugEl    = document.getElementById('category-slug');
        const sortEl    = document.getElementById('category-sort');
        const activeEl  = document.getElementById('category-active');
        const notesEl   = document.getElementById('category-notes');
        const descEl    = document.getElementById('category-description');
        const parentEl  = document.getElementById('category-parent-id');

        if (titleEl)  titleEl.textContent    = 'Edit Category';
        if (idEl)     idEl.value             = id;
        if (nameEl)   nameEl.value           = btn.getAttribute('data-current-name')          || '';
        if (slugEl)   slugEl.value           = btn.getAttribute('data-current-slug')          || '';
        if (sortEl)   sortEl.value           = btn.getAttribute('data-current-display-order') || '0';
        if (activeEl) activeEl.checked       = btn.getAttribute('data-current-is-active') === '1';
        if (notesEl)  notesEl.value          = btn.getAttribute('data-current-notes')         || '';
        if (descEl)   descEl.value           = btn.getAttribute('data-current-description')   || '';

        const parentId = btn.getAttribute('data-current-parent-id') || '';
        loadParentDropdown(parseInt(id)).then(function() {
            if (parentEl && parentId) parentEl.value = parentId;
        });

        resetImageSection(false); // hide image section for edit — use detail page

        bindSaveAction(async function() {
            const payload = collectPayload();

            await Bridge.API.runMutation({
                operation: 'Update Category',
                endpoint: 'categories/update',
                method: 'POST',
                payload,
                successMessage: 'Category updated successfully.',
                modal: '#category-modal',
                modalOptions: { resetForm: true },
                reloadHandler: reload
            });
        });

        Bridge.Modal.open('#category-modal');
    }

    // ── Sort modal ─────────────────────────────────────────────────────────

    function openSortModal(id, btn) {
        const idEl       = document.getElementById('sort-category-id');
        const valueEl    = document.getElementById('sort-new-value');
        const parentIdEl = document.getElementById('sort-parent-id');
        if (idEl)       idEl.value       = id;
        if (valueEl)    valueEl.value    = btn.getAttribute('data-current-sort') || '';
        if (parentIdEl) parentIdEl.value = btn.getAttribute('data-parent-id') || '';
        Bridge.Modal.open('#sort-modal');
    }

    const sortSaveBtn = document.getElementById('btn-save-sort');
    if (sortSaveBtn) {
        sortSaveBtn.addEventListener('click', async function() {
            const raw = Bridge.Form.collect({
                id:            { selector: '#sort-category-id', type: 'int' },
                display_order: { selector: '#sort-new-value',   type: 'int' },
                parent_id:     { selector: '#sort-parent-id',   type: 'int' }
            });

            // Only include parent_id when it was actually set (non-zero)
            const payload = { id: raw.id, display_order: raw.display_order };
            if (raw.parent_id) payload.parent_id = raw.parent_id;

            await Bridge.API.runMutation({
                operation: 'Update Sort Order',
                endpoint: 'categories/update-sort',
                method: 'POST',
                payload,
                successMessage: 'Sort order updated successfully.',
                modal: '#sort-modal',
                reloadHandler: reload
            });
        });
    }

    // ── Button bindings ────────────────────────────────────────────────────

    Helpers.setupButtonHandler('.edit-category-btn', function(id, btn) {
        openEditCategoryModal(id, btn);
    });

    Helpers.setupButtonHandler('.update-sort-btn', function(id, btn) {
        openSortModal(id, btn);
    });

    // ── Public API ─────────────────────────────────────────────────────────

    window.CategoriesModalsV2 = {
        openCreateCategoryModal,
        openEditCategoryModal,
        openSortModal
    };

    window.openCreateCategoryModalV2 = openCreateCategoryModal;
})();
