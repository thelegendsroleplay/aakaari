<?php
/**
 * Print Area Manager Class
 * Manages print area configuration and storage
 *
 * @package Aakaari_Customizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aakaari_Print_Area_Manager {

    /**
     * AJAX save print area
     */
    public function ajax_save() {
        check_ajax_referer('aakaari_customizer_admin', 'nonce');

        if (!current_user_can('edit_products')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'aakaari')), 403);
        }

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;
        $print_area = isset($_POST['print_area']) ? json_decode(stripslashes($_POST['print_area']), true) : array();

        if (!$product_id && !$variation_id) {
            wp_send_json_error(array('message' => __('Invalid product/variation ID.', 'aakaari')), 400);
        }

        if (empty($print_area) || !is_array($print_area)) {
            wp_send_json_error(array('message' => __('Invalid print area data.', 'aakaari')), 400);
        }

        // Sanitize print area
        $sanitized = $this->sanitize_print_area($print_area);

        // Save to variation or product
        if ($variation_id > 0) {
            update_post_meta($variation_id, Aakaari_Customizer_Core::VAR_PRINT_AREA, $sanitized);
        } else {
            $print_areas = get_post_meta($product_id, Aakaari_Customizer_Core::META_PRINT_AREAS, true);
            if (!is_array($print_areas)) {
                $print_areas = array();
            }

            // For now, store as single print area (can be extended to multiple)
            $print_areas['default'] = $sanitized;
            update_post_meta($product_id, Aakaari_Customizer_Core::META_PRINT_AREAS, $print_areas);
        }

        wp_send_json_success(array(
            'message' => __('Print area saved successfully.', 'aakaari'),
            'print_area' => $sanitized
        ));
    }

    /**
     * Sanitize print area data
     */
    private function sanitize_print_area($print_area) {
        return array(
            'x' => isset($print_area['x']) ? floatval($print_area['x']) : 0,
            'y' => isset($print_area['y']) ? floatval($print_area['y']) : 0,
            'w' => isset($print_area['w']) ? floatval($print_area['w']) : 1.0,
            'h' => isset($print_area['h']) ? floatval($print_area['h']) : 1.0
        );
    }

    /**
     * Get product print areas
     */
    public function get_product_print_areas($product_id) {
        $print_areas = get_post_meta($product_id, Aakaari_Customizer_Core::META_PRINT_AREAS, true);

        if (empty($print_areas) || !is_array($print_areas)) {
            return array();
        }

        return $print_areas;
    }

    /**
     * Get variation print area
     */
    public function get_variation_print_area($variation_id) {
        $print_area = get_post_meta($variation_id, Aakaari_Customizer_Core::VAR_PRINT_AREA, true);

        if (empty($print_area) || !is_array($print_area)) {
            return false;
        }

        return $print_area;
    }

    /**
     * Delete print area
     */
    public function delete_print_area($product_id, $variation_id = 0) {
        if ($variation_id > 0) {
            delete_post_meta($variation_id, Aakaari_Customizer_Core::VAR_PRINT_AREA);
        } else {
            delete_post_meta($product_id, Aakaari_Customizer_Core::META_PRINT_AREAS);
        }

        return true;
    }

    /**
     * Validate print area coordinates
     */
    public function validate_coordinates($print_area) {
        if (!is_array($print_area)) {
            return false;
        }

        $required = array('x', 'y', 'w', 'h');
        foreach ($required as $key) {
            if (!isset($print_area[$key])) {
                return false;
            }

            $value = floatval($print_area[$key]);
            if ($value < 0 || $value > 1) {
                return false;
            }
        }

        // Check that print area doesn't exceed mockup bounds
        if (($print_area['x'] + $print_area['w']) > 1) {
            return false;
        }

        if (($print_area['y'] + $print_area['h']) > 1) {
            return false;
        }

        return true;
    }
}
