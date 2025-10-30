<?php
/**
 * Shipping Configuration
 * Flat rate shipping with conditional free shipping for orders 499+
 *
 * @package Aakaari
 */

defined('ABSPATH') || exit;

/**
 * Add custom flat rate shipping with conditional free shipping
 */
add_filter('woocommerce_package_rates', 'aakaari_conditional_free_shipping', 100, 2);

function aakaari_conditional_free_shipping($rates, $package) {
    // Get cart subtotal
    $cart_total = WC()->cart->get_subtotal();

    // Free shipping threshold (in rupees)
    $free_shipping_threshold = 499;

    // Flat rate shipping cost (for orders below threshold)
    $flat_rate_cost = 40; // You can change this value

    if ($cart_total >= $free_shipping_threshold) {
        // Cart total is 499 or above - offer free shipping
        $rates['free_shipping'] = new WC_Shipping_Rate(
            'free_shipping',
            __('Free Shipping', 'woocommerce'),
            0,
            array(),
            'free_shipping'
        );
    } else {
        // Cart total is below 499 - charge flat rate
        $remaining = $free_shipping_threshold - $cart_total;
        $label = sprintf(
            __('Flat Rate (Add ₹%s for free shipping)', 'woocommerce'),
            number_format($remaining, 2)
        );

        $rates['flat_rate'] = new WC_Shipping_Rate(
            'flat_rate',
            $label,
            $flat_rate_cost,
            array(),
            'flat_rate'
        );
    }

    return $rates;
}

/**
 * Customize shipping method display
 */
add_filter('woocommerce_cart_shipping_method_full_label', 'aakaari_customize_shipping_label', 10, 2);

function aakaari_customize_shipping_label($label, $method) {
    if ($method->cost > 0) {
        $label = $method->get_label() . ': ' . wc_price($method->cost);
    } else {
        $label = $method->get_label();
    }

    return $label;
}

/**
 * Show shipping notice on cart page
 */
add_action('woocommerce_before_cart_table', 'aakaari_shipping_notice');

function aakaari_shipping_notice() {
    $cart_total = WC()->cart->get_subtotal();
    $free_shipping_threshold = 499;

    if ($cart_total < $free_shipping_threshold) {
        $remaining = $free_shipping_threshold - $cart_total;

        echo '<div class="woocommerce-info" style="margin-bottom: 20px;">';
        printf(
            __('Add ₹%s more to get <strong>FREE SHIPPING</strong>!', 'woocommerce'),
            number_format($remaining, 2)
        );
        echo '</div>';
    } else {
        echo '<div class="woocommerce-message" style="margin-bottom: 20px;">';
        echo '<strong>' . __('Congratulations! You qualify for FREE SHIPPING!', 'woocommerce') . '</strong>';
        echo '</div>';
    }
}

/**
 * Enable shipping calculations
 */
add_filter('woocommerce_shipping_calculator_enable_city', '__return_true');
add_filter('woocommerce_shipping_calculator_enable_postcode', '__return_true');

/**
 * Force calculation of shipping
 */
add_action('woocommerce_before_checkout_form', 'aakaari_force_shipping_calculation');

function aakaari_force_shipping_calculation() {
    if (WC()->cart) {
        WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();
    }
}
