<?php
/**
 * Mockup Manager Class
 * Manages mockup images for products and variations
 *
 * @package Aakaari_Customizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aakaari_Mockup_Manager {

    /**
     * AJAX upload mockup
     */
    public function ajax_upload() {
        check_ajax_referer('aakaari_customizer_admin', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'aakaari')), 403);
        }

        if (empty($_FILES['mockup_file'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'aakaari')), 400);
        }

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;
        $color = isset($_POST['color']) ? sanitize_text_field($_POST['color']) : '';

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $file = $_FILES['mockup_file'];

        // Validate image
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        $file_type = wp_check_filetype($file['name']);

        if (!in_array($file_type['type'], $allowed_types)) {
            wp_send_json_error(array('message' => __('Invalid file type. Only images are allowed.', 'aakaari')), 400);
        }

        // Upload
        $attachment_id = media_handle_upload('mockup_file', $product_id, array(), array(
            'mimes' => array_combine($allowed_types, $allowed_types),
            'test_form' => false
        ));

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()), 500);
        }

        $mockup_url = wp_get_attachment_url($attachment_id);

        // Save mockup reference
        if ($variation_id > 0) {
            update_post_meta($variation_id, Aakaari_Customizer_Core::VAR_MOCKUP_ID, $attachment_id);
        } elseif ($color) {
            $this->save_color_mockup($product_id, $color, $attachment_id, $mockup_url);
        }

        wp_send_json_success(array(
            'message' => __('Mockup uploaded successfully.', 'aakaari'),
            'attachment_id' => $attachment_id,
            'url' => $mockup_url
        ));
    }

    /**
     * Save color mockup
     */
    private function save_color_mockup($product_id, $color, $attachment_id, $url) {
        $mockups = get_post_meta($product_id, Aakaari_Customizer_Core::META_MOCKUPS, true);
        if (!is_array($mockups)) {
            $mockups = array();
        }

        $mockups[$color] = array(
            'attachment_id' => $attachment_id,
            'url' => $url
        );

        update_post_meta($product_id, Aakaari_Customizer_Core::META_MOCKUPS, $mockups);
    }

    /**
     * Get product mockups
     */
    public function get_product_mockups($product_id) {
        $mockups = array();

        // Get color-based mockups from product meta
        $color_mockups = get_post_meta($product_id, Aakaari_Customizer_Core::META_MOCKUPS, true);
        if (is_array($color_mockups)) {
            $mockups = $color_mockups;
        }

        // Check if variable product with variations
        $product = wc_get_product($product_id);
        if ($product && $product->is_type('variable')) {
            foreach ($product->get_available_variations() as $variation) {
                $variation_id = $variation['variation_id'];
                $mockup_id = get_post_meta($variation_id, Aakaari_Customizer_Core::VAR_MOCKUP_ID, true);

                if ($mockup_id) {
                    $key = 'variation_' . $variation_id;
                    $mockups[$key] = array(
                        'attachment_id' => $mockup_id,
                        'url' => wp_get_attachment_url($mockup_id),
                        'variation_id' => $variation_id,
                        'attributes' => $variation['attributes']
                    );

                    // Also index by color if available
                    if (isset($variation['attributes']['attribute_pa_color'])) {
                        $mockups[$variation['attributes']['attribute_pa_color']] = $mockups[$key];
                    }
                }
            }
        }

        return $mockups;
    }

    /**
     * Get variation mockup
     */
    public function get_variation_mockup($variation_id) {
        $mockup_id = get_post_meta($variation_id, Aakaari_Customizer_Core::VAR_MOCKUP_ID, true);

        if (!$mockup_id) {
            return false;
        }

        return array(
            'attachment_id' => $mockup_id,
            'url' => wp_get_attachment_url($mockup_id)
        );
    }

    /**
     * Delete mockup
     */
    public function delete_mockup($product_id, $color_or_variation) {
        // Check if it's a variation ID
        if (is_numeric($color_or_variation)) {
            $variation_id = absint($color_or_variation);
            $mockup_id = get_post_meta($variation_id, Aakaari_Customizer_Core::VAR_MOCKUP_ID, true);

            if ($mockup_id) {
                wp_delete_attachment($mockup_id, true);
                delete_post_meta($variation_id, Aakaari_Customizer_Core::VAR_MOCKUP_ID);
            }
        } else {
            // It's a color
            $mockups = get_post_meta($product_id, Aakaari_Customizer_Core::META_MOCKUPS, true);
            if (is_array($mockups) && isset($mockups[$color_or_variation])) {
                if (isset($mockups[$color_or_variation]['attachment_id'])) {
                    wp_delete_attachment($mockups[$color_or_variation]['attachment_id'], true);
                }

                unset($mockups[$color_or_variation]);
                update_post_meta($product_id, Aakaari_Customizer_Core::META_MOCKUPS, $mockups);
            }
        }

        return true;
    }

    /**
     * Validate mockup coverage for product
     *
     * Checks if all colors/variations have mockups
     */
    public function validate_mockup_coverage($product_id) {
        $product = wc_get_product($product_id);

        if (!$product) {
            return new WP_Error('invalid_product', __('Invalid product.', 'aakaari'));
        }

        $missing = array();

        if ($product->is_type('variable')) {
            // Check variations
            foreach ($product->get_available_variations() as $variation) {
                $variation_id = $variation['variation_id'];
                $mockup_id = get_post_meta($variation_id, Aakaari_Customizer_Core::VAR_MOCKUP_ID, true);

                if (!$mockup_id) {
                    $missing[] = sprintf(
                        __('Variation #%d is missing a mockup.', 'aakaari'),
                        $variation_id
                    );
                }
            }
        } else {
            // Check color mockups for simple products
            $mockups = get_post_meta($product_id, Aakaari_Customizer_Core::META_MOCKUPS, true);

            // Get expected colors from Print Studio data (if exists)
            $studio_data = get_post_meta($product_id, '_aakaari_print_studio_data', true);
            if (!empty($studio_data['colors']) && is_array($studio_data['colors'])) {
                foreach ($studio_data['colors'] as $color) {
                    if (!isset($mockups[$color]) || empty($mockups[$color]['attachment_id'])) {
                        $missing[] = sprintf(
                            __('Color %s is missing a mockup.', 'aakaari'),
                            $color
                        );
                    }
                }
            }
        }

        if (!empty($missing)) {
            return new WP_Error('missing_mockups', implode('<br>', $missing));
        }

        return true;
    }
}
