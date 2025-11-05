<?php
// Improved cp-functions.php for the product customizer

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get product mockups supporting both WooCommerce variations and color slugs
 *
 * @param int $product_id Product ID
 * @return array Mockup data indexed by variation ID or color slug
 */
function aakaari_get_product_mockups($product_id) {
    $mockups = array();

    // First, try to get color-specific mockups stored on the parent product
    $color_mockups = get_post_meta($product_id, '_aakaari_color_mockups', true);
    if (is_array($color_mockups)) {
        $mockups = $color_mockups;
    }

    // Check if this is a variable product with actual variations
    $product = wc_get_product($product_id);
    if ($product && $product->is_type('variable')) {
        $variations = $product->get_available_variations();

        foreach ($variations as $variation) {
            $variation_id = $variation['variation_id'];

            // Check if this variation has its own mockup
            $variation_mockup = get_post_meta($variation_id, '_variation_mockup_data', true);

            if (!empty($variation_mockup) && is_array($variation_mockup)) {
                // Store by variation ID
                $mockups['variation_' . $variation_id] = $variation_mockup;

                // Also store by color attribute if available
                $attributes = $variation['attributes'];
                if (isset($attributes['attribute_pa_color'])) {
                    $mockups[$attributes['attribute_pa_color']] = $variation_mockup;
                }
            }
        }
    }

    // Ensure mockups are valid
    foreach ($mockups as $key => $mockup) {
        if (!isset($mockup['attachment_id']) || !isset($mockup['url'])) {
            unset($mockups[$key]);
        }
    }

    return $mockups;
}

