<?php
/**
 * Product Customizer/Designer Functionality
 * 
 * This file contains:
 * - Product designer assets
 * - AJAX handlers for customization
 * - Cart and order meta handling
 * - Color/size attribute management
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register and enqueue product customizer scripts and styles
 */
function enqueue_product_customizer_assets() {
    if (is_page_template('product-customizer.php')) {
        // External libraries
        wp_enqueue_script(
            'fabricjs',
            'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.2.1/fabric.min.js',
            array(),
            '5.2.1',
            true
        );
        
        wp_enqueue_style(
            'dropzonejs-css',
            'https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css',
            array(),
            '5.9.3'
        );
        
        wp_enqueue_script(
            'dropzonejs',
            'https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js',
            array('jquery'),
            '5.9.3',
            true
        );
        
        // Customizer CSS
        wp_enqueue_style(
            'product-customizer-css',
            get_template_directory_uri() . '/assets/css/product-customizer.css',
            array(),
            filemtime(get_template_directory() . '/assets/css/product-customizer.css')
        );
        
        // Customizer JS
        wp_enqueue_script(
            'product-customizer-js',
            get_template_directory_uri() . '/assets/js/product-customizer.js',
            array('jquery', 'fabricjs', 'dropzonejs'),
            filemtime(get_template_directory() . '/assets/js/product-customizer.js'),
            true
        );
        
        // Localize script
        wp_localize_script('product-customizer-js', 'product_customizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'customization_nonce' => wp_create_nonce('product_customization'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_product_customizer_assets');

/**
 * Register color attribute meta field
 */
function register_color_attribute_meta() {
    register_meta('term', 'color_value', array(
        'type' => 'string',
        'description' => 'Color hex value',
        'single' => true,
        'show_in_rest' => true,
    ));
}
add_action('init', 'register_color_attribute_meta');

/**
 * Add color picker to attribute term edit form
 */
function add_color_attribute_fields($term) {
    $color_value = get_term_meta($term->term_id, 'color_value', true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="color_value">Color Value</label></th>
        <td>
            <input type="color" name="color_value" id="color_value" value="<?php echo esc_attr($color_value); ?>">
            <p class="description">Choose a color value for this attribute.</p>
        </td>
    </tr>
    <?php
}
add_action('pa_color_edit_form_fields', 'add_color_attribute_fields');

/**
 * Save color attribute meta
 */
function save_color_attribute_meta($term_id) {
    if (isset($_POST['color_value'])) {
        update_term_meta($term_id, 'color_value', sanitize_text_field($_POST['color_value']));
    }
}
add_action('edited_pa_color', 'save_color_attribute_meta');
add_action('created_pa_color', 'save_color_attribute_meta');

/**
 * AJAX handler for adding customized product to cart
 */
function add_customized_product_to_cart() {
    // Check nonce
    if (!check_ajax_referer('product_customization', 'customization_nonce', false)) {
        wp_send_json_error(array('message' => 'Security check failed'));
        exit;
    }
    
    // Get and validate form data
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $color_id = isset($_POST['color_id']) ? absint($_POST['color_id']) : 0;
    $size_id = isset($_POST['size_id']) ? absint($_POST['size_id']) : 0;
    $design_data = isset($_POST['design_data']) ? sanitize_text_field($_POST['design_data']) : '';
    $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;
    
    // Validate data
    if (!$product_id || !$color_id || !$size_id || empty($design_data)) {
        wp_send_json_error(array('message' => 'Invalid customization data'));
        exit;
    }
    
    // Get variation ID for variable products
    $variation_id = 0;
    $product = wc_get_product($product_id);
    
    if ($product && $product->is_type('variable')) {
        // Get color and size terms
        $color_term = get_term($color_id, 'pa_color');
        $size_term = get_term($size_id, 'pa_size');
        
        if ($color_term && $size_term) {
            // Find matching variation
            $variation_id = find_matching_product_variation($product_id, array(
                'attribute_pa_color' => $color_term->slug,
                'attribute_pa_size' => $size_term->slug
            ));
        }
    }
    
    // Prepare custom cart item data
    $cart_item_data = array(
        'custom_design' => array(
            'design_data' => $design_data,
            'color_id' => $color_id,
            'size_id' => $size_id
        ),
        // Add unique key to prevent cart item merging
        'unique_key' => md5(microtime() . rand())
    );
    
    // Add to cart
    $added = WC()->cart->add_to_cart(
        $product_id,
        $quantity,
        $variation_id,
        array(),
        $cart_item_data
    );
    
    if ($added) {
        wp_send_json_success(array(
            'message' => 'Product added to cart successfully',
            'cart_url' => wc_get_cart_url()
        ));
    } else {
        wp_send_json_error(array('message' => 'Failed to add product to cart'));
    }
    
    exit;
}
add_action('wp_ajax_add_customized_product_to_cart', 'add_customized_product_to_cart');
add_action('wp_ajax_nopriv_add_customized_product_to_cart', 'add_customized_product_to_cart');

/**
 * Find matching product variation based on attributes
 * 
 * @param int $product_id Product ID
 * @param array $attributes Attribute values
 * @return int Variation ID or 0
 */
function find_matching_product_variation($product_id, $attributes) {
    $data_store = new WC_Product_Data_Store_CPT();
    $product = new WC_Product($product_id);
    return $data_store->find_matching_product_variation($product, $attributes);
}

/**
 * Display custom design information in cart
 */
function display_custom_design_in_cart($item_data, $cart_item) {
    if (isset($cart_item['custom_design'])) {
        $custom_data = $cart_item['custom_design'];
        
        // Add custom design indicator
        $item_data[] = array(
            'key'   => 'Custom Design',
            'value' => 'Yes'
        );
        
        // Add color information
        if (isset($custom_data['color_id'])) {
            $color_term = get_term($custom_data['color_id'], 'pa_color');
            if ($color_term) {
                $item_data[] = array(
                    'key'   => 'Color',
                    'value' => $color_term->name
                );
            }
        }
        
        // Add size information
        if (isset($custom_data['size_id'])) {
            $size_term = get_term($custom_data['size_id'], 'pa_size');
            if ($size_term) {
                $item_data[] = array(
                    'key'   => 'Size',
                    'value' => $size_term->name
                );
            }
        }
    }
    
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'display_custom_design_in_cart', 10, 2);

/**
 * Save custom design data to order items
 */
function save_custom_design_to_order($item, $cart_item_key, $values, $order) {
    if (isset($values['custom_design'])) {
        $item->add_meta_data('_custom_design', $values['custom_design']);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'save_custom_design_to_order', 10, 4);

/**
 * Display custom design data in order details
 */
function display_custom_design_in_order($item_id, $item, $product) {
    $custom_design = $item->get_meta('_custom_design');
    
    if ($custom_design) {
        // Display custom design info
        echo '<p><strong>Custom Design:</strong> Yes</p>';
        
        // Display color info
        if (isset($custom_design['color_id'])) {
            $color_term = get_term($custom_design['color_id'], 'pa_color');
            if ($color_term) {
                echo '<p><strong>Color:</strong> ' . esc_html($color_term->name) . '</p>';
            }
        }
        
        // Display size info
        if (isset($custom_design['size_id'])) {
            $size_term = get_term($custom_design['size_id'], 'pa_size');
            if ($size_term) {
                echo '<p><strong>Size:</strong> ' . esc_html($size_term->name) . '</p>';
            }
        }
        
        // Optionally display design data preview
        if (isset($custom_design['design_data']) && !empty($custom_design['design_data'])) {
            echo '<p><strong>Design ID:</strong> ' . substr($custom_design['design_data'], 0, 10) . '...</p>';
        }
    }
}
add_action('woocommerce_order_item_meta_end', 'display_custom_design_in_order', 10, 3);

/**
 * Admin column for custom designs in orders
 */
function add_custom_design_order_column($columns) {
    $columns['custom_design'] = __('Custom Design', 'aakaari');
    return $columns;
}
add_filter('manage_edit-shop_order_columns', 'add_custom_design_order_column', 20);

/**
 * Display custom design indicator in admin orders list
 */
function display_custom_design_order_column($column) {
    global $post;
    
    if ('custom_design' === $column) {
        $order = wc_get_order($post->ID);
        $has_custom = false;
        
        foreach ($order->get_items() as $item) {
            if ($item->get_meta('_custom_design')) {
                $has_custom = true;
                break;
            }
        }
        
        if ($has_custom) {
            echo '<span class="dashicons dashicons-art" title="Has custom design"></span>';
        }
    }
}
add_action('manage_shop_order_posts_custom_column', 'display_custom_design_order_column');