<?php
/**
 * Reseller Dashboard Functionality
 * 
 * This file contains:
 * - Dashboard assets loading
 * - Dashboard settings
 * - Login redirect logic
 * - AJAX handlers for dashboard features
 * - Earnings calculation functions
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register and enqueue Reseller Dashboard assets
 */
function aakaari_dashboard_assets() {
    if (is_page_template('dashboard.php')) {
        // Dashboard CSS
        wp_enqueue_style(
            'dashboard-styles',
            get_template_directory_uri() . '/assets/css/dashboard.css',
            array(),
            '1.0.0'
        );
        
        // Dashboard JavaScript
        wp_enqueue_script(
            'dashboard-js',
            get_template_directory_uri() . '/assets/js/dashboard.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Localize script data
        wp_localize_script('dashboard-js', 'dashboard_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('aakaari_dashboard_nonce'),
            'current_date' => date('Y-m-d'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'aakaari_dashboard_assets');

/**
 * Add Dashboard Page settings to Theme Options
 */
function theme_dashboard_settings() {
    // Add field for Dashboard Page
    add_settings_field(
        'dashboard_page_id',
        'Dashboard Page',
        'dashboard_page_callback',
        'general',
        'reseller_settings_section' // Use existing section from reseller-cta.php
    );
    
    // Register the setting
    register_setting('general', 'dashboard_page_id');
}
add_action('admin_init', 'theme_dashboard_settings');

/**
 * Field callback for Dashboard Page selector
 */
function dashboard_page_callback() {
    $dashboard_page_id = get_option('dashboard_page_id');
    
    wp_dropdown_pages(array(
        'name' => 'dashboard_page_id',
        'show_option_none' => 'Select a page',
        'option_none_value' => '0',
        'selected' => $dashboard_page_id,
    ));
    echo '<p class="description">Select the page that uses the Reseller Dashboard template</p>';
}

/**
 * Redirect users to dashboard after login
 */
function redirect_to_dashboard_after_login($redirect_to, $request, $user) {
    // Check if user has appropriate role
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('customer', $user->roles) || in_array('reseller', $user->roles)) {
            $dashboard_page_id = get_option('dashboard_page_id');
            if ($dashboard_page_id) {
                return get_permalink($dashboard_page_id);
            }
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'redirect_to_dashboard_after_login', 10, 3);

/**
 * AJAX handler for withdraw funds
 */
function aakaari_withdraw_funds() {
    // Check nonce
    check_ajax_referer('aakaari_dashboard_nonce', 'security');
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'User not logged in'));
        return;
    }
    
    $user_id = get_current_user_id();
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    
    // Get current wallet balance
    $wallet_balance = get_user_meta($user_id, 'wallet_balance', true);
    if (empty($wallet_balance)) {
        $wallet_balance = 0;
    }
    
    // Validate withdrawal amount
    if ($amount <= 0 || $amount > $wallet_balance) {
        wp_send_json_error(array('message' => 'Invalid withdrawal amount'));
        return;
    }
    
    // Process withdrawal
    $new_balance = $wallet_balance - $amount;
    update_user_meta($user_id, 'wallet_balance', $new_balance);
    
    // Record transaction
    $transaction_id = 'TXN-' . strtoupper(substr(md5(uniqid()), 0, 8));
    $transactions = get_user_meta($user_id, 'wallet_transactions', true);
    if (empty($transactions)) {
        $transactions = array();
    }
    
    $transactions[] = array(
        'id' => $transaction_id,
        'type' => 'Debit',
        'description' => 'Withdrawal to bank',
        'date' => date('Y-m-d'),
        'amount' => -$amount
    );
    
    update_user_meta($user_id, 'wallet_transactions', $transactions);
    
    wp_send_json_success(array(
        'message' => 'Withdrawal successful',
        'new_balance' => $new_balance,
        'transaction_id' => $transaction_id
    ));
}
add_action('wp_ajax_aakaari_withdraw_funds', 'aakaari_withdraw_funds');

/**
 * AJAX handler for generating product affiliate links
 */
function aakaari_generate_product_link() {
    // Check nonce
    check_ajax_referer('aakaari_dashboard_nonce', 'security');
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'User not logged in'));
        return;
    }
    
    $user_id = get_current_user_id();
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    if (!$product_id) {
        wp_send_json_error(array('message' => 'Invalid product ID'));
        return;
    }
    
    // Generate affiliate/tracking link
    $product_permalink = get_permalink($product_id);
    $tracking_id = 'r=' . $user_id . '&p=' . $product_id;
    $separator = (strpos($product_permalink, '?') !== false) ? '&' : '?';
    $affiliate_link = $product_permalink . $separator . $tracking_id;
    
    wp_send_json_success(array(
        'product_id' => $product_id,
        'affiliate_link' => $affiliate_link
    ));
}
add_action('wp_ajax_aakaari_generate_product_link', 'aakaari_generate_product_link');

/**
 * Get user earnings for dashboard display
 * 
 * @param int $user_id User ID
 * @param string $period Period for earnings ('total', 'month', 'year')
 * @return array Earnings data
 */
function get_user_earnings($user_id, $period = 'total') {
    // Skip if WooCommerce not active
    if (!class_exists('WooCommerce')) {
        return array('amount' => 0, 'count' => 0);
    }
    
    // Set up order query args
    $args = array(
        'customer_id' => $user_id,
        'status' => array('completed', 'processing', 'on-hold'),
        'limit' => -1,
    );
    
    // Add date filters for specific periods
    if ($period == 'month') {
        $args['date_created'] = '>' . date('Y-m-01');
    } else if ($period == 'year') {
        $args['date_created'] = '>' . date('Y-01-01');
    }
    
    // Get orders
    $orders = wc_get_orders($args);
    
    $total_earnings = 0;
    $order_count = 0;
    
    foreach ($orders as $order) {
        $order_total = $order->get_total();
        // Calculate commission (15% - customize as needed)
        $commission = $order_total * 0.15;
        $total_earnings += $commission;
        $order_count++;
    }
    
    return array(
        'amount' => $total_earnings,
        'count' => $order_count
    );
}

/**
 * Get dashboard statistics for a user
 * 
 * @param int $user_id User ID
 * @return array Statistics data
 */
function get_dashboard_statistics($user_id) {
    $stats = array();
    
    // Get earnings data
    $stats['total_earnings'] = get_user_earnings($user_id, 'total');
    $stats['monthly_earnings'] = get_user_earnings($user_id, 'month');
    $stats['yearly_earnings'] = get_user_earnings($user_id, 'year');
    
    // Get wallet balance
    $stats['wallet_balance'] = get_user_meta($user_id, 'wallet_balance', true) ?: 0;
    
    // Get transaction history
    $stats['transactions'] = get_user_meta($user_id, 'wallet_transactions', true) ?: array();
    
    return $stats;
}