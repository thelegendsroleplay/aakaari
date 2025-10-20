<?php
/**
 * AJAX Handlers for Registration and Login
 */

if (!defined('ABSPATH')) {
    exit;
}

// --- AJAX VALIDATION HANDLERS ---

/**
 * AJAX: Check if email exists
 */
function aakaari_ajax_check_email_exists() {
    check_ajax_referer('aakaari_ajax_nonce', 'nonce');

    if (!isset($_POST['email'])) {
        wp_send_json_error(array('message' => 'No email provided.'));
        exit;
    }

    $email = sanitize_email($_POST['email']);
    if (email_exists($email)) {
        wp_send_json_error(array('message' => 'Email is already registered.'));
    } else {
        wp_send_json_success(array('message' => 'Email is available.'));
    }
    exit;
}
add_action('wp_ajax_nopriv_check_email_exists', 'aakaari_ajax_check_email_exists');
add_action('wp_ajax_check_email_exists', 'aakaari_ajax_check_email_exists'); // For logged-in users? (future proof)

/**
 * AJAX: Check if phone exists
 */
function aakaari_ajax_check_phone_exists() {
    check_ajax_referer('aakaari_ajax_nonce', 'nonce');

    if (!isset($_POST['phone'])) {
        wp_send_json_error(array('message' => 'No phone provided.'));
        exit;
    }

    $phone = sanitize_text_field($_POST['phone']);
    $users = get_users(array(
        'meta_key' => 'phone',
        'meta_value' => $phone,
        'number' => 1,
        'count_total' => false,
    ));

    if (!empty($users)) {
        wp_send_json_error(array('message' => 'Phone number is already registered.'));
    } else {
        wp_send_json_success(array('message' => 'Phone number is available.'));
    }
    exit;
}
add_action('wp_ajax_nopriv_check_phone_exists', 'aakaari_ajax_check_phone_exists');
add_action('wp_ajax_check_phone_exists', 'aakaari_ajax_check_phone_exists');


// --- REGISTRATION OTP HANDLERS ---

/**
 * Get the user ID currently in the verification session.
 * @return int|null
 */
function aakaari_get_verifying_user_id() {
    if (function_exists('WC') && WC()->session) {
        return WC()->session->get('aakaari_user_verifying');
    }
    return null;
}

/**
 * AJAX: Verify Registration OTP
 */
function aakaari_ajax_verify_registration_otp() {
    check_ajax_referer('aakaari_ajax_nonce', 'nonce');
    
    $user_id = aakaari_get_verifying_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'No verification process found. Please register again.'));
        exit;
    }
    
    if (!isset($_POST['otp'])) {
        wp_send_json_error(array('message' => 'Please enter the OTP code.'));
        exit;
    }
    
    $submitted_otp = sanitize_text_field($_POST['otp']);
    
    // Check for expiration
    if (aakaari_is_otp_expired($user_id)) {
        wp_send_json_error(array('message' => 'OTP has expired. Please request a new one.', 'expired' => true));
        exit;
    }
    
    // Check attempts
    $attempts = (int)get_user_meta($user_id, 'otp_verify_attempts', true);
    if ($attempts >= OTP_MAX_VERIFY_ATTEMPTS) {
        wp_send_json_error(array('message' => 'Too many failed attempts. Please request a new code.', 'expired' => true));
        exit;
    }
    
    // Check the code
    $stored_hash = get_user_meta($user_id, 'otp_code', true);
    if (aakaari_verify_otp($submitted_otp, $stored_hash)) {
        // SUCCESS!
        update_user_meta($user_id, 'email_verified', true);
        aakaari_clear_otp_meta($user_id);
        
        // Clear the session
        if (function_exists('WC') && WC()->session) {
             WC()->session->set('aakaari_user_verifying', null);
        }
        
        // Log the user in
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        wp_send_json_success(array('message' => 'Email verified successfully! Redirecting...'));
    } else {
        // FAILED
        update_user_meta($user_id, 'otp_verify_attempts', $attempts + 1);
        $remaining = OTP_MAX_VERIFY_ATTEMPTS - ($attempts + 1);
        wp_send_json_error(array('message' => "Invalid OTP code. You have $remaining attempt(s) remaining."));
    }
    exit;
}
add_action('wp_ajax_nopriv_verify_registration_otp', 'aakaari_ajax_verify_registration_otp');

/**
 * AJAX: Resend Registration OTP
 */
function aakaari_ajax_resend_otp() {
    check_ajax_referer('aakaari_ajax_nonce', 'nonce');
    
    $user_id = aakaari_get_verifying_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'No verification process found. Please register again.'));
        exit;
    }
    
    $user = get_user_by('id', $user_id);
    if (!$user) {
        wp_send_json_error(array('message' => 'User not found.'));
        exit;
    }
    
    // Check resend limit
    $limit_check = aakaari_check_otp_resend_limit($user_id);
    if (!$limit_check['allowed']) {
        wp_send_json_error(array('message' => $limit_check['message']));
        exit;
    }
    
    // Increment resend count
    $resend_count = (int)get_user_meta($user_id, 'otp_resend_count', true);
    update_user_meta($user_id, 'otp_resend_count', $resend_count + 1);
    
    // Generate and send new OTP
    if (aakaari_generate_and_send_otp($user_id, $user->user_email)) {
        wp_send_json_success(array('message' => 'A new OTP has been sent to your email.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to send email. Please try again later.'));
    }
    exit;
}
add_action('wp_ajax_nopriv_resend_otp', 'aakaari_ajax_resend_otp');


