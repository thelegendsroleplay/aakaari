<?php
/**
 * Validator Class
 * Server-side validation for design boundaries and data integrity
 *
 * @package Aakaari_Customizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aakaari_Validator {

    /**
     * AJAX validate design
     */
    public function ajax_validate() {
        check_ajax_referer('aakaari_customizer', 'nonce');

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;
        $design_data = isset($_POST['design_data']) ? json_decode(stripslashes($_POST['design_data']), true) : array();

        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product ID.', 'aakaari')), 400);
        }

        if (empty($design_data)) {
            wp_send_json_error(array('message' => __('No design data provided.', 'aakaari')), 400);
        }

        $validation = $this->validate_design_boundaries($product_id, $variation_id, $design_data);

        if (is_wp_error($validation)) {
            wp_send_json_error(array('message' => $validation->get_error_message()), 400);
        }

        wp_send_json_success(array('message' => __('Design is valid.', 'aakaari')));
    }

    /**
     * Validate design boundaries
     *
     * @param int $product_id Product ID
     * @param int $variation_id Variation ID (if applicable)
     * @param array $design_data Design data with transform
     * @return true|WP_Error
     */
    public function validate_design_boundaries($product_id, $variation_id, $design_data) {
        // Get print area
        $print_area = $this->get_print_area($product_id, $variation_id);

        if (!$print_area) {
            // No print area defined - allow anything
            return true;
        }

        // Validate print area meta in design data
        if (!isset($design_data['print_area_meta'])) {
            return new WP_Error(
                'missing_print_area',
                __('Print area metadata is missing.', 'aakaari')
            );
        }

        // Get transform data
        $transform = isset($design_data['applied_transform']) ? $design_data['applied_transform'] : array();

        // Calculate design bounding box
        $design_bounds = $this->calculate_design_bounds($transform, $design_data);

        // Check if design is within print area
        $is_valid = $this->is_within_bounds($design_bounds, $print_area);

        if (!$is_valid) {
            return new WP_Error(
                'design_out_of_bounds',
                __('Your design extends outside the allowed print area. Please adjust your design to fit within the boundaries.', 'aakaari')
            );
        }

        return true;
    }

    /**
     * Get print area for product/variation
     */
    private function get_print_area($product_id, $variation_id = 0) {
        // Try variation first
        if ($variation_id > 0) {
            $print_area = get_post_meta($variation_id, Aakaari_Customizer_Core::VAR_PRINT_AREA, true);
            if (!empty($print_area) && is_array($print_area)) {
                return $print_area;
            }
        }

        // Fall back to product
        $print_areas = get_post_meta($product_id, Aakaari_Customizer_Core::META_PRINT_AREAS, true);
        if (!empty($print_areas) && is_array($print_areas)) {
            // Return first print area
            return reset($print_areas);
        }

        return false;
    }

    /**
     * Calculate design bounding box from transform
     *
     * @param array $transform Applied transform {scale, x, y, rotation}
     * @param array $design_data Design data including original dimensions
     * @return array Bounding box {x, y, width, height} in relative coordinates
     */
    private function calculate_design_bounds($transform, $design_data) {
        // Default transform values
        $scale = isset($transform['scale']) ? floatval($transform['scale']) : 1.0;
        $x = isset($transform['x']) ? floatval($transform['x']) : 0;
        $y = isset($transform['y']) ? floatval($transform['y']) : 0;
        $rotation = isset($transform['rotation']) ? floatval($transform['rotation']) : 0;

        // Get design dimensions (relative to mockup)
        $width = isset($design_data['width']) ? floatval($design_data['width']) : 0;
        $height = isset($design_data['height']) ? floatval($design_data['height']) : 0;

        // Apply scale
        $scaled_width = $width * $scale;
        $scaled_height = $height * $scale;

        // For now, we'll use a simple bounding box (not accounting for rotation)
        // For production, you'd want to calculate the actual rotated bounding box
        if ($rotation !== 0) {
            // Calculate rotated bounding box
            $rad = deg2rad($rotation);
            $cos = abs(cos($rad));
            $sin = abs(sin($rad));

            $rotated_width = $scaled_width * $cos + $scaled_height * $sin;
            $rotated_height = $scaled_width * $sin + $scaled_height * $cos;

            return array(
                'x' => $x - ($rotated_width - $scaled_width) / 2,
                'y' => $y - ($rotated_height - $scaled_height) / 2,
                'width' => $rotated_width,
                'height' => $rotated_height
            );
        }

        return array(
            'x' => $x,
            'y' => $y,
            'width' => $scaled_width,
            'height' => $scaled_height
        );
    }

    /**
     * Check if design bounds are within print area
     *
     * @param array $design_bounds Design bounding box
     * @param array $print_area Print area bounds
     * @return bool
     */
    private function is_within_bounds($design_bounds, $print_area) {
        $design_x = $design_bounds['x'];
        $design_y = $design_bounds['y'];
        $design_right = $design_bounds['x'] + $design_bounds['width'];
        $design_bottom = $design_bounds['y'] + $design_bounds['height'];

        $print_x = isset($print_area['x']) ? floatval($print_area['x']) : 0;
        $print_y = isset($print_area['y']) ? floatval($print_area['y']) : 0;
        $print_width = isset($print_area['w']) ? floatval($print_area['w']) : 1.0;
        $print_height = isset($print_area['h']) ? floatval($print_area['h']) : 1.0;
        $print_right = $print_x + $print_width;
        $print_bottom = $print_y + $print_height;

        // Add small tolerance for floating point comparison (0.1%)
        $tolerance = 0.001;

        // Check all edges
        $within_left = $design_x >= ($print_x - $tolerance);
        $within_top = $design_y >= ($print_y - $tolerance);
        $within_right = $design_right <= ($print_right + $tolerance);
        $within_bottom = $design_bottom <= ($print_bottom + $tolerance);

        return $within_left && $within_top && $within_right && $within_bottom;
    }

    /**
     * Validate custom design data structure
     *
     * @param array $design_data Design data to validate
     * @return true|WP_Error
     */
    public function validate_design_data($design_data) {
        if (!is_array($design_data)) {
            return new WP_Error('invalid_data', __('Invalid design data format.', 'aakaari'));
        }

        // Required fields
        $required = array('attachment_ids', 'applied_transform', 'print_area_meta');
        foreach ($required as $field) {
            if (!isset($design_data[$field])) {
                return new WP_Error(
                    'missing_field',
                    sprintf(__('Required field "%s" is missing.', 'aakaari'), $field)
                );
            }
        }

        // Validate attachment IDs
        if (!is_array($design_data['attachment_ids']) || empty($design_data['attachment_ids'])) {
            return new WP_Error('invalid_attachments', __('No valid attachments provided.', 'aakaari'));
        }

        foreach ($design_data['attachment_ids'] as $attachment_id) {
            if (!wp_attachment_is_image($attachment_id)) {
                return new WP_Error(
                    'invalid_attachment',
                    sprintf(__('Attachment ID %d is not a valid image.', 'aakaari'), $attachment_id)
                );
            }
        }

        // Validate transform
        $transform = $design_data['applied_transform'];
        if (!is_array($transform)) {
            return new WP_Error('invalid_transform', __('Invalid transform data.', 'aakaari'));
        }

        // Validate print area meta
        $print_area_meta = $design_data['print_area_meta'];
        if (!is_array($print_area_meta)) {
            return new WP_Error('invalid_print_area', __('Invalid print area metadata.', 'aakaari'));
        }

        return true;
    }

    /**
     * Sanitize design data
     *
     * @param array $design_data Raw design data
     * @return array Sanitized design data
     */
    public function sanitize_design_data($design_data) {
        $sanitized = array();

        // Sanitize attachment IDs
        if (isset($design_data['attachment_ids']) && is_array($design_data['attachment_ids'])) {
            $sanitized['attachment_ids'] = array_map('absint', $design_data['attachment_ids']);
        }

        // Sanitize preview URL
        if (isset($design_data['preview_url'])) {
            $sanitized['preview_url'] = esc_url_raw($design_data['preview_url']);
        }

        // Sanitize transform
        if (isset($design_data['applied_transform']) && is_array($design_data['applied_transform'])) {
            $sanitized['applied_transform'] = array(
                'scale' => isset($design_data['applied_transform']['scale']) ?
                    floatval($design_data['applied_transform']['scale']) : 1.0,
                'x' => isset($design_data['applied_transform']['x']) ?
                    floatval($design_data['applied_transform']['x']) : 0,
                'y' => isset($design_data['applied_transform']['y']) ?
                    floatval($design_data['applied_transform']['y']) : 0,
                'rotation' => isset($design_data['applied_transform']['rotation']) ?
                    floatval($design_data['applied_transform']['rotation']) : 0
            );
        }

        // Sanitize print area meta
        if (isset($design_data['print_area_meta']) && is_array($design_data['print_area_meta'])) {
            $sanitized['print_area_meta'] = array(
                'x' => isset($design_data['print_area_meta']['x']) ?
                    floatval($design_data['print_area_meta']['x']) : 0,
                'y' => isset($design_data['print_area_meta']['y']) ?
                    floatval($design_data['print_area_meta']['y']) : 0,
                'w' => isset($design_data['print_area_meta']['w']) ?
                    floatval($design_data['print_area_meta']['w']) : 1.0,
                'h' => isset($design_data['print_area_meta']['h']) ?
                    floatval($design_data['print_area_meta']['h']) : 1.0
            );
        }

        // Sanitize strings
        if (isset($design_data['print_type'])) {
            $sanitized['print_type'] = sanitize_text_field($design_data['print_type']);
        }

        if (isset($design_data['fabric_type'])) {
            $sanitized['fabric_type'] = sanitize_text_field($design_data['fabric_type']);
        }

        if (isset($design_data['color'])) {
            $sanitized['color'] = sanitize_text_field($design_data['color']);
        }

        if (isset($design_data['variation_id'])) {
            $sanitized['variation_id'] = absint($design_data['variation_id']);
        }

        return $sanitized;
    }
}
