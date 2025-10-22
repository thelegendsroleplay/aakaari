<?php
/**
 * AJAX Handlers for the Aakaari Print Studio
 *
 * Included from functions.php
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper function to check for valid permissions and nonce
 * We'll call this at the beginning of every AJAX handler.
 */
function aakaari_ps_ajax_check() {
    // Check nonce (security token)
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
    // Make sure the nonce name matches the one in print-studio-init.php
    if (!wp_verify_nonce($nonce, 'aakaari_print_studio_nonce')) { 
        wp_send_json_error('Nonce verification failed. Please refresh and try again.', 403);
    }

    // Check capability (only users who can manage products)
    // You might want a more specific capability later.
    if (!current_user_can('edit_products')) {
        wp_send_json_error('You do not have permission to perform this action.', 403);
    }
}

/**
 * AJAX Handler: Fetches all initial data for the app.
 * Action: aakaari_ps_load_data
 */
function aakaari_ps_load_data() {
    // Run security checks first
    aakaari_ps_ajax_check(); 

    try {
        // --- Get Categories ---
        $category_terms = get_terms([
            'taxonomy'   => 'product_cat', // Standard WooCommerce category taxonomy
            'hide_empty' => false,
        ]);
        $categories = [];
        if (!is_wp_error($category_terms)) {
            foreach ($category_terms as $term) {
                // Format for the JavaScript app
                $categories[] = [
                    'id'   => $term->term_id, // Use the actual WordPress term ID
                    'name' => $term->name,
                ];
            }
        }

        // --- Get Colors ---
        // Assumes you have a product attribute with the slug 'color' (like pa_color)
        $color_terms = get_terms([
            'taxonomy'   => 'pa_color', // *** ADJUST THIS if your attribute slug is different ***
            'hide_empty' => false,
        ]);
        $colors = [];
        if (is_wp_error($color_terms) || empty($color_terms)) {
            // Provide fallback colors if the attribute doesn't exist or has no terms
            $colors = [
                ['id' => 'wc_black', 'name' => 'Black', 'hex' => '#000000'],
                ['id' => 'wc_white', 'name' => 'White', 'hex' => '#FFFFFF'],
                ['id' => 'wc_red', 'name' => 'Red', 'hex' => '#FF0000'],
                 // Add more default colors if needed
            ];
        } else {
            foreach ($color_terms as $term) {
                // Tries to get a 'hex_code' value stored with the color term.
                // You might need to add a plugin or custom code to allow saving hex codes for attribute terms.
                $hex = get_term_meta($term->term_id, 'hex_code', true); 
                $colors[] = [
                    'id'   => $term->term_id, // Use the actual WordPress term ID
                    'name' => $term->name,
                     // Use saved hex or fallback to black
                    'hex'  => $hex ? sanitize_hex_color($hex) : '#000000', 
                ];
            }
        }

        // --- Get Existing Print Studio Products ---
        // We identify them by checking if they have our custom data saved.
        $product_query = new WP_Query([
            'post_type'      => 'product',
            'posts_per_page' => -1, // Get all of them
            'post_status'    => ['publish', 'draft'], // Include drafts too
            'meta_query'     => [
                [
                    // This meta key acts as a flag for products created/managed by our app
                    'key'     => '_aakaari_print_studio_data', 
                    'compare' => 'EXISTS', // Just check if the key exists
                ],
            ],
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        $products = [];
        if ($product_query->have_posts()) {
            while ($product_query->have_posts()) {
                $product_query->the_post();
                $product_id = get_the_ID();
                $wc_product = wc_get_product($product_id); // Get the WooCommerce product object
                
                // Retrieve the specific Print Studio data we saved earlier
                $studio_data = get_post_meta($product_id, '_aakaari_print_studio_data', true);
                // Ensure studio_data is an array, even if it wasn't saved correctly
                if (!is_array($studio_data)) {
                    $studio_data = []; 
                }
                
                // Get product category names
                $category_names = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'names']);
                $first_category = !is_wp_error($category_names) && !empty($category_names) ? $category_names[0] : '';


                // Format for the JavaScript app
                $products[] = [
                    // Use a prefix for the internal JS ID to avoid conflicts if needed
                    'id'                  => 'prod-' . $product_id, 
                    'woocommerceId'       => $product_id, // The actual WordPress Post ID
                    'name'                => $wc_product->get_name(),
                    'description'         => $wc_product->get_description(),
                    'basePrice'           => (float) $wc_product->get_regular_price(),
                    'salePrice'           => $wc_product->get_sale_price() ? (float) $wc_product->get_sale_price() : null,
                    'category'            => $first_category, // Send back the first category name
                    'isActive'            => $wc_product->get_status() === 'publish', // Check if published
                    // Safely access keys from our saved studio data, providing defaults
                    'colors'              => $studio_data['colors'] ?? [],
                    'availablePrintTypes' => $studio_data['printTypes'] ?? [],
                    'sides'               => $studio_data['sides'] ?? [],
                ];
            }
        }
        wp_reset_postdata(); // Important after custom WP_Query loops

        // --- Send all collected data back to JavaScript ---
        wp_send_json_success([
            'products'   => $products,
            'categories' => $categories,
            'colors'     => $colors,
        ]);

    } catch (Exception $e) {
        // If anything goes wrong, send an error response
        wp_send_json_error('Server error while loading data: ' . $e->getMessage(), 500);
    }
}
// Hook the PHP function 'aakaari_ps_load_data' to the AJAX action 'aakaari_ps_load_data'
// The 'wp_ajax_' prefix means this only works for logged-in users.
add_action('wp_ajax_aakaari_ps_load_data', 'aakaari_ps_load_data');

