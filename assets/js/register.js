// registration_form.js

document.addEventListener('DOMContentLoaded', function () {
    // Get DOM elements
    const regFormContainer = document.getElementById('registration-form-container');
    const otpFormContainer = document.getElementById('otp-verification-container');
    const regForm = document.getElementById('reseller-registration-form');
    const otpForm = document.getElementById('otp-verification-form');
    const toast = document.getElementById('toast-notification');

    // --- Toast Notification ---
    function showToast(message, type = 'error') {
        if (!toast) return;
        toast.textContent = message;
        toast.className = 'toast'; // Reset classes
        toast.classList.add('show', type);

        setTimeout(() => {
            toast.classList.remove('show');
        }, 4000);
    }

    // --- Password Strength ---
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const confirmPasswordMsg = document.getElementById('confirm-password-validation-msg');
    
    const strengthMeter = {
        length: document.getElementById('ps-length'),
        uppercase: document.getElementById('ps-uppercase'),
        number: document.getElementById('ps-number'),
        special: document.getElementById('ps-special'),
    };

    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const pass = this.value;

            // Length
            if (pass.length >= 8) {
                strengthMeter.length.classList.add('valid');
            } else {
                strengthMeter.length.classList.remove('valid');
            }
            
            // Uppercase
            if (/[A-Z]/.test(pass)) {
                strengthMeter.uppercase.classList.add('valid');
            } else {
                strengthMeter.uppercase.classList.remove('valid');
            }
            
            // Number
            if (/[0-9]/.test(pass)) {
                strengthMeter.number.classList.add('valid');
            } else {
                strengthMeter.number.classList.remove('valid');
            }

            // Special
            if (/[!@#$%^&*()]/.test(pass)) {
                strengthMeter.special.classList.add('valid');
            } else {
                strengthMeter.special.classList.remove('valid');
            }
            
            // Check confirmation
            checkPasswordMatch();
        });
    }

    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    }
    
    function checkPasswordMatch() {
        if (!confirmPasswordInput || !passwordInput) return;
        
        if (confirmPasswordInput.value && confirmPasswordInput.value !== passwordInput.value) {
            confirmPasswordMsg.textContent = 'Passwords do not match.';
            confirmPasswordMsg.className = 'validation-message';
            confirmPasswordMsg.style.display = 'block';
            return false;
        } else {
            confirmPasswordMsg.style.display = 'none';
            return true;
        }
    }

    // --- Real-time Email/Phone Validation ---
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    const emailMsg = document.getElementById('email-validation-msg');
    const phoneMsg = document.getElementById('phone-validation-msg');
    let emailTimer, phoneTimer;

    function isValidEmail(email) {
        // Simple but effective regex for email format
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // Corrected: Only ONE event listener for email input, with format check BEFORE AJAX!
    if (emailInput) {
        emailInput.addEventListener('input', () => {
            clearTimeout(emailTimer);

            if (!isValidEmail(emailInput.value)) {
                emailMsg.textContent = 'Please enter a valid email address.';
                emailMsg.className = 'validation-message';
                emailMsg.style.display = 'block';
                return; // Do NOT proceed to AJAX availability check
            }

            emailMsg.textContent = 'Checking...';
            emailMsg.className = 'validation-message loading';
            emailMsg.style.display = 'block';

            emailTimer = setTimeout(() => {
                validateField('email', emailInput.value, emailMsg, 'check_email_exists');
            }, 800);
        });
    }

    if (phoneInput) {
        phoneInput.addEventListener('input', () => {
            clearTimeout(phoneTimer);
            phoneMsg.textContent = 'Checking...';
            phoneMsg.className = 'validation-message loading';
            phoneMsg.style.display = 'block';
            
            phoneTimer = setTimeout(() => {
                validateField('phone', phoneInput.value, phoneMsg, 'check_phone_exists');
            }, 800);
        });
    }

    async function validateField(fieldName, value, msgElement, action) {
        if (!value) {
            msgElement.style.display = 'none';
            return;
        }

        const formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', registration_ajax_object.nonce);
        formData.append(fieldName, value);
        
        try {
            const response = await fetch(registration_ajax_object.ajax_url, {
                method: 'POST',
                body: formData,
            });
            const data = await response.json();
            
            if (data.success) {
                msgElement.textContent = data.data.message;
                msgElement.className = 'validation-message success';
            } else {
                msgElement.textContent = data.data.message;
                msgElement.className = 'validation-message';
            }
            msgElement.style.display = 'block';
            
        } catch (error) {
            msgElement.textContent = 'Network error during validation.';
            msgElement.className = 'validation-message';
            msgElement.style.display = 'block';
        }
    }

    // --- Registration Form Submission ---
    if (regForm) {
        regForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            
            const submitButton = document.getElementById('register-submit-btn');
            submitButton.disabled = true;
            submitButton.textContent = 'Creating Account...';
            
            // Final client-side checks
            if (!checkPasswordMatch()) {
                showToast('Passwords do not match');
                submitButton.disabled = false;
                submitButton.textContent = 'Create Account';
                return;
            }
            
            // Check password strength
            const pass = passwordInput.value;
            if (pass.length < 8 || !/[A-Z]/.test(pass) || !/[0-9]/.test(pass) || !/[!@#$%^&*()]/.test(pass)) {
                showToast('Password does not meet all security requirements.');
                submitButton.disabled = false;
                submitButton.textContent = 'Create Account';
                return;
            }

            // Final email format check before submit
            if (!isValidEmail(emailInput.value)) {
                showToast('Please enter a valid email address.');
                submitButton.disabled = false;
                submitButton.textContent = 'Create Account';
                return;
            }

            const formData = new FormData(regForm);
            formData.append('action', 'reseller_register');
            formData.append('reseller_registration_nonce', registration_ajax_object.nonce);

            try {
                const response = await fetch(registration_ajax_object.ajax_url, {
                    method: 'POST',
                    body: formData,
                });
                const data = await response.json();

                if (data.success) {
                    showToast(data.data.message, 'success');
                    if (data.data.otp_required) {
                        // Switch to OTP form
                        const emailDisplay = document.getElementById('otp-email-display');
                        if (emailDisplay) {
                            emailDisplay.textContent = data.data.email || 'your email';
                        }
                        regFormContainer.style.display = 'none';
                        otpFormContainer.style.display = 'block';
                        startOtpTimer();
                    } else {
                        // Fallback (shouldn't happen with new flow, but good to have)
                        window.location.href = registration_ajax_object.login_url;
                    }
                } else {
                    showToast(data.data.message || 'An unknown error occurred.');
                    submitButton.disabled = false;
                    submitButton.textContent = 'Create Account';
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('A network error occurred. Please try again.');
                submitButton.disabled = false;
                submitButton.textContent = 'Create Account';
            }
        });
    }

    // --- OTP Form Submission & Resend ---
    const otpCodeInput = document.getElementById('otpCode');
    const otpSubmitBtn = document.getElementById('otp-submit-btn');
    const resendOtpBtn = document.getElementById('resend-otp-btn');
    const otpValidationMsg = document.getElementById('otp-validation-msg');
    const otpTimerEl = document.getElementById('otp-timer');
    let otpTimerInterval;

    // Global OTP Timer function
    function startOtpTimer(seconds = 60) {
        let timer = seconds;
        if (resendOtpBtn) {
            resendOtpBtn.disabled = true;
        }
        
        // Clear any existing timer
        if (window.otpTimerInterval) {
            clearInterval(window.otpTimerInterval);
        }
        
        otpTimerInterval = setInterval(() => {
            if (otpTimerEl) {
                const minutes = Math.floor(timer / 60);
                const secs = timer % 60;
                otpTimerEl.textContent = `Resend available in ${minutes}:${secs < 10 ? '0' : ''}${secs}`;
            }
            
            timer--;

            if (timer < 0) {
                clearInterval(otpTimerInterval);
                if (otpTimerEl) {
                    otpTimerEl.textContent = '';
                }
                if (resendOtpBtn) {
                    resendOtpBtn.disabled = false;
                }
            }
        }, 1000);
        
        // Store reference globally
        window.otpTimerInterval = otpTimerInterval;
    }
    
    // Start timer on page load if OTP form is visible
    if (otpFormContainer && otpFormContainer.style.display !== 'none') {
        startOtpTimer();
    }

    if (otpForm) {
        otpForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            
            if (otpSubmitBtn) {
                otpSubmitBtn.disabled = true;
                otpSubmitBtn.textContent = 'Verifying...';
            }
            
            if (otpValidationMsg) {
                otpValidationMsg.style.display = 'none';
            }
            
            const email = document.getElementById('otp-email-display')?.textContent.trim() || '';
            
            const formData = new FormData();
            formData.append('action', 'verify_otp'); // Changed to use our new handler
            formData.append('nonce', registration_ajax_object.nonce);
            formData.append('otp', otpCodeInput.value);
            formData.append('email', email);

            try {
                const response = await fetch(registration_ajax_object.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showToast(data.data.message, 'success');
                    // Redirect after a delay
                    setTimeout(() => {
                        window.location.href = data.data.redirect_url || registration_ajax_object.login_url;
                    }, 1500);
                } else {
                    if (otpValidationMsg) {
                        otpValidationMsg.textContent = data.data.message;
                        otpValidationMsg.style.display = 'block';
                    }
                    if (otpSubmitBtn) {
                        otpSubmitBtn.disabled = false;
                        otpSubmitBtn.textContent = 'Verify Account';
                    }
                    if (data.data.expired) {
                        clearInterval(otpTimerInterval);
                        if (otpTimerEl) {
                            otpTimerEl.textContent = 'Code expired.';
                        }
                        if (resendOtpBtn) {
                            resendOtpBtn.disabled = false;
                        }
                    }
                }
            } catch (error) {
                if (otpValidationMsg) {
                    otpValidationMsg.textContent = 'A network error occurred.';
                    otpValidationMsg.style.display = 'block';
                }
                if (otpSubmitBtn) {
                    otpSubmitBtn.disabled = false;
                    otpSubmitBtn.textContent = 'Verify Account';
                }
            }
        });
    }

    if (resendOtpBtn) {
        resendOtpBtn.addEventListener('click', async function () {
            this.disabled = true;
            this.textContent = 'Sending...';
            if (otpValidationMsg) {
                otpValidationMsg.style.display = 'none';
            }
            
            const email = document.getElementById('otp-email-display')?.textContent.trim() || '';
            
            const formData = new FormData();
            formData.append('action', 'resend_otp');
            formData.append('nonce', registration_ajax_object.nonce);
            formData.append('email', email);

            try {
                const response = await fetch(registration_ajax_object.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showToast(data.data.message, 'success');
                    startOtpTimer();
                } else {
                    showToast(data.data.message, 'error');
                    this.disabled = false;
                }
                this.textContent = 'Resend Code';

            } catch (error) {
                showToast('A network error occurred.', 'error');
                this.disabled = false;
                this.textContent = 'Resend Code';
            }
        });
    }
});