/**
 * 📦 Category Detail V2 — Sub-categories, Settings, Images
 */
(function () {
    'use strict';

    if (!window.AdminPageBridge) {
        console.error('❌ AdminPageBridge not found');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const ctx = window.categoryDetailContext || {};
    const caps = window.categoryDetailCapabilities || {};
    const categoryId = ctx.category_id;

    if (!categoryId) {
        console.error('❌ [CategoryDetailV2] categoryId is missing from window.categoryDetailContext. Aborting. Context was:', window.categoryDetailContext);
        return;
    }

    // ═══════════════════════════════════════════════════════════════════
    // TAB SWITCHING
    // ═══════════════════════════════════════════════════════════════════
    function initTabs() {
        const tabs = document.querySelectorAll('.tab-btn');
        const panels = document.querySelectorAll('.tab-panel');

        tabs.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const target = btn.getAttribute('data-tab');

                tabs.forEach(function (t) {
                    t.classList.remove('border-blue-600', 'text-blue-600', 'dark:text-blue-400', 'dark:border-blue-400');
                    t.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                });
                btn.classList.add('border-blue-600', 'text-blue-600', 'dark:text-blue-400', 'dark:border-blue-400');
                btn.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');

                panels.forEach(function (p) {
                    p.classList.add('hidden');
                });
                var targetPanel = document.getElementById('tab-' + target);
                if (targetPanel) targetPanel.classList.remove('hidden');

                // Lazy load on first tab switch
                if (target === 'sub-categories' && !subCategoriesLoaded) loadSubCategories();
                if (target === 'images' && !imagesLoaded) loadImages();
                if (target === 'translations' && !translationsLoaded) loadTranslations();
            });
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    // MODAL HELPERS
    // ═══════════════════════════════════════════════════════════════════
    function setupModalClose() {
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('close-modal') || e.target.closest('.close-modal')) {
                var modal = (e.target.closest('[id$="-modal"]'));
                if (modal) Bridge.Modal.close(modal, { resetForm: true });
            }
            if (e.target.id && e.target.id.endsWith('-modal')) {
                Bridge.Modal.close('#' + e.target.id, { resetForm: true });
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('[id$="-modal"]').forEach(function (m) {
                    Bridge.Modal.close(m, { resetForm: true });
                });
            }
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    // SUB-CATEGORIES
    // ═══════════════════════════════════════════════════════════════════
    var subCategoriesLoaded = false;
    var subPage = 1;
    var subPerPage = 20;

    function loadSubCategories(page, perPage) {
        if (page != null) subPage = page;
        if (perPage != null) subPerPage = perPage;
        subCategoriesLoaded = true;

        var search = (document.getElementById('sub-categories-search') || {}).value || '';
        var params = { page: subPage, per_page: subPerPage };
        if (search.trim()) params.search = { global: search.trim() };

        var containerId = 'sub-categories-table-container';

        Bridge.Table.withTargetContainer(containerId, function () {
            return createTable(
                'categories/' + categoryId + '/sub-categories/query',
                params,
                ['ID', 'Name', 'Order', 'Status'],
                ['id', 'name', 'display_order', 'is_active'],
                false, 'id', null,
                {
                    name: function (v) { return '<span class="font-medium text-gray-900 dark:text-gray-200">' + (v || 'N/A') + '</span>'; },
                    display_order: function (v) { return AdminUIComponents.renderSortBadge(v, { size: 'md', color: 'indigo' }); },
                    is_active: function (v) { return AdminUIComponents.renderStatusBadge(v, { activeText: 'Active', inactiveText: 'Inactive' }); }
                },
                null, null
            ).catch(function (err) {
                console.error('Sub-categories table error:', err);
                var c = document.getElementById(containerId);
                if (c) c.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">Failed to load sub-categories.</div>';
            });
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    // SETTINGS
    // ═══════════════════════════════════════════════════════════════════
    var settingsLoaded = false;
    var settingsPage = 1;

    function loadSettings(page) {
        if (page != null) settingsPage = page;
        settingsLoaded = true;

        var search = (document.getElementById('settings-search') || {}).value || '';
        var params = { page: settingsPage, per_page: 20 };
        if (search.trim()) params.search = { global: search.trim() };

        Bridge.API.execute({
            endpoint: 'categories/' + categoryId + '/settings/query',
            payload: params,
            method: 'POST',
            operation: 'Query Settings',
            showErrorMessage: false
        }).then(function (result) {
            if (!result.success) {
                var c = document.getElementById('settings-table-container');
                if (c) c.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">' + (result.error || 'Failed') + '</div>';
                return;
            }
            var data = result.data || {};
            var items = Array.isArray(data.data) ? data.data : [];
            var pagination = data.pagination || { page: settingsPage, per_page: 20, total: items.length };

            var oldContainer = document.getElementById('table-container');
            var settingsContainer = document.getElementById('settings-table-container');
            if (settingsContainer) settingsContainer.id = 'table-container';
            if (oldContainer && oldContainer !== settingsContainer) oldContainer.id = '_main-table-container';

            var actionHeaders = caps.can_upsert_settings || caps.can_delete_settings ? ['Actions'] : [];
            var actionRows = actionHeaders.length ? ['actions'] : [];

            var renderers = {
                key: function (v) { return '<code class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-sm text-gray-800 dark:text-gray-200">' + v + '</code>'; },
                value: function (v) { return '<span class="text-gray-900 dark:text-gray-200">' + (v || '') + '</span>'; },
            };

            if (actionHeaders.length) {
                renderers.actions = function (_, row) {
                    var btns = [];
                    if (caps.can_upsert_settings) {
                        btns.push('<button class="edit-setting-btn px-2 py-1 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded hover:bg-blue-200 transition-colors" data-key="' + row.key + '" data-value="' + (row.value || '').replace(/"/g, '&quot;') + '">Edit</button>');
                    }
                    if (caps.can_delete_settings) {
                        btns.push('<button class="delete-setting-btn px-2 py-1 text-xs bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 rounded hover:bg-red-200 transition-colors" data-key="' + row.key + '">Delete</button>');
                    }
                    return '<div class="flex gap-1">' + btns.join('') + '</div>';
                };
            }

            try {
                TableComponent(items,
                    ['Key', 'Value'].concat(actionHeaders),
                    ['key', 'value'].concat(actionRows),
                    pagination, '', false, 'key', null, renderers);
            } catch (e) { console.error(e); }

            var rendered = document.getElementById('table-container');
            if (rendered) rendered.id = 'settings-table-container';
            if (oldContainer && oldContainer.id === '_main-table-container') oldContainer.id = 'table-container';
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    // IMAGES
    // ═══════════════════════════════════════════════════════════════════
    var imagesLoaded = false;

    // ═══════════════════════════════════════════════════════════════════
    // TRANSLATIONS (inline tab)
    // ═══════════════════════════════════════════════════════════════════
    var translationsLoaded = false;
    var translationsPage = 1;
    var translationsPerPage = 20;

    function loadTranslations(page) {
        if (page != null) translationsPage = page;
        translationsLoaded = true;

        var langMap = {};
        (ctx.languages || []).forEach(function (l) { langMap[l.id] = l.name + ' (' + l.code + ')'; });

        var params = { page: translationsPage, per_page: translationsPerPage };
        var containerId = 'translations-inline-table-container';

        var actionHeaders = caps.can_upsert || caps.can_delete ? ['Actions'] : [];
        var actionRows = actionHeaders.length ? ['actions'] : [];

        var renderers = {
            language_id: function (v) {
                return '<span class="text-sm text-gray-800 dark:text-gray-200">' + (langMap[v] || 'ID:' + v) + '</span>';
            },
            language_name: function (v) {
                return '<span class="font-medium text-gray-900 dark:text-gray-100">' + (v || '') + '</span>';
            },
            translated_name: function (v) {
                return v
                    ? '<span class="font-medium text-gray-900 dark:text-gray-100">' + v + '</span>'
                    : '<em class="text-gray-400">—</em>';
            },
            translated_description: function (v) {
                return v
                    ? '<span class="text-gray-700 dark:text-gray-300 text-sm">' + v + '</span>'
                    : '<em class="text-gray-400 text-sm">No description</em>';
            }
        };

        if (actionHeaders.length) {
            renderers.actions = function (_, row) {
                var btns = [];
                if (caps.can_upsert) {
                    btns.push('<button class="edit-translation-inline-btn px-2 py-1 text-xs bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300 rounded hover:bg-indigo-200 transition-colors"'
                        + ' data-language-id="' + row.language_id + '"'
                        + ' data-name="' + (row.translated_name || '').replace(/"/g, '&quot;') + '"'
                        + ' data-description="' + (row.translated_description || '').replace(/"/g, '&quot;') + '">'
                        + 'Edit</button>');
                }
                if (caps.can_delete && row.has_translation) {
                    btns.push('<button class="delete-translation-inline-btn px-2 py-1 text-xs bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 rounded hover:bg-red-200 transition-colors"'
                        + ' data-language-id="' + row.language_id + '">Delete</button>');
                }
                return '<div class="flex gap-1">' + btns.join('') + '</div>';
            };
        }

        Bridge.Table.withTargetContainer(containerId, function () {
            return createTable(
                'categories/' + categoryId + '/translations/query',
                params,
                ['Language', 'Translated Name', 'Translated Description'].concat(actionHeaders),
                ['language_name', 'translated_name', 'translated_description'].concat(actionRows),
                false, 'language_id', null,
                renderers,
                null, null
            ).catch(function (err) {
                console.error('Translations table error:', err);
                var c = document.getElementById(containerId);
                if (c) c.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">Failed to load translations.</div>';
            });
        });
    }

    function openTranslationInlineModal(languageId, name, description) {
        var langEl = document.getElementById('translation-language-id');
        var nameEl = document.getElementById('translation-name');
        var descEl = document.getElementById('translation-description');
        var titleEl = document.getElementById('translation-modal-title');

        if (titleEl) titleEl.textContent = languageId ? 'Edit Translation' : 'Add Translation';
        if (langEl) { langEl.value = languageId || ''; langEl.disabled = !!languageId; }
        if (nameEl) nameEl.value = name || '';
        if (descEl) descEl.value = description || '';

        Bridge.Modal.open('#translation-upsert-modal');
    }

    function saveTranslationInline() {
        var langEl = document.getElementById('translation-language-id');
        var nameEl = document.getElementById('translation-name');
        var descEl = document.getElementById('translation-description');

        var languageId = langEl ? parseInt(langEl.value, 10) : 0;
        var name = nameEl ? nameEl.value.trim() : '';
        var description = descEl ? descEl.value.trim() : '';

        if (!languageId) { Bridge.UI.error('Please select a language.'); return; }
        if (!name) { Bridge.UI.error('Translated name is required.'); return; }

        Bridge.API.runMutation({
            operation: 'Upsert Translation',
            endpoint: 'categories/' + categoryId + '/translations/upsert',
            method: 'POST',
            payload: { language_id: languageId, translated_name: name, translated_description: description || null },
            successMessage: 'Translation saved.',
            modal: '#translation-upsert-modal',
            modalOptions: { resetForm: true },
            reloadHandler: function () {
                var langElInner = document.getElementById('translation-language-id');
                if (langElInner) langElInner.disabled = false;
                loadTranslations();
            }
        });
    }

    function deleteTranslationInline(languageId) {
        if (!confirm('Delete this translation?')) return;
        Bridge.API.runMutation({
            operation: 'Delete Translation',
            endpoint: 'categories/' + categoryId + '/translations/delete',
            method: 'POST',
            payload: { language_id: parseInt(languageId, 10) },
            successMessage: 'Translation deleted.',
            reloadHandler: function () { loadTranslations(); }
        });
    }


    function loadImages() {
        imagesLoaded = true;

        var containerId = 'images-table-container';

        Bridge.API.execute({
            endpoint: 'categories/' + categoryId + '/images/query',
            payload: {},
            method: 'POST',
            operation: 'Query Images',
            showErrorMessage: false
        }).then(function (result) {
            var c = document.getElementById(containerId);
            if (!result.success) {
                if (c) c.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">' + (result.error || 'Failed') + '</div>';
                return;
            }

            // API returns grouped: { image: [...], mobile_image: [...], ... }
            // Flatten all groups into a single array
            var grouped = (result.data && typeof result.data === 'object' && !Array.isArray(result.data))
                ? result.data
                : {};
            var items = [];
            Object.values(grouped).forEach(function (group) {
                if (Array.isArray(group)) items = items.concat(group);
            });

            var langMap = {};
            (ctx.languages || []).forEach(function (l) { langMap[l.id] = l.name + ' (' + l.code + ')'; });

            var actionHeaders = caps.can_upsert_images || caps.can_delete_images ? ['Actions'] : [];
            var actionRows = actionHeaders.length ? ['actions'] : [];

            var renderers = {
                image_type: function (v) {
                    var labels = { image: 'Default', mobile_image: 'Mobile', api_image: 'API', website_image: 'Website' };
                    return AdminUIComponents.renderCodeBadge(labels[v] || v, { color: 'blue' });
                },
                language_id: function (v) { return '<span class="text-gray-900 dark:text-gray-200">' + (langMap[v] || 'ID:' + v) + '</span>'; },
                path: function (v) { return '<code class="text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-gray-800 dark:text-gray-200 break-all">' + (v || '') + '</code>'; }
            };

            if (actionHeaders.length) {
                renderers.actions = function (_, row) {
                    var btns = [];
                    if (caps.can_upsert_images) {
                        btns.push('<button class="edit-image-btn px-2 py-1 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded hover:bg-blue-200 transition-colors" data-image-type="' + row.image_type + '" data-language-id="' + row.language_id + '" data-path="' + (row.path || '').replace(/"/g, '&quot;') + '">Edit</button>');
                    }
                    if (caps.can_delete_images) {
                        btns.push('<button class="delete-image-btn px-2 py-1 text-xs bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 rounded hover:bg-red-200 transition-colors" data-image-type="' + row.image_type + '" data-language-id="' + row.language_id + '">Delete</button>');
                    }
                    return '<div class="flex gap-1">' + btns.join('') + '</div>';
                };
            }

            var oldContainer = document.getElementById('table-container');
            var imgContainer = document.getElementById(containerId);
            if (imgContainer) imgContainer.id = 'table-container';
            if (oldContainer && oldContainer !== imgContainer) oldContainer.id = '_main-table-container';

            try {
                var pagination = { page: 1, per_page: Math.max(items.length, 20), total: items.length };
                TableComponent(items,
                    ['Type', 'Language', 'Path'].concat(actionHeaders),
                    ['image_type', 'language_id', 'path'].concat(actionRows),
                    pagination, '', false, 'image_type', null, renderers);
            } catch (e) { console.error(e); }

            var rendered = document.getElementById('table-container');
            if (rendered) rendered.id = containerId;
            if (oldContainer && oldContainer.id === '_main-table-container') oldContainer.id = 'table-container';
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    // SETTING MUTATIONS
    // ═══════════════════════════════════════════════════════════════════
    function openSettingModal(key, value) {
        var titleEl = document.getElementById('setting-modal-title');
        var keyEl = document.getElementById('setting-key');
        var valEl = document.getElementById('setting-value');

        if (titleEl) titleEl.textContent = key ? 'Edit Setting' : 'Add Setting';
        if (keyEl) { keyEl.value = key || ''; keyEl.disabled = !!key; }
        if (valEl) valEl.value = value || '';

        Bridge.Modal.open('#setting-modal');
    }

    function saveSetting() {
        var key = (document.getElementById('setting-key') || {}).value;
        var value = (document.getElementById('setting-value') || {}).value;
        if (!key || !value) { Bridge.UI.error('Key and value are required.'); return; }

        Bridge.API.runMutation({
            operation: 'Upsert Setting',
            endpoint: 'categories/' + categoryId + '/settings/upsert',
            method: 'POST',
            payload: { key: key, value: value },
            successMessage: 'Setting saved.',
            modal: '#setting-modal',
            modalOptions: { resetForm: true },
            reloadHandler: function () { loadSettings(); }
        });
    }

    function deleteSetting(key) {
        if (!confirm('Delete setting "' + key + '"?')) return;
        Bridge.API.runMutation({
            operation: 'Delete Setting',
            endpoint: 'categories/' + categoryId + '/settings/delete',
            method: 'POST',
            payload: { key: key },
            successMessage: 'Setting deleted.',
            reloadHandler: function () { loadSettings(); }
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    // IMAGE MUTATIONS
    // ═══════════════════════════════════════════════════════════════════
    function openImageModal(imageType, languageId, path) {
        var titleEl = document.getElementById('image-modal-title');
        var typeEl = document.getElementById('image-type');
        var langEl = document.getElementById('image-language');
        var pathEl = document.getElementById('image-path');

        if (titleEl) titleEl.textContent = imageType ? 'Edit Image' : 'Add Image';
        if (typeEl) typeEl.value = imageType || 'image';
        if (langEl) langEl.value = languageId || '';
        if (pathEl) pathEl.value = path || '';

        Bridge.Modal.open('#image-modal');
    }

    function saveImage() {
        var imageType = (document.getElementById('image-type') || {}).value;
        var languageId = parseInt((document.getElementById('image-language') || {}).value, 10);
        var path = (document.getElementById('image-path') || {}).value;
        if (!imageType || !languageId || !path) { Bridge.UI.error('All fields are required.'); return; }

        Bridge.API.runMutation({
            operation: 'Upsert Image',
            endpoint: 'categories/' + categoryId + '/images/upsert',
            method: 'POST',
            payload: { image_type: imageType, language_id: languageId, path: path },
            successMessage: 'Image saved.',
            modal: '#image-modal',
            modalOptions: { resetForm: true },
            reloadHandler: function () { loadImages(); }
        });
    }

    function deleteImage(imageType, languageId) {
        if (!confirm('Delete this image?')) return;
        Bridge.API.runMutation({
            operation: 'Delete Image',
            endpoint: 'categories/' + categoryId + '/images/delete',
            method: 'POST',
            payload: { image_type: imageType, language_id: parseInt(languageId, 10) },
            successMessage: 'Image deleted.',
            reloadHandler: function () { loadImages(); }
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    // CATEGORY INFO EDIT
    // ═══════════════════════════════════════════════════════════════════

    async function loadParentDropdownForEdit() {
        var select = document.getElementById('edit-category-info-parent');
        if (!select) return;
        select.innerHTML = '<option value="">— None (Top Level) —</option>';

        var result = await Bridge.API.execute({
            endpoint: 'categories/dropdown',
            payload: {},
            operation: 'Load Parent Categories',
            method: 'POST',
            showErrorMessage: false
        });

        if (result.success) {
            var items = Array.isArray(result.data?.data) ? result.data.data : (Array.isArray(result.data) ? result.data : []);
            items.forEach(function (cat) {
                if (cat.id === categoryId) return; // can't be own parent
                var opt = document.createElement('option');
                opt.value = cat.id;
                opt.textContent = cat.name;
                select.appendChild(opt);
            });
        }

        // Set current parent
        if (ctx.category_parent_id) {
            select.value = ctx.category_parent_id;
        }
    }

    function openEditCategoryInfoModal() {
        var nameEl = document.getElementById('edit-category-info-name');
        var slugEl = document.getElementById('edit-category-info-slug');
        var orderEl = document.getElementById('edit-category-info-order');
        var activeEl = document.getElementById('edit-category-info-active');
        var notesEl = document.getElementById('edit-category-info-notes');
        var descEl = document.getElementById('edit-category-info-description');

        if (nameEl) nameEl.value = ctx.category_name || '';
        if (slugEl) slugEl.value = ctx.category_slug || '';
        if (orderEl) orderEl.value = ctx.category_display_order || 0;
        if (activeEl) activeEl.checked = !!ctx.category_is_active;
        if (notesEl) notesEl.value = ctx.category_notes || '';
        if (descEl) descEl.value = ctx.category_description || '';

        loadParentDropdownForEdit();
        Bridge.Modal.open('#edit-category-info-modal');
    }

    function saveCategoryInfo() {
        var nameEl = document.getElementById('edit-category-info-name');
        var slugEl = document.getElementById('edit-category-info-slug');
        var name = nameEl ? nameEl.value.trim() : '';
        var slug = slugEl ? slugEl.value.trim() : '';
        if (!name) { Bridge.UI.error('Name is required.'); return; }
        if (!slug) { Bridge.UI.error('Slug is required.'); return; }

        var payload = { id: categoryId, name: name, slug: slug };

        var orderEl = document.getElementById('edit-category-info-order');
        if (orderEl) payload.display_order = parseInt(orderEl.value, 10) || 0;

        var activeEl = document.getElementById('edit-category-info-active');
        if (activeEl) payload.is_active = activeEl.checked;

        var parentEl = document.getElementById('edit-category-info-parent');
        if (parentEl) {
            var pVal = parentEl.value;
            payload.parent_id = pVal ? parseInt(pVal, 10) : null;
        }

        var notesEl = document.getElementById('edit-category-info-notes');
        if (notesEl) payload.notes = notesEl.value.trim() || null;

        var descEl = document.getElementById('edit-category-info-description');
        if (descEl) payload.description = descEl.value.trim() || null;

        Bridge.API.runMutation({
            operation: 'Update Category',
            endpoint: 'categories/update',
            method: 'POST',
            payload: payload,
            successMessage: 'Category updated successfully.',
            modal: '#edit-category-info-modal',
            modalOptions: { resetForm: false },
            reloadHandler: function () {
                // Update the info card in-place
                var infoName = document.getElementById('category-info-name');
                if (infoName) infoName.textContent = payload.name;

                var infoSlug = document.getElementById('category-info-slug');
                if (infoSlug) infoSlug.textContent = payload.slug;

                var infoOrder = document.getElementById('category-info-order');
                if (infoOrder) infoOrder.textContent = payload.display_order;

                var infoStatus = document.getElementById('category-info-status');
                if (infoStatus) {
                    var isActive = payload.is_active;
                    infoStatus.textContent = isActive ? 'Active' : 'Inactive';
                    infoStatus.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' +
                        (isActive
                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                            : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300');
                }

                var infoNotes = document.getElementById('category-info-notes');
                if (infoNotes) {
                    if (payload.notes) {
                        infoNotes.textContent = payload.notes;
                        infoNotes.className = 'text-sm text-gray-800 dark:text-gray-100';
                    } else {
                        infoNotes.textContent = 'No notes found';
                        infoNotes.className = 'text-sm italic text-gray-400 dark:text-gray-500';
                    }
                }

                var infoDesc = document.getElementById('category-info-description');
                if (infoDesc) {
                    if (payload.description) {
                        infoDesc.textContent = payload.description;
                        infoDesc.className = 'text-sm text-gray-800 dark:text-gray-100';
                    } else {
                        infoDesc.textContent = 'No description found';
                        infoDesc.className = 'text-sm italic text-gray-400 dark:text-gray-500';
                    }
                }

                // Update the page title
                var pageTitle = document.querySelector('h2');
                if (pageTitle) pageTitle.textContent = payload.name;

                // Update context
                ctx.category_name = payload.name;
                ctx.category_slug = payload.slug;
                ctx.category_parent_id = payload.parent_id;
                ctx.category_display_order = payload.display_order;
                ctx.category_is_active = payload.is_active;
                ctx.category_notes = payload.notes;
                ctx.category_description = payload.description;
            }
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    // INIT
    // ═══════════════════════════════════════════════════════════════════
    function init() {
        initTabs();
        setupModalClose();

        // Sub-categories search
        var subSearchBtn = document.getElementById('sub-categories-search-btn');
        if (subSearchBtn) subSearchBtn.addEventListener('click', function () { subPage = 1; loadSubCategories(); });
        var subClearBtn = document.getElementById('sub-categories-clear-search');
        if (subClearBtn) subClearBtn.addEventListener('click', function () {
            var el = document.getElementById('sub-categories-search');
            if (el) el.value = '';
            subPage = 1;
            loadSubCategories();
        });

        // Settings search
        var settingsSearchBtn = document.getElementById('settings-search-btn');
        if (settingsSearchBtn) settingsSearchBtn.addEventListener('click', function () { settingsPage = 1; loadSettings(); });
        var settingsClearBtn = document.getElementById('settings-clear-search');
        if (settingsClearBtn) settingsClearBtn.addEventListener('click', function () {
            var el = document.getElementById('settings-search');
            if (el) el.value = '';
            settingsPage = 1;
            loadSettings();
        });

        // Settings actions
        var createSettingBtn = document.getElementById('btn-create-setting');
        if (createSettingBtn) createSettingBtn.addEventListener('click', function () { openSettingModal(); });

        var saveSettingBtn = document.getElementById('btn-save-setting');
        if (saveSettingBtn) saveSettingBtn.addEventListener('click', saveSetting);

        document.addEventListener('click', function (e) {
            var editBtn = e.target.closest('.edit-setting-btn');
            if (editBtn) { openSettingModal(editBtn.getAttribute('data-key'), editBtn.getAttribute('data-value')); return; }
            var delBtn = e.target.closest('.delete-setting-btn');
            if (delBtn) { deleteSetting(delBtn.getAttribute('data-key')); return; }
        });

        // Images actions
        var upsertImageBtn = document.getElementById('btn-upsert-image');
        if (upsertImageBtn) upsertImageBtn.addEventListener('click', function () { openImageModal(); });

        var saveImageBtn = document.getElementById('btn-save-image');
        if (saveImageBtn) saveImageBtn.addEventListener('click', saveImage);

        // Category info edit
        var editInfoBtn = document.getElementById('btn-edit-category-info');
        if (editInfoBtn) editInfoBtn.addEventListener('click', function () { openEditCategoryInfoModal(); });

        var saveInfoBtn = document.getElementById('btn-save-category-info');
        if (saveInfoBtn) saveInfoBtn.addEventListener('click', saveCategoryInfo);

        document.addEventListener('click', function (e) {
            var editBtn = e.target.closest('.edit-image-btn');
            if (editBtn) { openImageModal(editBtn.getAttribute('data-image-type'), editBtn.getAttribute('data-language-id'), editBtn.getAttribute('data-path')); return; }
            var delBtn = e.target.closest('.delete-image-btn');
            if (delBtn) { deleteImage(delBtn.getAttribute('data-image-type'), delBtn.getAttribute('data-language-id')); return; }
        });

        // Translations actions
        var upsertTranslationBtn = document.getElementById('btn-upsert-translation');
        if (upsertTranslationBtn) upsertTranslationBtn.addEventListener('click', function () { openTranslationInlineModal(); });

        var saveTranslationBtn = document.getElementById('btn-save-translation-inline');
        if (saveTranslationBtn) saveTranslationBtn.addEventListener('click', saveTranslationInline);

        document.addEventListener('click', function (e) {
            var editBtn = e.target.closest('.edit-translation-inline-btn');
            if (editBtn) {
                openTranslationInlineModal(
                    editBtn.getAttribute('data-language-id'),
                    editBtn.getAttribute('data-name'),
                    editBtn.getAttribute('data-description')
                );
                return;
            }
            var delBtn = e.target.closest('.delete-translation-inline-btn');
            if (delBtn) { deleteTranslationInline(delBtn.getAttribute('data-language-id')); return; }
        });

        // Load first visible tab
        if (caps.can_view_sub_categories) loadSubCategories();
        else if (caps.can_view_images) loadImages();
        else if (caps.can_view_translations) loadTranslations();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    console.log('✅ Category Detail V2 loaded');
})();




