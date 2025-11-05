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
 * Helper function to convert common color names to hex codes
 */
function aakaari_ps_color_name_to_hex($color_name) {
    $color_map = [
        'black'   => '#000000',
        'white'   => '#FFFFFF',
        'red'     => '#FF0000',
        'green'   => '#00FF00',
        'blue'    => '#0000FF',
        'yellow'  => '#FFFF00',
        'orange'  => '#FFA500',
        'purple'  => '#800080',
        'pink'    => '#FFC0CB',
        'brown'   => '#A52A2A',
        'gray'    => '#808080',
        'grey'    => '#808080',
        'navy'    => '#000080',
        'teal'    => '#008080',
        'lime'    => '#00FF00',
        'aqua'    => '#00FFFF',
        'maroon'  => '#800000',
        'olive'   => '#808000',
        'silver'  => '#C0C0C0',
        'gold'    => '#FFD700',
    ];
    
    $name_lower = strtolower(trim($color_name));
    
    // Check exact match
    if (isset($color_map[$name_lower])) {
        return $color_map[$name_lower];
    }
    
    // Check if color name contains a key
    foreach ($color_map as $key => $hex) {
        if (strpos($name_lower, $key) !== false) {
            return $hex;
        }
    }
    
    // Default gray if no match
    return '#808080';
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
        $color_terms = get_terms([
            'taxonomy'   => 'pa_color', // WooCommerce color attribute
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC'
        ]);
        
        $colors = [];
        if (is_wp_error($color_terms) || empty($color_terms)) {
            $attribute_exists = taxonomy_exists('pa_color');
            if (!$attribute_exists) {
                error_log('Aakaari Print Studio: pa_color attribute does not exist. Please create it in WooCommerce > Products > Attributes.');
            }
            
            $colors = [
                ['id' => 'wc_black', 'name' => 'Black', 'hex' => '#000000'],
                ['id' => 'wc_white', 'name' => 'White', 'hex' => '#FFFFFF'],
                ['id' => 'wc_red', 'name' => 'Red', 'hex' => '#FF0000'],
                ['id' => 'wc_blue', 'name' => 'Blue', 'hex' => '#0000FF'],
                ['id' => 'wc_green', 'name' => 'Green', 'hex' => '#00FF00'],
                ['id' => 'wc_yellow', 'name' => 'Yellow', 'hex' => '#FFFF00'],
            ];
        } else {
            foreach ($color_terms as $term) {
                $hex = get_term_meta($term->term_id, 'product_attribute_color', true);
                if (empty($hex)) {
                    $hex = get_term_meta($term->term_id, 'hex_code', true);
                }
                
                if (empty($hex)) {
                    $hex = aakaari_ps_color_name_to_hex($term->name);
                }
                
                $colors[] = [
                    'id'   => 'color_' . $term->term_id,
                    'name' => $term->name,
                    'hex'  => $hex ? sanitize_hex_color($hex) : '#808080',
                    'slug' => $term->slug,
                ];
            }
        }
        
        // --- Get Fabrics ---
        $fabric_terms = get_terms([
            'taxonomy'   => 'pa_fabric',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC'
        ]);
        
        $fabrics = [];
        if (!is_wp_error($fabric_terms) && !empty($fabric_terms)) {
            foreach ($fabric_terms as $term) {
                $description = get_term_meta($term->term_id, 'description', true) ?: $term->description;
                $price = get_term_meta($term->term_id, 'price', true) ?: 0;
                
                $fabrics[] = [
                    'id' => 'fab_' . $term->term_id,
                    'name' => $term->name,
                    'description' => $description,
                    'price' => floatval($price),
                    'slug' => $term->slug,
                ];
            }
        }
        
        // --- Get Print Types ---
        $print_type_terms = get_terms([
            'taxonomy'   => 'pa_print_type',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC'
        ]);
        
        $print_types = [];
        if (!is_wp_error($print_type_terms) && !empty($print_type_terms)) {
            foreach ($print_type_terms as $term) {
                $description = get_term_meta($term->term_id, 'description', true) ?: $term->description;
                $pricing_model = get_term_meta($term->term_id, 'pricing_model', true) ?: 'fixed';
                $price = get_term_meta($term->term_id, 'price', true) ?: 0;
                
                $print_types[] = [
                    'id' => 'pt_' . $term->term_id,
                    'name' => $term->name,
                    'description' => $description,
                    'pricingModel' => $pricing_model,
                    'price' => floatval($price),
                    'slug' => $term->slug,
                ];
            }
        }

        // --- Get Sizes ---
        $size_terms = get_terms([
            'taxonomy'   => 'pa_size',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC'
        ]);
        
        $sizes = [];
        if (!is_wp_error($size_terms) && !empty($size_terms)) {
            foreach ($size_terms as $term) {
                $sizes[] = [
                    'id' => 'size_' . $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                ];
            }
        }

        // --- Get Existing Print Studio Products ---
        $product_query = new WP_Query([
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'post_status'    => ['publish', 'draft'],
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
                    'fabrics'             => $studio_data['fabrics'] ?? [],
                    'availablePrintTypes' => $studio_data['printTypes'] ?? [],
                    'sizes'               => $studio_data['sizes'] ?? [],
                    'sides'               => $studio_data['sides'] ?? [],
                ];
            }
        }
        wp_reset_postdata();

        wp_send_json_success([
            'products'    => $products,
            'categories'  => $categories,
            'colors'      => $colors,
            'fabrics'     => $fabrics,
            'printTypes'  => $print_types,
            'sizes'       => $sizes,
        ]);

    } catch (Exception $e) {
        wp_send_json_error('Server error while loading data: ' . $e->getMessage(), 500);
    }
}
add_action('wp_ajax_aakaari_ps_load_data', 'aakaari_ps_load_data');

