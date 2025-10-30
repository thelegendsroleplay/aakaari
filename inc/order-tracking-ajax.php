<?php
/**
 * Order Tracking AJAX Handlers
 * Handles OTP generation, verification, and tracking details retrieval
 */

defined('ABSPATH') || exit;

/**
 * Send OTP for order tracking
 */
add_action('wp_ajax_send_tracking_otp', 'handle_send_tracking_otp');
add_action('wp_ajax_nopriv_send_tracking_otp', 'handle_send_tracking_otp');

function handle_send_tracking_otp() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tracking_otp_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'woocommerce')));
        return;
    }

    $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;

    if (!$order_id) {
        wp_send_json_error(array('message' => __('Invalid order number', 'woocommerce')));
        return;
    }

    // Get order
    $order = wc_get_order($order_id);

    if (!$order) {
        wp_send_json_error(array('message' => __('Order not found', 'woocommerce')));
        return;
    }

    // Get order email
    $email = $order->get_billing_email();

    if (!$email) {
        wp_send_json_error(array('message' => __('No email associated with this order', 'woocommerce')));
        return;
    }

    // Generate 6-digit OTP
    $otp = sprintf('%06d', mt_rand(0, 999999));

    // Store OTP in transient (valid for 10 minutes)
    $transient_key = 'tracking_otp_' . $order_id;
    set_transient($transient_key, $otp, 10 * MINUTE_IN_SECONDS);

    // Send OTP email
    $sent = send_tracking_otp_email($order, $otp);

    if ($sent) {
        // Return masked email
        $masked_email = mask_email($email);

        wp_send_json_success(array(
            'message' => __('OTP sent successfully', 'woocommerce'),
            'email' => $email,
            'masked_email' => $masked_email
        ));
    } else {
        wp_send_json_error(array('message' => __('Failed to send OTP. Please try again.', 'woocommerce')));
    }
}

/**
 * Verify OTP and return tracking details
 */
add_action('wp_ajax_verify_tracking_otp', 'handle_verify_tracking_otp');
add_action('wp_ajax_nopriv_verify_tracking_otp', 'handle_verify_tracking_otp');

function handle_verify_tracking_otp() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tracking_otp_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'woocommerce')));
        return;
    }

    $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
    $otp_code = isset($_POST['otp_code']) ? sanitize_text_field($_POST['otp_code']) : '';

    if (!$order_id || !$otp_code) {
        wp_send_json_error(array('message' => __('Invalid request', 'woocommerce')));
        return;
    }

    // Get stored OTP
    $transient_key = 'tracking_otp_' . $order_id;
    $stored_otp = get_transient($transient_key);

    if (!$stored_otp) {
        wp_send_json_error(array('message' => __('OTP expired. Please request a new one.', 'woocommerce')));
        return;
    }

    // Verify OTP
    if ($otp_code !== $stored_otp) {
        wp_send_json_error(array('message' => __('Invalid OTP code', 'woocommerce')));
        return;
    }

    // OTP is valid - delete it and return tracking details
    delete_transient($transient_key);

    $order = wc_get_order($order_id);

    if (!$order) {
        wp_send_json_error(array('message' => __('Order not found', 'woocommerce')));
        return;
    }

    // Prepare tracking data
    $tracking_data = get_order_tracking_data($order);

    wp_send_json_success($tracking_data);
}

/**
 * Send OTP email to customer
 */
function send_tracking_otp_email($order, $otp) {
    $email = $order->get_billing_email();
    $name = $order->get_billing_first_name();

    $subject = sprintf(__('Your Order Tracking OTP - %s', 'woocommerce'), get_bloginfo('name'));

    $message = '<html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">';
    $message .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8fafc; border-radius: 10px;">';
    $message .= '<div style="background: linear-gradient(135deg, #2563EB 0%, #1e40af 100%); color: #fff; padding: 24px; text-align: center; border-radius: 8px 8px 0 0;">';
    $message .= '<h1 style="margin: 0; font-size: 24px;">' . get_bloginfo('name') . '</h1>';
    $message .= '</div>';
    $message .= '<div style="background: #fff; padding: 32px; border-radius: 0 0 8px 8px;">';
    $message .= '<p style="font-size: 16px;">Hi ' . esc_html($name) . ',</p>';
    $message .= '<p style="font-size: 15px; color: #64748b;">You requested to track your order. Please use the following OTP code to verify your identity:</p>';
    $message .= '<div style="background: #f1f5f9; border: 2px dashed #2563EB; border-radius: 8px; padding: 24px; text-align: center; margin: 24px 0;">';
    $message .= '<p style="font-size: 14px; color: #64748b; margin: 0 0 8px 0;">Your OTP Code</p>';
    $message .= '<p style="font-size: 36px; font-weight: bold; color: #2563EB; letter-spacing: 8px; margin: 0; font-family: monospace;">' . esc_html($otp) . '</p>';
    $message .= '</div>';
    $message .= '<p style="font-size: 14px; color: #ef4444; background: #fee2e2; padding: 12px; border-radius: 6px; border-left: 4px solid #ef4444;">';
    $message .= '<strong>Important:</strong> This code will expire in 10 minutes.';
    $message .= '</p>';
    $message .= '<p style="font-size: 13px; color: #94a3b8; margin-top: 24px;">If you did not request this code, please ignore this email.</p>';
    $message .= '</div>';
    $message .= '</div>';
    $message .= '</body></html>';

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
    );

    return wp_mail($email, $subject, $message, $headers);
}

/**
 * Get order tracking data
 */
function get_order_tracking_data($order) {
    $order_id = $order->get_id();

    // Get tracking details from order meta
    $tracking_number = get_post_meta($order_id, '_tracking_number', true);
    $courier_name = get_post_meta($order_id, '_courier_name', true);
    $tracking_url = get_post_meta($order_id, '_tracking_url', true);

    // Get order status
    $status = $order->get_status();
    $status_label = wc_get_order_status_name($status);

    // Get order items
    $items = array();
    foreach ($order->get_items() as $item_id => $item) {
        $items[] = array(
            'name' => $item->get_name(),
            'quantity' => $item->get_quantity()
        );
    }

    return array(
        'order_number' => $order->get_order_number(),
        'order_date' => wc_format_datetime($order->get_date_created()),
        'order_total' => $order->get_formatted_order_total(),
        'status' => $status,
        'status_label' => $status_label,
        'tracking_number' => $tracking_number,
        'courier_name' => $courier_name,
        'tracking_url' => $tracking_url,
        'items' => $items
    );
}

/**
 * Mask email address for display
 */
function mask_email($email) {
    $parts = explode('@', $email);

    if (count($parts) !== 2) {
        return $email;
    }

    $name = $parts[0];
    $domain = $parts[1];

    // Show first 2 characters and last character of name
    $name_length = strlen($name);

    if ($name_length <= 3) {
        $masked_name = $name[0] . str_repeat('*', $name_length - 1);
    } else {
        $masked_name = substr($name, 0, 2) . str_repeat('*', $name_length - 3) . substr($name, -1);
    }

    return $masked_name . '@' . $domain;
}
