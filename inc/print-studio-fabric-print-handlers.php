<?php
/**
 * AJAX Handlers for Fabric and Print Type Management
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX Handler: Save Fabric
 */
function aakaari_ps_save_fabric() {
    error_log('=== aakaari_ps_save_fabric called ===');
    error_log('POST data: ' . print_r($_POST, true));
    
    aakaari_ps_ajax_check();
    
    $fabric_data_json = isset($_POST['fabric_data']) ? wp_unslash($_POST['fabric_data']) : '';
    error_log('Fabric data JSON: ' . $fabric_data_json);
    
    $fabric_data = json_decode($fabric_data_json, true);
    error_log('Decoded fabric data: ' . print_r($fabric_data, true));
    
    if (empty($fabric_data) || empty($fabric_data['name'])) {
        error_log('ERROR: Fabric data validation failed - name is missing');
        wp_send_json_error('Fabric name is required.', 400);
    }
    
    $name = sanitize_text_field($fabric_data['name']);
    $description = isset($fabric_data['description']) ? sanitize_textarea_field($fabric_data['description']) : '';
    $price = isset($fabric_data['price']) ? floatval($fabric_data['price']) : 0;
    
    // Extract term ID from 'fab_123' format
    $fabric_id = 0;
    if (isset($fabric_data['id']) && strpos($fabric_data['id'], 'fab_') === 0) {
        $fabric_id = intval(str_replace('fab_', '', $fabric_data['id']));
    }
    
    $attribute_taxonomy = 'pa_fabric';
    
    if (!taxonomy_exists($attribute_taxonomy)) {
        wp_send_json_error('Fabric attribute does not exist. Please create it first.', 400);
    }
    
    if ($fabric_id > 0) {
        // Update existing
        $result = wp_update_term($fabric_id, $attribute_taxonomy, [
            'name' => $name,
            'description' => $description
        ]);
    } else {
        // Create new
        $result = wp_insert_term($name, $attribute_taxonomy, [
            'description' => $description
        ]);
    }
    
    if (is_wp_error($result)) {
        wp_send_json_error('Failed to save fabric: ' . $result->get_error_message(), 500);
    }
    
    $term_id = $result['term_id'];
    
    // Save meta
    update_term_meta($term_id, 'price', $price);
    update_term_meta($term_id, 'description', $description);
    
    $term = get_term($term_id, $attribute_taxonomy);
    
    wp_send_json_success([
        'id' => 'fab_' . $term->term_id,
        'name' => $term->name,
        'description' => $description,
        'price' => $price,
        'slug' => $term->slug,
    ]);
}
add_action('wp_ajax_aakaari_ps_save_fabric', 'aakaari_ps_save_fabric');

/**
 * AJAX Handler: Delete Fabric
 */
function aakaari_ps_delete_fabric() {
    aakaari_ps_ajax_check();
    
    $fabric_id_raw = isset($_POST['fabric_id']) ? sanitize_text_field($_POST['fabric_id']) : '';
    
    if (strpos($fabric_id_raw, 'fab_') === 0) {
        $fabric_id = intval(str_replace('fab_', '', $fabric_id_raw));
    } else {
        $fabric_id = intval($fabric_id_raw);
    }
    
    if ($fabric_id <= 0) {
        wp_send_json_error('Invalid fabric ID.', 400);
    }
    
    $result = wp_delete_term($fabric_id, 'pa_fabric');
    
    if (is_wp_error($result)) {
        wp_send_json_error('Failed to delete fabric: ' . $result->get_error_message(), 500);
    }
    
    if ($result === false) {
        wp_send_json_error('Fabric not found or could not be deleted.', 404);
    }
    
    wp_send_json_success(true);
}
add_action('wp_ajax_aakaari_ps_delete_fabric', 'aakaari_ps_delete_fabric');

/**
 * AJAX Handler: Save Print Type
 */
