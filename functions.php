<?php
/**
 * Aakaari functions and definitions
 */

if ( ! function_exists( 'aakaari_setup' ) ) :
function aakaari_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo' );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );
    add_theme_support( 'woocommerce' );
    register_nav_menus( array(
        'primary' => __( 'Primary Menu', 'aakaari' ),
    ) );
}
endif;
add_action( 'after_setup_theme', 'aakaari_setup' );

function aakaari_scripts() {
    wp_enqueue_style( 'aakaari-style', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
    wp_enqueue_script( 'aakaari-script', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), wp_get_theme()->get('Version'), true );


}
add_action( 'wp_enqueue_scripts', 'aakaari_scripts' );

// WooCommerce wrappers (basic)
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

add_action('woocommerce_before_main_content', 'aakaari_wrapper_start', 10);
add_action('woocommerce_after_main_content', 'aakaari_wrapper_end', 10);

function aakaari_wrapper_start() {
    echo '<div class="container"><main id="main" class="site-main">';
}
function aakaari_wrapper_end() {
    echo '</main></div>';
}

// Add support for AJAX cart fragments (so cart count updates)
add_filter( 'woocommerce_add_to_cart_fragments', 'aakaari_cart_fragment' );
function aakaari_cart_fragment( $fragments ) {
    ob_start();
    ?>
    <a class="cart-contents" href="<?php echo wc_get_cart_url(); ?>" title="<?php _e( 'View your shopping cart' ); ?>">
        <?php echo WC()->cart->get_cart_contents_count(); ?>
    </a>
    <?php
    $fragments['aakaari-cart-contents'] = ob_get_clean();
    return $fragments;
}

// child theme functions.php additions

// ==== Aakaari main-theme additions (paste into your main theme functions.php) ====

// Enqueue homepage CSS & JS
// Enqueue homepage CSS & JS (with localization)
function aakaari_main_enqueue_assets() {
    // Google font (Inter)
    wp_enqueue_style( 'aakaari_main_google_fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap', array(), null );

    // Homepage styles (only load if the file exists)
    $css_path = get_template_directory() . '/assets/css/homepage.css';
    if ( file_exists( $css_path ) ) {
        wp_enqueue_style( 'aakaari_main_homepage_style', get_template_directory_uri() . '/assets/css/homepage.css', array(), filemtime( $css_path ) );
    }

    // Homepage JS
    $js_path = get_template_directory() . '/assets/js/homepage.js';
    if ( file_exists( $js_path ) ) {
        wp_enqueue_script( 'aakaari_main_homepage_js', get_template_directory_uri() . '/assets/js/homepage.js', array('jquery'), filemtime( $js_path ), true );

        // LOCALIZE Ajax URL (important)
        wp_localize_script( 'aakaari_main_homepage_js', 'aakaari_qv', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
        ) );
    }

    // Dashicons (optional but useful)
    wp_enqueue_style( 'dashicons' );
}
add_action( 'wp_enqueue_scripts', 'aakaari_main_enqueue_assets', 20 );


// Add theme support for WooCommerce if not present (optional)
function aakaari_main_woocommerce_support() {
    if ( function_exists('is_woocommerce') ) {
        // do nothing — WooCommerce likely supported by theme
        return;
    }
    add_theme_support( 'woocommerce' );
}
add_action( 'after_setup_theme', 'aakaari_main_woocommerce_support' );

// Register image size for hero
if ( ! function_exists( 'aakaari_main_register_image_sizes' ) ) {
    function aakaari_main_register_image_sizes() {
        add_image_size( 'aakaari-hero', 1200, 800, true );
    }
    add_action( 'after_setup_theme', 'aakaari_main_register_image_sizes' );
}


// Featured Product Section code 

// Aakaari: Quick View AJAX handler
add_action( 'wp_ajax_nopriv_aakaari_quick_view', 'aakaari_quick_view_handler' );
add_action( 'wp_ajax_aakaari_quick_view', 'aakaari_quick_view_handler' );

