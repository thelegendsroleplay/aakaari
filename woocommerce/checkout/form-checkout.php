<?php
/**
 * Checkout Form - Complete Rebuild
 * Mobile-first, minimal, modern design
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
                <?php bloginfo('name'); ?>
            </a>
            <div class="secure-badge">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span><?php esc_html_e('Secure Checkout', 'woocommerce'); ?></span>
            </div>
        </div>
    </div>

    <!-- Progress -->
    <div class="checkout-progress">
        <div class="container">
            <div class="steps">
                <div class="step active" data-step="1">
                    <span class="num">1</span>
                    <span class="label"><?php esc_html_e('Info', 'woocommerce'); ?></span>
                </div>
                <div class="line"></div>
                <div class="step" data-step="2">
                    <span class="num">2</span>
                    <span class="label"><?php esc_html_e('Shipping', 'woocommerce'); ?></span>
                </div>
                <div class="line"></div>
                <div class="step" data-step="3">
                    <span class="num">3</span>
                    <span class="label"><?php esc_html_e('Payment', 'woocommerce'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main -->
    <div class="checkout-content">
        <div class="container">

            <form name="checkout" method="post" class="checkout woocommerce-checkout" id="checkout-form" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" novalidate>

                <?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>
                <?php wp_nonce_field('woocommerce-process_checkout', '_wpnonce'); ?>
                <input type="hidden" name="woocommerce_checkout_update_totals" value="false">
                <?php do_action('woocommerce_checkout_before_customer_details'); ?>

                <!-- Step 1 -->
                <div class="step-content" id="step-1">

                    <div class="card">
                        <h2><?php esc_html_e('Contact', 'woocommerce'); ?></h2>
                        <div class="field">
                            <label><?php esc_html_e('Email', 'woocommerce'); ?> <span class="req">*</span></label>
                            <input type="email" name="billing_email" id="billing_email" class="input" value="<?php echo esc_attr($checkout->get_value('billing_email')); ?>" required>
                        </div>
                        <div class="field">
                            <label><?php esc_html_e('Phone', 'woocommerce'); ?> <span class="req">*</span></label>
                            <input type="tel" name="billing_phone" id="billing_phone" class="input" value="<?php echo esc_attr($checkout->get_value('billing_phone')); ?>" required>
                        </div>
                    </div>

                    <div class="card">
                        <h2><?php esc_html_e('Shipping Address', 'woocommerce'); ?></h2>

                        <!-- Hidden shipping fields (will be synced from billing) -->
                        <input type="hidden" name="shipping_first_name" id="shipping_first_name">
                        <input type="hidden" name="shipping_last_name" id="shipping_last_name">
                        <input type="hidden" name="shipping_address_1" id="shipping_address_1">
                        <input type="hidden" name="shipping_address_2" id="shipping_address_2">
                        <input type="hidden" name="shipping_city" id="shipping_city">
                        <input type="hidden" name="shipping_state" id="shipping_state">
                        <input type="hidden" name="shipping_postcode" id="shipping_postcode">
                        <input type="hidden" name="shipping_country" id="shipping_country">

                        <div class="field-row">
                            <div class="field">
                                <label><?php esc_html_e('First Name', 'woocommerce'); ?> <span class="req">*</span></label>
                                <input type="text" name="billing_first_name" id="billing_first_name" class="input" value="<?php echo esc_attr($checkout->get_value('billing_first_name')); ?>" required>
                            </div>
                            <div class="field">
                                <label><?php esc_html_e('Last Name', 'woocommerce'); ?> <span class="req">*</span></label>
                                <input type="text" name="billing_last_name" id="billing_last_name" class="input" value="<?php echo esc_attr($checkout->get_value('billing_last_name')); ?>" required>
                            </div>
                        </div>
                        <div class="field">
                            <label><?php esc_html_e('Address', 'woocommerce'); ?> <span class="req">*</span></label>
                            <input type="text" name="billing_address_1" id="billing_address_1" class="input" value="<?php echo esc_attr($checkout->get_value('billing_address_1')); ?>" required>
                        </div>
                        <div class="field">
                            <input type="text" name="billing_address_2" id="billing_address_2" class="input" placeholder="<?php esc_attr_e('Apartment, suite, etc. (optional)', 'woocommerce'); ?>" value="<?php echo esc_attr($checkout->get_value('billing_address_2')); ?>">
                        </div>
                        <div class="field-row field-row-3">
                            <div class="field">
                                <label><?php esc_html_e('City', 'woocommerce'); ?> <span class="req">*</span></label>
                                <input type="text" name="billing_city" id="billing_city" class="input" value="<?php echo esc_attr($checkout->get_value('billing_city')); ?>" required>
                            </div>
                            <div class="field">
                                <label><?php esc_html_e('State', 'woocommerce'); ?> <span class="req">*</span></label>
                                <input type="text" name="billing_state" id="billing_state" class="input" value="<?php echo esc_attr($checkout->get_value('billing_state')); ?>" required>
                            </div>
                            <div class="field">
                                <label><?php esc_html_e('ZIP', 'woocommerce'); ?> <span class="req">*</span></label>
                                <input type="text" name="billing_postcode" id="billing_postcode" class="input" value="<?php echo esc_attr($checkout->get_value('billing_postcode')); ?>" required>
                            </div>
                        </div>
                        <div class="field">
                            <label><?php esc_html_e('Country', 'woocommerce'); ?> <span class="req">*</span></label>
                            <select name="billing_country" id="billing_country" class="input" required>
                                <?php
                                $countries = WC()->countries->get_allowed_countries();
                                $selected = $checkout->get_value('billing_country') ?: WC()->countries->get_base_country();
                                foreach ($countries as $key => $value) {
                                    echo '<option value="' . esc_attr($key) . '" ' . selected($selected, $key, false) . '>' . esc_html($value) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="link-back">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <?php esc_html_e('Back to cart', 'woocommerce'); ?>
                    </a>

                    <button type="button" class="btn btn-next" data-next="2">
                        <?php esc_html_e('Continue to shipping', 'woocommerce'); ?>
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>

                <!-- Step 2 -->
                <div class="step-content" id="step-2" style="display:none;">

                    <div class="card">
                        <h2><?php esc_html_e('Shipping Method', 'woocommerce'); ?></h2>
                        <div id="shipping-options">
                            <div class="loading">
                                <div class="spinner"></div>
                                <p><?php esc_html_e('Loading...', 'woocommerce'); ?></p>
                            </div>
                        </div>
                        <div id="wc-shipping" style="display:none;">
                            <?php if (WC()->cart->needs_shipping()) woocommerce_cart_totals_shipping_html(); ?>
                        </div>
                    </div>

                    <div class="nav-btns">
                        <button type="button" class="btn btn-back" data-prev="1">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            <?php esc_html_e('Back', 'woocommerce'); ?>
                        </button>
                        <button type="button" class="btn btn-next" data-next="3">
                            <?php esc_html_e('Continue', 'woocommerce'); ?>
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="step-content" id="step-3" style="display:none;">

                    <div class="card">
                        <h2><?php esc_html_e('Order Summary', 'woocommerce'); ?></h2>
                        <div class="order-review">
                            <?php woocommerce_order_review(); ?>
                        </div>
                    </div>

                    <div class="card">
                        <h2><?php esc_html_e('Payment', 'woocommerce'); ?></h2>
                        <div id="payment-options" class="woocommerce-checkout-payment">
                            <?php if (WC()->cart->needs_payment()) : ?>
                                <?php wc_get_template('checkout/payment.php', array(
                                    'checkout' => $checkout,
                                    'available_gateways' => WC()->payment_gateways()->get_available_payment_gateways(),
                                    'order_button_text' => __('Place Order', 'woocommerce'),
                                )); ?>
                            <?php else : ?>
                                <button type="submit" class="btn btn-submit btn-place-order" name="woocommerce_checkout_place_order" id="place_order" value="<?php esc_attr_e('Place order', 'woocommerce'); ?>">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    <?php esc_html_e('Place Order', 'woocommerce'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="trust-badge">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span><?php esc_html_e('Secure & encrypted', 'woocommerce'); ?></span>
                    </div>

                    <button type="button" class="btn btn-back btn-back-step3" data-prev="2">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <?php esc_html_e('Back', 'woocommerce'); ?>
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>