function aakaari_cp_enqueue_assets_and_localize() {
    if ( ! function_exists( 'is_product' ) || ! is_product() ) {
        return; // only enqueue on single product pages
    }

    $css_path = get_stylesheet_directory_uri() . '/assets/css/product-customizer.css';
    $css_file = get_template_directory() . '/assets/css/product-customizer.css';
    $js_path  = get_stylesheet_directory_uri() . '/assets/js/product-customizer.js';
    $js_file  = get_template_directory() . '/assets/js/product-customizer.js';

    // Only enqueue CSS if file exists
    if ( file_exists( $css_file ) ) {
        wp_enqueue_style( 'aakaari-product-customizer', $css_path, array(), filemtime( $css_file ) );
    }
    
    wp_enqueue_script( 'lucide-icons', 'https://unpkg.com/lucide@latest', array(), null, true );
    
    // Only enqueue main JS if file exists
    $product_customizer_deps = array( 'jquery', 'lucide-icons' );
    if ( file_exists( $js_file ) ) {
        wp_enqueue_script( 'aakaari-product-customizer', $js_path, $product_customizer_deps, filemtime( $js_file ), true );
        $enhancements_deps = array( 'jquery', 'aakaari-product-customizer' );
    } else {
        // If main file doesn't exist, enhancements can depend on jquery and lucide only
        $enhancements_deps = array( 'jquery', 'lucide-icons' );
    }

    // Enqueue enhancements (this file exists)
    $enhancements_path = get_stylesheet_directory_uri() . '/assets/js/product-customizer-enhancements.js';
    $enhancements_file = get_template_directory() . '/assets/js/product-customizer-enhancements.js';
    if ( file_exists( $enhancements_file ) ) {
        wp_enqueue_script( 'aakaari-product-customizer-enhancements', $enhancements_path, $enhancements_deps, filemtime( $enhancements_file ), true );
    }

    global $post, $product;
    if ( empty( $product ) || ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
        $product = wc_get_product( isset( $post ) ? $post->ID : get_the_ID() );
    }

    // Determine which script handle to use for localization
    $localize_handle = file_exists( $js_file ) ? 'aakaari-product-customizer' : 'aakaari-product-customizer-enhancements';

    if ( ! $product ) {
        // Localize minimal empty structures so front-end never errors
        if ( wp_script_is( $localize_handle, 'enqueued' ) ) {
            wp_localize_script( $localize_handle, 'AAKAARI_PRODUCTS', array() );
            wp_localize_script( $localize_handle, 'AAKAARI_PRINT_TYPES', array() );
            wp_localize_script( $localize_handle, 'AAKAARI_SETTINGS', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'aakaari_customizer' ),
                'max_upload_size' => wp_max_upload_size(),
            ) );
        }
        return;
    }

    // Robust price extraction (works with variable products)
    $regular = $product->get_regular_price();
    $sale    = $product->get_sale_price();
    $price   = $product->get_price(); // fallback

    $base_price = $regular !== '' ? (float)$regular : ( $price !== '' ? (float)$price : 0.0 );
    $sale_price = $sale !== '' ? (float)$sale : null;

    // Get product ID
    $product_id = $product->get_id();

    // categories
    $cats = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );

    // attributes (simple flattening)
    $attributes = array();
    foreach ( $product->get_attributes() as $attr_key => $attr_obj ) {
        $attributes[ $attr_key ] = wc_get_product_terms( $product_id, $attr_key, array( 'fields' => 'names' ) );
    }

    // IMPROVED: Get print studio data (with fallback)
    $studio_data = get_post_meta($product_id, '_aakaari_print_studio_data', true);
    
    if (!is_array($studio_data)) {
        $studio_data = array(
            'printTypes' => array(),
            'colors' => array(),
            'sides' => array(),
        );
    }
    
    // IMPROVED: Get image URL (with better error handling)
    $image_id = $product->get_image_id();
    $image_url = '';
    
    if ($image_id) {
        $image_url = wp_get_attachment_image_url($image_id, 'full');
    }
    
    // If no product image, try to get one from the sides data
    if (empty($image_url) && !empty($studio_data['sides']) && is_array($studio_data['sides'])) {
        foreach ($studio_data['sides'] as $side) {
            if (!empty($side['imageUrl'])) {
                $image_url = $side['imageUrl'];
                break;
            }
        }
    }
    
    // If still no image, use placeholder
    if (empty($image_url)) {
        $image_url = wc_placeholder_img_src('full');
    }

    // IMPROVED: Transform Print Studio colors format (hex array) to product customizer format (object array)
    // ENHANCED: Include color variant images
    $colors_for_customizer = array();
    $color_variant_images = get_post_meta($product_id, '_aakaari_color_variant_images', true);
    if (!is_array($color_variant_images)) {
        $color_variant_images = array();
    }

    if (!empty($studio_data['colors']) && is_array($studio_data['colors'])) {
        // Print Studio saves colors as array of hex values: ['#FF0000', '#00FF00']
        // Product customizer needs: [{name: 'Red', color: '#FF0000', image: 'url'}, ...]
        foreach ($studio_data['colors'] as $hex) {
            $color_name = aakaari_hex_to_color_name($hex); // Convert hex back to name

            // Get color variant image URL if available
            $color_image_url = '';
            if (isset($color_variant_images[$hex])) {
                $color_image_url = wp_get_attachment_image_url($color_variant_images[$hex], 'full') ?: '';
            }

            $colors_for_customizer[] = array(
                'name' => $color_name,
                'color' => $hex,
                'image' => $color_image_url, // Per-color image override
            );
        }
    }

    // Fallback if no colors configured in Print Studio
    if (empty($colors_for_customizer)) {
        $colors_for_customizer = aakaari_get_product_colors($product);
    }

    // IMPROVED: Transform Print Studio fabrics format
    $fabrics_for_customizer = array();
    if (!empty($studio_data['fabrics']) && is_array($studio_data['fabrics'])) {
        // Print Studio saves fabrics as array of IDs: ['fab_123', 'fab_456']
        // Get full fabric data from pa_fabric taxonomy
        foreach ($studio_data['fabrics'] as $fabric_id) {
            // Extract term ID from 'fab_123' format
            $term_id = intval(str_replace('fab_', '', $fabric_id));
            $term = get_term($term_id, 'pa_fabric');
            
            if ($term && !is_wp_error($term)) {
                $description = get_term_meta($term_id, 'description', true) ?: $term->description;
                $price = get_term_meta($term_id, 'price', true) ?: 0;
                
                $fabrics_for_customizer[] = array(
                    'id' => $fabric_id,
                    'name' => $term->name,
                    'description' => $description,
                    'price' => floatval($price),
                );
            }
        }
    }

    // IMPROVED: Transform Print Studio print types format
    $print_types_for_customizer = array();
    if (!empty($studio_data['printTypes']) && is_array($studio_data['printTypes'])) {
        // Print Studio saves print types as array of IDs: ['pt_123', 'pt_456']
        // Get full print type data from pa_print_type taxonomy
        foreach ($studio_data['printTypes'] as $print_type_id) {
            // Extract term ID from 'pt_123' format
            $term_id = intval(str_replace('pt_', '', $print_type_id));
            $term = get_term($term_id, 'pa_print_type');
            
            if ($term && !is_wp_error($term)) {
                $description = get_term_meta($term_id, 'description', true) ?: $term->description;
                $pricing_model = get_term_meta($term_id, 'pricing_model', true) ?: 'fixed';
                $price = get_term_meta($term_id, 'price', true) ?: 0;
                
                $print_types_for_customizer[] = array(
                    'id' => $print_type_id,
                    'name' => $term->name,
                    'description' => $description,
                    'pricingModel' => $pricing_model,
                    'price' => floatval($price),
                );
            }
        }
    }
    
    // Fallback to defaults if no print types configured
    if (empty($print_types_for_customizer)) {
        $print_types_for_customizer = array(
            array('id'=>'pt_dtg','name'=>'DTG','description'=>'Per-square-inch pricing','pricingModel'=>'per-inch','price'=>0.12),
            array('id'=>'pt_vinyl','name'=>'HTV','description'=>'Fixed per-design','pricingModel'=>'fixed','price'=>5.00),
        );
    }

    // IMPROVED: Transform Print Studio sizes format
    $sizes_for_customizer = array();
    if (!empty($studio_data['sizes']) && is_array($studio_data['sizes'])) {
        // Print Studio saves sizes as array of IDs: ['size_123', 'size_456']
        // Get full size data from pa_size taxonomy
        foreach ($studio_data['sizes'] as $size_id) {
            // Extract term ID from 'size_123' format
            $term_id = intval(str_replace('size_', '', $size_id));
            $term = get_term($term_id, 'pa_size');
            
            if ($term && !is_wp_error($term)) {
                $sizes_for_customizer[] = array(
                    'id' => $size_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                );
            }
        }
    }

    // Build the product data array
    $product_data = array(
        'id' => $product_id,
        'name' => $product->get_name(),
        'description' => wp_strip_all_tags( $product->get_description() ),
        'basePrice' => $base_price,
        'salePrice' => $sale_price,
        'price' => (float) $price,
        'thumbnail' => $image_url, // FIXED: Now has better fallbacks
        'categories' => $cats,
        'attributes' => $attributes,
        'isActive' => true,
        // IMPROVED: Use studio data directly or fallback to defaults
        'availablePrintTypes' => !empty($studio_data['printTypes']) ? $studio_data['printTypes'] : array( 'pt_dtg', 'pt_vinyl' ),
        'colors' => $colors_for_customizer, // FIXED: Now uses properly formatted color objects
        'fabrics' => $fabrics_for_customizer, // NEW: Fabric options
        'sizes' => $sizes_for_customizer, // NEW: Size options
        'sides' => !empty($studio_data['sides']) ? $studio_data['sides'] : aakaari_get_product_sides($product),
    );

    // Get color-specific mockups (supports both variation IDs and color slugs)
    $color_mockups = aakaari_get_product_mockups($product_id);

    // Localize arrays - use the converted print types
    wp_localize_script( 'aakaari-product-customizer', 'AAKAARI_PRODUCTS', array( $product_data ) );
    wp_localize_script( 'aakaari-product-customizer', 'AAKAARI_PRINT_TYPES', $print_types_for_customizer );
    wp_localize_script( 'aakaari-product-customizer', 'AAKAARI_COLOR_MOCKUPS', $color_mockups );
    wp_localize_script( 'aakaari-product-customizer', 'AAKAARI_SETTINGS', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'aakaari_customizer' ),
        'max_upload_size' => wp_max_upload_size(),
        'is_print_studio_product' => !empty($studio_data), // ADDED: Flag for JS
        'woocommerce_currency' => get_woocommerce_currency_symbol(),
    ) );

}
add_action( 'wp_enqueue_scripts', 'aakaari_cp_enqueue_assets_and_localize', 20 );


