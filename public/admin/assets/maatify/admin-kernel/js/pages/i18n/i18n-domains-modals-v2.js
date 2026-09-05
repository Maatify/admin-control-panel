/**
 * 🌐 I18n Domains Modals V2
 */
(function() {
    'use strict';

    console.log('🧩 I18n Domains Modals V2 loading...');

    if (!window.AdminPageBridge || !window.I18nHelpersV2) {
        console.error('❌ Missing dependencies for i18n-domains-modals-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.I18nHelpersV2;
    const capabilities = window.i18nDomainsCapabilities || {};

    function reloadDomainsTableV2() {
        if (typeof window.reloadDomainsTableV2 === 'function') {
            return window.reloadDomainsTableV2();
        }
    }

    function mountModals() {
        const existing = document.getElementById('domains-modals-container-v2');
        if (existing) return;

        const container = document.createElement('div');
        container.id = 'domains-modals-container-v2';
        container.innerHTML =
            '<div id="create-domain-modal-v2" class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 z-50 flex items-center justify-center hidden">' +
                '<div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full mx-4 border border-transparent dark:border-gray-700">' +
                    '<div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">' +
                        '<h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">➕ Create New Domain</h3>' +
                        '<button class="close-modal text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">✕</button>' +
                    '</div>' +
                    '<form id="create-domain-form-v2" class="px-6 py-4 space-y-4">' +
                        '<input id="create-code-v2" name="code" required maxlength="50" placeholder="e.g., user.profile" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg" />' +
                        '<input id="create-name-v2" name="name" required maxlength="100" placeholder="Domain name" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg" />' +
                        '<textarea id="create-description-v2" name="description" rows="3" maxlength="255" placeholder="Description" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg"></textarea>' +
                        '<label class="inline-flex items-center gap-2"><input type="checkbox" id="create-active-v2" checked><span class="text-sm text-gray-700 dark:text-gray-300">Active</span></label>' +
                        '<div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">' +
                            '<button type="button" class="close-modal px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-700 rounded-lg">Cancel</button>' +
                            '<button type="submit" class="px-4 py-2 text-white bg-green-600 rounded-lg">Create Domain</button>' +
                        '</div>' +
                    '</form>' +
                '</div>' +
            '</div>' +
            '<div id="change-code-modal-v2" class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 z-50 flex items-center justify-center hidden">' +
                '<div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full mx-4 border border-transparent dark:border-gray-700">' +
                    '<div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">' +
                        '<h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">🔁 Change Domain Code</h3>' +
                        '<button class="close-modal text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">✕</button>' +
                    '</div>' +
                    '<form id="change-code-form-v2" class="px-6 py-4 space-y-4">' +
                        '<input type="hidden" id="code-domain-id-v2" name="id" />' +
                        '<div id="code-current-code-v2" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-600 dark:text-gray-300 font-mono"></div>' +
                        '<input id="code-new-code-v2" name="new_code" required maxlength="50" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg font-mono" />' +
                        '<div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">' +
                            '<button type="button" class="close-modal px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-700 rounded-lg">Cancel</button>' +
                            '<button type="submit" class="px-4 py-2 text-white bg-amber-600 rounded-lg">Change Code</button>' +
                        '</div>' +
                    '</form>' +
                '</div>' +
            '</div>' +
            '<div id="update-metadata-modal-v2" class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 z-50 flex items-center justify-center hidden">' +
                '<div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full mx-4 border border-transparent dark:border-gray-700">' +
                    '<div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">' +
                        '<h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">✏️ Update Domain Metadata</h3>' +
                        '<button class="close-modal text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">✕</button>' +
                    '</div>' +
                    '<form id="update-metadata-form-v2" class="px-6 py-4 space-y-4">' +
                        '<input type="hidden" id="meta-domain-id-v2" name="id" />' +
                        '<input id="meta-name-v2" name="name" maxlength="100" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg" />' +
                        '<textarea id="meta-description-v2" name="description" rows="3" maxlength="255" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg"></textarea>' +
                        '<div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">' +
                            '<button type="button" class="close-modal px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-700 rounded-lg">Cancel</button>' +
                            '<button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded-lg">Update Metadata</button>' +
                        '</div>' +
                    '</form>' +
                '</div>' +
            '</div>' +
            '<div id="update-sort-modal-v2" class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 z-50 flex items-center justify-center hidden">' +
                '<div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 border border-transparent dark:border-gray-700">' +
                    '<div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">' +
                        '<h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">🔢 Update Sort Order</h3>' +
                        '<button class="close-modal text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">✕</button>' +
                    '</div>' +
                    '<form id="update-sort-form-v2" class="px-6 py-4 space-y-4">' +
                        '<input type="hidden" id="sort-domain-id-v2" name="id" />' +
                        '<div id="sort-domain-name-v2" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-200 font-medium"></div>' +
                        '<div id="sort-current-order-v2" class="px-4 py-2 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-600 dark:text-gray-400"></div>' +
                        '<input type="number" id="sort-new-order-v2" name="position" min="0" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg" />' +
                        '<div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">' +
                            '<button type="button" class="close-modal px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-700 rounded-lg">Cancel</button>' +
                            '<button type="submit" class="px-4 py-2 text-white bg-indigo-600 rounded-lg">Update Order</button>' +
                        '</div>' +
                    '</form>' +
                '</div>' +
            '</div>';

        document.body.appendChild(container);

        Helpers.wireModalDismiss('#create-domain-modal-v2');
        Helpers.wireModalDismiss('#change-code-modal-v2');
        Helpers.wireModalDismiss('#update-metadata-modal-v2');
        Helpers.wireModalDismiss('#update-sort-modal-v2');
    }

    async function fetchDomainDetails(domainId) {
        const result = await Bridge.API.execute({
            endpoint: 'i18n/domains/query',
            payload: { page: 1, per_page: 1, search: { columns: { id: domainId } } },
            operation: 'Fetch Domain Details',
            showErrorMessage: false
        });

        if (result.success && result.data?.data?.length) {
            return result.data.data[0];
        }

        Bridge.UI.error(result.error || 'Failed to fetch domain details');
        return null;
    }

    function setupCreateDomainModal() {
        const btnCreate = document.getElementById('btn-create-domain');
        if (btnCreate && capabilities.can_create) {
            btnCreate.addEventListener('click', function() {
                Bridge.Modal.open('#create-domain-modal-v2');
            });
        }

        const form = document.getElementById('create-domain-form-v2');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const payload = {
                code: Bridge.DOM.value('#create-code-v2', '').trim(),
                name: Bridge.DOM.value('#create-name-v2', '').trim(),
                description: Bridge.DOM.value('#create-description-v2', '').trim(),
                is_active: Bridge.DOM.checked('#create-active-v2', true)
            };

            Bridge.API.runMutation({
                operation: 'Create Domain',
                endpoint: 'i18n/domains/create',
                method: 'POST',
                payload,
                successMessage: 'Domain created successfully',
                reloadHandler: reloadDomainsTableV2,
                onSuccess: function() {
                    Bridge.Modal.close('#create-domain-modal-v2');
                    form.reset();
                },
                onFailure: function(result) {
                    if (result?.data?.errors && window.ApiHandler?.showFieldErrors) {
                        window.ApiHandler.showFieldErrors(result.data.errors, 'create-domain-form-v2');
                    }
                }
            });
        });
    }

    async function openChangeCodeModal(id) {
        const domain = await fetchDomainDetails(id);
        if (!domain) return;

        Bridge.DOM.setValue('#code-domain-id-v2', domain.id);
        const currentCode = document.getElementById('code-current-code-v2');
        if (currentCode) currentCode.textContent = domain.code;
        Bridge.DOM.setValue('#code-new-code-v2', domain.code || '');
        Bridge.Modal.open('#change-code-modal-v2');
    }

    async function openUpdateSortModal(id) {
        const domain = await fetchDomainDetails(id);
        if (!domain) return;

        Bridge.DOM.setValue('#sort-domain-id-v2', domain.id);
        const nameEl = document.getElementById('sort-domain-name-v2');
        const orderEl = document.getElementById('sort-current-order-v2');
        if (nameEl) nameEl.textContent = domain.name;
        if (orderEl) orderEl.textContent = domain.sort_order;
        Bridge.DOM.setValue('#sort-new-order-v2', domain.sort_order || 0);
        Bridge.Modal.open('#update-sort-modal-v2');
    }

    async function openUpdateMetadataModal(id) {
        const domain = await fetchDomainDetails(id);
        if (!domain) return;

        Bridge.DOM.setValue('#meta-domain-id-v2', domain.id);
        Bridge.DOM.setValue('#meta-name-v2', domain.name || '');
        Bridge.DOM.setValue('#meta-description-v2', domain.description || '');
        Bridge.Modal.open('#update-metadata-modal-v2');
    }

    function setupChangeCodeForm() {
        const form = document.getElementById('change-code-form-v2');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const payload = {
                id: Bridge.normalizeInt(Bridge.DOM.value('#code-domain-id-v2', 0), 0),
                new_code: Bridge.DOM.value('#code-new-code-v2', '').trim()
            };

            Bridge.API.runMutation({
                operation: 'Change Code',
                endpoint: 'i18n/domains/change-code',
                method: 'POST',
                payload,
                successMessage: 'Code changed successfully',
                reloadHandler: reloadDomainsTableV2,
                onSuccess: function() {
                    Bridge.Modal.close('#change-code-modal-v2');
                    form.reset();
                },
                onFailure: function(result) {
                    if (result?.data?.errors && window.ApiHandler?.showFieldErrors) {
                        window.ApiHandler.showFieldErrors(result.data.errors, 'change-code-form-v2');
                    } else if (result?.data?.message) {
                        Bridge.UI.error(result.data.message);
                    }
                }
            });
        });
    }

    function setupUpdateSortForm() {
        const form = document.getElementById('update-sort-form-v2');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const payload = {
                id: Bridge.normalizeInt(Bridge.DOM.value('#sort-domain-id-v2', 0), 0),
                position: Bridge.normalizeInt(Bridge.DOM.value('#sort-new-order-v2', 0), 0)
            };

            Bridge.API.runMutation({
                operation: 'Update Sort',
                endpoint: 'i18n/domains/update-sort',
                method: 'POST',
                payload,
                successMessage: 'Sort order updated successfully',
                reloadHandler: reloadDomainsTableV2,
                onSuccess: function() {
                    Bridge.Modal.close('#update-sort-modal-v2');
                    form.reset();
                },
                onFailure: function(result) {
                    if (result?.data?.errors && window.ApiHandler?.showFieldErrors) {
                        window.ApiHandler.showFieldErrors(result.data.errors, 'update-sort-form-v2');
                    }
                }
            });
        });
    }

    function setupUpdateMetadataForm() {
        const form = document.getElementById('update-metadata-form-v2');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const payload = {
                id: Bridge.normalizeInt(Bridge.DOM.value('#meta-domain-id-v2', 0), 0),
                name: Bridge.DOM.value('#meta-name-v2', '').trim(),
                description: Bridge.DOM.value('#meta-description-v2', '').trim()
            };

            Bridge.API.runMutation({
                operation: 'Update Metadata',
                endpoint: 'i18n/domains/update-metadata',
                method: 'POST',
                payload,
                successMessage: 'Metadata updated successfully',
                reloadHandler: reloadDomainsTableV2,
                onSuccess: function() {
                    Bridge.Modal.close('#update-metadata-modal-v2');
                    form.reset();
                },
                onFailure: function(result) {
                    if (result?.data?.errors && window.ApiHandler?.showFieldErrors) {
                        window.ApiHandler.showFieldErrors(result.data.errors, 'update-metadata-form-v2');
                    }
                }
            });
        });
    }

    function init() {
        mountModals();
        setupCreateDomainModal();
        setupChangeCodeForm();
        setupUpdateSortForm();
        setupUpdateMetadataForm();

        window.DomainsModalsV2 = {
            openChangeCodeModal: openChangeCodeModal,
            openUpdateSortModal: openUpdateSortModal,
            openUpdateMetadataModal: openUpdateMetadataModal
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
