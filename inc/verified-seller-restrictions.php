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
        // Hide products from non-verified users on shop pages
        add_action('pre_get_posts', [$this, 'restrict_product_visibility']);

        // Restrict single product access
        add_action('template_redirect', [$this, 'restrict_product_access']);

        // Restrict cart access
        add_action('template_redirect', [$this, 'restrict_cart_access']);

        // Prevent adding to cart if not verified
        add_filter('woocommerce_is_purchasable', [$this, 'restrict_purchase'], 10, 2);

        // Show verification notice on restricted pages
        add_action('woocommerce_before_main_content', [$this, 'show_verification_notice']);

        // Hide add to cart button for non-verified users
        add_filter('woocommerce_is_purchasable', [$this, 'hide_add_to_cart_for_non_verified'], 10, 2);

        // Restrict AJAX add to cart
        add_filter('woocommerce_add_to_cart_validation', [$this, 'validate_add_to_cart'], 10, 3);
    }

    /**
     * Check if current user is a verified seller
     */
    private function is_verified_seller() {
        // Administrators have full access
        if (current_user_can('manage_options')) {
            return true;
        }

        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        // Check if user has verified email
        $email_verified = get_user_meta($user_id, 'email_verified', true);
        if ('true' != $email_verified && true !== $email_verified) {
            return false;
        }

        // Check if user has approved onboarding status
        $onboarding_status = get_user_meta($user_id, 'onboarding_status', true);
        if ('approved' !== $onboarding_status && 'completed' !== $onboarding_status) {
            return false;
        }

        // Check if user has reseller role
        $user = wp_get_current_user();
        if (!in_array('reseller', (array) $user->roles) && !in_array('administrator', (array) $user->roles)) {
            return false;
        } // Note: Admin check is now at the top of the function.

        return true;
    }

    /**
     * Restrict product visibility in shop archives
     */
    public function restrict_product_visibility($query) {
        // Only apply to main query on product archives
        if (!$query->is_main_query() || !is_admin() && (!is_shop() && !is_product_category() && !is_product_tag())) {
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

        if (!is_user_logged_in()) {
            wc_print_notice(
                sprintf(
                    __('Please <a href="%s">login</a> to view products.', 'aakaari'),
                    wp_login_url(get_permalink())
                ),
                'notice'
            );
            return;
        }

        $user_id = get_current_user_id();
        $email_verified = get_user_meta($user_id, 'email_verified', true);
        $onboarding_status = get_user_meta($user_id, 'onboarding_status', true);

        if ($email_verified !== 'true' && $email_verified !== true) {
            wc_print_notice(
                __('Please verify your email address to access products.', 'aakaari'),
                'notice'
            );
            return;
        }

        if ($onboarding_status === 'pending') {
            wc_print_notice(
                __('Your seller application is pending approval. You will be able to access products once approved.', 'aakaari'),
                'notice'
            );
            return;
        }

        if ($onboarding_status !== 'approved') {
            wc_print_notice(
                sprintf(
                    __('Please <a href="%s">apply to become a seller</a> to access products.', 'aakaari'),
                    home_url('/become-a-reseller')
                ),
                'notice'
            );
            return;
        }
    }

    /**
     * Redirect non-verified users with notice
     */
    private function redirect_with_notice() {
        if (!is_user_logged_in()) {
            // Redirect to login
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }

        $user_id = get_current_user_id();
        $email_verified = get_user_meta($user_id, 'email_verified', true);
        $onboarding_status = get_user_meta($user_id, 'onboarding_status', true);

        if ($email_verified !== 'true' && $email_verified !== true) {
            // Redirect to verification page
            wp_redirect(home_url('/verify-email'));
            exit;
        }

        if ($onboarding_status === 'pending' || $onboarding_status === 'submitted') {
            // Redirect to application pending page
            wp_redirect(home_url('/application-pending'));
            exit;
        }

        if ($onboarding_status !== 'approved' && $onboarding_status !== 'completed') {
            // Redirect to become a seller page
            wp_redirect(home_url('/become-a-reseller'));
            exit;
        }

        // Default redirect to shop
        wp_redirect(home_url('/shop'));
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

        $user_id = get_current_user_id();
        $email_verified = get_user_meta($user_id, 'email_verified', true);
        $onboarding_status = get_user_meta($user_id, 'onboarding_status', true);

        if ($email_verified !== 'true' && $email_verified !== true) {
            return array(
                'status' => 'email_not_verified',
                'message' => __('Please verify your email address', 'aakaari'),
                'action_url' => home_url('/verify-email'),
                'action_text' => __('Verify Email', 'aakaari'),
            );
        }

        if ($onboarding_status === 'pending' || $onboarding_status === 'submitted') {
            return array(
                'status' => 'pending_approval',
                'message' => __('Your application is pending approval', 'aakaari'),
                'action_url' => home_url('/application-pending'),
                'action_text' => __('Check Status', 'aakaari'),
            );
        }

        if ($onboarding_status === 'approved' || $onboarding_status === 'completed') {
            return array(
                'status' => 'verified',
                'message' => __('You are a verified seller', 'aakaari'),
                'action_url' => '',
                'action_text' => '',
            );
        }

        return array(
            'status' => 'not_applied',
            'message' => __('Please apply to become a seller', 'aakaari'),
            'action_url' => home_url('/become-a-reseller'),
            'action_text' => __('Become a Seller', 'aakaari'),
        );
    }
}

// Initialize
add_action('init', ['Aakaari_Verified_Seller_Restrictions', 'init']);
