<?php
/**
 * Aakaari functions and definitions
 * 
 * This file now acts as a loader for modular function files
 * Each major functionality is separated into its own file in the /inc directory
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Load all modular function files
 * Each file handles specific functionality to keep code organized
 * 
 * IMPORTANT: Make sure to create the /inc folder in your theme directory
 * and add all the required files before activating this functions.php
 */

$inc_dir = get_template_directory() . '/inc/';

// Check if inc directory exists
if (!is_dir($inc_dir)) {
    wp_die('Error: The /inc directory is missing in your theme. Please create it at: ' . $inc_dir);
}

// List of required files
$required_files = array(
    'theme-setup.php',
    'woocommerce.php',
    'page-assets.php',
    'reseller-cta.php',
    'reseller-application.php',
    'reseller-registration.php',
    'reseller-dashboard.php',
    'security-features.php',
    'otp-service.php',
    'ajax-handlers.php',
    'admin-login-functions.php',
    'admin-dashboard-functions.php',
    'print-studio-init.php',
    'print-studio-ajax.php',
    'product-customizer-functions.php',
    'cp-functions.php',
    'print-studio-fabric-print-handlers.php',
    'functional-cart-functions.php',
    'woocommerce-checkout.php',
);

// Load each file with error checking
foreach ($required_files as $file) {
    $filepath = $inc_dir . $file;
    
    // Skip WooCommerce files if WooCommerce is not active
    if ($file === 'woocommerce.php' || $file === 'product-customizer.php') {
        if (!class_exists('WooCommerce')) {
            continue;
        }
    }
    
    if (file_exists($filepath)) {
        require_once $filepath;
    } else {
        // Show admin notice instead of dying
        add_action('admin_notices', function() use ($file) {
            echo '<div class="notice notice-error"><p>';
            echo 'Missing required file: /inc/' . esc_html($file);
            echo '</p></div>';
        });
    }
}

// === Cart page assets (CSS/JS) ===
add_action('wp_enqueue_scripts', function () {
    if ( ! is_cart() ) return;

    // Fonts used by the design
    wp_enqueue_style(
        'aakaari-inter',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        [],
        null
    );
    // Optional (trust-badge icons): use <span class="material-icons-outlined">verified_user</span>
    wp_enqueue_style(
        'aakaari-material-icons-outlined',
        'https://fonts.googleapis.com/css2?family=Material+Icons+Outlined',
        [],
        null
    );

    // Your cart CSS (load after WooCommerce so it wins)
    wp_enqueue_style(
        'aakaari-cart',
        get_stylesheet_directory_uri() . './assets/css/cart2.css', // change path if you keep it elsewhere
        ['woocommerce-general','woocommerce-layout','woocommerce-smallscreen'],
        '1.0'
    );

    // Your cart JS
    wp_enqueue_script(
        'aakaari-cart',
        get_stylesheet_directory_uri() . './assets/js/cart2.js', // change path if needed
        ['jquery','jquery-blockui','wc-cart-fragments'],
        '1.0',
        true
    );
}, 99);

add_action('wp_enqueue_scripts', function () {
    if ( ! is_checkout() || is_order_received_page() ) {
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

    // Path + version for cache-busting
    $css_path = get_stylesheet_directory() . '/assets/css/checkout.css';
    $css_uri  = get_stylesheet_directory_uri() . '/assets/css/checkout.css';
    $ver      = file_exists($css_path) ? filemtime($css_path) : '1.0';

    // Load AFTER Woo + theme styles
    wp_enqueue_style(
        'aakaari-checkout',
        $css_uri,
        ['woocommerce-general', 'select2'],   // make sure we come after these
        $ver
    );

    // Your JS (after wc-checkout so update_checkout works)
    $js_path = get_stylesheet_directory() . '/assets/js/checkout.js';
    $js_uri  = get_stylesheet_directory_uri() . '/assets/js/checkout.js';
    $js_ver  = file_exists($js_path) ? filemtime($js_path) : '1.0';

    wp_enqueue_script(
        'aakaari-checkout',
        $js_uri,
        ['jquery', 'wc-checkout'],
        $js_ver,
        true
    );

    wp_localize_script('aakaari-checkout', 'aakaariCheckout', [
        'cartUrl' => wc_get_cart_url(),
    ]);
}, /* priority */ 9999);   // <- important: load last
// Include the shipping debug tool for admins
if (is_admin() || current_user_can('manage_options')) {
    require_once get_stylesheet_directory() . '/woocommerce-shipping-debug.php';
}