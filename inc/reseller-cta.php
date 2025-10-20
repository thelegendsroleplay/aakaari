<?php
/**
 * Reseller CTA Settings
 * 
 * This file contains:
 * - Reseller CTA settings for homepage
 * - Admin settings integration
 * - CTA styles loading
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add Reseller CTA Settings to Theme Options
 */
function theme_reseller_settings() {
    // Add a section to the Customizer
    add_settings_section(
        'reseller_settings_section',
        'Reseller Settings',
        'reseller_settings_callback',
        'general'
    );
    
    // Add a field for the Reseller Page
    add_settings_field(
        'reseller_page_id',
        'Reseller Page',
        'reseller_page_callback',
        'general',
        'reseller_settings_section'
    );
    
    // Register the setting
    register_setting('general', 'reseller_page_id');
}
add_action('admin_init', 'theme_reseller_settings');

/**
 * Section callback
 */
function reseller_settings_callback() {
    echo '<p>Settings for the Reseller Call-to-Action section on the front page.</p>';
}

/**
 * Field callback for Reseller Page selector
 */
function reseller_page_callback() {
    $reseller_page_id = get_option('reseller_page_id');
    
    wp_dropdown_pages(array(
        'name' => 'reseller_page_id',
        'show_option_none' => 'Select a page',
        'option_none_value' => '0',
        'selected' => $reseller_page_id,
    ));
    echo '<p class="description">Select the page where users will go when clicking "Become a Reseller Today"</p>';
}

/**
 * Enqueue styles for Reseller CTA
 */
function enqueue_reseller_cta_styles() {
    if (is_front_page()) {
        wp_enqueue_style(
            'reseller-cta-styles',
            get_template_directory_uri() . '/assets/css/reseller-cta.css',
            array(),
            '1.0.0'
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_reseller_cta_styles');