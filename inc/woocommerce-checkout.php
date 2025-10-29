<?php
/**
 * Aakaari Checkout - WooCommerce Checkout Functions
 * Complete implementation for the custom multi-step checkout process
 *
 * @package Aakaari
 * @subpackage WooCommerce
 */

defined('ABSPATH') || exit;

/**
 * Class Aakaari_Checkout
 * Contains all functionality for the multi-step checkout process
 */
class Aakaari_Checkout {
    
    /**
     * Initialize hooks
     */
    public static function init() {
        $instance = new self();
        $instance->hooks();
    }
/**
 * Force shipping calculations to work with billing address
 */
public function fix_shipping_calculations() {
    // Copy billing to shipping when "Ship to different address" is not checked
    add_filter('woocommerce_ship_to_different_address_checked', '__return_false', 99);
    
    // Force shipping calculations to use billing address if shipping is empty
    add_filter('woocommerce_shipping_packages', function($packages) {
        // If we're not on checkout page, return packages unchanged
        if (!is_checkout()) {
            return $packages;
        }
        
        // Modify each package to ensure address data is set
        foreach ($packages as $key => $package) {
            // If any of the destination fields are empty, copy from billing
            if (empty($package['destination']['country']) || 
                empty($package['destination']['postcode']) || 
                empty($package['destination']['state']) || 
                empty($package['destination']['city'])) {
                
                // Get billing data
                $customer = WC()->customer;
                if ($customer) {
                    $packages[$key]['destination']['country'] = $customer->get_billing_country();
                    $packages[$key]['destination']['state'] = $customer->get_billing_state();
                    $packages[$key]['destination']['postcode'] = $customer->get_billing_postcode();
                    $packages[$key]['destination']['city'] = $customer->get_billing_city();
                    $packages[$key]['destination']['address'] = $customer->get_billing_address();
                    $packages[$key]['destination']['address_2'] = $customer->get_billing_address_2();
                }
            }
        }
        
        return $packages;
    }, 100);
    
    // Set shipping to match billing
    add_filter('woocommerce_checkout_get_value', function($value, $input) {
        // Only process shipping fields
        if (strpos($input, 'shipping_') !== 0) {
            return $value;
        }
        
        // If shipping field is empty, use billing
        if (empty($value) && is_checkout()) {
            $billing_field = str_replace('shipping_', 'billing_', $input);
            
            // Check if the shipping field exists and is empty
            if (isset($_POST[$input]) && empty($_POST[$input]) && isset($_POST[$billing_field])) {
                return $_POST[$billing_field];
            }
            
            // Check if we have the billing field in the customer data
            $customer = WC()->customer;
            if ($customer) {
                $billing_getter = 'get_' . str_replace('shipping_', '', $billing_field);
                
                // Check if the getter method exists on the customer object
                if (method_exists($customer, $billing_getter)) {
                    return $customer->$billing_getter();
                }
            }
        }
        
        return $value;
    }, 10, 2);
    
    // Ensure customer shipping address matches billing address
    add_action('woocommerce_checkout_update_order_review', function($post_data) {
        parse_str($post_data, $data);
        
        // Set billing data to customer
        $customer = WC()->customer;
        if ($customer && isset($data['billing_country'])) {
            $customer->set_billing_country($data['billing_country']);
            $customer->set_billing_state(isset($data['billing_state']) ? $data['billing_state'] : '');
            $customer->set_billing_postcode(isset($data['billing_postcode']) ? $data['billing_postcode'] : '');
            $customer->set_billing_city(isset($data['billing_city']) ? $data['billing_city'] : '');
            
            // If "ship to different address" is not checked, copy billing to shipping
            if (empty($data['ship_to_different_address'])) {
                $customer->set_shipping_country($data['billing_country']);
                $customer->set_shipping_state(isset($data['billing_state']) ? $data['billing_state'] : '');
                $customer->set_shipping_postcode(isset($data['billing_postcode']) ? $data['billing_postcode'] : '');
                $customer->set_shipping_city(isset($data['billing_city']) ? $data['billing_city'] : '');
            }
        }
    }, 10);
    
    // Force shipping calculation on checkout load
    add_action('woocommerce_checkout_update_order_review', function() {
        // Clear shipping cache
        $packages = WC()->shipping()->get_packages();
        foreach ($packages as $package_key => $package) {
            WC()->session->set('shipping_for_package_' . $package_key, false);
        }
    }, 99);
}
    /**
     * Register all hooks
     */
    public function hooks() {
        // Make custom page templates work with WooCommerce checkout
        add_filter('woocommerce_is_checkout', [$this, 'recognize_custom_checkout']);
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_checkout_assets'], 99);
        
        // Add body class for checkout page
        add_filter('body_class', [$this, 'add_checkout_body_class']);
        
        // Use custom checkout template
        add_filter('woocommerce_locate_template', [$this, 'use_custom_checkout_template'], 10, 3);
        
        // Remove default coupon form (we have it in the summary)
        remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
        
        // Field validation
        add_action('woocommerce_checkout_process', [$this, 'verify_checkout_fields']);
        
        // Debug helpers (only active when WP_DEBUG is true)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('woocommerce_checkout_process', [$this, 'log_checkout_process']);
            add_action('woocommerce_after_checkout_validation', [$this, 'log_checkout_validation'], 10, 2);
            add_action('woocommerce_checkout_order_created', [$this, 'log_order_created']);
            add_action('woocommerce_checkout_order_processed', [$this, 'log_order_processing'], 10, 3);
        }
        
        // Fix payment process issues
        add_action('woocommerce_checkout_before_customer_details', [$this, 'add_payment_process_fix']);
        
        // Handle AJAX payment refreshes better
        add_action('wp_ajax_woocommerce_checkout', [$this, 'before_ajax_checkout'], 5);
        add_action('wp_ajax_nopriv_woocommerce_checkout', [$this, 'before_ajax_checkout'], 5);
        
        // Fix nonce validation issue
        add_filter('woocommerce_order_button_html', [$this, 'fix_checkout_nonce']);
        