/**
 * Convert hex color to human-readable color name.
 * Returns the closest matching color name from common colors.
 */
function aakaari_hex_to_color_name($hex) {
    // Remove # if present
    $hex = ltrim($hex, '#');
    
    // Common color mappings (hex => name)
    $color_map = array(
        'FF0000' => 'Red',
        'FF6347' => 'Tomato',
        'FF4500' => 'Orange Red',
        'FFA500' => 'Orange',
        'FFD700' => 'Gold',
        'FFFF00' => 'Yellow',
        '00FF00' => 'Lime',
        '32CD32' => 'Lime Green',
        '008000' => 'Green',
        '00FFFF' => 'Cyan',
        '00CED1' => 'Dark Turquoise',
        '0000FF' => 'Blue',
        '0000CD' => 'Medium Blue',
        '000080' => 'Navy',
        '800080' => 'Purple',
        '8B008B' => 'Dark Magenta',
        'FF00FF' => 'Magenta',
        'FFC0CB' => 'Pink',
        'FFFFFF' => 'White',
        'F5F5F5' => 'White Smoke',
        'C0C0C0' => 'Silver',
        '808080' => 'Gray',
        '000000' => 'Black',
        'A52A2A' => 'Brown',
        '8B4513' => 'Saddle Brown',
    );
    
    // Check for exact match
    $hex_upper = strtoupper($hex);
    if (isset($color_map[$hex_upper])) {
        return $color_map[$hex_upper];
    }
    
    // If no exact match, try to find closest color by name similarity
    // or just return the hex with # prefix
    return '#' . $hex;
}


/**
 * Try to retrieve product colors from attributes or post meta.
 * Adjust to match how you store product colors (attribute slug, meta key).
 */
function aakaari_get_product_colors( $product ) {
    $colors = array();

    // Example 1: from attribute pa_color (WooCommerce attribute)
    if ( $product->is_type( 'variable' ) || $product->get_attribute( 'pa_color' ) ) {
        $attr = $product->get_attribute( 'pa_color' ); // comma separated values
        if ( $attr ) {
            $terms = array_map( 'trim', explode( ',', $attr ) );
            foreach ( $terms as $t ) {
                $colors[] = array(
                    'name'  => $t,
                    'color' => '', // optionally map term -> hex in a lookup
                    'image' => '', // optional override image per color
                );
            }
        }
    }

    // If none found, fallback to a default single color (white)
    if ( empty( $colors ) ) {
        $colors[] = array( 
            'name' => 'Default', 
            'color' => '#FFFFFF', 
            'image' => wp_get_attachment_image_url( $product->get_image_id(), 'full' ) ?: wc_placeholder_img_src('full')
        );
    }

    return $colors;
}

/**
 * Build product sides and print area(s).
 * Customize this to read post meta if you store actual print area rectangles.
 */
function aakaari_get_product_sides( $product ) {
    // Example: simple one-side product with one print area
    $sides = array();

    // IMPROVED: Get the image for this side
    $image_url = wp_get_attachment_image_url($product->get_image_id(), 'full') ?: wc_placeholder_img_src('full');

    $sides[] = array(
        'id' => 'side_front',
        'name' => 'Front',
        'imageUrl' => $image_url, // ADDED: Important for canvas display
        'printAreas' => array(
            array(
                'id' => 'front_main',
                'name' => 'Main Print Area', // ADDED: Name for the print area
                'x' => 60, 'y' => 60,
                'width' => 380, 'height' => 420
            )
        ),
        'restrictionAreas' => array()
    );

    // Try to get product gallery images for additional sides
    $gallery_ids = $product->get_gallery_image_ids();
    if (!empty($gallery_ids)) {
        $side_names = array('Back', 'Left', 'Right', 'Top', 'Bottom');
        $side_count = 0;
        
        foreach ($gallery_ids as $gallery_id) {
            $side_count++;
            $side_name = isset($side_names[$side_count-1]) ? $side_names[$side_count-1] : 'Side ' . $side_count;
            $gallery_url = wp_get_attachment_image_url($gallery_id, 'full');
            
            if ($gallery_url) {
                $sides[] = array(
                    'id' => 'side_' . $side_count,
                    'name' => $side_name,
                    'imageUrl' => $gallery_url,
                    'printAreas' => array(
                        array(
                            'id' => 'area_' . $side_count,
                            'name' => $side_name . ' Print Area',
                            'x' => 60, 'y' => 60,
                            'width' => 380, 'height' => 420
                        )
                    ),
                    'restrictionAreas' => array()
                );
            }
        }
    }

    return $sides;
}


/**
 * AJAX endpoint to add customized product to cart.
 *
 * Expects:
 *  - security (nonce)
 *  - product_id (int)
 *  - designs (JSON string): the serialized design state from the front-end
 *  - files[] (optional file uploads) — supports standard file upload via FormData
 *
 * Returns { success: true, cart_item_key: '...' } on success, or WP_Error response on failure.
 */
/**
 * Validate design boundaries against print areas
 *
 * @param int $product_id Product ID
 * @param array $designs Array of design data
 * @return bool|WP_Error True on success, WP_Error on validation failure
 */
