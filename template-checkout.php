<?php
/**
 * Template Name: Checkout (Aakaari Shell)
 * Description: Renders the WooCommerce checkout using the theme override.
 */
defined('ABSPATH') || exit;

get_header();

// Print notices (login required, etc.)
if (function_exists('wc_print_notices')) {
    wc_print_notices();
}

// Render Woo checkout (our filter will swap in your custom template)
if (function_exists('WC')) {
    // FIX: Check if we are on the order-received endpoint.
    // If so, load the thankyou template. Otherwise, load the checkout form.
    if (is_wc_endpoint_url('order-received')) {
        // The order ID is passed in the URL, so the thankyou template can use it.
        wc_get_template('checkout/thankyou.php');
    } else {
        echo '<div class="aak-checkout-shell">';
        wc_get_template('checkout/form-checkout.php');
        echo '</div>';
    }
}

get_footer();