function aakaari_ps_save_print_type() {
    aakaari_ps_ajax_check();
    
    $print_type_data_json = isset($_POST['print_type_data']) ? wp_unslash($_POST['print_type_data']) : '';
    $print_type_data = json_decode($print_type_data_json, true);
    
    if (empty($print_type_data) || empty($print_type_data['name'])) {
        wp_send_json_error('Print type name is required.', 400);
    }
    
    $name = sanitize_text_field($print_type_data['name']);
    $description = isset($print_type_data['description']) ? sanitize_textarea_field($print_type_data['description']) : '';
    $pricing_model = isset($print_type_data['pricingModel']) ? sanitize_text_field($print_type_data['pricingModel']) : 'fixed';
    $price = isset($print_type_data['price']) ? floatval($print_type_data['price']) : 0;
    
    // Extract term ID from 'pt_123' format
    $print_type_id = 0;
    if (isset($print_type_data['id']) && strpos($print_type_data['id'], 'pt_') === 0) {
        $print_type_id = intval(str_replace('pt_', '', $print_type_data['id']));
    }
    
    $attribute_taxonomy = 'pa_print_type';
    
    if (!taxonomy_exists($attribute_taxonomy)) {
        wp_send_json_error('Print Type attribute does not exist. Please create it first.', 400);
    }
    
    if ($print_type_id > 0) {
        // Update existing
        $result = wp_update_term($print_type_id, $attribute_taxonomy, [
            'name' => $name,
            'description' => $description
        ]);
    } else {
        // Create new
        $result = wp_insert_term($name, $attribute_taxonomy, [
            'description' => $description
        ]);
    }
    
    if (is_wp_error($result)) {
        wp_send_json_error('Failed to save print type: ' . $result->get_error_message(), 500);
    }
    
    $term_id = $result['term_id'];
    
    // Save meta
    update_term_meta($term_id, 'pricing_model', $pricing_model);
    update_term_meta($term_id, 'price', $price);
    update_term_meta($term_id, 'description', $description);
    
    $term = get_term($term_id, $attribute_taxonomy);
    
    wp_send_json_success([
        'id' => 'pt_' . $term->term_id,
        'name' => $term->name,
        'description' => $description,
        'pricingModel' => $pricing_model,
        'price' => $price,
        'slug' => $term->slug,
    ]);
}
add_action('wp_ajax_aakaari_ps_save_print_type', 'aakaari_ps_save_print_type');

/**
 * AJAX Handler: Delete Print Type
 */
function aakaari_ps_delete_print_type() {
    aakaari_ps_ajax_check();
    
    $print_type_id_raw = isset($_POST['print_type_id']) ? sanitize_text_field($_POST['print_type_id']) : '';
    
    if (strpos($print_type_id_raw, 'pt_') === 0) {
        $print_type_id = intval(str_replace('pt_', '', $print_type_id_raw));
    } else {
        $print_type_id = intval($print_type_id_raw);
    }
    
    if ($print_type_id <= 0) {
        wp_send_json_error('Invalid print type ID.', 400);
    }
    
    $result = wp_delete_term($print_type_id, 'pa_print_type');
    
    if (is_wp_error($result)) {
        wp_send_json_error('Failed to delete print type: ' . $result->get_error_message(), 500);
    }
    
    if ($result === false) {
        wp_send_json_error('Print type not found or could not be deleted.', 404);
    }
    
    wp_send_json_success(true);
}
add_action('wp_ajax_aakaari_ps_delete_print_type', 'aakaari_ps_delete_print_type');

/**
 * AJAX Handler: Save Size
 */
function aakaari_ps_save_size() {
    aakaari_ps_ajax_check();
    
    $size_data_json = isset($_POST['size_data']) ? wp_unslash($_POST['size_data']) : '';
    $size_data = json_decode($size_data_json, true);
    
    if (empty($size_data) || empty($size_data['name'])) {
        wp_send_json_error('Size name is required.', 400);
    }
    
    $name = sanitize_text_field($size_data['name']);
    
    // Extract term ID from 'size_123' format
    $size_id = 0;
    if (isset($size_data['id']) && strpos($size_data['id'], 'size_') === 0) {
        $size_id = intval(str_replace('size_', '', $size_data['id']));
    }
    
    $attribute_taxonomy = 'pa_size';
    
    // Ensure the attribute taxonomy exists (create if it doesn't)
    if (!taxonomy_exists($attribute_taxonomy)) {
        // Create the attribute if it doesn't exist
        $attribute_id = wc_create_attribute([
            'name' => 'Size',
            'slug' => 'size',
            'type' => 'select',
            'order_by' => 'name',
            'has_archives' => false,
        ]);
        
        if (is_wp_error($attribute_id)) {
            wp_send_json_error('Size attribute does not exist and could not be created.', 400);
        }
        
        // Register the taxonomy
        register_taxonomy($attribute_taxonomy, 'product', []);
    }
    
    if ($size_id > 0) {
        // Update existing
        $result = wp_update_term($size_id, $attribute_taxonomy, [
            'name' => $name
        ]);
    } else {
        // Create new
        $result = wp_insert_term($name, $attribute_taxonomy);
    }
    
    if (is_wp_error($result)) {
        wp_send_json_error('Failed to save size: ' . $result->get_error_message(), 500);
    }
    
    $term_id = $result['term_id'];
    $term = get_term($term_id, $attribute_taxonomy);
    
    wp_send_json_success([
        'id' => 'size_' . $term->term_id,
        'name' => $term->name,
        'slug' => $term->slug,
    ]);
}
add_action('wp_ajax_aakaari_ps_save_size', 'aakaari_ps_save_size');

/**
 * AJAX Handler: Delete Size
 */