function aakaari_validate_design_boundaries($product_id, $designs) {
    // Get product print studio data
    $studio_data = get_post_meta($product_id, '_aakaari_print_studio_data', true);

    if (empty($studio_data) || !is_array($studio_data)) {
        return true; // No validation needed if no studio data
    }

    // Get print areas from studio data
    $sides = isset($studio_data['sides']) ? $studio_data['sides'] : array();

    if (empty($sides)) {
        return true; // No validation needed if no sides defined
    }

    // Handle both old structure (side/designs) and new structure (flat array with sideIndex)
    // First check if it's the new flat structure
    $is_flat_structure = false;
    if (!empty($designs) && isset($designs[0]) && isset($designs[0]['sideIndex'])) {
        $is_flat_structure = true;
    }

    // Validate each design
    foreach ($designs as $design) {
        // Handle new flat structure (sideIndex directly on design)
        if ($is_flat_structure) {
            if (!isset($design['sideIndex']) || !isset($design['x']) || !isset($design['y']) || 
                !isset($design['width']) || !isset($design['height'])) {
                continue;
            }

            $side_index = intval($design['sideIndex']);
            
            // Find the corresponding side by index
            $side_data = null;
            if (isset($sides[$side_index])) {
                $side_data = $sides[$side_index];
            }

            if (!$side_data || empty($side_data['printAreas'])) {
                continue; // No validation if side or print areas not found
            }

            // Get the first print area
            $print_area = $side_data['printAreas'][0];

            if (empty($print_area)) {
                continue;
            }

            // Extract print area boundaries
            $pa_x = floatval($print_area['x']);
            $pa_y = floatval($print_area['y']);
            $pa_width = floatval($print_area['width']);
            $pa_height = floatval($print_area['height']);

            // Validate this design element
            // IMPORTANT: Frontend sends center-based coordinates (x, y = center of design)
            // Backend validation expects top-left coordinates, so we need to convert
            $elem_center_x = floatval($design['x']);
            $elem_center_y = floatval($design['y']);
            $elem_width = floatval($design['width']);
            $elem_height = floatval($design['height']);
            
            // Convert center coordinates to top-left coordinates
            $elem_x = $elem_center_x - ($elem_width / 2);
            $elem_y = $elem_center_y - ($elem_height / 2);

            // Check if element is completely within print area
            // Add small tolerance (0.5px) for floating point precision errors
            $tolerance = 0.5;
            $elem_right = $elem_x + $elem_width;
            $elem_bottom = $elem_y + $elem_height;
            $pa_right = $pa_x + $pa_width;
            $pa_bottom = $pa_y + $pa_height;

            if ($elem_x < ($pa_x - $tolerance) || 
                $elem_y < ($pa_y - $tolerance) ||
                $elem_right > ($pa_right + $tolerance) || 
                $elem_bottom > ($pa_bottom + $tolerance)) {
                return new WP_Error(
                    'design_out_of_bounds',
                    __('Your design extends outside the allowed print area. Please adjust your design to fit within the boundaries before adding to cart.', 'aakaari')
                );
            }
            continue; // Processed this design, continue to next
        }

        // Handle old structure (side/designs nested)
        if (!isset($design['side']) || !isset($design['designs'])) {
            continue;
        }

        $side_id = $design['side'];

        // Find the corresponding side
        $side_data = null;
        foreach ($sides as $side) {
            if (isset($side['id']) && $side['id'] === $side_id) {
                $side_data = $side;
                break;
            }
        }

        if (!$side_data || empty($side_data['printAreas'])) {
            continue; // No validation if side or print areas not found
        }

        // Get the first print area (assuming one main print area)
        $print_area = $side_data['printAreas'][0];

        if (empty($print_area)) {
            continue;
        }

        // Extract print area boundaries
        $pa_x = floatval($print_area['x']);
        $pa_y = floatval($print_area['y']);
        $pa_width = floatval($print_area['width']);
        $pa_height = floatval($print_area['height']);

        // Validate each design element
        foreach ($design['designs'] as $element) {
            if (!isset($element['x']) || !isset($element['y']) ||
                !isset($element['width']) || !isset($element['height'])) {
                continue;
            }

            // IMPORTANT: Frontend sends center-based coordinates (x, y = center of design)
            // Backend validation expects top-left coordinates, so we need to convert
            $elem_center_x = floatval($element['x']);
            $elem_center_y = floatval($element['y']);
            $elem_width = floatval($element['width']);
            $elem_height = floatval($element['height']);
            
            // Convert center coordinates to top-left coordinates
            $elem_x = $elem_center_x - ($elem_width / 2);
            $elem_y = $elem_center_y - ($elem_height / 2);

            // Check if element is completely within print area
            $tolerance = 0.5; // Add tolerance for floating point precision
            $elem_right = $elem_x + $elem_width;
            $elem_bottom = $elem_y + $elem_height;
            $pa_right = $pa_x + $pa_width;
            $pa_bottom = $pa_y + $pa_height;

            if ($elem_x < ($pa_x - $tolerance) || 
                $elem_y < ($pa_y - $tolerance) ||
                $elem_right > ($pa_right + $tolerance) || 
                $elem_bottom > ($pa_bottom + $tolerance)) {
                return new WP_Error(
                    'design_out_of_bounds',
                    __('Your design extends outside the allowed print area. Please adjust your design to fit within the boundaries before adding to cart.', 'aakaari')
                );
            }
        }
    }

    return true;
}

/**
 * Hook into WooCommerce add to cart validation to check design boundaries
 * This provides server-side protection even if JavaScript is bypassed
 */
add_filter('woocommerce_add_to_cart_validation', 'aakaari_wc_validate_add_to_cart', 10, 3);
function aakaari_wc_validate_add_to_cart($passed, $product_id, $quantity) {
    // Skip validation for AJAX requests (they handle validation separately)
    if (defined('DOING_AJAX') && DOING_AJAX && isset($_REQUEST['action']) && $_REQUEST['action'] === 'aakaari_add_to_cart') {
        return $passed;
    }
    
    // Only validate if there are custom designs in the request
    if (isset($_POST['aakaari_designs']) || isset($_REQUEST['designs'])) {
        $designs_raw = isset($_POST['aakaari_designs']) ? $_POST['aakaari_designs'] : (isset($_REQUEST['designs']) ? $_REQUEST['designs'] : '');

        if (!empty($designs_raw)) {
            // Handle both JSON string and already-decoded array
            if (is_string($designs_raw)) {
                $designs = json_decode(stripslashes($designs_raw), true);
            } else {
                $designs = $designs_raw;
            }

            if (!empty($designs) && is_array($designs)) {
                $validation_result = aakaari_validate_design_boundaries($product_id, $designs);

                if (is_wp_error($validation_result)) {
                    wc_add_notice($validation_result->get_error_message(), 'error');
                    return false;
                }
            }
        }
    }

    return $passed;
}

