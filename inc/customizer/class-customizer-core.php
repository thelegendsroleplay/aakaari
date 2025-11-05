<?php
/**
 * Customizer Core Class
 * Main orchestrator for the product customization system
 *
 * @package Aakaari_Customizer
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aakaari_Customizer_Core {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Customizer version
     */
    const VERSION = '2.0.0';

    /**
     * Meta key for customizer enabled
     */
    const META_ENABLED = '_customizer_enabled';
    const META_PRINT_AREAS = '_customizer_print_areas';
    const META_MOCKUPS = '_customizer_mockups';
    const META_REQUIRED = '_customizer_required';

    /**
     * Variation meta keys
     */
    const VAR_MOCKUP_ID = '_variation_mockup_attachment_id';
    const VAR_PRINT_AREA = '_variation_print_area';

    /**
     * Cart/Order meta keys
     */
    const CART_CUSTOM_DESIGN = 'custom_design';
    const CART_UNIQUE_KEY = 'unique_key';
    const ORDER_CUSTOM_DESIGN = '_custom_design';
    const ORDER_ATTACHMENTS = '_custom_design_attachments';

    /**
     * Component classes
     */
    private $file_handler;
    private $validator;
    private $cart_handler;
    private $order_handler;
    private $print_area_manager;
    private $mockup_manager;

    /**
     * Get instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_components();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once __DIR__ . '/class-file-handler.php';
        require_once __DIR__ . '/class-validator.php';
        require_once __DIR__ . '/class-cart-handler.php';
        require_once __DIR__ . '/class-order-handler.php';
        require_once __DIR__ . '/class-print-area-manager.php';
        require_once __DIR__ . '/class-mockup-manager.php';
    }

    /**
     * Initialize component instances
     */
    private function init_components() {
        $this->file_handler = new Aakaari_File_Handler();
        $this->validator = new Aakaari_Validator();
        $this->cart_handler = new Aakaari_Cart_Handler();
        $this->order_handler = new Aakaari_Order_Handler();
        $this->print_area_manager = new Aakaari_Print_Area_Manager();
        $this->mockup_manager = new Aakaari_Mockup_Manager();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // AJAX endpoints
        add_action('wp_ajax_aakaari_upload_design', array($this->file_handler, 'ajax_upload_design'));
        add_action('wp_ajax_nopriv_aakaari_upload_design', array($this->file_handler, 'ajax_upload_design'));

        add_action('wp_ajax_aakaari_validate_design', array($this->validator, 'ajax_validate'));
        add_action('wp_ajax_nopriv_aakaari_validate_design', array($this->validator, 'ajax_validate'));

        add_action('wp_ajax_aakaari_add_customized_to_cart', array($this->cart_handler, 'ajax_add_to_cart'));
        add_action('wp_ajax_nopriv_aakaari_add_customized_to_cart', array($this->cart_handler, 'ajax_add_to_cart'));

        // Admin AJAX
        add_action('wp_ajax_aakaari_save_print_area', array($this->print_area_manager, 'ajax_save'));
        add_action('wp_ajax_aakaari_upload_mockup', array($this->mockup_manager, 'ajax_upload'));
        add_action('wp_ajax_aakaari_get_order_design', array($this->order_handler, 'ajax_get_design'));

        // Product admin
        add_action('add_meta_boxes', array($this, 'add_product_meta_boxes'));
        add_action('save_post_product', array($this, 'save_product_meta'), 10, 2);

        // Variation admin
        add_action('woocommerce_product_after_variable_attributes', array($this, 'variation_fields'), 10, 3);
        add_action('woocommerce_save_product_variation', array($this, 'save_variation'), 10, 2);
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!is_product()) {
            return;
        }

        global $product;
        if (!$this->is_customizer_enabled($product->get_id())) {
            return;
        }

        // Fabric.js
        wp_enqueue_script(
            'fabricjs',
            'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js',
            array(),
            '5.3.0',
            true
        );

        // Customizer canvas
        wp_enqueue_script(
            'aakaari-customizer-canvas',
            get_stylesheet_directory_uri() . '/assets/js/customizer-canvas.js',
            array('jquery', 'fabricjs'),
            self::VERSION,
            true
        );

        // Styles
        wp_enqueue_style(
            'aakaari-customizer-frontend',
            get_stylesheet_directory_uri() . '/assets/css/customizer-frontend.css',
            array(),
            self::VERSION
        );

        // Localize data
        $this->localize_product_data($product);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }

        global $post;
        if (!$post || $post->post_type !== 'product') {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_script(
            'aakaari-customizer-admin',
            get_stylesheet_directory_uri() . '/assets/js/customizer-admin.js',
            array('jquery'),
            self::VERSION,
            true
        );

        wp_enqueue_style(
            'aakaari-customizer-admin',
            get_stylesheet_directory_uri() . '/assets/css/customizer-admin.css',
            array(),
            self::VERSION
        );

        wp_localize_script('aakaari-customizer-admin', 'aakaariCustomizerAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aakaari_customizer_admin'),
            'product_id' => $post->ID
        ));
    }

    /**
     * Localize product data for frontend
     */
    private function localize_product_data($product) {
        $product_id = $product->get_id();

        $data = array(
            'product_id' => $product_id,
            'is_variable' => $product->is_type('variable'),
            'mockups' => $this->mockup_manager->get_product_mockups($product_id),
            'print_areas' => $this->print_area_manager->get_product_print_areas($product_id),
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aakaari_customizer'),
            'max_upload_size' => wp_max_upload_size(),
            'allowed_types' => array('image/jpeg', 'image/png', 'image/gif', 'image/webp'),
            'fabric_types' => $this->get_fabric_types(),
            'print_types' => $this->get_print_types(),
            'customizer_required' => get_post_meta($product_id, self::META_REQUIRED, true)
        );

        if ($product->is_type('variable')) {
            $data['variations'] = $this->get_variations_data($product);
        }

        wp_localize_script('aakaari-customizer-canvas', 'aakaariCustomizer', $data);
    }

    /**
     * Get variations data including mockups
     */
    private function get_variations_data($product) {
        $variations_data = array();

        foreach ($product->get_available_variations() as $variation) {
            $variation_id = $variation['variation_id'];

            $variations_data[$variation_id] = array(
                'attributes' => $variation['attributes'],
                'mockup_id' => get_post_meta($variation_id, self::VAR_MOCKUP_ID, true),
                'mockup_url' => '',
                'print_area' => get_post_meta($variation_id, self::VAR_PRINT_AREA, true)
            );

            $mockup_id = $variations_data[$variation_id]['mockup_id'];
            if ($mockup_id) {
                $variations_data[$variation_id]['mockup_url'] = wp_get_attachment_url($mockup_id);
            }
        }

        return $variations_data;
    }

    /**
     * Get fabric types
     */
    private function get_fabric_types() {
        return apply_filters('aakaari_fabric_types', array(
            'cotton' => __('Cotton', 'aakaari'),
            'polyester' => __('Polyester', 'aakaari'),
            'blend' => __('Cotton/Poly Blend', 'aakaari'),
            'silk' => __('Silk', 'aakaari')
        ));
    }

    /**
     * Get print types
     */
    private function get_print_types() {
        return apply_filters('aakaari_print_types', array(
            'dtg' => __('Direct to Garment (DTG)', 'aakaari'),
            'screen' => __('Screen Print', 'aakaari'),
            'vinyl' => __('Vinyl Transfer', 'aakaari'),
            'sublimation' => __('Sublimation', 'aakaari')
        ));
    }

    /**
     * Check if customizer is enabled for product
     */
    public function is_customizer_enabled($product_id) {
        return (bool) get_post_meta($product_id, self::META_ENABLED, true);
    }

    /**
     * Add product meta boxes
     */
    public function add_product_meta_boxes() {
        add_meta_box(
            'aakaari_customizer_settings',
            __('Product Customizer', 'aakaari'),
            array($this, 'render_customizer_meta_box'),
            'product',
            'normal',
            'high'
        );
    }

    /**
     * Render customizer meta box
     */
    public function render_customizer_meta_box($post) {
        wp_nonce_field('aakaari_customizer_meta', 'aakaari_customizer_nonce');

        $enabled = get_post_meta($post->ID, self::META_ENABLED, true);
        $required = get_post_meta($post->ID, self::META_REQUIRED, true);
        $print_areas = get_post_meta($post->ID, self::META_PRINT_AREAS, true);

        include __DIR__ . '/views/admin-meta-box.php';
    }

    /**
     * Save product meta
     */
    public function save_product_meta($post_id, $post) {
        if (!isset($_POST['aakaari_customizer_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['aakaari_customizer_nonce'], 'aakaari_customizer_meta')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save enabled status
        $enabled = isset($_POST['customizer_enabled']) ? 1 : 0;
        update_post_meta($post_id, self::META_ENABLED, $enabled);

        // Save required status
        $required = isset($_POST['customizer_required']) ? 1 : 0;
        update_post_meta($post_id, self::META_REQUIRED, $required);
    }

    /**
     * Variation fields
     */
    public function variation_fields($loop, $variation_data, $variation) {
        $variation_id = $variation->ID;

        $mockup_id = get_post_meta($variation_id, self::VAR_MOCKUP_ID, true);
        $print_area = get_post_meta($variation_id, self::VAR_PRINT_AREA, true);

        include __DIR__ . '/views/variation-fields.php';
    }

    /**
     * Save variation
     */
    public function save_variation($variation_id, $i) {
        if (isset($_POST['variation_mockup_id'][$i])) {
            update_post_meta(
                $variation_id,
                self::VAR_MOCKUP_ID,
                absint($_POST['variation_mockup_id'][$i])
            );
        }

        if (isset($_POST['variation_print_area'][$i])) {
            $print_area = json_decode(stripslashes($_POST['variation_print_area'][$i]), true);
            if ($print_area && is_array($print_area)) {
                update_post_meta($variation_id, self::VAR_PRINT_AREA, $print_area);
            }
        }
    }

    /**
     * Get component
     */
    public function get_component($name) {
        $property = $name . '_handler';
        if ($name === 'print_area' || $name === 'mockup') {
            $property = $name . '_manager';
        }

        return isset($this->$property) ? $this->$property : null;
    }
}

// Initialize
function aakaari_customizer() {
    return Aakaari_Customizer_Core::instance();
}

add_action('plugins_loaded', 'aakaari_customizer');
