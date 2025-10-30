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
            get_template_directory_uri() . '/assets/js/how-it-works.js',
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