/**
 * Helper Function: Get or create attachment from URL
 * Downloads an image from a URL and creates a WordPress attachment
 * Returns attachment ID or false on failure
 */
function aakaari_ps_get_or_create_attachment_from_url($image_url, $post_id = 0) {
    // Validate URL
    if (empty($image_url) || !filter_var($image_url, FILTER_VALIDATE_URL)) {
        return false;
    }

    // Check if this URL already exists as an attachment
    global $wpdb;
    $attachment = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE guid = %s AND post_type = 'attachment' LIMIT 1",
        $image_url
    ));

    if (!empty($attachment)) {
        return $attachment[0];
    }

    // Include required WordPress files
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Download the image
    $tmp = download_url($image_url);

    if (is_wp_error($tmp)) {
        error_log("Aakaari PS: Failed to download image from {$image_url}: " . $tmp->get_error_message());
        return false;
    }

    // Get the filename and extension
    $file_array = array();
    preg_match('/[^\?]+\.(jpg|jpeg|gif|png|webp)/i', $image_url, $matches);

    if (empty($matches)) {
        // If we can't get extension from URL, try to get it from the downloaded file
        $image_info = getimagesize($tmp);
        if ($image_info && isset($image_info['mime'])) {
            $ext = image_type_to_extension($image_info[2], false);
            $file_array['name'] = 'product-image-' . uniqid() . '.' . $ext;
        } else {
            $file_array['name'] = 'product-image-' . uniqid() . '.jpg';
        }
    } else {
        $file_array['name'] = basename($matches[0]);
    }

    $file_array['tmp_name'] = $tmp;

    // Handle the upload
    $attachment_id = media_handle_sideload($file_array, $post_id, null);

    // Clean up temp file
    if (is_file($tmp)) {
        @unlink($tmp);
    }

    // Check for errors
    if (is_wp_error($attachment_id)) {
        error_log("Aakaari PS: Failed to create attachment: " . $attachment_id->get_error_message());
        return false;
    }

    return $attachment_id;
}

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
            'fabrics'    => isset($product_data['fabrics']) ? array_map('sanitize_text_field', $product_data['fabrics']) : [],
            'printTypes' => isset($product_data['availablePrintTypes']) ? array_map('sanitize_text_field', $product_data['availablePrintTypes']) : [],
            'sizes'      => isset($product_data['sizes']) ? array_map('sanitize_text_field', $product_data['sizes']) : [],
            'sides'      => $sanitized_sides, // Use sanitized sides
        ];

        $product->update_meta_data('_aakaari_print_studio_data', $studio_data);

        // **Sync to WooCommerce product attributes**
        if (!empty($studio_data['colors']) && is_array($studio_data['colors'])) {
            aakaari_ps_sync_colors_to_attribute($product, $studio_data['colors']);
        }
        if (!empty($studio_data['fabrics']) && is_array($studio_data['fabrics'])) {
            aakaari_ps_sync_fabrics_to_attribute($product, $studio_data['fabrics']);
        }
        if (!empty($studio_data['printTypes']) && is_array($studio_data['printTypes'])) {
            aakaari_ps_sync_print_types_to_attribute($product, $studio_data['printTypes']);
        }
        if (!empty($studio_data['sizes']) && is_array($studio_data['sizes'])) {
            aakaari_ps_sync_sizes_to_attribute($product, $studio_data['sizes']);
        }

        $new_product_id = $product->save();

        // **Set Featured Image from first side image**
        // This ensures WooCommerce displays the product image properly
        if (!empty($sanitized_sides) && isset($sanitized_sides[0]['imageUrl']) && !empty($sanitized_sides[0]['imageUrl'])) {
            $image_url = $sanitized_sides[0]['imageUrl'];
            $attachment_id = aakaari_ps_get_or_create_attachment_from_url($image_url, $new_product_id);

            if ($attachment_id) {
                set_post_thumbnail($new_product_id, $attachment_id);
                error_log("Aakaari PS: Set featured image (attachment #{$attachment_id}) for product #{$new_product_id}");
            } else {
                error_log("Aakaari PS: Failed to create attachment for product #{$new_product_id} from URL: {$image_url}");
            }
        } else {
            error_log("Aakaari PS: No side image available to set as featured image for product #{$new_product_id}");
        }

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
            'fabrics'             => is_array($final_studio_data) && isset($final_studio_data['fabrics']) ? $final_studio_data['fabrics'] : [],
            'availablePrintTypes' => is_array($final_studio_data) && isset($final_studio_data['printTypes']) ? $final_studio_data['printTypes'] : [],
            'sizes'               => is_array($final_studio_data) && isset($final_studio_data['sizes']) ? $final_studio_data['sizes'] : [],
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
add_action('wp_ajax_aakaari_ps_upload_side_image', 'aakaari_ps_upload_side_image');


