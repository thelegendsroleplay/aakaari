<?php

/**

 * ✅ Aakaari → Full Multi-Step Checkout Engine

 */

defined('ABSPATH') || exit;

/**

 * ✅ Detect Checkout Template Page

 */

function aakaari_is_template_checkout_page() {

    return is_page_template('template-checkout.php');

}

/**

 * ✅ Load Aakaari Checkout UI if:

 * - Real Woo checkout OR our template is active

 */

add_action('wp_enqueue_scripts', function () {

    $is_checkout = (function_exists('is_checkout') && is_checkout() && !is_order_received_page());

    $is_template = aakaari_is_template_checkout_page();

    if (!$is_checkout && !$is_template) return;

    wp_enqueue_style('aakaari-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    wp_enqueue_script('aakaari-lucide', 'https://unpkg.com/lucide@latest/dist/umd/lucide.min.js', [], null, true);

    wp_enqueue_style('aakaari-checkout', get_stylesheet_directory_uri() . '/assets/css/checkout.css', [], time());

    wp_enqueue_script('wc-checkout');

    wp_enqueue_script(

        'aakaari-checkout',

        get_stylesheet_directory_uri() . '/assets/js/checkout.js',

        ['jquery', 'wc-checkout'],

        time(),

        true

    );

    wp_localize_script('aakaari-checkout', 'aakaariCheckout', [

        'cartUrl' => wc_get_cart_url(),

    ]);

});

/**

 * ✅ Force WC to use our form-checkout template

 */

add_filter('woocommerce_locate_template', function ($template, $template_name, $template_path) {

    $override = get_stylesheet_directory() . '/woocommerce/' . $template_name;

    if ($template_name === 'checkout/form-checkout.php' && file_exists($override)) {

        return $override;

    }

    return $template;

}, 100, 3);

/**

 * ✅ Pretend template-checkout.php IS the Woo checkout page

 * Woo thinks: ✅ is_checkout() = TRUE

 */

add_filter('woocommerce_is_checkout', function ($is_checkout) {

    if (aakaari_is_template_checkout_page()) return true;

    return $is_checkout;

});

/**

 * ✅ Shipping radio styling wrappers

 */

add_action('woocommerce_before_shipping_rate', function () {

    echo '<div class="radio-option"><label>';

}, 10);

add_action('woocommerce_after_shipping_rate', function () {

    echo '</label></div>';

}, 10);

/**

 * ✅ Body class for Aakaari styling

 */

add_filter('body_class', function ($classes) {

    if (aakaari_is_template_checkout_page() || (is_checkout() && !is_order_received_page())) {

        $classes[] = 'aak-checkout-page';

    }

    return $classes;

});