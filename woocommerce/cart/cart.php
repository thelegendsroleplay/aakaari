<?php
/**
 * Cart Page Template - Fixed Fully Functional Version
 *
 * This template properly integrates with WooCommerce functions to ensure
 * all product details, images, quantity controls and discount functionality works.
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' );

// ---- Discount Configuration ----
$discount_threshold = apply_filters('aakaari_discount_threshold', 250);
$discount_percent   = apply_filters('aakaari_discount_percent', 10);

// Get cart totals directly from WooCommerce
$cart_subtotal = (float) WC()->cart->get_displayed_subtotal();
$remaining     = max( 0, $discount_threshold - $cart_subtotal );
$progress      = min( 100, max( 0, ($cart_subtotal / $discount_threshold) * 100 ) );

// Discount tiers - these will be used for the discount bar display
$discount_tiers = array(
    array('threshold' => 250, 'discount' => 10, 'benefits' => '10% off entire order'),
    array('threshold' => 500, 'discount' => 15, 'benefits' => '15% off + free express shipping'),
    array('threshold' => 1000, 'discount' => 20, 'benefits' => '20% off + premium support'),
);

// Determine active tier
$active_tier_index = -1;
foreach ($discount_tiers as $index => $tier) {
    if ($cart_subtotal >= $tier['threshold']) {
        $active_tier_index = $index;
    } else {
        break;
    }
}

// Get the next tier
$next_tier = null;
if ($active_tier_index < count($discount_tiers) - 1) {
    $next_tier = $discount_tiers[$active_tier_index + 1];
    $remaining = $next_tier['threshold'] - $cart_subtotal;
} 

// Store the cart subtotal in data attribute for JavaScript
?>

<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
    <?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>

    <div class="checkout-container">

        <main class="cart-details">
            <h1 class="cart-title">
                <?php esc_html_e('Shopping Cart', 'woocommerce'); ?>
            </h1>
            <p class="cart-items-count">
                <?php
                printf(
                    esc_html( _n( '%d item in your cart', '%d items in your cart', WC()->cart->get_cart_contents_count(), 'woocommerce' ) ),
                    esc_html( WC()->cart->get_cart_contents_count() )
                );
                ?>
            </p>

            <!-- Savings Progress Bar -->
            <div class="card savings-bar">
                <div class="savings-header">
                    <div class="savings-icon">
                        <i class="icon">%</i>
                    </div>
                    <?php if ($active_tier_index >= 0): ?>
                        <div class="savings-badge">
                            <?php echo esc_html($discount_tiers[$active_tier_index]['discount']); ?>% OFF
                        </div>
                    <?php else: ?>
                        <div class="savings-badge">
                            0% OFF
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($next_tier): ?>
                    <div class="savings-text">
                        Add <strong><?php echo wc_price($remaining); ?></strong> more to unlock
                        <strong><?php echo esc_html($next_tier['discount']); ?>% discount</strong>
                    </div>
                <?php elseif ($active_tier_index >= 0): ?>
                    <div class="savings-text">
                        <strong>Congrats!</strong> You unlocked the maximum 
                        <strong><?php echo esc_html($discount_tiers[$active_tier_index]['discount']); ?>% discount</strong>!
                    </div>
                <?php else: ?>
                    <div class="savings-text">
                        Add <strong><?php echo wc_price($discount_tiers[0]['threshold']); ?></strong> to unlock your first discount!
                    </div>
                <?php endif; ?>
                
                <div class="savings-progress">
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?php echo esc_attr(round($progress)); ?>%;"></div>
                    </div>
                </div>
                
                <div class="discount-tiers">
                    <?php foreach ($discount_tiers as $index => $tier): ?>
                        <div class="discount-tier" data-threshold="<?php echo esc_attr($tier['threshold']); ?>">
                            <div class="tier-radio <?php echo $cart_subtotal >= $tier['threshold'] ? 'active' : ''; ?>"></div>
                            $<?php echo esc_html($tier['threshold']); ?>+ - <?php echo esc_html($tier['benefits']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cart Items -->
            <div class="card cart-items-list">
                <?php
                do_action('woocommerce_before_cart_contents');

                if (WC()->cart->is_empty()) {
                    echo '<div class="empty-cart">';
                    echo '<div class="empty-cart-icon">🛒</div>';
                    echo '<h2>' . esc_html__('Your cart is empty', 'woocommerce') . '</h2>';
                    echo '<p>' . esc_html__('Looks like you haven\'t added any items to your cart yet.', 'woocommerce') . '</p>';
                    echo '<a href="' . esc_url(get_permalink(wc_get_page_id('shop'))) . '" class="continue-shopping-btn">';
                    echo esc_html__('Continue Shopping', 'woocommerce');
                    echo '</a>';
                    echo '</div>';
                } else {
                    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
                        $_product   = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                        $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

                        if (!$_product || !$_product->exists() || $cart_item['quantity'] <= 0 || !apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
                            continue;
                        }

                        $product_permalink = apply_filters(
                            'woocommerce_cart_item_permalink',
                            $_product->is_visible() ? $_product->get_permalink($cart_item) : '',
                            $cart_item,
                            $cart_item_key
                        );

                        // Get product short description
                        $short_description = $_product->get_short_description();
                        ?>
                        <div class="cart-item woocommerce-cart-form__cart-item <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>" data-item-key="<?php echo esc_attr($cart_item_key); ?>">
                            <div class="cart-item-image">
                                <?php
                                // Product thumbnail
                                $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);
                                
                                if ($product_permalink) {
                                    echo '<a href="' . esc_url($product_permalink) . '">' . $thumbnail . '</a>';
                                } else {
                                    echo $thumbnail;
                                }
                                ?>
                            </div>

                            <div class="cart-item-details">
                                <h3 class="item-name">
                                    <?php
                                    if ($product_permalink) {
                                        echo '<a href="' . esc_url($product_permalink) . '">' . $_product->get_name() . '</a>';
                                    } else {
                                        echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key) . '&nbsp;');
                                    }

                                    do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);

                                    // Meta data.
                                    echo wc_get_formatted_cart_item_data($cart_item);

                                    // Backorder notification.
                                    if ($_product->backorders_require_notification() && $_product->is_on_backorder($cart_item['quantity'])) {
                                        echo wp_kses_post(apply_filters('woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__('Available on backorder', 'woocommerce') . '</p>', $product_id));
                                    }
                                    ?>
                                </h3>
                                
                                <p class="item-sku">
                                    <?php echo esc_html__('SKU:', 'woocommerce') . ' ' . ($_product->get_sku() ? esc_html($_product->get_sku()) : esc_html__('N/A', 'woocommerce')); ?>
                                </p>
                                
                                <?php if (!empty($short_description)): ?>
                                <div class="item-description">
                                    <?php echo wp_kses_post($short_description); ?>
                                </div>
                                <?php endif; ?>
                                
                                <p class="item-each">
                                    <?php echo wc_price($_product->get_price()); ?> <?php esc_html_e('each', 'woocommerce'); ?>
                                </p>
                            </div>

                            <div class="item-quantity quantity-selector">
                                <?php
                                // Add custom quantity selector
                                ?>
                                <button type="button" class="quantity-btn minus" aria-label="<?php esc_attr_e('Decrease quantity', 'woocommerce'); ?>">-</button>
                                <input 
                                    type="number" 
                                    id="quantity_<?php echo esc_attr($cart_item_key); ?>"
                                    class="quantity-input input-text qty text" 
                                    step="1" 
                                    min="0" 
                                    max="100" 
                                    name="cart[<?php echo esc_attr($cart_item_key); ?>][qty]" 
                                    value="<?php echo esc_attr($cart_item['quantity']); ?>" 
                                    title="<?php esc_attr_e('Qty', 'woocommerce'); ?>" 
                                    size="4" 
                                    placeholder="<?php esc_attr_e('Qty', 'woocommerce'); ?>" 
                                    inputmode="numeric" 
                                    data-item-key="<?php echo esc_attr($cart_item_key); ?>"
                                />
                                <button type="button" class="quantity-btn plus" aria-label="<?php esc_attr_e('Increase quantity', 'woocommerce'); ?>">+</button>
                            </div>

                            <div class="cart-item-price item-price">
                                <div class="item-total current-price">
                                    <?php
                                    echo apply_filters(
                                        'woocommerce_cart_item_subtotal',
                                        WC()->cart->get_product_subtotal($_product, $cart_item['quantity']),
                                        $cart_item,
                                        $cart_item_key
                                    );
                                    ?>
                                </div>

                                <?php if ($_product->is_on_sale()) :
                                    $regular_price = (float) $_product->get_regular_price();
                                    $original = $regular_price * (int) $cart_item['quantity'];
                                    if ($original > 0) :
                                    ?>
                                    <div class="original-price"><?php echo wc_price($original); ?></div>
                                    <?php 
                                    endif;
                                endif; 
                                ?>
                            </div>
                            
                            <?php
                            // Remove link
                            echo apply_filters(
                                'woocommerce_cart_item_remove_link',
                                sprintf(
                                    '<a href="%s" class="remove-item" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
                                    esc_url(wc_get_cart_remove_url($cart_item_key)),
                                    esc_html__('Remove this item', 'woocommerce'),
                                    esc_attr($product_id),
                                    esc_attr($_product->get_sku())
                                ),
                                $cart_item_key
                            );
                            ?>
                        </div>
                    <?php endforeach;
                }

                do_action('woocommerce_after_cart_contents');
                ?>

                <!-- Cart actions (hidden) -->
                <button type="submit" class="button update-cart-button" name="update_cart" value="<?php esc_attr_e('Update cart', 'woocommerce'); ?>" style="display:none;">
                    <?php esc_html_e('Update cart', 'woocommerce'); ?>
                </button>
                
                <?php do_action('woocommerce_cart_actions'); ?>
                
                <!-- Store cart subtotal for JavaScript -->
                <input type="hidden" id="cart_subtotal" data-value="<?php echo esc_attr($cart_subtotal); ?>">
                
                <?php do_action('woocommerce_after_cart_table'); ?>
            </div>

            <!-- You Might Also Like / Cross-sells -->
            <?php if (WC()->cart->get_cross_sells()): ?>
            <div class="you-might-like recommendations">
                <div class="recommendations-title">
                    <i class="icon">★</i> <?php esc_html_e('You Might Also Like', 'woocommerce'); ?>
                </div>
                
                <?php
                // Display cross-sell products with custom styling
                add_filter('woocommerce_cross_sells_columns', function() { return 3; });
                add_filter('woocommerce_cross_sells_total', function() { return 3; });
                woocommerce_cross_sell_display();
                ?>
            </div>
            <?php endif; ?>
        </main>

        <!-- Order Summary -->
        <aside class="order-summary">
            <div class="cart_totals card">
                <h2 class="summary-title"><?php esc_html_e('Order Summary', 'woocommerce'); ?></h2>

                <?php if (wc_coupons_enabled()) : ?>
                    <div class="promo-code coupon">
                        <div class="promo-label"><?php esc_html_e('Promo Code', 'woocommerce'); ?></div>
                        <div class="promo-input-container coupon-row">
                            <input type="text" name="coupon_code" class="promo-input" id="coupon_code" value="" placeholder="<?php esc_attr_e('Enter code', 'woocommerce'); ?>" />
                            <button type="submit" class="button apply-btn" name="apply_coupon" value="<?php esc_attr_e('Apply coupon', 'woocommerce'); ?>">
                                <?php esc_html_e('Apply', 'woocommerce'); ?>
                            </button>
                        </div>
                        <?php do_action('woocommerce_cart_coupon'); ?>
                        <div class="promo-suggestions">
                            <?php esc_html_e('Try: SAVE10, WELCOME20, BULK15', 'woocommerce'); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="summary-breakdown">
                    <?php
                    // Manually create summary rows for better control
                    $subtotal = WC()->cart->get_cart_subtotal();
                    $shipping_total = WC()->cart->get_shipping_total();
                    $shipping_tax = WC()->cart->get_shipping_tax();
                    $discount_total = WC()->cart->get_discount_total();
                    $discount_tax = WC()->cart->get_discount_tax();
                    $tax_total = WC()->cart->get_taxes_total();
                    $total = WC()->cart->get_total();
                    
                    // Calculate shipping display
                    $shipping_display = ($shipping_total > 0) ? wc_price($shipping_total + $shipping_tax) : __('FREE', 'woocommerce');
                    $shipping_free = ($shipping_total <= 0);
                    
                    // Get tax rate percentage (approximate)
                    $tax_rate = 0;
                    if ($cart_subtotal > 0 && $tax_total > 0) {
                        $tax_rate = round(($tax_total / $cart_subtotal) * 100);
                    }
                    ?>
                    
                    <div class="summary-row summary-subtotal" data-value="<?php echo esc_attr($cart_subtotal); ?>">
                        <span><?php esc_html_e('Subtotal', 'woocommerce'); ?></span>
                        <span class="summary-subtotal-value"><?php echo $subtotal; ?></span>
                    </div>
                    
                    <div class="summary-row summary-shipping <?php echo $shipping_free ? 'free' : ''; ?>">
                        <span><?php esc_html_e('Shipping', 'woocommerce'); ?></span>
                        <span class="summary-shipping-value"><?php echo $shipping_display; ?></span>
                    </div>
                    
                    <?php if ($discount_total > 0) : ?>
                    <div class="summary-row summary-discount">
                        <span><?php esc_html_e('Discount', 'woocommerce'); ?></span>
                        <span class="summary-discount-value">-<?php echo wc_price($discount_total + $discount_tax); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="summary-row">
                        <span><?php esc_html_e('Tax', 'woocommerce'); ?> (<?php echo $tax_rate; ?>%)</span>
                        <span class="summary-tax-value"><?php echo wc_price($tax_total); ?></span>
                    </div>
                    
                    <div class="summary-row summary-total">
                        <span><?php esc_html_e('Total', 'woocommerce'); ?></span>
                        <span class="summary-total-value"><?php echo $total; ?></span>
                    </div>
                </div>

                <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="checkout-btn">
                    <?php esc_html_e('Proceed to Checkout', 'woocommerce'); ?> <i class="icon">→</i>
                </a>

                <div class="checkout-features trust-badges">
                    <div class="feature-item tb">
                        <span class="feature-icon dot"><span class="dot-inner"></span></span>
                        <?php esc_html_e('Secure checkout', 'woocommerce'); ?>
                    </div>
                    <div class="feature-item tb">
                        <span class="feature-icon dot"><span class="dot-inner"></span></span>
                        <?php esc_html_e('Free returns within 30 days', 'woocommerce'); ?>
                    </div>
                    <div class="feature-item tb">
                        <span class="feature-icon dot"><span class="dot-inner"></span></span>
                        <?php esc_html_e('24/7 customer support', 'woocommerce'); ?>
                    </div>
                </div>
            </div>
        </aside>

    </div><!-- /.checkout-container -->
</form>

<?php do_action('woocommerce_after_cart'); ?>