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
