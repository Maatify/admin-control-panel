/**
 * Admin Emails Management Page
 * Handles display, add, verify, replace, fail, and restart operations
 * Follows same patterns as sessions.js
 */

document.addEventListener('DOMContentLoaded', () => {
    // ========================================================================
    // Configuration & State
    // ========================================================================

    // Get admin ID from URL (e.g., /admins/10/emails)
    const pathParts = window.location.pathname.split('/');
    const adminIdIndex = pathParts.indexOf('admins') + 1;
    const adminId = pathParts[adminIdIndex];

    if (!adminId || isNaN(adminId)) {
        showAlert('d', 'Invalid admin ID');
        return;
    }

    // DOM Elements
    const addEmailForm = document.getElementById('add-email-form');
    const newEmailInput = document.getElementById('new-email-input');
    const addEmailBtn = document.getElementById('add-email-btn');
    const addEmailMessage = document.getElementById('add-email-message');
    const emailsContainer = document.getElementById('emails-container');
    const statusBtns = document.querySelectorAll('[data-status]');

    // State
    let emails = [];
    let currentStatusFilter = 'all';

    // ========================================================================
    // Initialize
    // ========================================================================
    init();

    function init() {
        console.log('🚀 INIT - Admin Emails Page:', {
            admin_id: adminId,
            url: window.location.pathname,
            timestamp: new Date().toISOString()
        });

        loadEmails();
        setupEventListeners();

        console.log('✅ Event listeners attached');
    }

    // ========================================================================
    // Event Listeners
    // ========================================================================
    function setupEventListeners() {
        // Add email form
        if (addEmailForm) {
            addEmailForm.addEventListener('submit', handleAddEmail);
        }

        // Status filter buttons
        statusBtns.forEach(btn => {
            btn.addEventListener('click', handleStatusFilter);
        });

        // Email action buttons (delegated)
        if (emailsContainer) {
            emailsContainer.addEventListener('click', handleEmailAction);
        }
    }

    // ========================================================================
    // Load Emails
    // ========================================================================
    async function loadEmails() {
        showLoadingState();

        console.log('📤 REQUEST - Load Emails:', {
            url: `/api/admins/${adminId}/emails`,
            method: 'GET',
            admin_id: adminId
        });

        try {
            const response = await fetch(`/api/admins/${adminId}/emails`, {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include'
            });

            console.log('📥 RESPONSE Status:', response.status, response.statusText);

            if (!response.ok) {
                const errorData = await response.json().catch(() => null);
                console.error('❌ ERROR Response:', {
                    status: response.status,
                    statusText: response.statusText,
                    data: errorData
                });
                throw new Error(`HTTP ${response.status}: ${errorData?.message || response.statusText}`);
            }

            const data = await response.json();
            console.log('✅ SUCCESS - Full Response:', JSON.stringify(data, null, 2));
            console.log('📊 Response Structure:', {
                admin_id: data.admin_id,
                display_name: data.display_name,
                items_count: data.items?.length || 0,
                items: data.items
            });

            // Store emails from correct response structure
            emails = data.items || [];

            console.log(`📧 Loaded ${emails.length} email(s)`);

            renderEmails();

        } catch (error) {
            console.error('❌ EXCEPTION - Load Emails Failed:', {
                error: error.message,
                stack: error.stack
            });
            showErrorState('Failed to load emails. Please try again.');
        }
    }

    // ========================================================================
    // Add Email
    // ========================================================================
    async function handleAddEmail(e) {
        e.preventDefault();

        const email = newEmailInput.value.trim();

        if (!email) {
            showAddEmailMessage('Email address is required.', 'error');
            return;
        }

        if (!isValidEmail(email)) {
            showAddEmailMessage('Please enter a valid email address.', 'error');
            return;
        }

        // Disable form during submission
        setAddFormDisabled(true);
        showAddEmailMessage('Adding email...', 'info');

        const requestBody = { email: email };
        console.log('📤 REQUEST - Add Email:', {
            url: `/api/admins/${adminId}/emails`,
            method: 'POST',
            body: requestBody
        });

        try {
            const response = await fetch(`/api/admins/${adminId}/emails`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(requestBody)
            });

            console.log('📥 RESPONSE Status:', response.status, response.statusText);

            const data = await response.json().catch(() => null);

            if (data) {
                console.log('📥 RESPONSE Body:', JSON.stringify(data, null, 2));
            }

            // Handle Step-Up required (2FA verification)
            if (response.status === 403) {
                // ✅ Use ErrorNormalizer Bridge
                const stepUp = window.ErrorNormalizer.getLegacyStepUpView(data);
                if (stepUp) {
                    const scope = stepUp.scope || 'admin.email.add';
                    await window.ErrorNormalizer.redirectToStepUp(scope);
                    return;
                }
            }

            if (!response.ok) {
                const errorMsg = data && data.message ? data.message : 'Failed to add email.';
                console.error('❌ ERROR - Add Email Failed:', {
                    status: response.status,
                    message: errorMsg,
                    full_response: data
                });
                showAddEmailMessage(errorMsg, 'error');
                setAddFormDisabled(false);
                return;
            }

            // Success
            console.log('✅ SUCCESS - Email Added:', {
                admin_id: data.admin_id,
                emailAdded: data.emailAdded
            });

            showAddEmailMessage('Email added successfully!', 'success');
            newEmailInput.value = '';

            // Reload emails list
            await loadEmails();

            // Clear success message after 3 seconds
            setTimeout(() => {
                hideAddEmailMessage();
            }, 3000);

        } catch (error) {
            console.error('❌ EXCEPTION - Add Email Failed:', {
                error: error.message,
                stack: error.stack
            });
            showAddEmailMessage('Network error. Please try again.', 'error');
        } finally {
            setAddFormDisabled(false);
        }
    }

    // ========================================================================
    // Email Actions (Verify, Replace, Fail, Restart)
    // ========================================================================
    async function handleEmailAction(e) {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;

        const action = btn.getAttribute('data-action');
        const emailId = btn.getAttribute('data-email-id');
        const emailAddress = btn.getAttribute('data-email-address');

        if (!emailId) return;

        // Confirmation dialogs
        let confirmMsg = '';
        switch(action) {
            case 'verify':
                confirmMsg = `Mark email "${emailAddress}" as verified?`;
                break;
            case 'replace':
                confirmMsg = `Mark email "${emailAddress}" as replaced?`;
                break;
            case 'fail':
                confirmMsg = `Mark email "${emailAddress}" as failed?`;
                break;
            case 'restart':
                confirmMsg = `Restart verification for "${emailAddress}"?`;
                break;
            default:
                return;
        }

        if (!confirm(confirmMsg)) return;

        // Call appropriate API
        await performEmailAction(action, emailId, emailAddress);
    }

    async function performEmailAction(action, emailId, emailAddress) {
        let endpoint = '';
        switch(action) {
            case 'verify':
                endpoint = `/api/admin-emails/${emailId}/verify`;
                break;
            case 'replace':
                endpoint = `/api/admin-emails/${emailId}/replace`;
                break;
            case 'fail':
                endpoint = `/api/admin-emails/${emailId}/fail`;
                break;
            case 'restart':
                endpoint = `/api/admin-emails/${emailId}/restart-verification`;
                break;
            default:
                console.error('❌ Invalid action:', action);
                return;
        }

        console.log(`📤 REQUEST - ${action.toUpperCase()} Email:`, {
            action: action,
            email_id: emailId,
            email_address: emailAddress,
            endpoint: endpoint,
            method: 'POST'
        });

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include'
            });

            console.log('📥 RESPONSE Status:', response.status, response.statusText);

            const data = await response.json().catch(() => null);

            if (data) {
                console.log('📥 RESPONSE Body:', JSON.stringify(data, null, 2));
            }

            // Handle Step-Up required
            if (response.status === 403) {
                // ✅ Use ErrorNormalizer Bridge
                const stepUp = window.ErrorNormalizer.getLegacyStepUpView(data);
                if (stepUp) {
                    const scope = stepUp.scope || `admin.email.${action}`;
                    await window.ErrorNormalizer.redirectToStepUp(scope);
                    return;
                }
            }

            if (!response.ok) {
                const errorMsg = data && data.message ? data.message : `Failed to ${action} email.`;
                console.error(`❌ ERROR - ${action.toUpperCase()} Failed:`, {
                    status: response.status,
                    message: errorMsg,
                    full_response: data
                });
                showAlert('d', errorMsg);
                return;
            }

            // Success
            console.log(`✅ SUCCESS - Email ${action.toUpperCase()}:`, {
                email_id: data.email_id,
                status: data.status,
                action: action
            });

            showAlert('s', `Email ${action} successful!`);

            // Reload emails list
            await loadEmails();

        } catch (error) {
            console.error(`❌ EXCEPTION - ${action.toUpperCase()} Failed:`, {
                action: action,
                email_id: emailId,
                error: error.message,
                stack: error.stack
            });
            showAlert('d', 'Network error. Please try again.');
        }
    }

    // ========================================================================
    // Render Emails
    // ========================================================================
    function renderEmails() {
        if (!emailsContainer) return;

        console.log('🎨 RENDER - Emails List:', {
            total_emails: emails.length,
            current_filter: currentStatusFilter,
            timestamp: new Date().toISOString()
        });

        // Filter emails by status
        let filteredEmails = emails;
        if (currentStatusFilter !== 'all') {
            filteredEmails = emails.filter(email =>
                email.status.toLowerCase() === currentStatusFilter.toLowerCase()
            );
        }

        console.log('📊 Filtered Results:', {
            filter: currentStatusFilter,
            total: emails.length,
            filtered: filteredEmails.length,
            statuses: emails.map(e => e.status)
        });

        if (filteredEmails.length === 0) {
            console.log('ℹ️ No emails to display');
            emailsContainer.innerHTML = `
                <div class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">No emails found</p>
                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">
                        ${currentStatusFilter !== 'all'
                ? 'Try selecting a different status filter'
                : 'Add a new email address to get started'}
                    </p>
                </div>
            `;
            return;
        }

        const emailsHtml = filteredEmails.map(email => renderEmailCard(email)).join('');
        emailsContainer.innerHTML = emailsHtml;

        console.log(`✅ Rendered ${filteredEmails.length} email card(s)`);
    }

    function renderEmailCard(email) {
        const status = email.status.toLowerCase();

        // Get capabilities from window object (injected by Twig)
        const capabilities = window.AdminEmailsCapabilities || {};

        // Status badge configuration
        let statusBadge = '';
        switch(status) {
            case 'pending':
                statusBadge = `
                    <span class="bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300 px-3 py-1 rounded-full text-xs font-medium uppercase tracking-wide flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Pending
                    </span>
                `;
                break;
            case 'verified':
                statusBadge = `
                    <span class="bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 px-3 py-1 rounded-full text-xs font-medium uppercase tracking-wide flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Verified
                    </span>
                `;
                break;
            case 'failed':
                statusBadge = `
                    <span class="bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300 px-3 py-1 rounded-full text-xs font-medium uppercase tracking-wide flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                        Failed
                    </span>
                `;
                break;
            case 'replaced':
                statusBadge = `
                    <span class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 px-3 py-1 rounded-full text-xs font-medium uppercase tracking-wide flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Replaced
                    </span>
                `;
                break;
            default:
                statusBadge = `<span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-xs font-medium">${email.status}</span>`;
        }

        // Action buttons based on status AND capabilities
        let actionButtons = '';

        if (status === 'pending') {
            const buttons = [];

            // Show "Verify" button only if user has can_verify capability
            if (capabilities.can_verify) {
                buttons.push(`
                    <button 
                        data-action="verify" 
                        data-email-id="${email.email_id}" 
                        data-email-address="${email.email}"
                        class="inline-flex items-center gap-1 px-3 py-1 bg-green-600 text-white text-xs rounded-md hover:bg-green-700 transition-colors duration-200"
                        title="Mark as verified">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Verify
                    </button>
                `);
            }

            // Show "Fail" button only if user has can_fail capability
            if (capabilities.can_fail) {
                buttons.push(`
                    <button 
                        data-action="fail" 
                        data-email-id="${email.email_id}" 
                        data-email-address="${email.email}"
                        class="inline-flex items-center gap-1 px-3 py-1 bg-red-600 text-white text-xs rounded-md hover:bg-red-700 transition-colors duration-200"
                        title="Mark as failed">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                        Fail
                    </button>
                `);
            }

            actionButtons = buttons.join('');
        } else if (status === 'verified') {
            // Show "Replace" button only if user has can_replace capability
            if (capabilities.can_replace) {
                actionButtons = `
                    <button 
                        data-action="replace" 
                        data-email-id="${email.email_id}" 
                        data-email-address="${email.email}"
                        class="inline-flex items-center gap-1 px-3 py-1 bg-orange-600 text-white text-xs rounded-md hover:bg-orange-700 transition-colors duration-200"
                        title="Mark as replaced">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Replace
                    </button>
                `;
            }
        } else if (status === 'failed' || status === 'replaced') {
            // Show "Restart" button only if user has can_restart capability
            if (capabilities.can_restart) {
                actionButtons = `
                    <button 
                        data-action="restart" 
                        data-email-id="${email.email_id}" 
                        data-email-address="${email.email}"
                        class="inline-flex items-center gap-1 px-3 py-1 bg-blue-600 text-white text-xs rounded-md hover:bg-blue-700 transition-colors duration-200"
                        title="Restart verification">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Restart
                    </button>
                `;
            }
        }

        // Verified date (if available)
        const verifiedInfo = email.verified_at
            ? `
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    <span>Verified: ${email.verified_at}</span>
                </div>
            `
            : '';

        return `
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200 bg-gray-50 dark:bg-gray-800 dark:text-gray-100 dark:border-gray-700">
                <div class="flex items-start justify-between gap-4">
                    <!-- Left: Email info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="flex items-center justify-center w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-full flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-600 dark:text-blue-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-base font-mono font-medium text-gray-900 dark:text-gray-100 truncate">${email.email}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">ID: #${email.email_id}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-4 mt-2 dark:text-gray-100">
                            ${statusBadge}
                            ${verifiedInfo}
                        </div>
                    </div>

                    <!-- Right: Actions -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        ${actionButtons}
                    </div>
                </div>
            </div>
        `;
    }

    // ========================================================================
    // Status Filter
    // ========================================================================
    function handleStatusFilter(e) {
        const btn = e.target;
        const status = btn.getAttribute('data-status');

        console.log('🔍 FILTER - Status Changed:', {
            from: currentStatusFilter,
            to: status
        });

        // Update current filter
        currentStatusFilter = status;

        // Update button styles
        statusBtns.forEach(b => {
            b.classList.remove('bg-blue-600', 'text-white');
            b.classList.add('hover:bg-blue-400', 'hover:text-white');
        });

        btn.classList.add('bg-blue-600', 'text-white');
        btn.classList.remove('hover:bg-blue-400', 'hover:text-white');

        // Re-render filtered emails
        renderEmails();
    }

    // ========================================================================
    // UI Helper Functions
    // ========================================================================
    function showLoadingState() {
        if (emailsContainer) {
            emailsContainer.innerHTML = `
                <div class="flex flex-col items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
                    <p class="text-gray-600 dark:text-gray-400">Loading emails...</p>
                </div>
            `;
        }
    }

    function showErrorState(message) {
        if (emailsContainer) {
            emailsContainer.innerHTML = `
                <div class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-16 h-16 text-red-300 dark:text-red-900/40 mx-auto mb-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <p class="text-red-600 dark:text-red-400 text-lg font-medium mb-2">Error</p>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">${message}</p>
                    <button onclick="location.reload()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Retry
                    </button>
                </div>
            `;
        }
    }

    function showAddEmailMessage(message, type = 'error') {
        if (!addEmailMessage) return;

        addEmailMessage.className = 'mb-4 p-4 rounded-lg flex items-start gap-3';

        if (type === 'error') {
            addEmailMessage.classList.add('bg-red-50', 'border', 'border-red-200', 'dark:bg-red-900/20', 'dark:border-red-800');
            addEmailMessage.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <p class="text-sm text-red-800 dark:text-red-300">${message}</p>
            `;
        } else if (type === 'success') {
            addEmailMessage.classList.add('bg-green-50', 'border', 'border-green-200', 'dark:bg-green-900/20', 'dark:border-green-800');
            addEmailMessage.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <p class="text-sm text-green-800 dark:text-green-300">${message}</p>
            `;
        } else if (type === 'info') {
            addEmailMessage.classList.add('bg-blue-50', 'border', 'border-blue-200', 'dark:bg-blue-900/20', 'dark:border-blue-800');
            addEmailMessage.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                <p class="text-sm text-blue-800 dark:text-blue-300">${message}</p>
            `;
        }

        addEmailMessage.classList.remove('hidden');
    }

    function hideAddEmailMessage() {
        if (addEmailMessage) {
            addEmailMessage.classList.add('hidden');
            addEmailMessage.textContent = '';
        }
    }

    function setAddFormDisabled(disabled) {
        if (addEmailBtn) addEmailBtn.disabled = disabled;
        if (newEmailInput) newEmailInput.disabled = disabled;
    }

    // ========================================================================
    // Validation Helpers
    // ========================================================================
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // ========================================================================
    // Alert Helper (using global showAlert if available)
    // ========================================================================
    function showAlert(type, message) {
        if (typeof window.showAlert === 'function') {
            window.showAlert(type, message);
        } else {
            console.log(`[${type}] ${message}`);
        }
    }
});