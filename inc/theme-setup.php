<?php
/**
 * Theme Setup and Core Functionality
 * 
 * This file contains:
 * - Basic theme setup
 * - Main scripts and styles
 * - Header/footer assets
 * - Navigation menus
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Setup
 */
if (!function_exists('aakaari_setup')) :
    function aakaari_setup() {
        // Add default theme supports
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('custom-logo');
        add_theme_support('html5', array(
            'search-form', 
            'comment-form', 
            'comment-list', 
            'gallery', 
            'caption'
        ));
        
        // Add WooCommerce support
        add_theme_support('woocommerce');
        
        // Register navigation menus
        register_nav_menus(array(
            'primary' => __('Primary Menu', 'aakaari'),
        ));
        
        // Register custom image sizes
        add_image_size('aakaari-hero', 1200, 800, true);
    }
endif;
add_action('after_setup_theme', 'aakaari_setup');

/**
 * Enqueue main theme scripts and styles
 */
function aakaari_scripts() {
    // Main theme stylesheet
    wp_enqueue_style(
        'aakaari-style',
        get_stylesheet_uri(),
        array(),
        wp_get_theme()->get('Version')
    );

    // Main theme JavaScript
    wp_enqueue_script(
        'aakaari-script',
        get_template_directory_uri() . '/assets/js/main.js',
        array('jquery'),
        wp_get_theme()->get('Version'),
        true
    );

    // Mobile Menu CSS - v1.0.3 with optimizations
    wp_enqueue_style(
        'aakaari-mobile-menu',
        get_template_directory_uri() . '/assets/css/mobile-menu.css',
        array(),
        '1.0.3'
    );

    // Mobile Menu JavaScript - v1.0.3 with fixed scroll restoration
    wp_enqueue_script(
        'aakaari-mobile-menu',
        get_template_directory_uri() . '/assets/js/mobile-menu.js',
        array('jquery'),
        '1.0.3',
        true
    );
}
add_action('wp_enqueue_scripts', 'aakaari_scripts');

/**
 * Enqueue homepage assets
 */
function aakaari_main_enqueue_assets() {
    // Google Fonts
    wp_enqueue_style(
        'aakaari_main_google_fonts', 
        'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap', 
        array(), 
        null
    );

    // Homepage styles
    $css_path = get_template_directory() . '/assets/css/homepage.css';
    if (file_exists($css_path)) {
        wp_enqueue_style(
            'aakaari_main_homepage_style', 
            get_template_directory_uri() . '/assets/css/homepage.css', 
            array(), 
            filemtime($css_path)
        );
    }

    // Homepage JavaScript
    $js_path = get_template_directory() . '/assets/js/homepage.js';
    if (file_exists($js_path)) {
        wp_enqueue_script(
            'aakaari_main_homepage_js', 
            get_template_directory_uri() . '/assets/js/homepage.js', 
            array('jquery'), 
            filemtime($js_path), 
            true
        );

        // Localize Ajax URL for Quick View
        wp_localize_script('aakaari_main_homepage_js', 'aakaari_qv', array(
            'ajax_url' => admin_url('admin-ajax.php'),
        ));
    }

    // Dashicons (optional but useful)
    wp_enqueue_style('dashicons');
}
add_action('wp_enqueue_scripts', 'aakaari_main_enqueue_assets', 20);

/**
 * Enqueue header assets
 */
function enqueue_aakaari_header_assets() {
    // Font Awesome for mobile menu icons
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css',
        array(),
        '6.0.0'
    );

    // Header styles
    wp_enqueue_style(
        'aakaari-header-styles',
        get_template_directory_uri() . '/assets/css/header.css',
        array('font-awesome'),
        '1.0.0'
    );

    // Header scripts - DISABLED: Conflicts with mobile-menu.js
    // Both scripts were listening to #mobile-menu-toggle causing menu to get stuck
    // Mobile-menu.js is the primary mobile menu controller
    /*
    wp_enqueue_script(
        'aakaari-header-script',
        get_template_directory_uri() . '/assets/js/header.js',
        array(),
        '1.0.0',
        true
    );
    */
}
add_action('wp_enqueue_scripts', 'enqueue_aakaari_header_assets');

/**
 * Enqueue footer assets
 */
function enqueue_aakaari_footer_assets() {
    wp_enqueue_style(
        'aakaari-footer-styles',
        get_template_directory_uri() . '/assets/css/footer.css',
        array(),
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'enqueue_aakaari_footer_assets');

/**
 * Enqueue How It Works page assets
 */
function enqueue_how_it_works_assets() {
    // Only load on the How It Works page
    if (is_page_template('how-it-works.php') || is_page('how-it-works')) {
        // How It Works CSS - v1.0.3 with improved FAQ transitions
        wp_enqueue_style(
            'aakaari-how-it-works-styles',
            get_template_directory_uri() . '/assets/css/how-it-works.css',
            array(),
            '1.0.3'
        );

        // How It Works JavaScript - v1.0.3 with debugged FAQ accordion
        wp_enqueue_script(
            'aakaari-how-it-works-script',
            get_template_directory_uri() . '/assets/js/how-it-works.js',
            array(),
            '1.0.3',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_how_it_works_assets');
