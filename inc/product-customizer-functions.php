<?php
/**
 * Aakaari Theme: Product Customizer Functions
 * Handles data fetching and asset enqueuing for the customizer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Enqueue scripts and styles specifically for the product customizer.
 *
 * This function checks if we are on a single product page and if that
 * product is marked as customizable via ACF. If so, it loads the
 * necessary CSS and JS, and passes product data to the script.
 */
function aakaari_customizer_assets() {
	// Only run on single product pages
	if ( ! is_product() ) {
		return;
	}

	global $post;
	if ( ! $post ) {
		return;
	}
	$product_id = $post->ID;

	// Check if ACF is active and the product is customizable
	// Make sure your ACF field name is exactly 'is_customizable'
	if ( function_exists('get_field') && get_field('is_customizable', $product_id) ) {

		// --- Enqueue Dedicated Customizer CSS ---
		$css_path = get_template_directory() . '/assets/css/product-customizer.css';
		if ( file_exists( $css_path ) ) {
			wp_enqueue_style(
				'aakaari-product-customizer-style', // Unique handle
				get_template_directory_uri() . '/assets/css/product-customizer.css',
				array(), // Dependencies, if any (like your main theme style)
				filemtime( $css_path ) // Version based on file modification time
			);
		} else {
            // Optional: Log error if file missing
            error_log('Aakaari Theme Error: assets/css/product-customizer.css not found.');
        }

		// --- Enqueue Lucide Icons (CDN) ---
        // Needed because the standalone HTML uses it
		if (!wp_script_is('lucide-icons', 'enqueued')) { // Check if not already loaded
            wp_enqueue_script(
                'lucide-icons',
                'https://unpkg.com/lucide@latest',
                array(),
                null, // No version needed for CDN
                true // Load in footer
            );
        }

		// --- Enqueue Dedicated Customizer JS ---
		$js_path = get_template_directory() . '/assets/js/product-customizer.js';
		if ( file_exists( $js_path ) ) {
			wp_enqueue_script(
				'aakaari-product-customizer-script', // Unique handle
				get_template_directory_uri() . '/assets/js/product-customizer.js',
				array('jquery', 'lucide-icons'), // Dependencies: jQuery and Lucide Icons
				filemtime( $js_path ), // Version
				true // Load in footer
			);

			// --- Pass data from PHP to JavaScript ---
			wp_localize_script(
				'aakaari-product-customizer-script', // Handle for the script to attach data to
				'wpCustomizerData', // Object name in JavaScript (window.wpCustomizerData)
				aakaari_get_customizer_data_for_js( $product_id ) // Function (defined below) to fetch the data
			);
		} else {
             // Optional: Log error if file missing
            error_log('Aakaari Theme Error: assets/js/product-customizer.js not found.');
        }

        // --- IMPORTANT: Ensure Tailwind CSS is loaded ---
        // Add your theme's Tailwind loading logic here IF it's not already loaded globally
        // For example, if you use a compiled main stylesheet:
        /*
        if (!wp_style_is('aakaari-main-style', 'enqueued')) { // Check your main style handle
             wp_enqueue_style(
                 'aakaari-main-style',
                 get_template_directory_uri() . '/style.css', // Or the path to your compiled CSS
                 array(),
                 filemtime( get_template_directory() . '/style.css' )
             );
        }
        */
        // Or if you use the CDN:
        /*
        if (!wp_script_is('tailwindcss-cdn', 'enqueued')) {
            wp_enqueue_script(
                'tailwindcss-cdn',
                'https://cdn.tailwindcss.com',
                array(), null, false // Load in header
            );
        }
        */
	}
}
// Hook the function to run when WordPress enqueues scripts
add_action( 'wp_enqueue_scripts', 'aakaari_customizer_assets', 20 ); // Priority 20 to load after main theme styles


/**
 * Gathers product data needed for the customizer JavaScript.
 *
 * Fetches product details, prices, colors, sides, print/restriction areas (from ACF),
 * and available print types (linking CPTs via ACF).
 * Requires ACF plugin to be active and fields configured correctly.
 *
 * @param int $product_id The ID of the WooCommerce product.
 * @return array An array of data ready to be passed to JavaScript. Returns error array if requirements not met.
 */
