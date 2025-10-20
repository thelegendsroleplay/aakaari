<?php
/**
 * OTP Service
 * Handles OTP generation, validation, and related functions
 */

if (!defined('ABSPATH')) {
    exit;
}

// OTP configuration
define('AAKAARI_OTP_LENGTH', 6);  // 6-digit OTP
define('AAKAARI_OTP_EXPIRY', 15 * MINUTE_IN_SECONDS);  // 15 minutes expiry
define('AAKAARI_OTP_RESEND_DELAY', 60);  // 60 seconds between resends

/**
 * Generate a random OTP
 *
 * @return string The generated OTP
 */
function aakaari_generate_otp() {
    $otp = '';
    for ($i = 0; $i < AAKAARI_OTP_LENGTH; $i++) {
        $otp .= rand(0, 9);
    }
    return $otp;
}

/**
 * Generate and send OTP to user email
 *
 * @param int $user_id User ID
 * @param string $email User email
 * @return bool Whether OTP was sent successfully
 */
function aakaari_generate_and_send_otp($user_id, $email) {
    // Check for resend delay
    $last_sent = get_user_meta($user_id, 'otp_generated_at', true);
    if ($last_sent && (time() - $last_sent) < AAKAARI_OTP_RESEND_DELAY) {
        return false; // Too soon to resend
    }

    // Generate OTP
    $otp = aakaari_generate_otp();
    
    // Store OTP in user meta with timestamp
    update_user_meta($user_id, 'email_verification_otp', $otp);
    update_user_meta($user_id, 'otp_generated_at', time());
    
    // Send email with OTP
    $subject = 'Your Verification Code - Aakaari';
    $message = "Hello,\n\n";
    $message .= "Your verification code for Aakaari is: $otp\n\n";
    $message .= "This code will expire in 15 minutes.\n\n";
    $message .= "If you didn't request this code, please ignore this email.\n\n";
    $message .= "Thank you,\nAakaari Team";
    
    $sent = wp_mail($email, $subject, $message);
    
    return $sent;
}

/**
 * Validate OTP submitted by user
 *
 * @param int $user_id User ID
 * @param string $submitted_otp OTP submitted by user
 * @return bool|WP_Error True if valid, WP_Error if invalid
 */
function aakaari_validate_otp($user_id, $submitted_otp) {
    // Get stored OTP
    $stored_otp = get_user_meta($user_id, 'email_verification_otp', true);
    $generated_at = get_user_meta($user_id, 'otp_generated_at', true);
    
    // Check if OTP exists
    if (empty($stored_otp)) {
        return new WP_Error('invalid_otp', 'No verification code found. Please request a new code.');
    }
    
    // Check if OTP has expired
    if (time() - $generated_at > AAKAARI_OTP_EXPIRY) {
        return new WP_Error('expired_otp', 'Verification code has expired. Please request a new code.');
    }
    
    // Check if OTP matches
    if ($submitted_otp !== $stored_otp) {
        return new WP_Error('incorrect_otp', 'Incorrect verification code. Please try again.');
    }
    
    // OTP is valid
    return true;
}

/**
 * Mark user as verified after successful OTP validation
 *
 * @param int $user_id User ID
 */
