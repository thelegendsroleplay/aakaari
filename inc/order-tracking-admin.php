<?php
/**
 * Order Tracking Admin Interface
 * Adds meta box for tracking details in WooCommerce order admin
 */

defined('ABSPATH') || exit;

/**
 * Add tracking meta box to order edit page
 */
add_action('add_meta_boxes', 'add_order_tracking_meta_box');

function add_order_tracking_meta_box() {
    add_meta_box(
        'order_tracking_details',
        __('Shipping & Tracking Details', 'woocommerce'),
        'render_order_tracking_meta_box',
        'shop_order',
        'side',
        'high'
    );

    // For HPOS (High-Performance Order Storage)
    add_meta_box(
        'order_tracking_details',
        __('Shipping & Tracking Details', 'woocommerce'),
        'render_order_tracking_meta_box',
        'woocommerce_page_wc-orders',
        'side',
        'high'
    );
}

/**
 * Render tracking meta box content
 */
function render_order_tracking_meta_box($post_or_order) {
    // Support both post and order objects
    $order = ($post_or_order instanceof WP_Post) ? wc_get_order($post_or_order->ID) : $post_or_order;

    if (!$order) {
        return;
    }

    $order_id = $order->get_id();

    // Get current tracking details
    $tracking_number = get_post_meta($order_id, '_tracking_number', true);
    $courier_name = get_post_meta($order_id, '_courier_name', true);
    $tracking_url = get_post_meta($order_id, '_tracking_url', true);

    // Nonce for security
    wp_nonce_field('save_tracking_details', 'tracking_details_nonce');

    ?>
    <div class="tracking-details-admin">
        <style>
            .tracking-details-admin {
                padding: 12px 0;
            }
            .tracking-field {
                margin-bottom: 16px;
            }
            .tracking-field label {
                display: block;
                margin-bottom: 4px;
                font-weight: 600;
                font-size: 13px;
                color: #1e293b;
            }
            .tracking-field input,
            .tracking-field select {
                width: 100%;
                padding: 8px 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
            }
            .tracking-field input:focus,
            .tracking-field select:focus {
                outline: none;
                border-color: #2563EB;
                box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
            }
            .tracking-field small {
                display: block;
                margin-top: 4px;
                color: #64748b;
                font-size: 12px;
            }
            .tracking-status {
                padding: 12px;
                background: #f0f9ff;
                border: 1px solid #bfdbfe;
                border-radius: 6px;
                margin-bottom: 16px;
            }
            .tracking-status.has-tracking {
                background: #d1fae5;
                border-color: #6ee7b7;
            }
            .tracking-status strong {
                display: block;
                margin-bottom: 4px;
                color: #1e293b;
                font-size: 13px;
            }
            .tracking-status p {
                margin: 0;
                font-size: 12px;
                color: #475569;
            }
            .send-notification-btn {
                margin-top: 12px;
                padding: 8px 16px;
                background: #2563EB;
                color: #fff;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 13px;
                font-weight: 600;
                width: 100%;
            }
            .send-notification-btn:hover {
                background: #1e40af;
            }
            .send-notification-btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
        </style>

        <?php if ($tracking_number) : ?>
            <div class="tracking-status has-tracking">
                <strong><?php esc_html_e('✓ Tracking Added', 'woocommerce'); ?></strong>
                <p><?php echo esc_html($courier_name); ?>: <?php echo esc_html($tracking_number); ?></p>
            </div>
        <?php else : ?>
            <div class="tracking-status">
                <strong><?php esc_html_e('No Tracking Info', 'woocommerce'); ?></strong>
                <p><?php esc_html_e('Add tracking details below', 'woocommerce'); ?></p>
            </div>
        <?php endif; ?>

        <div class="tracking-field">
            <label for="courier_name">
                <?php esc_html_e('Courier / Shipping Company', 'woocommerce'); ?>
                <span style="color: #ef4444;">*</span>
            </label>
            <select id="courier_name" name="courier_name">
                <option value=""><?php esc_html_e('-- Select Courier --', 'woocommerce'); ?></option>
                <?php
                $couriers = array(
                    'Blue Dart' => 'Blue Dart',
                    'Delhivery' => 'Delhivery',
                    'DTDC' => 'DTDC',
                    'FedEx' => 'FedEx',
                    'India Post' => 'India Post',
                    'Professional Couriers' => 'Professional Couriers',
                    'Trackon' => 'Trackon',
                    'Xpressbees' => 'Xpressbees',
                    'Ecom Express' => 'Ecom Express',
                    'Shadowfax' => 'Shadowfax',
                    'Other' => 'Other'
                );

                foreach ($couriers as $key => $label) {
                    $selected = ($courier_name === $key) ? 'selected' : '';
                    echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                }
                ?>
            </select>
        </div>

        <div class="tracking-field">
            <label for="tracking_number">
                <?php esc_html_e('Tracking Number', 'woocommerce'); ?>
                <span style="color: #ef4444;">*</span>
            </label>
            <input
                type="text"
                id="tracking_number"
                name="tracking_number"
                value="<?php echo esc_attr($tracking_number); ?>"
                placeholder="<?php esc_attr_e('Enter tracking number', 'woocommerce'); ?>"
            >
        </div>

        <div class="tracking-field">
            <label for="tracking_url">
                <?php esc_html_e('Tracking URL (Optional)', 'woocommerce'); ?>
            </label>
            <input
                type="url"
                id="tracking_url"
                name="tracking_url"
                value="<?php echo esc_attr($tracking_url); ?>"
                placeholder="<?php esc_attr_e('https://...', 'woocommerce'); ?>"
            >
            <small><?php esc_html_e('Full URL to track shipment on courier website', 'woocommerce'); ?></small>
        </div>

        <?php if ($tracking_number) : ?>
            <button type="button" id="send-tracking-notification" class="send-notification-btn">
                <?php esc_html_e('Send Tracking Update Email', 'woocommerce'); ?>
            </button>
        <?php endif; ?>

        <script>
        jQuery(function($) {
            $('#send-tracking-notification').on('click', function() {
                const $btn = $(this);
                const orderId = <?php echo intval($order_id); ?>;

                if (!confirm('<?php esc_html_e('Send tracking details email to customer?', 'woocommerce'); ?>')) {
                    return;
                }

                $btn.prop('disabled', true).text('<?php esc_html_e('Sending...', 'woocommerce'); ?>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'send_tracking_notification',
                        order_id: orderId,
                        nonce: '<?php echo wp_create_nonce('tracking_notification_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php esc_html_e('Tracking email sent successfully!', 'woocommerce'); ?>');
                        } else {
                            alert(response.data.message || '<?php esc_html_e('Failed to send email', 'woocommerce'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php esc_html_e('An error occurred', 'woocommerce'); ?>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('<?php esc_html_e('Send Tracking Update Email', 'woocommerce'); ?>');
                    }
                });
            });
        });
        </script>
    </div>
    <?php
}