/**
 * Sync Print Studio colors (hex values) to WooCommerce product attribute (pa_color)
 * This updates the product's color attribute with the selected colors
 * 
 * @param WC_Product $product The product object
 * @param array $color_hexes Array of hex color values like ['#FF0000', '#00FF00']
 */
function aakaari_ps_sync_colors_to_attribute($product, $color_hexes) {
    if (empty($color_hexes) || !is_array($color_hexes)) {
        return;
    }
    
    // Get or create the pa_color attribute
    $attribute_taxonomy = 'pa_color';
    
    // Make sure the taxonomy is registered
    if (!taxonomy_exists($attribute_taxonomy)) {
        error_log('Warning: pa_color taxonomy does not exist. Colors not synced to product attribute.');
        return;
    }
    
    $term_ids = array();
    
    // For each hex color, find or create the matching term
    foreach ($color_hexes as $hex) {
        // Remove # from hex
        $hex_clean = ltrim($hex, '#');
        
        // First, try to get color name to search by name too
        $expected_name = aakaari_ps_hex_to_color_name($hex_clean);
        $term = null;
        
        // Try to find by name first
        if (strpos($expected_name, '#') !== 0) {
            $term = get_term_by('name', $expected_name, $attribute_taxonomy);
        }
        
        // If not found by name, try meta query
        if (!$term) {
            $terms = get_terms(array(
                'taxonomy' => $attribute_taxonomy,
                'hide_empty' => false,
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => 'hex_code',
                        'value' => $hex_clean,
                        'compare' => '='
                    ),
                    array(
                        'key' => 'product_attribute_color',
                        'value' => '#' . $hex_clean,
                        'compare' => '='
                    )
                )
            ));
            
            if (!empty($terms) && !is_wp_error($terms)) {
                $term = $terms[0];
            }
        }
        
        if ($term) {
            // Found existing term
            $term_ids[] = $term->term_id;
            error_log("Found existing color term: {$term->name} (#{$hex_clean})");
        } else {
            // Term not found, create a new one
            $color_name = aakaari_ps_hex_to_color_name($hex_clean);
            
            // If the function returned the hex back, generate a name
            if (strpos($color_name, '#') === 0) {
                $color_name = 'Color ' . strtoupper($hex_clean);
            }
            
            $new_term = wp_insert_term($color_name, $attribute_taxonomy);
            
            if (!is_wp_error($new_term)) {
                $term_id = $new_term['term_id'];
                // Save the hex code as term meta
                update_term_meta($term_id, 'hex_code', $hex_clean);
                update_term_meta($term_id, 'product_attribute_color', '#' . $hex_clean);
                $term_ids[] = $term_id;
                error_log("Created new color term: $color_name (#{$hex_clean}) with ID $term_id");
            } else {
                error_log("Failed to create color term for $hex: " . $new_term->get_error_message());
            }
        }
    }
    
    // Now set these terms on the product
    if (!empty($term_ids)) {
        wp_set_object_terms($product->get_id(), $term_ids, $attribute_taxonomy);
        
        // Also set the attribute data on the product
        $attributes = $product->get_attributes();
        
        $color_attribute = new WC_Product_Attribute();
        $color_attribute->set_id(wc_attribute_taxonomy_id_by_name($attribute_taxonomy));
        $color_attribute->set_name($attribute_taxonomy);
        $color_attribute->set_options($term_ids);
        $color_attribute->set_visible(true);
        $color_attribute->set_variation(false); // Set to true if you want variations
        
        $attributes[$attribute_taxonomy] = $color_attribute;
        $product->set_attributes($attributes);
        
        error_log('Synced ' . count($term_ids) . ' colors to product attribute pa_color');
    }
}


