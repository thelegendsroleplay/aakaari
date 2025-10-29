<?php
/**
 * Aakaari Checkout - Custom Order Review Template
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-checkout-review-order-table">

    <div class="summary-items">
        <?php
        do_action( 'woocommerce_checkout_order_review_start' ); // Allow plugins to add content
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
            $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
            if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key)) :
        ?>
                <div class="summary-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
                    <?php
                    $thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image('woocommerce_thumbnail'), $cart_item, $cart_item_key );
                    echo $thumbnail; // PHPCS: XSS ok.
                    ?>
                    <div class="summary-item-details">
                        <p class="name"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ) . '&nbsp;'; ?></p>
                        <?php echo apply_filters( 'woocommerce_checkout_cart_item_quantity', ' <p class="qty">' . sprintf( __('Qty: %s', 'woocommerce'), $cart_item['quantity'] ) . '</p>', $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <p class="price"><?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
                    </div>
                </div>
        <?php
            endif;
        endforeach;
        do_action( 'woocommerce_checkout_order_review_list_end' ); // Allow plugins to add content
        ?>
    </div>
    <div class="summary-totals">
        <div class="summary-row cart-subtotal">
            <span class="summary-label"><?php esc_html_e('Subtotal', 'woocommerce'); ?></span>
            <span class="summary-value"><?php wc_cart_totals_subtotal_html(); ?></span>
        </div>

        <?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
            <div class="summary-row cart-discount coupon-<?php echo esc_attr(sanitize_title($code)); ?>">
                <span class="summary-label"><?php wc_cart_totals_coupon_label($coupon); ?></span>
                <span class="summary-value"><?php wc_cart_totals_coupon_html($coupon); ?></span>
            </div>
        <?php endforeach; ?>

        <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
            <?php do_action('woocommerce_review_order_before_shipping'); ?>
            <div classs="summary-row shipping">
                <span class="summary-label"><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></span>
                <span class="summary-value">
                    <?php 
                    // This part is important. We can't just show the total.
                    // We must let WC show the shipping method choices if they haven't been hidden yet
                    // But since our JS handles this, we can just output the total.
                    echo wp_kses_post( WC()->cart->get_cart_shipping_total() );
                    ?>
                </span>
            </div>
            <?php do_action('woocommerce_review_order_after_shipping'); ?>
        <?php endif; ?>


        <?php foreach (WC()->cart->get_fees() as $fee) : ?>
            <div class="summary-row fee">
                <span class="summary-label"><?php echo esc_html($fee->name); ?></span>
                <span class="summary-value"><?php wc_cart_totals_fee_html($fee); ?></span>
            </div>
        <?php endforeach; ?>

        <?php if (wc_tax_enabled() && !WC()->cart->display_prices_including_tax()) : ?>
            <?php if ('itemized' === get_option('woocommerce_tax_total_display')) : ?>
                <?php foreach (WC()->cart->get_tax_totals() as $code => $tax) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
                    <div class="summary-row tax-rate tax-rate-<?php echo esc_attr(sanitize_title($code)); ?>">
                        <span class="summary-label"><?php echo esc_html($tax->label); ?></span>
                        <span class="summary-value"><?php echo wp_kses_post($tax->formatted_amount); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="summary-row tax-total">
                    <span class="summary-label"><?php echo esc_html(WC()->countries->tax_or_vat()); ?></span>
                    <span class="summary-value"><?php wc_cart_totals_taxes_total_html(); ?></span>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php do_action('woocommerce_review_order_before_order_total'); ?>
        <div class="summary-row total order-total">
            <span class="summary-label"><?php esc_html_e('Total', 'woocommerce'); ?></span>
            <span class="summary-value"><?php wc_cart_totals_order_total_html(); ?></span>
        </div>
        <?php do_action('woocommerce_review_order_after_order_total'); ?>
    </div>
</div>