function aakaari_ajax_add_to_cart() {
    // Validate nonce - check both POST and REQUEST for FormData compatibility
    $nonce = '';
    if (isset($_POST['security'])) {
        $nonce = sanitize_text_field(wp_unslash($_POST['security']));
    } elseif (isset($_REQUEST['security'])) {
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['security']));
    }
    
    if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'aakaari_customizer' ) ) {
        wp_send_json_error( array( 'message' => 'Invalid security token' ), 403 );
    }

    // Validate product id - check both POST and REQUEST for FormData compatibility
    $product_id = 0;
    if (isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);
    } elseif (isset($_REQUEST['product_id'])) {
        $product_id = intval($_REQUEST['product_id']);
    }
    if ( ! $product_id ) {
        wp_send_json_error( array( 'message' => 'Invalid product id' ), 400 );
    }

    // Get product and check purchasable
    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        wp_send_json_error( array( 'message' => 'Product not found' ), 404 );
    }

    // Parse designs (JSON) - check both POST and REQUEST for FormData compatibility
    $designs_raw = '';
    if (isset($_POST['designs'])) {
        $designs_raw = wp_unslash($_POST['designs']);
    } elseif (isset($_REQUEST['designs'])) {
        $designs_raw = wp_unslash($_REQUEST['designs']);
    } else {
        $designs_raw = '[]'; // Default to an empty JSON array
    }
    $designs = array();
    // Always try to decode - empty string or '[]' should decode to empty array
    if ( !empty($designs_raw) && $designs_raw !== '[]' ) {
        $decoded = json_decode( $designs_raw, true );
        if ( json_last_error() === JSON_ERROR_NONE ) {
            $designs = $decoded;
        } else {
            wp_send_json_error( array( 'message' => 'Invalid designs payload: ' . json_last_error_msg() ), 400 );
        }
    } else {
        // Empty array is valid - means no designs (plain product)
        $designs = array();
    }

    // Validate design boundaries against print areas (only if designs exist)
    if (!empty($designs) && is_array($designs)) {
        $validation_result = aakaari_validate_design_boundaries($product_id, $designs);
        if (is_wp_error($validation_result)) {
            wp_send_json_error(array('message' => $validation_result->get_error_message()), 400);
        }
    }

    // Extract attachment IDs from designs array
    // Images are uploaded immediately when user selects them, so attachment IDs are already available
    $attached_image_ids = array();
    if (!empty($designs) && is_array($designs)) {
        foreach ($designs as $design) {
            // Check if this is an image design with attachmentId
            if (isset($design['type']) && $design['type'] === 'image' && !empty($design['attachmentId'])) {
                $attachment_id = intval($design['attachmentId']);
                if ($attachment_id > 0 && !in_array($attachment_id, $attached_image_ids)) {
                    $attached_image_ids[] = $attachment_id;
                }
            }
        }
    }

    // Get original attachment ID (first uploaded file)
    // IMPORTANT: If no files in $_FILES but we have designs with images, use the attachment ID from designs
    $original_attachment_id = !empty($attached_image_ids) ? intval($attached_image_ids[0]) : 0;

    // Handle preview image (data URL base64) -> save as attachment and get ID
    $preview_attachment_id = 0;
    if ( ! empty( $_REQUEST['preview_image'] ) ) {
        $data_url = $_REQUEST['preview_image'];
        if ( is_string( $data_url ) && strpos( $data_url, 'data:image/png;base64,' ) === 0 ) {
            $preview_attachment_id = aakaari_store_data_url_image( $data_url, 'aakaari-custom-preview-' . time() . '.png' );
        }
    }

    // Handle combined mockup (preview_image is actually the combined mockup)
    // This is the product with design composited
    $combined_attachment_id = $preview_attachment_id; // Same as preview for now
    
    // Also handle print area image separately (for reference, but combined is the main one)
    $print_area_attachment_id = 0;
    if ( ! empty( $_REQUEST['print_area_image'] ) ) {
        $data_url = $_REQUEST['print_area_image'];
        if ( is_string( $data_url ) && strpos( $data_url, 'data:image/png;base64,' ) === 0 ) {
            $print_area_attachment_id = aakaari_store_data_url_image( $data_url, 'aakaari-print-area-' . time() . '.png' );
        }
    }

    // Get selected customization options (fabric, size, color, print type)
    $selected_fabric = '';
    $selected_size = '';
    $selected_color = '';
    $selected_print_type = '';
    
    if (isset($_POST['selected_fabric'])) {
        $selected_fabric = sanitize_text_field(wp_unslash($_POST['selected_fabric']));
    } elseif (isset($_REQUEST['selected_fabric'])) {
        $selected_fabric = sanitize_text_field(wp_unslash($_REQUEST['selected_fabric']));
    }
    
    if (isset($_POST['selected_size'])) {
        $selected_size = sanitize_text_field(wp_unslash($_POST['selected_size']));
    } elseif (isset($_REQUEST['selected_size'])) {
        $selected_size = sanitize_text_field(wp_unslash($_REQUEST['selected_size']));
    }
    
    if (isset($_POST['selected_color'])) {
        $selected_color = sanitize_text_field(wp_unslash($_POST['selected_color']));
    } elseif (isset($_REQUEST['selected_color'])) {
        $selected_color = sanitize_text_field(wp_unslash($_REQUEST['selected_color']));
    }
    
    // Also check for print type in designs if not sent separately
    if (!empty($designs) && is_array($designs) && !empty($designs[0]['printType'])) {
        $selected_print_type = sanitize_text_field($designs[0]['printType']);
    }

    // Now add to cart with structured custom_design data containing all three attachment IDs
    $cart_item_data = array(
        'aakaari_designs' => $designs,
        'aakaari_timestamp' => time(),
    );
    
    // Save selected customization options
    if (!empty($selected_fabric)) {
        $cart_item_data['aakaari_selected_fabric'] = $selected_fabric;
    }
    if (!empty($selected_size)) {
        $cart_item_data['aakaari_selected_size'] = $selected_size;
    }
    if (!empty($selected_color)) {
        $cart_item_data['aakaari_selected_color'] = $selected_color;
    }
    if (!empty($selected_print_type)) {
        $cart_item_data['aakaari_selected_print_type'] = $selected_print_type;
    }
    
    // Save structured custom_design with three separate attachment IDs
    $custom_design = array();
    
    if ($original_attachment_id > 0) {
        $custom_design['original'] = $original_attachment_id;
    }
    
    if ($preview_attachment_id > 0) {
        $custom_design['preview'] = $preview_attachment_id;
    }
    
    if ($combined_attachment_id > 0) {
        $custom_design['combined'] = $combined_attachment_id;
    }
    
    // Store the structured custom_design data
    if (!empty($custom_design)) {
        $cart_item_data['custom_design'] = $custom_design;
    }
    
    // Also keep legacy fields for backward compatibility
    if (!empty($attached_image_ids)) {
        $cart_item_data['aakaari_attachments'] = $attached_image_ids;
    }

    $quantity = 1;
    
    // Check for WooCommerce notices/errors that might block add to cart
    $notices = wc_get_notices();
    if (!empty($notices)) {
        // Clear notices to avoid interference
        wc_clear_notices();
    }
    
    // Temporarily disable validation filter for AJAX requests to avoid interference
    $cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, 0, array(), $cart_item_data );

    if ( ! $cart_item_key ) {
        // Get WooCommerce notices to provide better error message
        $notices = wc_get_notices('error');
        $error_message = 'Could not add to cart';
        if (!empty($notices)) {
            $error_message .= ': ' . wp_strip_all_tags($notices[0]['notice']);
        }
        
        wp_send_json_error( array( 'message' => $error_message ), 500 );
    }

    // Return success with cart item key
    wp_send_json_success( array(
        'message' => 'Added to cart',
        'cart_item_key' => $cart_item_key,
        'attached_image_ids' => $attached_image_ids,
        'redirect' => wc_get_cart_url(),
    ) );
}
add_action( 'wp_ajax_aakaari_add_to_cart', 'aakaari_ajax_add_to_cart' );
add_action( 'wp_ajax_nopriv_aakaari_add_to_cart', 'aakaari_ajax_add_to_cart' );