/**
 * Save tracking details
 */
add_action('woocommerce_process_shop_order_meta', 'save_order_tracking_details', 10, 2);

function save_order_tracking_details($order_id, $post) {
    // Verify nonce
    if (!isset($_POST['tracking_details_nonce']) || !wp_verify_nonce($_POST['tracking_details_nonce'], 'save_tracking_details')) {
        return;
    }

    // Get old tracking number
    $old_tracking_number = get_post_meta($order_id, '_tracking_number', true);

    // Save tracking details
    $tracking_number = isset($_POST['tracking_number']) ? sanitize_text_field($_POST['tracking_number']) : '';
    $courier_name = isset($_POST['courier_name']) ? sanitize_text_field($_POST['courier_name']) : '';
    $tracking_url = isset($_POST['tracking_url']) ? esc_url_raw($_POST['tracking_url']) : '';

    update_post_meta($order_id, '_tracking_number', $tracking_number);
    update_post_meta($order_id, '_courier_name', $courier_name);
    update_post_meta($order_id, '_tracking_url', $tracking_url);

    // If tracking number was just added or changed, send notification email
    if ($tracking_number && $tracking_number !== $old_tracking_number) {
        // Add order note
        $order = wc_get_order($order_id);
        $order->add_order_note(
            sprintf(
                __('Tracking details added: %s - %s', 'woocommerce'),
                $courier_name,
                $tracking_number
            )
        );

        // Automatically send tracking email
        do_action('tracking_details_updated', $order_id);
    }
}

