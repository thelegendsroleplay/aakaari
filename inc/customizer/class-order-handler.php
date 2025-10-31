<?php
/**
 * Order Handler Class
 * Manages persistence and display of customization data in orders
 *
 * @package Aakaari_Customizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aakaari_Order_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        // WooCommerce hooks
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_to_order'), 10, 4);
        add_filter('woocommerce_order_item_display_meta_key', array($this, 'format_meta_key'), 10, 3);
        add_filter('woocommerce_order_item_display_meta_value', array($this, 'format_meta_value'), 10, 3);
        add_filter('woocommerce_order_item_get_formatted_meta_data', array($this, 'filter_meta_data'), 10, 2);

        // Admin order display
        add_action('woocommerce_admin_order_item_headers', array($this, 'admin_order_item_headers'));
        add_action('woocommerce_admin_order_item_values', array($this, 'admin_order_item_values'), 10, 3);
    }

    /**
     * Save customization to order
     */
    public function save_to_order($item, $cart_item_key, $values, $order) {
        if (!isset($values[Aakaari_Customizer_Core::CART_CUSTOM_DESIGN])) {
            return;
        }

        $design_data = $values[Aakaari_Customizer_Core::CART_CUSTOM_DESIGN];

        // Save complete design data
        $item->add_meta_data(Aakaari_Customizer_Core::ORDER_CUSTOM_DESIGN, $design_data, true);

        // Save attachment IDs separately for easy access
        if (!empty($design_data['attachment_ids'])) {
            $item->add_meta_data(Aakaari_Customizer_Core::ORDER_ATTACHMENTS, $design_data['attachment_ids'], true);
        }

        // Add individual meta for better display
        if (!empty($design_data['print_type'])) {
            $item->add_meta_data('_customizer_print_type', $design_data['print_type'], true);
        }

        if (!empty($design_data['fabric_type'])) {
            $item->add_meta_data('_customizer_fabric_type', $design_data['fabric_type'], true);
        }

        if (!empty($design_data['color'])) {
            $item->add_meta_data('_customizer_color', $design_data['color'], true);
        }

        // Add a flag for easy identification
        $item->add_meta_data('_is_customized', 'yes', true);
    }

    /**
     * Format meta key for display
     */
    public function format_meta_key($display_key, $meta, $item) {
        switch ($meta->key) {
            case Aakaari_Customizer_Core::ORDER_CUSTOM_DESIGN:
                return __('Customization', 'aakaari');

            case Aakaari_Customizer_Core::ORDER_ATTACHMENTS:
                return __('Design Files', 'aakaari');

            case '_customizer_print_type':
                return __('Print Type', 'aakaari');

            case '_customizer_fabric_type':
                return __('Fabric Type', 'aakaari');

            case '_customizer_color':
                return __('Color', 'aakaari');

            case '_is_customized':
                return false; // Hide this meta
        }

        return $display_key;
    }

    /**
     * Format meta value for display
     */
    public function format_meta_value($display_value, $meta, $item) {
        switch ($meta->key) {
            case Aakaari_Customizer_Core::ORDER_CUSTOM_DESIGN:
                $design = maybe_unserialize($meta->value);
                if (is_array($design)) {
                    return $this->render_design_summary($design);
                }
                break;

            case Aakaari_Customizer_Core::ORDER_ATTACHMENTS:
                $attachments = maybe_unserialize($meta->value);
                if (is_array($attachments)) {
                    return $this->render_attachments($attachments);
                }
                break;

            case '_customizer_print_type':
                $print_types = aakaari_customizer()->get_print_types();
                return isset($print_types[$meta->value]) ? $print_types[$meta->value] : $meta->value;

            case '_customizer_fabric_type':
                $fabric_types = aakaari_customizer()->get_fabric_types();
                return isset($fabric_types[$meta->value]) ? $fabric_types[$meta->value] : $meta->value;
        }

        return $display_value;
    }

    /**
     * Filter meta data to hide internal keys
     */
    public function filter_meta_data($formatted_meta, $item) {
        $filtered = array();

        foreach ($formatted_meta as $meta) {
            // Hide internal meta keys
            if (in_array($meta->key, array('_is_customized', Aakaari_Customizer_Core::CART_UNIQUE_KEY))) {
                continue;
            }

            $filtered[] = $meta;
        }

        return $filtered;
    }

    /**
     * Render design summary
     */
    private function render_design_summary($design) {
        $output = '<div class="customizer-design-summary">';
        $output .= '<p><strong>' . __('Custom Design', 'aakaari') . '</strong></p>';

        // Preview image
        if (!empty($design['preview_url'])) {
            $output .= '<p><a href="' . esc_url($design['preview_url']) . '" target="_blank">';
            $output .= '<img src="' . esc_url($design['preview_url']) . '" style="max-width:150px; height:auto; border:1px solid #ddd; border-radius:4px;" alt="' . esc_attr__('Design Preview', 'aakaari') . '" />';
            $output .= '</a></p>';
        }

        // Details
        if (!empty($design['print_type'])) {
            $print_types = aakaari_customizer()->get_print_types();
            $print_type_label = isset($print_types[$design['print_type']]) ?
                $print_types[$design['print_type']] :
                ucfirst($design['print_type']);
            $output .= '<p><strong>' . __('Print Type:', 'aakaari') . '</strong> ' . esc_html($print_type_label) . '</p>';
        }

        if (!empty($design['fabric_type'])) {
            $fabric_types = aakaari_customizer()->get_fabric_types();
            $fabric_type_label = isset($fabric_types[$design['fabric_type']]) ?
                $fabric_types[$design['fabric_type']] :
                ucfirst($design['fabric_type']);
            $output .= '<p><strong>' . __('Fabric:', 'aakaari') . '</strong> ' . esc_html($fabric_type_label) . '</p>';
        }

        if (!empty($design['color'])) {
            $output .= '<p><strong>' . __('Color:', 'aakaari') . '</strong> ' . esc_html($design['color']) . '</p>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Render attachments
     */
    private function render_attachments($attachments) {
        $output = '<div class="customizer-attachments">';

        foreach ($attachments as $attachment_id) {
            $attachment_url = wp_get_attachment_url($attachment_id);
            $thumb_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');

            if ($attachment_url) {
                $output .= '<div style="display:inline-block; margin:5px;">';

                if ($thumb_url) {
                    $output .= '<a href="' . esc_url($attachment_url) . '" target="_blank">';
                    $output .= '<img src="' . esc_url($thumb_url) . '" style="max-width:80px; height:auto; border:1px solid #ddd; border-radius:4px;" />';
                    $output .= '</a><br>';
                }

                $output .= '<a href="' . esc_url($attachment_url) . '" download class="button" style="font-size:11px; padding:3px 8px; margin-top:5px;">' . __('Download', 'aakaari') . '</a>';
                $output .= '</div>';
            }
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Admin order item headers
     */
    public function admin_order_item_headers() {
        echo '<th class="customizer-column">' . __('Customization', 'aakaari') . '</th>';
    }

    /**
     * Admin order item values
     */
    public function admin_order_item_values($product, $item, $item_id) {
        echo '<td class="customizer-column">';

        $design = $item->get_meta(Aakaari_Customizer_Core::ORDER_CUSTOM_DESIGN);
        if ($design) {
            echo $this->render_admin_design_cell($design, $item);
        } else {
            echo '<span style="color:#999;">—</span>';
        }

        echo '</td>';
    }

    /**
     * Render admin design cell
     */
    private function render_admin_design_cell($design, $item) {
        $output = '<div class="customizer-admin-cell">';

        // Preview thumbnail
        if (!empty($design['preview_url'])) {
            $output .= '<a href="' . esc_url($design['preview_url']) . '" target="_blank">';
            $output .= '<img src="' . esc_url($design['preview_url']) . '" style="max-width:60px; height:auto; border:1px solid #ddd; border-radius:3px; margin-bottom:5px;" />';
            $output .= '</a><br>';
        }

        // Download button
        if (!empty($design['attachment_ids'])) {
            $output .= '<button type="button" class="button button-small download-design-files" data-item-id="' . esc_attr($item->get_id()) . '">';
            $output .= __('Download Files', 'aakaari');
            $output .= '</button>';
        }

        // Quick info
        $info = array();
        if (!empty($design['print_type'])) {
            $info[] = '<small>' . esc_html(ucfirst($design['print_type'])) . '</small>';
        }
        if (!empty($design['color'])) {
            $info[] = '<small>' . esc_html($design['color']) . '</small>';
        }

        if (!empty($info)) {
            $output .= '<br>' . implode(' | ', $info);
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * AJAX get design files
     */
    public function ajax_get_design() {
        check_ajax_referer('aakaari_customizer_admin', 'nonce');

        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'aakaari')), 403);
        }

        $item_id = isset($_POST['item_id']) ? absint($_POST['item_id']) : 0;

        if (!$item_id) {
            wp_send_json_error(array('message' => __('Invalid item ID.', 'aakaari')), 400);
        }

        $item = new WC_Order_Item_Product($item_id);
        $design = $item->get_meta(Aakaari_Customizer_Core::ORDER_CUSTOM_DESIGN);

        if (!$design || empty($design['attachment_ids'])) {
            wp_send_json_error(array('message' => __('No design files found.', 'aakaari')), 404);
        }

        $files = array();
        foreach ($design['attachment_ids'] as $attachment_id) {
            $attachment_url = wp_get_attachment_url($attachment_id);
            if ($attachment_url) {
                $files[] = array(
                    'id' => $attachment_id,
                    'url' => $attachment_url,
                    'filename' => basename(get_attached_file($attachment_id))
                );
            }
        }

        wp_send_json_success(array('files' => $files));
    }

    /**
     * Get order customization data
     */
    public function get_order_customization($order, $item_id) {
        $items = $order->get_items();

        if (!isset($items[$item_id])) {
            return false;
        }

        $item = $items[$item_id];
        return $item->get_meta(Aakaari_Customizer_Core::ORDER_CUSTOM_DESIGN);
    }

    /**
     * Check if order item is customized
     */
    public function is_customized($item) {
        return $item->get_meta('_is_customized') === 'yes';
    }
}