/**
 * AJAX handler for immediate image upload
 * Uploads design image to media library as soon as user selects it
 * Returns attachment ID and URL to store with design
 */
function aakaari_ajax_upload_design_image() {
    // Validate nonce
    $nonce = '';
    if (isset($_POST['security'])) {
        $nonce = sanitize_text_field(wp_unslash($_POST['security']));
    } elseif (isset($_REQUEST['security'])) {
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['security']));
    }

    if (empty($nonce) || !wp_verify_nonce($nonce, 'aakaari_customizer')) {
        wp_send_json_error(array('message' => 'Invalid security token'), 403);
    }

    // Check if file was uploaded
    if (empty($_FILES['file'])) {
        wp_send_json_error(array('message' => 'No file provided'), 400);
    }

    $file = $_FILES['file'];

    // Validate file type
    $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
    if (!in_array($file['type'], $allowed_types)) {
        wp_send_json_error(array('message' => 'Invalid file type. Only images are allowed.'), 400);
    }

    // Upload file to media library
    $attachment_id = aakaari_handle_upload_and_attach($file);

    if (!$attachment_id) {
        wp_send_json_error(array('message' => 'Failed to upload image'), 500);
    }

    // Get attachment URL
    $attachment_url = wp_get_attachment_url($attachment_id);

    if (!$attachment_url) {
        wp_send_json_error(array('message' => 'Failed to get image URL'), 500);
    }

    // Return success with attachment data
    wp_send_json_success(array(
        'attachment_id' => $attachment_id,
        'url' => $attachment_url,
        'message' => 'Image uploaded successfully'
    ));
}
add_action('wp_ajax_aakaari_upload_design_image', 'aakaari_ajax_upload_design_image');
add_action('wp_ajax_nopriv_aakaari_upload_design_image', 'aakaari_ajax_upload_design_image');

/**
 * Handle uploaded file and insert into Media Library.
 * Returns attachment ID on success or false on failure.
 *
 * Expects $file to be a standard $_FILES entry for one file (not the array of multiple).
 */
function aakaari_handle_upload_and_attach( $file ) {
    if ( empty( $file ) || ! isset( $file['tmp_name'] ) ) {
        return false;
    }

    // Move the uploaded file into WordPress uploads folder
    $overrides = array( 'test_form' => false );
    $upload = wp_handle_upload( $file, $overrides );

    if ( isset( $upload['error'] ) ) {
        return false;
    }

    $file_path = $upload['file']; // full path
    $file_url  = $upload['url'];
    $file_type = wp_check_filetype( basename( $file_path ), null );

    $attachment = array(
        'post_mime_type' => $file_type['type'],
        'post_title'     => sanitize_file_name( basename( $file_path ) ),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );

    $attach_id = wp_insert_attachment( $attachment, $file_path );
    if ( ! is_wp_error( $attach_id ) ) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
        wp_update_attachment_metadata( $attach_id, $attach_data );

        return $attach_id;
    }

    return false;
}

/**
 * Store a base64 data URL image into uploads and return attachment ID.
 * Returns 0 on failure, attachment ID on success.
 */
