<?php
/**
 * Template Name: Track Order
 * Template for tracking orders with OTP verification
 */

get_header();
?>

<div class="track-order-page">
    <div class="track-order-container">

        <!-- Header -->
        <div class="track-order-header">
            <h1 class="page-title"><?php esc_html_e('Track Your Order', 'woocommerce'); ?></h1>
            <p class="page-description">
                <?php esc_html_e('Enter your order number to track your package', 'woocommerce'); ?>
            </p>
        </div>

        <!-- Step 1: Enter Order ID -->
        <div id="step-order-id" class="tracking-step active">
            <div class="tracking-card">
                <div class="card-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 11L12 14L22 4" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M21 12V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H16" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h2 class="card-title"><?php esc_html_e('Enter Order Number', 'woocommerce'); ?></h2>
                <p class="card-description">
                    <?php esc_html_e('You can find your order number in the confirmation email', 'woocommerce'); ?>
                </p>

                <form id="order-id-form" class="tracking-form">
                    <div class="form-group">
                        <label for="order_id"><?php esc_html_e('Order Number', 'woocommerce'); ?></label>
                        <input
                            type="text"
                            id="order_id"
                            name="order_id"
                            placeholder="<?php esc_attr_e('e.g., 12345', 'woocommerce'); ?>"
                            required
                            class="form-input"
                        >
                        <span class="input-prefix">#</span>
                    </div>

                    <div class="error-message" id="order-error" style="display: none;"></div>

                    <button type="submit" class="btn-submit" id="btn-order-submit">
                        <span class="btn-text"><?php esc_html_e('Continue', 'woocommerce'); ?></span>
                        <svg class="btn-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- Step 2: Verify OTP -->
        <div id="step-otp" class="tracking-step" style="display: none;">
            <div class="tracking-card">
                <div class="card-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M22 6L12 13L2 6" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h2 class="card-title"><?php esc_html_e('Verify OTP', 'woocommerce'); ?></h2>
                <p class="card-description">
                    <?php esc_html_e('We have sent a verification code to your email', 'woocommerce'); ?>
                    <br>
                    <strong id="masked-email"></strong>
                </p>

                <form id="otp-form" class="tracking-form">
                    <div class="form-group">
                        <label for="otp_code"><?php esc_html_e('Enter OTP Code', 'woocommerce'); ?></label>
                        <input
                            type="text"
                            id="otp_code"
                            name="otp_code"
                            placeholder="<?php esc_attr_e('6-digit code', 'woocommerce'); ?>"
                            maxlength="6"
                            required
                            class="form-input otp-input"
                        >
                    </div>

                    <div class="error-message" id="otp-error" style="display: none;"></div>

                    <button type="submit" class="btn-submit" id="btn-otp-submit">
                        <span class="btn-text"><?php esc_html_e('Verify & Track', 'woocommerce'); ?></span>
                        <svg class="btn-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>

                    <button type="button" class="btn-link" id="btn-resend-otp">
                        <?php esc_html_e('Resend OTP', 'woocommerce'); ?>
                    </button>

                    <button type="button" class="btn-link" id="btn-back-to-order">
                        <?php esc_html_e('← Change Order Number', 'woocommerce'); ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Step 3: Show Tracking Details -->
        <div id="step-tracking" class="tracking-step" style="display: none;">
            <div class="tracking-card">
                <div class="tracking-header">
                    <h2 class="tracking-title"><?php esc_html_e('Order Status', 'woocommerce'); ?></h2>
                    <span class="order-number-badge" id="tracking-order-number"></span>
                </div>

                <div id="tracking-details-content">
                    <!-- This will be populated via JavaScript -->
                </div>

                <button type="button" class="btn-secondary" id="btn-track-another">
                    <?php esc_html_e('Track Another Order', 'woocommerce'); ?>
                </button>
            </div>
        </div>

    </div>
</div>

