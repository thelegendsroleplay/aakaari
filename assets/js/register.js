document.addEventListener('DOMContentLoaded', function () {
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
            let validCount = 0;

            // Length
            if (pass.length >= 8) {
                strengthMeter.length.classList.add('valid');
                validCount++;
            } else {
                strengthMeter.length.classList.remove('valid');
            }
            
            // Uppercase
            if (/[A-Z]/.test(pass)) {
                strengthMeter.uppercase.classList.add('valid');
                validCount++;
            } else {
                strengthMeter.uppercase.classList.remove('valid');
            }
            
            // Number
            if (/[0-9]/.test(pass)) {
                strengthMeter.number.classList.add('valid');
                validCount++;
            } else {
                strengthMeter.number.classList.remove('valid');
            }

            // Special
            if (/[!@#$%^&*()]/.test(pass)) {
                strengthMeter.special.classList.add('valid');
                validCount++;
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

    if (emailInput) {
        emailInput.addEventListener('input', () => {
            clearTimeout(emailTimer);
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
                        document.getElementById('otp-email-display').textContent = data.data.email || 'your email';
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

    function startOtpTimer(duration = 60) {
        let timer = duration;
        resendOtpBtn.disabled = true;
        
        otpTimerInterval = setInterval(() => {
            otpTimerEl.textContent = `Resend code in ${timer}s`;
            timer--;

            if (timer < 0) {
                clearInterval(otpTimerInterval);
                otpTimerEl.textContent = '';
                resendOtpBtn.disabled = false;
            }
        }, 1000);
    }
    
    // Start timer on page load if OTP form is visible
    if (otpFormContainer.style.display === 'block') {
        startOtpTimer();
    }

    if (otpForm) {
        otpForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            
            otpSubmitBtn.disabled = true;
            otpSubmitBtn.textContent = 'Verifying...';
            otpValidationMsg.style.display = 'none';
            
            const formData = new FormData();
            formData.append('action', 'verify_registration_otp');
            formData.append('nonce', registration_ajax_object.nonce);
            formData.append('otp', otpCodeInput.value);

            try {
                const response = await fetch(registration_ajax_object.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showToast(data.data.message, 'success');
                    // Redirect to dashboard
                    window.location.href = registration_ajax_object.dashboard_url;
                } else {
                    otpValidationMsg.textContent = data.data.message;
                    otpValidationMsg.style.display = 'block';
                    otpSubmitBtn.disabled = false;
                    otpSubmitBtn.textContent = 'Verify Account';
                    if (data.data.expired) {
                        clearInterval(otpTimerInterval);
                        otpTimerEl.textContent = 'Code expired.';
                        resendOtpBtn.disabled = false;
                    }
                }
            } catch (error) {
                otpValidationMsg.textContent = 'A network error occurred.';
                otpValidationMsg.style.display = 'block';
                otpSubmitBtn.disabled = false;
                otpSubmitBtn.textContent = 'Verify Account';
            }
        });
    }

    if (resendOtpBtn) {
        resendOtpBtn.addEventListener('click', async function () {
            this.disabled = true;
            this.textContent = 'Sending...';
            otpValidationMsg.style.display = 'none';
            
            const formData = new FormData();
            formData.append('action', 'resend_otp');
            formData.append('nonce', registration_ajax_object.nonce);

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