/**
 * Roles Page - Metadata Edit Module
 * Handles editing display_name and description
 */

(function() {
    'use strict';

    console.log('✏️ Roles Metadata Module - Initializing');

    if (!window.RolesCore) {
        console.error('❌ RolesCore not found');
        return;
    }

    const { capabilities, loadRoles, showAlert } = window.RolesCore;

    // DOM Elements
    const editModal = document.getElementById('edit-metadata-modal');
    const editForm = document.getElementById('edit-metadata-form');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const cancelModalBtn = document.getElementById('cancel-modal-btn');
    const saveMetadataBtn = document.getElementById('save-metadata-btn');
    const modalMessage = document.getElementById('modal-message');
    const modalRoleId = document.getElementById('modal-role-id');
    const modalRoleName = document.getElementById('modal-role-name');
    const modalRoleGroup = document.getElementById('modal-role-group');
    const modalDisplayName = document.getElementById('modal-display-name');
    const modalDescription = document.getElementById('modal-description');

    let currentEditingRole = null;

    // ========================================================================
    // Modal Operations
    // ========================================================================

    function handleEditClick(btn) {
        const roleId = btn.getAttribute('data-role-id');
        const roleName = btn.getAttribute('data-role-name');
        const roleGroup = btn.getAttribute('data-role-group');
        const displayName = btn.getAttribute('data-display-name');
        const description = btn.getAttribute('data-description');

        console.log('━'.repeat(60));
        console.log('✏️ Edit Metadata - Opening Modal');
        console.log('━'.repeat(60));
        console.log('📌 Role Details:');
        console.log('  ├─ ID:', roleId);
        console.log('  ├─ Name:', roleName);
        console.log('  ├─ Group:', roleGroup);
        console.log('  ├─ Display Name:', displayName || '(not set)');
        console.log('  └─ Description:', description || '(not set)');

        currentEditingRole = {
            id: roleId,
            name: roleName,
            group: roleGroup,
            display_name: displayName === 'null' || !displayName ? '' : displayName,
            description: description === 'null' || !description ? '' : description
        };

        console.log('💾 Stored in currentEditingRole');
        console.log('🎨 Populating modal fields');

        modalRoleId.textContent = `#${roleId}`;
        modalRoleName.textContent = roleName;
        modalRoleGroup.textContent = roleGroup;
        modalDisplayName.value = currentEditingRole.display_name;
        modalDescription.value = currentEditingRole.description;

        hideModalMessage();
        console.log('✅ Modal ready');
        console.log('━'.repeat(60));

        openEditModal();
    }

    function openEditModal() {
        console.log('🎨 Opening edit modal');
        if (editModal) {
            editModal.classList.remove('hidden');
            console.log('  ├─ Modal visible');

            // ✅ Reset loading state and enable form
            setModalFormDisabled(false);
            hideModalLoadingState();
            console.log('  ├─ Loading state reset');

            setTimeout(() => {
                if (modalDisplayName) {
                    modalDisplayName.focus();
                    console.log('  └─ Focus set on display_name field');
                }
            }, 100);
        }
    }

    function closeEditModal() {
        console.log('🚪 Closing edit modal');
        if (editModal) {
            editModal.classList.add('hidden');
            console.log('  ├─ Modal hidden');
        }
        currentEditingRole = null;
        console.log('  ├─ Cleared currentEditingRole');

        if (editForm) {
            editForm.reset();
            console.log('  ├─ Form reset');
        }

        // ✅ Reset loading state and enable form
        setModalFormDisabled(false);
        hideModalLoadingState();
        console.log('  ├─ Loading state reset');

        hideModalMessage();
        console.log('  └─ Messages cleared');
    }

    async function handleMetadataUpdate(e) {
        e.preventDefault();

        if (!currentEditingRole) {
            console.error('❌ No role being edited');
            return;
        }

        console.log('━'.repeat(60));
        console.log('💾 Metadata Update - Starting');
        console.log('━'.repeat(60));

        hideModalMessage();

        const newDisplayName = modalDisplayName.value.trim();
        const newDescription = modalDescription.value.trim();

        console.log('📝 Current values:');
        console.log('  ├─ display_name:', currentEditingRole.display_name || '(empty)');
        console.log('  └─ description:', currentEditingRole.description || '(empty)');
        console.log('📝 New values:');
        console.log('  ├─ display_name:', newDisplayName || '(empty)');
        console.log('  └─ description:', newDescription || '(empty)');

        const requestBody = {};
        let hasChanges = false;

        if (newDisplayName !== currentEditingRole.display_name) {
            requestBody.display_name = newDisplayName;
            hasChanges = true;
            console.log('✏️ display_name changed');
        }

        if (newDescription !== currentEditingRole.description) {
            requestBody.description = newDescription;
            hasChanges = true;
            console.log('✏️ description changed');
        }

        if (!hasChanges) {
            console.log('ℹ️ No changes detected - skipping API call');
            console.log('━'.repeat(60));
            showModalMessage('No changes to save.', 'info');
            return;
        }

        console.log('📤 Sending to: POST /api/roles/' + currentEditingRole.id + '/metadata');
        console.log('📦 Payload:', JSON.stringify(requestBody, null, 2));

        setModalFormDisabled(true);
        showModalLoadingState();

        try {
            const response = await fetch(`/api/roles/${currentEditingRole.id}/metadata`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestBody)
            });

            console.log('📥 Response status:', response.status, response.statusText);

            if (response.status === 403) {
                const data = await response.json().catch(() => null);
                console.log('🔐 Step-Up 2FA required');
                if (data && data.code === 'STEP_UP_REQUIRED') {
                    const scope = encodeURIComponent(data.scope || 'roles.metadata.update');
                    const returnTo = encodeURIComponent(window.location.pathname);
                    window.location.href = `/2fa/verify?scope=${scope}&return_to=${returnTo}`;
                    return;
                }
            }

            if (response.status === 204) {
                console.log('ℹ️ 204 No Content - no update needed');
                console.log('━'.repeat(60));
                showModalMessage('No changes were necessary.', 'info');
                setModalFormDisabled(false);
                hideModalLoadingState();
                return;
            }

            if (!response.ok) {
                const data = await response.json().catch(() => null);
                const errorMsg = data && data.message ? data.message : 'Failed to update metadata.';
                console.error('❌ Update failed:', errorMsg);
                console.error('Response data:', data);
                console.log('━'.repeat(60));
                showModalMessage(errorMsg, 'error');
                setModalFormDisabled(false);
                hideModalLoadingState();
                return;
            }

            console.log('✅ Metadata updated successfully');
            console.log('━'.repeat(60));
            showModalMessage('Metadata updated successfully!', 'success');

            setTimeout(() => {
                closeEditModal();
                loadRoles();
                showAlert('s', 'Role metadata updated successfully');
            }, 1500);

        } catch (err) {
            console.error('━'.repeat(60));
            console.error('❌ Network error');
            console.error('Error:', err);
            console.error('Stack:', err.stack);
            console.error('━'.repeat(60));
            showModalMessage('Network error. Please try again.', 'error');
            setModalFormDisabled(false);
            hideModalLoadingState();
        }
    }

    // ========================================================================
    // Helper Functions
    // ========================================================================

    function showModalMessage(message, type = 'error') {
        if (!modalMessage) return;
        modalMessage.className = 'mb-4 p-4 rounded-lg flex items-start gap-3';

        const icons = {
            error: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>',
            success: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>',
            info: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>'
        };

        const colors = {
            error: 'bg-red-50 border border-red-200',
            success: 'bg-green-50 border border-green-200',
            info: 'bg-blue-50 border border-blue-200'
        };

        const textColors = {
            error: 'text-red-800',
            success: 'text-green-800',
            info: 'text-blue-800'
        };

        modalMessage.classList.add(...colors[type].split(' '));
        modalMessage.innerHTML = `${icons[type]}<p class="text-sm ${textColors[type]}">${message}</p>`;
        modalMessage.classList.remove('hidden');
    }

    function hideModalMessage() {
        if (modalMessage) {
            modalMessage.classList.add('hidden');
            modalMessage.innerHTML = '';
        }
    }

    function setModalFormDisabled(disabled) {
        if (saveMetadataBtn) saveMetadataBtn.disabled = disabled;
        if (modalDisplayName) modalDisplayName.disabled = disabled;
        if (modalDescription) modalDescription.disabled = disabled;
    }

    function showModalLoadingState() {
        if (!saveMetadataBtn) return;
        const originalHTML = saveMetadataBtn.innerHTML;
        saveMetadataBtn.setAttribute('data-original-html', originalHTML);
        saveMetadataBtn.innerHTML = `
            <svg class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Saving...</span>
        `;
    }

    function hideModalLoadingState() {
        if (!saveMetadataBtn) return;
        const originalHTML = saveMetadataBtn.getAttribute('data-original-html');
        if (originalHTML) {
            saveMetadataBtn.innerHTML = originalHTML;
        }
    }

    // ========================================================================
    // Event Listeners
    // ========================================================================

    if (closeModalBtn) closeModalBtn.addEventListener('click', closeEditModal);
    if (cancelModalBtn) cancelModalBtn.addEventListener('click', closeEditModal);
    if (editForm) editForm.addEventListener('submit', handleMetadataUpdate);
    if (editModal) {
        editModal.addEventListener('click', (e) => {
            if (e.target === editModal) closeEditModal();
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && editModal && !editModal.classList.contains('hidden')) {
            closeEditModal();
        }
    });

    // ========================================================================
    // Expose Public API
    // ========================================================================
    window.RolesMetadata = {
        handleEditClick,
        openEditModal,
        closeEditModal
    };

    console.log('✅ Roles Metadata Module - Ready');

})();