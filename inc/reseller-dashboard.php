<?php
/**
 * Reseller Dashboard Functionality
 * 
 * - Dashboard assets loading
 * - Dashboard settings
 * - Login redirect logic (now approval-aware)
 * - AJAX handlers for dashboard features
 * - Earnings calculation functions
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register and enqueue Reseller Dashboard assets
 * Ensure this matches your actual template file name (reseller-dashboard.php)
 */
if (!function_exists('aakaari_dashboard_assets')) {
function aakaari_dashboard_assets() {
    if (is_page_template('reseller-dashboard.php')) {
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
}}
add_action('wp_enqueue_scripts', 'aakaari_dashboard_assets');

/**
 * Add Dashboard Page settings to Theme Options
 */
if (!function_exists('theme_dashboard_settings')) {
function theme_dashboard_settings() {
    add_settings_field(
        'dashboard_page_id',
        'Dashboard Page',
        'dashboard_page_callback',
        'general',
        'reseller_settings_section'
    );
    register_setting('general', 'dashboard_page_id');
}}
add_action('admin_init', 'theme_dashboard_settings');

if (!function_exists('dashboard_page_callback')) {
function dashboard_page_callback() {
    $dashboard_page_id = get_option('dashboard_page_id');
    
    wp_dropdown_pages(array(
        'name' => 'dashboard_page_id',
        'show_option_none' => 'Select a page',
        'option_none_value' => '0',
        'selected' => $dashboard_page_id,
    ));
    echo '<p class="description">Select the page that uses the Reseller Dashboard template</p>';
}}

/**
 * Login redirect: only send to dashboard if verified AND approved
 */
if (!function_exists('redirect_to_dashboard_after_login')) {
function redirect_to_dashboard_after_login($redirect_to, $request, $user) {
    if (!($user instanceof WP_User)) {
        return $redirect_to;
    }

    $dashboard_page_id = get_option('dashboard_page_id');
    $dashboard_url     = $dashboard_page_id ? get_permalink($dashboard_page_id) : home_url('/dashboard/');
    $reseller_page_id  = get_option('reseller_page_id');
    $reseller_url      = $reseller_page_id ? get_permalink($reseller_page_id) : home_url('/become-a-reseller/');
    $register_url      = home_url('/register/');

    // Require verified email - improved check for different possible values
    $email_verified = get_user_meta($user->ID, 'email_verified', true);
    if ($email_verified !== 'true' && $email_verified !== true && $email_verified !== '1' && $email_verified !== 1) {
        return $register_url;
    }

    // Check application status by email
    $approved = false;
    $status   = 'not-submitted';

    $q = new WP_Query(array(
        'post_type'      => 'reseller_application',
        'post_status'    => array('private', 'publish', 'draft', 'pending'),
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => array(
            array(
                'key'   => 'reseller_email',
                'value' => $user->user_email,
            ),
        ),
    ));

    if ($q->have_posts()) {
        $q->the_post();
        $terms = wp_get_post_terms(get_the_ID(), 'reseller_application_status', array('fields' => 'slugs'));
        wp_reset_postdata();

        if (!is_wp_error($terms) && !empty($terms)) {
            if (in_array('approved', $terms, true)) {
                $approved = true;
            } elseif (in_array('pending', $terms, true)) {
                $status = 'pending';
            } elseif (in_array('rejected', $terms, true)) {
                $status = 'rejected';
            }
        }
    }

    if ($approved) {
        return $dashboard_url;
    }

    // Not approved yet → send to become-a-reseller with status
    return add_query_arg('status', $status, $reseller_url);
}}
add_filter('login_redirect', 'redirect_to_dashboard_after_login', 10, 3);

/**
 * AJAX: Withdraw funds
 */
if (!function_exists('aakaari_withdraw_funds')) {
function aakaari_withdraw_funds() {
    check_ajax_referer('aakaari_dashboard_nonce', 'security');
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'User not logged in'));
        return;
    }
    
    $user_id = get_current_user_id();
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $wallet_balance = (float)(get_user_meta($user_id, 'wallet_balance', true) ?: 0);

    if ($amount <= 0 || $amount > $wallet_balance) {
        wp_send_json_error(array('message' => 'Invalid withdrawal amount'));
        return;
    }
    
    $new_balance = $wallet_balance - $amount;
    update_user_meta($user_id, 'wallet_balance', $new_balance);
    
    $transaction_id = 'TXN-' . strtoupper(substr(md5(uniqid()), 0, 8));
    $transactions = get_user_meta($user_id, 'wallet_transactions', true);
    if (!is_array($transactions)) $transactions = array();
    
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
}}
add_action('wp_ajax_aakaari_withdraw_funds', 'aakaari_withdraw_funds');

/**
 * AJAX: Generate product affiliate link
 */
if (!function_exists('aakaari_generate_product_link')) {
function aakaari_generate_product_link() {
    check_ajax_referer('aakaari_dashboard_nonce', 'security');
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
    
    $product_permalink = get_permalink($product_id);
    $tracking_id = 'r=' . $user_id . '&p=' . $product_id;
    $separator = (strpos($product_permalink, '?') !== false) ? '&' : '?';
    $affiliate_link = $product_permalink . $separator . $tracking_id;
    
    wp_send_json_success(array(
        'product_id' => $product_id,
        'affiliate_link' => $affiliate_link
    ));
}}
add_action('wp_ajax_aakaari_generate_product_link', 'aakaari_generate_product_link');

/**
 * Earnings helpers
 */
if (!function_exists('get_user_earnings')) {
function get_user_earnings($user_id, $period = 'total') {
    if (!class_exists('WooCommerce')) {
        return array('amount' => 0, 'count' => 0);
    }
    
    $args = array(
        'customer_id' => $user_id,
        'status' => array('completed', 'processing', 'on-hold'),
        'limit' => -1,
    );
    
    if ($period == 'month') {
        $args['date_created'] = '>' . date('Y-m-01');
    } else if ($period == 'year') {
        $args['date_created'] = '>' . date('Y-01-01');
    }
    
    $orders = wc_get_orders($args);
    
    $total_earnings = 0;
    $order_count = 0;
    
    foreach ($orders as $order) {
        $order_total = $order->get_total();
        $commission = $order_total * 0.15;
        $total_earnings += $commission;
        $order_count++;
    }
    
    return array(
        'amount' => $total_earnings,
        'count' => $order_count
    );
}}

if (!function_exists('get_dashboard_statistics')) {
function get_dashboard_statistics($user_id) {
    $stats = array();
    $stats['total_earnings'] = get_user_earnings($user_id, 'total');
    $stats['monthly_earnings'] = get_user_earnings($user_id, 'month');
    $stats['yearly_earnings'] = get_user_earnings($user_id, 'year');
    $stats['wallet_balance'] = get_user_meta($user_id, 'wallet_balance', true) ?: 0;
    $stats['transactions'] = get_user_meta($user_id, 'wallet_transactions', true) ?: array();
    return $stats;
}}