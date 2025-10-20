<?php
/**
 * Reseller Registration System
 * * This file contains:
 * - Registration page assets
 * - AJAX registration handler
 * - User role creation
 * - Registration form processing
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// --- UNVERIFIED USER CLEANUP CONFIG ---
// Users unverified after this time will be deleted (1 hour = 3600 seconds)
define('UNVERIFIED_USER_LIFETIME', 1 * HOUR_IN_SECONDS);
define('UNVERIFIED_USER_CRON_HOOK', 'aakaari_cleanup_unverified_users');

/**
 * Schedule the cleanup event on theme activation/init
 */
function aakaari_schedule_user_cleanup() {
    // Schedule a daily event to check for old unverified users
    if (!wp_next_scheduled(UNVERIFIED_USER_CRON_HOOK)) {
        wp_schedule_event(time(), 'daily', UNVERIFIED_USER_CRON_HOOK);
    }
}
add_action('init', 'aakaari_schedule_user_cleanup');

/**
 * Cleanup job: Deletes reseller users who are unverified and older than the defined lifetime.
 */
function aakaari_cleanup_unverified_users_job() {
    // Calculate the time threshold for deletion
    $cutoff_time = time() - UNVERIFIED_USER_LIFETIME;
    
    $args = array(
        'role'       => 'reseller', // Only target resellers
        'meta_query' => array(
            'relation' => 'AND',
            array(
                // Find users where email_verified is NOT 'true'
                'key'     => 'email_verified',
                'value'   => 'true',
                'compare' => '!='
            ),
            array(
                // Find users who haven't verified within the lifetime period
                // We use 'otp_generated_at' as the creation/last-attempt time marker (set in otp-service.php)
                'key'     => 'otp_generated_at', 
                'value'   => $cutoff_time,
                'compare' => '<',
                'type'    => 'NUMERIC'
            )
        )
    );
    
    $unverified_users = get_users($args);
    
    foreach ($unverified_users as $user) {
        // Delete the user permanently.
        wp_delete_user($user->ID); 
    }
}
add_action(UNVERIFIED_USER_CRON_HOOK, 'aakaari_cleanup_unverified_users_job');

// --- END UNVERIFIED USER CLEANUP CONFIG ---

/**
 * Enqueue scripts and styles for the Registration page
 */
