<?php
/**
 * Verified Seller Restrictions
 * Restricts product visibility and cart access to verified (approved) sellers only
 *
 * @package Aakaari
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Aakaari_Verified_Seller_Restrictions {

    /**
     * Initialize restrictions
     */
    public static function init() {
        $instance = new self();
        $instance->hooks();
    }

    /**
     * Setup hooks
     */
    private function hooks() {
        // NOTE: Shop page redirect disabled - templates now show beautiful access message instead
        // add_action('template_redirect', [$this, 'restrict_shop_page_access'], 1);

        // Hide products from non-verified users on shop pages
        add_action('pre_get_posts', [$this, 'restrict_product_visibility']);

        // Restrict single product access
        add_action('template_redirect', [$this, 'restrict_product_access'], 10);

        // Restrict cart access
        add_action('template_redirect', [$this, 'restrict_cart_access'], 10);

        // Prevent adding to cart if not verified
        add_filter('woocommerce_is_purchasable', [$this, 'restrict_purchase'], 10, 2);

        // Show verification notice on restricted pages (not used on shop pages anymore)
        // add_action('woocommerce_before_main_content', [$this, 'show_verification_notice']);

        // Hide add to cart button for non-verified users
        add_filter('woocommerce_is_purchasable', [$this, 'hide_add_to_cart_for_non_verified'], 10, 2);

        // Restrict AJAX add to cart
        add_filter('woocommerce_add_to_cart_validation', [$this, 'validate_add_to_cart'], 10, 3);
        
        // Block WooCommerce product REST API for non-verified users
        add_filter('rest_pre_dispatch', [$this, 'restrict_product_api'], 10, 3);
    }

    /**
     * Check if current user is a verified/approved seller
     */
    private function is_verified_seller() {
        // Administrators have full access
        if (current_user_can('manage_options')) {
            return true;
        }

        if (!is_user_logged_in()) {
            return false;
        }

        $user = wp_get_current_user();
        $user_id = $user->ID;
        $user_email = $user->user_email;

        // Check if user has verified email
        $email_verified = get_user_meta($user_id, 'email_verified', true);
        if ($email_verified !== 'true' && $email_verified !== true) {
            return false;
        }

        // Get reseller application status using the centralized function
        if (function_exists('get_reseller_application_status')) {
            $application_info = get_reseller_application_status($user_email);
            $status = $application_info['status'];
            
            // Only approved resellers can access products
            if ($status === 'approved') {
                return true;
            }
        }

        // Fallback: Check legacy onboarding_status meta
        $onboarding_status = get_user_meta($user_id, 'onboarding_status', true);
        if ($onboarding_status === 'approved' || $onboarding_status === 'completed') {
            return true;
        }

        return false;
    }

    /**
     * Restrict shop page access (early redirect - priority 1)
     * This blocks access to /shop/, /products/, and product archive pages
     */
    public function restrict_shop_page_access() {
        // Check if this is a shop page, product category, product tag, or custom products page
        $is_products_page = false;
        
        // Check standard WooCommerce pages
        if (is_shop() || is_product_category() || is_product_tag() || is_post_type_archive('product')) {
            $is_products_page = true;
        }
        
        // Check if current page has slug 'products' or 'shop'
        if (is_page()) {
            global $post;
            if ($post && in_array($post->post_name, array('products', 'shop', 'store'))) {
                $is_products_page = true;
            }
        }
        
        // Exit if not a products-related page
        if (!$is_products_page) {
            return;
        }

        // Skip for administrators
        if (current_user_can('manage_options')) {
            return;
        }

        // Redirect non-verified users
        if (!$this->is_verified_seller()) {
            $this->redirect_with_notice();
        }
    }

    /**
     * Restrict product visibility in shop archives
     */
    public function restrict_product_visibility($query) {
        // Only apply to main query on product archives (not in admin)
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Only apply to product queries
        if ($query->get('post_type') !== 'product' && !is_shop() && !is_product_category() && !is_product_tag()) {
            return;
        }

        // Skip for administrators
        if (current_user_can('manage_options')) {
            return;
        }

        // If not verified seller, hide all products
        if (!$this->is_verified_seller()) {
            // Set query to return no products
            $query->set('post__in', array(0));
        }
    }

    /**
     * Restrict single product page access
     */
    public function restrict_product_access() {
        if (!is_product()) {
            return;
        }

        // Skip for administrators
        if (current_user_can('manage_options')) {
            return;
        }

        // Redirect non-verified users
        if (!$this->is_verified_seller()) {
            $this->redirect_with_notice();
        }
    }

    /**
     * Restrict cart page access
     */
    public function restrict_cart_access() {
        if (!is_cart() && !is_checkout()) {
            return;
        }

        // Skip for administrators
        if (current_user_can('manage_options')) {
            return;
        }

        // Redirect non-verified users
        if (!$this->is_verified_seller()) {
            $this->redirect_with_notice();
        }
    }

    /**
     * Restrict product purchase capability
     */
    public function restrict_purchase($is_purchasable, $product) {
        // Skip for administrators
        if (current_user_can('manage_options')) {
            return $is_purchasable;
        }

        // Only verified sellers can purchase
        if (!$this->is_verified_seller()) {
            return false;
        }

        return $is_purchasable;
    }

    /**
     * Hide add to cart button for non-verified users
     */
    public function hide_add_to_cart_for_non_verified($is_purchasable, $product) {
        // Skip for administrators
        if (current_user_can('manage_options')) {
            return $is_purchasable;
        }

        if (!$this->is_verified_seller()) {
            return false;
        }

        return $is_purchasable;
    }

    /**
     * Validate add to cart action
     */
    public function validate_add_to_cart($passed, $product_id, $quantity) {
        // Skip for administrators
        if (current_user_can('manage_options')) {
            return $passed;
        }

        if (!$this->is_verified_seller()) {
            wc_add_notice(__('You must be a verified seller to purchase products.', 'aakaari'), 'error');
            return false;
        }

        return $passed;
    }

    /**
     * Show verification notice
     */
    public function show_verification_notice() {
        if (!is_shop() && !is_product_category() && !is_product_tag() && !is_product()) {
            return;
        }

        // Skip for administrators
        if (current_user_can('manage_options')) {
            return;
        }

        // Skip if already verified
        if ($this->is_verified_seller()) {
            return;
        }

        if (!is_user_logged_in()) {
            wc_print_notice(
                sprintf(
                    __('🔒 Please <a href="%s" style="font-weight: 600; text-decoration: underline;">login</a> to view products and place orders.', 'aakaari'),
                    wp_login_url(get_permalink())
                ),
                'notice'
            );
            return;
        }

        $user = wp_get_current_user();
        $user_id = $user->ID;
        $user_email = $user->user_email;
        
        $email_verified = get_user_meta($user_id, 'email_verified', true);

        if ($email_verified !== 'true' && $email_verified !== true) {
            wc_print_notice(
                sprintf(
                    __('📧 Please <a href="%s" style="font-weight: 600; text-decoration: underline;">verify your email address</a> to access products.', 'aakaari'),
                    home_url('/verify-email')
                ),
                'notice'
            );
            return;
        }

        // Get application status
        if (function_exists('get_reseller_application_status')) {
            $application_info = get_reseller_application_status($user_email);
            $status = $application_info['status'];
            
            if ($status === 'pending' || $status === 'under_review') {
                wc_print_notice(
                    sprintf(
                        __('⏳ Your reseller application is under review. You will be able to access products once approved. <a href="%s" style="font-weight: 600; text-decoration: underline;">Check Status</a>', 'aakaari'),
                        home_url('/application-pending')
                    ),
                    'notice'
                );
                return;
            }
            
            if ($status === 'rejected') {
                wc_print_notice(
                    sprintf(
                        __('❌ Your reseller application was not approved. Please <a href="%s" style="font-weight: 600; text-decoration: underline;">review your application status</a> for more information.', 'aakaari'),
                        home_url('/become-a-reseller')
                    ),
                    'error'
                );
                return;
            }
            
            if ($status === 'documents_requested') {
                wc_print_notice(
                    sprintf(
                        __('📄 Additional documents requested. Please <a href="%s" style="font-weight: 600; text-decoration: underline;">upload the required documents</a> to proceed.', 'aakaari'),
                        home_url('/become-a-reseller')
                    ),
                    'notice'
                );
                return;
            }
            
            if ($status === 'resubmission_allowed') {
                wc_print_notice(
                    sprintf(
                        __('📝 You can resubmit your application. Please <a href="%s" style="font-weight: 600; text-decoration: underline;">update and resubmit your application</a> to access products.', 'aakaari'),
                        home_url('/become-a-reseller')
                    ),
                    'notice'
                );
                return;
            }
        }

        // Default message for users who haven't applied
        wc_print_notice(
            sprintf(
                __('🎯 Please <a href="%s" style="font-weight: 600; text-decoration: underline;">apply to become a reseller</a> to access products and start earning commissions.', 'aakaari'),
                home_url('/become-a-reseller')
            ),
            'notice'
        );
    }

    /**
     * Redirect non-verified users with notice
     */
    private function redirect_with_notice() {
        if (!is_user_logged_in()) {
            // Redirect to login
            wp_safe_redirect(wp_login_url(get_permalink()));
            exit;
        }

        $user = wp_get_current_user();
        $user_id = $user->ID;
        $user_email = $user->user_email;
        
        $email_verified = get_user_meta($user_id, 'email_verified', true);

        if ($email_verified !== 'true' && $email_verified !== true) {
            // Redirect to verification page
            wp_safe_redirect(home_url('/verify-email'));
            exit;
        }

        // Get application status
        if (function_exists('get_reseller_application_status')) {
            $application_info = get_reseller_application_status($user_email);
            $status = $application_info['status'];
            
            if ($status === 'pending' || $status === 'under_review') {
                // Redirect to application pending page
                wp_safe_redirect(home_url('/application-pending'));
                exit;
            }
            
            if ($status === 'rejected' || $status === 'documents_requested' || $status === 'resubmission_allowed') {
                // Redirect to become a reseller page where they can see their status
                wp_safe_redirect(home_url('/become-a-reseller'));
                exit;
            }
        }

        // Default redirect to become a reseller page
        wp_safe_redirect(home_url('/become-a-reseller'));
        exit;
    }

    /**
     * Get verification status message for current user
     */
    public static function get_verification_status_message() {
        if (!is_user_logged_in()) {
            return array(
                'status' => 'not_logged_in',
                'message' => __('Please login to continue', 'aakaari'),
                'action_url' => wp_login_url(),
                'action_text' => __('Login', 'aakaari'),
            );
        }

        $user = wp_get_current_user();
        $user_id = $user->ID;
        $user_email = $user->user_email;
        
        $email_verified = get_user_meta($user_id, 'email_verified', true);

        if ($email_verified !== 'true' && $email_verified !== true) {
            return array(
                'status' => 'email_not_verified',
                'message' => __('Please verify your email address', 'aakaari'),
                'action_url' => home_url('/verify-email'),
                'action_text' => __('Verify Email', 'aakaari'),
            );
        }

        // Get application status
        if (function_exists('get_reseller_application_status')) {
            $application_info = get_reseller_application_status($user_email);
            $status = $application_info['status'];
            
            if ($status === 'approved') {
                return array(
                    'status' => 'verified',
                    'message' => __('You are an approved reseller', 'aakaari'),
                    'action_url' => home_url('/shop'),
                    'action_text' => __('Browse Products', 'aakaari'),
                );
            }
            
            if ($status === 'pending' || $status === 'under_review') {
                return array(
                    'status' => 'pending_approval',
                    'message' => __('Your application is under review', 'aakaari'),
                    'action_url' => home_url('/application-pending'),
                    'action_text' => __('Check Status', 'aakaari'),
                );
            }
            
            if ($status === 'rejected') {
                return array(
                    'status' => 'rejected',
                    'message' => __('Your application was not approved', 'aakaari'),
                    'action_url' => home_url('/become-a-reseller'),
                    'action_text' => __('View Details', 'aakaari'),
                );
            }
            
            if ($status === 'documents_requested') {
                return array(
                    'status' => 'documents_requested',
                    'message' => __('Additional documents required', 'aakaari'),
                    'action_url' => home_url('/become-a-reseller'),
                    'action_text' => __('Upload Documents', 'aakaari'),
                );
            }
            
            if ($status === 'resubmission_allowed') {
                return array(
                    'status' => 'resubmission_allowed',
                    'message' => __('You can resubmit your application', 'aakaari'),
                    'action_url' => home_url('/become-a-reseller'),
                    'action_text' => __('Resubmit Application', 'aakaari'),
                );
            }
        }

        return array(
            'status' => 'not_applied',
            'message' => __('Please apply to become a reseller', 'aakaari'),
            'action_url' => home_url('/become-a-reseller'),
            'action_text' => __('Apply Now', 'aakaari'),
        );
    }

    /**
     * Restrict WooCommerce product REST API access
     * Blocks AJAX/JavaScript from loading products for non-verified users
     */
    public function restrict_product_api($result, $server, $request) {
        // Check if this is a products endpoint
        $route = $request->get_route();
        
        if (strpos($route, '/wc/') !== false && (strpos($route, '/products') !== false || strpos($route, 'product') !== false)) {
            // Skip for administrators
            if (current_user_can('manage_options')) {
                return $result;
            }

            // Block for non-verified users
            if (!$this->is_verified_seller()) {
                return new WP_Error(
                    'rest_forbidden',
                    __('You must be an approved reseller to access products.', 'aakaari'),
                    array('status' => 403)
                );
            }
        }
        
        return $result;
    }
}

// Initialize
add_action('init', ['Aakaari_Verified_Seller_Restrictions', 'init']);
