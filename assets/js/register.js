document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('reseller-registration-form');
    if (!form) {
        return;
    }

    const toast = document.getElementById('toast-notification');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');

    function showToast(message, type = 'error') {
        toast.textContent = message;
        toast.className = `toast ${type}`; // Reset classes
        toast.classList.add('show', type);

        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    // Check password strength as user types
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const passwordHint = document.querySelector('.password-hint');
        
        if (password.length < 8) {
            passwordHint.textContent = 'Password must be at least 8 characters long';
            passwordHint.style.color = '#dc2626';
        } else {
            passwordHint.textContent = 'Password strength: Good';
            passwordHint.style.color = '#16a34a';
        }
    });

    // Check password match as user types in confirm password
    confirmPasswordInput.addEventListener('input', function() {
        if (this.value !== passwordInput.value) {
            this.setCustomValidity('Passwords do not match');
        } else {
            this.setCustomValidity('');
        }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        // --- Client-side validation ---
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        const email = emailInput.value;
        const phone = phoneInput.value;
        const acceptTerms = form.querySelector('#acceptTerms').checked;

        if (password !== confirmPassword) {
            showToast('Passwords do not match');
            confirmPasswordInput.focus();
            return;
        }

        if (password.length < 8) {
            showToast('Password must be at least 8 characters');
            passwordInput.focus();
            return;
        }

        if (!acceptTerms) {
            showToast('Please accept the terms and conditions');
            return;
        }

        // Disable button to prevent multiple submissions
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Creating Account...';

        // --- AJAX request to WordPress ---
        const formData = new FormData(form);
        // Add the action and nonce for WordPress AJAX handling
        formData.append('action', 'reseller_register');
        formData.append('reseller_registration_nonce', registration_ajax_object.nonce);

        fetch(registration_ajax_object.ajax_url, {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Registration successful! Redirecting...', 'success');
                setTimeout(() => {
                    // Redirect to the dashboard page after success
                    window.location.href = registration_ajax_object.dashboard_url;
                }, 1500);
            } else {
                // Display error from server
                showToast(data.data.message || 'An unknown error occurred.');
                submitButton.disabled = false;
                submitButton.textContent = 'Create Account';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('A network error occurred. Please try again.');
            submitButton.disabled = false;
            submitButton.textContent = 'Create Account';
        });
    });
});