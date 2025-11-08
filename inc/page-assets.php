<?php
/**
 * Page-Specific Asset Loading
 * 
 * This file contains conditional asset loading for specific pages:
 * - How It Works
 * - Pricing
 * - Contact
 * - Login
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue How It Works page assets
 */
function enqueue_how_it_works_scripts() {
    if (is_page_template('how-it-works.php')) {
        // JavaScript file
        wp_enqueue_script(
            'how-it-works-js',
            get_template_directory_uri() . '/assets/js/how-it-work.js',
            array(),
            '1.0.0',
            true
        );
        
        // CSS file
        wp_enqueue_style(
            'how-it-works-styles',
            get_template_directory_uri() . '/assets/css/how-it-works.css',
            array(),
            '1.0.0'
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_how_it_works_scripts');

/**
 * Enqueue Pricing page assets
 */
function enqueue_pricing_assets() {
    if (is_page_template('pricing.php')) {
        // CSS
        wp_enqueue_style(
            'pricing-styles',
            get_template_directory_uri() . '/assets/css/pricing.css',
            array(),
            '1.0.0'
        );
        
        // JavaScript
        wp_enqueue_script(
            'pricing-js',
            get_template_directory_uri() . '/assets/js/pricing.js',
            array(),
            '1.0.0',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_pricing_assets');

/**
 * Enqueue Contact page assets
 */
function enqueue_contact_assets() {
    if (is_page_template('contact.php')) {
        // CSS
        wp_enqueue_style(
            'contact-styles',
            get_template_directory_uri() . '/assets/css/contact.css',
            array(),
            '1.0.0'
        );
        
        // JavaScript
        wp_enqueue_script(
            'contact-js',
            get_template_directory_uri() . '/assets/js/contact.js',
            array(),
            '1.0.0',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_contact_assets');

/**
 * Enqueue Terms & Conditions page assets
 */
function enqueue_terms_conditions_assets() {
    if (is_page_template('page-terms-conditions.php') || 
        is_page('terms-conditions') || 
        is_page('terms-and-conditions')) {
        // CSS
        wp_enqueue_style(
            'terms-conditions-styles',
            get_template_directory_uri() . '/assets/css/terms-conditions.css',
            array(),
            '1.0.0'
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_terms_conditions_assets');

/**
 * Enqueue Privacy Policy page assets
 */
function enqueue_privacy_policy_assets() {
    if (is_page_template('page-privacy-policy.php') || 
        is_page('privacy-policy') || 
        is_page('privacy')) {
        // CSS
        wp_enqueue_style(
            'privacy-policy-styles',
            get_template_directory_uri() . '/assets/css/privacy-policy.css',
            array(),
            '1.0.0'
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_privacy_policy_assets');

/**
 * Enqueue Shipping Policy page assets
 */
function enqueue_shipping_policy_assets() {
    if (is_page_template('page-shipping-policy.php') || 
        is_page('shipping-policy') || 
        is_page('shipping')) {
        // CSS
        wp_enqueue_style(
            'shipping-policy-styles',
            get_template_directory_uri() . '/assets/css/shipping-policy.css',
            array(),
            '1.0.0'
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_shipping_policy_assets');

/**
 * Enqueue Login page assets
 */
function enqueue_login_assets() {
    if (is_page_template('login.php')) {
        // CSS
        wp_enqueue_style(
            'login-styles',
            get_template_directory_uri() . '/assets/css/login.css',
            array(),
            '1.0.0'
        );

        // JavaScript
        wp_enqueue_script(
            'login-js',
            get_template_directory_uri() . '/assets/js/login.js',
            array(),
            '1.0.0',
            true
        );

        // Localize script with AJAX data needed for OTP functionality
        $dashboard_url = function_exists('aakaari_get_dashboard_url') ? aakaari_get_dashboard_url() : home_url('/my-account/');

        wp_localize_script('login-js', 'registration_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('aakaari_ajax_nonce'),
            'login_url' => wc_get_page_permalink('myaccount'),
            'dashboard_url' => $dashboard_url,
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_login_assets');

/**
 * Enqueue Order pages assets (Order Received & Track Order)
 */
function enqueue_order_pages_assets() {
    // For WooCommerce order received page
    if (is_order_received_page() || is_page('track-order')) {
        wp_enqueue_style(
            'order-pages-styles',
            get_template_directory_uri() . '/assets/css/order-pages.css',
            array(),
            '1.0.0'
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_order_pages_assets');