/**
 * category-images-v2.js
 *
 * Handles the Category Images dedicated page.
 *
 * Reads:
 *   window.categoryImagesContext      — { category_id, category_name }
 *   window.categoryImagesCapabilities — { can_upsert_images, can_delete_images }
 */
(function () {
    'use strict';

    var ctx          = window.categoryImagesContext || {};
    var caps         = window.categoryImagesCapabilities || {};
    var categoryId = ctx.category_id;
    var languages  = ctx.languages || [];   // injected by controller

    // ── Language helpers ──────────────────────────────────────────────
    function populateLanguageSelect(selectId) {
        var sel = document.getElementById(selectId);
        if (!sel) return;
        sel.innerHTML = '';
        if (!languages.length) {
            sel.innerHTML = '<option value="">No languages available</option>';
            return;
        }
        languages.forEach(function (l) {
            var opt         = document.createElement('option');
            opt.value       = l.id;
            opt.textContent = l.name + ' (' + l.code + ')';
            sel.appendChild(opt);
        });
    }

    function langName(id) {
        var found = languages.find(function (l) { return String(l.id) === String(id); });
        return found ? found.name + ' (' + found.code + ')' : 'ID:' + id;
    }

    // ── Load images table ─────────────────────────────────────────────
    function loadImagesTable() {
        var container = document.getElementById('images-table-container');
        if (!container) return;

        container.innerHTML = '<p class="text-sm text-gray-400 py-4 text-center">Loading\u2026</p>';

        ApiHandler.call(
            'categories/' + categoryId + '/images/query',
            {},
            'Query Images',
            'POST'
        ).then(function (res) {
            if (!res || !res.success || !res.data) {
                container.innerHTML = '<p class="text-sm text-red-500 py-4 text-center">Failed to load images.</p>';
                return;
            }

            // Flatten grouped structure: { image:[...], mobile_image:[...], ... }
            var rows = [];
            Object.keys(res.data).forEach(function (type) {
                (res.data[type] || []).forEach(function (img) { rows.push(img); });
            });

            if (!rows.length) {
                container.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">No images found.</p>';
                return;
            }

            renderTable(container, rows);
        });
    }

    function renderTable(container, rows) {
        var hasActions = caps.can_upsert_images || caps.can_delete_images;
        var html = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">'
            + '<thead class="bg-gray-50 dark:bg-gray-700"><tr>'
            + '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>'
            + '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Language</th>'
            + '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Path</th>';
        if (hasActions) {
            html += '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>';
        }
        html += '</tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-700">';

        rows.forEach(function (row) {
            html += '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">'
                + '<td class="px-4 py-2"><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">' + row.image_type + '</span></td>'
                + '<td class="px-4 py-2 text-gray-700 dark:text-gray-300">' + langName(row.language_id) + '</td>'
                + '<td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300 break-all">' + (row.path || '') + '</td>';

            if (hasActions) {
                var actions = '';
                if (caps.can_upsert_images) {
                    actions += '<button class="btn-edit-image px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200 mr-1"'
                        + ' data-type="' + row.image_type + '" data-lang="' + row.language_id + '" data-path="' + (row.path || '') + '">Edit</button>';
                }
                if (caps.can_delete_images) {
                    actions += '<button class="btn-delete-image px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200"'
                        + ' data-type="' + row.image_type + '" data-lang="' + row.language_id + '">Delete</button>';
                }
                html += '<td class="px-4 py-2">' + actions + '</td>';
            }
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;
        bindImageTableActions();
    }

    function bindImageTableActions() {
        document.querySelectorAll('.btn-edit-image').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openImageModal(
                    btn.getAttribute('data-type'),
                    btn.getAttribute('data-lang'),
                    btn.getAttribute('data-path')
                );
            });
        });

        document.querySelectorAll('.btn-delete-image').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!confirm('Delete this image?')) return;
                ApiHandler.call(
                    'categories/' + categoryId + '/images/delete',
                    { image_type: btn.getAttribute('data-type'), language_id: parseInt(btn.getAttribute('data-lang'), 10) },
                    'Delete Image',
                    'POST'
                ).then(function (res) {
                    if (res && res.success) loadImagesTable();
                });
            });
        });
    }

    // ── Image modal ───────────────────────────────────────────────────
    function openImageModal(type, langId, path) {
        var modal = document.getElementById('image-modal');
        if (!modal) return;

        populateLanguageSelect('image-language');

        var typeSel = document.getElementById('image-type');
        if (typeSel && type) typeSel.value = type;

        var langSel = document.getElementById('image-language');
        if (langSel && langId) langSel.value = String(langId);

        var pathInput = document.getElementById('image-path');
        if (pathInput) pathInput.value = path || '';

        modal.classList.remove('hidden');
    }

    // ── Init ──────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        loadImagesTable();

        // Add image button
        var btnUpsert = document.getElementById('btn-upsert-image');
        if (btnUpsert) {
            btnUpsert.addEventListener('click', function () {
                openImageModal(null, null, null);
            });
        }

        // Save image
        var btnSave = document.getElementById('btn-save-image');
        if (btnSave) {
            btnSave.addEventListener('click', function () {
                var type   = document.getElementById('image-type').value;
                var langId = document.getElementById('image-language').value;
                var path   = (document.getElementById('image-path').value || '').trim();

                if (!type || !langId || !path) {
                    ApiHandler.showAlert('danger', 'Please fill in all fields.');
                    return;
                }

                ApiHandler.call('categories/' + categoryId + '/images/upsert', {
                    image_type:  type,
                    language_id: parseInt(langId, 10),
                    image_path:  path,
                }, 'Save Image', 'POST').then(function (res) {
                    if (res && res.success) {
                        document.getElementById('image-modal').classList.add('hidden');
                        loadImagesTable();
                    }
                });
            });
        }

        // Close modals
        document.querySelectorAll('.close-modal').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var modal = btn.closest('.fixed');
                if (modal) modal.classList.add('hidden');
            });
        });
    });
}());





