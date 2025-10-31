<?php
/**
 * Admin Dashboard Functions
 *
 * Contains functions for the custom admin dashboard
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register the Admin Dashboard template
 */
if (!function_exists('aakaari_register_admin_dashboard_template')) {
function aakaari_register_admin_dashboard_template($templates) {
    $templates['admindashboard.php'] = 'Admin Dashboard';
    return $templates;
}}
add_filter('theme_page_templates', 'aakaari_register_admin_dashboard_template');

/**
 * Enqueue admin dashboard styles and scripts
 */
if (!function_exists('aakaari_admin_dashboard_enqueue_assets')) {
function aakaari_admin_dashboard_enqueue_assets() {
    // Ensure we are on the correct page template
    if (is_page_template('admindashboard.php')) {
        // Enqueue Stylesheet
        wp_enqueue_style(
            'aakaari-admin-dashboard-style',
            get_template_directory_uri() . '/assets/css/admindashboard.css',
            array(),
            '1.0.0'
        );

        // Enqueue Script
        wp_enqueue_script(
            'aakaari-admin-dashboard-script',
            get_template_directory_uri() . '/assets/js/admindashboard.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Localize script with AJAX URL and Nonce
        wp_localize_script(
            'aakaari-admin-dashboard-script',
            'aakaari_admin_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('aakaari_ajax_nonce')
            )
        );
    }
}}
add_action('wp_enqueue_scripts', 'aakaari_admin_dashboard_enqueue_assets');

/**
 * Restrict access to custom admin dashboard to administrators only
 */
if (!function_exists('aakaari_restrict_admin_dashboard_access')) {
function aakaari_restrict_admin_dashboard_access() {
    if (is_page_template('admindashboard.php')) {
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/adminlogin/'));
            exit;
        } elseif (!current_user_can('manage_options')) {
            wp_redirect(home_url('/'));
            exit;
        }
    }
}}
add_action('template_redirect', 'aakaari_restrict_admin_dashboard_access');

/**
 * Add a link to the custom dashboard in the admin bar
 */
if (!function_exists('aakaari_add_custom_dashboard_to_admin_bar')) {
function aakaari_add_custom_dashboard_to_admin_bar($wp_admin_bar) {
    if (current_user_can('manage_options')) {
        $wp_admin_bar->add_node(array(
            'id'    => 'aakaari-custom-dashboard',
            'title' => 'Aakaari Dashboard',
            'href'  => home_url('/admindashboard/'),
            'meta'  => array(
                'title' => 'Access Aakaari Custom Dashboard',
            ),
        ));
    }
}}
add_action('admin_bar_menu', 'aakaari_add_custom_dashboard_to_admin_bar', 100);

/**************************************
 * DASHBOARD STATISTICS FUNCTIONS
 **************************************/

/**
 * Get all dashboard statistics for overview tab
 * 
 * @return array All dashboard statistics
 */

function aakaari_get_dashboard_stats() {
    return array(
        'totalResellers' => aakaari_get_total_resellers_count(),
        'activeResellers' => aakaari_get_active_resellers_count(),
        'pendingApplications' => aakaari_get_pending_applications_count(),
        'unseenNotifications' => aakaari_get_unseen_notifications_count(), // Add this line
        'totalOrders' => aakaari_get_total_orders_count(),
        'todayOrders' => aakaari_get_today_orders_count(),
        'totalRevenue' => aakaari_calculate_total_revenue(),
        'thisMonthRevenue' => aakaari_calculate_month_revenue(),
        'pendingPayouts' => aakaari_get_pending_payouts_amount()
    );
}

/**
 * Get total number of resellers (users with reseller role)
 * 
 * @return int Total resellers count
 */
function aakaari_get_total_resellers_count() {
    $user_query = new WP_User_Query(array(
        'role' => 'reseller',
        'count_total' => true,
    ));
    
    return $user_query->get_total();
}

/**
 * Get number of active resellers
 * 
 * @return int Active resellers count
 */
function aakaari_get_active_resellers_count() {
    $user_query = new WP_User_Query(array(
        'role' => 'reseller',
        'count_total' => true,
        'meta_query' => array(
            array(
                'key' => 'account_status',
                'value' => 'active',
                'compare' => '='
            )
        )
    ));
    
    return $user_query->get_total();
}

/**
 * Get number of pending reseller applications
 * 
 * @return int Pending applications count
 */
function aakaari_get_pending_applications_count() {
    $args = array(
        'post_type' => 'reseller_application',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'reseller_application_status',
                'field'    => 'slug',
                'terms'    => 'pending',
            ),
        ),
    );
    
    $query = new WP_Query($args);
    return $query->found_posts;
}

/**
 * Get total number of orders in WooCommerce
 * 
 * @return int Total orders count
 */
function aakaari_get_total_orders_count() {
    if (!function_exists('wc_get_orders')) {
        return 0; // WooCommerce not active
    }
    
    $args = array(
        'limit' => -1,
        'return' => 'ids',
    );
    
    $orders = wc_get_orders($args);
    return count($orders);
}

/**
 * Get number of orders created today
 * 
 * @return int Today's orders count
 */
function aakaari_get_today_orders_count() {
    if (!function_exists('wc_get_orders')) {
        return 0; // WooCommerce not active
    }
    
    $today = date('Y-m-d');
    $args = array(
        'limit' => -1,
        'return' => 'ids',
        'date_created' => $today,
    );
    
    $orders = wc_get_orders($args);
    return count($orders);
}

/**
 * Calculate total revenue from all orders
 * 
 * @return float Total revenue amount
 */
function aakaari_calculate_total_revenue() {
    if (!function_exists('wc_get_orders')) {
        return 0; // WooCommerce not active
    }
    
    $args = array(
        'status' => array('completed', 'processing'),
        'limit' => -1,
    );
    
    $orders = wc_get_orders($args);
    
    $total = 0;
    foreach ($orders as $order) {
        $total += $order->get_total();
    }
    
    return $total;
}

/**
 * Calculate revenue for current month
 * 
 * @return float Current month revenue
 */
function aakaari_calculate_month_revenue() {
    if (!function_exists('wc_get_orders')) {
        return 0; // WooCommerce not active
    }
    
    $first_day = date('Y-m-01'); // First day of current month
    $last_day = date('Y-m-t'); // Last day of current month
    
    $args = array(
        'status' => array('completed', 'processing'),
        'limit' => -1,
        'date_created' => $first_day . '...' . $last_day,
    );
    
    $orders = wc_get_orders($args);
    
    $total = 0;
    foreach ($orders as $order) {
        $total += $order->get_total();
    }
    
    return $total;
}

