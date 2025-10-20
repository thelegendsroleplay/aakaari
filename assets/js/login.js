document.addEventListener('DOMContentLoaded', function() {
    // --- Password Visibility Toggle ---
    const passwordField = document.getElementById('password');
    if (passwordField) {
        const togglePassword = document.createElement('button');
        togglePassword.setAttribute('type', 'button');
        togglePassword.classList.add('password-toggle');
        togglePassword.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/><path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/></svg>';
        
        const inputWrapper = passwordField.parentElement;
        inputWrapper.appendChild(togglePassword);
        
        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            // ... (icon switching logic) ...
        });
    }
    
    // --- Form Validation (Client-side) ---
    const loginForm = document.getElementById('aakaari-login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (email === '' || password === '') {
                // Let WP handle empty field errors
                return;
            }
            
            if (!/^\S+@\S+\.\S+$/.test(email)) {
                e.preventDefault();
                showError('email', 'Please enter a valid email address');
            } else {
                clearError('email');
            }
        });
    }
    
    // --- Error Helpers (for client-side only) ---
    function showError(fieldId, message) {
        // This is a simplified version. The server-side error is more important.
        const field = document.getElementById(fieldId);
        if (field) field.classList.add('input-error');
    }
    
    function clearError(fieldId) {
        const field = document.getElementById(fieldId);
        if (field) field.classList.remove('input-error');
    }
    
    // --- NEW: Login Form Toggling ---
    const passContainer = document.getElementById('login-with-password-container');
    const otpContainer = document.getElementById('login-with-otp-container');
    const showOtpBtn = document.getElementById('show-login-otp-btn');
    const showPassBtn = document.getElementById('show-login-password-btn');

    if (showOtpBtn) {
        showOtpBtn.addEventListener('click', () => {
            passContainer.style.display = 'none';
            otpContainer.style.display = 'block';
        });
    }
    if (showPassBtn) {
        showPassBtn.addEventListener('click', () => {
            passContainer.style.display = 'block';
            otpContainer.style.display = 'none';
        });
    }

    // --- NEW: Login with OTP Logic ---
    const loginOtpForm = document.getElementById('aakaari-login-otp-form');
    const loginOtpEmail = document.getElementById('login-otp-email');
    const loginOtpCode = document.getElementById('login-otp-code');
    const sendLoginOtpBtn = document.getElementById('send-login-otp-btn');
    const verifyLoginOtpBtn = document.getElementById('verify-login-otp-btn');
    const resendLoginOtpBtn = document.getElementById('resend-login-otp-btn');
    const loginOtpStep1 = document.getElementById('login-otp-step-1');
    const loginOtpStep2 = document.getElementById('login-otp-step-2');
    const loginOtpMsg = document.getElementById('login-otp-validation-msg');
    const loginOtpTimerEl = document.getElementById('login-otp-timer');
    let loginOtpTimerInterval;

    function showLoginOtpMessage(message, type = 'error') {
        loginOtpMsg.textContent = message;
        loginOtpMsg.className = `validation-message ${type}`;
        loginOtpMsg.style.display = 'block';
    }
    
    function startLoginOtpTimer(duration = 60) {
        let timer = duration;
        resendLoginOtpBtn.disabled = true;
        
        loginOtpTimerInterval = setInterval(() => {
            loginOtpTimerEl.textContent = `Resend code in ${timer}s`;
            timer--;

            if (timer < 0) {
                clearInterval(loginOtpTimerInterval);
                loginOtpTimerEl.textContent = '';
                resendLoginOtpBtn.disabled = false;
            }
        }, 1000);
    }

    // Handle "Send Code" click
    if (sendLoginOtpBtn) {
        sendLoginOtpBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            sendLoginOtpBtn.disabled = true;
            sendLoginOtpBtn.textContent = 'Sending...';
            showLoginOtpMessage('Sending code...', 'loading');
            
            const formData = new FormData();
            formData.append('action', 'send_login_otp');
            formData.append('nonce', registration_ajax_object.nonce); // Using the same nonce
            formData.append('email', loginOtpEmail.value);
            
            try {
                const response = await fetch(registration_ajax_object.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    showLoginOtpMessage(data.data.message, 'success');
                    loginOtpStep1.style.display = 'none';
                    loginOtpStep2.style.display = 'block';
                    startLoginOtpTimer();
                } else {
                    showLoginOtpMessage(data.data.message, 'error');
                    sendLoginOtpBtn.disabled = false;
                    sendLoginOtpBtn.textContent = 'Send Login Code';
                }
            } catch (error) {
                showLoginOtpMessage('A network error occurred.', 'error');
                sendLoginOtpBtn.disabled = false;
                sendLoginOtpBtn.textContent = 'Send Login Code';
            }
        });
    }
    
    // Handle "Login with Code" click (part of the same form)
    if (loginOtpForm) {
        loginOtpForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            // This submit handles both step 1 and step 2
            // If step 2 is visible, we verify.
            if (loginOtpStep2.style.display !== 'block') {
                return; 
            }
            
            verifyLoginOtpBtn.disabled = true;
            verifyLoginOtpBtn.textContent = 'Verifying...';
            showLoginOtpMessage('Verifying code...', 'loading');

            const formData = new FormData();
            formData.append('action', 'verify_login_otp');
            formData.append('nonce', registration_ajax_object.nonce);
            formData.append('otp', loginOtpCode.value);
            
            try {
                const response = await fetch(registration_ajax_object.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    showLoginOtpMessage(data.data.message, 'success');
                    // Redirect!
                    window.location.href = data.data.redirect_url || registration_ajax_object.dashboard_url;
                } else {
                    showLoginOtpMessage(data.data.message, 'error');
                    verifyLoginOtpBtn.disabled = false;
                    verifyLoginOtpBtn.textContent = 'Login with Code';
                    if (data.data.expired) {
                        clearInterval(loginOtpTimerInterval);
                        loginOtpTimerEl.textContent = 'Code expired.';
                        resendLoginOtpBtn.disabled = false;
                    }
                }
            } catch (error) {
                showLoginOtpMessage('A network error occurred.', 'error');
                verifyLoginOtpBtn.disabled = false;
                verifyLoginOtpBtn.textContent = 'Login with Code';
            }
        });
    }

    // Handle "Resend" click
    if (resendLoginOtpBtn) {
        resendLoginOtpBtn.addEventListener('click', async () => {
            resendLoginOtpBtn.disabled = true;
            resendLoginOtpBtn.textContent = 'Sending...';
            showLoginOtpMessage('Sending new code...', 'loading');
            
            const formData = new FormData();
            formData.append('action', 'resend_otp'); // Uses the same resend logic as registration
            formData.append('nonce', registration_ajax_object.nonce);

            try {
                const response = await fetch(registration_ajax_object.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showLoginOtpMessage(data.data.message, 'success');
                    startLoginOtpTimer();
                } else {
                    showLoginOtpMessage(data.data.message, 'error');
                    resendLoginOtpBtn.disabled = false;
                }
                resendLoginOtpBtn.textContent = 'Resend Code';

            } catch (error) {
                showLoginOtpMessage('A network error occurred.', 'error');
                resendLoginOtpBtn.disabled = false;
                resendLoginOtpBtn.textContent = 'Resend Code';
            }
        });
    }
});