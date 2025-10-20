<?php
/**
 * OTP (One-Time Password) Service
 * * Handles generation, storage, sending, and verification of OTPs.
 */

if (!defined('ABSPATH')) {
    exit;
}

// --- OTP Configuration ---
define('OTP_EXPIRY_TIME', 10 * MINUTE_IN_SECONDS); // 10 minutes
define('OTP_MAX_VERIFY_ATTEMPTS', 3);
define('OTP_MAX_RESEND_ATTEMPTS', 5);

/**
 * Generate a secure 6-digit OTP.
 * @return string
 */
function aakaari_generate_otp() {
    return (string)rand(100000, 999999);
}

/**
 * Encrypts the OTP for database storage.
 * @param string $otp
 * @return string
 */
function aakaari_encrypt_otp($otp) {
    // Simple reversible encryption. For production, consider a more robust method.
    // This is NOT secure hashing, as we need to compare the plain text.
    // Using wp_hash_password is one-way. We'll use a simple method for this example.
    // A better way would be to hash the OTP and store the hash, then hash the user input and compare.
    // But for expiry, we often store the hash *and* the timestamp.
    
    // Let's store a hash instead.
    return wp_hash_password($otp);
}

/**
 * Verifies a user-submitted OTP against the stored hash.
 * @param string $submitted_otp
 * @param string $stored_hash
 * @return bool
 */
function aakaari_verify_otp($submitted_otp, $stored_hash) {
    global $wp_hasher;
    if (empty($wp_hasher)) {
        require_once ABSPATH . WPINC . '/class-phpass.php';
        $wp_hasher = new PasswordHash(8, true);
    }
    return $wp_hasher->CheckPassword($submitted_otp, $stored_hash);
}


/**
 * Send the OTP email to the user.
 * @param string $email
 * @param string $otp
 * @return bool
 */
function aakaari_send_otp_email($email, $otp) {
    $subject = 'Your Verification Code for Aakaari';
    $message = "Your Aakaari verification code is: $otp\n\n";
    $message .= "This code will expire in 10 minutes.\n\n";
    $message .= "If you did not request this, please ignore this email.";
    
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    
    return wp_mail($email, $subject, $message, $headers);
}

/**
 * Generates and stores a new OTP for a user.
 * Resets attempts and updates timestamp.
 *
 * @param int $user_id
 * @param string $email
 * @return bool True on success, false on failure (e.g., email failed to send)
 */
function aakaari_generate_and_send_otp($user_id, $email) {
    $otp = aakaari_generate_otp();
    $otp_hash = aakaari_encrypt_otp($otp);
    
    update_user_meta($user_id, 'otp_code', $otp_hash);
    update_user_meta($user_id, 'otp_generated_at', time());
    update_user_meta($user_id, 'otp_verify_attempts', 0); // Reset verify attempts
    
    // Send the plain text OTP via email
    return aakaari_send_otp_email($email, $otp);
}

/**
 * Checks if a user is allowed to request a resend.
 * @param int $user_id
 * @return array ['allowed' => bool, 'message' => string]
 */
function aakaari_check_otp_resend_limit($user_id) {
    $resend_count = (int)get_user_meta($user_id, 'otp_resend_count', true);
    
    if ($resend_count >= OTP_MAX_RESEND_ATTEMPTS) {
        return [
            'allowed' => false,
            'message' => 'You have exceeded the maximum number of resend requests. Please contact support.'
        ];
    }
    
    return ['allowed' => true, 'message' => ''];
}

/**
 * Checks if the stored OTP for a user is expired.
 * @param int $user_id
 * @return bool
 */
function aakaari_is_otp_expired($user_id) {
    $generated_at = (int)get_user_meta($user_id, 'otp_generated_at', true);
    return (time() - $generated_at) > OTP_EXPIRY_TIME;
}

/**
 * Clears all OTP meta from a user profile.
 * Call this after successful verification.
 * @param int $user_id
 */
function aakaari_clear_otp_meta($user_id) {
    delete_user_meta($user_id, 'otp_code');
    delete_user_meta($user_id, 'otp_generated_at');
    delete_user_meta($user_id, 'otp_verify_attempts');
    delete_user_meta($user_id, 'otp_resend_count');
}