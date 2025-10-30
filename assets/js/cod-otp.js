/**
 * COD OTP Verification JavaScript
 * Handles OTP verification for Cash on Delivery orders
 */

(function($) {
    'use strict';

    let otpVerified = false;
    let otpSendCooldown = false;

    $(document).ready(function() {
        init();
    });

    function init() {
        // Show/hide OTP field based on payment method
        $(document.body).on('change', 'input[name="payment_method"]', function() {
            toggleOTPField();
        });

        // Initial check
        toggleOTPField();

        // Add OTP UI to checkout
        addOTPUI();

        // Handle OTP send button
        $(document).on('click', '#send-cod-otp', handleSendOTP);

        // Handle OTP verify button
        $(document).on('click', '#verify-cod-otp', handleVerifyOTP);

        // Handle resend OTP
        $(document).on('click', '#resend-cod-otp', handleSendOTP);

        // Validate OTP before checkout
        $(document.body).on('checkout_place_order', validateCODCheckout);

        // Listen for checkout updates
        $(document.body).on('updated_checkout', function() {
            toggleOTPField();
            addOTPUI();
        });
    }

    function toggleOTPField() {
        const selectedMethod = $('input[name="payment_method"]:checked').val();
        const $otpSection = $('#cod-otp-section');
        const $otpField = $('.cod-otp-field');

        if (selectedMethod === 'cod') {
            $otpField.removeClass('hidden').show();
            if ($otpSection.length) {
                $otpSection.slideDown();
            } else {
                addOTPUI();
            }
        } else {
            $otpField.addClass('hidden').hide();
            $otpSection.slideUp();
            otpVerified = false;
        }
    }

    function addOTPUI() {
        // Check if COD payment method exists
        const $codPayment = $('input[value="cod"]').closest('li.payment_method');
        if (!$codPayment.length) return;

        // Check if OTP section already exists
        if ($('#cod-otp-section').length) return;

        // Create OTP verification UI
        const otpHTML = `
            <div id="cod-otp-section" class="cod-otp-verification" style="display:none; margin-top:15px; padding:15px; background:#f8f9fa; border-radius:8px;">
                <h4 style="margin:0 0 10px 0; font-size:14px; font-weight:600;">
                    ${aakaariCODOTP.messages.otp_required}
                </h4>

                <div id="otp-send-section">
                    <p style="font-size:13px; margin:0 0 10px 0; color:#6c757d;">
                        We'll send a verification code to your phone number
                    </p>
                    <button type="button" id="send-cod-otp" class="button alt" style="margin-bottom:10px;">
                        Send OTP
                    </button>
                </div>

                <div id="otp-verify-section" style="display:none;">
                    <div style="margin-bottom:10px;">
                        <input type="text" id="cod-otp-input" placeholder="Enter 6-digit OTP"
                               maxlength="6" pattern="[0-9]{6}"
                               style="width:150px; margin-right:10px; padding:8px; border:1px solid #ddd; border-radius:4px;">
                        <button type="button" id="verify-cod-otp" class="button alt">
                            Verify OTP
                        </button>
                    </div>
                    <p style="font-size:12px; margin:0; color:#6c757d;">
                        Didn't receive OTP?
                        <a href="#" id="resend-cod-otp" style="color:#007bff;">Resend</a>
                    </p>
                </div>

                <div id="otp-success-section" style="display:none;">
                    <div style="display:flex; align-items:center; color:#28a745;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px;">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <span style="font-weight:600;">Phone number verified successfully!</span>
                    </div>
                </div>

                <div id="otp-message" style="margin-top:10px; padding:8px; border-radius:4px; display:none;"></div>
            </div>
        `;

        // Insert after COD payment method
        $codPayment.find('.payment_box').append(otpHTML);

        // Show if COD is selected
        if ($('input[value="cod"]').is(':checked')) {
            $('#cod-otp-section').slideDown();
        }
    }

    function handleSendOTP(e) {
        e.preventDefault();

        if (otpSendCooldown) {
            showMessage('Please wait before requesting another OTP', 'error');
            return;
        }

        const phone = $('#billing_phone').val();
        const email = $('#billing_email').val();

        if (!phone || !email) {
            showMessage('Please enter your phone number and email first', 'error');
            return;
        }

        const $button = $(this);
        $button.prop('disabled', true).text('Sending...');

        $.ajax({
            url: aakaariCODOTP.ajax_url,
            method: 'POST',
            data: {
                action: 'send_cod_otp',
                nonce: aakaariCODOTP.nonce,
                phone: phone,
                email: email
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    $('#otp-send-section').slideUp();
                    $('#otp-verify-section').slideDown();

                    // Set cooldown
                    otpSendCooldown = true;
                    setTimeout(() => {
                        otpSendCooldown = false;
                    }, 60000); // 60 seconds
                } else {
                    showMessage(response.data.message || 'Failed to send OTP', 'error');
                }
            },
            error: function() {
                showMessage('Failed to send OTP. Please try again.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text('Send OTP');
            }
        });
    }

    function handleVerifyOTP(e) {
        e.preventDefault();

        const otp = $('#cod-otp-input').val();
        const phone = $('#billing_phone').val();
        const email = $('#billing_email').val();

        if (!otp || otp.length !== 6) {
            showMessage('Please enter a valid 6-digit OTP', 'error');
            return;
        }

        const $button = $(this);
        $button.prop('disabled', true).text('Verifying...');

        $.ajax({
            url: aakaariCODOTP.ajax_url,
            method: 'POST',
            data: {
                action: 'verify_cod_otp',
                nonce: aakaariCODOTP.nonce,
                phone: phone,
                email: email,
                otp: otp
            },
            success: function(response) {
                if (response.success) {
                    otpVerified = true;
                    showMessage(response.data.message, 'success');
                    $('#otp-verify-section').slideUp();
                    $('#otp-success-section').slideDown();
                } else {
                    showMessage(response.data.message || 'Invalid OTP', 'error');
                    $('#cod-otp-input').val('').focus();
                }
            },
            error: function() {
                showMessage('Failed to verify OTP. Please try again.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text('Verify OTP');
            }
        });
    }

    function validateCODCheckout() {
        const selectedMethod = $('input[name="payment_method"]:checked').val();

        if (selectedMethod === 'cod' && !otpVerified) {
            showMessage(aakaariCODOTP.messages.otp_required, 'error');
            scrollToOTPSection();
            return false;
        }

        return true;
    }

    function showMessage(message, type) {
        const $messageDiv = $('#otp-message');

        $messageDiv
            .removeClass('success error')
            .addClass(type)
            .html(message)
            .css({
                'background-color': type === 'success' ? '#d4edda' : '#f8d7da',
                'color': type === 'success' ? '#155724' : '#721c24',
                'border': type === 'success' ? '1px solid #c3e6cb' : '1px solid #f5c6cb'
            })
            .slideDown();

        setTimeout(() => {
            $messageDiv.slideUp();
        }, 5000);
    }

    function scrollToOTPSection() {
        const $section = $('#cod-otp-section');
        if ($section.length) {
            $('html, body').animate({
                scrollTop: $section.offset().top - 100
            }, 500);
        }
    }

})(jQuery);