/**
 * Helper function to convert hex to color name
 * Returns color name if found in map, otherwise returns the hex
 */
function aakaari_ps_hex_to_color_name($hex) {
    $hex = strtoupper(ltrim($hex, '#'));
    
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
    
    return isset($color_map[$hex]) ? $color_map[$hex] : '#' . $hex;
}

/**
 * Sync Print Studio fabrics (IDs) to WooCommerce product attribute (pa_fabric)
 * This updates the product's fabric attribute with the selected fabrics
 * 
 * @param WC_Product $product The product object
 * @param array $fabric_ids Array of fabric IDs like ['fab_123', 'fab_456']
 */
function aakaari_ps_sync_fabrics_to_attribute($product, $fabric_ids) {
    if (empty($fabric_ids) || !is_array($fabric_ids)) {
        return;
    }
    
    $attribute_taxonomy = 'pa_fabric';
    
    if (!taxonomy_exists($attribute_taxonomy)) {
        error_log('Warning: pa_fabric taxonomy does not exist. Fabrics not synced to product attribute.');
        return;
    }
    
    $term_ids = array();
    
    foreach ($fabric_ids as $fabric_id) {
        // Extract term ID from 'fab_123' format
        $term_id = intval(str_replace('fab_', '', $fabric_id));
        
        if ($term_id > 0 && term_exists($term_id, $attribute_taxonomy)) {
            $term_ids[] = $term_id;
        } else {
            error_log("Fabric term ID $term_id not found in pa_fabric taxonomy");
        }
    }
    
    if (!empty($term_ids)) {
        wp_set_object_terms($product->get_id(), $term_ids, $attribute_taxonomy);
        
        $attributes = $product->get_attributes();
        
        $fabric_attribute = new WC_Product_Attribute();
        $fabric_attribute->set_id(wc_attribute_taxonomy_id_by_name($attribute_taxonomy));
        $fabric_attribute->set_name($attribute_taxonomy);
        $fabric_attribute->set_options($term_ids);
        $fabric_attribute->set_visible(true);
        $fabric_attribute->set_variation(false);
        
        $attributes[$attribute_taxonomy] = $fabric_attribute;
        $product->set_attributes($attributes);
        
        error_log('Synced ' . count($term_ids) . ' fabrics to product attribute pa_fabric');
    }
}