function aakaari_ps_save_product() {
    aakaari_ps_ajax_check(); // Security check

    // Get the JSON data sent from JavaScript and decode it
    $product_data_json = isset($_POST['product_data']) ? wp_unslash($_POST['product_data']) : '';
    $product_data = json_decode($product_data_json, true);

    // Basic validation
    if (empty($product_data) || empty($product_data['name'])) {
        wp_send_json_error('Product data is missing or invalid.', 400);
    }

    try {
        // Check if we are updating an existing product (has a WooCommerce ID) or creating a new one
        $product_id = isset($product_data['woocommerceId']) ? intval($product_data['woocommerceId']) : 0;
        $is_new     = ($product_id === 0);

        // --- Create or Get Product Object ---
        if ($is_new) {
            $product = new WC_Product_Simple();
            // Set a flag so we know this product originated from our studio
            $product->add_meta_data('_is_aakaari_print_studio', true, true); 
        } else {
            $product = wc_get_product($product_id);
            if (!$product) {
                // If the ID exists but product doesn't, treat as new
                 $product = new WC_Product_Simple();
                 $product->add_meta_data('_is_aakaari_print_studio', true, true); 
                 $is_new = true; // Force creation
                 error_log("Aakaari PS: Product ID {$product_id} not found for update, creating new.");
            }
        }

        // --- Set Standard WooCommerce Product Data ---
        $product->set_name(sanitize_text_field($product_data['name']));
        $product->set_description(wp_kses_post($product_data['description'])); // Allow basic HTML
        $product->set_regular_price(wc_format_decimal($product_data['basePrice'])); 
        
        // Handle sale price (set to empty string if not provided or 0)
        $sale_price = isset($product_data['salePrice']) ? wc_format_decimal($product_data['salePrice']) : '';
        $product->set_sale_price($sale_price > 0 ? $sale_price : '');

        // Set status (published or draft)
        $product->set_status(isset($product_data['isActive']) && $product_data['isActive'] ? 'publish' : 'draft');
        
        // --- Set Product Category ---
        // Find the term ID for the category name provided by the JS
        $category_name = sanitize_text_field($product_data['category']);
        $term = get_term_by('name', $category_name, 'product_cat');
        if ($term instanceof WP_Term) {
            $product->set_category_ids([$term->term_id]);
        } else {
             // Maybe log a warning if category not found?
             error_log("Aakaari PS: Category '{$category_name}' not found for product '{$product_data['name']}'.");
             $product->set_category_ids([]); // Assign no category or a default one
        }
        
        // We probably want these products to be virtual as they don't ship physically
        $product->set_virtual(true); 
        // You might set 'sold individually' if needed
        // $product->set_sold_individually(true); 

        // --- Save Custom Print Studio Data ---
        // This includes sides, print areas, available colors/print types from our app
        $studio_data = [
            // Ensure data exists before saving
            'colors'     => isset($product_data['colors']) ? array_map('sanitize_text_field', $product_data['colors']) : [],
            'printTypes' => isset($product_data['availablePrintTypes']) ? array_map('sanitize_text_field', $product_data['availablePrintTypes']) : [],
            // Sides data can be complex, basic sanitization for now
            'sides'      => isset($product_data['sides']) ? $product_data['sides'] : [], 
        ];
         // Use update_meta_data for better handling (creates if not exists, updates if it does)
        $product->update_meta_data('_aakaari_print_studio_data', $studio_data);
        
        // --- Save the Product ---
        $new_product_id = $product->save(); // This returns the product ID

        if ($new_product_id === 0) {
             throw new Exception('Failed to save product to WooCommerce.');
        }

        // --- Prepare Response ---
        // Send back the *full*, updated product data as it exists in WC now
        // This helps keep the JavaScript state synchronised.
        $saved_wc_product = wc_get_product($new_product_id);
        $category_terms = wp_get_post_terms($new_product_id, 'product_cat', ['fields' => 'names']);
        $first_category = !is_wp_error($category_terms) && !empty($category_terms) ? $category_terms[0] : '';
        $final_studio_data = $saved_wc_product->get_meta('_aakaari_print_studio_data', true);
        
        $response_data = [
            'id'                  => 'prod-' . $new_product_id, // Keep the JS internal ID format
            'woocommerceId'       => $new_product_id,
            'name'                => $saved_wc_product->get_name(),
            'description'         => $saved_wc_product->get_description(),
            'basePrice'           => (float) $saved_wc_product->get_regular_price(),
            'salePrice'           => $saved_wc_product->get_sale_price() ? (float) $saved_wc_product->get_sale_price() : null,
            'category'            => $first_category,
            'isActive'            => $saved_wc_product->get_status() === 'publish',
            'colors'              => is_array($final_studio_data) && isset($final_studio_data['colors']) ? $final_studio_data['colors'] : [],
            'availablePrintTypes' => is_array($final_studio_data) && isset($final_studio_data['printTypes']) ? $final_studio_data['printTypes'] : [],
            'sides'               => is_array($final_studio_data) && isset($final_studio_data['sides']) ? $final_studio_data['sides'] : [],
        ];
        
        // Send success response with the updated product data
        wp_send_json_success($response_data);

    } catch (Exception $e) {
        // Send error response
        wp_send_json_error('Server error saving product: ' . $e->getMessage(), 500);
    }
}
// Hook the save product function
add_action('wp_ajax_aakaari_ps_save_product', 'aakaari_ps_save_product');