/**
 * Calculate total pending payouts amount
 * 
 * @return float Pending payouts amount
 */
function aakaari_get_pending_payouts_amount() {
    global $wpdb;
    
    // This assumes you're storing wallet balances in user meta
    $query = "
        SELECT SUM(meta_value) as total 
        FROM $wpdb->usermeta 
        WHERE meta_key = 'wallet_balance'
    ";
    
    $result = $wpdb->get_var($query);
    
    return $result ? floatval($result) : 0;
}

/**************************************
 * RESELLER APPLICATIONS FUNCTIONS
 **************************************/

/**
 * Get all reseller applications with filtering options
 * 
 * @param array $args Filter arguments
 * @return array List of applications
 */
function aakaari_get_applications($args = array()) {
    $defaults = array(
        'status' => '', // empty for all, or specify: pending, approved, rejected
        'search' => '',
        'orderby' => 'date',
        'order' => 'DESC',
        'posts_per_page' => -1,
        'paged' => 1,
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $query_args = array(
        'post_type' => 'reseller_application',
        'posts_per_page' => $args['posts_per_page'],
        'paged' => $args['paged'],
        'orderby' => $args['orderby'],
        'order' => $args['order'],
    );
    
    // Add status filter if provided
    if (!empty($args['status'])) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'reseller_application_status',
                'field'    => 'slug',
                'terms'    => $args['status'],
            ),
        );
    }
    
    // Add search if provided
    if (!empty($args['search'])) {
        $query_args['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key'     => 'reseller_name',
                'value'   => $args['search'],
                'compare' => 'LIKE',
            ),
            array(
                'key'     => 'reseller_email',
                'value'   => $args['search'],
                'compare' => 'LIKE',
            ),
            array(
                'key'     => 'reseller_business',
                'value'   => $args['search'],
                'compare' => 'LIKE',
            ),
        );
    }
    
    $applications_query = new WP_Query($query_args);
    
    $applications = array();
    if ($applications_query->have_posts()) {
        while ($applications_query->have_posts()) {
            $applications_query->the_post();
            $post_id = get_the_ID();
            $status_terms = wp_get_post_terms($post_id, 'reseller_application_status');
            $status = !empty($status_terms) ? $status_terms[0]->slug : 'pending'; // Default to pending if no status set

            $applications[] = array(
                'id' => $post_id,
                'name' => get_post_meta($post_id, 'reseller_name', true),
                'email' => get_post_meta($post_id, 'reseller_email', true),
                'phone' => get_post_meta($post_id, 'reseller_phone', true),
                'businessName' => get_post_meta($post_id, 'reseller_business', true),
                'businessType' => get_post_meta($post_id, 'reseller_business_type', true),
                'city' => get_post_meta($post_id, 'reseller_city', true),
                'state' => get_post_meta($post_id, 'reseller_state', true),
                'appliedDate' => get_the_date('Y-m-d'),
                'status' => $status,
                'rejectionReason' => get_post_meta($post_id, 'rejection_reason', true),
                'submittedData' => get_post_meta($post_id, 'form_data', true) // All form data if stored
            );
        }
        wp_reset_postdata();
    }
    
    return array(
        'applications' => $applications,
        'total' => $applications_query->found_posts,
        'max_pages' => $applications_query->max_num_pages
    );
}

/**
 * Process application approval via AJAX
 */
if (!function_exists('aakaari_approve_application')) {
function aakaari_approve_application() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aakaari_ajax_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
        exit;
    }

    $application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;

    if ($application_id <= 0 || get_post_type($application_id) !== 'reseller_application') {
        wp_send_json_error(array('message' => 'Invalid Application ID'));
        exit;
    }

    $result = wp_set_object_terms($application_id, 'approved', 'reseller_application_status', false);

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => 'Failed to update status: ' . $result->get_error_message()));
    } else {
        // Link the approval to the user account
        $applicant_email = get_post_meta($application_id, 'reseller_email', true);
        if ($applicant_email) {
            $user = get_user_by('email', $applicant_email);
            if ($user) {
                // Mark onboarding completed only on approval
                update_user_meta($user->ID, 'email_verified', 'true');
                update_user_meta($user->ID, 'onboarding_status', 'approved');
                update_user_meta($user->ID, 'account_status', 'active');
                update_user_meta($user->ID, 'approved_date', current_time('mysql'));

                // Ensure reseller role
                $wp_user = new WP_User($user->ID);
                if (!in_array('reseller', (array) $wp_user->roles, true)) {
                    $wp_user->add_role('reseller');
                }
            }
        }

        // Record approval in application post
        update_post_meta($application_id, 'approval_date', current_time('mysql'));
        update_post_meta($application_id, 'approved_by', get_current_user_id());

        // Notify applicant
        if (!empty($applicant_email)) {
            $subject = 'Your Aakaari Reseller Application Approved!';
            $message = "Congratulations! Your reseller application has been approved. You can now log in and access your dashboard.";
            wp_mail($applicant_email, $subject, $message);
        }

        wp_send_json_success(array('message' => 'Application approved successfully'));
    }
    exit;
}}
add_action('wp_ajax_approve_application', 'aakaari_approve_application');

/**
 * Process application rejection via AJAX
 */
if (!function_exists('aakaari_reject_application')) {
function aakaari_reject_application() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aakaari_ajax_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
        exit;
    }

    $application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
    $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';

    if ($application_id <= 0 || get_post_type($application_id) !== 'reseller_application') {
        wp_send_json_error(array('message' => 'Invalid Application ID'));
        exit;
    }

    if (empty($reason)) {
        wp_send_json_error(array('message' => 'Please provide a rejection reason'));
        exit;
    }

    $result = wp_set_object_terms($application_id, 'rejected', 'reseller_application_status', false);

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => 'Failed to update status: ' . $result->get_error_message()));
    } else {
        // Save the rejection reason as post meta
        update_post_meta($application_id, 'rejection_reason', $reason);
        update_post_meta($application_id, 'rejected_date', current_time('mysql'));
        update_post_meta($application_id, 'rejected_by', get_current_user_id());

        // Update user status if applicable
        $applicant_email = get_post_meta($application_id, 'reseller_email', true);
        if ($applicant_email) {
            $user = get_user_by('email', $applicant_email);
            if ($user) {
                update_user_meta($user->ID, 'application_status', 'rejected');
            }
        }

        // Notify applicant
        if ($applicant_email) {
            $subject = 'Update on Your Aakaari Reseller Application';
            $message = "We regret to inform you that your reseller application has been rejected.\n\nReason: " . $reason;
            wp_mail($applicant_email, $subject, $message);
        }

        wp_send_json_success(array('message' => 'Application rejected'));
    }
    exit;
}}
add_action('wp_ajax_reject_application', 'aakaari_reject_application');

