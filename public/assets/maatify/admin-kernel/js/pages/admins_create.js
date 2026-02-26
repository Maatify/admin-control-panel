/**
 * Create Admin Page - JavaScript Handler
 * Handles form submission, validation, API calls, and UI updates
 */

document.addEventListener('DOMContentLoaded', () => {
    // ========================================================================
    // DOM Elements
    // ========================================================================
    const form = document.getElementById('create-admin-form');
    const emailInput = document.getElementById('admin-email');
    const displayNameInput = document.getElementById('admin-display-name');
    const messageBox = document.getElementById('create-admin-message');
    const submitBtn = document.getElementById('submit-btn');
    const formContainer = document.getElementById('form-container');
    const resultBox = document.getElementById('create-admin-result');
    const createAnotherBtn = document.getElementById('create-another-btn');

    // ========================================================================
    // Validation
    // ========================================================================
    if (!form || !emailInput || !messageBox) {
        console.error('❌ Required elements not found');
        return;
    }

    // ========================================================================
    // Initialize
    // ========================================================================
    init();

    function init() {
        setupEventListeners();
    }

    // ========================================================================
    // Event Listeners
    // ========================================================================
    function setupEventListeners() {
        // Form submission
        form.addEventListener('submit', handleFormSubmit);

        // Create another button
        if (createAnotherBtn) {
            createAnotherBtn.addEventListener('click', handleCreateAnother);
        }

        // Copy buttons
        document.addEventListener('click', handleCopyClick);
    }

    // ========================================================================
    // Form Submission Handler
    // ========================================================================
    async function handleFormSubmit(e) {
        e.preventDefault();

        // Hide any previous messages
        hideMessage();

        // Get and validate inputs
        const email = emailInput.value.trim();
        const displayName = displayNameInput.value.trim();

        if (!email) {
            showMessage('Email is required.', 'error');
            return;
        }

        if (!displayName) {
            showMessage('Display name is required.', 'error');
            return;
        }

        // Validate email format
        if (!isValidEmail(email)) {
            showMessage('Please enter a valid email address.', 'error');
            return;
        }

        // Disable form during submission
        setFormDisabled(true);
        showLoadingState();

        try {
            const response = await fetch('/api/admins/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    email: email,
                    display_name: displayName
                })
            });

            const data = await response.json().catch(() => null);

            // Handle Step-Up required (2FA verification)
            if (response.status === 403) {
                // ✅ Use ErrorNormalizer Bridge
                const stepUp = window.ErrorNormalizer.getLegacyStepUpView(data);
                if (stepUp) {
                    const scope = stepUp.scope || 'admins.create';
                    await window.ErrorNormalizer.redirectToStepUp(scope);
                    return;
                }
            }

            // Handle error response
            if (!response.ok) {
                const errorMsg = data && data.message ? data.message : 'Failed to create admin.';
                showMessage(errorMsg, 'error');
                setFormDisabled(false);
                hideLoadingState();
                return;
            }

            // Success - Show result
            showSuccessResult(data);

        } catch (err) {
            console.error('❌ Network error:', err);
            showMessage('Network error. Please try again.', 'error');
            setFormDisabled(false);
            hideLoadingState();
        }
    }

    // ========================================================================
    // Success Handler
    // ========================================================================
    function showSuccessResult(data) {
        // Hide form
        formContainer.classList.add('hidden');

        // Populate result data
        const adminIdElement = document.getElementById('result-admin-id');
        const tempPasswordElement = document.getElementById('result-temp-password');

        if (adminIdElement) {
            adminIdElement.textContent = data.admin_id || 'N/A';
        }

        if (tempPasswordElement) {
            tempPasswordElement.textContent = data.temp_password || 'N/A';
        }

        // Show result box
        resultBox.classList.remove('hidden');

        // Scroll to result
        setTimeout(() => {
            resultBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    }

    // ========================================================================
    // Create Another Handler
    // ========================================================================
    function handleCreateAnother() {
        // Reset form
        emailInput.value = '';
        displayNameInput.value = '';
        emailInput.disabled = false;
        displayNameInput.disabled = false;
        submitBtn.disabled = false;
        hideMessage();
        hideLoadingState();

        // Show form, hide result
        formContainer.classList.remove('hidden');
        resultBox.classList.add('hidden');

        // Scroll to form
        setTimeout(() => {
            formContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            displayNameInput.focus();
        }, 100);
    }

    // ========================================================================
    // Copy to Clipboard Handler
    // ========================================================================
    function handleCopyClick(e) {
        const btn = e.target.closest('.copy-btn');
        if (!btn) return;

        const targetId = btn.getAttribute('data-copy-target');
        const targetElement = document.getElementById(targetId);

        if (targetElement) {
            const textToCopy = targetElement.textContent;
            copyToClipboard(textToCopy, btn);
        }
    }

    function copyToClipboard(text, btn) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                showCopyFeedback(btn);
            }).catch(err => {
                console.error('Failed to copy:', err);
                fallbackCopy(text, btn);
            });
        } else {
            fallbackCopy(text, btn);
        }
    }

    function fallbackCopy(text, btn) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();

        try {
            document.execCommand('copy');
            showCopyFeedback(btn);
        } catch (err) {
            console.error('Fallback copy failed:', err);
        }

        document.body.removeChild(textarea);
    }

    function showCopyFeedback(btn) {
        // Store original HTML
        const originalHTML = btn.innerHTML;

        // Change button to checkmark
        btn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        `;
        btn.classList.add('text-green-600');

        // Show notification
        showCopyNotification(btn);

        // Revert after 2 seconds
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('text-green-600');
        }, 2000);
    }

    function showCopyNotification(element) {
        // Create notification
        const notification = document.createElement('div');
        notification.textContent = 'Copied!';
        notification.className = 'fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg text-sm font-medium copy-notification z-50';
        document.body.appendChild(notification);

        // Remove after animation
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 2000);
    }

    // ========================================================================
    // UI Helper Functions
    // ========================================================================
    function showMessage(message, type = 'error') {
        messageBox.className = 'mb-4 p-4 rounded-lg flex items-start gap-3';

        if (type === 'error') {
            messageBox.classList.add('bg-red-50', 'border', 'border-red-200', 'dark:bg-red-900/20', 'dark:border-red-800');
            messageBox.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <p class="text-sm text-red-800 dark:text-red-300">${message}</p>
            `;
        } else if (type === 'success') {
            messageBox.classList.add('bg-green-50', 'border', 'border-green-200', 'dark:bg-green-900/20', 'dark:border-green-800');
            messageBox.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <p class="text-sm text-green-800 dark:text-green-300">${message}</p>
            `;
        }

        messageBox.classList.remove('hidden');
    }

    function hideMessage() {
        messageBox.classList.add('hidden');
        messageBox.textContent = '';
    }

    function setFormDisabled(disabled) {
        submitBtn.disabled = disabled;
        emailInput.disabled = disabled;
        displayNameInput.disabled = disabled;
    }

    function showLoadingState() {
        const originalHTML = submitBtn.innerHTML;
        submitBtn.setAttribute('data-original-html', originalHTML);

        submitBtn.innerHTML = `
            <svg class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Creating...</span>
        `;
    }

    function hideLoadingState() {
        const originalHTML = submitBtn.getAttribute('data-original-html');
        if (originalHTML) {
            submitBtn.innerHTML = originalHTML;
        }
    }

    // ========================================================================
    // Validation Helpers
    // ========================================================================
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // ========================================================================
    // Alert Helper (if available globally)
    // ========================================================================
    function showAlert(type, message) {
        if (typeof window.showAlert === 'function') {
            window.showAlert(type, message);
        } else {
            console.log(`[${type}] ${message}`);
        }
    }
});