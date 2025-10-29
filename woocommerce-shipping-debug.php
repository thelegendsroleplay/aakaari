<?php
/**
 * WooCommerce Shipping Debug
 * Helps identify shipping calculation issues
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Only run in admin or for admin users
if (!is_admin() && !current_user_can('manage_options')) {
    return;
}

add_action('admin_menu', function() {
    add_submenu_page(
        'woocommerce',
        'Shipping Debug',
        'Shipping Debug',
        'manage_options',
        'wc-shipping-debug',
        'wc_shipping_debug_page'
    );
});

function wc_shipping_debug_page() {
    ?>
    <div class="wrap">
        <h1>WooCommerce Shipping Debug</h1>
        <p>Use this page to test shipping calculations for specific addresses.</p>
        
        <form method="post" action="">
            <?php wp_nonce_field('wc_shipping_debug', 'wc_shipping_debug_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="country">Country</label></th>
                    <td>
                        <select name="country" id="country">
                            <?php
                            $countries = WC()->countries->get_countries();
                            foreach ($countries as $code => $name) {
                                echo '<option value="' . esc_attr($code) . '">' . esc_html($name) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="state">State</label></th>
                    <td><input type="text" name="state" id="state" value="" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="postcode">Postcode</label></th>
                    <td><input type="text" name="postcode" id="postcode" value="" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="city">City</label></th>
                    <td><input type="text" name="city" id="city" value="" class="regular-text"></td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="test_shipping" class="button button-primary" value="Test Shipping">
            </p>
        </form>
        
        <?php
        // Process form submission
        if (isset($_POST['test_shipping']) && check_admin_referer('wc_shipping_debug', 'wc_shipping_debug_nonce')) {
            $country = sanitize_text_field($_POST['country']);
            $state = sanitize_text_field($_POST['state']);
            $postcode = sanitize_text_field($_POST['postcode']);
            $city = sanitize_text_field($_POST['city']);
            
            echo '<h2>Test Results</h2>';
            
            // Set customer location
            WC()->customer->set_shipping_location($country, $state, $postcode, $city);
            WC()->customer->set_billing_location($country, $state, $postcode, $city);
            
            // Calculate shipping
            $packages = WC()->cart->get_shipping_packages();
            
            if (empty($packages)) {
                echo '<div class="notice notice-error"><p>No shipping packages found. Make sure you have items in your cart.</p></div>';
            } else {
                echo '<h3>Available Shipping Packages</h3>';
                echo '<pre>';
                print_r($packages);
                echo '</pre>';
                
                echo '<h3>Available Shipping Zones</h3>';
                $zones = WC_Shipping_Zones::get_zones();
                if (empty($zones)) {
                    echo '<p>No shipping zones found. <a href="' . admin_url('admin.php?page=wc-settings&tab=shipping') . '">Configure shipping zones</a>.</p>';
                } else {
                    echo '<ul>';
                    foreach ($zones as $zone) {
                        echo '<li><strong>' . esc_html($zone['zone_name']) . '</strong> (ID: ' . $zone['id'] . ')<br>';
                        echo 'Locations: ';
                        
                        $locations = $zone['zone_locations'];
                        $location_strings = [];
                        foreach ($locations as $location) {
                            $location_strings[] = $location->type . ':' . $location->code;
                        }
                        echo implode(', ', $location_strings);
                        
                        echo '<br>Shipping Methods: ';
                        $methods = $zone['shipping_methods'];
                        $method_strings = [];
                        foreach ($methods as $method) {
                            $method_strings[] = $method->get_title() . ' (' . $method->id . ')';
                        }
                        echo implode(', ', $method_strings);
                        
                        echo '</li>';
                    }
                    echo '</ul>';
                }
                
                echo '<h3>Available Shipping Methods for This Address</h3>';
                
                foreach ($packages as $package_key => $package) {
                    // Clear cached rates
                    WC()->session->set('shipping_for_package_' . $package_key, false);
                    
                    $shipping = WC()->shipping->calculate_shipping_for_package($package);
                    
                    if (empty($shipping['rates'])) {
                        echo '<div class="notice notice-warning"><p>No shipping methods available for this address.</p></div>';
                    } else {
                        echo '<ul>';
                        foreach ($shipping['rates'] as $rate) {
                            echo '<li>';
                            echo '<strong>' . esc_html($rate->get_label()) . '</strong>: ';
                            echo wc_price($rate->get_cost());
                            echo ' (ID: ' . $rate->get_id() . ')';
                            echo '</li>';
                        }
                        echo '</ul>';
                    }
                }
            }
        }
        ?>
    </div>
    <?php
}