function aakaari_quick_view_handler() {
    if ( empty( $_GET['product_id'] ) ) {
        wp_send_json_error('Missing product id');
    }

    $pid = intval( wp_unslash( $_GET['product_id'] ) );
    if ( ! $pid ) {
        wp_send_json_error('Invalid id');
    }

    if ( ! class_exists( 'WooCommerce' ) ) {
        wp_send_json_error( 'WooCommerce not active' );
    }

    $product = wc_get_product( $pid );
    if ( ! $product || ! $product->is_visible() ) {
        wp_send_json_error('Product not found');
    }

    // Prepare data
    $title = $product->get_name();
    $permalink = get_permalink( $pid );
    $image = $product->get_image( 'woocommerce_single' );
    $short_desc = apply_filters( 'woocommerce_short_description', $product->get_short_description() );
    $avg_rating = floatval( $product->get_average_rating() );
    $rating_html = wc_get_rating_html( $avg_rating, $product->get_rating_count() );

    // Prices
    $regular = $product->get_regular_price();
    $sale = $product->get_sale_price();
    $price_html = $product->get_price_html();

    // wholesale meta fallback keys
    $wholesale_price = get_post_meta( $pid, '_wholesale_price', true );
    if ( empty( $wholesale_price ) ) {
        $wholesale_price = get_post_meta( $pid, 'wholesale_price', true );
    }

    // Render HTML (small partial)
    ob_start();
    ?>
    <div class="aqv">
      <div class="aqv-left">
        <div class="aqv-image"><?php echo $image; ?></div>
      </div>
      <div class="aqv-right">
        <h2 class="aqv-title"><?php echo esc_html( $title ); ?></h2>
        <div class="aqv-rating"><?php echo $rating_html; ?> <span class="aqv-count">(<?php echo intval( $product->get_rating_count() ); ?>)</span></div>
        <div class="aqv-prices">
          <?php if ( $regular ) : ?>
            <div class="aqv-mrp">MRP: <span><?php echo wc_price( $regular ); ?></span></div>
          <?php endif; ?>
          <?php if ( $wholesale_price && is_numeric($wholesale_price) ) : ?>
            <div class="aqv-wholesale">Wholesale: <span><?php echo wc_price( $wholesale_price ); ?></span></div>
          <?php endif; ?>
          <div class="aqv-current">Price: <span><?php echo $price_html; ?></span></div>
        </div>

        <div class="aqv-desc"><?php echo wp_kses_post( $short_desc ); ?></div>

        <div class="aqv-actions">
          <?php
          // If simple product, build add-to-cart URL to add 1 and redirect to cart
          if ( $product->is_type( 'simple' ) ) {
              $add_url = esc_url( add_query_arg( 'add-to-cart', $pid, home_url() ) );
              echo '<a class="btn aaq-order-now" href="' . $add_url . '">Order Now</a>';
          } else {
              // variable/other types -> link to product page
              echo '<a class="btn aaq-order-now" href="' . esc_url( $permalink ) . '">View & Order</a>';
          }
          // Quick link to product page
          echo '<a class="btn btn-outline aaq-view" href="' . esc_url( $permalink ) . '">Open product page</a>';
          ?>
        </div>
      </div>
    </div>



    <?php

    $html = ob_get_clean();
    wp_send_json_success( $html );
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

// Section callback
function reseller_settings_callback() {
    echo '<p>Settings for the Reseller Call-to-Action section on the front page.</p>';
}

// Field callback for Reseller Page selector
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


/* Mobile Header */

/* Mobile Header - FIXED */

/**
 * REMOVE this function as it's causing conflicts with the existing menu registration
 * The menu is already registered in aakaari_setup() as 'primary'
 */
// function register_aakaari_menus() {
//     register_nav_menus(array(
//         'primary-menu' => esc_html__('Primary Menu', 'aakaari'),
//     ));
// }
// add_action('after_setup_theme', 'register_aakaari_menus');

/**
 * Enqueue header scripts and styles
 */
function enqueue_aakaari_header_assets() {
    // Enqueue header styles
    wp_enqueue_style(
        'aakaari-header-styles',
        get_template_directory_uri() . '/assets/css/header.css',
        array(),
        '1.0.0'
    );
    
    // Enqueue header scripts
    wp_enqueue_script(
        'aakaari-header-script',
        get_template_directory_uri() . '/assets/js/header.js',
        array(),
        '1.0.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'enqueue_aakaari_header_assets');

/* Footer */

/**
 * Enqueue footer styles
 */
function enqueue_aakaari_footer_assets() {
    wp_enqueue_style(
        'aakaari-footer-styles',
        get_template_directory_uri() . '/assets/css/footer.css',
        array(),
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'enqueue_aakaari_footer_assets');


/**
 * Enqueue How It Works JavaScript and CSS
 */
function enqueue_how_it_works_scripts() {
    if (is_page_template('how-it-works.php')) {
        // Enqueue JavaScript file
        wp_enqueue_script(
            'how-it-works-js',
            get_template_directory_uri() . '/assets/js/how-it-works.js',
            array(),
            '1.0.0',
            true
        );
        
        // Enqueue How It Works CSS file
        wp_enqueue_style(
            'how-it-works-styles',
            get_template_directory_uri() . '/assets/css/how-it-works.css',
            array(),
            '1.0.0'
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_how_it_works_scripts');

/**
 * Enqueue Pricing Page Assets
 */
function enqueue_pricing_assets() {
    if (is_page_template('pricing.php')) {
        // Enqueue CSS
        wp_enqueue_style(
            'pricing-styles',
            get_template_directory_uri() . '/assets/css/pricing.css',
            array(),
            '1.0.0'
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'pricing-js',
            get_template_directory_uri() . '/assets/js/pricing.js',
            array(),
            '1.0.0',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_pricing_assets');

/**
 * Enqueue Contact Page Assets
 */
function enqueue_contact_assets() {
    if (is_page_template('contact.php')) {
        // Enqueue CSS
        wp_enqueue_style(
            'contact-styles',
            get_template_directory_uri() . '/assets/css/contact.css',
            array(),
            '1.0.0'
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'contact-js',
            get_template_directory_uri() . '/assets/js/contact.js',
            array(),
            '1.0.0',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_contact_assets');

/**
 * Enqueue Login Page Assets
 */
function enqueue_login_assets() {
    if (is_page_template('login.php')) {
        // Enqueue CSS
        wp_enqueue_style(
            'login-styles',
            get_template_directory_uri() . '/assets/css/login.css',
            array(),
            '1.0.0'
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'login-js',
            get_template_directory_uri() . '/assets/js/login.js',
            array(),
            '1.0.0',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_login_assets');

/**
 * Register and enqueue Reseller Dashboard assets
 */
function aakaari_dashboard_assets() {
    if (is_page_template('dashboard.php')) {
        // Enqueue CSS
        wp_enqueue_style(
            'dashboard-styles',
            get_template_directory_uri() . '/assets/css/dashboard.css',
            array(),
            '1.0.0'
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'dashboard-js',
            get_template_directory_uri() . '/assets/js/dashboard.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Pass data to JavaScript
        wp_localize_script('dashboard-js', 'dashboard_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('aakaari_dashboard_nonce'),
            'current_date' => date('Y-m-d'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'aakaari_dashboard_assets');

/**
 * Add Dashboard Page to Theme Options
 */
function theme_dashboard_settings() {
    // Add a field for the Dashboard Page
    add_settings_field(
        'dashboard_page_id',
        'Dashboard Page',
        'dashboard_page_callback',
        'general',
        'reseller_settings_section' // Use existing section
    );
    
    // Register the setting
    register_setting('general', 'dashboard_page_id');
}
add_action('admin_init', 'theme_dashboard_settings');

// Field callback for Dashboard Page selector
function dashboard_page_callback() {
    $dashboard_page_id = get_option('dashboard_page_id');
    
    wp_dropdown_pages(array(
        'name' => 'dashboard_page_id',
        'show_option_none' => 'Select a page',
        'option_none_value' => '0',
        'selected' => $dashboard_page_id,
    ));
    echo '<p class="description">Select the page that uses the Reseller Dashboard template</p>';
}

/**
 * Redirect after login to dashboard
 */
function redirect_to_dashboard_after_login($redirect_to, $request, $user) {
    // If the user is a reseller (you can use a role check here)
    if (isset($user->roles) && is_array($user->roles)) {
        // Check if user is a customer or a specific role
        if (in_array('customer', $user->roles) || in_array('reseller', $user->roles)) {
            $dashboard_page_id = get_option('dashboard_page_id');
            if ($dashboard_page_id) {
                return get_permalink($dashboard_page_id);
            }
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'redirect_to_dashboard_after_login', 10, 3);

/**
 * AJAX handler for withdraw funds
 */
function aakaari_withdraw_funds() {
    // Check nonce for security
    check_ajax_referer('aakaari_dashboard_nonce', 'security');
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'User not logged in'));
        return;
    }
    
    $user_id = get_current_user_id();
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    
    // Get current wallet balance
    $wallet_balance = get_user_meta($user_id, 'wallet_balance', true);
    if (empty($wallet_balance)) {
        $wallet_balance = 0;
    }
    
    // Check if enough balance
    if ($amount <= 0 || $amount > $wallet_balance) {
        wp_send_json_error(array('message' => 'Invalid withdrawal amount'));
        return;
    }
    
    // Process withdrawal (in a real implementation, you'd connect to payment gateway)
    $new_balance = $wallet_balance - $amount;
    update_user_meta($user_id, 'wallet_balance', $new_balance);
    
    // Record transaction
    $transaction_id = 'TXN-' . strtoupper(substr(md5(uniqid()), 0, 8));
    $transactions = get_user_meta($user_id, 'wallet_transactions', true);
    if (empty($transactions)) {
        $transactions = array();
    }
    
    $transactions[] = array(
        'id' => $transaction_id,
        'type' => 'Debit',
        'description' => 'Withdrawal to bank',
        'date' => date('Y-m-d'),
        'amount' => -$amount
    );
    
    update_user_meta($user_id, 'wallet_transactions', $transactions);
    
    wp_send_json_success(array(
        'message' => 'Withdrawal successful',
        'new_balance' => $new_balance,
        'transaction_id' => $transaction_id
    ));
}
add_action('wp_ajax_aakaari_withdraw_funds', 'aakaari_withdraw_funds');

/**
 * AJAX handler for generating product links
 */
function aakaari_generate_product_link() {
    // Check nonce for security
    check_ajax_referer('aakaari_dashboard_nonce', 'security');
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'User not logged in'));
        return;
    }
    
    $user_id = get_current_user_id();
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    if (!$product_id) {
        wp_send_json_error(array('message' => 'Invalid product ID'));
        return;
    }
    
    // Generate affiliate/tracking link
    $product_permalink = get_permalink($product_id);
    $tracking_id = 'r=' . $user_id . '&p=' . $product_id;
    $separator = (strpos($product_permalink, '?') !== false) ? '&' : '?';
    $affiliate_link = $product_permalink . $separator . $tracking_id;
    
    wp_send_json_success(array(
        'product_id' => $product_id,
        'affiliate_link' => $affiliate_link
    ));
}
add_action('wp_ajax_aakaari_generate_product_link', 'aakaari_generate_product_link');

/**
 * Create custom user roles for resellers
 */
function aakaari_create_reseller_role() {
    add_role(
        'reseller',
        'Reseller',
        array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'view_woocommerce_reports' => true,
        )
    );
}
register_activation_hook(__FILE__, 'aakaari_create_reseller_role');

/**
 * Function to get user's earnings for dashboard
 */
function get_user_earnings($user_id, $period = 'total') {
    // Get the user's orders
    $args = array(
        'customer_id' => $user_id,
        'status' => array('completed', 'processing', 'on-hold'),
        'limit' => -1,
    );
    
    // Add date filters for specific periods
    if ($period == 'month') {
        $args['date_created'] = '>' . date('Y-m-01');
    } else if ($period == 'year') {
        $args['date_created'] = '>' . date('Y-01-01');
    }
    
    $orders = wc_get_orders($args);
    
    $total_earnings = 0;
    $order_count = 0;
    
    foreach ($orders as $order) {
        $order_total = $order->get_total();
        // Calculate commission (customize this formula as needed)
        $commission = $order_total * 0.15; // 15% commission
        $total_earnings += $commission;
        $order_count++;
    }
    
    return array(
        'amount' => $total_earnings,
        'count' => $order_count
    );
}

/**
 * Add Dashboard link to account menu
 */
function add_dashboard_link_to_account_menu($items) {
    $dashboard_page_id = get_option('dashboard_page_id');
    
    if ($dashboard_page_id) {
        $dashboard_link = array(
            'dashboard' => array(
                'title' => 'Reseller Dashboard',
                'url' => get_permalink($dashboard_page_id)
            )
        );
        
        // Insert after Dashboard but before Orders
        $new_items = array();
        foreach ($items as $key => $value) {
            if ($key === 'dashboard') {
                $new_items[$key] = $value;
                $new_items['reseller-dashboard'] = $dashboard_link['dashboard'];
            } else {
                $new_items[$key] = $value;
            }
        }
        
        return $new_items;
    }
    
    return $items;
}
add_filter('woocommerce_account_menu_items', 'add_dashboard_link_to_account_menu');

/**
 * Dashboard JS Script
 * Create this file at /assets/js/dashboard.js
 */
function create_dashboard_js_file() {
    $js_content = '
    jQuery(document).ready(function($) {
        // Tab switching
        $(".tab-item").on("click", function(e) {
            e.preventDefault();
            
            const tabId = $(this).data("tab");
            
            // Update active tab
            $(".tab-item").removeClass("active");
            $(this).addClass("active");
            
            // Show active content
            $(".tab-content").removeClass("active");
            $("#" + tabId + "-content").addClass("active");
            
            // Update URL without reloading
            history.pushState(null, null, "#" + tabId);
        });
        
        // Handle tab triggers from elsewhere on the page
        $("[data-tab-trigger]").on("click", function(e) {
            e.preventDefault();
            
            const tabId = $(this).data("tab-trigger");
            $(`.tab-item[data-tab="${tabId}"]`).click();
        });
        
        // Handle direct links to tabs (e.g., from URL hash)
        if (window.location.hash) {
            const tabId = window.location.hash.substring(1);
            $(`.tab-item[data-tab="${tabId}"]`).click();
        }
        
        // Withdraw funds functionality
        $("[data-action=\'withdraw-funds\']").on("click", function(e) {
            e.preventDefault();
            
            const walletBalance = parseFloat($(this).data("balance"));
            
            if (walletBalance <= 0) {
                alert("You don\'t have enough balance to withdraw.");
                return;
            }
            
            const amount = prompt("Enter amount to withdraw:", walletBalance);
            
            if (amount === null) return; // User canceled
            
            const withdrawAmount = parseFloat(amount);
            
            if (isNaN(withdrawAmount) || withdrawAmount <= 0 || withdrawAmount > walletBalance) {
                alert("Please enter a valid amount (between 1 and " + walletBalance + ")");
                return;
            }
            
            // Send AJAX request
            $.ajax({
                url: dashboard_data.ajax_url,
                type: "POST",
                data: {
                    action: "aakaari_withdraw_funds",
                    security: dashboard_data.security,
                    amount: withdrawAmount
                },
                success: function(response) {
                    if (response.success) {
                        alert("Withdrawal request submitted successfully!");
                        // Reload page to update balances
                        location.reload();
                    } else {
                        alert(response.data.message || "Error processing withdrawal.");
                    }
                },
                error: function() {
                    alert("Server error. Please try again later.");
                }
            });
        });
        
        // File input styling for bulk order
        $(".file-input").on("change", function() {
            const fileName = $(this).val().split("\\").pop();
            if (fileName) {
                $(this).siblings(".selected-file").text(fileName);
            } else {
                $(this).siblings(".selected-file").text("No file chosen");
            }
        });
    });
    ';
    
    // Instructions for manual file creation
    return $js_content;
}


/**
 * Register and enqueue product customizer scripts and styles
 */
function enqueue_product_customizer_assets() {
    if (is_page_template('product-customizer.php')) {
        // Enqueue Fabric.js
        wp_enqueue_script(
            'fabricjs',
            'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.2.1/fabric.min.js',
            array(),
            '5.2.1',
            true
        );
        
        // Enqueue Dropzone.js
        wp_enqueue_style(
            'dropzonejs-css',
            'https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css',
            array(),
            '5.9.3'
        );
        
        wp_enqueue_script(
            'dropzonejs',
            'https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js',
            array('jquery'),
            '5.9.3',
            true
        );
        
        // Enqueue customizer CSS
        wp_enqueue_style(
            'product-customizer-css',
            get_template_directory_uri() . '/assets/css/product-customizer.css',
            array(),
            filemtime(get_template_directory() . '/assets/css/product-customizer.css')
        );
        
        // Enqueue customizer JS
        wp_enqueue_script(
            'product-customizer-js',
            get_template_directory_uri() . '/assets/js/product-customizer.js',
            array('jquery', 'fabricjs', 'dropzonejs'),
            filemtime(get_template_directory() . '/assets/js/product-customizer.js'),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_product_customizer_assets');

/**
 * Register color attribute meta field
 */
function register_color_attribute_meta() {
    register_meta('term', 'color_value', array(
        'type' => 'string',
        'description' => 'Color hex value',
        'single' => true,
        'show_in_rest' => true,
    ));
}
add_action('init', 'register_color_attribute_meta');

/**
 * Add color picker to attribute term form
 */
function add_color_attribute_fields($term) {
    $color_value = get_term_meta($term->term_id, 'color_value', true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="color_value">Color Value</label></th>
        <td>
            <input type="color" name="color_value" id="color_value" value="<?php echo esc_attr($color_value); ?>">
            <p class="description">Choose a color value for this attribute.</p>
        </td>
    </tr>
    <?php
}
add_action('pa_color_edit_form_fields', 'add_color_attribute_fields');

/**
 * Save color attribute meta
 */
function save_color_attribute_meta($term_id) {
    if (isset($_POST['color_value'])) {
        update_term_meta($term_id, 'color_value', sanitize_text_field($_POST['color_value']));
    }
}
add_action('edited_pa_color', 'save_color_attribute_meta');
add_action('created_pa_color', 'save_color_attribute_meta');

/**
 * AJAX handler for adding customized product to cart
 */
function add_customized_product_to_cart() {
    // Check nonce
    if (!check_ajax_referer('product_customization', 'customization_nonce', false)) {
        wp_send_json_error(array('message' => 'Security check failed'));
        exit;
    }
    
    // Get form data
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $color_id = isset($_POST['color_id']) ? absint($_POST['color_id']) : 0;
    $size_id = isset($_POST['size_id']) ? absint($_POST['size_id']) : 0;
    $design_data = isset($_POST['design_data']) ? sanitize_text_field($_POST['design_data']) : '';
    $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;
    
    // Validate data
    if (!$product_id || !$color_id || !$size_id || empty($design_data)) {
        wp_send_json_error(array('message' => 'Invalid customization data'));
        exit;
    }
    
    // Get variation ID if this is a variable product
    $variation_id = 0;
    $product = wc_get_product($product_id);
    
    if ($product && $product->is_type('variable')) {
        // Get color and size terms
        $color_term = get_term($color_id, 'pa_color');
        $size_term = get_term($size_id, 'pa_size');
        
        if ($color_term && $size_term) {
            // Find matching variation
            $variation_id = find_matching_product_variation($product_id, array(
                'attribute_pa_color' => $color_term->slug,
                'attribute_pa_size' => $size_term->slug
            ));
        }
    }
    
    // Prepare custom data
    $cart_item_data = array(
        'custom_design' => array(
            'design_data' => $design_data,
            'color_id' => $color_id,
            'size_id' => $size_id
        ),
        // Add unique key to prevent cart merging
        'unique_key' => md5(microtime().rand())
    );
    
    // Add to cart
    $added = WC()->cart->add_to_cart(
        $product_id,
        $quantity,
        $variation_id,
        array(),
        $cart_item_data
    );
    
    if ($added) {
        wp_send_json_success(array(
            'message' => 'Product added to cart successfully',
            'cart_url' => wc_get_cart_url()
        ));
    } else {
        wp_send_json_error(array('message' => 'Failed to add product to cart'));
    }
    
    exit;
}
add_action('wp_ajax_add_customized_product_to_cart', 'add_customized_product_to_cart');
add_action('wp_ajax_nopriv_add_customized_product_to_cart', 'add_customized_product_to_cart');

/**
 * Find matching product variation
 */
function find_matching_product_variation($product_id, $attributes) {
    return (new WC_Product_Data_Store_CPT())->find_matching_product_variation(
        new WC_Product($product_id),
        $attributes
    );
}

/**
 * Display custom design in cart
 */
function display_custom_design_in_cart($item_data, $cart_item) {
    if (isset($cart_item['custom_design'])) {
        $custom_data = $cart_item['custom_design'];
        
        // Add custom design info
        $item_data[] = array(
            'key'   => 'Custom Design',
            'value' => 'Yes'
        );
        
        // Add color info
        if (isset($custom_data['color_id'])) {
            $color_term = get_term($custom_data['color_id'], 'pa_color');
            if ($color_term) {
                $item_data[] = array(
                    'key'   => 'Color',
                    'value' => $color_term->name
                );
            }
        }
        
        // Add size info
        if (isset($custom_data['size_id'])) {
            $size_term = get_term($custom_data['size_id'], 'pa_size');
            if ($size_term) {
                $item_data[] = array(
                    'key'   => 'Size',
                    'value' => $size_term->name
                );
            }
        }
    }
    
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'display_custom_design_in_cart', 10, 2);

/**
 * Save custom design data to order
 */
function save_custom_design_to_order($item, $cart_item_key, $values, $order) {
    if (isset($values['custom_design'])) {
        $item->add_meta_data('_custom_design', $values['custom_design']);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'save_custom_design_to_order', 10, 4);

/**
 * Display custom design data in order details
 */
function display_custom_design_in_order($item_id, $item, $product) {
    $custom_design = $item->get_meta('_custom_design');
    
    if ($custom_design) {
        // Display custom design info
        echo '<p><strong>Custom Design:</strong> Yes</p>';
        
        // Display color info
        if (isset($custom_design['color_id'])) {
            $color_term = get_term($custom_design['color_id'], 'pa_color');
            if ($color_term) {
                echo '<p><strong>Color:</strong> ' . esc_html($color_term->name) . '</p>';
            }
        }
        
        // Display size info
        if (isset($custom_design['size_id'])) {
            $size_term = get_term($custom_design['size_id'], 'pa_size');
            if ($size_term) {
                echo '<p><strong>Size:</strong> ' . esc_html($size_term->name) . '</p>';
            }
        }
    }
}
add_action('woocommerce_order_item_meta_end', 'display_custom_design_in_order', 10, 3);


/* functions-addons.php
   Copy this into your theme's functions.php or include it from there.
   - Enqueues CSS/JS
   - Registers shortcode [become_a_reseller]
   - Handles form submission (admin-post)
   - Registers a private CPT 'reseller_application' to store submissions
*/

function aar_enqueue_reseller_assets(){
  // Update paths if your theme structure differs
  wp_enqueue_style('become-reseller-css', get_stylesheet_directory_uri() . '/assets/css/become-a-reseller.css', array(), '1.0');
  wp_enqueue_script('become-reseller-js', get_stylesheet_directory_uri() . '/assets/js/become-a-reseller.js', array(), '1.0', true);
}
add_action('wp_enqueue_scripts','aar_enqueue_reseller_assets');

/* 2) Shortcode to include the template */
function aar_reseller_shortcode($atts){
  ob_start();
  $tpl = get_stylesheet_directory() . '/template-parts/become-a-reseller.php';
  if(file_exists($tpl)){
    include $tpl;
  } else {
    echo '<p><strong>Reseller form template missing.</strong> Please add <code>template-parts/become-a-reseller.php</code> to your theme.</p>';
  }
  return ob_get_clean();
}
add_shortcode('become_a_reseller','aar_reseller_shortcode');

/* 3) Handle form submission */
function aar_handle_reseller_submission(){
  // Basic nonce check
  if( ! isset($_POST['reseller_nonce']) || ! wp_verify_nonce($_POST['reseller_nonce'], 'reseller_apply_nonce') ){
    wp_die('Security check failed', 'Error', array('response'=>403));
  }

  // Sanitize inputs
  $name    = sanitize_text_field($_POST['reseller_full_name'] ?? '');
  $business= sanitize_text_field($_POST['reseller_business_name'] ?? '');
  $email   = sanitize_email($_POST['reseller_email'] ?? '');
  $phone   = sanitize_text_field($_POST['reseller_phone'] ?? '');
  $address = sanitize_text_field($_POST['reseller_address'] ?? '');
  $city    = sanitize_text_field($_POST['reseller_city'] ?? '');
  $state   = sanitize_text_field($_POST['reseller_state'] ?? '');
  $pincode = sanitize_text_field($_POST['reseller_pincode'] ?? '');
  $gstin   = sanitize_text_field($_POST['reseller_gstin'] ?? '');
  $bank    = sanitize_text_field($_POST['reseller_bank_name'] ?? '');
  $account = sanitize_text_field($_POST['reseller_account'] ?? '');
  $ifsc    = sanitize_text_field($_POST['reseller_ifsc'] ?? '');
  $tnc     = isset($_POST['reseller_tnc']) ? 1 : 0;

  // Server-side required check
  if(empty($name) || empty($email) || empty($phone) || empty($address) || empty($city) || empty($state) || empty($pincode) || empty($bank) || empty($account) || empty($ifsc) || ! $tnc){
    // redirect back with query param
    $ref = wp_get_referer() ? wp_get_referer() : home_url('/');
    wp_redirect(add_query_arg('reseller_status', 'missing', $ref));
    exit;
  }

  // Handle file upload (ID proof)
  $uploaded_file_url = '';
  if(!empty($_FILES['reseller_id_proof']) && !empty($_FILES['reseller_id_proof']['name'])){
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );

    // Use wp_handle_upload
    $uploaded = wp_handle_upload($_FILES['reseller_id_proof'], array('test_form' => false));
    if(isset($uploaded['url'])){
      $uploaded_file_url = esc_url_raw($uploaded['url']);
    }
  }

  // Create private post to store submission (post type registered below)
  $postarr = array(
    'post_title'   => wp_strip_all_tags($name . ' — ' . $business),
    'post_content' => '',
    'post_status'  => 'private',
    'post_type'    => 'reseller_application'
  );
  $post_id = wp_insert_post($postarr);
  if($post_id){
    update_post_meta($post_id, 'reseller_name', $name);
    update_post_meta($post_id, 'reseller_business', $business);
    update_post_meta($post_id, 'reseller_email', $email);
    update_post_meta($post_id, 'reseller_phone', $phone);
    update_post_meta($post_id, 'reseller_address', $address);
    update_post_meta($post_id, 'reseller_city', $city);
    update_post_meta($post_id, 'reseller_state', $state);
    update_post_meta($post_id, 'reseller_pincode', $pincode);
    update_post_meta($post_id, 'reseller_gstin', $gstin);
    update_post_meta($post_id, 'reseller_bank', $bank);
    update_post_meta($post_id, 'reseller_account', $account);
    update_post_meta($post_id, 'reseller_ifsc', $ifsc);
    update_post_meta($post_id, 'reseller_id_proof_url', $uploaded_file_url);
  }

  // Notify admin via email
  $admin_email = get_option('admin_email');
  $subject = 'New Reseller Application: ' . $name;
  $message = "A new reseller application has been submitted:\n\n";
  $message .= "Name: $name\nBusiness: $business\nEmail: $email\nPhone: $phone\nAddress: $address, $city, $state - $pincode\nBank: $bank (A/C: $account) IFSC: $ifsc\nGSTIN: $gstin\nID Proof: $uploaded_file_url\n\n";
  wp_mail($admin_email, $subject, $message);

  // Redirect back with success
  $ref = wp_get_referer() ? wp_get_referer() : home_url('/');
  wp_redirect(add_query_arg('reseller_status', 'success', $ref));
  exit;
}
add_action('admin_post_nopriv_submit_reseller_application', 'aar_handle_reseller_submission');
add_action('admin_post_submit_reseller_application', 'aar_handle_reseller_submission');






// 1. Enqueue Scripts and Styles for the Registration Page
add_action('wp_enqueue_scripts', 'enqueue_reseller_registration_assets');
function enqueue_reseller_registration_assets() {
    // Only load these files on the page with our template
    if (is_page_template('register.php')) {
        // Enqueue the stylesheet
        wp_enqueue_style(
            'reseller-registration-style',
            get_template_directory_uri() . '/assets/css/register.css',
            [],
            '1.0.0'
        );

        // Enqueue the javascript file
        wp_enqueue_script(
            'reseller-registration-script',
            get_template_directory_uri() . '/assets/js/register.js',
            [], // No dependencies
            '1.0.0',
            true // Load in footer
        );

        // Pass data from PHP to our JavaScript file
        wp_localize_script('reseller-registration-script', 'registration_ajax_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('reseller_registration_nonce'),
            'login_url' => wc_get_page_permalink('myaccount'), // Get WooCommerce My Account URL
        ]);
    }
}



/**
 * Handle reseller registration
 */
function handle_reseller_registration() {
    // Verify nonce
    if (!isset($_POST['reseller_registration_nonce']) || 
        !wp_verify_nonce($_POST['reseller_registration_nonce'], 'reseller_register')) {
        wp_send_json_error(['message' => 'Security check failed.']);
        exit;
    }

    // Get form data
    $fullName = sanitize_text_field($_POST['fullName']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $password = $_POST['password'];
    $businessName = sanitize_text_field($_POST['businessName']);
    $businessType = sanitize_text_field($_POST['businessType']);
    $city = sanitize_text_field($_POST['city']);
    $state = sanitize_text_field($_POST['state']);

    // Validate data
    if (empty($fullName) || empty($email) || empty($phone) || empty($password) || 
        empty($city) || empty($state)) {
        wp_send_json_error(['message' => 'Please fill all required fields.']);
        exit;
    }

    // Validate password length
    if (strlen($password) < 8) {
        wp_send_json_error(['message' => 'Password must be at least 8 characters long.']);
        exit;
    }

    // Check if email already exists
    if (email_exists($email)) {
        wp_send_json_error(['message' => 'This email address is already registered. Please login instead.']);
        exit;
    }

    // Check if phone number already exists (assuming you store it in user meta)
    $users_with_phone = get_users([
        'meta_key' => 'phone',
        'meta_value' => $phone,
        'number' => 1,
        'count_total' => false
    ]);

    if (!empty($users_with_phone)) {
        wp_send_json_error(['message' => 'This phone number is already registered. Please use another number.']);
        exit;
    }

    // Create new user
    $username = sanitize_user(strtolower(str_replace(' ', '', $fullName)) . rand(100, 999));
    
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => $user_id->get_error_message()]);
        exit;
    }

    // Set user role to reseller (assuming you have this role)
    $user = new WP_User($user_id);
    $user->set_role('reseller');

    // Save additional user meta
    update_user_meta($user_id, 'full_name', $fullName);
    update_user_meta($user_id, 'phone', $phone);
    update_user_meta($user_id, 'business_name', $businessName);
    update_user_meta($user_id, 'business_type', $businessType);
    update_user_meta($user_id, 'city', $city);
    update_user_meta($user_id, 'state', $state);
    update_user_meta($user_id, 'onboarding_status', 'pending'); // Mark as pending approval
    
    // Automatically log in the user
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);

    // Send notification to admin
    $admin_email = get_option('admin_email');
    $subject = 'New Reseller Registration: ' . $fullName;
    $message = "A new reseller has registered and requires approval:\n\n";
    $message .= "Name: $fullName\n";
    $message .= "Email: $email\n";
    $message .= "Phone: $phone\n";
    $message .= "Business Name: $businessName\n";
    $message .= "City: $city, State: $state\n\n";
    $message .= "Approve this reseller: " . admin_url('users.php?role=reseller');
    
    wp_mail($admin_email, $subject, $message);

    // Send welcome email to user
    $user_subject = 'Welcome to Aakaari Reseller Program!';
    $user_message = "Dear $fullName,\n\n";
    $user_message .= "Thank you for registering as an Aakaari reseller!\n\n";
    $user_message .= "Your account is currently under review. You'll receive an email notification once your account is approved (typically within 24-48 hours).\n\n";
    $user_message .= "Meanwhile, you can log in to your account and complete your profile.\n\n";
    $user_message .= "Regards,\nAakaari Team";
    
    wp_mail($email, $user_subject, $user_message);

    wp_send_json_success(['message' => 'Registration successful!']);
    exit;
}
add_action('wp_ajax_nopriv_reseller_register', 'handle_reseller_registration');
add_action('wp_ajax_reseller_register', 'handle_reseller_registration');