function aakaari_get_customizer_data_for_js( $product_id ) {
	// Ensure ACF and WooCommerce functions are available
	if ( ! function_exists('get_field') || ! function_exists('wc_get_product') ) {
		// Log error for debugging
        error_log('Aakaari Theme Error: ACF or WooCommerce function not found in aakaari_get_customizer_data_for_js.');
		return array('error' => 'Required plugin (ACF or WooCommerce) not active or functions unavailable.');
	}

	$product = wc_get_product( $product_id );
	if ( ! $product ) {
        error_log('Aakaari Theme Error: Could not get product object for ID: ' . $product_id);
		return array('error' => 'Product data could not be loaded.');
	}

	// --- Base Product Data ---
	$data = array(
		'product_id'   => $product->get_id(),
		'product_name' => $product->get_name(),
		'description'  => $product->get_short_description() ?: $product->get_description(), // Prefer short description
		'base_price'   => (float) $product->get_price('edit'), // Get raw price without formatting/taxes
        'sale_price'   => $product->is_on_sale('edit') ? (float) $product->get_sale_price('edit') : null,
	);

	// --- ACF: Colors (Repeater field: 'product_colors') ---
	$data['colors'] = array();
	if ( have_rows('product_colors', $product_id) ) {
		while ( have_rows('product_colors', $product_id) ) : the_row();
			$image = get_sub_field('color_image'); // Assumes ACF image field returning array
			$data['colors'][] = array(
				'name'  => sanitize_text_field(get_sub_field('color_name')),   // Sub-field: 'color_name' (Text)
				'color' => sanitize_hex_color(get_sub_field('color_hex')),    // Sub-field: 'color_hex' (Color Picker)
				'image' => $image ? esc_url($image['url']) : null,            // Sub-field: 'color_image' (Image) -> URL
			);
		endwhile;
	}
    // Ensure at least one default color object if none are defined via ACF
    if (empty($data['colors'])) {
        $data['colors'][] = array('name' => 'Default', 'color' => '#FFFFFF', 'image' => null);
    }


	// --- ACF: Sides & Areas (Repeater field: 'product_sides') ---
	$data['sides'] = array();
	if ( have_rows('product_sides', $product_id) ) {
		$side_index = 0;
		while ( have_rows('product_sides', $product_id) ) : the_row();
			$side_id = 'side_' . $side_index++; // Generate unique ID for this side instance
			$side_data = array(
				'id'   => $side_id,
				'name' => sanitize_text_field(get_sub_field('side_name')), // Sub-field: 'side_name' (Text)
				'printAreas' => array(),
				'restrictionAreas' => array(),
			);

			// Get Print Area (Group field: 'print_area')
			$print_area_group = get_sub_field('print_area');
			// Check if group exists AND has necessary dimensions
			if ( $print_area_group && isset($print_area_group['pa_width']) && isset($print_area_group['pa_height']) ) {
				$side_data['printAreas'][] = array(
                    'id'     => 'pa_' . $side_id . '_0', // Unique ID for print area
                    'name'   => 'Print Area', // Default name, consider adding ACF field later
					'x'      => absint($print_area_group['pa_x'] ?? 0),      // Sub-field: 'pa_x' (Number)
					'y'      => absint($print_area_group['pa_y'] ?? 0),      // Sub-field: 'pa_y' (Number)
					'width'  => absint($print_area_group['pa_width'] ?? 100), // Sub-field: 'pa_width' (Number), default 100
					'height' => absint($print_area_group['pa_height'] ?? 100),// Sub-field: 'pa_height' (Number), default 100
				);
			}

			// Get Restriction Area(s) (Repeater field: 'restriction_areas')
			if ( have_rows('restriction_areas') ) {
                $ra_index = 0;
				while ( have_rows('restriction_areas') ) : the_row();
					$side_data['restrictionAreas'][] = array(
                        'id'     => 'ra_' . $side_id . '_' . $ra_index++, // Unique ID
                        'name'   => 'Restriction Zone ' . ($ra_index), // Default name
						'x'      => absint(get_sub_field('ra_x') ?? 0),        // Sub-field: 'ra_x' (Number)
						'y'      => absint(get_sub_field('ra_y') ?? 0),        // Sub-field: 'ra_y' (Number)
						'width'  => absint(get_sub_field('ra_width') ?? 50),   // Sub-field: 'ra_width' (Number), default 50
						'height' => absint(get_sub_field('ra_height') ?? 50),  // Sub-field: 'ra_height' (Number), default 50
					);
				endwhile;
			}
			$data['sides'][] = $side_data;
		endwhile;
	}

	// --- ACF: Available Print Types (Post Object field: 'available_print_types') ---
	// This field should link to posts of your 'Print Type' CPT
	$data['printTypes'] = array();
	$available_print_type_posts = get_field('available_print_types', $product_id);

	if ( is_array($available_print_type_posts) ) {
		foreach ( $available_print_type_posts as $post_obj_or_id ) {
            // ACF Post Object field can return ID or WP_Post object
            $post_id = is_object($post_obj_or_id) ? $post_obj_or_id->ID : $post_obj_or_id;

			// Ensure it's a valid post ID and the correct post type
            $print_type_post = get_post($post_id);
			if ( !$print_type_post || $print_type_post->post_type !== 'print_type' ) { // IMPORTANT: Replace 'print_type' with your actual CPT slug
                continue;
            }
            $post_id = $print_type_post->ID; // Use the confirmed ID

			// Get ACF fields for the Print Type post
            // Make sure these field names match your Print Type CPT ACF group
			$pricing_model = get_field('pricing_model', $post_id);        // Field: 'pricing_model' (Select: fixed, per-inch, per-px)
			$price = get_field('price', $post_id);                          // Field: 'price' (Number)
            $description = get_field('print_description', $post_id);      // Field: 'print_description' (Text Area or Text)

			$data['printTypes'][] = array(
				'id'          => 'pt_' . $post_id, // Unique ID for JS usage (prefix 'pt_')
				'name'        => get_the_title( $post_id ),
				'description' => sanitize_text_field($description ?: ''),
				'pricingModel'=> sanitize_key($pricing_model ?: 'fixed'), // Default to 'fixed' if not set
				'price'       => (float) ($price ?: 0), // Default to 0 if not set
			);
		}
	}

	return $data; // Return the complete data array
}

?>