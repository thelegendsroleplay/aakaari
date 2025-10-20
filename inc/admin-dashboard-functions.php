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
function aakaari_register_admin_dashboard_template($templates) {
    $templates['admindashboard.php'] = 'Admin Dashboard';
    return $templates;
}
add_filter('theme_page_templates', 'aakaari_register_admin_dashboard_template');

/**
 * Enqueue admin dashboard styles and scripts
 */
/**
 * Enqueue admin dashboard styles and scripts
 */
function aakaari_admin_dashboard_enqueue_assets() {
    // Ensure we are on the correct page template
    if (is_page_template('admindashboard.php')) {
        // Enqueue Stylesheet
        wp_enqueue_style(
            'aakaari-admin-dashboard-style',
            get_template_directory_uri() . '/assets/css/admindashboard.css',
            array(),
            '1.0.0' // Consider using filemtime() for cache busting in production
        );

        // Enqueue Script
        wp_enqueue_script(
            'aakaari-admin-dashboard-script',
            get_template_directory_uri() . '/assets/js/admindashboard.js',
            array('jquery'), // Depends on jQuery
            '1.0.0', // Consider using filemtime()
            true // Load in footer
        );

        // Localize script with AJAX URL and Nonce
        wp_localize_script(
            'aakaari-admin-dashboard-script', // Handle for the script to attach data to
            'aakaari_admin_ajax',            // Object name in JavaScript
            array(                           // Data array
                'ajax_url' => admin_url('admin-ajax.php'),
                // Correct nonce name used in AJAX handlers
                'nonce'    => wp_create_nonce('aakaari_ajax_nonce')
            ) // This parenthesis was likely the cause of the error if misplaced or incorrectly structured previously
        );
    }
}
add_action('wp_enqueue_scripts', 'aakaari_admin_dashboard_enqueue_assets'); // Ensure this action hook is present

/**
 * Restrict access to custom admin dashboard to administrators only
 */
function aakaari_restrict_admin_dashboard_access() {
    if (is_page_template('admindashboard.php')) {
        if (!is_user_logged_in()) {
            // Not logged in at all, redirect to custom admin login
            wp_redirect(home_url('/adminlogin/'));
            exit;
        } elseif (!current_user_can('manage_options')) {
            // Logged in but not an admin, redirect to homepage
            wp_redirect(home_url('/'));
            exit;
        }
        // If they are logged in and an admin, continue to show the dashboard
    }
}
add_action('template_redirect', 'aakaari_restrict_admin_dashboard_access');

/**
 * Add a link to the custom dashboard in the admin bar
 */
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
}
add_action('admin_bar_menu', 'aakaari_add_custom_dashboard_to_admin_bar', 100);

/**
 * Get mock data for testing the dashboard
 * In a real scenario, you would fetch this data from the database
 */