/**************************************
 * RESELLERS MANAGEMENT FUNCTIONS
 **************************************/

/**
 * Get all resellers with their statistics
 * 
 * @param array $args Filter arguments
 * @return array List of resellers with their data
 */
function aakaari_get_resellers($args = array()) {
    $defaults = array(
        'status' => '', // empty for all, or specify: active, inactive, suspended
        'search' => '',
        'orderby' => 'registered',
        'order' => 'DESC',
        'number' => -1,
        'paged' => 1,
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $query_args = array(
        'role' => 'reseller',
        'number' => $args['number'],
        'paged' => $args['paged'],
        'orderby' => $args['orderby'],
        'order' => $args['order'],
    );
    
    // Add status filter if provided
    if (!empty($args['status'])) {
        $query_args['meta_query'][] = array(
            'key' => 'account_status',
            'value' => $args['status'],
            'compare' => '=',
        );
    }
    
    // Add search if provided
    if (!empty($args['search'])) {
        // WP_User_Query will handle the search parameter
        $query_args['search'] = '*' . $args['search'] . '*';
    }
    
    $user_query = new WP_User_Query($query_args);
    
    $resellers = array();
    if (!empty($user_query->results)) {
        foreach ($user_query->results as $user) {
            $orders = aakaari_get_reseller_orders_count($user->ID);
            $revenue = aakaari_calculate_reseller_revenue($user->ID);
            $commission = aakaari_calculate_reseller_commission($user->ID);
            
            $status = get_user_meta($user->ID, 'account_status', true);
            if (empty($status)) {
                $status = 'active'; // Default status if not set
            }
            
            $resellers[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'phone' => get_user_meta($user->ID, 'phone', true),
                'businessName' => get_user_meta($user->ID, 'business_name', true),
                'businessType' => get_user_meta($user->ID, 'business_type', true),
                'totalOrders' => $orders,
                'totalRevenue' => $revenue,
                'commission' => $commission,
                'status' => $status,
                'joinedDate' => date('Y-m-d', strtotime($user->user_registered)),
                'walletBalance' => get_user_meta($user->ID, 'wallet_balance', true) ?: 0
            );
        }
    }
    
    return array(
        'resellers' => $resellers,
        'total' => $user_query->get_total(),
        'max_pages' => ceil($user_query->get_total() / $args['number'])
    );
}

/**
 * Get reseller's orders count
 * 
 * @param int $user_id User ID
 * @return int Number of orders
 */
function aakaari_get_reseller_orders_count($user_id) {
    if (!function_exists('wc_get_orders')) {
        return 0; // WooCommerce not active
    }
    
    $args = array(
        'customer' => $user_id,
        'return' => 'ids',
        'limit' => -1,
    );
    
    $orders = wc_get_orders($args);
    return count($orders);
}

/**
 * Calculate reseller's total revenue
 * 
 * @param int $user_id User ID
 * @return float Total revenue amount
 */
function aakaari_calculate_reseller_revenue($user_id) {
    if (!function_exists('wc_get_orders')) {
        return 0; // WooCommerce not active
    }
    
    $args = array(
        'customer' => $user_id,
        'status' => array('completed', 'processing'),
        'limit' => -1,
    );
    
    $orders = wc_get_orders($args);
    
    $total = 0;
    foreach ($orders as $order) {
        $total += $order->get_total();
    }
    
    return $total;
}

/**
 * Calculate reseller's total commission
 * 
 * @param int $user_id User ID
 * @return float Total commission amount
 */
function aakaari_calculate_reseller_commission($user_id) {
    // Get commission rate (can be customized per reseller or use default)
    $commission_rate = get_user_meta($user_id, 'commission_rate', true);
    if (empty($commission_rate)) {
        $commission_rate = 0.15; // Default 15% commission
    }
    
    // Calculate based on revenue
    $revenue = aakaari_calculate_reseller_revenue($user_id);
    return $revenue * $commission_rate;
}

/**
 * Update reseller status
 * 
 * @param int $user_id User ID
 * @param string $status New status (active, inactive, suspended)
 * @return bool|WP_Error True on success, WP_Error on failure
 */
function aakaari_update_reseller_status($user_id, $status) {
    // Validate status
    $valid_statuses = array('active', 'inactive', 'suspended');
    if (!in_array($status, $valid_statuses)) {
        return new WP_Error('invalid_status', 'Invalid status provided');
    }
    
    // Validate user
    $user = get_user_by('ID', $user_id);
    if (!$user || !in_array('reseller', (array) $user->roles)) {
        return new WP_Error('invalid_user', 'Invalid reseller user ID');
    }
    
    // Update status
    $result = update_user_meta($user_id, 'account_status', $status);
    
    if ($result) {
        // Log the status change
        $admin_id = get_current_user_id();
        $log_entry = array(
            'changed_by' => $admin_id,
            'old_status' => get_user_meta($user_id, 'account_status', true),
            'new_status' => $status,
            'timestamp' => current_time('mysql')
        );
        
        add_user_meta($user_id, 'status_change_log', $log_entry);
        
        // Notify user of status change
        $user_email = $user->user_email;
        $subject = 'Your Aakaari Reseller Account Status Update';
        
        switch ($status) {
            case 'active':
                $message = "Your reseller account has been activated. You can now log in and access your dashboard.";
                break;
            case 'inactive':
                $message = "Your reseller account has been deactivated. Please contact support for more information.";
                break;
            case 'suspended':
                $message = "Your reseller account has been suspended. Please contact support for assistance.";
                break;
        }
        
        wp_mail($user_email, $subject, $message);
        
        return true;
    }
    
    return new WP_Error('update_failed', 'Failed to update reseller status');
}

/**************************************
 * ORDERS MANAGEMENT FUNCTIONS
 **************************************/

/**
 * Get orders with reseller information
 * 
 * @param array $args Filter arguments
 * @return array List of orders with details
 */
function aakaari_get_orders($args = array()) {
    if (!function_exists('wc_get_orders')) {
        return array(
            'orders' => array(),
            'total' => 0,
            'max_pages' => 0
        ); // WooCommerce not active
    }
    
    $defaults = array(
        'status' => '', // empty for all, or specify status
        'reseller_id' => '', // specific reseller
        'date_from' => '',
        'date_to' => '',
        'orderby' => 'date',
        'order' => 'DESC',
        'limit' => 10,
        'paged' => 1,
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $query_args = array(
        'limit' => $args['limit'],
        'paged' => $args['paged'],
        'orderby' => $args['orderby'],
        'order' => $args['order'],
    );
    
    // Add status filter if provided
    if (!empty($args['status'])) {
        $query_args['status'] = $args['status'];
    }
    
    // Add reseller filter if provided
    if (!empty($args['reseller_id'])) {
        $query_args['customer'] = $args['reseller_id'];
    }
    
    // Add date range if provided
    if (!empty($args['date_from']) && !empty($args['date_to'])) {
        $query_args['date_created'] = $args['date_from'] . '...' . $args['date_to'];
    }
    
    $wc_orders = wc_get_orders($query_args);
    $orders = array();
    
    foreach ($wc_orders as $order) {
        $customer_id = $order->get_customer_id();
        $customer = $customer_id ? get_userdata($customer_id) : null;

        // Get if the order is by a reseller
        $is_reseller = $customer && in_array('reseller', (array) $customer->roles);

        // Process line items
        $items = $order->get_items();
        $products_count = count($items);

        // Check if order has customized products
        $has_customization = false;
        foreach ($items as $item) {
            if ($item->get_meta('_aakaari_designs') || $item->get_meta('_aakaari_attachments') || $item->get_meta('_aakaari_preview_image')) {
                $has_customization = true;
                break;
            }
        }

        $orders[] = array(
            'id' => $order->get_id(),
            'orderId' => $order->get_order_number(),
            'reseller' => $is_reseller ? $customer->display_name : 'Direct Customer',
            'reseller_id' => $is_reseller ? $customer_id : 0,
            'reseller_email' => $is_reseller && $customer ? $customer->user_email : '',
            'customer' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'customer_email' => $order->get_billing_email(),
            'products' => $products_count,
            'amount' => $order->get_total(),
            'status' => $order->get_status(),
            'date' => $order->get_date_created()->date('Y-m-d'),
            'paymentStatus' => $order->is_paid() ? 'paid' : 'pending',
            'commission' => $is_reseller ? aakaari_calculate_order_commission($order, $customer_id) : 0,
            'has_customization' => $has_customization
        );
    }
    
    // Get total order count for pagination
    $count_args = $query_args;
    $count_args['limit'] = -1;
    $count_args['return'] = 'ids';
    $total_orders = wc_get_orders($count_args);
    
    return array(
        'orders' => $orders,
        'total' => count($total_orders),
        'max_pages' => ceil(count($total_orders) / $args['limit'])
    );
}

/**
 * Calculate commission for a specific order
 * 
 * @param WC_Order $order Order object
 * @param int $reseller_id Reseller user ID
 * @return float Commission amount
 */
function aakaari_calculate_order_commission($order, $reseller_id) {
    // Get commission rate (can be customized per reseller or use default)
    $commission_rate = get_user_meta($reseller_id, 'commission_rate', true);
    if (empty($commission_rate)) {
        $commission_rate = 0.15; // Default 15% commission
    }
    
    // Calculate commission on order total
    $commission = $order->get_total() * $commission_rate;
    
    return $commission;
}

/**************************************
 * PRODUCTS FUNCTIONS
 **************************************/

/**
 * Get product statistics for admin dashboard
 * 
 * @return array Product statistics
 */
function aakaari_get_product_stats() {
    if (!function_exists('wc_get_products')) {
        return array(
            'total' => 0,
            'inStock' => 0,
            'lowStock' => 0
        ); // WooCommerce not active
    }
    
    // Get all products count
    $args = array(
        'limit' => -1,
        'return' => 'ids',
    );
    $products = wc_get_products($args);
    $total = count($products);
    
    // Get in stock products
    $args = array(
        'limit' => -1,
        'return' => 'ids',
        'stock_status' => 'instock',
    );
    $in_stock = wc_get_products($args);
    $in_stock_count = count($in_stock);
    
    // Get low stock products
    // This is more complex as we need to check actual stock levels
    $low_stock_threshold = get_option('woocommerce_notify_low_stock_amount', 2);
    $low_stock_count = 0;
    
    foreach ($products as $product_id) {
        $product = wc_get_product($product_id);
        if ($product->managing_stock() && $product->get_stock_quantity() <= $low_stock_threshold && $product->get_stock_quantity() > 0) {
            $low_stock_count++;
        }
    }
    
    return array(
        'total' => $total,
        'inStock' => $in_stock_count,
        'lowStock' => $low_stock_count
    );
}

/**************************************
 * PAYOUTS FUNCTIONS
 **************************************/

/**
 * Get payout statistics
 * 
 * @return array Payout statistics
 */
function aakaari_get_payout_stats() {
    global $wpdb;
    
    // Get total pending payout amount (from wallet balances)
    $pending_query = "
        SELECT SUM(meta_value) as total 
        FROM $wpdb->usermeta 
        WHERE meta_key = 'wallet_balance'
    ";
    $pending_amount = $wpdb->get_var($pending_query);
    $pending_amount = $pending_amount ? floatval($pending_amount) : 0;
    
    // Get resellers with pending payouts
    $resellers_query = "
        SELECT COUNT(DISTINCT user_id) as total 
        FROM $wpdb->usermeta 
        WHERE meta_key = 'wallet_balance' 
        AND meta_value > 0
    ";
    $pending_resellers = $wpdb->get_var($resellers_query);
    $pending_resellers = $pending_resellers ? intval($pending_resellers) : 0;
    
    // Get month to date payout amount (from payout records)
    $month_start = date('Y-m-01');
    $today = date('Y-m-d');
    
    // This assumes you're storing payout records in a custom table or post type
    // Adjust according to your actual data storage method
    $month_paid = 0;
    $month_transactions = 0;
    
    // Example if using custom post type:
    $payout_args = array(
        'post_type' => 'reseller_payout',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'payout_date',
                'value' => array($month_start, $today),
                'compare' => 'BETWEEN',
                'type' => 'DATE'
            )
        )
    );
    
    $payouts = get_posts($payout_args);
    foreach ($payouts as $payout) {
        $amount = get_post_meta($payout->ID, 'payout_amount', true);
        $month_paid += floatval($amount);
        $month_transactions++;
    }
    
    // Get lifetime payout amount
    $lifetime_paid = 0;
    $lifetime_args = array(
        'post_type' => 'reseller_payout',
        'posts_per_page' => -1,
    );
    
    $all_payouts = get_posts($lifetime_args);
    foreach ($all_payouts as $payout) {
        $amount = get_post_meta($payout->ID, 'payout_amount', true);
        $lifetime_paid += floatval($amount);
    }
    
    return array(
        'pendingAmount' => $pending_amount,
        'pendingResellers' => $pending_resellers,
        'monthPaid' => $month_paid,
        'monthTransactions' => $month_transactions,
        'lifetimePaid' => $lifetime_paid
    );
}