/**
 * AJAX Handler: Updates a Product's Status (Publish / Draft)
 * Action: aakaari_ps_update_status
 */
function aakaari_ps_update_status() {
    aakaari_ps_ajax_check(); // Security check
    
    // Get data sent from JS
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    // Check 'is_active' which is sent as 1 (true) or 0 (false) from JS
    $is_active  = isset($_POST['is_active']) ? (intval($_POST['is_active']) === 1) : false; 

    if ($product_id <= 0) {
        wp_send_json_error('Invalid Product ID provided.', 400);
    }
    
    // Determine the new WordPress post status
    $new_status = $is_active ? 'publish' : 'draft';
    
    // Update the post status using WordPress function
    $result = wp_update_post([
        'ID'          => $product_id,
        'post_status' => $new_status,
    ], true); // true enables WP_Error return on failure

    if (is_wp_error($result)) {
         wp_send_json_error('Failed to update product status: ' . $result->get_error_message(), 500);
    }
    
    // Send simple success confirmation
    wp_send_json_success(['new_status' => $new_status]); 
}
// Hook the update status function
add_action('wp_ajax_aakaari_ps_update_status', 'aakaari_ps_update_status');


/**
 * AJAX Handler: Saves a Category (Creates or Updates)
 * Action: aakaari_ps_save_category
 */
function aakaari_ps_save_category() {
    aakaari_ps_ajax_check(); // Security check

    // Get JSON data for the category
    $category_data_json = isset($_POST['category_data']) ? wp_unslash($_POST['category_data']) : '';
    $category_data = json_decode($category_data_json, true);

    // Validate
    if (empty($category_data) || empty($category_data['name'])) {
        wp_send_json_error('Category name is required.', 400);
    }

    $name = sanitize_text_field($category_data['name']);
    // ID will be present if editing, otherwise 0 or null if new
    $id   = isset($category_data['id']) ? intval($category_data['id']) : 0; 

    if ($id > 0) {
        // Update existing category term
        $result = wp_update_term($id, 'product_cat', ['name' => $name]);
    } else {
        // Create new category term
        $result = wp_insert_term($name, 'product_cat');
    }

    // Check if term creation/update failed
    if (is_wp_error($result)) {
        wp_send_json_error('Failed to save category: ' . $result->get_error_message(), 500);
    }
    
    // Get the ID of the saved term (wp_insert_term returns an array, wp_update_term returns an array)
    $term_id = $result['term_id'];
    $new_term = get_term($term_id, 'product_cat');
    
    // Send back the data for the saved category (including its ID)
    wp_send_json_success([
        'id'   => $new_term->term_id,
        'name' => $new_term->name,
    ]);
}
// Hook the save category function
add_action('wp_ajax_aakaari_ps_save_category', 'aakaari_ps_save_category');


/**
 * AJAX Handler: Deletes a Category
 * Action: aakaari_ps_delete_category
 */
function aakaari_ps_delete_category() {
    aakaari_ps_ajax_check(); // Security check
    
    // Get category ID from JS
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    if ($category_id <= 0) { // Basic check for valid ID
        wp_send_json_error('Invalid Category ID provided.', 400);
    }
    
    // Attempt to delete the category term
    $result = wp_delete_term($category_id, 'product_cat');
    
    // Check for errors during deletion
    if (is_wp_error($result)) {
        wp_send_json_error('Failed to delete category: ' . $result->get_error_message(), 500);
    }
    // wp_delete_term returns false if term doesn't exist, true on success
    if ($result === false) {
         wp_send_json_error('Category not found or could not be deleted.', 404);
    }
    
    // Send success confirmation
    wp_send_json_success(true); 
}
// Hook the delete category function
add_action('wp_ajax_aakaari_ps_delete_category', 'aakaari_ps_delete_category');
// --- We will add the save/delete functions here later ---