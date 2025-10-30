<?php
/**
 * Order Received / Thank You Page
 */

defined('ABSPATH') || exit;
?>

<div class="order-received-page">
    <div class="order-received-container">

        <?php if ($order) : ?>

            <!-- Success Header -->
            <div class="order-success-header">
                <div class="success-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="#10b981" stroke-width="2" fill="#f0fdf4"/>
                        <path d="M8 12.5L10.5 15L16 9.5" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h1 class="order-success-title"><?php esc_html_e('Thank you for your order!', 'woocommerce'); ?></h1>
                <p class="order-success-message">
                    <?php esc_html_e('Your order has been received and is being processed.', 'woocommerce'); ?>
                </p>
            </div>

            <!-- Order Details Card -->
            <div class="order-details-card">
                <h2 class="card-title"><?php esc_html_e('Order Details', 'woocommerce'); ?></h2>

                <div class="order-info-grid">
                    <div class="order-info-item">
                        <span class="info-label"><?php esc_html_e('Order Number', 'woocommerce'); ?></span>
                        <span class="info-value">#<?php echo esc_html($order->get_order_number()); ?></span>
                    </div>
                    <div class="order-info-item">
                        <span class="info-label"><?php esc_html_e('Date', 'woocommerce'); ?></span>
                        <span class="info-value"><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></span>
                    </div>
                    <div class="order-info-item">
                        <span class="info-label"><?php esc_html_e('Email', 'woocommerce'); ?></span>
                        <span class="info-value"><?php echo esc_html($order->get_billing_email()); ?></span>
                    </div>
                    <div class="order-info-item">
                        <span class="info-label"><?php esc_html_e('Total', 'woocommerce'); ?></span>
                        <span class="info-value"><?php echo wp_kses_post($order->get_formatted_order_total()); ?></span>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="order-items-section">
                    <h3 class="section-title"><?php esc_html_e('Items Ordered', 'woocommerce'); ?></h3>
                    <div class="order-items-list">
                        <?php
                        foreach ($order->get_items() as $item_id => $item) {
                            $product = $item->get_product();
                            $thumbnail = $product ? $product->get_image('thumbnail') : '';
                            ?>
                            <div class="order-item-row">
                                <div class="item-thumbnail">
                                    <?php echo wp_kses_post($thumbnail); ?>
                                </div>
                                <div class="item-details">
                                    <h4 class="item-name"><?php echo esc_html($item->get_name()); ?></h4>
                                    <p class="item-meta">
                                        <?php esc_html_e('Quantity:', 'woocommerce'); ?> <?php echo esc_html($item->get_quantity()); ?>
                                    </p>
                                </div>
                                <div class="item-total">
                                    <?php echo wp_kses_post($order->get_formatted_line_subtotal($item)); ?>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>

                <!-- Order Totals -->
                <div class="order-totals-section">
                    <div class="total-row">
                        <span class="total-label"><?php esc_html_e('Subtotal', 'woocommerce'); ?></span>
                        <span class="total-value"><?php echo wp_kses_post(wc_price($order->get_subtotal())); ?></span>
                    </div>
                    <div class="total-row">
                        <span class="total-label"><?php esc_html_e('Shipping', 'woocommerce'); ?></span>
                        <span class="total-value"><?php echo wp_kses_post(wc_price($order->get_shipping_total())); ?></span>
                    </div>
                    <?php if ($order->get_total_tax() > 0) : ?>
                        <div class="total-row">
                            <span class="total-label"><?php esc_html_e('Tax', 'woocommerce'); ?></span>
                            <span class="total-value"><?php echo wp_kses_post(wc_price($order->get_total_tax())); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="total-row total-row-final">
                        <span class="total-label"><?php esc_html_e('Total', 'woocommerce'); ?></span>
                        <span class="total-value"><?php echo wp_kses_post($order->get_formatted_order_total()); ?></span>
                    </div>
                </div>
            </div>

            <!-- Address & Payment Info -->
            <div class="order-info-cards">
                <div class="info-card">
                    <h3 class="card-title"><?php esc_html_e('Billing Address', 'woocommerce'); ?></h3>
                    <address class="address-content">
                        <?php echo wp_kses_post($order->get_formatted_billing_address()); ?>
                    </address>
                </div>

                <div class="info-card">
                    <h3 class="card-title"><?php esc_html_e('Shipping Address', 'woocommerce'); ?></h3>
                    <address class="address-content">
                        <?php echo wp_kses_post($order->get_formatted_shipping_address()); ?>
                    </address>
                </div>

                <div class="info-card">
                    <h3 class="card-title"><?php esc_html_e('Payment Method', 'woocommerce'); ?></h3>
                    <p class="payment-method">
                        <?php echo esc_html($order->get_payment_method_title()); ?>
                    </p>
                </div>
            </div>

            <!-- Track Order CTA -->
            <div class="track-order-cta">
                <p class="cta-message">
                    <?php esc_html_e('Want to track your order?', 'woocommerce'); ?>
                </p>
                <a href="<?php echo esc_url(home_url('/track-order')); ?>" class="btn-track-order">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php esc_html_e('Track Your Order', 'woocommerce'); ?>
                </a>
            </div>

            <!-- Email Confirmation Notice -->
            <div class="email-notice">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 8L10.89 13.26C11.2187 13.4793 11.6049 13.5963 12 13.5963C12.3951 13.5963 12.7813 13.4793 13.11 13.26L21 8M5 19H19C19.5304 19 20.0391 18.7893 20.4142 18.4142C20.7893 18.0391 21 17.5304 21 17V7C21 6.46957 20.7893 5.96086 20.4142 5.58579C20.0391 5.21071 19.5304 5 19 5H5C4.46957 5 3.96086 5.21071 3.58579 5.58579C3.21071 5.96086 3 6.46957 3 7V17C3 17.5304 3.21071 18.0391 3.58579 18.4142C3.96086 18.7893 4.46957 19 5 19Z" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <p>
                    <?php
                    printf(
                        esc_html__('A confirmation email has been sent to %s', 'woocommerce'),
                        '<strong>' . esc_html($order->get_billing_email()) . '</strong>'
                    );
                    ?>
                </p>
            </div>

        <?php else : ?>

            <div class="order-not-found">
                <p><?php esc_html_e('Order not found.', 'woocommerce'); ?></p>
            </div>

        <?php endif; ?>

    </div>
</div>
