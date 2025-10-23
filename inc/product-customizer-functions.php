<?php
/**
 * Enqueue assets and localize data for the custom Shop Page (archive-product.php).
 * FIXED: Added proper print type data loading and thumbnail handling
 */
add_action( 'wp_enqueue_scripts', 'aakaari_enqueue_shop_assets', 30 ); // Use priority 30 to run after customizer check
function aakaari_enqueue_shop_assets() {

    // Only run on WooCommerce shop, category, or tag archives
    if ( ! ( is_shop() || is_product_category() || is_product_tag() ) ) {
        return;
    }

    // --- Define Asset Paths ---
    $js_folder  = '/assets/js/';
    $css_folder = '/assets/css/';
    $js_dir  = get_template_directory_uri() . $js_folder;
    $js_path = get_template_directory() . $js_folder;
    $css_dir = get_template_directory_uri() . $css_folder;
    $css_path = get_template_directory() . $css_folder;

    // --- Enqueue Shop CSS ---
    $shop_css_file = 'shop-style.css';
    if ( file_exists( $css_path . $shop_css_file ) ) {
        wp_enqueue_style(
            'aakaari-shop-style', // Unique handle
            $css_dir . $shop_css_file,
            array(), // Dependencies
            filemtime( $css_path . $shop_css_file )
        );
    }

    // --- Enqueue Lucide Icons (if not already loaded by customizer or theme) ---
     if (!wp_script_is('lucide-icons', 'enqueued')) {
        wp_enqueue_script('lucide-icons','https://unpkg.com/lucide@latest', array(), null, true);
    }

    // --- Enqueue Shop JS ---
    $shop_js_file = 'shop-logic.js';
    if ( file_exists( $js_path . $shop_js_file ) ) {
        wp_enqueue_script(
            'aakaari-shop-logic', // Unique handle
            $js_dir . $shop_js_file,
            array('jquery', 'lucide-icons'), // Dependencies
            filemtime( $js_path . $shop_js_file ),
            true // Load in footer
        );

        // --- Prepare Data for JS ---
        // 1. Get All Product Categories
        $categories_data = array();
        $product_categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) ); // Hide empty cats for filter
        if ( ! is_wp_error( $product_categories ) ) {
            foreach ( $product_categories as $term ) {
                $categories_data[] = array( 'slug' => $term->slug, 'name' => $term->name );
            }
        }

        // 2. Get All Products (relevant data only)
        $products_data = array();
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1, // Get all products
            'post_status'    => 'publish', // Only show published products
            'fields'         => 'ids', // Get only IDs for efficiency
        );
        $product_ids = get_posts( $args );

        foreach ( $product_ids as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( ! $product || ! $product->is_visible() ) { // Skip invalid or hidden products
                continue; 
            }
            
            // IMPROVED: Check if this is a print studio product
            $is_print_studio_product = metadata_exists('post', $product_id, '_aakaari_print_studio_data');
            
            // FIXED: Get print studio data if available
            $studio_data = array(
                'printTypes' => array(),
                'sides' => array()
            );
            
            if ($is_print_studio_product) {
                $saved_studio_data = get_post_meta($product_id, '_aakaari_print_studio_data', true);
                if (is_array($saved_studio_data)) {
                    $studio_data = $saved_studio_data;
                }
            }
            
            // IMPROVED: Get the image with better error handling
            $thumbnail_id = $product->get_image_id();
            $thumbnail_url = '';
            
            if ($thumbnail_id) {
                $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'woocommerce_thumbnail');
            } 
            
            // If no product image, check if we have a side image from print studio
            if (empty($thumbnail_url) && !empty($studio_data['sides']) && is_array($studio_data['sides'])) {
                // Use the first side's image if available
                foreach ($studio_data['sides'] as $side) {
                    if (!empty($side['imageUrl'])) {
                        $thumbnail_url = $side['imageUrl'];
                        break;
                    }
                }
            }
            
            // If still no image, use placeholder
            if (empty($thumbnail_url)) {
                $thumbnail_url = wc_placeholder_img_src();
            }
            
            // Get category slugs for filtering
            $term_slugs = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
            $first_category_slug = !is_wp_error($term_slugs) && !empty($term_slugs) ? $term_slugs[0] : '';
            
            // Get print types if available
            $print_types = array();
            if (!empty($studio_data['printTypes'])) {
                $print_types = $studio_data['printTypes'];
            }

            // Build the product data array with all needed info
            $products_data[] = array(
                'id'            => 'prod_' . $product_id, // Consistent ID for JS
                'wp_id'         => $product_id, // Actual WordPress ID
                'name'          => $product->get_name(),
                'description'   => $product->get_short_description() ?: wp_trim_words($product->get_description(), 20),
                'basePrice'     => (float) $product->get_regular_price('edit'), // Raw regular price
                'salePrice'     => $product->is_on_sale('edit') ? (float) $product->get_sale_price('edit') : null, // Raw sale price
                'displayPrice'  => (float) $product->get_price('edit'), // Current active price
                'category'      => $first_category_slug, // Use slug for filtering
                'thumbnail'     => $thumbnail_url, // FIXED: Get thumbnail with fallback
                'permalink'     => $product->get_permalink(), // Link to the product page
                'isCustomizable'=> $is_print_studio_product, // FIXED: Flag based on meta existence
                'printTypes'    => $print_types, // ADDED: Available print types
                'sidesCount'    => count($studio_data['sides']), // ADDED: Number of sides
                'isPrintStudio' => $is_print_studio_product, // Explicit flag
            );
        }

        // --- Pass Data to JavaScript ---
        wp_localize_script(
            'aakaari-shop-logic',       // Handle of the script to attach data to
            'AakaariShopData',          // Object name in JavaScript (window.AakaariShopData)
            array(
                'ajax_url'   => admin_url( 'admin-ajax.php' ), // For potential future AJAX actions
                'nonce'      => wp_create_nonce( 'aakaari_shop_nonce' ),
                'products'   => $products_data,    // Array of all products
                'categories' => $categories_data,  // Array of categories
                'defaultImage' => wc_placeholder_img_src(), // ADDED: Default image URL
            )
        );

    } else {
         error_log('Aakaari Theme Error: assets/js/shop-logic.js not found.');
    }
}

// Remove WooCommerce breadcrumbs to keep the clean design
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
?>