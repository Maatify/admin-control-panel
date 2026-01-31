/**
 * Roles Page - Create, Rename, Toggle Module
 * Handles role creation, renaming, and activation toggle
 */

(function() {
    'use strict';

    console.log('➕✏️🔄 Roles Create/Rename/Toggle Module - Initializing');

    if (!window.RolesCore) {
        console.error('❌ RolesCore not found');
        return;
    }

    const { capabilities, loadRoles, showAlert } = window.RolesCore;

    // ========================================================================
    // CREATE ROLE - DOM Elements
    // ========================================================================
    const createModal = document.getElementById('create-role-modal');
    const createForm = document.getElementById('create-role-form');
    const closeCreateModalBtn = document.getElementById('close-create-modal-btn');
    const cancelCreateBtn = document.getElementById('cancel-create-btn');
    const saveCreateBtn = document.getElementById('save-create-btn');
    const createModalMessage = document.getElementById('create-modal-message');
    const createRoleName = document.getElementById('create-role-name');
    const createDisplayName = document.getElementById('create-display-name');
    const createDescription = document.getElementById('create-description');

    // ========================================================================
    // CREATE ROLE - Modal Operations
    // ========================================================================

    function openCreateModal() {
        console.log('━'.repeat(60));
        console.log('➕ Create Role - Opening Modal');
        console.log('━'.repeat(60));

        if (createModal) {
            createModal.classList.remove('hidden');
            console.log('  ├─ Modal visible');
            hideCreateModalMessage();

            // Reset form
            if (createForm) {
                createForm.reset();
                console.log('  ├─ Form reset');
            }

            // ✅ Reset loading state and enable form
            setCreateFormDisabled(false);
            hideCreateLoadingState();
            console.log('  ├─ Loading state reset');

            setTimeout(() => {
                if (createRoleName) {
                    createRoleName.focus();
                    console.log('  └─ Focus set on name field');
                }
            }, 100);
        }
    }

    function closeCreateModal() {
        console.log('🚪 Closing create modal');
        if (createModal) {
            createModal.classList.add('hidden');
            console.log('  ├─ Modal hidden');
        }

        if (createForm) {
            createForm.reset();
            console.log('  ├─ Form reset');
        }

        // ✅ Reset loading state and enable form
        setCreateFormDisabled(false);
        hideCreateLoadingState();
        console.log('  ├─ Loading state reset');

        hideCreateModalMessage();
        console.log('  └─ Messages cleared');
    }

    async function handleCreateRoleSubmit(e) {
        e.preventDefault();

        console.log('━'.repeat(60));
        console.log('➕ Create Role - Starting');
        console.log('━'.repeat(60));

        hideCreateModalMessage();

        // Get form values
        const name = createRoleName.value.trim();
        const displayName = createDisplayName.value.trim();
        const description = createDescription.value.trim();

        console.log('📝 Form values:');
        console.log('  ├─ name:', name);
        console.log('  ├─ display_name:', displayName || '(empty)');
        console.log('  └─ description:', description || '(empty)');

        // Validate name format
        const namePattern = /^[a-z][a-z0-9_.-]*$/;
        if (!namePattern.test(name)) {
            console.error('❌ Invalid name format');
            console.log('━'.repeat(60));
            showCreateModalMessage('Invalid name format. Must start with lowercase letter and contain only lowercase letters, numbers, dots, dashes, and underscores.', 'error');
            return;
        }

        if (name.length < 3 || name.length > 190) {
            console.error('❌ Invalid name length');
            console.log('━'.repeat(60));
            showCreateModalMessage('Name must be between 3 and 190 characters.', 'error');
            return;
        }

        // Build request body
        const requestBody = { name };

        if (displayName) {
            requestBody.display_name = displayName;
        }

        if (description) {
            requestBody.description = description;
        }

        console.log('📤 Sending to: POST /api/roles/create');
        console.log('📦 Payload:', JSON.stringify(requestBody, null, 2));

        setCreateFormDisabled(true);
        showCreateLoadingState();

        try {
            const response = await fetch('/api/roles/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestBody)
            });

            console.log('📥 Response status:', response.status, response.statusText);

            // Handle Step-Up required (2FA verification)
            if (response.status === 403) {
                const data = await response.json().catch(() => null);
                console.log('🔐 Step-Up 2FA required');
                if (data && data.code === 'STEP_UP_REQUIRED') {
                    const scope = encodeURIComponent(data.scope || 'roles.create');
                    const returnTo = encodeURIComponent(window.location.pathname);
                    window.location.href = `/2fa/verify?scope=${scope}&return_to=${returnTo}`;
                    return;
                }
            }

            // Handle 409 Conflict (duplicate name)
            if (response.status === 409) {
                const data = await response.json().catch(() => null);
                const errorMsg = data && data.message ? data.message : 'Role with this name already exists.';
                console.error('❌ Conflict - duplicate name');
                console.error('Response data:', data);
                console.log('━'.repeat(60));
                showCreateModalMessage(errorMsg, 'error');
                setCreateFormDisabled(false);
                hideCreateLoadingState();
                return;
            }

            // Handle error response
            if (!response.ok) {
                const data = await response.json().catch(() => null);
                const errorMsg = data && data.message ? data.message : 'Failed to create role.';
                console.error('❌ Create failed:', errorMsg);
                console.error('Response data:', data);
                console.log('━'.repeat(60));
                showCreateModalMessage(errorMsg, 'error');
                setCreateFormDisabled(false);
                hideCreateLoadingState();
                return;
            }

            // Success
            let data;
            try {
                data = await response.json();
            } catch (jsonError) {
                // If response is 200 OK but empty body, treat as success
                console.log('⚠️  Response body is empty (valid for 200 OK)');
                data = {};
            }

            console.log('✅ Role created successfully');
            console.log('📊 Response data:', data);
            if (data.id) {
                console.log('  └─ New role ID:', data.id);
            }
            console.log('━'.repeat(60));

            const successMsg = data.id
                ? `Role "${name}" created successfully!`
                : `Role "${name}" created successfully!`;

            showCreateModalMessage(successMsg, 'success');

            // Wait a moment, then close modal and reload table
            setTimeout(() => {
                closeCreateModal();
                loadRoles();
                const alertMsg = data.id
                    ? `Role "${name}" created successfully (ID: ${data.id})`
                    : `Role "${name}" created successfully`;
                showAlert('s', alertMsg);
            }, 1500);

        } catch (err) {
            console.error('━'.repeat(60));
            console.error('❌ Network error');
            console.error('Error:', err);
            console.error('Stack:', err.stack);
            console.error('━'.repeat(60));
            showCreateModalMessage('Network error. Please try again.', 'error');
            setCreateFormDisabled(false);
            hideCreateLoadingState();
        }
    }

    // ========================================================================
    // CREATE ROLE - Helper Functions
    // ========================================================================

    function showCreateModalMessage(message, type = 'error') {
        if (!createModalMessage) return;
        createModalMessage.className = 'mb-4 p-4 rounded-lg flex items-start gap-3';

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

        createModalMessage.classList.add(...colors[type].split(' '));
        createModalMessage.innerHTML = `${icons[type]}<p class="text-sm ${textColors[type]}">${message}</p>`;
        createModalMessage.classList.remove('hidden');
    }

    function hideCreateModalMessage() {
        if (createModalMessage) {
            createModalMessage.classList.add('hidden');
            createModalMessage.innerHTML = '';
        }
    }

    function setCreateFormDisabled(disabled) {
        if (saveCreateBtn) saveCreateBtn.disabled = disabled;
        if (createRoleName) createRoleName.disabled = disabled;
        if (createDisplayName) createDisplayName.disabled = disabled;
        if (createDescription) createDescription.disabled = disabled;
    }

    function showCreateLoadingState() {
        if (!saveCreateBtn) return;
        const originalHTML = saveCreateBtn.innerHTML;
        saveCreateBtn.setAttribute('data-original-html', originalHTML);
        saveCreateBtn.innerHTML = `
            <svg class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Creating...</span>
        `;
    }

    function hideCreateLoadingState() {
        if (!saveCreateBtn) return;
        const originalHTML = saveCreateBtn.getAttribute('data-original-html');
        if (originalHTML) {
            saveCreateBtn.innerHTML = originalHTML;
        }
    }

    // ========================================================================
    // CREATE ROLE - Event Listeners
    // ========================================================================

    if (closeCreateModalBtn) closeCreateModalBtn.addEventListener('click', closeCreateModal);
    if (cancelCreateBtn) cancelCreateBtn.addEventListener('click', closeCreateModal);
    if (createForm) createForm.addEventListener('submit', handleCreateRoleSubmit);
    if (createModal) {
        createModal.addEventListener('click', (e) => {
            if (e.target === createModal) closeCreateModal();
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && createModal && !createModal.classList.contains('hidden')) {
            closeCreateModal();
        }
    });

    // ========================================================================
    // RENAME ROLE - DOM Elements
    // ========================================================================
    const renameModal = document.getElementById('rename-role-modal');
    const renameForm = document.getElementById('rename-role-form');
    const closeRenameModalBtn = document.getElementById('close-rename-modal-btn');
    const cancelRenameBtn = document.getElementById('cancel-rename-btn');
    const saveRenameBtn = document.getElementById('save-rename-btn');
    const renameModalMessage = document.getElementById('rename-modal-message');
    const renameRoleId = document.getElementById('rename-role-id');
    const renameCurrentName = document.getElementById('rename-current-name');
    const renameNewName = document.getElementById('rename-new-name');

    let currentRenamingRole = null;

    // ========================================================================
    // RENAME ROLE - Modal Operations
    // ========================================================================

    function handleRenameClick(btn) {
        const roleId = btn.getAttribute('data-role-id');
        const roleName = btn.getAttribute('data-role-name');

        console.log('━'.repeat(60));
        console.log('✏️ Rename Role - Opening Modal');
        console.log('━'.repeat(60));
        console.log('📌 Role Details:');
        console.log('  ├─ ID:', roleId);
        console.log('  └─ Current Name:', roleName);

        currentRenamingRole = {
            id: roleId,
            name: roleName
        };

        console.log('💾 Stored in currentRenamingRole');
        console.log('🎨 Populating modal fields');

        renameRoleId.textContent = `#${roleId}`;
        renameCurrentName.textContent = roleName;
        renameNewName.value = '';

        hideRenameModalMessage();
        console.log('✅ Modal ready');
        console.log('━'.repeat(60));

        openRenameModal();
    }

    function openRenameModal() {
        console.log('🎨 Opening rename modal');
        if (renameModal) {
            renameModal.classList.remove('hidden');
            console.log('  ├─ Modal visible');

            // ✅ Reset loading state and enable form
            setRenameFormDisabled(false);
            hideRenameLoadingState();
            console.log('  ├─ Loading state reset');

            setTimeout(() => {
                if (renameNewName) {
                    renameNewName.focus();
                    console.log('  └─ Focus set on new name field');
                }
            }, 100);
        }
    }

    function closeRenameModal() {
        console.log('🚪 Closing rename modal');
        if (renameModal) {
            renameModal.classList.add('hidden');
            console.log('  ├─ Modal hidden');
        }
        currentRenamingRole = null;
        console.log('  ├─ Cleared currentRenamingRole');

        if (renameForm) {
            renameForm.reset();
            console.log('  ├─ Form reset');
        }

        // ✅ Reset loading state and enable form
        setRenameFormDisabled(false);
        hideRenameLoadingState();
        console.log('  ├─ Loading state reset');

        hideRenameModalMessage();
        console.log('  └─ Messages cleared');
    }

    async function handleRenameSubmit(e) {
        e.preventDefault();

        if (!currentRenamingRole) {
            console.error('❌ No role being renamed');
            return;
        }

        console.log('━'.repeat(60));
        console.log('✏️ Rename Role - Starting');
        console.log('━'.repeat(60));
        console.log('⚠️  HIGH-IMPACT OPERATION WARNING');
        console.log('━'.repeat(60));

        hideRenameModalMessage();

        const newName = renameNewName.value.trim();

        console.log('📝 Values:');
        console.log('  ├─ Role ID:', currentRenamingRole.id);
        console.log('  ├─ Current name:', currentRenamingRole.name);
        console.log('  └─ New name:', newName);

        // Validate name format
        const namePattern = /^[a-z][a-z0-9_.-]*$/;
        if (!namePattern.test(newName)) {
            console.error('❌ Invalid name format');
            console.log('━'.repeat(60));
            showRenameModalMessage('Invalid name format. Must start with lowercase letter and contain only lowercase letters, numbers, dots, dashes, and underscores.', 'error');
            return;
        }

        if (newName.length < 3 || newName.length > 190) {
            console.error('❌ Invalid name length');
            console.log('━'.repeat(60));
            showRenameModalMessage('Name must be between 3 and 190 characters.', 'error');
            return;
        }

        // Check if name actually changed
        if (newName === currentRenamingRole.name) {
            console.log('ℹ️ Name unchanged - no operation needed');
            console.log('━'.repeat(60));
            showRenameModalMessage('New name is the same as current name.', 'info');
            return;
        }

        // Build request body
        const requestBody = { name: newName };

        console.log('📤 Sending to: POST /api/roles/' + currentRenamingRole.id + '/rename');
        console.log('📦 Payload:', JSON.stringify(requestBody, null, 2));

        setRenameFormDisabled(true);
        showRenameLoadingState();

        try {
            const response = await fetch(`/api/roles/${currentRenamingRole.id}/rename`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestBody)
            });

            console.log('📥 Response status:', response.status, response.statusText);

            // Handle Step-Up required (2FA verification)
            if (response.status === 403) {
                const data = await response.json().catch(() => null);
                console.log('🔐 Step-Up 2FA required');
                if (data && data.code === 'STEP_UP_REQUIRED') {
                    const scope = encodeURIComponent(data.scope || 'roles.rename');
                    const returnTo = encodeURIComponent(window.location.pathname);
                    window.location.href = `/2fa/verify?scope=${scope}&return_to=${returnTo}`;
                    return;
                }
            }

            // Handle 409 Conflict (duplicate name)
            if (response.status === 409) {
                const data = await response.json().catch(() => null);
                const errorMsg = data && data.message ? data.message : 'Role with this name already exists.';
                console.error('❌ Conflict - duplicate name');
                console.error('Response data:', data);
                console.log('━'.repeat(60));
                showRenameModalMessage(errorMsg, 'error');
                setRenameFormDisabled(false);
                hideRenameLoadingState();
                return;
            }

            // Handle error response
            if (!response.ok) {
                const data = await response.json().catch(() => null);
                const errorMsg = data && data.message ? data.message : 'Failed to rename role.';
                console.error('❌ Rename failed:', errorMsg);
                console.error('Response data:', data);
                console.log('━'.repeat(60));
                showRenameModalMessage(errorMsg, 'error');
                setRenameFormDisabled(false);
                hideRenameLoadingState();
                return;
            }

            // Success
            console.log('✅ Role renamed successfully');
            console.log('━'.repeat(60));

            showRenameModalMessage(`Role renamed from "${currentRenamingRole.name}" to "${newName}" successfully!`, 'success');

            // Wait a moment, then close modal and reload table
            setTimeout(() => {
                closeRenameModal();
                loadRoles();
                showAlert('s', `Role renamed to "${newName}" successfully`);
            }, 1500);

        } catch (err) {
            console.error('━'.repeat(60));
            console.error('❌ Network error');
            console.error('Error:', err);
            console.error('Stack:', err.stack);
            console.error('━'.repeat(60));
            showRenameModalMessage('Network error. Please try again.', 'error');
            setRenameFormDisabled(false);
            hideRenameLoadingState();
        }
    }

    // ========================================================================
    // RENAME ROLE - Helper Functions
    // ========================================================================

    function showRenameModalMessage(message, type = 'error') {
        if (!renameModalMessage) return;
        renameModalMessage.className = 'mb-4 p-4 rounded-lg flex items-start gap-3';

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

        renameModalMessage.classList.add(...colors[type].split(' '));
        renameModalMessage.innerHTML = `${icons[type]}<p class="text-sm ${textColors[type]}">${message}</p>`;
        renameModalMessage.classList.remove('hidden');
    }

    function hideRenameModalMessage() {
        if (renameModalMessage) {
            renameModalMessage.classList.add('hidden');
            renameModalMessage.innerHTML = '';
        }
    }

    function setRenameFormDisabled(disabled) {
        if (saveRenameBtn) saveRenameBtn.disabled = disabled;
        if (renameNewName) renameNewName.disabled = disabled;
    }

    function showRenameLoadingState() {
        if (!saveRenameBtn) return;
        const originalHTML = saveRenameBtn.innerHTML;
        saveRenameBtn.setAttribute('data-original-html', originalHTML);
        saveRenameBtn.innerHTML = `
            <svg class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Renaming...</span>
        `;
    }

    function hideRenameLoadingState() {
        if (!saveRenameBtn) return;
        const originalHTML = saveRenameBtn.getAttribute('data-original-html');
        if (originalHTML) {
            saveRenameBtn.innerHTML = originalHTML;
        }
    }

    // ========================================================================
    // RENAME ROLE - Event Listeners
    // ========================================================================

    if (closeRenameModalBtn) closeRenameModalBtn.addEventListener('click', closeRenameModal);
    if (cancelRenameBtn) cancelRenameBtn.addEventListener('click', closeRenameModal);
    if (renameForm) renameForm.addEventListener('submit', handleRenameSubmit);
    if (renameModal) {
        renameModal.addEventListener('click', (e) => {
            if (e.target === renameModal) closeRenameModal();
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && renameModal && !renameModal.classList.contains('hidden')) {
            closeRenameModal();
        }
    });

    // ========================================================================
    // RENAME ROLE - Placeholder (TODO)
    // ========================================================================

    window.RolesRename = {
        handleRenameClick,
        openRenameModal,
        closeRenameModal,
        handleRenameSubmit
    };

    // ========================================================================
    // TOGGLE ROLE - Implementation
    // ========================================================================

    window.RolesToggle = {
        handleToggleClick: (btn) => {
            const roleId = btn.getAttribute('data-role-id');
            const roleName = btn.getAttribute('data-role-name');
            const isActive = btn.getAttribute('data-is-active') === '1';
            const newState = !isActive;

            console.log('━'.repeat(60));
            console.log('🔄 Toggle Role - Confirmation');
            console.log('━'.repeat(60));
            console.log('📌 Target Role:');
            console.log('  ├─ ID:', roleId);
            console.log('  ├─ Name:', roleName);
            console.log('  ├─ Current state:', isActive ? 'ACTIVE' : 'DISABLED');
            console.log('  └─ New state:', newState ? 'ACTIVE' : 'DISABLED');

            const action = newState ? 'enable' : 'disable';
            const confirmMsg = `Are you sure you want to ${action} the role "${roleName}"?\n\n${
                newState
                    ? 'Enabled roles participate in authorization decisions.'
                    : 'Disabled roles are ignored during authorization.'
            }`;

            if (!confirm(confirmMsg)) {
                console.log('❌ User cancelled toggle operation');
                console.log('━'.repeat(60));
                return;
            }

            console.log('✅ User confirmed toggle');
            performToggle(roleId, roleName, newState);
        }
    };

    async function performToggle(roleId, roleName, newState) {
        console.log('━'.repeat(60));
        console.log('🔄 Toggle Role - Executing');
        console.log('━'.repeat(60));
        console.log('📤 Sending to: POST /api/roles/' + roleId + '/toggle');
        console.log('📦 Payload:', JSON.stringify({ is_active: newState }, null, 2));

        try {
            const response = await fetch(`/api/roles/${roleId}/toggle`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ is_active: newState })
            });

            console.log('📥 Response status:', response.status, response.statusText);

            if (response.status === 403) {
                const data = await response.json().catch(() => null);
                console.log('🔐 Step-Up 2FA required');
                if (data && data.code === 'STEP_UP_REQUIRED') {
                    const scope = encodeURIComponent(data.scope || 'roles.toggle');
                    const returnTo = encodeURIComponent(window.location.pathname);
                    window.location.href = `/2fa/verify?scope=${scope}&return_to=${returnTo}`;
                    return;
                }
            }

            if (!response.ok) {
                const data = await response.json().catch(() => null);
                const errorMsg = data && data.message ? data.message : 'Failed to toggle role.';
                console.error('❌ Toggle failed:', errorMsg);
                console.error('Response data:', data);
                console.log('━'.repeat(60));
                showAlert('d', errorMsg);
                return;
            }

            console.log('✅ Role toggled successfully');
            console.log('━'.repeat(60));
            showAlert('s', `Role "${roleName}" ${newState ? 'enabled' : 'disabled'} successfully`);
            loadRoles();

        } catch (err) {
            console.error('━'.repeat(60));
            console.error('❌ Network error');
            console.error('Error:', err);
            console.error('━'.repeat(60));
            showAlert('d', 'Network error. Please try again.');
        }
    }

    // ========================================================================
    // Expose Public API
    // ========================================================================
    window.RolesCreate = {
        openCreateModal,
        closeCreateModal,
        handleCreateRoleSubmit
    };

    console.log('✅ Roles Create/Rename/Toggle Module - Ready');
    console.log('  ├─ Create Role: ✅ FULLY IMPLEMENTED');
    console.log('  ├─ Rename Role: ✅ FULLY IMPLEMENTED');
    console.log('  └─ Toggle Role: ✅ FULLY IMPLEMENTED');
    console.log('━'.repeat(60));

})();