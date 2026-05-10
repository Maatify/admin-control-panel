document.addEventListener('DOMContentLoaded', () => {
    const capabilities = window.AdminProfileCapabilities || {};
    const adminId = window.AdminProfileAdminId;

    const tempBtn = document.getElementById('btn-generate-temp-password');
    const reset2faBtn = document.getElementById('btn-reset-2fa');
    const tempModal = document.getElementById('temp-password-result');
    const tempValue = document.getElementById('temp-password-value');
    const tempClose = document.getElementById('temp-password-close');

    if (tempClose) {
        tempClose.addEventListener('click', () => {
            if (tempValue) tempValue.textContent = '';
            if (tempModal) tempModal.classList.add('hidden');
        });
    }

    if (tempBtn && capabilities.can_reset_temp_password) {
        tempBtn.addEventListener('click', async () => {
            // if (!confirm('')) {
            //     return;
            // }

            const ok = await appConfirm({
                title: "are you sure?",
                message: "Generate a temporary password for this admin? The current password will be replaced.",
                type: "danger"
            })
            if (!ok) {
                return;
            }

            try {
                const response = await fetch(`/api/admins/${adminId}/password/reset-temp`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include'
                });

                const data = await response.json().catch(() => null);

                if (response.status === 403) {
                    const stepUp = window.ErrorNormalizer?.getLegacyStepUpView(data);
                    if (stepUp) {
                        const scope = encodeURIComponent(stepUp.scope || 'admin.password.reset_temp');
                        const returnTo = encodeURIComponent(window.location.pathname);
                        window.location.href = `/2fa/verify?scope=${scope}&return_to=${returnTo}`;
                        return;
                    }
                }

                if (!response.ok) {
                    showAlert('d', data?.message || 'Failed to generate temporary password.');
                    return;
                }

                if (!data || typeof data.temp_password !== 'string' || data.temp_password.length === 0) {
                    showAlert('d', 'Temporary password response is invalid.');
                    return;
                }

                if (tempValue) tempValue.textContent = data.temp_password;
                if (tempModal) tempModal.classList.remove('hidden');
                showAlert('s', 'Temporary password generated. Copy it now.');
            } catch (e) {
                showAlert('d', 'Network error. Please try again.');
            }
        });
    }

    if (reset2faBtn && capabilities.can_reset_2fa) {
        reset2faBtn.addEventListener('click', async () => {
            // if (!confirm('Reset 2FA for this admin? This will remove the existing 2FA setup and require re-enrollment.')) {
            //     return;
            // }
            const ok = await appConfirm({
                title: "are you sure?",
                message: "Reset 2FA for this admin? This will remove the existing 2FA setup and require re-enrollment.",
                type: "danger"
            })
            if (!ok) {
                return;
            }
            try {
                const response = await fetch(`/api/admins/${adminId}/2fa/reset`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include'
                });

                const data = await response.json().catch(() => null);

                if (response.status === 403) {
                    const stepUp = window.ErrorNormalizer?.getLegacyStepUpView(data);
                    if (stepUp) {
                        const scope = encodeURIComponent(stepUp.scope || 'admin.2fa.reset');
                        const returnTo = encodeURIComponent(window.location.pathname);
                        window.location.href = `/2fa/verify?scope=${scope}&return_to=${returnTo}`;
                        return;
                    }
                }

                if (!response.ok) {
                    showAlert('d', data?.message || 'Failed to reset 2FA.');
                    return;
                }

                showAlert('s', '2FA reset successfully.');
            } catch (e) {
                showAlert('d', 'Network error. Please try again.');
            }
        });
    }

});

function handleCopyClick(e) {
    const btn = e.target.closest('.copy-btn');
    // if (!btn) return;

    // const btn = e.target;
    const targetId = btn.getAttribute('data-copy-target');
console.log(btn)
console.log(targetId)
    const targetElement = document.getElementById(targetId);
    console.log(targetElement)
    if (targetElement) {
        const textToCopy = targetElement.textContent;
        copyToClipboard(textToCopy, btn);
    }
}

function copyToClipboard(text, btn) {
    console.log(text)
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

function showCopyNotification() {
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
