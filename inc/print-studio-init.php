<?php
// inc/print-studio-init.php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Initialize and enqueue Custom Print Studio assets for the Admin Dashboard page template.
 * - Place this file in your theme's inc/ folder and include it from functions.php:
 *   require get_template_directory() . '/inc/print-studio-init.php';
 */

// Only enqueue on front-end admin dashboard page template
add_action( 'wp_enqueue_scripts', function() {
    if ( is_admin() ) {
        return;
    }

    // Only load on the page that uses the Admin Dashboard template
    if ( ! is_page_template( 'admindashboard.php' ) ) {
        return;
    }

    $asset_dir = get_template_directory_uri() . '/assets/print-studio';

    // CSS
    wp_enqueue_style(
        'aakaari-print-studio',
        $asset_dir . '/print-studio.css',
        array(),
        filemtime( get_template_directory() . '/assets/print-studio/print-studio.css' )
    );

    // Lucide icons are used in original code; use unpkg CDN (or host locally if preferred)
    wp_enqueue_script( 'lucide-icons', 'https://unpkg.com/lucide@latest', array(), null, true );

    // JS - depends on lucide
wp_enqueue_script(
        'aakaari-print-studio',
        $asset_dir . '/print-studio.js',
        array('jquery', 'lucide-icons'), // <-- MODIFIED: Added 'jquery'
        filemtime(get_template_directory() . '/assets/print-studio/print-studio.js'),
        true
    );

    // Pass WP data to script (nonce + ajax if needed later)
     wp_localize_script( 'aakaari-print-studio', 'AakaariPS', array(
         'ajax_url' => admin_url( 'admin-ajax.php' ),
         // Make sure the nonce name here MATCHES the one checked in PHP
         'nonce'    => wp_create_nonce( 'aakaari_print_studio_nonce' ), 
         'siteUrl'  => get_site_url(),
     ) );
} );

/**
 * Optional helper to render the container where the app will render.
 * Your admindashboard.php already contains: <div id="custom-print-studio-app"></div>
 * If you prefer to output via include instead, call:
 *    include get_template_directory() . '/inc/print-studio-init.php';
 */
