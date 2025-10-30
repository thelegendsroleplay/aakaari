<?php
/**
 * Checkout Form - Fully Functional WooCommerce Integration
 * Mobile-first, minimal, modern design with complete backend functionality
 *
 * @package Aakaari
 */

defined('ABSPATH') || exit;

// Get checkout object
$checkout = WC()->checkout();

// Ensure customer data is loaded
if (!is_user_logged_in() && $checkout->is_registration_enabled()) {
    $checkout->enable_signup = true;
}
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

    <!-- Progress Bar -->
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

    <!-- Main Content -->
    <div class="checkout-content">
        <div class="container">

            <?php if (sizeof(WC()->cart->get_cart()) == 0) : ?>
                <p><?php esc_html_e('Your cart is empty.', 'woocommerce'); ?></p>
                <?php return; ?>
            <?php endif; ?>

            <?php do_action('woocommerce_before_checkout_form', $checkout); ?>

            <form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">

                <?php if ($checkout->get_checkout_fields()) : ?>

                    <?php do_action('woocommerce_checkout_before_customer_details'); ?>

                    <div class="woocommerce-billing-fields">

                        <!-- STEP 1: Contact & Billing Information -->
                        <div class="step-content" id="step-1">

                            <div class="card">
                                <h2><?php esc_html_e('Contact Information', 'woocommerce'); ?></h2>

                                <?php
                                // Email field
                                $email_field = $checkout->get_checkout_fields('billing')['billing_email'];
                                woocommerce_form_field('billing_email', $email_field, $checkout->get_value('billing_email'));

                                // Phone field
                                $phone_field = $checkout->get_checkout_fields('billing')['billing_phone'];
                                woocommerce_form_field('billing_phone', $phone_field, $checkout->get_value('billing_phone'));
                                ?>
                            </div>

                            <div class="card">
                                <h2><?php esc_html_e('Billing Address', 'woocommerce'); ?></h2>

                                <?php
                                $billing_fields = $checkout->get_checkout_fields('billing');

                                // Render all billing fields except email and phone (already shown above)
                                foreach ($billing_fields as $key => $field) {
                                    if ($key === 'billing_email' || $key === 'billing_phone') {
                                        continue;
                                    }
                                    woocommerce_form_field($key, $field, $checkout->get_value($key));
                                }
                                ?>
                            </div>

                            <?php do_action('woocommerce_checkout_billing'); ?>

                            <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="link-back">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                <?php esc_html_e('Back to cart', 'woocommerce'); ?>
                            </a>

                            <button type="button" class="btn btn-next" data-next="2">
                                <?php esc_html_e('Continue to shipping', 'woocommerce'); ?>
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </div>

                    </div>

                    <div class="woocommerce-shipping-fields">

                        <!-- STEP 2: Shipping Method -->
                        <div class="step-content" id="step-2" style="display:none;">

                            <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>

                                <?php do_action('woocommerce_checkout_shipping'); ?>

                                <div class="card">
                                    <h2><?php esc_html_e('Shipping Method', 'woocommerce'); ?></h2>

                                    <div id="shipping-options">
                                        <div class="loading">
                                            <div class="spinner"></div>
                                            <p><?php esc_html_e('Loading shipping methods...', 'woocommerce'); ?></p>
                                        </div>
                                    </div>

                                    <!-- Hidden WooCommerce shipping container -->
                                    <div id="wc-shipping" style="display:none;">
                                        <?php woocommerce_checkout_shipping(); ?>
                                    </div>
                                </div>

                            <?php endif; ?>

                            <div class="nav-btns">
                                <button type="button" class="btn btn-back" data-prev="1">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                    <?php esc_html_e('Back', 'woocommerce'); ?>
                                </button>
                                <button type="button" class="btn btn-next" data-next="3">
                                    <?php esc_html_e('Continue to payment', 'woocommerce'); ?>
                                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </button>
                            </div>
                        </div>

                    </div>

                    <?php do_action('woocommerce_checkout_after_customer_details'); ?>

                <?php endif; ?>

                <!-- STEP 3: Review & Payment -->
                <div class="step-content" id="step-3" style="display:none;">

                    <div class="card">
                        <h2><?php esc_html_e('Order Summary', 'woocommerce'); ?></h2>
                        <div class="order-review-wrapper">
                            <?php do_action('woocommerce_checkout_before_order_review'); ?>
                            <div id="order_review" class="woocommerce-checkout-review-order">
                                <?php do_action('woocommerce_checkout_order_review'); ?>
                            </div>
                            <?php do_action('woocommerce_checkout_after_order_review'); ?>
                        </div>
                    </div>

                    <div class="trust-badge">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span><?php esc_html_e('Secure & encrypted checkout', 'woocommerce'); ?></span>
                    </div>

                    <button type="button" class="btn btn-back btn-back-step3" data-prev="2">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <?php esc_html_e('Back', 'woocommerce'); ?>
                    </button>
                </div>

            </form>

            <?php do_action('woocommerce_after_checkout_form', $checkout); ?>

        </div>
    </div>

</div>