        // Add debugging tools in footer for development environments
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_footer', [$this, 'add_checkout_debugging']);
        }
        
        // Ensure shipping methods are calculated properly
        $this->ensure_shipping_methods();
        
        // Fix payment nonce issues
        $this->fix_payment_nonce();
            // Fix shipping calculations - ADD THIS LINE
        $this->fix_shipping_calculations();
    }
    
    /**
     * Recognize custom page templates as checkout pages
     */
    public function recognize_custom_checkout($is_checkout) {
        if (is_page_template('template-checkout.php')) {
            return true;
        }
        return $is_checkout;
    }
    
    /**
     * Enqueue checkout assets only on checkout pages
     */
    public function enqueue_checkout_assets() {
        $is_aakaari_checkout = is_checkout() || is_page_template('template-checkout.php');
        if (!$is_aakaari_checkout || is_order_received_page()) {
            return;
        }
        
        // Fonts + icons
        wp_enqueue_style(
            'aakaari-inter',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            [],
            null
        );
        
        wp_enqueue_script(
            'aakaari-lucide',
            'https://unpkg.com/lucide@latest/dist/lucide.min.js',
            [],
            null,
            true
        );
        
        // Ensure WooCommerce scripts are loaded first
        wp_enqueue_script('jquery');
        wp_enqueue_script('wc-checkout');
        wp_enqueue_script('wc-address-i18n');
        wp_enqueue_script('wc-country-select');
        
        // Checkout CSS 
        wp_enqueue_style(
            'aakaari-checkout',
            get_stylesheet_directory_uri() . '/assets/css/checkout.css',
            [], 
            filemtime(get_stylesheet_directory() . '/assets/css/checkout.css')
        );
        
        // Create necessary JS files if they don't exist
        $this->ensure_checkout_files_exist();
        
        // Main checkout JS with enhanced dependency management
        wp_enqueue_script(
            'aakaari-checkout',
            get_stylesheet_directory_uri() . '/assets/js/checkout.js',
            ['jquery', 'wc-checkout', 'wc-address-i18n', 'wc-country-select'],
            filemtime(get_stylesheet_directory() . '/assets/js/checkout.js'),
            true
        );
        
        // Add shipping fix script
        wp_enqueue_script(
            'aakaari-checkout-shipping-fix',
            get_stylesheet_directory_uri() . '/assets/js/checkout-shipping-fix.js',
            ['jquery', 'aakaari-checkout'],
            filemtime(get_stylesheet_directory() . '/assets/js/checkout-shipping-fix.js'),
            time() // Use timestamp to bypass caching
        );
        
        // Data for JS
        wp_localize_script('aakaari-checkout', 'aakaariCheckout', [
            'cartUrl' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : '',
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aakaari-checkout'),
            'processNonce' => wp_create_nonce('woocommerce-process_checkout'),
            'isDebug' => defined('WP_DEBUG') && WP_DEBUG,
        ]);
        
        // Add debug mode if needed
        if (isset($_GET['checkout_debug']) && $_GET['checkout_debug'] == '1') {
            add_action('wp_footer', [$this, 'add_checkout_debug_mode']);
        }
    }
    
    /**
     * Add checkout body class
     */
    public function add_checkout_body_class($classes) {
        if ((is_checkout() || is_page_template('template-checkout.php')) && !is_order_received_page()) {
            $classes[] = 'aak-checkout-page';
        }
        return $classes;
    }
    
    /**
     * Use custom checkout template
     */
    public function use_custom_checkout_template($template, $template_name, $template_path) {
        if ($template_name !== 'checkout/form-checkout.php') {
            return $template;
        }
        
        $theme_file = get_stylesheet_directory() . '/woocommerce/checkout/form-checkout.php';
        if (file_exists($theme_file)) {
            return $theme_file;
        }
        
        return $template;
    }
    
    /**
     * Add payment process fix to ensure gateways initialize correctly
     */
    public function add_payment_process_fix() {
        ?>
        <script>
        jQuery(function($) {
            if (typeof wc_checkout_params === 'undefined') {
                return false;
            }
            
            // Re-trigger payment method selection after checkout updates
            $(document.body).on('updated_checkout', function() {
                setTimeout(function() {
                    $(document.body).trigger('payment_method_selected');
                    $('input[name="payment_method"]:checked').trigger('click');
                }, 300);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Before AJAX checkout action to ensure proper handling
     */
    public function before_ajax_checkout() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Aakaari Checkout: AJAX update triggered - ' . (isset($_POST['payment_method']) ? 'Payment: ' . $_POST['payment_method'] : 'No payment method'));
        }
    }
    
    /**
     * Log checkout process
     */
    public function log_checkout_process() {
        $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'not set';
        error_log('Aakaari Checkout: Process started - Payment method: ' . $payment_method);
    }
    
    /**
     * Verify required checkout fields
     */
    public function verify_checkout_fields() {
        if (!isset($_POST['billing_email']) || empty($_POST['billing_email'])) {
            wc_add_notice('Email address is required', 'error');
        }
        
        // Check if payment method is selected when needed
        if (WC()->cart->needs_payment()) {
            $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
            if (!empty($available_gateways)) {
                $chosen_payment_method = isset($_POST['payment_method']) ? wc_clean($_POST['payment_method']) : '';
                if (empty($chosen_payment_method)) {
                    wc_add_notice('Please select a payment method', 'error');
                }
            }
        }
    }
    
    /**
     * Log checkout validation
     */
    public function log_checkout_validation($data, $errors) {
        if ($errors->get_error_messages()) {
            error_log('Aakaari Checkout: Validation errors - ' . print_r($errors->get_error_messages(), true));
        }
    }
    
    /**
     * Log order created
     */
    public function log_order_created($order) {
        error_log('Aakaari Checkout: Order created - ID: ' . $order->get_id() . ', Status: ' . $order->get_status());
    }
    
    /**
     * Log order processing for debugging
     */
    public function log_order_processing($order_id, $posted_data, $order) {
        error_log('Order ' . $order_id . ' processed with status: ' . $order->get_status());
        error_log('Payment method: ' . $order->get_payment_method());
    }
    
    /**
     * Fix nonce validation issue
     */
    public function fix_checkout_nonce($button_html) {
        $nonce = wp_create_nonce('woocommerce-process_checkout');
        $button_html .= '<input type="hidden" name="woocommerce-process-checkout-nonce" value="' . $nonce . '" />';
        return $button_html;
    }
    
    /**
     * Fix payment nonce issues
     */
    public function fix_payment_nonce() {
        // Make sure checkout page has the proper nonces
        add_action('woocommerce_checkout_before_customer_details', function() {
            // Force shipping calculation
            if (WC()->cart) {
                WC()->cart->calculate_shipping();
                WC()->cart->calculate_totals();
            }
            
            // Add hidden nonce fields
            echo '<input type="hidden" id="woocommerce-process-checkout-nonce-backup" name="_wpnonce" value="' . wp_create_nonce('woocommerce-process_checkout') . '">';
            echo '<input type="hidden" id="aakaari-checkout-nonce-timestamp" name="aakaari_checkout_ts" value="' . time() . '">';
        }, 1);
        
        // Prevent WooCommerce from validating nonces too strictly
        add_filter('woocommerce_checkout_update_order_review_expired', '__return_false');
        
        // Add AJAX action to get fresh nonce
        add_action('wp_ajax_aakaari_get_checkout_nonce', [$this, 'ajax_get_checkout_nonce']);
        add_action('wp_ajax_nopriv_aakaari_get_checkout_nonce', [$this, 'ajax_get_checkout_nonce']);
    }

    /**
     * AJAX handler to get a fresh checkout nonce
     */
    public function ajax_get_checkout_nonce() {
        wp_send_json_success([
            'nonce' => wp_create_nonce('woocommerce-process_checkout'),
            'wpnonce' => wp_create_nonce('woocommerce-process_checkout')
        ]);
    }
    
    /**
     * Ensure shipping methods are calculated properly
     */
    public function ensure_shipping_methods() {
        // Force WooCommerce to calculate shipping on every page load for checkout
        add_action('woocommerce_before_checkout_form', function() {
            if (WC()->cart) {
                WC()->cart->calculate_shipping();
                WC()->cart->calculate_totals();
            }
        }, 5);
        
        // Make sure shipping methods are recalculated before displaying
        add_action('woocommerce_review_order_before_shipping', function() {
            if (WC()->cart) {
                WC()->cart->calculate_shipping();
            }
        }, 5);
        
        // Add AJAX handler to refresh shipping methods
        add_action('wp_ajax_aakaari_refresh_shipping', [$this, 'ajax_refresh_shipping']);
        add_action('wp_ajax_nopriv_aakaari_refresh_shipping', [$this, 'ajax_refresh_shipping']);
        
        // Make sure shipping packages are available
        add_filter('woocommerce_cart_ready_to_calc_shipping', '__return_true');
    }
    
    /**
     * AJAX handler to refresh shipping methods
     */
    public function ajax_refresh_shipping() {
        check_ajax_referer('aakaari-checkout', 'security');
        
        if (WC()->cart) {
            WC()->cart->calculate_shipping();
            WC()->cart->calculate_totals();
        }
        
        ob_start();
        woocommerce_order_review();
        $order_review = ob_get_clean();
        
        $has_shipping = false;
        if (WC()->cart && WC()->cart->needs_shipping() && WC()->cart->show_shipping()) {
            $has_shipping = true;
        }
        
        wp_send_json_success([
            'has_shipping' => $has_shipping,
            'html' => $order_review,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Add checkout debug mode
     */
    public function add_checkout_debug_mode() {
        ?>
        <div id="checkout-debug" style="position: fixed; bottom: 0; right: 0; background: rgba(0,0,0,0.8); color: #fff; padding: 10px; max-width: 50%; max-height: 50%; overflow: auto; z-index: 9999; font-family: monospace; font-size: 12px;">
            <h4>Checkout Debug</h4>
            <div id="checkout-debug-log"></div>
            <script>
            jQuery(function($) {
                // Override console.log to capture checkout debugging
                const originalLog = console.log;
                console.log = function() {
                    const args = Array.from(arguments);
                    if (args[0] && typeof args[0] === 'string' && args[0].includes('Checkout')) {
                        $('#checkout-debug-log').prepend('<div>' + args.join(' ') + '</div>');
                    }
                    originalLog.apply(console, arguments);
                };
                
                // Add shipping debug info
                $('#checkout-debug').append('<div>Available shipping methods:</div>');
                
                // List all shipping methods
                if ($('#shipping_method').length) {
                    $('#shipping_method li').each(function() {
                        $('#checkout-debug').append('<div>- ' + $(this).text() + '</div>');
                    });
                } else {
                    $('#checkout-debug').append('<div>No shipping methods found in DOM</div>');
                }
                
                // Add quick fix buttons
                $('#checkout-debug').append(
                    '<div style="margin-top: 10px;">' +
                    '<button id="debug-recalc-shipping" style="margin-right: 10px;">Recalculate Shipping</button>' +
                    '<button id="debug-force-step2">Go to Step 2</button>' +
                    '<button id="debug-refresh-nonce" style="margin-left: 10px;">Refresh Nonce</button>' +
                    '</div>'
                );
                
                // Attach handlers
                $('#debug-recalc-shipping').on('click', function() {
                    $(document.body).trigger('update_checkout');
                    $('#checkout-debug-log').prepend('<div>Manually triggered update_checkout</div>');
                });
                
                $('#debug-force-step2').on('click', function() {
                    if (typeof window.goToStep === 'function') {
                        window.goToStep(2);
                        $('#checkout-debug-log').prepend('<div>Manually forced step 2</div>');
                    } else {
                        $('#checkout-debug-log').prepend('<div>goToStep function not available</div>');
                    }
                });
                
                $('#debug-refresh-nonce').on('click', function() {
                    $.ajax({
                        url: aakaariCheckout.ajaxUrl,
                        data: {
                            action: 'aakaari_get_checkout_nonce',
                            security: aakaariCheckout.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                $('input[name="woocommerce-process-checkout-nonce"]').val(response.data.nonce);
                                $('input[name="_wpnonce"]').val(response.data.wpnonce);
                                $('#checkout-debug-log').prepend('<div>Refreshed checkout nonce</div>');
                            }
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * Add checkout debugging tools in footer
     */
    public function add_checkout_debugging() {
        if (!is_checkout() || is_order_received_page()) {
            return;
        }
        
        ?>
        <script>
        jQuery(function($) {
            // Debug checkout errors
            $(document.body).on('checkout_error', function(event, error_message) {
                console.log('Checkout Error:', error_message);
            });
            
            // Debug form submission
            $('#checkout-form').on('submit', function(e) {
                console.log('Form submitted with payment method:', $('input[name="payment_method"]:checked').val());
                
                // Check if all required nonce fields exist
                if (!$('input[name="woocommerce-process-checkout-nonce"]').length) {
                    console.error('Missing woocommerce-process-checkout-nonce field');
                    $('#checkout-form').append('<input type="hidden" name="woocommerce-process-checkout-nonce" value="' + 
                        $('#woocommerce-process-checkout-nonce').val() + '">');
                }
            });
            
            // Debug payment method selection
            $(document.body).on('payment_method_selected', function() {
                console.log('Payment method selected:', $('input[name="payment_method"]:checked').val());
            });
            
            // Debug step navigation
            window.debugStep = function(stepNum, action) {
                console.log(`Step ${stepNum} ${action}`);
            };
        });
        </script>
        <?php
    }
    
    /**
     * Make sure all required files exist
     */
    public function ensure_checkout_files_exist() {
        // Create the directory structure
        $this->create_required_directories();
        
        // Create checkout.js if it doesn't exist
        $this->create_checkout_js();
        
        // Create shipping fix JS
        $this->create_checkout_shipping_fix_js();
    }
    
    /**
     * Create necessary directories
     */
    private function create_required_directories() {
        $dirs = [
            get_stylesheet_directory() . '/assets',
            get_stylesheet_directory() . '/assets/css',
            get_stylesheet_directory() . '/assets/js',
            get_stylesheet_directory() . '/woocommerce/checkout',
        ];
        
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
        }
    }
    
    /**
     * Create the checkout.js file if it doesn't exist
     */
    private function create_checkout_js() {
        $file_path = get_stylesheet_directory() . '/assets/js/checkout.js';
        
        if (file_exists($file_path)) {
            return;
        }
        
        // Create directory if needed
        wp_mkdir_p(dirname($file_path));
        
        // Get the new checkout.js content
        $js_content = $this->get_checkout_js_content();
        
        // Write the file
        file_put_contents($file_path, $js_content);
    }
    
    /**
     * Create checkout shipping fix JS file
     */
    public function create_checkout_shipping_fix_js() {
        $file_path = get_stylesheet_directory() . '/assets/js/checkout-shipping-fix.js';
        
        if (file_exists($file_path)) {
            return;
        }
        
        // Create directory if needed
        wp_mkdir_p(dirname($file_path));
        
        // File content
        $js_content = $this->get_checkout_shipping_fix_js_content();
        
        // Write the file
        file_put_contents($file_path, $js_content);
    }
    
    /**
     * Get the contents for checkout.js
     */
    private function get_checkout_js_content() {
        return <<<'EOT'
/**
 * Aakaari Checkout – Modern Step Controller
 * Complete rewrite for reliable multi-step checkout
 */

jQuery(function($) {
    // Make goToStep global for debugging
    window.goToStep = null;
    
    // State management
    let currentStep = 1;
    const totalSteps = 3;
    let isSubmitting = false;
    let fieldsMovedInitially = false;
    
    // Expose currentStep to other scripts
    window.currentStep = currentStep;

    // DOM elements
    const $form = $('#checkout-form');
    const $stepContents = {
        1: $('#step-1-content'),
        2: $('#step-2-content'),
        3: $('#step-3-content')
    };
    const $progressSteps = {
        1: $('#progress-step-1'),
        2: $('#progress-step-2'),
        3: $('#progress-step-3')
    };
    const $backBtns = $('#mobile-back-btn, #desktop-back-btn');
    const $nextBtns = $('#mobile-next-btn, #desktop-next-btn');
    
    // Debug helper
    const isDebug = aakaariCheckout && aakaariCheckout.isDebug;
    function debug(message, data) {
        if (isDebug && window.console) {
            if (data) {
                console.log('Aakaari Checkout: ' + message, data);
            } else {
                console.log('Aakaari Checkout: ' + message);
            }
        }
        if (typeof window.debugStep === 'function') {
            window.debugStep(currentStep, message);
        }
    }

    /**
     * Initialize the checkout form
     */
    function init() {
        debug('Initializing multi-step checkout');
        
        // Setup form
        $form.attr('novalidate', 'novalidate');
        
        // Move checkout fields to their proper places
        moveCheckoutFields();
        
        // Setup button handlers
        setupButtonHandlers();
        
        // Listen for WooCommerce events
        setupWooCommerceEvents();
        
        // Setup step-specific logic
        goToStep(1);
        updateButtonLabels();
        ensureNonceField();
    }
    
    /**
     * Ensure the WooCommerce nonce field is present
     */
    function ensureNonceField() {
        const $nonceField = $('input[name="woocommerce-process-checkout-nonce"]');
        const $wpNonce = $('input[name="_wpnonce"]');
        
        // If we're missing the nonce, add it
        if (!$nonceField.length && aakaariCheckout.processNonce) {
            debug('Adding missing checkout nonce');
            $form.append('<input type="hidden" name="woocommerce-process-checkout-nonce" value="' + aakaariCheckout.processNonce + '">');
        }
        
        // If we're missing the _wpnonce, add it
        if (!$wpNonce.length && aakaariCheckout.processNonce) {
            debug('Adding missing _wpnonce');
            $form.append('<input type="hidden" name="_wpnonce" value="' + aakaariCheckout.processNonce + '">');
        }
        
        // Check for these fields again before form submission
        $form.on('submit', function() {
            ensureNonceField();
        });
    }
    
    /**
     * Setup button event handlers
     */
    function setupButtonHandlers() {
        // Back button handler
        $backBtns.on('click', function(e) {
            e.preventDefault();
            if (currentStep > 1) {
                goToStep(currentStep - 1);
            }
        });
        
        // Next/Continue button handler
        $nextBtns.on('click', function(e) {
            // Don't do anything if already submitting
            if (isSubmitting) {
                debug('Preventing action - already submitting');
                e.preventDefault();
                return false;
            }
            
            // If not on the final step, navigate forward
            if (currentStep < totalSteps) {
                e.preventDefault();
                debug('Continue button clicked on step ' + currentStep);
                
                if (validateStep(currentStep)) {
                    goToStep(currentStep + 1);
                } else {
                    debug('Validation failed for step ' + currentStep);
                }
            }
            // On the final step, handle form submission
            else if (currentStep === totalSteps) {
                debug('Place order button clicked');
                
                if (!validateStep(currentStep)) {
                    e.preventDefault();
                    isSubmitting = false;
                    debug('Final validation failed - preventing submission');
                    return false;
                }
                
                // Show loading state
                $(this).addClass('aak-button--loading');
                isSubmitting = true;
                
                // Ensure nonce is present before submission
                ensureNonceField();
                
                // Let form submission continue
                debug('Form validation passed, proceeding with submission');
                return true;
            }
        });
        
        // Handle shipping option clicks
        $(document).on('click', '#aakaari-shipping-methods .radio-option', function() {
            const methodId = $(this).data('method-id');
            if (methodId) {
                const $input = $('#' + methodId);
                if ($input.length) {
                    $input.prop('checked', true).trigger('change');
                    $('#aakaari-shipping-methods .radio-option').removeClass('selected');
                    $(this).addClass('selected');
                    debug('Shipping method selected: ' + methodId);
                }
            }
        });
        
        // Handle payment option clicks
        $(document).on('click', '.payment-method-option', function() {
            const $li = $(this).closest('li.payment_method');
            const $input = $li.find('input.input-radio').first();
            
            if ($input.length && !$input.is(':checked')) {
                $input.prop('checked', true).trigger('click');
                
                setTimeout(function() {
                    $(document.body).trigger('payment_method_selected');
                }, 50);
                
                $('.payment-method-option').removeClass('selected');
                $(this).addClass('selected');
                debug('Payment method selected: ' + $input.val());
            }
        });
        
        // Ship to different address toggle
        $(document).on('change', '#ship-to-different-address-checkbox', function() {

            if (window.currentStep === 2 && !isCalculating) {

                if (!$(this).is(':checked')) {

                    copyBillingToShipping();

                }

                calculationAttempts = 0;

                setTimeout(forceShippingUpdate, 300);

            }

        });
    }
    
    /**
     * Setup WooCommerce event handlers
     */
    function setupWooCommerceEvents() {
        // After WooCommerce updates the checkout via AJAX
        $(document.body).on('updated_checkout', function() {
            debug('WooCommerce updated_checkout event triggered');
            
            // Make sure fields are in right places
            moveCheckoutFields(true);
            
            // Reinitialize payment methods on step 3
            if (currentStep === 3) {
                setTimeout(function() {
                    $(document.body).trigger('payment_method_selected');
                    $('input[name="payment_method"]:checked').trigger('click');
                }, 300);
            }
            
            // Update button labels
            updateButtonLabels();
        });
        
        // Form submission handler for WooCommerce integration
        $form.on('submit', function(e) {
            debug('Form submission triggered');
            
            // If already being processed by WooCommerce, let it through
            if ($form.is('.processing')) {
                debug('Form already processing, allowing submission');
                return true;
            }
            
            // Final validation check
            if (currentStep === totalSteps && !validateStep(totalSteps)) {
                e.preventDefault();
                isSubmitting = false;
                debug('Form submission blocked - validation failed');
                return false;
            }
            
            // Get selected payment method
            const paymentMethod = $('input[name="payment_method"]:checked').val();
            
            // If no payment method selected but payment is needed, prevent submission
            if ($('.wc_payment_methods').length > 0 && (!paymentMethod || paymentMethod === '')) {
                e.preventDefault();
                isSubmitting = false;
                alert('Please select a payment method.');
                debug('Form submission blocked - no payment method selected');
                return false;
            }
            
            // Ensure necessary fields are present
            ensureNonceField();
            
            // Let WooCommerce payment gateway handle submission
            try {
                debug('Triggering WooCommerce checkout handlers');
                
                if ($form.triggerHandler('checkout_place_order') !== false) {
                    if (!paymentMethod || $form.triggerHandler('checkout_place_order_' + paymentMethod) !== false) {
                        $form.addClass('processing');
                        $nextBtns.addClass('aak-button--loading');
                        debug('Form submission proceeding with payment method: ' + paymentMethod);
                        return true;
                    }
                }
            } catch (error) {
                debug('Error in form submission handler', error);
            }
            
            e.preventDefault();
            isSubmitting = false;
            debug('Form submission blocked by WooCommerce handlers');
            return false;
        });
        
        // Handle checkout errors
        $(document.body).on('checkout_error', function() {
            isSubmitting = false;
            $nextBtns.removeClass('aak-button--loading');
        });
    }

    /**
     * Move checkout fields into their proper containers
     */
    function moveCheckoutFields(isUpdate = false) {
        if (fieldsMovedInitially && !isUpdate) return;
        
        const $fieldWrapper = $('#aak-fields-wrapper');
        const $contactCard = $('#aak-contact-card');
        const $addressCard = $('#aak-address-card');
        
        if (!$contactCard.length || !$addressCard.length) {
            debug('Target cards not found');
            return;
        }
        
        debug('Moving checkout fields' + (isUpdate ? ' (update)' : ''));
        
        // Define field selectors
        const contactSelectors = [
            '#billing_email_field',
            '#billing_phone_field',
            '.woocommerce-billing-fields > .form-row[class*="aak-field-key-marketing_opt_in"]',
            '.woocommerce-billing-fields > .checkbox-wrapper:has(#marketing_opt_in)'
        ];
        const addressSelectors = [
            '.woocommerce-billing-fields',
            '.woocommerce-shipping-fields',
            '#order_comments_field',
            'p.woocommerce-form-row.save-info',
            '.create-account'
        ];
        
        // Determine source container
        const $source = isUpdate && !$fieldWrapper.length ? $form : $fieldWrapper;
        if (!$source.length) return;
        
        // Store original field values before moving
        const formData = $form.serialize();
        
        // Move Contact Fields
        contactSelectors.forEach(selector => {
            const $field = $source.find(selector);
            if ($field.length && !$contactCard.find(selector).length) {
                $field.appendTo($contactCard);
            }
        });
        
        // Move Address Fields
        addressSelectors.forEach(selector => {
            const $section = $source.find(selector);
            if ($section.length && !$addressCard.find(selector).length) {
                $section.appendTo($addressCard);
            }
        });
        
        // Cleanup and finalize
        if (!isUpdate && $fieldWrapper.length) {
            $fieldWrapper.remove();
            fieldsMovedInitially = true;
        }
        
        // Re-initialize form elements after moving
        setTimeout(function() {
            // Re-init Select2 if present
            if ($.fn.select2) {
                $addressCard.find('select.country_select, select.state_select').filter(':visible').each(function() {
                    try {
                        $(this).select2('destroy');
                    } catch(e) { /* may not be initialized yet */ }
                    $(this).select2();
                });
            }
            
            // Trigger WooCommerce events
            $(document.body).trigger('country_to_state_changed');
            $(document.body).trigger('wc_address_i18n_ready');
            
            // Check if form data changed unexpectedly
            if ($form.serialize() !== formData) {
                debug('Field values changed during move - monitoring form integrity');
            }
        }, 150);
    }
    
    /**
     * Go to a specific checkout step
     */
    function goToStep(step) {
        if (step < 1 || step > totalSteps) return;
        
        debug('Navigating to step ' + step);
        
        // Reset submission state
        isSubmitting = false;
        $nextBtns.removeClass('aak-button--loading');
        
        // Update current step
        currentStep = step;
        window.currentStep = step; // Make available globally
        
        // Hide all steps and show current
        Object.values($stepContents).forEach(el => el.addClass('hidden'));
        $stepContents[step].removeClass('hidden');
        
        // Update UI
        updateProgressBar();
        updateButtonLabels();
        scrollToTop();
        
        // Trigger updates based on step
        if (step === 2 || step === 3) {
            $('body').trigger('update_checkout');
            
            if (step === 3) {
                setTimeout(function() {
                    $(document.body).trigger('payment_method_selected');
                    $('input[name="payment_method"]:checked').trigger('click');
                }, 300);
            }
        }
        
        // Trigger a custom event that other scripts can listen for
        $(document).trigger('aak_step_changed', [step]);
        
        // Close any open Select2 dropdowns
        if ($.fn.select2) {
            $('select.select2-hidden-accessible').select2('close');
        }
    }
    
    /**
     * Update progress bar UI
     */
    function updateProgressBar() {
        Object.values($progressSteps).forEach(el => el.removeClass('active completed'));
        
        for (let i = 1; i <= totalSteps; i++) {
            if (i < currentStep) {
                $progressSteps[i].addClass('completed');
            } else if (i === currentStep) {
                $progressSteps[i].addClass('active');
            }
        }
    }
    
    /**
     * Update button labels based on current step
     */
    function updateButtonLabels() {
        let nextBtnText = 'Continue';
        let nextBtnHtml = nextBtnText;
        const placeOrderText = $nextBtns.eq(0).data('value') || $nextBtns.eq(0).attr('value') || 'Place Order';
        
        if (currentStep === totalSteps) {
            nextBtnText = placeOrderText;
            nextBtnHtml = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> ${nextBtnText}`;
            
            // Change button type and name for submission
            $nextBtns.attr('type', 'submit').attr('name', 'woocommerce_checkout_place_order');
        } else {
            // Revert to button type for navigation
            $nextBtns.attr('type', 'button').removeAttr('name');
        }
        
        // Apply text/HTML updates
        $nextBtns.html(nextBtnText);
        
        // Desktop button with special structure
        const $desktopBtnText = $('#desktop-btn-text');
        if ($desktopBtnText.length) {
            if (currentStep === totalSteps) {
                $desktopBtnText.parent().html(nextBtnHtml);
            } else {
                $desktopBtnText.text(nextBtnText);
            }
        }
        
        // Show/hide back button
        $backBtns.css('display', currentStep > 1 ? 'flex' : 'none');
    }
    
    /**
     * Validate the current step
     */
    function validateStep(step) {
        let isValid = true;
        const $currentStepContent = $stepContents[step];
        
        debug('Validating step ' + step);
        
        // Find required fields in current step
        const $requiredRows = $currentStepContent.find('.validate-required, .validate-email');
        
        $requiredRows.each(function() {
            const $row = $(this);
            
            // Find the input element
            const $field = $row.find('input, select, textarea').filter(':visible').first();
            
            // Skip if no visible field
            if (!$field.length) {
                // Check for Select2
                if ($row.find('select.select2-hidden-accessible').length) {
                    const $select2 = $row.find('select.select2-hidden-accessible');
                    if (!$select2.val()) {
                        highlightInvalidField($row);
                        isValid = false;
                    } else {
                        removeInvalidHighlight($row);
                    }
                }
                return;
            }
            
            // Get field value
            let fieldValue = $field.val();
            
            // Special handling for checkboxes
            if ($field.is(':checkbox') && !$field.is(':checked')) {
                fieldValue = '';
            }
            
            // Validate field value
            if (fieldValue === null || fieldValue.trim() === '') {
                highlightInvalidField($row);
                isValid = false;
            } else {
                removeInvalidHighlight($row);
                
                // Email validation
                if ($row.hasClass('validate-email') && !isValidEmail(fieldValue)) {
                    highlightInvalidField($row);
                    isValid = false;
                }
            }
        });
        
        // Scroll to first error
        if (!isValid) {
            const $firstError = $currentStepContent.find('.woocommerce-invalid').first();
            if ($firstError.length) {
                scrollToElement($firstError);
            }
        }
        
        // Step-specific validation
        if (step === 2 && isValid) {
            isValid = validateShippingMethod();
        } else if (step === 3 && isValid) {
            isValid = validatePaymentMethod();
        }
        
        debug('Validation ' + (isValid ? 'passed' : 'failed') + ' for step ' + step);
        return isValid;
    }
    
    /**
     * Validate shipping method selection
     */
    function validateShippingMethod() {
        // Only validate if shipping is required
        const $container = $('#aakaari-shipping-methods');
        if (!$container.length || $container.children().length === 0) {
            return true;
        }
        
        const isSelected = $('input.shipping_method:checked').length > 0;
        const $shippingCard = $container.closest('.card');
        
        if (!isSelected) {
            highlightInvalidField($shippingCard);
            scrollToElement($shippingCard);
            return false;
        } else {
            removeInvalidHighlight($shippingCard);
        }
        
        return true;
    }
    
    /**
     * Validate payment method selection
     */
    function validatePaymentMethod() {
        // Skip if no payment needed
        if (!$('.wc_payment_methods').length) {
            return true;
        }
        
        const isSelected = $('input[name="payment_method"]:checked').length > 0;
        const $paymentCard = $('#aakaari-payment').closest('.card');
        
        if (!isSelected) {
            highlightInvalidField($paymentCard);
            scrollToElement($paymentCard);
            return false;
        } else {
            removeInvalidHighlight($paymentCard);
        }
        
        return true;
    }
    
    /**
     * Highlight an invalid field
     */
    function highlightInvalidField($element) {
        if (!$element.hasClass('woocommerce-invalid')) {
            $element.addClass('woocommerce-invalid aak-shake');
            setTimeout(() => {
                $element.removeClass('aak-shake');
            }, 600);
        }
    }
    
    /**
     * Remove invalid field highlighting
     */
    function removeInvalidHighlight($element) {
        $element.removeClass('woocommerce-invalid');
    }
    
    /**
     * Validate email format
     */
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    /**
     * Scroll to an element
     */
    function scrollToElement($element) {
        if ($element.length) {
            $('html, body').animate({
                scrollTop: $element.offset().top - 50
            }, 400);
        }
    }
    
    /**
     * Scroll to top of checkout container
     */
    function scrollToTop() {
        const $container = $('#checkout-container');
        if ($container.length && $(window).scrollTop() > $container.offset().top + 10) {
            $('html, body').animate({
                scrollTop: $container.offset().top - 30
            }, 400);
        }
    }
    
    // Make goToStep available for debugging
    window.goToStep = goToStep;
    
    // Initialize when document is ready
    $(document).ready(function() {
        init();
    });
});
EOT;
    }
    
    /**
     * Get the contents for checkout-shipping-fix.js
     */
    private function get_checkout_shipping_fix_js_content() {
        return <<<'EOT'
/**
 * Aakaari Checkout - Shipping Methods Fix
 * Direct integration with WooCommerce shipping methods
 */
jQuery(function($) {
    // Function to force shipping calculation
    function forceShippingUpdate() {
        // Add a hidden field to trigger recalculation
        if (!$('#force_shipping_calc').length) {
            $('<input type="hidden" id="force_shipping_calc" name="force_shipping_calc" value="1">').appendTo('#checkout-form');
        }
        
        // Trigger checkout update
        $(document.body).trigger('update_checkout');
        
        console.log('Aakaari Checkout: Forcing shipping recalculation...');
    }
    
    // Function to format shipping options directly from WooCommerce
    function formatShippingMethodsDirectly() {
        const $container = $('#aakaari-shipping-methods');
        if (!$container.length) return;
        
        // Look for shipping in all possible places WooCommerce might put it
        let hasShippingMethods = false;
        
        // Check WooCommerce shipping methods (original UL format)
        const $shippingMethodList = $('#shipping_method');
        if ($shippingMethodList.length && $shippingMethodList.find('li').length > 0) {
            hasShippingMethods = true;
            
            // Clear container
            $container.empty();
            
            // Process each shipping method
            $shippingMethodList.find('li').each(function() {
                const $li = $(this);
                const $input = $li.find('input.shipping_method');
                const $label = $li.find('label');
                
                if ($input.length && $label.length) {
                    const id = $input.attr('id');
                    const isChecked = $input.is(':checked');
                    const methodName = $label.clone().children().remove().end().text().trim();
                    const $priceEl = $label.find('.amount');
                    let priceHtml = $priceEl.length ? $priceEl.parent().html() : '';
                    
                    // Create custom radio option
                    const newOption = $(`
                        <div class="radio-option ${isChecked ? 'selected' : ''}" data-method-id="${id}">
                            <div class="radio-visual-input"></div>
                            <div class="radio-option-content">
                                <div class="radio-option-title">${methodName}</div>
                            </div>
                            <span class="radio-option-price">${priceHtml}</span>
                        </div>
                    `);
                    
                    $container.append(newOption);
                }
            });
            
            // Bind click events
            $container.find('.radio-option').off('click').on('click', function() {
                const methodId = $(this).data('method-id');
                if (methodId) {
                    $('#' + methodId).prop('checked', true).trigger('change');
                    $container.find('.radio-option').removeClass('selected');
                    $(this).addClass('selected');
                }
            });
        }
        // Check WooCommerce shipping methods (table format)
        else if ($('table.woocommerce-shipping-totals').length) {
            const $shippingRows = $('table.woocommerce-shipping-totals tr.shipping');
            
            if ($shippingRows.length) {
                hasShippingMethods = true;
                
                // Clear container
                $container.empty();
                
                $shippingRows.each(function() {
                    const $row = $(this);
                    const $methodCell = $row.find('td');
                    
                    // Check for radio buttons first
                    const $input = $methodCell.find('input[type="radio"]');
                    if ($input.length) {
                        const id = $input.attr('id');
                        const isChecked = $input.is(':checked');
                        const $label = $methodCell.find('label[for="' + id + '"]');
                        
                        if ($label.length) {
                            const methodName = $label.clone().children().remove().end().text().trim();
                            const $priceEl = $label.find('.amount');
                            let priceHtml = $priceEl.length ? $priceEl.parent().html() : '';
                            
                            // Create custom radio option
                            const newOption = $(`
                                <div class="radio-option ${isChecked ? 'selected' : ''}" data-method-id="${id}">
                                    <div class="radio-visual-input"></div>
                                    <div class="radio-option-content">
                                        <div class="radio-option-title">${methodName}</div>
                                    </div>
                                    <span class="radio-option-price">${priceHtml}</span>
                                </div>
                            `);
                            
                            $container.append(newOption);
                        }
                    }
                    // For flat rate / single method
                    else {
                        const methodName = $row.find('th').text().trim();
                        const priceHtml = $methodCell.find('.amount').length ? 
                            $methodCell.find('.amount').parent().html() : 
                            $methodCell.clone().children().remove().end().text().trim();
                        
                        // Create method display (not clickable since no radio button)
                        const newOption = $(`
                            <div class="radio-option selected">
                                <div class="radio-visual-input"></div>
                                <div class="radio-option-content">
                                    <div class="radio-option-title">${methodName}</div>
                                </div>
                                <span class="radio-option-price">${priceHtml}</span>
                            </div>
                        `);
                        
                        $container.append(newOption);
                    }
                });
                
                // Bind click events
                $container.find('.radio-option[data-method-id]').off('click').on('click', function() {
                    const methodId = $(this).data('method-id');
                    if (methodId) {
                        $('#' + methodId).prop('checked', true).trigger('change');
                        $container.find('.radio-option').removeClass('selected');
                        $(this).addClass('selected');
                    }
                });
            }
        }
        
        // If no shipping methods were found, check if we need shipping
        if (!hasShippingMethods) {
            if ($('.woocommerce-shipping-destination').length) {
                // We need shipping but no methods are available
                $container.html(`
                    <p>No shipping methods available for your location. Please check your address.</p>
                    <button type="button" id="force-shipping-refresh" class="btn btn-primary">Refresh Shipping Methods</button>
                `);
                
                $('#force-shipping-refresh').off('click').on('click', function() {
                    $(this).text('Refreshing...').prop('disabled', true);
                    forceShippingUpdate();
                });
            } else if (window.currentStep === 2) {
                // Show message if we're on step 2
                $container.html(`
                    <p>Please enter your shipping address to view available shipping methods.</p>
                    <button type="button" id="force-shipping-refresh" class="btn btn-primary">Refresh</button>
                `);
                
                $('#force-shipping-refresh').off('click').on('click', function() {
                    $(this).text('Refreshing...').prop('disabled', true);
                    forceShippingUpdate();
                });
                
                // Force update_checkout to get shipping methods
                setTimeout(forceShippingUpdate, 500);
            }
        }
    }
    
    // Run on document ready
    formatShippingMethodsDirectly();
    
    // Run after checkout updates
    $(document.body).on('updated_checkout', function() {
        setTimeout(formatShippingMethodsDirectly, 100);
    });
    
    // Run when switching to step 2
    $(document).on('aak_step_changed', function(e, step) {
        if (step === 2) {
            setTimeout(function() {
                formatShippingMethodsDirectly();
                forceShippingUpdate();
            }, 300);
        }
    });
    
    // Debug helper
    if (window.location.href.includes('checkout_debug=1')) {
        console.log('Aakaari Checkout: Shipping fix loaded in debug mode');
        
        // Check if any shipping methods exist in the DOM
        if ($('#shipping_method').length) {
            console.log('Shipping method UL exists with ' + $('#shipping_method li').length + ' methods');
        } else {
            console.log('No #shipping_method UL found in DOM');
        }
        
        if ($('table.woocommerce-shipping-totals').length) {
            console.log('Shipping table exists with ' + $('table.woocommerce-shipping-totals tr.shipping').length + ' rows');
        } else {
            console.log('No shipping table found in DOM');
        }
    }
    
    // Manually add a test shipping method if needed for debugging
    if ($('#aakaari-shipping-methods').length && window.location.href.includes('shipping_debug=1')) {
        $('#aakaari-shipping-methods').html(`
            <div class="radio-option selected">
                <div class="radio-visual-input"></div>
                <div class="radio-option-content">
                    <div class="radio-option-title">Standard Shipping</div>
                    <p class="radio-option-description">5-7 business days</p>
                </div>
                <span class="radio-option-price"><span class="woocommerce-Price-amount amount">$10.00</span></span>
            </div>
        `);
        console.log('Aakaari Checkout: Added test shipping method for debugging');
    }
});
EOT;
    }
    
    /**
     * Create checkout template page on theme activation
     */
    public static function create_checkout_page() {
        // Only run once
        if (get_option('aakaari_checkout_page_created')) {
            return;
        }
        
        // Create the page
        $page_data = array(
            'post_title'    => 'Checkout',
            'post_content'  => '',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => 'aakaari-checkout',
            'page_template' => 'template-checkout.php'
        );
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id) {
            update_post_meta($page_id, '_wp_page_template', 'template-checkout.php');
            update_option('aakaari_checkout_page_created', true);
        }
    }
    
    /**
     * Create template file for checkout page
     */
    public static function create_template_file() {
        $file_path = get_stylesheet_directory() . '/template-checkout.php';
        
        if (file_exists($file_path)) {
            return;
        }
        
        $template_content = <<<EOT
<?php
/**
 * Template Name: Aakaari Checkout
 * Description: Custom multi-step checkout page template
 */

defined('ABSPATH') || exit;

get_header();

// Ensure WooCommerce is active
if (class_exists('WooCommerce')) {
    // Use WC's checkout shortcode to display the form
    echo do_shortcode('[woocommerce_checkout]');
} else {
    echo '<div class="error-message container"><p>WooCommerce is required for the checkout page.</p></div>';
}

get_footer();
EOT;
        
        // Write the file
        file_put_contents($file_path, $template_content);
    }
}

// Initialize Aakaari Checkout
add_action('init', ['Aakaari_Checkout', 'init']);

// Setup theme activation hooks
add_action('after_switch_theme', ['Aakaari_Checkout', 'create_checkout_page']);
add_action('after_switch_theme', ['Aakaari_Checkout', 'create_template_file']);