/**
 * Sync Print Studio print types (IDs) to WooCommerce product attribute (pa_print_type)
 * This updates the product's print type attribute with the selected print types
 * 
 * @param WC_Product $product The product object
 * @param array $print_type_ids Array of print type IDs like ['pt_123', 'pt_456']
 */
function aakaari_ps_sync_print_types_to_attribute($product, $print_type_ids) {
    if (empty($print_type_ids) || !is_array($print_type_ids)) {
        return;
    }
    
    $attribute_taxonomy = 'pa_print_type';
    
    if (!taxonomy_exists($attribute_taxonomy)) {
        error_log('Warning: pa_print_type taxonomy does not exist. Print types not synced to product attribute.');
        return;
    }
    
    $term_ids = array();
    
    foreach ($print_type_ids as $print_type_id) {
        // Extract term ID from 'pt_123' format
        $term_id = intval(str_replace('pt_', '', $print_type_id));
        
        if ($term_id > 0 && term_exists($term_id, $attribute_taxonomy)) {
            $term_ids[] = $term_id;
        } else {
            error_log("Print type term ID $term_id not found in pa_print_type taxonomy");
        }
    }
    
    if (!empty($term_ids)) {
        wp_set_object_terms($product->get_id(), $term_ids, $attribute_taxonomy);
        
        $attributes = $product->get_attributes();
        
        $print_type_attribute = new WC_Product_Attribute();
        $print_type_attribute->set_id(wc_attribute_taxonomy_id_by_name($attribute_taxonomy));
        $print_type_attribute->set_name($attribute_taxonomy);
        $print_type_attribute->set_options($term_ids);
        $print_type_attribute->set_visible(true);
        $print_type_attribute->set_variation(false);
        
        $attributes[$attribute_taxonomy] = $print_type_attribute;
        $product->set_attributes($attributes);
        
        error_log('Synced ' . count($term_ids) . ' print types to product attribute pa_print_type');
    }
}

/**
 * Sync Print Studio sizes (IDs) to WooCommerce product attribute (pa_size)
 * This updates the product's size attribute with the selected sizes
 * 
 * @param WC_Product $product The product object
 * @param array $size_ids Array of size IDs like ['size_123', 'size_456']
 */
function aakaari_ps_sync_sizes_to_attribute($product, $size_ids) {
    if (empty($size_ids) || !is_array($size_ids)) {
        return;
    }
    
    $attribute_taxonomy = 'pa_size';
    
    if (!taxonomy_exists($attribute_taxonomy)) {
        error_log('Warning: pa_size taxonomy does not exist. Sizes not synced to product attribute.');
        return;
    }
    
    $term_ids = array();
    
    foreach ($size_ids as $size_id) {
        // Extract term ID from 'size_123' format
        $term_id = intval(str_replace('size_', '', $size_id));
        
        if ($term_id > 0 && term_exists($term_id, $attribute_taxonomy)) {
            $term_ids[] = $term_id;
        } else {
            error_log("Size term ID $term_id not found in pa_size taxonomy");
        }
    }
    
    if (!empty($term_ids)) {
        wp_set_object_terms($product->get_id(), $term_ids, $attribute_taxonomy);
        
        $attributes = $product->get_attributes();
        
        $size_attribute = new WC_Product_Attribute();
        $size_attribute->set_id(wc_attribute_taxonomy_id_by_name($attribute_taxonomy));
        $size_attribute->set_name($attribute_taxonomy);
        $size_attribute->set_options($term_ids);
        $size_attribute->set_visible(true);
        $size_attribute->set_variation(false);
        
        $attributes[$attribute_taxonomy] = $size_attribute;
        $product->set_attributes($attributes);
        
        error_log('Synced ' . count($term_ids) . ' sizes to product attribute pa_size');
    }
}

