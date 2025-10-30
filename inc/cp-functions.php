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
    $js_path  = get_stylesheet_directory_uri() . '/assets/js/product-customizer.js';

    wp_enqueue_style( 'aakaari-product-customizer', $css_path, array(), '1.0.1' );
    wp_enqueue_script( 'lucide-icons', 'https://unpkg.com/lucide@latest', array(), null, true );
    wp_enqueue_script( 'aakaari-product-customizer', $js_path, array( 'jquery', 'lucide-icons' ), '1.0.1', true );

    // Enqueue enhancements
    $enhancements_path = get_stylesheet_directory_uri() . '/assets/js/product-customizer-enhancements.js';
    wp_enqueue_script( 'aakaari-product-customizer-enhancements', $enhancements_path, array( 'jquery', 'aakaari-product-customizer' ), '1.0.0', true );

    global $post, $product;
    if ( empty( $product ) || ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
        $product = wc_get_product( isset( $post ) ? $post->ID : get_the_ID() );
    }

    if ( ! $product ) {
        // Localize minimal empty structures so front-end never errors
        wp_localize_script( 'aakaari-product-customizer', 'AAKAARI_PRODUCTS', array() );
        wp_localize_script( 'aakaari-product-customizer', 'AAKAARI_PRINT_TYPES', array() );
        wp_localize_script( 'aakaari-product-customizer', 'AAKAARI_SETTINGS', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'aakaari_customizer' ),
            'max_upload_size' => wp_max_upload_size(),
        ) );
        // add a quick inline logger
        wp_add_inline_script( 'aakaari-product-customizer', 'console.warn("AAKAARI_PRODUCTS localized as empty — check PHP localization.");' );
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
    
    // DEBUG: Log what we got from meta
    error_log('Print Studio Data for Product #' . $product_id . ': ' . print_r($studio_data, true));
    
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
        error_log('Converting ' . count($studio_data['colors']) . ' colors from Print Studio');
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
            error_log("  - Converted: $hex => $color_name" . ($color_image_url ? " (with image)" : ""));
        }
    }

    // Fallback if no colors configured in Print Studio
    if (empty($colors_for_customizer)) {
        error_log('No Print Studio colors found, using fallback colors');
        $colors_for_customizer = aakaari_get_product_colors($product);
    }

    error_log('Final colors for customizer: ' . print_r($colors_for_customizer, true));

    // IMPROVED: Transform Print Studio fabrics format
    $fabrics_for_customizer = array();
    if (!empty($studio_data['fabrics']) && is_array($studio_data['fabrics'])) {
        // Print Studio saves fabrics as array of IDs: ['fab_123', 'fab_456']
        // Get full fabric data from pa_fabric taxonomy
        error_log('Converting ' . count($studio_data['fabrics']) . ' fabrics from Print Studio');
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
                error_log("  - Converted fabric: $fabric_id => {$term->name} (\${$price})");
            }
        }
    }
    
    error_log('Final fabrics for customizer: ' . print_r($fabrics_for_customizer, true));

    // IMPROVED: Transform Print Studio print types format
    $print_types_for_customizer = array();
    if (!empty($studio_data['printTypes']) && is_array($studio_data['printTypes'])) {
        // Print Studio saves print types as array of IDs: ['pt_123', 'pt_456']
        // Get full print type data from pa_print_type taxonomy
        error_log('Converting ' . count($studio_data['printTypes']) . ' print types from Print Studio');
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
                error_log("  - Converted print type: $print_type_id => {$term->name} (Model: {$pricing_model}, Price: \${$price})");
            }
        }
    }
    
    // Fallback to defaults if no print types configured
    if (empty($print_types_for_customizer)) {
        error_log('No Print Studio print types found, using fallback defaults');
        $print_types_for_customizer = array(
            array('id'=>'pt_dtg','name'=>'DTG','description'=>'Per-square-inch pricing','pricingModel'=>'per-inch','price'=>0.12),
            array('id'=>'pt_vinyl','name'=>'HTV','description'=>'Fixed per-design','pricingModel'=>'fixed','price'=>5.00),
        );
    }
    
    error_log('Final print types for customizer: ' . print_r($print_types_for_customizer, true));

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

    // Add inline script to immediately log what was localized (handy for debugging)
    $inline = 'console.group("Aakaari Localization");';
    $inline .= 'console.log("AAKAARI_PRODUCTS", window.AAKAARI_PRODUCTS);';
    $inline .= 'console.log("AAKAARI_PRINT_TYPES", window.AAKAARI_PRINT_TYPES);';
    $inline .= 'console.log("AAKAARI_SETTINGS", window.AAKAARI_SETTINGS);';
    $inline .= 'console.groupEnd();';
    wp_add_inline_script( 'aakaari-product-customizer', $inline );
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

    // Validate each design
    foreach ($designs as $design) {
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

            $elem_x = floatval($element['x']);
            $elem_y = floatval($element['y']);
            $elem_width = floatval($element['width']);
            $elem_height = floatval($element['height']);

            // Check if element is completely within print area
            $elem_right = $elem_x + $elem_width;
            $elem_bottom = $elem_y + $elem_height;
            $pa_right = $pa_x + $pa_width;
            $pa_bottom = $pa_y + $pa_height;

            if ($elem_x < $pa_x || $elem_y < $pa_y ||
                $elem_right > $pa_right || $elem_bottom > $pa_bottom) {
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
    error_log('AJAX: aakaari_add_to_cart triggered.'); // Check if function runs

    // Validate nonce
    if ( empty( $_REQUEST['security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ), 'aakaari_customizer' ) ) {
        error_log('AJAX Error: Nonce verification failed.'); // Log nonce failure
        wp_send_json_error( array( 'message' => 'Invalid security token' ), 403 );
    }
    error_log('AJAX: Nonce verified.');

    // Validate product id
    $product_id = isset( $_REQUEST['product_id'] ) ? intval( $_REQUEST['product_id'] ) : 0;
    error_log('AJAX: Received product_id: ' . $product_id); // Log product ID
    if ( ! $product_id ) {
         error_log('AJAX Error: Invalid product ID.');
        wp_send_json_error( array( 'message' => 'Invalid product id' ), 400 );
    }

    // Get product and check purchasable
    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        error_log('AJAX Error: Product not found for ID: ' . $product_id);
        wp_send_json_error( array( 'message' => 'Product not found' ), 404 );
    }
    error_log('AJAX: Product found: ' . $product->get_name());

    // Parse designs (JSON)
    $designs_raw = isset( $_REQUEST['designs'] ) ? wp_unslash( $_REQUEST['designs'] ) : '[]'; // Default to an empty JSON array
    error_log('AJAX: Received designs JSON: ' . $designs_raw); // Log raw designs
    $designs = array();
    if ( $designs_raw ) {
        $decoded = json_decode( $designs_raw, true );
        if ( json_last_error() === JSON_ERROR_NONE ) {
            $designs = $decoded;
            error_log('AJAX: Designs decoded successfully: ' . print_r($designs, true));
        } else {
            error_log('AJAX Error: Invalid designs JSON payload. Error: ' . json_last_error_msg());
            wp_send_json_error( array( 'message' => 'Invalid designs payload' ), 400 );
        }
    } else {
        error_log('AJAX Warning: No designs payload received.'); // Log if designs are missing
    }

    // Validate design boundaries against print areas
    $validation_result = aakaari_validate_design_boundaries($product_id, $designs);
    if (is_wp_error($validation_result)) {
        error_log('AJAX Error: Design boundary validation failed: ' . $validation_result->get_error_message());
        wp_send_json_error(array('message' => $validation_result->get_error_message()), 400);
    }

    // Handle file uploads
    $attached_image_ids = array();
    if ( ! empty( $_FILES['files'] ) ) {
        $files = $_FILES['files'];
        // Re-format the $_FILES array for easier processing
        $files_reformatted = array();
        foreach ($files['name'] as $key => $name) {
            $files_reformatted[$key] = array(
                'name'     => $name,
                'type'     => $files['type'][$key],
                'tmp_name' => $files['tmp_name'][$key],
                'error'    => $files['error'][$key],
                'size'     => $files['size'][$key],
            );
        }

        foreach ( $files_reformatted as $file ) {
            $attach_id = aakaari_handle_upload_and_attach( $file );
            if ( $attach_id ) {
                $attached_image_ids[] = $attach_id;
            }
        }
    }
    error_log('AJAX: Processed file uploads (if any). Attached IDs: ' . print_r($attached_image_ids, true));


    // Now add to cart and include $designs + $attached_image_ids as cart item meta
    $cart_item_data = array(
        'aakaari_designs' => $designs,
        'aakaari_timestamp' => time(),
    );
    // Only add attachments if they exist
    if (!empty($attached_image_ids)) {
        $cart_item_data['aakaari_attachments'] = $attached_image_ids;
    }
    error_log('AJAX: Cart Item Data prepared: ' . print_r($cart_item_data, true));


    $quantity = 1;
    error_log('AJAX: Attempting to add product ID ' . $product_id . ' to cart...');
    $cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, 0, array(), $cart_item_data );

    if ( ! $cart_item_key ) {
        error_log('AJAX Error: WC()->cart->add_to_cart returned false.'); // Log add_to_cart failure
        wp_send_json_error( array( 'message' => 'Could not add to cart' ), 500 );
    }

    error_log('AJAX: Product added successfully. Cart Item Key: ' . $cart_item_key); // Log success

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
    if ( isset( $values['aakaari_designs'] ) ) {
        $item->add_meta_data( '_aakaari_designs', $values['aakaari_designs'], true );
    }
    if ( isset( $values['aakaari_attachments'] ) ) {
        $item->add_meta_data( '_aakaari_attachments', $values['aakaari_attachments'], true );
    }
    if ( isset( $values['aakaari_preview_image'] ) ) {
        $item->add_meta_data( '_aakaari_preview_image', $values['aakaari_preview_image'], true );
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