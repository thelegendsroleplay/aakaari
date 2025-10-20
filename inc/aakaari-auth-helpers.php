<?php
/**
 * Aakaari Authentication Helper Functions
 *
 * @package Aakaari
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the password regex pattern.
 *
 * @return string
 */
function aakaari_get_password_regex() {
	// 8 chars, 1 uppercase, 1 number, 1 special char.
	return '^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$';
}

/**
 * Get the password requirements as an HTML string.
 *
 * @return string
 */
function aakaari_get_password_requirements_html() {
	return '
    <ul class="password-requirements">
        <li data-req="length">At least 8 characters</li>
        <li data-req="uppercase">1 uppercase letter (A-Z)</li>
        <li data-req="number">1 number (0-9)</li>
        <li data-req="special">1 special character (!@#$%^&*)</li>
    </ul>
    ';
}

/**
 * Generate a secure 6-digit OTP.
 *
 * @return string
 */
function aakaari_generate_otp() {
	return (string) rand( 100000, 999999 );
}

/**
 * Send the OTP email to the user.
 *
 * @param string $email The user's email address.
 * @param string $otp   The OTP to send.
 * @return bool
 */
function aakaari_send_otp_email( $email, $otp ) {
	$subject = 'Your Verification Code for Aakaari';
	$message = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <h2>Verify Your Aakaari Account</h2>
        <p>Hello,</p>
        <p>Thank you for registering. Please use the following One-Time Password (OTP) to verify your email address. This code is valid for 10 minutes.</p>
        <p style='font-size: 24px; font-weight: bold; letter-spacing: 2px; color: #1D4ED8; margin: 20px 0;'>
            {$otp}
        </p>
        <p>If you did not request this, please ignore this email.</p>
        <p>Thanks,<br>The Aakaari Team</p>
    </div>
    ";

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );

	return wp_mail( $email, $subject, $message, $headers );
}

/**
 * Hash the OTP for database storage.
 *
 * @param string $otp The plaintext OTP.
 * @return string The hashed OTP.
 */
function aakaari_hash_otp( $otp ) {
	return wp_hash_password( $otp );
}

/**
 * Verify the user-submitted OTP against the stored hash.
 *
 * @param string $otp_submitted The plaintext OTP from the user.
 * @param string $otp_hash      The hashed OTP from user meta.
 * @return bool
 */
function aakaari_verify_otp( $otp_submitted, $otp_hash ) {
	if ( ! $otp_submitted || ! $otp_hash ) {
		return false;
	}
	return wp_check_password( $otp_submitted, $otp_hash, false );
}

/**
 * Get user meta for OTP, with defaults.
 *
 * @param int $user_id The user ID.
 * @return array
 */
function aakaari_get_otp_meta( $user_id ) {
	return array(
		'hash'      => get_user_meta( $user_id, '_aakaari_otp_code', true ),
		'timestamp' => (int) get_user_meta( $user_id, '_aakaari_otp_generated_at', true ),
		'attempts'  => (int) get_user_meta( $user_id, '_aakaari_otp_attempts', true ),
		'resends'   => (int) get_user_meta( $user_id, '_aakaari_otp_resend_count', true ),
	);
}

/**
 * Clear all OTP meta for a user.
 *
 * @param int $user_id The user ID.
 */
function aakaari_clear_otp_meta( $user_id ) {
	delete_user_meta( $user_id, '_aakaari_otp_code' );
	delete_user_meta( $user_id, '_aakaari_otp_generated_at' );
	delete_user_meta( $user_id, '_aakaari_otp_attempts' );
	delete_user_meta( $user_id, '_aakaari_otp_resend_count' );
}

/**
 * Create and store a new OTP for a user.
 *
 * @param int $user_id The user ID.
 * @return string The new plaintext OTP.
 */
function aakaari_create_and_store_otp( $user_id ) {
	$otp = aakaari_generate_otp();
	$hash = aakaari_hash_otp( $otp );

	update_user_meta( $user_id, '_aakaari_otp_code', $hash );
	update_user_meta( $user_id, '_aakaari_otp_generated_at', time() );
	update_user_meta( $user_id, '_aakaari_otp_attempts', 0 ); // Reset attempts on new OTP

	return $otp;
}