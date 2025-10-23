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
 */
function aakaari_ps_ajax_check() {
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
    if (!wp_verify_nonce($nonce, 'aakaari_print_studio_nonce')) {
        wp_send_json_error('Nonce verification failed. Please refresh and try again.', 403);
    }
    if (!current_user_can('edit_products')) {
        wp_send_json_error('You do not have permission to perform this action.', 403);
    }
}

/**
 * AJAX Handler: Fetches all initial data for the app.
 * Action: aakaari_ps_load_data
 */
function aakaari_ps_load_data() {
    aakaari_ps_ajax_check();
    // ... Keep your existing aakaari_ps_load_data function code ...
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
                $hex = get_term_meta($term->term_id, 'hex_code', true);
                $colors[] = [
                    'id'   => $term->term_id, // Use the actual WordPress term ID
                    'name' => $term->name,
                    'hex'  => $hex ? sanitize_hex_color($hex) : '#000000',
                ];
            }
        }

        // --- Get Existing Print Studio Products ---
        $product_query = new WP_Query([
            'post_type'      => 'product',
            'posts_per_page' => -1, // Get all of them
            'post_status'    => ['publish', 'draft'], // Include drafts too
            'meta_query'     => [
                [
                    'key'     => '_aakaari_print_studio_data',
                    'compare' => 'EXISTS',
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
                $wc_product = wc_get_product($product_id);

                $studio_data = get_post_meta($product_id, '_aakaari_print_studio_data', true);
                if (!is_array($studio_data)) {
                    $studio_data = [];
                }

                $category_names = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'names']);
                $first_category = !is_wp_error($category_names) && !empty($category_names) ? $category_names[0] : '';

                $products[] = [
                    'id'                  => 'prod-' . $product_id,
                    'woocommerceId'       => $product_id,
                    'name'                => $wc_product->get_name(),
                    'description'         => $wc_product->get_description(),
                    'basePrice'           => (float) $wc_product->get_regular_price(),
                    'salePrice'           => $wc_product->get_sale_price() ? (float) $wc_product->get_sale_price() : null,
                    'category'            => $first_category,
                    'isActive'            => $wc_product->get_status() === 'publish',
                    'colors'              => $studio_data['colors'] ?? [],
                    'availablePrintTypes' => $studio_data['printTypes'] ?? [],
                    'sides'               => $studio_data['sides'] ?? [],
                ];
            }
        }
        wp_reset_postdata();

        wp_send_json_success([
            'products'   => $products,
            'categories' => $categories,
            'colors'     => $colors,
        ]);

    } catch (Exception $e) {
        wp_send_json_error('Server error while loading data: ' . $e->getMessage(), 500);
    }
}
add_action('wp_ajax_aakaari_ps_load_data', 'aakaari_ps_load_data');

/**
 * AJAX Handler: Saves a Product (Creates or Updates)
 * Action: aakaari_ps_save_product
 */
