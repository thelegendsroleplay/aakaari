<?php

/**

 * Template Name: Checkout (Aakaari Shell)

 * Description: Renders the WooCommerce checkout using the theme override.

 */

defined('ABSPATH') || exit;

get_header();

/**

 * If this page isn't set as the official WooCommerce Checkout page,

 * force Woo to treat it as checkout so scripts & hooks load.

 */

add_filter('woocommerce_is_checkout', '__return_true');

echo '<div class="aak-checkout-shell">';

wc_print_notices();

/* Load our override template */

wc_get_template('checkout/form-checkout.php');

echo '</div>';

/* Remove the temporary flag after render */

remove_filter('woocommerce_is_checkout', '__return_true');

get_footer();