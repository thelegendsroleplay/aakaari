<?php
/**
 * Customer processing order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-processing-order.php.
 */

defined('ABSPATH') || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email); ?>

<?php /* translators: %s: Customer first name */ ?>
<p><?php printf(esc_html__('Hi %s,', 'woocommerce'), esc_html($order->get_billing_first_name())); ?></p>

<p><?php esc_html_e('Thank you for your order! We have received your order and will start processing it shortly.', 'woocommerce'); ?></p>

<table class="order-details" cellspacing="0" cellpadding="0" style="width: 100%; margin: 30px 0; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
    <thead>
        <tr style="background: #f8fafc;">
            <th style="text-align: left; padding: 16px; font-size: 14px; font-weight: 600; color: #1e293b; border-bottom: 2px solid #e5e7eb;"><?php esc_html_e('Order Details', 'woocommerce'); ?></th>
            <th style="text-align: right; padding: 16px; font-size: 14px; font-weight: 600; color: #1e293b; border-bottom: 2px solid #e5e7eb;"></th>
        </tr>
    </thead>
    <tbody style="background: #fff;">
        <tr>
            <td style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; color: #64748b; font-size: 13px;">
                <?php esc_html_e('Order Number:', 'woocommerce'); ?>
            </td>
            <td style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 600; color: #1e293b; font-size: 14px;">
                #<?php echo esc_html($order->get_order_number()); ?>
            </td>
        </tr>
        <tr>
            <td style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; color: #64748b; font-size: 13px;">
                <?php esc_html_e('Order Date:', 'woocommerce'); ?>
            </td>
            <td style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 600; color: #1e293b; font-size: 14px;">
                <?php echo esc_html(wc_format_datetime($order->get_date_created())); ?>
            </td>
        </tr>
        <tr>
            <td style="padding: 12px 16px; color: #64748b; font-size: 13px;">
                <?php esc_html_e('Order Total:', 'woocommerce'); ?>
            </td>
            <td style="padding: 12px 16px; text-align: right; font-weight: 700; color: #2563EB; font-size: 16px;">
                <?php echo wp_kses_post($order->get_formatted_order_total()); ?>
            </td>
        </tr>
    </tbody>
</table>

<?php
/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 */
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 */
do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);
?>

<div style="background: linear-gradient(135deg, #2563EB 0%, #1e40af 100%); border-radius: 10px; padding: 24px; margin: 30px 0; text-align: center;">
    <h3 style="color: #fff; margin: 0 0 12px 0; font-size: 18px;"><?php esc_html_e('Track Your Order', 'woocommerce'); ?></h3>
    <p style="color: #e0f2fe; margin: 0 0 16px 0; font-size: 14px;">
        <?php esc_html_e('You can track your order status anytime', 'woocommerce'); ?>
    </p>
    <a href="<?php echo esc_url(home_url('/track-order')); ?>" style="display: inline-block; background: #fff; color: #2563EB; padding: 12px 32px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px;">
        <?php esc_html_e('Track Order', 'woocommerce'); ?>
    </a>
</div>

<p style="font-size: 14px; color: #64748b; line-height: 1.6;">
    <?php esc_html_e('If you have any questions about your order, please contact us.', 'woocommerce'); ?>
</p>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);
