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
    'product-customizer.php'
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