<?php
/**
 * Aakaari Custom Checkout Implementation
 * 
 * This file contains all the necessary code to implement the custom checkout experience.
 * Add this code to your theme's functions.php file, or include it as a separate file.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Load custom checkout UI only on the checkout page
 */
add_action('wp_enqueue_scripts', function() {
    if (!is_checkout() || is_order_received_page()) return;

    // Inter font + lucide icons
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

    // Checkout CSS
    wp_enqueue_style(
        'aakaari-checkout',
        get_stylesheet_directory_uri() . '/assets/css/checkout.css',
        [],
        '1.0'
    );

    // Checkout JS – depends on wc-checkout + jquery for update_checkout events etc.
    wp_enqueue_script(
        'aakaari-checkout',
        get_stylesheet_directory_uri() . '/assets/js/checkout.js',
        ['jquery', 'wc-checkout'],
        '1.0',
        true
    );

    // Pass a couple of useful values to JS
    wp_localize_script('aakaari-checkout', 'aakaariCheckout', [
        'cartUrl' => wc_get_cart_url(),
    ]);
}, 99);

/**
 * Add a custom class to the checkout page body
 */
add_filter('body_class', function($classes) {
    if (is_checkout() && !is_order_received_page()) {
        $classes[] = 'aak-checkout-page';
    }
    return $classes;
});

/**
 * Make shipping options more JS-friendly: add price data-* attribute to inputs
 * (Used if you want to read selected rate price in JS; optional)
 */
add_filter('woocommerce_cart_shipping_method_full_label', function($label, $method) {
    if (is_callable([$method, 'get_cost'])) {
        $cost = (float) $method->get_cost();
        // Woo prints the <input> separately; we can still include a readable price string in label
        $label .= sprintf(
            '<span class="aakaari-shipping-price" data-price="%s" style="display:none;"></span>',
            esc_attr($cost)
        );
    }
    return $label;
}, 10, 2);

/**
 * Style WooCommerce's radio buttons and shipping methods to match our design
 */
add_filter('woocommerce_shipping_rate_html', function($html, $method) {
    // Get original price
    $price = $method->get_cost() ? wc_price($method->get_cost()) : __('Free', 'woocommerce');
    
    // Create our custom HTML
    $html = '<div class="radio-option-details">';
    $html .= '<div class="title">' . esc_html($method->get_label()) . '</div>';
    
    // Add meta data if any
    if ($method->get_meta_data()) {
        $html .= '<div class="description">' . wc_get_formatted_cart_item_data($method) . '</div>';
    }
    
    $html .= '</div>';
    
    // Add price with special free class if needed
    $priceClass = ($method->get_cost() > 0) ? 'radio-option-price' : 'radio-option-price free';
    $html .= '<div class="' . esc_attr($priceClass) . '">' . $price . '</div>';
    
    return $html;
}, 10, 2);

/**
 * Wrap shipping methods in our custom radio option container
 */
add_filter('woocommerce_after_shipping_rate', function($method, $index) {
    echo '</label></div>'; // Close the radio option
}, 10, 2);

add_filter('woocommerce_before_shipping_rate', function($method, $index) {
    echo '<div class="radio-option"><label>'; // Open the radio option
}, 10, 2);

/**
 * Locate the custom checkout template from the theme
 */
add_filter('woocommerce_locate_template', function($template, $template_name, $template_path) {
    if ($template_name !== 'checkout/form-checkout.php') {
        return $template;
    }
    
    // Look for the template in the theme directory
    $theme_file = get_stylesheet_directory() . '/woocommerce/' . $template_name;
    
    // Check if the file exists in the theme
    if (file_exists($theme_file)) {
        return $theme_file;
    }
    
    return $template;
}, 10, 3);

/**
 * Add wrapper to payment methods to match our design
 */
add_action('woocommerce_checkout_before_payment', function() {
    echo '<div class="radio-group">';
}, 5);

add_action('woocommerce_checkout_after_payment', function() {
    echo '</div>';
}, 15);

/**
 * Add custom classes to payment method inputs
 */
add_filter('woocommerce_gateway_icon', function($icon, $id) {
    // Add custom icon handling here if needed
    return $icon;
}, 10, 2);

/**
 * Make sure file structure exists
 */
function aakaari_ensure_checkout_files_exist() {
    // Check if theme assets directory exists
    $assets_dir = get_stylesheet_directory() . '/assets';
    if (!file_exists($assets_dir)) {
        mkdir($assets_dir, 0755, true);
    }
    
    // Check if CSS directory exists
    $css_dir = $assets_dir . '/css';
    if (!file_exists($css_dir)) {
        mkdir($css_dir, 0755, true);
    }
    
    // Check if JS directory exists
    $js_dir = $assets_dir . '/js';
    if (!file_exists($js_dir)) {
        mkdir($js_dir, 0755, true);
    }
    
    // Check if WooCommerce template directory exists
    $woo_dir = get_stylesheet_directory() . '/woocommerce/checkout';
    if (!file_exists($woo_dir)) {
        mkdir($woo_dir, 0755, true);
    }
}

// Run the function to ensure directories exist
add_action('after_switch_theme', 'aakaari_ensure_checkout_files_exist');