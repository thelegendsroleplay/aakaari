<?php
/**
 * Aakaari Custom Checkout – Bootstrapper
 */

if (!defined('ABSPATH')) { exit; }

/**
 * Treat our custom page template as a WooCommerce checkout.
 * This makes gateways, wc-checkout.js, validation, etc. behave normally.
 */
add_filter('woocommerce_is_checkout', function ($is_checkout) {
    if (is_page_template('template-checkout.php')) {
        return true;
    }
    return $is_checkout;
});

/**
 * Enqueue Checkout CSS/JS on real checkout OR our custom template,
 * but not on the order-received (thank you) page.
 */
add_action('wp_enqueue_scripts', function () {

    $is_aakaari_checkout = is_checkout() || is_page_template('template-checkout.php');

    if (!$is_aakaari_checkout || is_order_received_page()) {
        return;
    }

    // Fonts + icons
    wp_enqueue_style(
        'aakaari-inter',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        [],
        null
    );
    wp_enqueue_script(
        'aakaari-lucide',
        'https://unpkg.com/lucide@latest/dist/lucide.min.js',
        [],
        null,
        true
    );

    // Force Woo checkout script to be present even when not on the official WC page.
    if (wp_script_is('wc-checkout', 'registered')) {
        wp_enqueue_script('wc-checkout');
    }

    // Checkout CSS (yours)
    wp_enqueue_style(
        'aakaari-checkout',
        get_stylesheet_directory_uri() . '/assets/css/checkout.css',
        [], // after Woo styles naturally
        filemtime(get_stylesheet_directory() . '/assets/css/checkout.css')
    );

    // Checkout JS (yours)
    wp_enqueue_script(
        'aakaari-checkout',
        get_stylesheet_directory_uri() . '/assets/js/checkout.js',
        ['jquery', 'wc-checkout'],
        filemtime(get_stylesheet_directory() . '/assets/js/checkout.js'),
        true
    );

    // Data for JS
    if (function_exists('wc_get_cart_url')) {
        wp_localize_script('aakaari-checkout', 'aakaariCheckout', [
            'cartUrl' => wc_get_cart_url(),
        ]);
    }
}, 99);

/**
 * Add a body class so CSS can scope to the custom UI.
 */
add_filter('body_class', function ($classes) {
    if ((is_checkout() || is_page_template('template-checkout.php')) && !is_order_received_page()) {
        $classes[] = 'aak-checkout-page';
    }
    return $classes;
});

/**
 * Use our custom multi-step template when Woo resolves checkout/form-checkout.php
 * (keeps working whether you open /checkout/ or the template-checkout.php shell).
 */
add_filter('woocommerce_locate_template', function ($template, $template_name, $template_path) {
    if ($template_name !== 'checkout/form-checkout.php') {
        return $template;
    }
    $theme_file = get_stylesheet_directory() . '/woocommerce/checkout/form-checkout.php';
    if (file_exists($theme_file)) {
        return $theme_file;
    }
    return $template;
}, 10, 3);

/**
 * Optional: remove Woo’s default “Have a coupon?” banner at top.
 * You already render coupon UI in the summary card.
 */
remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);

/**
 * (Optional) Make sure the folders exist when the theme activates.
 */
add_action('after_switch_theme', function () {
    foreach ([
        get_stylesheet_directory() . '/assets',
        get_stylesheet_directory() . '/assets/css',
        get_stylesheet_directory() . '/assets/js',
        get_stylesheet_directory() . '/woocommerce/checkout',
    ] as $dir) {
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
    }
});