/**
 * Track and update notification read status
 */
function aakaari_mark_notifications_seen() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aakaari_ajax_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
        exit;
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied'));
        exit;
    }

    // Get the current user ID
    $user_id = get_current_user_id();
    
    // Get timestamp to mark as last seen time
    $timestamp = isset($_POST['timestamp']) ? intval($_POST['timestamp']) : time();
    
    // Update the user meta with last viewed timestamp
    update_user_meta($user_id, 'notifications_last_seen', $timestamp);
    
    // Return success
    wp_send_json_success(array('message' => 'Notifications marked as seen'));
    exit;
}
add_action('wp_ajax_mark_notifications_seen', 'aakaari_mark_notifications_seen');

/**
 * Get the number of new unseen notifications
 * 
 * @return int Number of unseen notifications
 */
function aakaari_get_unseen_notifications_count() {
    // Get the current user ID
    $user_id = get_current_user_id();
    
    // Get the last time notifications were viewed
    $last_seen = get_user_meta($user_id, 'notifications_last_seen', true);
    if (empty($last_seen)) {
        $last_seen = 0; // If never seen before, treat all as new
    }
    
    // Get all pending applications with timestamp greater than last_seen
    $args = array(
        'post_type' => 'reseller_application',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'reseller_application_status',
                'field'    => 'slug',
                'terms'    => 'pending',
            ),
        ),
        'date_query' => array(
            'after' => date('Y-m-d H:i:s', $last_seen)
        )
    );
    
    $query = new WP_Query($args);
    return $query->found_posts;
}

