<?php
/**
 * Reseller Registration System
 * 
 * This file contains:
 * - Registration page assets
 * - AJAX registration handler
 * - User role creation
 * - Registration form processing
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue scripts and styles for the Registration page
 */
function enqueue_reseller_registration_assets() {
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
            array(),
            '1.0.0',
            true
        );

        // Localize script with data
        wp_localize_script('reseller-registration-script', 'registration_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('reseller_registration_nonce'),
            'login_url' => wc_get_page_permalink('myaccount'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_reseller_registration_assets');

/**
 * Handle reseller registration via AJAX
 */
function handle_reseller_registration() {
    // Verify nonce
    if (!isset($_POST['reseller_registration_nonce']) || 
        !wp_verify_nonce($_POST['reseller_registration_nonce'], 'reseller_register')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
        exit;
    }

    // Get and sanitize form data
    $fullName = sanitize_text_field($_POST['fullName']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $password = $_POST['password'];
    $businessName = sanitize_text_field($_POST['businessName']);
    $businessType = sanitize_text_field($_POST['businessType']);
    $city = sanitize_text_field($_POST['city']);
    $state = sanitize_text_field($_POST['state']);

    // Validate required fields
    if (empty($fullName) || empty($email) || empty($phone) || empty($password) || 
        empty($city) || empty($state)) {
        wp_send_json_error(array('message' => 'Please fill all required fields.'));
        exit;
    }

    // Validate password length
    if (strlen($password) < 8) {
        wp_send_json_error(array('message' => 'Password must be at least 8 characters long.'));
        exit;
    }

    // Check if email already exists
    if (email_exists($email)) {
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

    // Create username
    $username = sanitize_user(strtolower(str_replace(' ', '', $fullName)) . rand(100, 999));
    
    // Create new user
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error(array('message' => $user_id->get_error_message()));
        exit;
    }

    // Set user role to reseller
    $user = new WP_User($user_id);
    $user->set_role('reseller');

    // Save user meta data
    update_user_meta($user_id, 'full_name', $fullName);
    update_user_meta($user_id, 'phone', $phone);
    update_user_meta($user_id, 'business_name', $businessName);
    update_user_meta($user_id, 'business_type', $businessType);
    update_user_meta($user_id, 'city', $city);
    update_user_meta($user_id, 'state', $state);
    update_user_meta($user_id, 'onboarding_status', 'pending');
    
    // Initialize wallet balance
    update_user_meta($user_id, 'wallet_balance', 0);
    update_user_meta($user_id, 'wallet_transactions', array());
    
    // Auto login the user
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);

    // Send notification to admin
    send_reseller_registration_admin_notification($fullName, $email, $phone, $businessName, $city, $state);
    
    // Send welcome email to user
    send_reseller_welcome_email($email, $fullName);

    wp_send_json_success(array('message' => 'Registration successful!'));
    exit;
}
add_action('wp_ajax_nopriv_reseller_register', 'handle_reseller_registration');
add_action('wp_ajax_reseller_register', 'handle_reseller_registration');

/**
 * Send admin notification for new reseller registration
 */
function send_reseller_registration_admin_notification($name, $email, $phone, $businessName, $city, $state) {
    $admin_email = get_option('admin_email');
    $subject = 'New Reseller Registration: ' . $name;
    
    $message = "A new reseller has registered and requires approval:\n\n";
    $message .= "Name: $name\n";
    $message .= "Email: $email\n";
    $message .= "Phone: $phone\n";
    $message .= "Business Name: $businessName\n";
    $message .= "City: $city, State: $state\n\n";
    $message .= "Approve this reseller: " . admin_url('users.php?role=reseller');
    
    wp_mail($admin_email, $subject, $message);
}

/**
 * Send welcome email to newly registered reseller
 */
function send_reseller_welcome_email($email, $name) {
    $subject = 'Welcome to Aakaari Reseller Program!';
    
    $message = "Dear $name,\n\n";
    $message .= "Thank you for registering as an Aakaari reseller!\n\n";
    $message .= "Your account is currently under review. You'll receive an email notification ";
    $message .= "once your account is approved (typically within 24-48 hours).\n\n";
    $message .= "Meanwhile, you can log in to your account and complete your profile.\n\n";
    $message .= "Regards,\nAakaari Team";
    
    wp_mail($email, $subject, $message);
}

/**
 * Create custom reseller user role
 */
function aakaari_create_reseller_role() {
    // Remove role first to avoid duplicates
    remove_role('reseller');
    
    // Add reseller role with specific capabilities
    add_role(
        'reseller',
        'Reseller',
        array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'view_woocommerce_reports' => true,
        )
    );
}
add_action('init', 'aakaari_create_reseller_role');