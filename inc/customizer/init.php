<?php
/**
 * Customizer Initialization
 * Loads and initializes the product customizer system
 *
 * @package Aakaari_Customizer
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define customizer constants
if (!defined('AAKAARI_CUSTOMIZER_VERSION')) {
    define('AAKAARI_CUSTOMIZER_VERSION', '2.0.0');
}

if (!defined('AAKAARI_CUSTOMIZER_DIR')) {
    define('AAKAARI_CUSTOMIZER_DIR', __DIR__);
}

/**
 * Load customizer core
 */
function aakaari_customizer_init() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>';
            echo __('Aakaari Product Customizer requires WooCommerce to be installed and active.', 'aakaari');
            echo '</p></div>';
        });
        return;
    }

    // Load core class
    require_once AAKAARI_CUSTOMIZER_DIR . '/class-customizer-core.php';

    // Initialize
    aakaari_customizer();
}
add_action('after_setup_theme', 'aakaari_customizer_init');

/**
 * Activation hook
 */
function aakaari_customizer_activate() {
    // Flush rewrite rules
    flush_rewrite_rules();

    // Create upload directory for customizations
    $upload_dir = wp_upload_dir();
    $customizer_dir = $upload_dir['basedir'] . '/customizations';

    if (!file_exists($customizer_dir)) {
        wp_mkdir_p($customizer_dir);

        // Add .htaccess for protection
        $htaccess = $customizer_dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, 'Options -Indexes');
        }

        // Add index.php
        $index = $customizer_dir . '/index.php';
        if (!file_exists($index)) {
            file_put_contents($index, '<?php // Silence is golden');
        }
    }
}
register_activation_hook(__FILE__, 'aakaari_customizer_activate');

/**
 * Helper function to check if customizer is enabled for product
 */
function aakaari_is_customizer_product($product_id = 0) {
    if (!$product_id) {
        global $product;
        if ($product) {
            $product_id = $product->get_id();
        }
    }

    if (!$product_id) {
        return false;
    }

    return aakaari_customizer()->is_customizer_enabled($product_id);
}

/**
 * Helper to get customizer component
 */
function aakaari_get_customizer_component($name) {
    return aakaari_customizer()->get_component($name);
}
