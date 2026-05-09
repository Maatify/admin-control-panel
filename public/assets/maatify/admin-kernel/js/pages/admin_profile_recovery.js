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
            if (!confirm('Generate a temporary password for this admin? The current password will be replaced.')) {
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
            if (!confirm('Reset 2FA for this admin? This will remove the existing 2FA setup and require re-enrollment.')) {
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
