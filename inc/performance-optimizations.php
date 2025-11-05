<?php
/**
 * Performance Optimizations for Aakaari Theme
 * Optimized for: 1.5GB RAM, 2 CPU Cores, 60 PHP Workers
 *
 * This file contains optimizations to reduce memory usage,
 * CPU load, and improve overall performance on shared hosting.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/* =========================================
   1. BROWSER CACHING & COMPRESSION
   ========================================= */

/**
 * Add browser caching headers for static assets
 */
function aakaari_add_cache_headers() {
    if (!is_admin()) {
        header('Cache-Control: public, max-age=31536000');
    }
}
// Disabled by default - use .htaccess or server config instead
// add_action('send_headers', 'aakaari_add_cache_headers');

/* =========================================
   2. SCRIPT & STYLE OPTIMIZATION
   ========================================= */

/**
 * Defer non-critical JavaScript
 * Reduces initial page load time
 */
function aakaari_defer_scripts($tag, $handle, $src) {
    // List of scripts to defer
    $defer_scripts = array(
        'aakaari-mobile-menu',
        'aakaari-script',
        'aakaari_main_homepage_js',
        'font-awesome'
    );

    // Don't defer jQuery or admin scripts
    if (is_admin() || in_array($handle, array('jquery', 'jquery-core', 'jquery-migrate'))) {
        return $tag;
    }

    // Defer specific scripts
    if (in_array($handle, $defer_scripts)) {
        return str_replace(' src', ' defer src', $tag);
    }

    return $tag;
}
add_filter('script_loader_tag', 'aakaari_defer_scripts', 10, 3);

/**
 * Add preconnect for external resources
 * Speeds up loading of external fonts and CDNs
 */
function aakaari_resource_hints() {
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://unpkg.com">
    <?php
}
add_action('wp_head', 'aakaari_resource_hints', 1);

/**
 * Remove unnecessary scripts from homepage
 * Reduces HTTP requests
 */
function aakaari_dequeue_unnecessary_scripts() {
    // Remove Gutenberg block CSS on frontend if not needed
    if (!is_admin() && !is_singular()) {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('wc-blocks-style');
    }

    // Remove emoji detection script (saves ~15KB)
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
}
add_action('wp_enqueue_scripts', 'aakaari_dequeue_unnecessary_scripts', 100);

/* =========================================
   3. IMAGE OPTIMIZATION
   ========================================= */

/**
 * Add native lazy loading to images
 * Reduces initial page load
 */
function aakaari_add_lazy_loading($attr) {
    if (!is_admin()) {
        $attr['loading'] = 'lazy';
    }
    return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'aakaari_add_lazy_loading');

/**
 * Optimize image quality for web
 * Reduces file sizes without noticeable quality loss
 */
add_filter('jpeg_quality', function() {
    return 82;
});

add_filter('wp_editor_set_quality', function() {
    return 82;
});

/* =========================================
   4. DATABASE OPTIMIZATION
   ========================================= */

/**
 * Limit post revisions to save database space
 * Each revision takes up storage and slows queries
 */
if (!defined('WP_POST_REVISIONS')) {
    define('WP_POST_REVISIONS', 3);
}

/**
 * Set autosave interval to 5 minutes
 * Reduces database writes
 */
if (!defined('AUTOSAVE_INTERVAL')) {
    define('AUTOSAVE_INTERVAL', 300);
}

/**
 * Optimize WooCommerce database queries
 * Removes unnecessary queries on non-shop pages
 */
function aakaari_optimize_woocommerce() {
    if (!class_exists('WooCommerce')) {
        return;
    }

    // Disable cart fragments on non-cart/checkout pages
    if (!is_cart() && !is_checkout() && !is_account_page()) {
        wp_dequeue_script('wc-cart-fragments');
    }
}
add_action('wp_enqueue_scripts', 'aakaari_optimize_woocommerce', 99);

/**
 * Disable WooCommerce scripts on non-WooCommerce pages
 */
function aakaari_disable_woocommerce_non_shop() {
    if (!class_exists('WooCommerce')) {
        return;
    }

    // Only load WooCommerce assets on relevant pages
    if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page()) {
        // Dequeue WooCommerce styles
        wp_dequeue_style('woocommerce-general');
        wp_dequeue_style('woocommerce-layout');
        wp_dequeue_style('woocommerce-smallscreen');

        // Dequeue WooCommerce scripts
        wp_dequeue_script('wc-cart-fragments');
        wp_dequeue_script('woocommerce');
        wp_dequeue_script('wc-add-to-cart');
    }
}
add_action('wp_enqueue_scripts', 'aakaari_disable_woocommerce_non_shop', 99);

/* =========================================
   5. CLEAN UP WORDPRESS HEAD
   ========================================= */

/**
 * Remove unnecessary meta tags from <head>
 * Reduces HTML size
 */
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);

