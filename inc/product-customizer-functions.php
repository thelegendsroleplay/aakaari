<?php
/**
 * Enqueue assets and localize data for the custom Shop Page (archive-product.php).
 */
add_action( 'wp_enqueue_scripts', 'aakaari_enqueue_shop_assets', 30 ); // Use priority 30 to run after customizer check
function aakaari_enqueue_shop_assets() {

    // Only run on WooCommerce shop, category, or tag archives
    if ( ! ( is_shop() || is_product_category() || is_product_tag() ) ) {
        return;
    }

    // --- Define Asset Paths ---
    // *** IMPORTANT: Adjust '/assets/' if your js/css folders are elsewhere ***
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
            
            // Check if customizable (using ACF field as defined in your single-product.php)
            // Or use the meta field check: $is_customizable = metadata_exists('post', $product_id, '_aakaari_print_studio_data');
            $is_customizable = function_exists('get_field') ? get_field('is_customizable', $product_id) : false;
            
            // Get category slugs for filtering
             $term_slugs = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
             $first_category_slug = !is_wp_error($term_slugs) && !empty($term_slugs) ? $term_slugs[0] : '';


            $products_data[] = array(
                'id'            => 'prod_' . $product_id, // Consistent ID for JS
                'wp_id'         => $product_id, // Actual WordPress ID
                'name'          => $product->get_name(),
                'description'   => $product->get_short_description() ?: $product->get_description(),
                'basePrice'     => (float) $product->get_regular_price('edit'), // Raw regular price
                'salePrice'     => $product->is_on_sale('edit') ? (float) $product->get_sale_price('edit') : null, // Raw sale price
                'displayPrice'  => (float) $product->get_price('edit'), // Current active price
                'category'      => $first_category_slug, // Use slug for filtering
                'thumbnail'     => get_the_post_thumbnail_url( $product_id, 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src(), // Get thumbnail or placeholder
                'permalink'     => $product->get_permalink(), // Link to the product page
                'isCustomizable'=> $is_customizable, // Flag for the button
                // Add any other simple data needed for the shop card
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
            )
        );

    } else {
         error_log('Aakaari Theme Error: assets/js/shop-logic.js not found.');
    }
}

remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
?>