function aakaari_get_mock_dashboard_data() {
    return array(
        'stats' => array(
            'totalResellers' => 1247,
            'activeResellers' => 1089,
            'pendingApplications' => 23,
            'totalOrders' => 5432,
            'todayOrders' => 89,
            'totalRevenue' => 2847650,
            'thisMonthRevenue' => 456780,
            'pendingPayouts' => 125340
        ),
        'applications' => array(
            array(
                'id' => '1',
                'name' => 'Rajesh Kumar',
                'email' => 'rajesh@example.com',
                'phone' => '+91 9876543210',
                'businessName' => 'Kumar Enterprises',
                'businessType' => 'Retail Shop',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'appliedDate' => '2025-10-18',
                'status' => 'pending'
            ),
            array(
                'id' => '2',
                'name' => 'Priya Sharma',
                'email' => 'priya@example.com',
                'phone' => '+91 9876543211',
                'businessName' => 'Fashion Hub',
                'businessType' => 'Online Store',
                'city' => 'Delhi',
                'state' => 'Delhi',
                'appliedDate' => '2025-10-17',
                'status' => 'pending'
            ),
            array(
                'id' => '3',
                'name' => 'Amit Patel',
                'email' => 'amit@example.com',
                'phone' => '+91 9876543212',
                'businessName' => '',
                'businessType' => 'Individual/Freelancer',
                'city' => 'Ahmedabad',
                'state' => 'Gujarat',
                'appliedDate' => '2025-10-16',
                'status' => 'pending'
            )
        ),
        'resellers' => array(
            array(
                'id' => '1',
                'name' => 'Vikram Singh',
                'email' => 'vikram@example.com',
                'phone' => '+91 9876543213',
                'totalOrders' => 145,
                'totalRevenue' => 287500,
                'commission' => 28750,
                'status' => 'active',
                'joinedDate' => '2025-01-15'
            ),
            array(
                'id' => '2',
                'name' => 'Anita Desai',
                'email' => 'anita@example.com',
                'phone' => '+91 9876543214',
                'totalOrders' => 89,
                'totalRevenue' => 156700,
                'commission' => 15670,
                'status' => 'active',
                'joinedDate' => '2025-02-20'
            ),
            array(
                'id' => '3',
                'name' => 'Mohammed Ali',
                'email' => 'mohammed@example.com',
                'phone' => '+91 9876543215',
                'totalOrders' => 234,
                'totalRevenue' => 456800,
                'commission' => 45680,
                'status' => 'active',
                'joinedDate' => '2024-12-10'
            )
        ),
        'orders' => array(
            array(
                'id' => '1',
                'orderId' => 'ORD-2025-1234',
                'reseller' => 'Vikram Singh',
                'customer' => 'Ramesh Verma',
                'products' => 3,
                'amount' => 1899,
                'status' => 'processing',
                'date' => '2025-10-20',
                'paymentStatus' => 'paid'
            ),
            array(
                'id' => '2',
                'orderId' => 'ORD-2025-1235',
                'reseller' => 'Anita Desai',
                'customer' => 'Sunita Rao',
                'products' => 5,
                'amount' => 2499,
                'status' => 'shipped',
                'date' => '2025-10-19',
                'paymentStatus' => 'paid'
            ),
            array(
                'id' => '3',
                'orderId' => 'ORD-2025-1236',
                'reseller' => 'Mohammed Ali',
                'customer' => 'Deepak Joshi',
                'products' => 2,
                'amount' => 1299,
                'status' => 'pending',
                'date' => '2025-10-20',
                'paymentStatus' => 'pending'
            )
        )
    );
}

/**
 * Process application approval
 * In a real scenario, this would update the database record
 */
/**
 * Process application approval via AJAX
 */
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
        // Also link the approval to the user account
        $applicant_email = get_post_meta($application_id, 'reseller_email', true);
        if ($applicant_email) {
            $user = get_user_by('email', $applicant_email);
            if ($user) {
                update_user_meta($user->ID, 'onboarding_status', 'completed');
                // Optionally ensure they have the reseller role
                $wp_user = new WP_User($user->ID);
                if (!$wp_user->has_cap('read')) {
                    // no-op, just a safety check
                }
                if (!in_array('reseller', (array) $wp_user->roles, true)) {
                    $wp_user->add_role('reseller');
                }
            }
        }

        // Notify applicant (keep your existing mail)
        $applicant_email = get_post_meta($application_id, 'reseller_email', true);
        if ($applicant_email) {
            $subject = 'Your Aakaari Reseller Application Approved!';
            $message = "Congratulations! Your reseller application has been approved. You can now log in and access your dashboard.";
            wp_mail($applicant_email, $subject, $message);
        }

        wp_send_json_success(array('message' => 'Application approved successfully'));
    }
    exit;
}
add_action('wp_ajax_approve_application', 'aakaari_approve_application'); // Keep existing action hook

/**
 * Process application rejection
 * In a real scenario, this would update the database record
 */
/**
 * Process application rejection via AJAX
 */
function aakaari_reject_application() {
    // Verify nonce
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

    // Update the status taxonomy term
    $result = wp_set_object_terms($application_id, 'rejected', 'reseller_application_status', false);

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => 'Failed to update status: ' . $result->get_error_message()));
    } else {
        // Save the rejection reason as post meta
        update_post_meta($application_id, 'rejection_reason', $reason);

        // Optional: Send notification email to applicant
        $applicant_email = get_post_meta($application_id, 'reseller_email', true);
        if ($applicant_email) {
            $subject = 'Update on Your Aakaari Reseller Application';
            $message = "We regret to inform you that your reseller application has been rejected.\n\nReason: " . $reason;
            wp_mail($applicant_email, $subject, $message);
        }

        wp_send_json_success(array('message' => 'Application rejected'));
    }
    exit;
}
add_action('wp_ajax_reject_application', 'aakaari_reject_application'); // Keep existing action hook