function aakaari_mark_user_verified($user_id) {
    update_user_meta($user_id, 'email_verified', true);
    
    // Clean up OTP data
    delete_user_meta($user_id, 'email_verification_otp');
    delete_user_meta($user_id, 'otp_generated_at');
    
    // Clear verification session
    if (function_exists('WC') && WC()->session) {
        WC()->session->set('aakaari_user_verifying', null);
    }
    
    // Remove any verification cookies
    if (isset($_COOKIE['aakaari_pending_verification'])) {
        setcookie('aakaari_pending_verification', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
    }
    
    // Send welcome email now that the user is verified
    aakaari_send_verified_welcome_email($user_id);
}

/**
 * Send welcome email to verified users
 *
 * @param int $user_id User ID
 */
function aakaari_send_verified_welcome_email($user_id) {
    $user = get_user_by('id', $user_id);
    if (!$user) return;
    
    $subject = 'Welcome to Aakaari!';
    $message = "Hello " . get_user_meta($user_id, 'full_name', true) . ",\n\n";
    $message .= "Thank you for verifying your email address. Your Aakaari reseller account is now active!\n\n";
    $message .= "Login here: " . home_url('/login/') . "\n\n";
    $message .= "Thank you for joining us!\n";
    $message .= "The Aakaari Team";
    
    wp_mail($user->user_email, $subject, $message);
}

/**
 * AJAX handler for OTP verification
 */
function aakaari_handle_otp_verification() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aakaari_ajax_nonce')) {
        wp_send_json_error(['message' => 'Security check failed.']);
        exit;
    }
    
    // Get user ID from session
    $user_id = null;
    if (function_exists('WC') && WC()->session) {
        $user_id = WC()->session->get('aakaari_user_verifying');
    }
    
    // Try to get user ID from email if not in session
    if (!$user_id && isset($_POST['email'])) {
        $email = sanitize_email($_POST['email']);
        $user = get_user_by('email', $email);
        if ($user) {
            $user_id = $user->ID;
            // Save to session
            if (function_exists('WC') && WC()->session) {
                WC()->session->set('aakaari_user_verifying', $user_id);
            }
        }
    }
    
    // Check if we have a user ID
    if (!$user_id) {
        wp_send_json_error(['message' => 'No verification process found. Please register again.']);
        exit;
    }
    
    // Get OTP from POST
    $submitted_otp = sanitize_text_field($_POST['otp']);
    if (empty($submitted_otp)) {
        wp_send_json_error(['message' => 'Please enter a verification code.']);
        exit;
    }
    
    // Validate OTP
    $validation_result = aakaari_validate_otp($user_id, $submitted_otp);
    
    if (is_wp_error($validation_result)) {
        wp_send_json_error(['message' => $validation_result->get_error_message()]);
        exit;
    }
    
    // Mark user as verified
    aakaari_mark_user_verified($user_id);
    
    // Send success response
    wp_send_json_success([
        'message' => 'Email verified successfully!',
        'redirect_url' => home_url('/login/?verification=success')
    ]);
    exit;
}
add_action('wp_ajax_nopriv_verify_otp', 'aakaari_handle_otp_verification');
add_action('wp_ajax_verify_otp', 'aakaari_handle_otp_verification');

/**
 * AJAX handler for OTP resend
 */
function aakaari_handle_otp_resend() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aakaari_ajax_nonce')) {
        wp_send_json_error(['message' => 'Security check failed.']);
        exit;
    }
    
    // Get user ID from session
    $user_id = null;
    if (function_exists('WC') && WC()->session) {
        $user_id = WC()->session->get('aakaari_user_verifying');
    }
    
    // Try to get user ID from email if not in session
    if (!$user_id && isset($_POST['email'])) {
        $email = sanitize_email($_POST['email']);
        $user = get_user_by('email', $email);
        if ($user) {
            $user_id = $user->ID;
            // Save to session
            if (function_exists('WC') && WC()->session) {
                WC()->session->set('aakaari_user_verifying', $user_id);
            }
        }
    }
    
    // Check if we have a user ID
    if (!$user_id) {
        wp_send_json_error(['message' => 'User not found.']);
        exit;
    }
    
    $user = get_user_by('id', $user_id);
    
    // Send new OTP
    $sent = aakaari_generate_and_send_otp($user_id, $user->user_email);
    
    if (!$sent) {
        $last_sent = get_user_meta($user_id, 'otp_generated_at', true);
        $wait_time = AAKAARI_OTP_RESEND_DELAY - (time() - $last_sent);
        if ($wait_time > 0) {
            wp_send_json_error([
                'message' => "Please wait {$wait_time} seconds before requesting a new code."
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to send verification code.']);
        }
        exit;
    }
    
    wp_send_json_success(['message' => 'A new verification code has been sent to your email.']);
    exit;
}
add_action('wp_ajax_nopriv_resend_otp', 'aakaari_handle_otp_resend');
add_action('wp_ajax_resend_otp', 'aakaari_handle_otp_resend');