function aakaari_ps_save_product() {
    aakaari_ps_ajax_check();
    // ... Keep your existing aakaari_ps_save_product function code ...
     $product_data_json = isset($_POST['product_data']) ? wp_unslash($_POST['product_data']) : '';
    $product_data = json_decode($product_data_json, true);

    if (empty($product_data) || empty($product_data['name'])) {
        wp_send_json_error('Product data is missing or invalid.', 400);
    }

     try {
        $product_id = isset($product_data['woocommerceId']) ? intval($product_data['woocommerceId']) : 0;
        $is_new     = ($product_id === 0);

        if ($is_new) {
            $product = new WC_Product_Simple();
            $product->add_meta_data('_is_aakaari_print_studio', true, true);
        } else {
            $product = wc_get_product($product_id);
            if (!$product) {
                 $product = new WC_Product_Simple();
                 $product->add_meta_data('_is_aakaari_print_studio', true, true);
                 $is_new = true;
                 error_log("Aakaari PS: Product ID {$product_id} not found for update, creating new.");
            }
        }

        $product->set_name(sanitize_text_field($product_data['name']));
        $product->set_description(wp_kses_post($product_data['description']));
        $product->set_regular_price(wc_format_decimal($product_data['basePrice']));

        $sale_price = isset($product_data['salePrice']) ? wc_format_decimal($product_data['salePrice']) : '';
        $product->set_sale_price($sale_price > 0 ? $sale_price : '');

        $product->set_status(isset($product_data['isActive']) && $product_data['isActive'] ? 'publish' : 'draft');

        $category_name = sanitize_text_field($product_data['category']);
        $term = get_term_by('name', $category_name, 'product_cat');
        if ($term instanceof WP_Term) {
            $product->set_category_ids([$term->term_id]);
        } else {
             error_log("Aakaari PS: Category '{$category_name}' not found for product '{$product_data['name']}'.");
             $product->set_category_ids([]);
        }

        $product->set_virtual(true);

        // --- Save Custom Print Studio Data ---
        // **IMPORTANT**: Sanitize side data before saving
        $sides_data = isset($product_data['sides']) ? $product_data['sides'] : [];
        $sanitized_sides = [];
        if (is_array($sides_data)) {
            foreach($sides_data as $side) {
                 $sanitized_side = [
                     'id' => isset($side['id']) ? sanitize_text_field($side['id']) : uniqid('side_'),
                     'name' => isset($side['name']) ? sanitize_text_field($side['name']) : 'Unnamed Side',
                     'imageUrl' => isset($side['imageUrl']) ? esc_url_raw($side['imageUrl']) : '', // Crucial: Save the persistent URL
                     'printAreas' => [],
                     'restrictionAreas' => [],
                 ];
                 // Sanitize print areas
                 if (isset($side['printAreas']) && is_array($side['printAreas'])) {
                     foreach($side['printAreas'] as $pa) {
                         $sanitized_side['printAreas'][] = [
                             'id' => isset($pa['id']) ? sanitize_text_field($pa['id']) : uniqid('pa_'),
                             'name' => isset($pa['name']) ? sanitize_text_field($pa['name']) : 'Print Area',
                             'x' => isset($pa['x']) ? intval($pa['x']) : 0,
                             'y' => isset($pa['y']) ? intval($pa['y']) : 0,
                             'width' => isset($pa['width']) ? intval($pa['width']) : 100,
                             'height' => isset($pa['height']) ? intval($pa['height']) : 100,
                         ];
                     }
                 }
                  // Sanitize restriction areas
                 if (isset($side['restrictionAreas']) && is_array($side['restrictionAreas'])) {
                     foreach($side['restrictionAreas'] as $ra) {
                         $sanitized_side['restrictionAreas'][] = [
                             'id' => isset($ra['id']) ? sanitize_text_field($ra['id']) : uniqid('ra_'),
                             'name' => isset($ra['name']) ? sanitize_text_field($ra['name']) : 'Restriction Area',
                             'x' => isset($ra['x']) ? intval($ra['x']) : 0,
                             'y' => isset($ra['y']) ? intval($ra['y']) : 0,
                             'width' => isset($ra['width']) ? intval($ra['width']) : 50,
                             'height' => isset($ra['height']) ? intval($ra['height']) : 50,
                         ];
                     }
                 }
                 $sanitized_sides[] = $sanitized_side;
            }
        }


        $studio_data = [
            'colors'     => isset($product_data['colors']) ? array_map('sanitize_text_field', $product_data['colors']) : [],
            'printTypes' => isset($product_data['availablePrintTypes']) ? array_map('sanitize_text_field', $product_data['availablePrintTypes']) : [],
            'sides'      => $sanitized_sides, // Use sanitized sides
        ];

        $product->update_meta_data('_aakaari_print_studio_data', $studio_data);

        $new_product_id = $product->save();

        if ($new_product_id === 0) {
             throw new Exception('Failed to save product to WooCommerce.');
        }

        // --- Prepare Response ---
        $saved_wc_product = wc_get_product($new_product_id);
        $category_terms = wp_get_post_terms($new_product_id, 'product_cat', ['fields' => 'names']);
        $first_category = !is_wp_error($category_terms) && !empty($category_terms) ? $category_terms[0] : '';
        $final_studio_data = $saved_wc_product->get_meta('_aakaari_print_studio_data', true);

        $response_data = [
            'id'                  => 'prod-' . $new_product_id,
            'woocommerceId'       => $new_product_id,
            'name'                => $saved_wc_product->get_name(),
            'description'         => $saved_wc_product->get_description(),
            'basePrice'           => (float) $saved_wc_product->get_regular_price(),
            'salePrice'           => $saved_wc_product->get_sale_price() ? (float) $saved_wc_product->get_sale_price() : null,
            'category'            => $first_category,
            'isActive'            => $saved_wc_product->get_status() === 'publish',
            // Read back sanitized data
            'colors'              => is_array($final_studio_data) && isset($final_studio_data['colors']) ? $final_studio_data['colors'] : [],
            'availablePrintTypes' => is_array($final_studio_data) && isset($final_studio_data['printTypes']) ? $final_studio_data['printTypes'] : [],
            'sides'               => is_array($final_studio_data) && isset($final_studio_data['sides']) ? $final_studio_data['sides'] : [],
        ];

        wp_send_json_success($response_data);

    } catch (Exception $e) {
        wp_send_json_error('Server error saving product: ' . $e->getMessage(), 500);
    }
}
add_action('wp_ajax_aakaari_ps_save_product', 'aakaari_ps_save_product');

