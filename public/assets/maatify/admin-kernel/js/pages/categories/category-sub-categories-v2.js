/**
 * category-sub-categories-v2.js
 *
 * Handles the Category Sub-categories dedicated page.
 *
 * Reads:
 *   window.categorySubCategoriesContext      — { category_id, category_name }
 *   window.categorySubCategoriesCapabilities — { can_create, can_update, can_active, can_update_sort }
 */
(function () {
    'use strict';

    if (!window.AdminPageBridge) {
        console.error('❌ AdminPageBridge not found for category-sub-categories-v2');
        return;
    }

    var Bridge      = window.AdminPageBridge;
    var ctx         = window.categorySubCategoriesContext || {};
    var caps        = window.categorySubCategoriesCapabilities || {};
    var categoryId  = ctx.category_id;

    var currentParams = { page: 1, per_page: 20 };
    var searchVal     = '';

    var CONTAINER_ID = 'sub-categories-table-container';
    var apiQueryUrl  = 'categories/' + categoryId + '/sub-categories/query';

    var headers = ['ID', 'Name', 'Slug', 'Order', 'Status'];
    var rowKeys  = ['id', 'name', 'slug', 'display_order', 'is_active'];

    if (caps.can_update || caps.can_active) {
        headers.push('Actions');
        rowKeys.push('actions');
    }

    var customRenderers = {
        id: function (value) {
            return '<span class="text-gray-500 text-xs font-mono">#' + value + '</span>';
        },
        name: function (value, row) {
            return '<a href="/categories/' + row.id + '" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">' + value + '</a>';
        },
        slug: function (value) {
            return '<code class="text-xs font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">' + value + '</code>';
        },
        display_order: function (value) {
            return '<span class="text-sm text-gray-700 dark:text-gray-300">' + (value !== null && value !== undefined ? value : 0) + '</span>';
        },
        is_active: function (value) {
            return value
                ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Active</span>'
                : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">Inactive</span>';
        },
        actions: function (value, row) {
            var html = '';
            if (caps.can_update) {
                html += '<a href="/categories/' + row.id + '" class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200 mr-1">View</a>';
            }
            if (caps.can_active) {
                var isActive    = row.is_active;
                var toggleLabel = isActive ? 'Deactivate' : 'Activate';
                var toggleClass = isActive ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200';
                html += '<button class="btn-toggle-active px-2 py-1 text-xs ' + toggleClass + ' rounded"'
                    + ' data-id="' + row.id + '" data-active="' + (isActive ? '1' : '0') + '">' + toggleLabel + '</button>';
            }
            return html;
        }
    };

    // ── Build params ──────────────────────────────────────────────────
    function buildParams() {
        var params = { page: currentParams.page, per_page: currentParams.per_page };
        if (searchVal) params.search = { global: searchVal };
        return params;
    }

    // ── Load sub-categories table ─────────────────────────────────────
    function loadTable() {
        Bridge.Table.withTargetContainer(CONTAINER_ID, function () {
            return createTable(
                apiQueryUrl,
                buildParams(),
                headers,
                rowKeys,
                false,
                'id',
                null,
                customRenderers
            );
        }).then(function () {
            bindTableActions();
        }).catch(function (err) {
            console.error('Sub-categories table error:', err);
            var container = document.getElementById(CONTAINER_ID);
            if (container) {
                container.innerHTML = '<p class="text-sm text-red-500 py-4 text-center">Failed to load sub-categories.</p>';
            }
        });
    }

    function bindTableActions() {
        document.querySelectorAll('.btn-toggle-active').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id     = btn.getAttribute('data-id');
                var active = btn.getAttribute('data-active') === '1';
                ApiHandler.call(
                    'categories/set-active',
                    { category_id: parseInt(id, 10), is_active: !active },
                    'Toggle Active',
                    'POST'
                ).then(function (res) {
                    if (res && res.success) loadTable();
                });
            });
        });
    }

    // ── tableAction listener (pagination / per-page changes) ──────────
    var unbindTableAction = Bridge.Table.bindActionState({
        sourceContainerId: CONTAINER_ID,
        getState: function () { return currentParams; },
        setState: function (next) { currentParams = next; },
        reload: function (params) {
            currentParams = params;
            loadTable();
        }
    });

    // ── Create sub-category modal ─────────────────────────────────────
    function openCreateModal() {
        var modal = document.getElementById('sub-category-create-modal');
        if (!modal) return;
        var nameInput  = document.getElementById('sub-cat-name');
        var slugInput  = document.getElementById('sub-cat-slug');
        var orderInput = document.getElementById('sub-cat-order');
        if (nameInput)  nameInput.value  = '';
        if (slugInput)  slugInput.value  = '';
        if (orderInput) orderInput.value = '0';
        modal.classList.remove('hidden');
    }

    function slugify(str) {
        return str.toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    // ── Init ──────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        loadTable();

        // Search
        var searchInput = document.getElementById('sub-categories-search');
        var searchBtn   = document.getElementById('sub-categories-search-btn');
        var clearBtn    = document.getElementById('sub-categories-clear-search');

        if (searchBtn) {
            searchBtn.addEventListener('click', function () {
                searchVal = searchInput ? searchInput.value.trim() : '';
                currentParams.page = 1;
                loadTable();
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                searchVal = '';
                currentParams.page = 1;
                if (searchInput) searchInput.value = '';
                loadTable();
            });
        }

        if (searchInput) {
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    searchVal = searchInput.value.trim();
                    currentParams.page = 1;
                    loadTable();
                }
            });
        }

        // Add sub-category button
        var btnCreate = document.getElementById('btn-create-sub-category');
        if (btnCreate && caps.can_create) {
            btnCreate.addEventListener('click', function () { openCreateModal(); });
        }

        // Auto-slug from name
        var nameInput = document.getElementById('sub-cat-name');
        var slugInput = document.getElementById('sub-cat-slug');
        if (nameInput && slugInput) {
            nameInput.addEventListener('input', function () {
                slugInput.value = slugify(nameInput.value);
            });
        }

        // Save new sub-category
        var btnSave = document.getElementById('btn-save-sub-category');
        if (btnSave) {
            btnSave.addEventListener('click', function () {
                var name  = (document.getElementById('sub-cat-name').value  || '').trim();
                var slug  = (document.getElementById('sub-cat-slug').value  || '').trim();
                var order = parseInt(document.getElementById('sub-cat-order').value || '0', 10);

                if (!name || !slug) {
                    ApiHandler.showAlert('danger', 'Name and Slug are required.');
                    return;
                }

                ApiHandler.call('categories/create', {
                    name:          name,
                    slug:          slug,
                    parent_id:     categoryId,
                    display_order: isNaN(order) ? 0 : order,
                    is_active:     true,
                }, 'Create Sub-category', 'POST').then(function (res) {
                    if (res && res.success) {
                        document.getElementById('sub-category-create-modal').classList.add('hidden');
                        currentParams.page = 1;
                        loadTable();
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