// --- LOGIN OTP HANDLERS ---

/**
 * AJAX: Send Login OTP
 */
function aakaari_ajax_send_login_otp() {
    check_ajax_referer('aakaari_ajax_nonce', 'nonce');
    
    if (!isset($_POST['email'])) {
        wp_send_json_error(array('message' => 'Please enter your email address.'));
        exit;
    }
    
    $email = sanitize_email($_POST['email']);
    $user = get_user_by('email', $email);
    
    if (!$user) {
        wp_send_json_error(array('message' => 'No account found with that email address.'));
        exit;
    }
    
    $user_id = $user->ID;
    
    // Check for cooldown
    $cooldown_until = (int)get_user_meta($user_id, 'cooldown_until', true);
    if ($cooldown_until > time()) {
        $minutes = ceil(($cooldown_until - time()) / MINUTE_IN_SECONDS);
        wp_send_json_error(array('message' => "This account is in cooldown. Please wait $minutes minute(s)."));
        exit;
    }
    
    // Check resend limit
    $limit_check = aakaari_check_otp_resend_limit($user_id);
    if (!$limit_check['allowed']) {
        wp_send_json_error(array('message' => $limit_check['message']));
        exit;
    }
    
    // Increment resend count
    $resend_count = (int)get_user_meta($user_id, 'otp_resend_count', true);
    update_user_meta($user_id, 'otp_resend_count', $resend_count + 1);
    
    // Set user in session
    if (function_exists('WC') && WC()->session) {
         WC()->session->set('aakaari_user_verifying', $user_id);
    }
    
    // Generate and send new OTP
    if (aakaari_generate_and_send_otp($user_id, $user->user_email)) {
        wp_send_json_success(array('message' => 'A login code has been sent to your email.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to send email. Please try again later.'));
    }
    exit;
}
add_action('wp_ajax_nopriv_send_login_otp', 'aakaari_ajax_send_login_otp');

/**
 * AJAX: Verify Login OTP
 * (This is identical to the registration verification, but we keep it separate for clarity)
 */
function aakaari_ajax_verify_login_otp() {
    check_ajax_referer('aakaari_ajax_nonce', 'nonce');
    
    $user_id = aakaari_get_verifying_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'No login process found. Please try again.'));
        exit;
    }
    
    if (!isset($_POST['otp'])) {
        wp_send_json_error(array('message' => 'Please enter the login code.'));
        exit;
    }
    
    $submitted_otp = sanitize_text_field($_POST['otp']);
    
    // Check for expiration
    if (aakaari_is_otp_expired($user_id)) {
        wp_send_json_error(array('message' => 'Code has expired. Please request a new one.', 'expired' => true));
        exit;
    }
    
    // Check attempts
    $attempts = (int)get_user_meta($user_id, 'otp_verify_attempts', true);
    if ($attempts >= OTP_MAX_VERIFY_ATTEMPTS) {
        wp_send_json_error(array('message' => 'Too many failed attempts. Please request a new code.', 'expired' => true));
        exit;
    }
    
    // Check the code
    $stored_hash = get_user_meta($user_id, 'otp_code', true);
    if (aakaari_verify_otp($submitted_otp, $stored_hash)) {
        // SUCCESS!
        aakaari_clear_otp_meta($user_id);
        
        // Clear the session
        if (function_exists('WC') && WC()->session) {
             WC()->session->set('aakaari_user_verifying', null);
        }
        
        // Clear failed login counts
        aakaari_clear_failed_logins(null, get_user_by('id', $user_id));
        
        // Log the user in
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        // Check application status for redirect
        $user = get_user_by('id', $user_id);
        $user_email = $user->user_email;
        
        $application_query = new WP_Query(array(
            'post_type'      => 'reseller_application',
            'post_status'    => array('private', 'publish', 'draft', 'pending'),
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'   => 'reseller_email',
                    'value' => $user_email,
                ),
            ),
        ));
        
        $application_status = null;
        if ($application_query->have_posts()) {
            $application_query->the_post();
            $app_id = get_the_ID();
            $terms = wp_get_post_terms($app_id, 'reseller_application_status', array('fields' => 'slugs'));
            if (!is_wp_error($terms) && !empty($terms)) {
                $application_status = $terms[0];
            }
            wp_reset_postdata();
        }
        
        // Determine redirect based on application status
        if (!$application_status) {
            $redirect_url = home_url('/become-a-reseller/');
        } elseif ($application_status === 'approved') {
            $redirect_url = home_url('/dashboard/');
        } else {
            // Pending, rejected, or suspended - dashboard will show appropriate message
            $redirect_url = home_url('/dashboard/');
        }
        
        wp_send_json_success(array('message' => 'Login successful! Redirecting...', 'redirect_url' => $redirect_url));
    } else {
        // FAILED
        update_user_meta($user_id, 'otp_verify_attempts', $attempts + 1);
        $remaining = OTP_MAX_VERIFY_ATTEMPTS - ($attempts + 1);
        wp_send_json_error(array('message' => "Invalid code. You have $remaining attempt(s) remaining."));
    }
    exit;
}
add_action('wp_ajax_nopriv_verify_login_otp', 'aakaari_ajax_verify_login_otp');