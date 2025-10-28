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
    echo '<div class="aak-checkout-shell">';
    wc_get_template('checkout/form-checkout.php');
    echo '</div>';
}

get_footer();
