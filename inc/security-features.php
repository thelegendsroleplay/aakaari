<?php
/**
 * Login & Registration Security Features
 * * - Failed login attempt tracking and progressive cooldown
 * - Hooks to modify registration and login flows
 */

if (!defined('ABSPATH')) {
    exit;
}

// --- FAILED LOGIN COOLDOWN ---

define('FAILED_LOGIN_COOLDOWN_INCREMENT', 15 * MINUTE_IN_SECONDS); // 15 minutes
define('FAILED_LOGIN_ATTEMPT_LIMIT', 3);
define('FAILED_LOGIN_RESET_PERIOD', 3 * DAY_IN_SECONDS); // 3 days

/**
 * HOOK: Check for cooldown *before* processing login.
 *
 * @param WP_User|WP_Error|null $user
 * @param string $username
 * @return WP_User|WP_Error
 */
function aakaari_check_login_cooldown($user, $username) {
    if (is_wp_error($user) || !$username) {
        return $user; // Pass through existing errors or empty submissions
    }

    $user_obj = get_user_by('login', $username) ?: get_user_by('email', $username);

    if (!$user_obj) {
        return $user; // User doesn't exist, let default process handle it
    }

    $user_id = $user_obj->ID;
    $cooldown_until = (int)get_user_meta($user_id, 'cooldown_until', true);

    // Check if cooldown is active
    if ($cooldown_until > time()) {
        $remaining = $cooldown_until - time();
        $minutes = ceil($remaining / MINUTE_IN_SECONDS);
        return new WP_Error(
            'login_cooldown',
            sprintf(
                'Too many failed login attempts. Please wait %d minute(s) before trying again.',
                $minutes
            )
        );
    }
    
    // Check if we need to reset the counter
    $last_failed = (int)get_user_meta($user_id, 'last_failed_attempt', true);
    if ($last_failed && (time() - $last_failed) > FAILED_LOGIN_RESET_PERIOD) {
        delete_user_meta($user_id, 'failed_login_count');
        delete_user_meta($user_id, 'last_failed_attempt');
        delete_user_meta($user_id, 'cooldown_until');
    }

    return $user;
}
add_filter('authenticate', 'aakaari_check_login_cooldown', 25, 2); // Priority 25, 2 args

/**
 * HOOK: Record a failed login attempt.
 *
 * @param string $username
 */
function aakaari_record_failed_login($username) {
    $user_obj = get_user_by('login', $username) ?: get_user_by('email', $username);

    if (!$user_obj) {
        return; // Don't track fails for non-existent users
    }

    $user_id = $user_obj->ID;
    
    // Increment fail count
    $count = (int)get_user_meta($user_id, 'failed_login_count', true);
    $count++;
    update_user_meta($user_id, 'failed_login_count', $count);
    update_user_meta($user_id, 'last_failed_attempt', time());

    // Check if cooldown should be applied
    if ($count >= FAILED_LOGIN_ATTEMPT_LIMIT) {
        $previous_cooldown = (int)get_user_meta($user_id, 'cooldown_until', true);
        $cooldown_base = ($previous_cooldown > time()) ? $previous_cooldown : time();
        
        // Progressive cooldown logic
        $fail_beyond_limit = $count - FAILED_LOGIN_ATTEMPT_LIMIT; // 0 on 3rd attempt, 1 on 4th, etc.
        $total_cooldown = ($fail_beyond_limit * FAILED_LOGIN_COOLDOWN_INCREMENT) + FAILED_LOGIN_COOLDOWN_INCREMENT; // 15min, 30min, 45min...
        
        $new_cooldown_until = time() + $total_cooldown;
        
        // If 3rd attempt, 15 min. 4th, 30min. 5th, 45min.
        $cooldown_levels = [
            3 => 15 * MINUTE_IN_SECONDS,
            4 => 30 * MINUTE_IN_SECONDS,
            5 => 45 * MINUTE_IN_SECONDS,
            6 => 60 * MINUTE_IN_SECONDS,
        ];
        
        // Simpler progressive: +15 min from *last* cooldown
        // Let's use your requested logic: 3->15, 4->30, 5->45
        
        $attempts_to_calc = $count - (FAILED_LOGIN_ATTEMPT_LIMIT - 1); // 3->1, 4->2, 5->3
        $new_cooldown_duration = $attempts_to_calc * FAILED_LOGIN_COOLDOWN_INCREMENT; // 1*15, 2*15, 3*15
        
        update_user_meta($user_id, 'cooldown_until', time() + $new_cooldown_duration);
    }
}
add_action('wp_login_failed', 'aakaari_record_failed_login', 10, 1);


