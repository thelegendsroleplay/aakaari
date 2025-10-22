<?php
/**
 * Functions for the Custom Products Page Template.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Enqueue scripts and styles for the Custom Products page.
 */
function enqueue_custom_products_assets() {
    // Only load these files on our new page template
    if (is_page_template('page-custom-products.php')) {
        
        // Enqueue the stylesheet
        wp_enqueue_style(
            'custom-products-style',
            get_template_directory_uri() . '/assets/css/custom-products.css',
            [],
            '1.0.0'
        );

        // Enqueue the javascript file
        wp_enqueue_script(
            'custom-products-script',
            get_template_directory_uri() . '/assets/js/custom-products.js',
            [], 
            '1.0.0',
            true // Load in footer
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_custom_products_assets');

/**
 * Helper function to get product print areas.
 * * This is a placeholder. You should store this data as product meta
 * (e.g., using Advanced Custom Fields repeater) and fetch it here.
 * * @param int $product_id The ID of the product.
 * @return array A list of print areas.
 */
function get_product_print_areas($product_id) {
    
    // First, try to get real meta data
    $areas = get_post_meta($product_id, '_print_areas', true);
    if (!empty($areas) && is_array($areas)) {
        return $areas;
    }

    // --- Placeholder/Example Data ---
    // If no meta is found, use this default data.
    $default_areas = [
        ['name' => 'Full Front', 'size' => '12" × 16"'],
        ['name' => 'Left Chest', 'size' => '4" × 4"']
    ];

    // You can add more placeholder logic here if needed
    // $sku = get_post_meta($product_id, '_sku', true);
    // if (strpos($sku, 'BAG') !== false) {
    //     return [['name' => 'Front Panel', 'size' => '10" × 10"']];
    // }
    
    return $default_areas;
}