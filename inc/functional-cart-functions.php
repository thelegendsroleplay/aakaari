<?php
/**
 * WooCommerce Custom Cart Integration Functions
 * Add this to your theme's functions.php file
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register custom cart assets
 */
function custom_cart_assets() {
    // Only load on cart page
    if (!is_cart()) {
        return;
    }
    
    // Enqueue custom cart styles
    wp_enqueue_style(
        'custom-cart-styles', 
        get_stylesheet_directory_uri() . '/assets/css/cart2.css', 
        array(), 
        filemtime(get_stylesheet_directory() . '/assets/css/cart2.css') // For cache busting
    );
    
    // Enqueue custom cart script
    wp_enqueue_script(
        'custom-cart-script', 
        get_stylesheet_directory_uri() . '/assets/js/cart2.js', 
        array('jquery', 'wc-cart'), 
        filemtime(get_stylesheet_directory() . '/assets/js/cart2.js'), 
        true
    );
    
    // Add dynamic data for JavaScript
    wp_localize_script('custom-cart-script', 'custom_cart_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'wc_ajax_url' => WC_AJAX::get_endpoint('%%endpoint%%'),
        'update_cart_nonce' => wp_create_nonce('update-cart'),
        'apply_coupon_nonce' => wp_create_nonce('apply-coupon'),
        'currency_symbol' => get_woocommerce_currency_symbol()
    ));
}
add_action('wp_enqueue_scripts', 'custom_cart_assets');

/**
 * Override WooCommerce cart template with our custom template
 */
function custom_woocommerce_locate_template($template, $template_name, $template_path) {
    // Only modify cart template
    if ($template_name != 'cart/cart.php') {
        return $template;
    }
    
    // Set the path to our custom template
    $custom_template = get_stylesheet_directory() . '/woocommerce/cart/cart.php';
    
    // Check if the custom template exists
    if (file_exists($custom_template)) {
        return $custom_template;
    }
    
    // Otherwise return the original template
    return $template;
}
add_filter('woocommerce_locate_template', 'custom_woocommerce_locate_template', 10, 3);

/**
 * Automatically apply discount when cart subtotal reaches threshold
 */
function custom_apply_discount_based_on_cart_subtotal() {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    
    // Skip if we're on checkout
    if (is_checkout()) {
        return;
    }
    
    // Only run if WooCommerce cart is available
    if (!WC()->cart) {
        return;
    }
    
    // Get discount thresholds
    $discount_tiers = array(
        array('threshold' => 250, 'discount' => 10, 'code' => 'TIER1'),
        array('threshold' => 500, 'discount' => 15, 'code' => 'TIER2'),
        array('threshold' => 1000, 'discount' => 20, 'code' => 'TIER3'),
    );
    
    // Get current cart subtotal
    $cart_subtotal = (float) WC()->cart->get_displayed_subtotal();
    
    // Remove any existing tier coupons
    $current_coupons = WC()->cart->get_applied_coupons();
    foreach ($current_coupons as $coupon_code) {
        if (in_array($coupon_code, array('TIER1', 'TIER2', 'TIER3'))) {
            WC()->cart->remove_coupon($coupon_code);
        }
    }
    
    // Find the highest applicable discount tier
    $applicable_tier = null;
    foreach ($discount_tiers as $tier) {
        if ($cart_subtotal >= $tier['threshold']) {
            $applicable_tier = $tier;
        } else {
            break; // Tiers are in ascending order
        }
    }
    
    // Apply the discount if we found an applicable tier
    if ($applicable_tier) {
        // Check if coupon exists
        $coupon_code = $applicable_tier['code'];
        $coupon = new WC_Coupon($coupon_code);
        
        // Only apply if the coupon exists and isn't already applied
        if ($coupon->get_id() && !in_array($coupon_code, $current_coupons)) {
            WC()->cart->apply_coupon($coupon_code);
        }
    }
}
add_action('woocommerce_before_cart', 'custom_apply_discount_based_on_cart_subtotal');
add_action('woocommerce_before_calculate_totals', 'custom_apply_discount_based_on_cart_subtotal');

/**
 * Create discount coupon codes if they don't exist
 */