/**
 * HOOK: Clear failed login attempts on successful login.
 *
 * @param string $user_login
 * @param WP_User $user
 */
function aakaari_clear_failed_logins($user_login, $user) {
    $user_id = $user->ID;
    delete_user_meta($user_id, 'failed_login_count');
    delete_user_meta($user_id, 'last_failed_attempt');
    delete_user_meta($user_id, 'cooldown_until');
}
add_action('wp_login', 'aakaari_clear_failed_logins', 10, 2);


// --- REGISTRATION & LOGIN FLOW MODIFICATION ---

/**
 * HOOK: Intercept user login to check for email verification.
 *
 * @param WP_User $user
 * @param string $password
 * @return WP_User|WP_Error
 */
function aakaari_check_email_verification_on_login($user, $password) {
    if (is_wp_error($user)) {
        return $user;
    }

    $user_id = $user->ID;
    $email_verified = get_user_meta($user_id, 'email_verified', true);

if (!$email_verified) {
        // User exists, password is correct, but email is not verified.

        // Send them a new OTP.
        $otp_sent = aakaari_generate_and_send_otp($user_id, $user->user_email);

        // Store user ID in the session *using our helper function*
        aakaari_set_verifying_user_id($user_id); // This ensures WC or PHP session is used correctly

        // Construct the error message
        $error_message = 'Your account is not verified. ';
        if ($otp_sent) {
            $error_message .= 'We have sent a new OTP to your email address. ';
        } else {
            // Log if email sending failed, inform user slightly differently
            error_log("Aakaari: Failed to send OTP during login verification for user ID: $user_id");
            $error_message .= 'We attempted to send a new OTP, but there was an issue. ';
        }
        $error_message .= 'Please check your email and verify your account on the <a href="' . esc_url(home_url('/register/')) . '">verification page</a>.';


        // Return an error telling them to check their email
        return new WP_Error(
            'email_not_verified',
             $error_message // Use wp_kses_post if needed, but WP_Error often handles HTML okay
        );
    }

    return $user;
}
add_filter('wp_authenticate_user', 'aakaari_check_email_verification_on_login', 20, 2);

/**
 * Helper function to set the user ID that needs verification in the session.
 *
 * @param int $user_id
 */
function aakaari_set_verifying_user_id($user_id) {
    // Check if the WooCommerce session is active and use it
    if (function_exists('WC') && WC()->session) {
        WC()->session->set('aakaari_user_verifying', $user_id);
    }
}


/**
 * HOOK: Redirect user to OTP verification screen after registration.
 *
 * @param int $user_id
 */
function aakaari_modify_registration_flow($user_id) {
    // This function is called by our custom AJAX handler, not a standard WP hook.
    // We send a JSON response instead of redirecting.
    
    $user = get_user_by('id', $user_id);
    if (!$user) {
        return; // Should not happen
    }
    
    // 1. Set email as unverified
    update_user_meta($user_id, 'email_verified', false);
    
    // 2. Generate and send the first OTP
    $email_sent = aakaari_generate_and_send_otp($user_id, $user->user_email);
    
    if (!$email_sent) {
        // This is a problem. The user was created, but email failed.
        // We'll have to let them use the "Resend OTP" function.
        // Log this error for the admin.
        error_log("Aakaari: Failed to send initial OTP for new user ID: $user_id");
    }
    
    // 3. Set a session/cookie to identify the user on the OTP page
    if (function_exists('WC') && WC()->session) {
         WC()->session->set('aakaari_user_verifying', $user_id);
    }
}


/**
 * HOOK: Add OTP verification fields to login form (for 'Login with OTP')
 */
function aakaari_add_login_otp_fields() {
    // We will modify login.php directly to add these fields,
    // as hooking into 'login_form' can be messy with custom styling.
}
// add_action('login_form', 'aakaari_add_login_otp_fields');

/**
 * HOOK: Add honeypot field to registration form
 */
function aakaari_add_honeypot_field() {
    // We will add this directly to register.php for better control over placement
    // echo '<p class="aakaari-hp-field" style="display:none !important;" aria-hidden="true"><label for="aakaari_hp">Leave this field empty</label><input type="text" name="aakaari_hp" id="aakaari_hp" tabindex="-1" autocomplete="off"></p>';
}
// add_action('register_form', 'aakaari_add_honeypot_field');