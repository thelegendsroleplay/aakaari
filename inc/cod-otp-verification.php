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
                'otp_sent'     => __('OTP sent to your phone number', 'aakaari'),
                'otp_verified' => __('OTP verified successfully', 'aakaari'),
                'otp_invalid'  => __('Invalid OTP. Please try again.', 'aakaari'),
                'otp_required' => __('Please verify your phone number with OTP for COD orders', 'aakaari'),
            ),
        ));
    }

    /**
     * Send OTP via AJAX
     */
    public function ajax_send_otp() {
        check_ajax_referer('cod_otp_nonce', 'nonce');

        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');

        if (empty($phone) || empty($email)) {
            wp_send_json_error(array('message' => __('Phone number and email are required', 'aakaari')));
        }

        // Generate 6-digit OTP
        $otp = wp_rand(100000, 999999);

        // Store OTP in transient (valid for 10 minutes)
        $transient_key = 'cod_otp_' . md5($phone . $email);
        set_transient($transient_key, $otp, 600);

        // Store attempt count
        $attempt_key = 'cod_otp_attempts_' . md5($phone . $email);
        $attempts = get_transient($attempt_key) ?: 0;
        $attempts++;
        set_transient($attempt_key, $attempts, 3600); // 1 hour

        // Limit to 5 attempts per hour
        if ($attempts > 5) {
            wp_send_json_error(array('message' => __('Too many OTP requests. Please try again later.', 'aakaari')));
        }

        // Send OTP via email
        $sent = $this->send_otp_email($email, $phone, $otp);

        if ($sent) {
            wp_send_json_success(array(
                'message' => __('OTP sent to your email and phone number', 'aakaari'),
                'masked_phone' => $this->mask_phone($phone),
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to send OTP. Please try again.', 'aakaari')));
        }
    }

    /**
     * Verify OTP via AJAX
     */
    public function ajax_verify_otp() {
        check_ajax_referer('cod_otp_nonce', 'nonce');

        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $submitted_otp = sanitize_text_field($_POST['otp'] ?? '');

        if (empty($phone) || empty($email) || empty($submitted_otp)) {
            wp_send_json_error(array('message' => __('All fields are required', 'aakaari')));
        }

        // Get stored OTP
        $transient_key = 'cod_otp_' . md5($phone . $email);
        $stored_otp = get_transient($transient_key);

        if (!$stored_otp) {
            wp_send_json_error(array('message' => __('OTP expired. Please request a new one.', 'aakaari')));
        }

        if ($stored_otp == $submitted_otp) {
            // Store verification status
            $verified_key = 'cod_otp_verified_' . md5($phone . $email);
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
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';

        // Only verify for COD orders
        if ($payment_method !== 'cod') {
            return;
        }

        $phone = isset($_POST['billing_phone']) ? sanitize_text_field($_POST['billing_phone']) : '';
        $email = isset($_POST['billing_email']) ? sanitize_email($_POST['billing_email']) : '';

        // Check if OTP was verified
        $verified_key = 'cod_otp_verified_' . md5($phone . $email);
        $is_verified = get_transient($verified_key);

        if (!$is_verified) {
            wc_add_notice(
                __('Please verify your phone number with OTP for Cash on Delivery orders.', 'aakaari'),
                'error'
            );
        }
    }

    /**
     * Store OTP verification status in order meta
     */
    public function store_otp_verification($order, $data) {
        if ($order->get_payment_method() === 'cod') {
            $phone = $data['billing_phone'] ?? '';
            $email = $data['billing_email'] ?? '';
            $verified_key = 'cod_otp_verified_' . md5($phone . $email);
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
    private function send_otp_email($email, $phone, $otp) {
        $subject = __('Your COD Verification Code - Aakaari', 'aakaari');

        $message = sprintf(
            __('Your verification code for Cash on Delivery order is: %s', 'aakaari'),
            '<strong>' . $otp . '</strong>'
        );
        $message .= '<br><br>';
        $message .= __('This code will expire in 10 minutes.', 'aakaari');
        $message .= '<br><br>';
        $message .= sprintf(__('Phone: %s', 'aakaari'), $this->mask_phone($phone));

        $headers = array('Content-Type: text/html; charset=UTF-8');

        return wp_mail($email, $subject, $message, $headers);
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
