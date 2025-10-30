<?php
/**
 * Order Email Configuration
 * Handles custom email settings and triggers
 */

defined('ABSPATH') || exit;

/**
 * Customize WooCommerce email settings
 */
add_filter('woocommerce_email_from_name', 'custom_email_from_name');
function custom_email_from_name($from_name) {
    return get_bloginfo('name');
}

/**
 * Ensure order confirmation email is sent
 */
add_action('woocommerce_thankyou', 'ensure_order_confirmation_email', 10, 1);
function ensure_order_confirmation_email($order_id) {
    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);

    // Check if this is a new order (not already processed)
    if (!$order || $order->has_status('failed')) {
        return;
    }

    // Trigger email notifications
    $mailer = WC()->mailer();
    $emails = $mailer->get_emails();

    if (!empty($emails) && isset($emails['WC_Email_Customer_Processing_Order'])) {
        $emails['WC_Email_Customer_Processing_Order']->trigger($order_id);
    }
}

/**
 * Add tracking link to email content
 */
add_filter('woocommerce_email_styles', 'add_custom_email_styles');
function add_custom_email_styles($css) {
    $css .= '
        .order-details {
            border-collapse: collapse;
        }
        .order-details th,
        .order-details td {
            text-align: left;
            vertical-align: middle;
        }
        a {
            color: #2563EB;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    ';
    return $css;
}

/**
 * Customize email subject line
 */
add_filter('woocommerce_email_subject_customer_processing_order', 'custom_processing_order_subject', 10, 2);
function custom_processing_order_subject($subject, $order) {
    $order_number = $order->get_order_number();
    return sprintf('Order #%s - Thank You for Your Purchase!', $order_number);
}