function create_tiered_discount_coupons() {
    $discount_tiers = array(
        array('threshold' => 250, 'discount' => 10, 'code' => 'TIER1'),
        array('threshold' => 500, 'discount' => 15, 'code' => 'TIER2'),
        array('threshold' => 1000, 'discount' => 20, 'code' => 'TIER3'),
    );
    
    foreach ($discount_tiers as $tier) {
        $coupon_code = $tier['code'];
        $discount_percent = $tier['discount'];
        
        // Check if coupon already exists
        $coupon = new WC_Coupon($coupon_code);
        
        if (!$coupon->get_id()) {
            // Coupon doesn't exist, create it
            $coupon = array(
                'post_title' => $coupon_code,
                'post_content' => 'Automatic discount for orders over $' . $tier['threshold'],
                'post_status' => 'publish',
                'post_author' => 1,
                'post_type' => 'shop_coupon'
            );
            
            // Insert the post/coupon
            $coupon_id = wp_insert_post($coupon);
            
            // Add coupon meta
            update_post_meta($coupon_id, 'discount_type', 'percent');
            update_post_meta($coupon_id, 'coupon_amount', $discount_percent);
            update_post_meta($coupon_id, 'individual_use', 'no');
            update_post_meta($coupon_id, 'product_ids', '');
            update_post_meta($coupon_id, 'exclude_product_ids', '');
            update_post_meta($coupon_id, 'usage_limit', '');
            update_post_meta($coupon_id, 'expiry_date', '');
            update_post_meta($coupon_id, 'apply_before_tax', 'yes');
            update_post_meta($coupon_id, 'free_shipping', 'no');
            update_post_meta($coupon_id, 'minimum_amount', $tier['threshold']);
        }
    }
}
// Run on theme activation
add_action('after_switch_theme', 'create_tiered_discount_coupons');

/**
 * Create or update tiered discount coupons via an admin action
 */
function custom_create_update_discount_coupons() {
    // Check admin permissions
    if (!current_user_can('manage_woocommerce')) {
        return;
    }
    
    create_tiered_discount_coupons();
    
    // Add admin notice
    add_action('admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>Discount tier coupons have been created/updated successfully.</p>';
        echo '</div>';
    });
}
add_action('admin_init', 'custom_create_update_discount_coupons');

/**
 * Add custom cross-sells to cart page
 * This ensures the "You might also like" section always shows products
 */
function add_custom_cross_sells() {
    // Only run if there are no cross-sells already
    if (empty(WC()->cart->get_cross_sells())) {
        $products = wc_get_products(array(
            'status' => 'publish',
            'limit' => 3,
            'orderby' => 'rand',
            'featured' => true, // Optionally show featured products
        ));
        
        $cross_sells = array();
        foreach ($products as $product) {
            $cross_sells[] = $product->get_id();
        }
        
        // Add these as cross-sells
        if (!empty($cross_sells)) {
            add_filter('woocommerce_cross_sells_cart_item_ids', function($cart_item_ids) use ($cross_sells) {
                return array_unique(array_merge($cart_item_ids, $cross_sells));
            });
        }
    }
}
add_action('woocommerce_before_cart', 'add_custom_cross_sells', 20);

/**
 * Make cart updates smoother with AJAX (no page reload)
 */
function cart_fragments_implementation() {
    ?>
    <script type="text/javascript">
    jQuery(function($) {
        // Update cart fragments on load
        $(document.body).trigger('wc_fragment_refresh');
        
        // Handle the update button - make it use AJAX
        $(document.body).on('click', '[name="update_cart"]', function() {
            $(document.body).trigger('wc_update_cart');
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'cart_fragments_implementation');

/**
 * Update cart fragments for custom elements
 */
function custom_cart_fragments($fragments) {
    ob_start();
    ?>
    <p class="cart-items-count">
        <?php
        printf(
            esc_html(_n('%d item in your cart', '%d items in your cart', WC()->cart->get_cart_contents_count(), 'woocommerce')),
            esc_html(WC()->cart->get_cart_contents_count())
        );
        ?>
    </p>
    <?php
    $fragments['.cart-items-count'] = ob_get_clean();
    
    // Add other fragments as needed
    
    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'custom_cart_fragments');