// Hook the new upload handler
add_action('wp_ajax_aakaari_ps_upload_side_image', 'aakaari_ps_upload_side_image');

/**
 * AJAX Handler: Upload color-specific mockup image
 * Action: aakaari_ps_upload_color_mockup
 */
function aakaari_ps_upload_color_mockup() {
    aakaari_ps_ajax_check(); // Security check

    if (empty($_FILES['color_mockup_file'])) {
        wp_send_json_error('No file uploaded.', 400);
    }

    if (empty($_POST['color'])) {
        wp_send_json_error('No color specified.', 400);
    }

    $color = sanitize_text_field($_POST['color']);
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;

    $file = $_FILES['color_mockup_file'];

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
    $attachment_id = media_handle_upload('color_mockup_file', $product_id, array(), array(
        'mimes' => $allowed_mime_types,
        'test_form' => false
    ));

    if (is_wp_error($attachment_id)) {
        wp_send_json_error('Upload Error: ' . $attachment_id->get_error_message(), 500);
    }

    // Get the URL of the uploaded image
    $image_url = wp_get_attachment_image_url($attachment_id, 'full');

    if (!$image_url) {
        wp_delete_attachment($attachment_id, true); // Clean up
        wp_send_json_error('Failed to get image URL after upload.', 500);
    }

    // Send back the attachment ID and URL
    wp_send_json_success(array(
        'message'       => 'Color mockup uploaded successfully.',
        'attachment_id' => $attachment_id,
        'url'           => $image_url,
        'color'         => $color
    ));
}
add_action('wp_ajax_aakaari_ps_upload_color_mockup', 'aakaari_ps_upload_color_mockup');

/**
 * AJAX Handler: Save color mockups with print areas
 * Action: aakaari_ps_save_color_mockups
 */
function aakaari_ps_save_color_mockups() {
    aakaari_ps_ajax_check(); // Security check

    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $color_mockups = isset($_POST['color_mockups']) ? $_POST['color_mockups'] : array();

    if (!$product_id) {
        wp_send_json_error('Invalid product ID.', 400);
    }

    // Sanitize and validate color mockups data
    $sanitized_mockups = array();
    foreach ($color_mockups as $color => $mockup_data) {
        $color_hex = sanitize_text_field($color);

        $sanitized_mockups[$color_hex] = array(
            'attachment_id' => isset($mockup_data['attachment_id']) ? absint($mockup_data['attachment_id']) : 0,
            'url' => isset($mockup_data['url']) ? esc_url_raw($mockup_data['url']) : '',
            'print_area' => array(
                'x' => isset($mockup_data['print_area']['x']) ? floatval($mockup_data['print_area']['x']) : 0,
                'y' => isset($mockup_data['print_area']['y']) ? floatval($mockup_data['print_area']['y']) : 0,
                'width' => isset($mockup_data['print_area']['width']) ? floatval($mockup_data['print_area']['width']) : 100,
                'height' => isset($mockup_data['print_area']['height']) ? floatval($mockup_data['print_area']['height']) : 100,
            )
        );
    }

    // Save to product meta
    update_post_meta($product_id, '_aakaari_color_mockups', $sanitized_mockups);

    // Also update the color variant images for backward compatibility
    $variant_images = array();
    foreach ($sanitized_mockups as $color => $mockup_data) {
        if (!empty($mockup_data['attachment_id'])) {
            $variant_images[$color] = $mockup_data['attachment_id'];
        }
    }
    update_post_meta($product_id, '_aakaari_color_variant_images', $variant_images);

    wp_send_json_success(array(
        'message' => 'Color mockups saved successfully.',
        'mockups' => $sanitized_mockups
    ));
}
add_action('wp_ajax_aakaari_ps_save_color_mockups', 'aakaari_ps_save_color_mockups');

?>