/* =========================================
   6. MEMORY & CPU OPTIMIZATION
   ========================================= */

/**
 * Limit WordPress memory to 128MB
 * Prevents single requests from consuming too much RAM
 */
if (!defined('WP_MEMORY_LIMIT')) {
    define('WP_MEMORY_LIMIT', '128M');
}

/**
 * Limit WordPress admin memory to 256MB
 * Allows more memory for admin operations
 */
if (!defined('WP_MAX_MEMORY_LIMIT')) {
    define('WP_MAX_MEMORY_LIMIT', '256M');
}

/**
 * Disable heartbeat API on frontend
 * Reduces AJAX requests and CPU usage
 */
function aakaari_disable_heartbeat() {
    global $pagenow;

    // Disable on frontend
    if (!is_admin()) {
        wp_deregister_script('heartbeat');
    }

    // Slow down on backend (from 15s to 60s)
    if (is_admin() && $pagenow != 'post.php' && $pagenow != 'post-new.php') {
        wp_deregister_script('heartbeat');
    }
}
add_action('init', 'aakaari_disable_heartbeat', 1);

/**
 * Optimize heartbeat frequency on post edit screen
 */
function aakaari_heartbeat_settings($settings) {
    $settings['interval'] = 60; // 60 seconds
    return $settings;
}
add_filter('heartbeat_settings', 'aakaari_heartbeat_settings');

/* =========================================
   7. QUERY OPTIMIZATION
   ========================================= */

/**
 * Remove query strings from static resources
 * Improves cacheability
 */
function aakaari_remove_query_strings($src) {
    if (strpos($src, '?ver=')) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}
add_filter('script_loader_src', 'aakaari_remove_query_strings', 15, 1);
add_filter('style_loader_src', 'aakaari_remove_query_strings', 15, 1);

/**
 * Disable dashicons on frontend for non-logged-in users
 */
function aakaari_disable_dashicons() {
    if (!is_user_logged_in()) {
        wp_deregister_style('dashicons');
    }
}
add_action('wp_enqueue_scripts', 'aakaari_disable_dashicons');

/* =========================================
   8. OBJECT CACHING SUPPORT
   ========================================= */

/**
 * Support for persistent object caching
 * If you install Redis or Memcached, this will help
 */
if (!defined('WP_CACHE')) {
    define('WP_CACHE', true);
}

/* =========================================
   9. RSS FEED OPTIMIZATION
   ========================================= */

/**
 * Limit RSS feed items to reduce processing
 */
function aakaari_limit_rss() {
    return 10;
}
add_filter('posts_per_rss', 'aakaari_limit_rss');

/* =========================================
   10. ADMIN OPTIMIZATION
   ========================================= */

/**
 * Disable admin bar for non-admins
 * Reduces memory usage and HTTP requests
 */
function aakaari_disable_admin_bar() {
    if (!current_user_can('administrator') && !is_admin()) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'aakaari_disable_admin_bar');

/**
 * Increase PHP execution time for admin operations
 * Prevents timeouts on slower hosting
 */
if (is_admin()) {
    @ini_set('max_execution_time', 300);
}

/* =========================================
   11. COMMENTS OPTIMIZATION
   ========================================= */

/**
 * Remove comment reply script if comments are disabled
 */
function aakaari_dequeue_comment_script() {
    if (!is_singular() || !comments_open() || !get_option('thread_comments')) {
        wp_dequeue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'aakaari_dequeue_comment_script', 100);

/* =========================================
   12. TRANSIENT CLEANUP
   ========================================= */

/**
 * Clean up expired transients weekly
 * Keeps database lean
 */
if (!wp_next_scheduled('aakaari_cleanup_transients')) {
    wp_schedule_event(time(), 'weekly', 'aakaari_cleanup_transients');
}

function aakaari_delete_expired_transients() {
    global $wpdb;

    $time = time();
    $expired = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT option_name FROM $wpdb->options
            WHERE option_name LIKE %s
            AND option_value < %d",
            '_transient_timeout_%',
            $time
        )
    );

    foreach ($expired as $transient) {
        $key = str_replace('_transient_timeout_', '', $transient);
        delete_transient($key);
    }
}
add_action('aakaari_cleanup_transients', 'aakaari_delete_expired_transients');

/* =========================================
   PERFORMANCE MONITORING (Optional)
   ========================================= */

/**
 * Add performance meta tag for debugging
 * Shows page generation time in HTML source
 */
function aakaari_performance_meta() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        echo '<!-- Page generated in ' . timer_stop(0, 3) . ' seconds -->' . "\n";
        echo '<!-- Memory usage: ' . size_format(memory_get_peak_usage(true)) . ' -->' . "\n";
    }
}
add_action('wp_footer', 'aakaari_performance_meta', 999);