<script>
jQuery(function($) {
    'use strict';

    let currentOrderId = null;
    let currentOrderEmail = null;

    // Step 1: Submit Order ID
    $('#order-id-form').on('submit', function(e) {
        e.preventDefault();

        const orderId = $('#order_id').val().trim();
        const $btn = $('#btn-order-submit');
        const $error = $('#order-error');

        if (!orderId) {
            showError($error, '<?php esc_html_e('Please enter an order number', 'woocommerce'); ?>');
            return;
        }

        $btn.prop('disabled', true).html('<span class="btn-text"><?php esc_html_e('Processing...', 'woocommerce'); ?></span>');
        $error.hide();

        // Send AJAX request to verify order and send OTP
        $.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            type: 'POST',
            data: {
                action: 'send_tracking_otp',
                order_id: orderId,
                nonce: '<?php echo wp_create_nonce('tracking_otp_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    currentOrderId = orderId;
                    currentOrderEmail = response.data.email;
                    $('#masked-email').text(response.data.masked_email);
                    showStep('step-otp');
                } else {
                    showError($error, response.data.message || '<?php esc_html_e('Order not found', 'woocommerce'); ?>');
                }
            },
            error: function() {
                showError($error, '<?php esc_html_e('An error occurred. Please try again.', 'woocommerce'); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="btn-text"><?php esc_html_e('Continue', 'woocommerce'); ?></span><svg class="btn-icon" width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>');
            }
        });
    });

    // Step 2: Submit OTP
    $('#otp-form').on('submit', function(e) {
        e.preventDefault();

        const otpCode = $('#otp_code').val().trim();
        const $btn = $('#btn-otp-submit');
        const $error = $('#otp-error');

        if (!otpCode || otpCode.length !== 6) {
            showError($error, '<?php esc_html_e('Please enter a valid 6-digit code', 'woocommerce'); ?>');
            return;
        }

        $btn.prop('disabled', true).html('<span class="btn-text"><?php esc_html_e('Verifying...', 'woocommerce'); ?></span>');
        $error.hide();

        // Verify OTP and get tracking details
        $.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            type: 'POST',
            data: {
                action: 'verify_tracking_otp',
                order_id: currentOrderId,
                otp_code: otpCode,
                nonce: '<?php echo wp_create_nonce('tracking_otp_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    displayTrackingDetails(response.data);
                    showStep('step-tracking');
                } else {
                    showError($error, response.data.message || '<?php esc_html_e('Invalid OTP code', 'woocommerce'); ?>');
                }
            },
            error: function() {
                showError($error, '<?php esc_html_e('An error occurred. Please try again.', 'woocommerce'); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="btn-text"><?php esc_html_e('Verify & Track', 'woocommerce'); ?></span><svg class="btn-icon" width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>');
            }
        });
    });

    // Resend OTP
    $('#btn-resend-otp').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).text('<?php esc_html_e('Sending...', 'woocommerce'); ?>');

        $.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            type: 'POST',
            data: {
                action: 'send_tracking_otp',
                order_id: currentOrderId,
                nonce: '<?php echo wp_create_nonce('tracking_otp_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php esc_html_e('OTP sent successfully!', 'woocommerce'); ?>');
                } else {
                    alert(response.data.message || '<?php esc_html_e('Failed to send OTP', 'woocommerce'); ?>');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).text('<?php esc_html_e('Resend OTP', 'woocommerce'); ?>');
            }
        });
    });

    // Navigation buttons
    $('#btn-back-to-order').on('click', function() {
        showStep('step-order-id');
        $('#order_id').val('');
        $('#otp_code').val('');
    });

    $('#btn-track-another').on('click', function() {
        showStep('step-order-id');
        $('#order_id').val('');
        $('#otp_code').val('');
        currentOrderId = null;
        currentOrderEmail = null;
    });

    // Helper functions
    function showStep(stepId) {
        $('.tracking-step').hide().removeClass('active');
        $('#' + stepId).fadeIn(300).addClass('active');
        $('html, body').animate({ scrollTop: 0 }, 300);
    }

    function showError($element, message) {
        $element.text(message).fadeIn(300);
    }

    function displayTrackingDetails(data) {
        $('#tracking-order-number').text('#' + data.order_number);

        let html = `
            <div class="order-status-badge status-${data.status.toLowerCase().replace(' ', '-')}">
                ${data.status_label}
            </div>

            <div class="order-info-section">
                <h3 class="section-title"><?php esc_html_e('Order Information', 'woocommerce'); ?></h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label"><?php esc_html_e('Order Date', 'woocommerce'); ?></span>
                        <span class="info-value">${data.order_date}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><?php esc_html_e('Total', 'woocommerce'); ?></span>
                        <span class="info-value">${data.order_total}</span>
                    </div>
                </div>
            </div>
        `;

        if (data.tracking_number) {
            html += `
                <div class="tracking-info-section">
                    <h3 class="section-title"><?php esc_html_e('Shipping Information', 'woocommerce'); ?></h3>
                    <div class="tracking-info-card">
                        <div class="tracking-row">
                            <span class="tracking-label"><?php esc_html_e('Courier', 'woocommerce'); ?></span>
                            <span class="tracking-value">${data.courier_name || 'N/A'}</span>
                        </div>
                        <div class="tracking-row">
                            <span class="tracking-label"><?php esc_html_e('Tracking Number', 'woocommerce'); ?></span>
                            <span class="tracking-value tracking-number">${data.tracking_number}</span>
                        </div>
                        ${data.tracking_url ? `
                            <a href="${data.tracking_url}" target="_blank" class="btn-track-external">
                                <?php esc_html_e('Track on Courier Website', 'woocommerce'); ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                    <path d="M18 13V19C18 19.5304 17.7893 20.0391 17.4142 20.4142C17.0391 20.7893 16.5304 21 16 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V8C3 7.46957 3.21071 6.96086 3.58579 6.58579C3.96086 6.21071 4.46957 6 5 6H11M15 3H21M21 3V9M21 3L10 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        if (data.items && data.items.length > 0) {
            html += `
                <div class="order-items-section">
                    <h3 class="section-title"><?php esc_html_e('Items', 'woocommerce'); ?></h3>
                    <div class="items-list">
            `;

            data.items.forEach(function(item) {
                html += `
                    <div class="item-row">
                        <span class="item-name">${item.name}</span>
                        <span class="item-qty">×${item.quantity}</span>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        }

        $('#tracking-details-content').html(html);
    }
});
</script>

<?php
get_footer();
?>