/**
 * AJAX Handler: Updates a Product's Status (Publish / Draft)
 * Action: aakaari_ps_update_status
 */
function aakaari_ps_update_status() {
    aakaari_ps_ajax_check();
    // ... Keep your existing aakaari_ps_update_status function code ...
     $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $is_active  = isset($_POST['is_active']) ? (intval($_POST['is_active']) === 1) : false;

    if ($product_id <= 0) {
        wp_send_json_error('Invalid Product ID provided.', 400);
    }

    $new_status = $is_active ? 'publish' : 'draft';

    $result = wp_update_post([
        'ID'          => $product_id,
        'post_status' => $new_status,
    ], true);

    if (is_wp_error($result)) {
         wp_send_json_error('Failed to update product status: ' . $result->get_error_message(), 500);
    }

    wp_send_json_success(['new_status' => $new_status]);
}
add_action('wp_ajax_aakaari_ps_update_status', 'aakaari_ps_update_status');


/**
 * AJAX Handler: Saves a Category (Creates or Updates)
 * Action: aakaari_ps_save_category
 */
function aakaari_ps_save_category() {
    aakaari_ps_ajax_check();
    // ... Keep your existing aakaari_ps_save_category function code ...
     $category_data_json = isset($_POST['category_data']) ? wp_unslash($_POST['category_data']) : '';
    $category_data = json_decode($category_data_json, true);

    if (empty($category_data) || empty($category_data['name'])) {
        wp_send_json_error('Category name is required.', 400);
    }

    $name = sanitize_text_field($category_data['name']);
    $id   = isset($category_data['id']) ? intval($category_data['id']) : 0;

    if ($id > 0) {
        $result = wp_update_term($id, 'product_cat', ['name' => $name]);
    } else {
        $result = wp_insert_term($name, 'product_cat');
    }

    if (is_wp_error($result)) {
        wp_send_json_error('Failed to save category: ' . $result->get_error_message(), 500);
    }

    $term_id = $result['term_id'];
    $new_term = get_term($term_id, 'product_cat');

    wp_send_json_success([
        'id'   => $new_term->term_id,
        'name' => $new_term->name,
    ]);
}
add_action('wp_ajax_aakaari_ps_save_category', 'aakaari_ps_save_category');

/**
 * AJAX Handler: Deletes a Category
 * Action: aakaari_ps_delete_category
 */
function aakaari_ps_delete_category() {
    aakaari_ps_ajax_check();
    // ... Keep your existing aakaari_ps_delete_category function code ...
     $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    if ($category_id <= 0) {
        wp_send_json_error('Invalid Category ID provided.', 400);
    }

    $result = wp_delete_term($category_id, 'product_cat');

    if (is_wp_error($result)) {
        wp_send_json_error('Failed to delete category: ' . $result->get_error_message(), 500);
    }
    if ($result === false) {
         wp_send_json_error('Category not found or could not be deleted.', 404);
    }

    wp_send_json_success(true);
}
add_action('wp_ajax_aakaari_ps_delete_category', 'aakaari_ps_delete_category');

// +++ NEW AJAX HANDLER FOR IMAGE UPLOAD +++
/**
 * AJAX Handler: Uploads a side image
 * Action: aakaari_ps_upload_side_image
 */
function aakaari_ps_upload_side_image() {
    aakaari_ps_ajax_check(); // Security check

    if (empty($_FILES['side_image_file'])) {
        wp_send_json_error('No file uploaded.', 400);
    }

    $file = $_FILES['side_image_file'];

    // Use WordPress's media uploader
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    // Allow common image types
    $allowed_mime_types = array(
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png'          => 'image/png',
        'gif'          => 'image/gif',
        'webp'         => 'image/webp',
    );

    // Upload the file to the media library
    $attachment_id = media_handle_upload('side_image_file', 0, array(), array(
        'mimes' => $allowed_mime_types,
        'test_form' => false
    ));

    if (is_wp_error($attachment_id)) {
        wp_send_json_error('Upload Error: ' . $attachment_id->get_error_message(), 500);
    }

    // Get the URL of the uploaded image ('large' size is usually good for previews)
    $image_url = wp_get_attachment_image_url($attachment_id, 'large');

    if (!$image_url) {
        wp_delete_attachment($attachment_id, true); // Clean up
        wp_send_json_error('Failed to get image URL after upload.', 500);
    }

    // Send back the attachment ID and URL
    wp_send_json_success(array(
        'message'       => 'File uploaded successfully.',
        'attachment_id' => $attachment_id,
        'url'           => $image_url,
    ));
}
// Hook the new upload handler
add_action('wp_ajax_aakaari_ps_upload_side_image', 'aakaari_ps_upload_side_image');

?>