function aakaari_store_data_url_image( $data_url, $filename = 'aakaari-preview.png' ) {
    // Validate data URL
    if ( ! is_string( $data_url ) ) {
        return 0;
    }
    if ( strpos( $data_url, 'data:image/' ) !== 0 ) {
        return 0;
    }

    // Split header and data
    if ( strpos( $data_url, ',' ) === false ) {
        return 0;
    }
    list( $meta, $base64 ) = explode( ',', $data_url, 2 );

    // Determine mime and extension
    $mime = 'image/png';
    if ( preg_match( '/data:(image\/[a-zA-Z0-9+.-]+);base64/', $meta, $m ) ) {
        $mime = $m[1];
    }
    $ext = 'png';
    if ( $mime === 'image/jpeg' ) { $ext = 'jpg'; }
    elseif ( $mime === 'image/webp' ) { $ext = 'webp'; }

    // Decode base64
    $binary = base64_decode( $base64 );
    if ( $binary === false ) {
        return 0;
    }

    // Prepare uploads path
    $uploads = wp_upload_dir();
    if ( ! empty( $uploads['error'] ) ) {
        return 0;
    }

    // Ensure unique filename
    $safe_name = wp_unique_filename( $uploads['path'], preg_replace('/\.[^.]+$/', '', $filename) . '.' . $ext );
    $file_path = trailingslashit( $uploads['path'] ) . $safe_name;

    // Write file
    $bytes = file_put_contents( $file_path, $binary );
    if ( $bytes === false ) {
        return 0;
    }

    // Create attachment in media library
    $filetype = wp_check_filetype( $safe_name, null );
    $attachment = array(
        'post_mime_type' => $filetype['type'] ?: $mime,
        'post_title'     => sanitize_file_name( preg_replace('/\.[^.]+$/', '', $safe_name) ),
        'post_content'   => '',
        'post_status'    => 'inherit',
    );

    $attach_id = wp_insert_attachment( $attachment, $file_path );
    if ( is_wp_error( $attach_id ) ) {
        return 0;
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
    wp_update_attachment_metadata( $attach_id, $attach_data );

    // Return attachment ID (not URL)
    return intval( $attach_id );
}

// ADDED: Display customization data in cart and order
add_filter( 'woocommerce_get_item_data', 'aakaari_display_customization_cart_item_data', 10, 2 );
function aakaari_display_customization_cart_item_data( $item_data, $cart_item ) {
    if ( isset( $cart_item['aakaari_designs'] ) && is_array($cart_item['aakaari_designs']) ) {
        $design_count = count( $cart_item['aakaari_designs'] );

        $item_data[] = array(
            'key'     => __( 'Customized', 'aakaari' ),
            'value'   => sprintf( _n( '%d custom design', '%d custom designs', $design_count, 'aakaari' ), $design_count ),
            'display' => '',
        );

        // Add print type if available
        if (!empty($cart_item['aakaari_designs'][0]['printType'])) {
            $item_data[] = array(
                'key'     => __( 'Print Method', 'aakaari' ),
                'value'   => ucfirst(str_replace('_', ' ', $cart_item['aakaari_designs'][0]['printType'])),
                'display' => '',
            );
        }

        // Add selected color if available
        if (!empty($cart_item['aakaari_designs'][0]['color'])) {
            $item_data[] = array(
                'key'     => __( 'Color', 'aakaari' ),
                'value'   => ucfirst($cart_item['aakaari_designs'][0]['color']),
                'display' => '',
            );
        }

        // Add selected side if available
        if (!empty($cart_item['aakaari_designs'][0]['side'])) {
            $item_data[] = array(
                'key'     => __( 'Side', 'aakaari' ),
                'value'   => ucfirst($cart_item['aakaari_designs'][0]['side']),
                'display' => '',
            );
        }
    }
    return $item_data;
}

// Display customization preview image in cart
add_filter( 'woocommerce_cart_item_thumbnail', 'aakaari_cart_item_customization_thumbnail', 10, 3 );
function aakaari_cart_item_customization_thumbnail( $thumbnail, $cart_item, $cart_item_key ) {
    // Check if this item has customization
    if ( isset( $cart_item['aakaari_designs'] ) && !empty($cart_item['aakaari_designs']) ) {
        // Check if we have a preview image URL
        if ( !empty($cart_item['aakaari_preview_image']) ) {
            $thumbnail = '<img src="' . esc_url($cart_item['aakaari_preview_image']) . '" alt="Customized Product" class="attachment-woocommerce_thumbnail" />';
        }
        // Check if we have attachment IDs
        elseif ( isset($cart_item['aakaari_attachments']) && !empty($cart_item['aakaari_attachments']) ) {
            $first_attachment = reset($cart_item['aakaari_attachments']);
            $image_url = wp_get_attachment_image_url($first_attachment, 'woocommerce_thumbnail');
            if ($image_url) {
                $thumbnail = '<img src="' . esc_url($image_url) . '" alt="Customized Product" class="attachment-woocommerce_thumbnail" />';
            }
        }
    }
    return $thumbnail;
}

// Save customization data to order items
add_action( 'woocommerce_checkout_create_order_line_item', 'aakaari_save_customization_to_order', 10, 4 );
function aakaari_save_customization_to_order( $item, $cart_item_key, $values, $order ) {
    // Save designs data
    if ( isset( $values['aakaari_designs'] ) ) {
        $item->add_meta_data( '_aakaari_designs', $values['aakaari_designs'], true );
    }
    
    // Save selected customization options
    if ( ! empty( $values['aakaari_selected_fabric'] ) ) {
        $item->add_meta_data( '_aakaari_selected_fabric', $values['aakaari_selected_fabric'], true );
    }
    
    if ( ! empty( $values['aakaari_selected_size'] ) ) {
        $item->add_meta_data( '_aakaari_selected_size', $values['aakaari_selected_size'], true );
    }
    
    if ( ! empty( $values['aakaari_selected_color'] ) ) {
        $item->add_meta_data( '_aakaari_selected_color', $values['aakaari_selected_color'], true );
    }
    
    if ( ! empty( $values['aakaari_selected_print_type'] ) ) {
        $item->add_meta_data( '_aakaari_selected_print_type', $values['aakaari_selected_print_type'], true );
    }
    
    // NEW: Save structured custom_design with three separate attachment IDs
    if ( ! empty( $values['custom_design'] ) && is_array( $values['custom_design'] ) ) {
        $design = $values['custom_design'];
        
        // Save original attachment ID
        if ( ! empty( $design['original'] ) ) {
            $original_id = intval( $design['original'] );
            if ( $original_id > 0 ) {
                $item->add_meta_data( '_aakaari_original_attachment', $original_id, true );
            }
        }
        
        // Save preview attachment ID
        if ( ! empty( $design['preview'] ) ) {
            $preview_id = intval( $design['preview'] );
            if ( $preview_id > 0 ) {
                $item->add_meta_data( '_aakaari_preview_attachment', $preview_id, true );
            }
        }
        
        // Save combined mockup attachment ID
        if ( ! empty( $design['combined'] ) ) {
            $combined_id = intval( $design['combined'] );
            if ( $combined_id > 0 ) {
                $item->add_meta_data( '_aakaari_combined_attachment', $combined_id, true );
            }
        }
    }
    
    // Legacy support: Also save old format for backward compatibility
    if ( isset( $values['aakaari_attachments'] ) ) {
        $item->add_meta_data( '_aakaari_attachments', $values['aakaari_attachments'], true );
    }
    if ( isset( $values['aakaari_original_image_urls'] ) ) {
        $item->add_meta_data( '_aakaari_original_image_urls', $values['aakaari_original_image_urls'], true );
    }
    if ( isset( $values['aakaari_preview_image'] ) ) {
        $item->add_meta_data( '_aakaari_preview_image', $values['aakaari_preview_image'], true );
    }
    if ( isset( $values['aakaari_print_area_image'] ) ) {
        $item->add_meta_data( '_aakaari_print_area_image', $values['aakaari_print_area_image'], true );
    }
}

// Display customization in order admin
add_filter( 'woocommerce_order_item_display_meta_key', 'aakaari_order_item_meta_key_display', 10, 3 );
function aakaari_order_item_meta_key_display( $display_key, $meta, $item ) {
    if ( $meta->key === '_aakaari_designs' ) {
        return __( 'Customization Details', 'aakaari' );
    }
    if ( $meta->key === '_aakaari_preview_image' ) {
        return __( 'Custom Design Preview', 'aakaari' );
    }
    if ( $meta->key === '_aakaari_attachments' ) {
        return __( 'Uploaded Design Files', 'aakaari' );
    }
    if ( $meta->key === '_aakaari_print_area_image' ) {
        return __( 'Print Area PNG', 'aakaari' );
    }
    return $display_key;
}

// Format customization data display in orders
add_filter( 'woocommerce_order_item_display_meta_value', 'aakaari_order_item_meta_value_display', 10, 3 );
function aakaari_order_item_meta_value_display( $display_value, $meta, $item ) {
    if ( $meta->key === '_aakaari_designs' ) {
        $designs = maybe_unserialize( $meta->value );
        if ( is_array($designs) && !empty($designs) ) {
            $output = '<div class="aakaari-design-details">';
            $output .= '<p>' . sprintf( _n( '%d custom design', '%d custom designs', count($designs), 'aakaari' ), count($designs) ) . '</p>';

            // Show print type, color, and side if available
            if (!empty($designs[0]['printType'])) {
                $output .= '<p><strong>Print Type:</strong> ' . esc_html(ucfirst(str_replace('_', ' ', $designs[0]['printType']))) . '</p>';
            }
            if (!empty($designs[0]['color'])) {
                $output .= '<p><strong>Color:</strong> ' . esc_html($designs[0]['color']) . '</p>';
            }
            if (!empty($designs[0]['side'])) {
                $output .= '<p><strong>Side:</strong> ' . esc_html(ucfirst($designs[0]['side'])) . '</p>';
            }

            $output .= '</div>';
            return $output;
        }
    }
    if ( $meta->key === '_aakaari_preview_image' ) {
        $image_html = '<div class="aakaari-preview-image">';
        $image_html .= '<a href="' . esc_url($meta->value) . '" target="_blank">';
        $image_html .= '<img src="' . esc_url($meta->value) . '" style="max-width:150px; height:auto; border:1px solid #ddd; border-radius:4px;" alt="Custom Design" />';
        $image_html .= '</a>';
        $image_html .= '<p><a href="' . esc_url($meta->value) . '" download class="button" style="margin-top:10px;">Download Design</a></p>';
        $image_html .= '</div>';
        return $image_html;
    }
    if ( $meta->key === '_aakaari_print_area_image' ) {
        $image_html = '<div class="aakaari-print-area-image">';
        $image_html .= '<a href="' . esc_url($meta->value) . '" target="_blank">';
        $image_html .= '<img src="' . esc_url($meta->value) . '" style="max-width:150px; height:auto; border:1px solid #ddd; border-radius:4px;" alt="Print Area" />';
        $image_html .= '</a>';
        $image_html .= '<p><a href="' . esc_url($meta->value) . '" download class="button" style="margin-top:10px;">Download PNG</a></p>';
        $image_html .= '</div>';
        return $image_html;
    }
    if ( $meta->key === '_aakaari_attachments' ) {
        $attachments = maybe_unserialize( $meta->value );
        if ( is_array($attachments) && !empty($attachments) ) {
            $output = '<div class="aakaari-attachments">';
            foreach ($attachments as $attachment_id) {
                $attachment_url = wp_get_attachment_url($attachment_id);
                $attachment_thumb = wp_get_attachment_image_url($attachment_id, 'thumbnail');
                if ($attachment_url) {
                    $output .= '<div style="display:inline-block; margin:5px;">';
                    if ($attachment_thumb) {
                        $output .= '<a href="' . esc_url($attachment_url) . '" target="_blank">';
                        $output .= '<img src="' . esc_url($attachment_thumb) . '" style="max-width:80px; height:auto; border:1px solid #ddd; border-radius:4px;" />';
                        $output .= '</a><br>';
                    }
                    $output .= '<a href="' . esc_url($attachment_url) . '" download class="button" style="font-size:11px; padding:3px 8px;">Download</a>';
                    $output .= '</div>';
                }
            }
            $output .= '</div>';
            return $output;
        }
    }
    return $display_value;
}