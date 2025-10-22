<?php
/**
 * Aakaari Theme: Custom Shop Page Template Setup
 * Enqueues dedicated assets ONLY for the 'page-shop-custom.php' template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Enqueue shop page specific CSS and JS for the custom template.
 */
function aakaari_custom_shop_page_assets() {
	// --- MODIFIED CONDITION ---
    // Only load on the specific page template we created
	if ( is_page_template('page-shop-custom.php') ) {

		// --- Enqueue Dedicated Custom Shop Page CSS ---
		$shop_css_path = get_template_directory() . '/assets/css/shop-page-custom.css'; // Use new CSS file name
		if ( file_exists( $shop_css_path ) ) {
			wp_enqueue_style(
				'aakaari-shop-page-custom-style', // New handle
				get_template_directory_uri() . '/assets/css/shop-page-custom.css',
				array(), // Dependencies
				filemtime( $shop_css_path ) // Version based on file modification time
			);
		}

         // --- Enqueue Lucide Icons (CDN) ---
         // Needed because the standalone HTML uses it
         if (!wp_script_is('lucide-icons', 'enqueued')) { // Check if not already loaded by theme
             wp_enqueue_script(
                 'lucide-icons',
                 'https://unpkg.com/lucide@latest',
                 array(),
                 null,
                 true // Load in footer
             );
         }

		// --- Enqueue Dedicated Custom Shop Page JS ---
		$shop_js_path = get_template_directory() . '/assets/js/shop-page-custom.js'; // Use new JS file name
		if ( file_exists( $shop_js_path ) ) {
			wp_enqueue_script(
				'aakaari-shop-page-custom-script', // New handle
				get_template_directory_uri() . '/assets/js/shop-page-custom.js',
				// Dependencies: jQuery and ensure Lucide loads first if needed by JS immediately
				array('jquery', 'lucide-icons'),
				filemtime( $shop_js_path ), // Version
				true // Load in footer
			);

            // --- Pass data --- (Minimal for now as JS uses mock data)
            wp_localize_script(
                'aakaari-shop-page-custom-script',
                'shopPageData', // JS Object Name
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('aakaari_shop_nonce'),
                    // Add other data here if needed later (e.g., product data via AJAX endpoint)
                 )
            );
		}

        // --- IMPORTANT: Ensure Tailwind CSS is loaded ---
        // This check should ideally be in your main theme setup (inc/theme-setup.php)
        // or header.php to load globally. Add it here only if it's *not* loaded globally.
        // Example CDN:
        /*
        if (!wp_script_is('tailwindcss-cdn', 'enqueued')) {
            wp_enqueue_script(
                'tailwindcss-cdn',
                'https://cdn.tailwindcss.com',
                array(), null, false // Load in header might be better
            );
        }
        */
	}
}
// Use the correct hook name used in the function definition
add_action( 'wp_enqueue_scripts', 'aakaari_custom_shop_page_assets', 30 ); // Priority 30 to load after main styles

?>