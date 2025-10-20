<?php
/**
 * Admin Login Functions
 *
 * Contains functions for the custom admin login system
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register the Admin Login template
 */
function aakaari_register_admin_login_template($templates) {
    $templates['adminlogin.php'] = 'Admin Login';
    return $templates;
}
add_filter('theme_page_templates', 'aakaari_register_admin_login_template');

/**
 * Enqueue admin login styles and scripts
 */
function aakaari_admin_login_enqueue_assets() {
    if (is_page_template('adminlogin.php')) {
        wp_enqueue_style('aakaari-admin-login-style', get_template_directory_uri() . '/assets/css/adminlogin.css', array(), '1.0.0');
        wp_enqueue_script('aakaari-admin-login-script', get_template_directory_uri() . '/assets/js/adminlogin.js', array('jquery'), '1.0.0', true);
        
        // Pass AJAX URL to script
        wp_localize_script('aakaari-admin-login-script', 'aakaari_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aakaari_admin_login_ajax')
        ));
    }
}
add_action('wp_enqueue_scripts', 'aakaari_admin_login_enqueue_assets');

/**
 * Handle AJAX admin login
 */
function aakaari_process_admin_login_ajax() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aakaari_admin_login_ajax')) {
        wp_send_json_error(array('message' => 'Security check failed'));
        exit;
    }
    
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($email) || empty($password)) {
        wp_send_json_error(array('message' => 'Please enter both email and password'));
        exit;
    }
    
    // Get user by email
    $user = get_user_by('email', $email);
    
    // If no user found, try username
    if (!$user) {
        $user = get_user_by('login', $email);
    }
    
    // Check if user exists and password is correct
    if (!$user || !wp_check_password($password, $user->user_pass, $user->ID)) {
        wp_send_json_error(array('message' => 'Invalid admin credentials'));
        exit;
    }
    
    // Check if user has administrator role
    if (!in_array('administrator', $user->roles)) {
        wp_send_json_error(array('message' => 'You must be an administrator to access this area'));
        exit;
    }
    
    // Set auth cookie
    wp_set_auth_cookie($user->ID, true);
    
    // CHANGED: Now using admindashboard URL to avoid conflicts
    $dashboard_url = home_url('/admindashboard/');
    
    wp_send_json_success(array(
        'message' => 'Login successful',
        'redirect' => $dashboard_url
    ));
    exit;
}
add_action('wp_ajax_nopriv_aakaari_admin_login', 'aakaari_process_admin_login_ajax');
add_action('wp_ajax_aakaari_admin_login', 'aakaari_process_admin_login_ajax');