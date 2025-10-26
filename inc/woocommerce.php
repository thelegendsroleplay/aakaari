<?php
/**
 * WooCommerce Specific Functions
 * 
 * This file contains:
 * - WooCommerce wrappers
 * - Cart fragments
 * - Quick View AJAX functionality
 * - WooCommerce support checks
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add theme support for WooCommerce if not already present
 */
function aakaari_main_woocommerce_support() {
    if (!function_exists('is_woocommerce')) {
        return;
    }
    add_theme_support('woocommerce');
}
add_action('after_setup_theme', 'aakaari_main_woocommerce_support');

/**
 * Remove default WooCommerce wrappers
 */
remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);

/**
 * Add custom WooCommerce wrappers
 */
add_action('woocommerce_before_main_content', 'aakaari_wrapper_start', 10);
add_action('woocommerce_after_main_content', 'aakaari_wrapper_end', 10);

function aakaari_wrapper_start() {
    echo '<div class="container"><main id="main" class="site-main">';
}

function aakaari_wrapper_end() {
    echo '</main></div>';
}

/**
 * Add support for AJAX cart fragments
 */
add_filter('woocommerce_add_to_cart_fragments', 'aakaari_cart_fragment');

function aakaari_cart_fragment($fragments) {
    ob_start();
    ?>
    <a class="cart-contents" href="<?php echo wc_get_cart_url(); ?>" title="<?php _e('View your shopping cart'); ?>">
        <?php echo WC()->cart->get_cart_contents_count(); ?>
    </a>
    <?php
    $fragments['aakaari-cart-contents'] = ob_get_clean();
    return $fragments;
}

/**
 * Quick View AJAX handler
 */
add_action('wp_ajax_nopriv_aakaari_quick_view', 'aakaari_quick_view_handler');
add_action('wp_ajax_aakaari_quick_view', 'aakaari_quick_view_handler');

function aakaari_quick_view_handler() {
    // Validate product ID
    if (empty($_GET['product_id'])) {
        wp_send_json_error('Missing product id');
    }

    $pid = intval(wp_unslash($_GET['product_id']));
    if (!$pid) {
        wp_send_json_error('Invalid id');
    }

    // Check WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce not active');
    }

    // Get product
    $product = wc_get_product($pid);
    if (!$product || !$product->is_visible()) {
        wp_send_json_error('Product not found');
    }

    // Prepare product data
    $title = $product->get_name();
    $permalink = get_permalink($pid);
    $image = $product->get_image('woocommerce_single');
    $short_desc = apply_filters('woocommerce_short_description', $product->get_short_description());
    $avg_rating = floatval($product->get_average_rating());
    $rating_html = wc_get_rating_html($avg_rating, $product->get_rating_count());

    // Get prices
    $regular = $product->get_regular_price();
    $sale = $product->get_sale_price();
    $price_html = $product->get_price_html();

    // Get wholesale price (with fallback keys)
    $wholesale_price = get_post_meta($pid, '_wholesale_price', true);
    if (empty($wholesale_price)) {
        $wholesale_price = get_post_meta($pid, 'wholesale_price', true);
    }

    // Render Quick View HTML
    ob_start();
    ?>
    <div class="aqv">
        <div class="aqv-left">
            <div class="aqv-image"><?php echo $image; ?></div>
        </div>
        <div class="aqv-right">
            <h2 class="aqv-title"><?php echo esc_html($title); ?></h2>
            <div class="aqv-rating">
                <?php echo $rating_html; ?> 
                <span class="aqv-count">(<?php echo intval($product->get_rating_count()); ?>)</span>
            </div>
            <div class="aqv-prices">
                <?php if ($regular) : ?>
                    <div class="aqv-mrp">MRP: <span><?php echo wc_price($regular); ?></span></div>
                <?php endif; ?>
                <?php if ($wholesale_price && is_numeric($wholesale_price)) : ?>
                    <div class="aqv-wholesale">Wholesale: <span><?php echo wc_price($wholesale_price); ?></span></div>
                <?php endif; ?>
                <div class="aqv-current">Price: <span><?php echo $price_html; ?></span></div>
            </div>

            <div class="aqv-desc"><?php echo wp_kses_post($short_desc); ?></div>

            <div class="aqv-actions">
                <?php
                // Simple product: add to cart URL
                if ($product->is_type('simple')) {
                    $add_url = esc_url(add_query_arg('add-to-cart', $pid, home_url()));
                    echo '<a class="btn aaq-order-now" href="' . $add_url . '">Order Now</a>';
                } else {
                    // Variable/other types: link to product page
                    echo '<a class="btn aaq-order-now" href="' . esc_url($permalink) . '">View & Order</a>';
                }
                // Link to product page
                echo '<a class="btn btn-outline aaq-view" href="' . esc_url($permalink) . '">Open product page</a>';
                ?>
            </div>
        </div>
    </div>
    <?php

    $html = ob_get_clean();
    wp_send_json_success($html);
}

/** Load Cart UI Assets */
add_action('wp_enqueue_scripts', function () {
    if ( ! is_cart() ) return;

    // Fonts for UI
    wp_enqueue_style(
        'aakaari-inter',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        [],
        null
    );

    wp_enqueue_style(
        'aakaari-material-icons-outlined',
        'https://fonts.googleapis.com/css2?family=Material+Icons+Outlined',
        [],
        null
    );

    // Cart CSS after WC so it overrides layout
    wp_enqueue_style(
        'aakaari-cart',
        get_stylesheet_directory_uri() . '/assets/css/cart2.css',
        [ 'woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen' ],
        '1.0'
    );

    // Cart JS, depends on jQuery + Woo fragments
    wp_enqueue_script(
        'aakaari-cart-js',
        get_stylesheet_directory_uri() . '/assets/js/cart2.js',
        [ 'jquery', 'jquery-blockui', 'wc-cart-fragments' ],
        '1.0',
        true
    );
}, 99);

/**
 * Add Dashboard link to WooCommerce account menu
 */
function add_dashboard_link_to_account_menu($items) {
    $dashboard_page_id = get_option('dashboard_page_id');
    
    if ($dashboard_page_id) {
        $dashboard_link = array(
            'dashboard' => array(
                'title' => 'Reseller Dashboard',
                'url' => get_permalink($dashboard_page_id)
            )
        );
        
        // Insert after Dashboard but before Orders
        $new_items = array();
        foreach ($items as $key => $value) {
            if ($key === 'dashboard') {
                $new_items[$key] = $value;
                $new_items['reseller-dashboard'] = $dashboard_link['dashboard'];
            } else {
                $new_items[$key] = $value;
            }
        }
        
        return $new_items;
    }
    
    return $items;
}
add_filter('woocommerce_account_menu_items', 'add_dashboard_link_to_account_menu');