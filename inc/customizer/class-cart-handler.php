<?php
/**
 * Cart Handler Class
 * Manages WooCommerce cart integration for customized products
 *
 * @package Aakaari_Customizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aakaari_Cart_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        // WooCommerce hooks
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_add_to_cart'), 10, 3);
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 2);
        add_filter('woocommerce_get_item_data', array($this, 'display_cart_item_data'), 10, 2);
        add_filter('woocommerce_cart_item_thumbnail', array($this, 'cart_item_thumbnail'), 10, 3);
    }

    /**
     * AJAX add to cart
     */
    public function ajax_add_to_cart() {
        check_ajax_referer('aakaari_customizer', 'nonce');

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;
        $is_customized = isset($_POST['is_customized']) && $_POST['is_customized'] === 'true';

        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product ID.', 'aakaari')), 400);
        }

        // If not customized, use standard add to cart
        if (!$is_customized) {
            $added = WC()->cart->add_to_cart($product_id, 1, $variation_id);
            if ($added) {
                wp_send_json_success(array(
                    'message' => __('Product added to cart.', 'aakaari'),
                    'cart_url' => wc_get_cart_url()
                ));
            } else {
                wp_send_json_error(array('message' => __('Could not add product to cart.', 'aakaari')), 500);
            }
            return;
        }

        // Get and validate custom design data
        $design_data = isset($_POST['design_data']) ? json_decode(stripslashes($_POST['design_data']), true) : array();

        if (empty($design_data)) {
            wp_send_json_error(array('message' => __('No customization data provided.', 'aakaari')), 400);
        }

        // Validate design
        $validator = new Aakaari_Validator();

        $validation = $validator->validate_design_data($design_data);
        if (is_wp_error($validation)) {
            wp_send_json_error(array('message' => $validation->get_error_message()), 400);
        }

        $boundary_validation = $validator->validate_design_boundaries($product_id, $variation_id, $design_data);
        if (is_wp_error($boundary_validation)) {
            wp_send_json_error(array('message' => $boundary_validation->get_error_message()), 400);
        }

        // Sanitize design data
        $design_data = $validator->sanitize_design_data($design_data);

        // Add unique key to force separate cart items
        $cart_item_data = array(
            Aakaari_Customizer_Core::CART_CUSTOM_DESIGN => $design_data,
            Aakaari_Customizer_Core::CART_UNIQUE_KEY => md5(microtime() . rand())
        );

        // Add to cart
        $cart_item_key = WC()->cart->add_to_cart(
            $product_id,
            1, // Quantity
            $variation_id,
            array(), // Variation attributes
            $cart_item_data
        );

        if (!$cart_item_key) {
            wp_send_json_error(array('message' => __('Could not add customized product to cart.', 'aakaari')), 500);
        }

        wp_send_json_success(array(
            'message' => __('Customized product added to cart.', 'aakaari'),
            'cart_url' => wc_get_cart_url(),
            'cart_item_key' => $cart_item_key
        ));
    }

    /**
     * Validate add to cart
     */
    public function validate_add_to_cart($passed, $product_id, $quantity) {
        // Only validate if custom design data is present
        if (!isset($_POST[Aakaari_Customizer_Core::CART_CUSTOM_DESIGN])) {
            return $passed;
        }

        $design_data = $_POST[Aakaari_Customizer_Core::CART_CUSTOM_DESIGN];

        // Handle both JSON string and array
        if (is_string($design_data)) {
            $design_data = json_decode(stripslashes($design_data), true);
        }

        if (empty($design_data)) {
            return $passed;
        }

        $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;

        $validator = new Aakaari_Validator();

        // Validate data structure
        $validation = $validator->validate_design_data($design_data);
        if (is_wp_error($validation)) {
            wc_add_notice($validation->get_error_message(), 'error');
            return false;
        }

        // Validate boundaries
        $boundary_validation = $validator->validate_design_boundaries($product_id, $variation_id, $design_data);
        if (is_wp_error($boundary_validation)) {
            wc_add_notice($boundary_validation->get_error_message(), 'error');
            return false;
        }

        return $passed;
    }

    /**
     * Add cart item data
     */
    public function add_cart_item_data($cart_item_data, $product_id) {
        // Check if custom design data is being added
        if (isset($_POST[Aakaari_Customizer_Core::CART_CUSTOM_DESIGN])) {
            $design_data = $_POST[Aakaari_Customizer_Core::CART_CUSTOM_DESIGN];

            // Handle both JSON string and array
            if (is_string($design_data)) {
                $design_data = json_decode(stripslashes($design_data), true);
            }

            if (!empty($design_data)) {
                $validator = new Aakaari_Validator();
                $design_data = $validator->sanitize_design_data($design_data);

                $cart_item_data[Aakaari_Customizer_Core::CART_CUSTOM_DESIGN] = $design_data;
                $cart_item_data[Aakaari_Customizer_Core::CART_UNIQUE_KEY] = md5(microtime() . rand());
            }
        }

        return $cart_item_data;
    }

    /**
     * Display cart item data
     */
    public function display_cart_item_data($item_data, $cart_item) {
        if (!isset($cart_item[Aakaari_Customizer_Core::CART_CUSTOM_DESIGN])) {
            return $item_data;
        }

        $design = $cart_item[Aakaari_Customizer_Core::CART_CUSTOM_DESIGN];

        // Customization indicator
        $item_data[] = array(
            'key' => __('Customization', 'aakaari'),
            'value' => __('Custom Design', 'aakaari'),
            'display' => ''
        );

        // Print type
        if (!empty($design['print_type'])) {
            $print_types = aakaari_customizer()->get_component('print')->get_print_types();
            $print_type_label = isset($print_types[$design['print_type']]) ?
                $print_types[$design['print_type']] :
                ucfirst($design['print_type']);

            $item_data[] = array(
                'key' => __('Print Type', 'aakaari'),
                'value' => $print_type_label,
                'display' => ''
            );
        }

        // Fabric type
        if (!empty($design['fabric_type'])) {
            $fabric_types = aakaari_customizer()->get_component('print')->get_fabric_types();
            $fabric_type_label = isset($fabric_types[$design['fabric_type']]) ?
                $fabric_types[$design['fabric_type']] :
                ucfirst($design['fabric_type']);

            $item_data[] = array(
                'key' => __('Fabric Type', 'aakaari'),
                'value' => $fabric_type_label,
                'display' => ''
            );
        }

        // Color
        if (!empty($design['color'])) {
            $item_data[] = array(
                'key' => __('Color', 'aakaari'),
                'value' => $design['color'],
                'display' => ''
            );
        }

        // Number of uploaded designs
        if (!empty($design['attachment_ids'])) {
            $count = count($design['attachment_ids']);
            $item_data[] = array(
                'key' => __('Uploaded Designs', 'aakaari'),
                'value' => sprintf(_n('%d file', '%d files', $count, 'aakaari'), $count),
                'display' => ''
            );
        }

        return $item_data;
    }

    /**
     * Cart item thumbnail
     */
    public function cart_item_thumbnail($thumbnail, $cart_item, $cart_item_key) {
        if (!isset($cart_item[Aakaari_Customizer_Core::CART_CUSTOM_DESIGN])) {
            return $thumbnail;
        }

        $design = $cart_item[Aakaari_Customizer_Core::CART_CUSTOM_DESIGN];

        // Use preview URL if available
        if (!empty($design['preview_url'])) {
            $thumbnail = '<img src="' . esc_url($design['preview_url']) . '" alt="' . esc_attr__('Custom Design', 'aakaari') . '" class="attachment-woocommerce_thumbnail" />';
            return $thumbnail;
        }

        // Fall back to first uploaded design
        if (!empty($design['attachment_ids'])) {
            $first_attachment = reset($design['attachment_ids']);
            $image_url = wp_get_attachment_image_url($first_attachment, 'woocommerce_thumbnail');

            if ($image_url) {
                $thumbnail = '<img src="' . esc_url($image_url) . '" alt="' . esc_attr__('Custom Design', 'aakaari') . '" class="attachment-woocommerce_thumbnail" />';
            }
        }

        return $thumbnail;
    }

    /**
     * Get custom design from cart item
     */
    public function get_custom_design($cart_item) {
        return isset($cart_item[Aakaari_Customizer_Core::CART_CUSTOM_DESIGN]) ?
            $cart_item[Aakaari_Customizer_Core::CART_CUSTOM_DESIGN] :
            false;
    }
}
