<?php
/**
 * COD OTP Verification System
 * Adds OTP verification for Cash on Delivery orders to verify customer validity
 *
 * @package Aakaari
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Aakaari_COD_OTP_Verification {

    /**
     * Initialize the COD OTP verification system
     */
    public static function init() {
        $instance = new self();
        $instance->hooks();
    }

    /**
     * Setup hooks
     */
    private function hooks() {
        // Add OTP verification step for COD orders
        add_action('woocommerce_checkout_process', [$this, 'verify_cod_otp']);

        // AJAX handlers
        add_action('wp_ajax_send_cod_otp', [$this, 'ajax_send_otp']);
        add_action('wp_ajax_nopriv_send_cod_otp', [$this, 'ajax_send_otp']);
        add_action('wp_ajax_verify_cod_otp', [$this, 'ajax_verify_otp']);
        add_action('wp_ajax_nopriv_verify_cod_otp', [$this, 'ajax_verify_otp']);

        // Add OTP field to checkout
        add_filter('woocommerce_checkout_fields', [$this, 'add_otp_field']);

        // Enqueue scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

        // Store OTP verification status in order
        add_action('woocommerce_checkout_create_order', [$this, 'store_otp_verification'], 10, 2);
    }

    /**
     * Add OTP field to checkout
     */
    public function add_otp_field($fields) {
        $fields['billing']['billing_cod_otp'] = array(
            'type'        => 'text',
            'label'       => __('COD Verification OTP', 'aakaari'),
            'placeholder' => __('Enter 6-digit OTP', 'aakaari'),
            'required'    => false,
            'class'       => array('form-row-wide', 'cod-otp-field', 'hidden'),
            'clear'       => true,
            'priority'    => 120,
        );

        return $fields;
    }

    /**
     * Enqueue scripts for COD OTP verification
     */
    public function enqueue_scripts() {
        if (!is_checkout() || is_order_received_page()) {
            return;
        }

        wp_enqueue_script(
            'aakaari-cod-otp',
            get_stylesheet_directory_uri() . '/assets/js/cod-otp.js',
            array('jquery', 'wc-checkout'),
            '1.0.0',
            true
        );

        wp_localize_script('aakaari-cod-otp', 'aakaariCODOTP', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cod_otp_nonce'),
            'messages' => array(
                'otp_sent'     => __('Verification code sent to your email', 'aakaari'),
                'otp_verified' => __('Email verified successfully', 'aakaari'),
                'otp_invalid'  => __('Invalid verification code. Please try again.', 'aakaari'),
                'otp_required' => __('Email Verification Required for COD Orders', 'aakaari'),
                'enter_details' => __('Please fill in your email and phone number first', 'aakaari'),
                'check_email'  => __('Check your email for the verification code', 'aakaari'),
            ),
        ));
    }

    /**
     * Send OTP via AJAX
     */
    public function ajax_send_otp() {
        check_ajax_referer('cod_otp_nonce', 'nonce');

        $email = sanitize_email($_POST['email'] ?? '');

        if (empty($email) || !is_email($email)) {
            wp_send_json_error(array('message' => __('A valid email address is required', 'aakaari')));
        }

        // Generate 6-digit OTP
        $otp = wp_rand(100000, 999999);

        // Store OTP in transient (valid for 10 minutes)
        $transient_key = 'cod_otp_' . md5($email);
        set_transient($transient_key, $otp, 600);

        // Store attempt count
        $attempt_key = 'cod_otp_attempts_' . md5($email);
        $attempts = get_transient($attempt_key) ?: 0;
        $attempts++;
        set_transient($attempt_key, $attempts, 3600); // 1 hour

        // Limit to 5 attempts per hour
        if ($attempts > 5) {
            wp_send_json_error(array('message' => __('Too many OTP requests. Please try again later.', 'aakaari')));
        }

        // Send OTP via email
        $sent = $this->send_otp_email($email, $otp);

        if ($sent) {
            wp_send_json_success(array(
                'message' => sprintf(
                    __('A 6-digit verification code has been sent to %s. Please check your email (including spam folder).', 'aakaari'),
                    $this->mask_email($email)
                ),
                'masked_email' => $this->mask_email($email),
                'expires_in' => 10, // minutes
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to send verification code. Please check your email address and try again.', 'aakaari')));
        }
    }

    /**
     * Verify OTP via AJAX
     */
    public function ajax_verify_otp() {
        check_ajax_referer('cod_otp_nonce', 'nonce');

        $email = sanitize_email($_POST['email'] ?? '');
        $submitted_otp = sanitize_text_field($_POST['otp'] ?? '');

        if (empty($email) || empty($submitted_otp)) {
            wp_send_json_error(array('message' => __('Email and OTP are required', 'aakaari')));
        }

        // Get stored OTP
        $transient_key = 'cod_otp_' . md5($email);
        $stored_otp = get_transient($transient_key);

        if (!$stored_otp) {
            wp_send_json_error(array('message' => __('OTP expired. Please request a new one.', 'aakaari')));
        }

        if ($stored_otp == $submitted_otp) {
            // Store verification status
            $verified_key = 'cod_otp_verified_' . md5($email);
            set_transient($verified_key, true, 1800); // 30 minutes

            // Delete OTP after successful verification
            delete_transient($transient_key);

            wp_send_json_success(array('message' => __('OTP verified successfully', 'aakaari')));
        } else {
            wp_send_json_error(array('message' => __('Invalid OTP. Please try again.', 'aakaari')));
        }
    }

    /**
     * Verify COD OTP during checkout process
     */
    public function verify_cod_otp() {
        // This function is hooked into 'woocommerce_checkout_process'.
        // It runs when the 'Place Order' button is clicked.

        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';

        // Only verify for COD orders
        if ($payment_method !== 'cod') {
            return;
        }
        error_log("Aakaari COD Check: Processing COD order. Checking for OTP verification.");

        $email = isset($_POST['billing_email']) ? sanitize_email($_POST['billing_email']) : '';

        // Check if OTP was verified
        $verified_key = 'cod_otp_verified_' . md5($email);
        $is_verified = get_transient($verified_key);

        if (!$is_verified) {
            error_log("Aakaari COD Check: OTP not verified for email: " . $email);
            wc_add_notice(
                __('Please verify your email with OTP for Cash on Delivery orders.', 'aakaari'),
                'error'
            );
        }
        error_log("Aakaari COD Check: OTP check passed for email: " . $email);
    }

    /**
     * Store OTP verification status in order meta
     */
    public function store_otp_verification($order, $data) {
        if ($order->get_payment_method() === 'cod') {
            $email = $data['billing_email'] ?? '';
            $verified_key = 'cod_otp_verified_' . md5($email);
            $is_verified = get_transient($verified_key);

            $order->update_meta_data('_cod_otp_verified', $is_verified ? 'yes' : 'no');
            $order->update_meta_data('_cod_otp_verification_time', current_time('mysql'));

            // Clean up transient
            delete_transient($verified_key);
        }
    }

    /**
     * Send OTP via email
     */
    private function send_otp_email($email, $otp) {
        $subject = __('Your Cash on Delivery Verification Code', 'aakaari');

        $site_name = get_bloginfo('name');

        $message = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
            <div style="background-color: #ffffff; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h2 style="color: #333; margin-top: 0; border-bottom: 2px solid #007bff; padding-bottom: 10px;">
                    ' . esc_html($site_name) . '
                </h2>

                <h3 style="color: #007bff; margin-bottom: 20px;">Email Verification Required</h3>

                <p style="color: #666; line-height: 1.6; margin-bottom: 20px;">
                    To complete your Cash on Delivery order, please verify your email address using the code below:
                </p>

                <div style="background-color: #e7f3ff; border-left: 4px solid #007bff; padding: 20px; margin: 30px 0; text-align: center;">
                    <p style="color: #666; margin: 0 0 10px 0; font-size: 14px;">Your Verification Code</p>
                    <h1 style="color: #007bff; margin: 0; font-size: 36px; letter-spacing: 8px; font-family: monospace;">
                        ' . esc_html($otp) . '
                    </h1>
                </div>

                <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
                    <p style="margin: 0; color: #856404; font-size: 14px;">
                        <strong>⏱ This code will expire in 10 minutes</strong>
                    </p>
                </div>

                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                    <h4 style="color: #333; margin-bottom: 10px;">Order Details:</h4>
                    <p style="color: #666; margin: 5px 0; font-size: 14px;">
                        <strong>Email:</strong> ' . esc_html($email) . '<br>
                        <strong>Payment Method:</strong> Cash on Delivery
                    </p>
                </div>

                <p style="color: #999; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                    If you didn\'t request this verification code, please ignore this email or contact our support team.
                </p>
            </div>
        </div>';

        $headers = array('Content-Type: text/html; charset=UTF-8');

        return wp_mail($email, $subject, $message, $headers);
    }

    /**
     * Mask email address for security
     */
    private function mask_email($email) {
        $parts = explode('@', $email);
        if (count($parts) != 2) {
            return $email;
        }

        $name = $parts[0];
        $domain = $parts[1];

        $name_length = strlen($name);
        if ($name_length <= 2) {
            $masked_name = str_repeat('*', $name_length);
        } else {
            $masked_name = substr($name, 0, 2) . str_repeat('*', $name_length - 2);
        }

        return $masked_name . '@' . $domain;
    }

    /**
     * Mask phone number for security
     */
    private function mask_phone($phone) {
        $length = strlen($phone);
        if ($length <= 4) {
            return $phone;
        }
        return substr($phone, 0, 2) . str_repeat('*', $length - 4) . substr($phone, -2);
    }
}

// Initialize
add_action('init', ['Aakaari_COD_OTP_Verification', 'init']);