/**
 * Process a single reseller payout via AJAX
 */
function aakaari_process_single_payout() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aakaari_ajax_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
        exit;
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied'));
        exit;
    }

    $reseller_id = isset($_POST['reseller_id']) ? intval($_POST['reseller_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

    if ($reseller_id <= 0) {
        wp_send_json_error(array('message' => 'Invalid reseller ID'));
        exit;
    }

    if ($amount <= 0) {
        wp_send_json_error(array('message' => 'Invalid payout amount'));
        exit;
    }

    // Get current wallet balance
    $current_balance = get_user_meta($reseller_id, 'wallet_balance', true);
    if (empty($current_balance)) {
        $current_balance = 0;
    }

    // Verify balance is sufficient
    if ($current_balance < $amount) {
        wp_send_json_error(array(
            'message' => 'Error: Payout amount exceeds wallet balance',
            'balance' => $current_balance,
            'amount' => $amount
        ));
        exit;
    }

    // Create payout record
    $payout_id = wp_insert_post(array(
        'post_type' => 'reseller_payout',
        'post_title' => 'Payout for Reseller #' . $reseller_id,
        'post_status' => 'publish',
        'meta_input' => array(
            'reseller_id' => $reseller_id,
            'payout_amount' => $amount,
            'payout_date' => current_time('mysql'),
            'payout_status' => 'completed',
            'processed_by' => get_current_user_id()
        )
    ));

    if (is_wp_error($payout_id)) {
        wp_send_json_error(array('message' => 'Failed to create payout record: ' . $payout_id->get_error_message()));
        exit;
    }

    // Update wallet balance
    $new_balance = $current_balance - $amount;
    update_user_meta($reseller_id, 'wallet_balance', $new_balance);
    
    // Record transaction in user meta for history
    $transaction = array(
        'type' => 'payout',
        'amount' => $amount,
        'date' => current_time('mysql'),
        'reference' => $payout_id,
        'note' => 'Manual payout processed by admin'
    );
    add_user_meta($reseller_id, 'wallet_transactions', $transaction);

    // Get reseller information
    $reseller = get_userdata($reseller_id);
    
    // Notify reseller via email
    if ($reseller && !is_wp_error($reseller)) {
        $subject = 'Your Commission Payout Has Been Processed';
        $message = "Hello {$reseller->display_name},\n\n";
        $message .= "We're pleased to inform you that your commission payout of ₹" . number_format($amount, 2) . " has been processed.\n\n";
        $message .= "The funds should be transferred to your registered bank account within 1-3 business days.\n\n";
        $message .= "Thank you for your partnership!\n\n";
        $message .= "Regards,\nAakaari Team";
        
        wp_mail($reseller->user_email, $subject, $message);
    }

    wp_send_json_success(array(
        'message' => 'Payout processed successfully',
        'payout_id' => $payout_id,
        'reseller_id' => $reseller_id,
        'amount' => $amount,
        'new_balance' => $new_balance
    ));
    exit;
}
add_action('wp_ajax_process_single_payout', 'aakaari_process_single_payout');

/**
 * Process bulk payouts for all eligible resellers
 */
function aakaari_process_bulk_payouts() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aakaari_ajax_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
        exit;
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied'));
        exit;
    }

    // Get minimum payout threshold
    $min_payout_threshold = get_option('aakaari_min_payout_amount', 1000);

    // Get all resellers with wallet balance above threshold
    $eligible_resellers = get_users(array(
        'role' => 'reseller',
        'meta_query' => array(
            array(
                'key' => 'wallet_balance',
                'value' => $min_payout_threshold,
                'compare' => '>=',
                'type' => 'NUMERIC'
            ),
            array(
                'key' => 'account_status',
                'value' => 'active',
                'compare' => '='
            )
        )
    ));

    if (empty($eligible_resellers)) {
        wp_send_json_error(array('message' => 'No eligible resellers found for payout'));
        exit;
    }

    $successful_payouts = array();
    $failed_payouts = array();
    $total_processed = 0;

    foreach ($eligible_resellers as $reseller) {
        $reseller_id = $reseller->ID;
        $balance = get_user_meta($reseller_id, 'wallet_balance', true);
        
        if (empty($balance) || $balance <= 0) {
            continue;
        }

        // Create payout record
        $payout_id = wp_insert_post(array(
            'post_type' => 'reseller_payout',
            'post_title' => 'Bulk Payout for Reseller #' . $reseller_id,
            'post_status' => 'publish',
            'meta_input' => array(
                'reseller_id' => $reseller_id,
                'payout_amount' => $balance,
                'payout_date' => current_time('mysql'),
                'payout_status' => 'completed',
                'processed_by' => get_current_user_id()
            )
        ));

        if (is_wp_error($payout_id)) {
            $failed_payouts[] = array(
                'reseller_id' => $reseller_id,
                'name' => $reseller->display_name,
                'amount' => $balance,
                'error' => $payout_id->get_error_message()
            );
            continue;
        }

        // Update wallet balance
        update_user_meta($reseller_id, 'wallet_balance', 0);
        
        // Record transaction
        $transaction = array(
            'type' => 'payout',
            'amount' => $balance,
            'date' => current_time('mysql'),
            'reference' => $payout_id,
            'note' => 'Bulk payout processed by admin'
        );
        add_user_meta($reseller_id, 'wallet_transactions', $transaction);

        // Notify reseller via email
        $subject = 'Your Commission Payout Has Been Processed';
        $message = "Hello {$reseller->display_name},\n\n";
        $message .= "We're pleased to inform you that your commission payout of ₹" . number_format($balance, 2) . " has been processed.\n\n";
        $message .= "The funds should be transferred to your registered bank account within 1-3 business days.\n\n";
        $message .= "Thank you for your partnership!\n\n";
        $message .= "Regards,\nAakaari Team";
        
        wp_mail($reseller->user_email, $subject, $message);

        $successful_payouts[] = array(
            'reseller_id' => $reseller_id,
            'name' => $reseller->display_name,
            'amount' => $balance,
            'payout_id' => $payout_id
        );
        
        $total_processed += $balance;
    }

    $result = array(
        'message' => count($successful_payouts) . ' payouts processed successfully for a total of ₹' . number_format($total_processed, 2),
        'successful_payouts' => $successful_payouts,
        'failed_payouts' => $failed_payouts,
        'total_amount' => $total_processed
    );

    if (empty($successful_payouts)) {
        wp_send_json_error(array_merge($result, array('message' => 'No payouts were processed successfully')));
    } else {
        wp_send_json_success($result);
    }
    exit;
}
add_action('wp_ajax_process_bulk_payouts', 'aakaari_process_bulk_payouts');