function enqueue_reseller_registration_assets() {
    // Only load these on the 'Reseller Registration' page template
    if (is_page_template('register.php')) {
        // Stylesheet
        wp_enqueue_style(
            'reseller-registration-style',
            get_template_directory_uri() . '/assets/css/register.css',
            array(),
            '1.0.0'
        );

        // JavaScript
        wp_enqueue_script(
            'reseller-registration-script',
            get_template_directory_uri() . '/assets/js/register.js',
            array('jquery'), // Add jquery as dependency
            '1.0.0',
            true
        );

        // Localize script with data
        wp_localize_script('reseller-registration-script', 'registration_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('aakaari_ajax_nonce'), // Single nonce for all AJAX
            'login_url' => wc_get_page_permalink('myaccount'),
            'dashboard_url' => home_url('/dashboard/'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_reseller_registration_assets');

/**
 * Handle reseller registration via AJAX
 * This is now the *first step* of registration.
 */
function handle_reseller_registration() {
    // Verify nonce
    if (!isset($_POST['reseller_registration_nonce']) || 
        !wp_verify_nonce($_POST['reseller_registration_nonce'], 'aakaari_ajax_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
        exit;
    }

    // --- Honeypot Bot Check ---
    if (!empty($_POST['aakaari_hp'])) {
        wp_send_json_error(array('message' => 'Bot detected.'));
        exit;
    }

    // Get and sanitize form data
    $fullName = sanitize_text_field($_POST['fullName']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $businessName = sanitize_text_field($_POST['businessName']);
    $businessType = sanitize_text_field($_POST['businessType']);
    $city = sanitize_text_field($_POST['city']);
    $state = sanitize_text_field($_POST['state']);

    // --- Server-Side Validation ---
    if (empty($fullName) || empty($email) || empty($phone) || empty($password) || 
        empty($city) || empty($state)) {
        wp_send_json_error(array('message' => 'Please fill all required fields.'));
        exit;
    }
    
    if ($password !== $confirmPassword) {
        wp_send_json_error(array('message' => 'Passwords do not match.'));
        exit;
    }

    // Password strength check (as requested)
    if (strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[!@#$%^&*()]/', $password)) {
        wp_send_json_error(array('message' => 'Password does not meet security requirements.'));
        exit;
    }

    // Check if email already exists
if (email_exists($email)) {
    // NEW: Check if user exists but is unverified
    $existing_user = get_user_by('email', $email);
    if ($existing_user && !get_user_meta($existing_user->ID, 'email_verified', true)) {
        // User exists but is unverified - resend OTP and redirect to verification
        aakaari_generate_and_send_otp($existing_user->ID, $email);
        
        // Store the user ID in session
        if (function_exists('WC') && WC()->session) {
            WC()->session->set('aakaari_user_verifying', $existing_user->ID);
        }
        
        wp_send_json_success(array(
            'message' => 'Account found but not verified. We\'ve sent a new verification code to your email.',
            'otp_required' => true,
            'email' => $email
        ));
        exit;
    }
    
    // Otherwise, standard error for existing email
    wp_send_json_error(array('message' => 'This email address is already registered. Please login instead.'));
    exit;
}

    // Check if phone number already exists
    $users_with_phone = get_users(array(
        'meta_key' => 'phone',
        'meta_value' => $phone,
        'number' => 1,
        'count_total' => false
    ));
    if (!empty($users_with_phone)) {
        wp_send_json_error(array('message' => 'This phone number is already registered. Please use another number.'));
        exit;
    }

    // --- Create User ---
    
    // Create username from email
    $username = sanitize_user(explode('@', $email)[0] . rand(10, 99));
    // Ensure username is unique
    while(username_exists($username)) {
        $username = sanitize_user(explode('@', $email)[0] . rand(100, 999));
    }
    
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error(array('message' => $user_id->get_error_message()));
        exit;
    }

    // Set user role to reseller
    $user = new WP_User($user_id);
    $user->set_role('reseller');
    $user->set_display_name($fullName);
    wp_update_user($user);

    // Save user meta data
    update_user_meta($user_id, 'full_name', $fullName);
    update_user_meta($user_id, 'phone', $phone);
    update_user_meta($user_id, 'business_name', $businessName);
    update_user_meta($user_id, 'business_type', $businessType);
    update_user_meta($user_id, 'city', $city);
    update_user_meta($user_id, 'state', $state);
    update_user_meta($user_id, 'onboarding_status', 'pending'); // 'pending' until they fill the kyc form
    update_user_meta($user_id, 'email_verified', false); // NOT verified yet
    
    // Initialize wallet balance
    update_user_meta($user_id, 'wallet_balance', 0);
    update_user_meta($user_id, 'wallet_transactions', array());

    // --- Trigger OTP Flow ---
    // This function is in `inc/security-features.php`
    aakaari_modify_registration_flow($user_id);
    
    // Send notification to admin
    send_reseller_registration_admin_notification($fullName, $email, $phone, $businessName, $city, $state);

    // --- Send Success Response ---
    // The JavaScript will use this to show the OTP screen.
    wp_send_json_success(array(
        'message' => 'Registration successful! Please check your email for a verification code.',
        'otp_required' => true,
        'email' => $email
    ));
    exit;
}
// Note: We use the *same* action name as in your original file
add_action('wp_ajax_nopriv_reseller_register', 'handle_reseller_registration');
add_action('wp_ajax_reseller_register', 'handle_reseller_registration'); 

/**
 * Send admin notification for new reseller registration
 */
function send_reseller_registration_admin_notification($name, $email, $phone, $businessName, $city, $state) {
    $admin_email = get_option('admin_email');
    $subject = 'New Reseller Registration: ' . $name;
    
    $message = "A new reseller has registered:\n\n";
    $message .= "Name: $name\n";
    $message .= "Email: $email\n";
    $message .= "Phone: $phone\n";
    $message .= "Business Name: $businessName\n";
    $message .= "City: $city, State: $state\n\n";
    $message .= "Their account is pending email verification and onboarding.\n";
    $message .= "View user profile: " . admin_url('user-edit.php?user_id=' . get_user_by('email', $email)->ID);
    
    wp_mail($admin_email, $subject, $message);
}

// NOTE: The `send_reseller_welcome_email` function is no longer needed here,
// as the `otp-service.php` file now handles the initial email.

/**
 * Create custom reseller user role
 */
function aakaari_create_reseller_role() {
    // Check if role already exists
    if (get_role('reseller')) {
        return;
    }
    
    // Add reseller role with specific capabilities (copied from customer)
    $customer_caps = get_role('customer')->capabilities;
    add_role(
        'reseller',
        'Reseller',
        $customer_caps
    );
}
add_action('init', 'aakaari_create_reseller_role');

// Add this inside inc/reseller-application.php

/**
 * Register Reseller Application Status Taxonomy
 */
function register_reseller_application_status_taxonomy() {
    $labels = array(
        'name'              => _x( 'Application Statuses', 'taxonomy general name', 'aakaari' ),
        'singular_name'     => _x( 'Application Status', 'taxonomy singular name', 'aakaari' ),
        'search_items'      => __( 'Search Statuses', 'aakaari' ),
        'all_items'         => __( 'All Statuses', 'aakaari' ),
        'parent_item'       => __( 'Parent Status', 'aakaari' ),
        'parent_item_colon' => __( 'Parent Status:', 'aakaari' ),
        'edit_item'         => __( 'Edit Status', 'aakaari' ),
        'update_item'       => __( 'Update Status', 'aakaari' ),
        'add_new_item'      => __( 'Add New Status', 'aakaari' ),
        'new_item_name'     => __( 'New Status Name', 'aakaari' ),
        'menu_name'         => __( 'Statuses', 'aakaari' ),
    );
    $args = array(
        'hierarchical'      => false, // like tags
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'reseller-application-status' ),
        'public'            => false, // Not needed on front-end usually
    );
    register_taxonomy( 'reseller_application_status', array( 'reseller_application' ), $args );

    // Optional: Pre-register default terms if they don't exist
    wp_insert_term('pending', 'reseller_application_status');
    wp_insert_term('approved', 'reseller_application_status');
    wp_insert_term('rejected', 'reseller_application_status');
}
add_action( 'init', 'register_reseller_application_status_taxonomy', 0 ); // Register taxonomy early