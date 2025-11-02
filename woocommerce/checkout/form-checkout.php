<?php
/**
 * Checkout Form - Simplified 2-Step with Beautiful Design
 * Step 1: Contact & Address | Step 2: Review & Payment
 *
 * @package Aakaari
 */

defined('ABSPATH') || exit;

$checkout = WC()->checkout();
?>

<div class="checkout-v2">

    <!-- Header -->
    <div class="checkout-header">
        <div class="container">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="logo">
                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/logo-3.png'); ?>" alt="<?php bloginfo('name'); ?>" class="checkout-logo-img">
            </a>
            <div class="secure-badge">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span><?php esc_html_e('Secure Checkout', 'woocommerce'); ?></span>
            </div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="checkout-progress">
        <div class="container">
            <div class="steps">
                <div class="step active" data-step="1">
                    <span class="num">1</span>
                    <span class="label"><?php esc_html_e('Information', 'woocommerce'); ?></span>
                </div>
                <div class="line"></div>
                <div class="step" data-step="2">
                    <span class="num">2</span>
                    <span class="label"><?php esc_html_e('Payment', 'woocommerce'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="checkout-content">
        <div class="container">

            <?php if (sizeof(WC()->cart->get_cart()) == 0) : ?>
                <p><?php esc_html_e('Your cart is empty.', 'woocommerce'); ?></p>
                <?php return; ?>
            <?php endif; ?>

            <?php do_action('woocommerce_before_checkout_form', $checkout); ?>

            <form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">

                <?php do_action('woocommerce_checkout_before_customer_details'); ?>

                <!-- STEP 1: Contact & Address -->
                <div class="step-content" id="step-1">

                    <div class="card">
                        <h2><?php esc_html_e('Contact', 'woocommerce'); ?></h2>

                        <div class="field">
                            <label><?php esc_html_e('Email', 'woocommerce'); ?> <span class="req">*</span></label>
                            <input type="email" name="billing_email" id="billing_email" class="input" value="<?php echo esc_attr($checkout->get_value('billing_email')); ?>" required autocomplete="email">
                        </div>

                        <div class="field">
                            <label><?php esc_html_e('Phone', 'woocommerce'); ?> <span class="req">*</span></label>
                            <input type="tel" name="billing_phone" id="billing_phone" class="input" value="<?php echo esc_attr($checkout->get_value('billing_phone')); ?>" required autocomplete="tel">
                        </div>
                    </div>

                    <div class="card">
                        <h2><?php esc_html_e('Shipping Address', 'woocommerce'); ?></h2>

                        <div class="field-row">
                            <div class="field">
                                <label><?php esc_html_e('First Name', 'woocommerce'); ?> <span class="req">*</span></label>
                                <input type="text" name="billing_first_name" id="billing_first_name" class="input" value="<?php echo esc_attr($checkout->get_value('billing_first_name')); ?>" required autocomplete="given-name">
                            </div>
                            <div class="field">
                                <label><?php esc_html_e('Last Name', 'woocommerce'); ?> <span class="req">*</span></label>
                                <input type="text" name="billing_last_name" id="billing_last_name" class="input" value="<?php echo esc_attr($checkout->get_value('billing_last_name')); ?>" required autocomplete="family-name">
                            </div>
                        </div>

                        <div class="field">
                            <label><?php esc_html_e('Address', 'woocommerce'); ?> <span class="req">*</span></label>
                            <input type="text" name="billing_address_1" id="billing_address_1" class="input" value="<?php echo esc_attr($checkout->get_value('billing_address_1')); ?>" required autocomplete="address-line1">
                        </div>

                        <div class="field">
                            <input type="text" name="billing_address_2" id="billing_address_2" class="input" placeholder="<?php esc_attr_e('Apartment, suite, etc. (optional)', 'woocommerce'); ?>" value="<?php echo esc_attr($checkout->get_value('billing_address_2')); ?>" autocomplete="address-line2">
                        </div>

                        <div class="field-row field-row-3">
                            <div class="field">
                                <label><?php esc_html_e('City', 'woocommerce'); ?> <span class="req">*</span></label>
                                <input type="text" name="billing_city" id="billing_city" class="input" value="<?php echo esc_attr($checkout->get_value('billing_city')); ?>" required autocomplete="address-level2">
                            </div>
                            <div class="field">
                                <label><?php esc_html_e('State', 'woocommerce'); ?> <span class="req">*</span></label>
                                <input type="text" name="billing_state" id="billing_state" class="input" value="<?php echo esc_attr($checkout->get_value('billing_state')); ?>" required autocomplete="address-level1">
                            </div>
                            <div class="field">
                                <label><?php esc_html_e('ZIP', 'woocommerce'); ?> <span class="req">*</span></label>
                                <input type="text" name="billing_postcode" id="billing_postcode" class="input" value="<?php echo esc_attr($checkout->get_value('billing_postcode')); ?>" required autocomplete="postal-code">
                            </div>
                        </div>

                        <div class="field">
                            <label><?php esc_html_e('Country', 'woocommerce'); ?> <span class="req">*</span></label>
                            <select name="billing_country" id="billing_country" class="input" required autocomplete="country">
                                <?php
                                $countries = WC()->countries->get_allowed_countries();
                                $selected = $checkout->get_value('billing_country') ?: WC()->countries->get_base_country();
                                foreach ($countries as $key => $value) {
                                    echo '<option value="' . esc_attr($key) . '" ' . selected($selected, $key, false) . '>' . esc_html($value) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <?php
                        // Hidden shipping fields - copy from billing
                        $shipping_fields = ['first_name', 'last_name', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country'];
                        foreach ($shipping_fields as $field) {
                            echo '<input type="hidden" name="shipping_' . $field . '" id="shipping_' . $field . '">';
                        }
                        ?>
                    </div>

                    <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="link-back">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <?php esc_html_e('Back to cart', 'woocommerce'); ?>
                    </a>

                    <button type="button" class="btn btn-next" data-next="2">
                        <?php esc_html_e('Continue to payment', 'woocommerce'); ?>
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>

                <?php do_action('woocommerce_checkout_after_customer_details'); ?>

                <!-- STEP 2: Review & Payment -->
                <div class="step-content" id="step-2" style="display:none;">

                    <!-- Order Summary -->
                    <div class="card">
                        <h2><?php esc_html_e('Order Summary', 'woocommerce'); ?></h2>

                        <div class="order-items">
                            <?php
                            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                                $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                                if ($_product && $_product->exists() && $cart_item['quantity'] > 0) {
                                    $product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);
                                    $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);
                                    $product_price = apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key);
                                    ?>
                                    <div class="order-item">
                                        <div class="item-image">
                                            <?php echo $thumbnail; ?>
                                            <span class="item-qty"><?php echo $cart_item['quantity']; ?></span>
                                        </div>
                                        <div class="item-details">
                                            <div class="item-name"><?php echo $product_name; ?></div>
                                            <?php if (!empty($cart_item['variation'])) : ?>
                                                <div class="item-meta">
                                                    <?php
                                                    foreach ($cart_item['variation'] as $key => $value) {
                                                        echo '<span>' . wc_attribute_label(str_replace('attribute_', '', $key)) . ': ' . $value . '</span>';
                                                    }
                                                    ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="item-price">
                                            <?php echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>

                        <div class="order-totals">
                            <div class="total-row">
                                <span><?php esc_html_e('Subtotal', 'woocommerce'); ?></span>
                                <span><?php echo WC()->cart->get_cart_subtotal(); ?></span>
                            </div>
                            <div class="total-row">
                                <span><?php esc_html_e('Shipping', 'woocommerce'); ?></span>
                                <span id="order-shipping">
                                    <?php
                                    if (WC()->cart->get_subtotal() >= 499) {
                                        echo '<strong style="color: #10b981;">' . esc_html__('FREE', 'woocommerce') . '</strong>';
                                    } else {
                                        echo wc_price(40);
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="total-row total-final">
                                <span><?php esc_html_e('Total', 'woocommerce'); ?></span>
                                <span><?php echo WC()->cart->get_total(); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="card">
                        <h2><?php esc_html_e('Payment Method', 'woocommerce'); ?></h2>

                        <?php if (WC()->cart->needs_payment()) : ?>
                            <div id="payment" class="woocommerce-checkout-payment">
                                <?php wc_get_template('checkout/payment.php'); ?>
                            </div>
                        <?php else : ?>
                            <p><?php esc_html_e('No payment is required for this order.', 'woocommerce'); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="trust-badge">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span><?php esc_html_e('Secure & encrypted checkout', 'woocommerce'); ?></span>
                    </div>

                    <button type="button" class="btn btn-back" data-prev="1">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <?php esc_html_e('Back', 'woocommerce'); ?>
                    </button>
                </div>

            </form>

            <?php do_action('woocommerce_after_checkout_form', $checkout); ?>

        </div>
    </div>

</div>