/**
 * Register Reseller Payout custom post type
 */
function aakaari_register_payout_post_type() {
    $labels = array(
        'name'               => 'Reseller Payouts',
        'singular_name'      => 'Reseller Payout',
        'menu_name'          => 'Reseller Payouts',
        'name_admin_bar'     => 'Reseller Payout',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Payout',
        'new_item'           => 'New Payout',
        'edit_item'          => 'Edit Payout',
        'view_item'          => 'View Payout',
        'all_items'          => 'All Payouts',
        'search_items'       => 'Search Payouts',
        'parent_item_colon'  => 'Parent Payouts:',
        'not_found'          => 'No payouts found.',
        'not_found_in_trash' => 'No payouts found in Trash.'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => 'edit.php?post_type=reseller_application',
        'query_var'          => true,
        'rewrite'            => array('slug' => 'reseller-payouts'),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title'),
        'menu_icon'          => 'dashicons-money-alt',
    );

    register_post_type('reseller_payout', $args);
}
add_action('init', 'aakaari_register_payout_post_type');

/**
 * AJAX Handler: Get order details
 */
add_action("wp_ajax_aakaari_get_order_details", "aakaari_ajax_get_order_details");
function aakaari_ajax_get_order_details() {
    // Verify nonce
    check_ajax_referer("aakaari_ajax_nonce", "nonce");

    if (!current_user_can("manage_options")) {
        wp_send_json_error(["message" => "Unauthorized"], 403);
    }

    $order_id = isset($_POST["order_id"]) ? absint($_POST["order_id"]) : 0;

    if (!$order_id) {
        wp_send_json_error(["message" => "Invalid order ID"], 400);
    }

    $order = wc_get_order($order_id);

    if (!$order) {
        wp_send_json_error(["message" => "Order not found"], 404);
    }

    // Format billing address
    $billing_address = sprintf(
        "%s\n%s\n%s, %s %s\n%s",
        $order->get_billing_first_name() . " " . $order->get_billing_last_name(),
        $order->get_billing_address_1() . ($order->get_billing_address_2() ? " " . $order->get_billing_address_2() : ""),
        $order->get_billing_city(),
        $order->get_billing_state(),
        $order->get_billing_postcode(),
        $order->get_billing_country()
    );

    // Format shipping address
    $shipping_address = sprintf(
        "%s\n%s\n%s, %s %s\n%s",
        $order->get_shipping_first_name() . " " . $order->get_shipping_last_name(),
        $order->get_shipping_address_1() . ($order->get_shipping_address_2() ? " " . $order->get_shipping_address_2() : ""),
        $order->get_shipping_city(),
        $order->get_shipping_state(),
        $order->get_shipping_postcode(),
        $order->get_shipping_country()
    );

    // Get order items with meta data
    $items = [];
    foreach ($order->get_items() as $item_id => $item) {
        $product = $item->get_product();
        $meta_display = "";
        $preview_image = "";
        $customization_details = "";

        // Get customization meta data - NEW: Use structured attachment IDs
        $designs_raw = $item->get_meta("_aakaari_designs");
        $original_attachment_id = intval($item->get_meta("_aakaari_original_attachment"));
        $preview_attachment_id = intval($item->get_meta("_aakaari_preview_attachment"));
        $combined_attachment_id = intval($item->get_meta("_aakaari_combined_attachment"));
        
        // Unserialize designs if needed
        $designs = is_string($designs_raw) ? maybe_unserialize($designs_raw) : $designs_raw;
        
        // Legacy support: Fallback to old meta keys if new ones don't exist
        if ($original_attachment_id <= 0) {
            // Try old attachments meta key
            $attachments_raw = $item->get_meta("_aakaari_attachments");
            $attachments = is_string($attachments_raw) ? maybe_unserialize($attachments_raw) : $attachments_raw;
            if (!empty($attachments) && is_array($attachments)) {
                $original_attachment_id = intval($attachments[0]);
                error_log('Admin Dashboard - Found original via legacy _aakaari_attachments: ' . $original_attachment_id);
            }
            
            // Also try extracting from designs array if still not found
            if ($original_attachment_id <= 0 && !empty($designs) && is_array($designs)) {
                foreach ($designs as $design) {
                    if (isset($design['type']) && $design['type'] === 'image' && !empty($design['src'])) {
                        $src = $design['src'];
                        if (strpos($src, 'wp-content/uploads') !== false && strpos($src, 'data:image') === false) {
                            $attachment_id = attachment_url_to_postid($src);
                            if ($attachment_id) {
                                $original_attachment_id = intval($attachment_id);
                                error_log('Admin Dashboard - Found original via designs array extraction: ' . $original_attachment_id);
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        if ($preview_attachment_id <= 0) {
            $preview_img_url = $item->get_meta("_aakaari_preview_image");
            if ($preview_img_url) {
                $preview_attachment_id = attachment_url_to_postid($preview_img_url);
                if (!$preview_attachment_id) {
                    // Try direct DB lookup
                    global $wpdb;
                    $preview_attachment_id = $wpdb->get_var($wpdb->prepare(
                        "SELECT ID FROM {$wpdb->posts} WHERE guid = %s AND post_type = 'attachment' LIMIT 1",
                        $preview_img_url
                    ));
                }
                if ($preview_attachment_id) {
                    error_log('Admin Dashboard - Found preview via legacy _aakaari_preview_image: ' . $preview_attachment_id);
                }
            }
        }
        
        // If combined is same as preview, set it
        if ($combined_attachment_id <= 0 && $preview_attachment_id > 0) {
            $combined_attachment_id = $preview_attachment_id;
        }

        // Debug: Log what we found
        error_log('Admin Dashboard - Order Item Meta Debug (Item ID: ' . $item_id . '):');
        error_log('  - Original Attachment ID: ' . ($original_attachment_id > 0 ? $original_attachment_id : 'NOT FOUND'));
        error_log('  - Preview Attachment ID: ' . ($preview_attachment_id > 0 ? $preview_attachment_id : 'NOT FOUND'));
        error_log('  - Combined Attachment ID: ' . ($combined_attachment_id > 0 ? $combined_attachment_id : 'NOT FOUND'));

        // Check if this is a customized product
        $is_customized = !empty($designs) || $original_attachment_id > 0 || $preview_attachment_id > 0 || $combined_attachment_id > 0;

        if ($is_customized) {
            $meta_display .= "<div class='customization-section'>";

            // Download Options Section - Three separate files with clear labels
            $meta_display .= "<div style='margin-bottom: 15px; padding: 12px; background: #f8f9fa; border-radius: 6px; border-left: 3px solid #2271b1;'>";
            $meta_display .= "<strong style='color: #2271b1; font-size: 14px; display: block; margin-bottom: 12px;'>Download Options:</strong>";
            
            // Helper function to render download option
            $render_download_option = function($attachment_id, $label, $description) use (&$meta_display) {
                if ($attachment_id <= 0) {
                    $meta_display .= "<div class='download-option' style='margin-bottom: 15px; padding: 10px; background: white; border-radius: 4px; border: 1px solid #e5e7eb;'>";
                    $meta_display .= "<strong style='color: #2271b1; font-size: 13px; display: block; margin-bottom: 8px;'>" . esc_html($label) . ":</strong>";
                    $meta_display .= "<p style='margin: 0; color: #999; font-size: 12px; font-style: italic;'>Not available</p>";
                    $meta_display .= "</div>";
                    return;
                }
                
                $file_url = wp_get_attachment_url($attachment_id);
                $thumb_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
                $file_name = basename(get_attached_file($attachment_id));
                if (!$file_name) {
                    $file_name = basename(parse_url($file_url, PHP_URL_PATH)) ?: 'file.png';
                }
                
                if ($file_url) {
                    $meta_display .= "<div class='download-option' style='margin-bottom: 15px; padding: 10px; background: white; border-radius: 4px; border: 1px solid #e5e7eb;'>";
                    $meta_display .= "<strong style='color: #2271b1; font-size: 13px; display: block; margin-bottom: 8px;'>" . esc_html($label) . ":</strong>";
                    $meta_display .= "<div style='display: flex; align-items: flex-start; gap: 12px;'>";
                    
                    // Thumbnail
                    if ($thumb_url) {
                        $meta_display .= "<img src='" . esc_url($thumb_url) . "' alt='" . esc_attr($label) . "' style='max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px; padding: 5px; object-fit: contain; background: #fff;' />";
                    } elseif ($file_url) {
                        $meta_display .= "<img src='" . esc_url($file_url) . "' alt='" . esc_attr($label) . "' style='max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px; padding: 5px; object-fit: contain; background: #fff;' />";
                    }
                    
                    $meta_display .= "<div style='flex: 1;'>";
                    $meta_display .= "<p style='margin: 0 0 8px 0; color: #666; font-size: 12px;'>" . esc_html($description) . "</p>";
                    $meta_display .= "<div style='font-size: 11px; color: #999; margin-bottom: 10px;'>" . esc_html($file_name) . "</div>";
                    $meta_display .= "<a href='" . esc_url($file_url) . "' target='_blank' download='" . esc_attr($file_name) . "' class='button button-small' style='background:#2271b1; color:white; padding: 6px 14px; text-decoration: none; border-radius: 3px; display: inline-block; font-size: 12px; font-weight: 500;'>Download " . esc_html($label) . "</a>";
                    $meta_display .= "</div></div></div>";
                }
            };
            
            // 1. Original Design - The exact file the user uploaded
            $render_download_option($original_attachment_id, 'Original Design', 'The exact file the customer uploaded for customization');
            
            // 2. Preview Product Image - Small preview/thumbnail used in cart/checkout
            $render_download_option($preview_attachment_id, 'Preview Product Image', 'Small preview/thumbnail used in cart and checkout');
            
            // 3. Combined (Product Mockup) - Generated mockup with design composited onto product
            $render_download_option($combined_attachment_id, 'Combined (Product Mockup)', 'Complete product preview with design applied');
            
            $meta_display .= "</div>"; // Close download options section

            // Get selected customization options from order item meta
            $selected_fabric_id = $item->get_meta("_aakaari_selected_fabric");
            $selected_size_id = $item->get_meta("_aakaari_selected_size");
            $selected_color_hex = $item->get_meta("_aakaari_selected_color");
            $selected_print_type_id = $item->get_meta("_aakaari_selected_print_type");
            
            // Helper function to get fabric name from ID
            $get_fabric_name = function($fabric_id) {
                if (empty($fabric_id)) return '';
                $term_id = intval(str_replace('fab_', '', $fabric_id));
                if ($term_id > 0) {
                    $term = get_term($term_id, 'pa_fabric');
                    if ($term && !is_wp_error($term)) {
                        return $term->name;
                    }
                }
                return $fabric_id;
            };
            
            // Helper function to get size name from ID
            $get_size_name = function($size_id) {
                if (empty($size_id)) return '';
                $term_id = intval(str_replace('size_', '', $size_id));
                if ($term_id > 0) {
                    $term = get_term($term_id, 'pa_size');
                    if ($term && !is_wp_error($term)) {
                        return $term->name;
                    }
                }
                return $size_id;
            };
            
            // Helper function to get print type name from ID
            $get_print_type_name = function($print_type_id) {
                if (empty($print_type_id)) return '';
                $term_id = intval(str_replace('pt_', '', $print_type_id));
                if ($term_id > 0) {
                    $term = get_term($term_id, 'pa_print_type');
                    if ($term && !is_wp_error($term)) {
                        return $term->name;
                    }
                }
                return str_replace('_', ' ', $print_type_id);
            };
            
            // Helper function to get color name from hex
            $get_color_name = function($color_hex) {
                if (empty($color_hex)) return '';
                // Try to find color term by hex
                $terms = get_terms([
                    'taxonomy' => 'pa_color',
                    'hide_empty' => false,
                    'meta_query' => [
                        'relation' => 'OR',
                        [
                            'key' => 'hex_code',
                            'value' => ltrim($color_hex, '#'),
                            'compare' => '='
                        ],
                        [
                            'key' => 'product_attribute_color',
                            'value' => $color_hex,
                            'compare' => '='
                        ]
                    ]
                ]);
                
                if (!empty($terms) && !is_wp_error($terms)) {
                    return $terms[0]->name;
                }
                
                // Fallback to hex code
                return $color_hex;
            };
            
            // Show customization details
            $meta_display .= "<div class='customization-details' style='margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 6px;'>";
            $meta_display .= "<strong style='color: #2271b1;'>Customization Details:</strong><br>";
            
            $has_details = false;
            
            // Show selected color
            if (!empty($selected_color_hex)) {
                $color_name = $get_color_name($selected_color_hex);
                $meta_display .= "• <strong>Color:</strong> " . esc_html($color_name) . "<br>";
                $has_details = true;
            }
            
            // Show selected fabric
            if (!empty($selected_fabric_id)) {
                $fabric_name = $get_fabric_name($selected_fabric_id);
                $meta_display .= "• <strong>Fabric:</strong> " . esc_html($fabric_name) . "<br>";
                $has_details = true;
            }
            
            // Show selected size
            if (!empty($selected_size_id)) {
                $size_name = $get_size_name($selected_size_id);
                $meta_display .= "• <strong>Size:</strong> " . esc_html($size_name) . "<br>";
                $has_details = true;
            }
            
            // Show selected print type (from meta or from designs)
            if (!empty($selected_print_type_id)) {
                $print_type_name = $get_print_type_name($selected_print_type_id);
                $meta_display .= "• <strong>Print Method:</strong> " . esc_html($print_type_name) . "<br>";
                $has_details = true;
            } elseif (!empty($designs) && is_array($designs) && !empty($designs[0]["printType"])) {
                $print_type_name = $get_print_type_name($designs[0]["printType"]);
                if (!empty($print_type_name)) {
                    $meta_display .= "• <strong>Print Method:</strong> " . esc_html($print_type_name) . "<br>";
                    $has_details = true;
                }
            }
            
            // Show design-specific details if available
            if (!empty($designs) && is_array($designs)) {
                foreach ($designs as $index => $design) {
                    if (count($designs) > 1) {
                        $meta_display .= "<div style='margin-top: 8px;'><em>Design " . ($index + 1) . ":</em></div>";
                    }
                    if (!empty($design["side"])) {
                        $meta_display .= "• <strong>Side:</strong> " . esc_html(ucfirst($design["side"])) . "<br>";
                        $has_details = true;
                    }
                }
            }
            
            if (!$has_details) {
                $meta_display .= "<em style='color: #999;'>No customization details available</em>";
            }
            
            $meta_display .= "</div>";

            $meta_display .= "</div>";
        }

        $items[] = [
            "name" => $item->get_name(),
            "quantity" => $item->get_quantity(),
            "price" => wc_price($item->get_subtotal() / $item->get_quantity()),
            "total" => wc_price($item->get_total()),
            "meta_display" => $meta_display,
            "is_customized" => $is_customized
        ];
    }

    $order_data = [
        "id" => $order->get_id(),
        "order_number" => $order->get_order_number(),
        "date" => $order->get_date_created()->date("Y-m-d H:i:s"),
        "status" => $order->get_status(),
        "total" => wc_price($order->get_total()),
        "payment_status" => $order->is_paid() ? "Paid" : "Pending",
        "customer_name" => $order->get_billing_first_name() . " " . $order->get_billing_last_name(),
        "customer_email" => $order->get_billing_email(),
        "customer_phone" => $order->get_billing_phone(),
        "billing_address" => nl2br($billing_address),
        "shipping_address" => nl2br($shipping_address),
        "items" => $items,
        "notes" => $order->get_customer_note()
    ];

    wp_send_json_success($order_data);
}

/**
 * AJAX Handler: Update order status
 */
add_action("wp_ajax_aakaari_update_order_status", "aakaari_ajax_update_order_status");
function aakaari_ajax_update_order_status() {
    // Verify nonce if you have one
    check_ajax_referer("aakaari_ajax_nonce", "nonce");

    if (!current_user_can("manage_options")) {
        wp_send_json_error(["message" => "Unauthorized"], 403);
    }

    $order_id = isset($_POST["order_id"]) ? absint($_POST["order_id"]) : 0;
    $new_status = isset($_POST["status"]) ? sanitize_text_field($_POST["status"]) : "";
    $note = isset($_POST["note"]) ? sanitize_textarea_field($_POST["note"]) : "";

    if (!$order_id || !$new_status) {
        wp_send_json_error(["message" => "Invalid parameters"], 400);
    }

    $order = wc_get_order($order_id);

    if (!$order) {
        wp_send_json_error(["message" => "Order not found"], 404);
    }

    // Update order status
    $order->update_status($new_status, $note, true);

    wp_send_json_success([
        "message" => "Order status updated successfully",
        "new_status" => $new_status
    ]);
}