/**
 * Send tracking notification email via AJAX
 */
add_action('wp_ajax_send_tracking_notification', 'handle_send_tracking_notification');

function handle_send_tracking_notification() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tracking_notification_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'woocommerce')));
        return;
    }

    // Check permissions
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error(array('message' => __('Permission denied', 'woocommerce')));
        return;
    }

    $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;

    if (!$order_id) {
        wp_send_json_error(array('message' => __('Invalid order', 'woocommerce')));
        return;
    }

    $order = wc_get_order($order_id);

    if (!$order) {
        wp_send_json_error(array('message' => __('Order not found', 'woocommerce')));
        return;
    }

    // Send the tracking email
    $sent = send_tracking_update_email($order);

    if ($sent) {
        wp_send_json_success(array('message' => __('Email sent successfully', 'woocommerce')));
    } else {
        wp_send_json_error(array('message' => __('Failed to send email', 'woocommerce')));
    }
}

/**
 * Send tracking update email to customer
 */
function send_tracking_update_email($order) {
    $order_id = $order->get_id();
    $tracking_number = get_post_meta($order_id, '_tracking_number', true);
    $courier_name = get_post_meta($order_id, '_courier_name', true);
    $tracking_url = get_post_meta($order_id, '_tracking_url', true);

    if (!$tracking_number) {
        return false;
    }

    $email = $order->get_billing_email();
    $name = $order->get_billing_first_name();
    $order_number = $order->get_order_number();

    $subject = sprintf(__('Your Order #%s Has Shipped - Tracking Details', 'woocommerce'), $order_number);

    $message = '<html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">';
    $message .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8fafc;">';
    $message .= '<div style="background: linear-gradient(135deg, #2563EB 0%, #1e40af 100%); color: #fff; padding: 24px; text-align: center; border-radius: 8px 8px 0 0;">';
    $message .= '<h1 style="margin: 0; font-size: 24px;">' . get_bloginfo('name') . '</h1>';
    $message .= '</div>';
    $message .= '<div style="background: #fff; padding: 32px; border-radius: 0 0 8px 8px;">';
    $message .= '<p style="font-size: 16px;">Hi ' . esc_html($name) . ',</p>';
    $message .= '<p style="font-size: 15px;">Great news! Your order <strong>#' . esc_html($order_number) . '</strong> has been shipped and is on its way to you.</p>';

    $message .= '<div style="background: #f0f9ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 20px; margin: 24px 0;">';
    $message .= '<h3 style="margin: 0 0 16px 0; color: #1e293b; font-size: 16px;">Tracking Information</h3>';
    $message .= '<table style="width: 100%; border-collapse: collapse;">';
    $message .= '<tr><td style="padding: 8px 0; color: #64748b; font-size: 14px; width: 140px;"><strong>Courier:</strong></td>';
    $message .= '<td style="padding: 8px 0; color: #1e293b; font-size: 14px;">' . esc_html($courier_name) . '</td></tr>';
    $message .= '<tr><td style="padding: 8px 0; color: #64748b; font-size: 14px;"><strong>Tracking Number:</strong></td>';
    $message .= '<td style="padding: 8px 0; color: #1e293b; font-size: 14px; font-family: monospace; background: #fff; border-radius: 4px;">' . esc_html($tracking_number) . '</td></tr>';
    $message .= '</table>';

    if ($tracking_url) {
        $message .= '<a href="' . esc_url($tracking_url) . '" style="display: inline-block; margin-top: 16px; padding: 12px 24px; background: #2563EB; color: #fff; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 600;">';
        $message .= 'Track Your Shipment';
        $message .= '</a>';
    }

    $message .= '</div>';

    $message .= '<div style="background: #f8fafc; border-left: 4px solid #2563EB; padding: 16px; margin: 24px 0; border-radius: 4px;">';
    $message .= '<p style="margin: 0; font-size: 14px; color: #475569;">You can also track your order anytime on our website:</p>';
    $message .= '<a href="' . esc_url(home_url('/track-order')) . '" style="color: #2563EB; text-decoration: none; font-weight: 600;">';
    $message .= home_url('/track-order');
    $message .= '</a>';
    $message .= '</div>';

    $message .= '<p style="font-size: 14px; color: #64748b;">If you have any questions, please don\'t hesitate to contact us.</p>';
    $message .= '<p style="font-size: 14px; color: #64748b;">Thank you for shopping with us!</p>';
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
 * Automatically send tracking email when tracking details are updated
 */
add_action('tracking_details_updated', 'auto_send_tracking_email');

function auto_send_tracking_email($order_id) {
    $order = wc_get_order($order_id);

    if (!$order) {
        return;
    }

    send_tracking_update_email($order);
}

/**
 * Add tracking info to order emails
 */
add_action('woocommerce_email_after_order_table', 'add_tracking_to_order_email', 10, 4);

function add_tracking_to_order_email($order, $sent_to_admin, $plain_text, $email) {
    // Only add to customer emails
    if ($sent_to_admin) {
        return;
    }

    $order_id = $order->get_id();
    $tracking_number = get_post_meta($order_id, '_tracking_number', true);
    $courier_name = get_post_meta($order_id, '_courier_name', true);
    $tracking_url = get_post_meta($order_id, '_tracking_url', true);

    if (!$tracking_number) {
        return;
    }

    if ($plain_text) {
        echo "\n\n" . strtoupper(__('Tracking Information', 'woocommerce')) . "\n";
        echo __('Courier:', 'woocommerce') . ' ' . $courier_name . "\n";
        echo __('Tracking Number:', 'woocommerce') . ' ' . $tracking_number . "\n";
        if ($tracking_url) {
            echo __('Track your shipment:', 'woocommerce') . ' ' . $tracking_url . "\n";
        }
    } else {
        ?>
        <div style="margin: 24px 0; padding: 20px; background: #f0f9ff; border: 1px solid #bfdbfe; border-radius: 8px;">
            <h3 style="margin: 0 0 16px 0; color: #1e293b; font-size: 16px;">
                <?php esc_html_e('Tracking Information', 'woocommerce'); ?>
            </h3>
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="padding: 6px 0; color: #64748b; font-size: 14px; width: 140px;">
                        <strong><?php esc_html_e('Courier:', 'woocommerce'); ?></strong>
                    </td>
                    <td style="padding: 6px 0; color: #1e293b; font-size: 14px;">
                        <?php echo esc_html($courier_name); ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #64748b; font-size: 14px;">
                        <strong><?php esc_html_e('Tracking Number:', 'woocommerce'); ?></strong>
                    </td>
                    <td style="padding: 6px 0; color: #1e293b; font-size: 14px; font-family: monospace;">
                        <?php echo esc_html($tracking_number); ?>
                    </td>
                </tr>
            </table>
            <?php if ($tracking_url) : ?>
                <a href="<?php echo esc_url($tracking_url); ?>" target="_blank" style="display: inline-block; margin-top: 12px; padding: 10px 20px; background: #2563EB; color: #fff; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 600;">
                    <?php esc_html_e('Track Your Shipment', 'woocommerce'); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }
}
