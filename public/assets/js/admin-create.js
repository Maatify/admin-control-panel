// public/assets/js/admin-create.js
(function() {
    'use strict';

    let modalInstance = null;
    let successCallback = null;

    const modalEl = document.getElementById('modal-create-admin');
    const form = document.getElementById('form-create-admin');
    const submitBtn = document.getElementById('btn-submit-create-admin');
    const errorContainer = document.getElementById('create-admin-errors');

    // Field Error Elements
    const emailError = document.getElementById('error-create-admin-email');
    const passwordError = document.getElementById('error-create-admin-password');
    const emailInput = document.getElementById('create-admin-email');
    const passwordInput = document.getElementById('create-admin-password');

    window.AdminCreate = {
        open: function() {
            if (!modalInstance && window.bootstrap) {
                modalInstance = new bootstrap.Modal(modalEl);
            }
            if (modalInstance) {
                resetForm();
                modalInstance.show();
            } else {
                console.error('Bootstrap Modal not initialized');
            }
        },

        onSuccess: function(callback) {
            successCallback = callback;
        }
    };

    if (form) {
        form.addEventListener('submit', handleFormSubmit);
    }

    async function handleFormSubmit(e) {
        e.preventDefault();

        // Reset previous errors
        clearErrors();
        setLoading(true);

        const formData = {
            email: emailInput.value.trim(),
            password: passwordInput.value
        };

        try {
            const response = await fetch('/api/admins/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (response.ok) {
                // Success (201)
                handleSuccess();
            } else {
                // Error (409, 422, 500)
                handleError(response.status, result);
            }

        } catch (error) {
            showGlobalError('Network or system error occurred. Please try again.');
        } finally {
            setLoading(false);
        }
    }

    function handleSuccess() {
        if (modalInstance) {
            modalInstance.hide();
        }
        resetForm();

        // Fire callback if registered
        if (typeof successCallback === 'function') {
            successCallback();
        }
    }

    function handleError(status, result) {
        if (status === 422 && result.errors) {
            // Validation Errors
            if (result.errors.email) {
                showFieldError(emailInput, emailError, result.errors.email[0]);
            }
            if (result.errors.password) {
                showFieldError(passwordInput, passwordError, result.errors.password[0]);
            }
            if (result.errors.general) {
                showGlobalError(result.errors.general[0]);
            }
        } else if (status === 409) {
            // Conflict
            showGlobalError(result.message || 'Admin already exists.');
        } else {
            // Server Error or Unknown
            showGlobalError(result.message || 'An unexpected error occurred.');
        }
    }

    function showFieldError(input, errorEl, message) {
        input.classList.add('is-invalid');
        errorEl.textContent = message;
    }

    function showGlobalError(message) {
        errorContainer.textContent = message;
        errorContainer.classList.remove('d-none');
    }

    function clearErrors() {
        errorContainer.classList.add('d-none');
        errorContainer.textContent = '';

        emailInput.classList.remove('is-invalid');
        passwordInput.classList.remove('is-invalid');

        emailError.textContent = '';
        passwordError.textContent = '';
    }

    function resetForm() {
        form.reset();
        clearErrors();
    }

    function setLoading(isLoading) {
        if (isLoading) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...';
        } else {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Create Admin';
        }
    }
})();