function aakaari_ps_delete_size() {
    aakaari_ps_ajax_check();
    
    $size_id_raw = isset($_POST['size_id']) ? sanitize_text_field($_POST['size_id']) : '';
    
    if (strpos($size_id_raw, 'size_') === 0) {
        $size_id = intval(str_replace('size_', '', $size_id_raw));
    } else {
        $size_id = intval($size_id_raw);
    }
    
    if ($size_id <= 0) {
        wp_send_json_error('Invalid size ID.', 400);
    }
    
    $result = wp_delete_term($size_id, 'pa_size');
    
    if (is_wp_error($result)) {
        wp_send_json_error('Failed to delete size: ' . $result->get_error_message(), 500);
    }
    
    if ($result === false) {
        wp_send_json_error('Size not found or could not be deleted.', 404);
    }
    
    wp_send_json_success(true);
}
add_action('wp_ajax_aakaari_ps_delete_size', 'aakaari_ps_delete_size');

/**
 * AJAX Handler: Save Color
 */
function aakaari_ps_save_color() {
    aakaari_ps_ajax_check();
    
    $color_data_json = isset($_POST['color_data']) ? wp_unslash($_POST['color_data']) : '';
    $color_data = json_decode($color_data_json, true);
    
    if (empty($color_data) || empty($color_data['name'])) {
        wp_send_json_error('Color name is required.', 400);
    }
    
    $name = sanitize_text_field($color_data['name']);
    $hex = isset($color_data['hex']) ? sanitize_hex_color($color_data['hex']) : '';
    
    // Extract term ID from 'color_123' format
    $color_id = 0;
    if (isset($color_data['id']) && strpos($color_data['id'], 'color_') === 0) {
        $color_id = intval(str_replace('color_', '', $color_data['id']));
    }
    
    $attribute_taxonomy = 'pa_color';
    
    // Ensure the attribute taxonomy exists (create if it doesn't)
    if (!taxonomy_exists($attribute_taxonomy)) {
        // Create the attribute if it doesn't exist
        $attribute_id = wc_create_attribute([
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'select',
            'order_by' => 'name',
            'has_archives' => false,
        ]);
        
        if (is_wp_error($attribute_id)) {
            wp_send_json_error('Color attribute does not exist and could not be created.', 400);
        }
        
        // Register the taxonomy
        register_taxonomy($attribute_taxonomy, 'product', []);
    }
    
    if ($color_id > 0) {
        // Update existing
        $result = wp_update_term($color_id, $attribute_taxonomy, [
            'name' => $name
        ]);
    } else {
        // Create new
        $result = wp_insert_term($name, $attribute_taxonomy);
    }
    
    if (is_wp_error($result)) {
        wp_send_json_error('Failed to save color: ' . $result->get_error_message(), 500);
    }
    
    $term_id = $result['term_id'];
    
    // Save hex color code as term meta
    if (!empty($hex)) {
        update_term_meta($term_id, 'product_attribute_color', $hex);
        update_term_meta($term_id, 'hex_code', $hex);
    }
    
    $term = get_term($term_id, $attribute_taxonomy);
    
    // Get hex from meta or generate from name
    $hex_meta = get_term_meta($term_id, 'product_attribute_color', true);
    if (empty($hex_meta)) {
        $hex_meta = get_term_meta($term_id, 'hex_code', true);
    }
    if (empty($hex_meta)) {
        $hex_meta = aakaari_ps_color_name_to_hex($term->name);
    }
    
    wp_send_json_success([
        'id' => 'color_' . $term->term_id,
        'name' => $term->name,
        'hex' => sanitize_hex_color($hex_meta),
        'slug' => $term->slug,
    ]);
}
add_action('wp_ajax_aakaari_ps_save_color', 'aakaari_ps_save_color');

/**
 * AJAX Handler: Delete Color
 */
function aakaari_ps_delete_color() {
    aakaari_ps_ajax_check();
    
    $color_id_raw = isset($_POST['color_id']) ? sanitize_text_field($_POST['color_id']) : '';
    
    if (strpos($color_id_raw, 'color_') === 0) {
        $color_id = intval(str_replace('color_', '', $color_id_raw));
    } else {
        $color_id = intval($color_id_raw);
    }
    
    if ($color_id <= 0) {
        wp_send_json_error('Invalid color ID.', 400);
    }
    
    $result = wp_delete_term($color_id, 'pa_color');
    
    if (is_wp_error($result)) {
        wp_send_json_error('Failed to delete color: ' . $result->get_error_message(), 500);
    }
    
    if ($result === false) {
        wp_send_json_error('Color not found or could not be deleted.', 404);
    }
    
    wp_send_json_success(true);
}
add_action('wp_ajax_aakaari_ps_delete_color', 'aakaari_ps_delete_color');