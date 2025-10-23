<?php
// Improved cp-functions.php for the product customizer

if ( ! defined( 'ABSPATH' ) ) {
    exit;
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
    $colors_for_customizer = array();
    if (!empty($studio_data['colors']) && is_array($studio_data['colors'])) {
        // Print Studio saves colors as array of hex values: ['#FF0000', '#00FF00']
        // Product customizer needs: [{name: 'Red', color: '#FF0000'}, ...]
        error_log('Converting ' . count($studio_data['colors']) . ' colors from Print Studio');
        foreach ($studio_data['colors'] as $hex) {
            $color_name = aakaari_hex_to_color_name($hex); // Convert hex back to name
            $colors_for_customizer[] = array(
                'name' => $color_name,
                'color' => $hex,
                'image' => '', // No per-color image override
            );
            error_log("  - Converted: $hex => $color_name");
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

    // Localize arrays - use the converted print types
    wp_localize_script( 'aakaari-product-customizer', 'AAKAARI_PRODUCTS', array( $product_data ) );
    wp_localize_script( 'aakaari-product-customizer', 'AAKAARI_PRINT_TYPES', $print_types_for_customizer );
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
function aakaari_ajax_add_to_cart() {
    // Validate nonce
    if ( empty( $_REQUEST['security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ), 'aakaari_customizer' ) ) {
        wp_send_json_error( array( 'message' => 'Invalid security token' ), 403 );
    }

    // Validate product id
    $product_id = isset( $_REQUEST['product_id'] ) ? intval( $_REQUEST['product_id'] ) : 0;
    if ( ! $product_id ) {
        wp_send_json_error( array( 'message' => 'Invalid product id' ), 400 );
    }

    // Get product and check purchasable
    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        wp_send_json_error( array( 'message' => 'Product not found' ), 404 );
    }

    // Parse designs (JSON)
    $designs_raw = isset( $_REQUEST['designs'] ) ? wp_unslash( $_REQUEST['designs'] ) : '';
    $designs = array();
    if ( $designs_raw ) {
        $decoded = json_decode( $designs_raw, true );
        if ( json_last_error() === JSON_ERROR_NONE ) {
            $designs = $decoded;
        } else {
            // Not valid JSON
            wp_send_json_error( array( 'message' => 'Invalid designs payload' ), 400 );
        }
    }

    // Process any uploaded files (files[]). Save to media library and store attachment IDs.
    $attached_image_ids = array();
    if ( ! empty( $_FILES ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Support files[] or single file field 'file'
        foreach ( $_FILES as $field => $fileinfo ) {
            // If multiple files in one field, handle array structure
            if ( is_array( $fileinfo['name'] ) ) {
                $file_count = count( $fileinfo['name'] );
                for ( $i = 0; $i < $file_count; $i++ ) {
                    if ( $fileinfo['error'][ $i ] !== UPLOAD_ERR_OK ) {
                        continue;
                    }
                    $file = array(
                        'name'     => $fileinfo['name'][ $i ],
                        'type'     => $fileinfo['type'][ $i ],
                        'tmp_name' => $fileinfo['tmp_name'][ $i ],
                        'error'    => $fileinfo['error'][ $i ],
                        'size'     => $fileinfo['size'][ $i ],
                    );
                    $attach_id = aakaari_handle_upload_and_attach( $file );
                    if ( $attach_id ) {
                        $attached_image_ids[] = $attach_id;
                    }
                }
            } else {
                if ( $fileinfo['error'] === UPLOAD_ERR_OK ) {
                    $attach_id = aakaari_handle_upload_and_attach( $fileinfo );
                    if ( $attach_id ) {
                        $attached_image_ids[] = $attach_id;
                    }
                }
            }
        }
    }

    // Now add to cart and include $designs + $attached_image_ids as cart item meta
    $cart_item_data = array(
        'aakaari_designs' => $designs,
        'aakaari_attachments' => $attached_image_ids,
        'aakaari_timestamp' => time(),
    );

    $quantity = 1;
    $cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, 0, array(), $cart_item_data );

    if ( ! $cart_item_key ) {
        wp_send_json_error( array( 'message' => 'Could not add to cart' ), 500 );
    }

    // Return success with cart item key
    wp_send_json_success( array(
        'message' => 'Added to cart',
        'cart_item_key' => $cart_item_key,
        'attached_image_ids' => $attached_image_ids,
        'redirect' => wc_get_cart_url(), // ADDED: Provide cart URL for redirect
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
    }
    return $item_data;
}