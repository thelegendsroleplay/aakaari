/**
 * Admin Login JavaScript
 */
(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        const loginForm = $('#aakaari-admin-login-form');
        const alertBox = $('#aakaari-admin-login-alert');
        
        // Form submission
        loginForm.on('submit', function(e) {
            e.preventDefault();
            
            const emailField = $('#admin_email');
            const passwordField = $('#admin_password');
            let isValid = true;
            
            // Remove any existing error messages
            $('.form-error').remove();
            hideAlert();
            
            // Basic validation
            if (!emailField.val().trim()) {
                addErrorMessage(emailField, 'Email is required');
                isValid = false;
            }
            
            if (!passwordField.val()) {
                addErrorMessage(passwordField, 'Password is required');
                isValid = false;
            }
            
            if (!isValid) return;
            
            // Add loading state
            const submitButton = loginForm.find('button[type="submit"]');
            submitButton.prop('disabled', true);
            submitButton.html('<span class="loading-spinner"></span> Logging in...');
            
            // Send AJAX request
            $.ajax({
                url: aakaari_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aakaari_admin_login',
                    nonce: aakaari_admin.nonce,
                    email: emailField.val(),
                    password: passwordField.val()
                },
                success: function(response) {
                    if (response.success) {
                        // Success
                        showAlert(response.data.message, 'success');
                        
                        // Redirect to dashboard
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    } else {
                        // Error
                        showAlert(response.data.message);
                        submitButton.prop('disabled', false);
                        submitButton.html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shield-icon-small"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg> Access Custom Dashboard');
                    }
                },
                error: function() {
                    showAlert('Server error. Please try again later.');
                    submitButton.prop('disabled', false);
                    submitButton.html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shield-icon-small"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg> Access Custom Dashboard');
                }
            });
        });
        
        // Helper function to add error message after an input
        function addErrorMessage(inputElement, message) {
            $('<div class="form-error" style="color:#dc2626;font-size:0.75rem;margin-top:0.25rem;">' + message + '</div>')
                .insertAfter(inputElement.closest('.aakaari-input-wrapper'));
        }
        
        // Helper function to show alert message
        function showAlert(message, type = 'error') {
            alertBox.removeClass('success error').addClass(type).text(message).show();
        }
        
        // Helper function to hide alert message
        function hideAlert() {
            alertBox.hide();
        }
        
        // Add password visibility toggle functionality
        const passwordInput = $('#admin_password');
        
        // Add toggle button after password input
        $('<button type="button" class="password-toggle" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#6b7280;">' + 
          '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
          '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>' +
          '<circle cx="12" cy="12" r="3"></circle>' +
          '</svg></button>')
            .insertAfter(passwordInput);
        
        // Toggle password visibility
        $('.password-toggle').on('click', function() {
            const passwordType = passwordInput.attr('type');
            
            if (passwordType === 'password') {
                passwordInput.attr('type', 'text');
                $(this).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                    '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>' +
                    '<circle cx="12" cy="12" r="3"></circle>' +
                    '<line x1="1" y1="1" x2="23" y2="23"></line>' +
                    '</svg>');
            } else {
                passwordInput.attr('type', 'password');
                $(this).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                    '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>' +
                    '<circle cx="12" cy="12" r="3"></circle>' +
                    '</svg>');
            }
        });
    });
    
})(jQuery);