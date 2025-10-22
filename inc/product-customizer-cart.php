<?php
/**
 * Aakaari Theme: Product Customizer Cart Handling
 * Manages adding custom data to cart items and calculating prices.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * 1. Add custom data to the cart item data array.
 *
 * Hooks into WooCommerce when a product is added to the cart.
 * Reads the hidden input fields from the customizer form submission.
 * Adds the custom data (designs, print type, color) to the cart item's data array.
 * Adds a unique key to ensure items with different customizations are separate lines in the cart.
 */
function aakaari_add_customizer_data_to_cart_item( $cart_item_data, $product_id, $variation_id ) {

    // Check for 'custom_designs_data' hidden input (should contain JSON string)
	if ( isset( $_POST['custom_designs_data'] ) ) {
		$designs_json = stripslashes( $_POST['custom_designs_data'] ); // Clean up backslashes
		$designs_data = json_decode( $designs_json, true ); // Decode JSON into PHP array

        // Basic validation: ensure it's a non-empty array before storing
		if ( is_array( $designs_data ) && ! empty( $designs_data ) ) {
			// Use a unique prefix for your theme's custom data
			$cart_item_data['aakaari_custom_designs'] = $designs_data;
		} else {
             // Optional: Add an error notice if the submitted design data is invalid
             // wc_add_notice( __('Error processing custom designs data.', 'aakaari'), 'error' );
             // You could also throw an exception here to prevent adding to cart:
             // throw new Exception('Invalid custom design data format.');
        }
	}

    // Check for 'custom_print_type_id' hidden input
	if ( isset( $_POST['custom_print_type_id'] ) ) {
		$cart_item_data['aakaari_print_type_id'] = sanitize_text_field( $_POST['custom_print_type_id'] );
	}

    // Check for 'custom_product_color' hidden input
    if ( isset( $_POST['custom_product_color'] ) ) {
         $cart_item_data['aakaari_product_color'] = sanitize_text_field( $_POST['custom_product_color'] );
     }

	// Add a unique key if any custom data exists. This prevents WooCommerce
    // from merging cart items that have different customizations.
	if ( ! empty( $cart_item_data['aakaari_custom_designs'] ) || ! empty( $cart_item_data['aakaari_print_type_id'] ) || ! empty( $cart_item_data['aakaari_product_color'] ) ) {
		$cart_item_data['unique_key'] = md5( microtime().rand() . serialize($cart_item_data) ); // More robust unique key
	}

	return $cart_item_data; // Return modified (or original) cart item data
}
// Hook the function to the WooCommerce filter
add_filter( 'woocommerce_add_cart_item_data', 'aakaari_add_customizer_data_to_cart_item', 10, 3 );


/**
 * 2. Calculate and set the custom price for the cart item.
 *
 * Hooks into WooCommerce *before* cart totals are calculated.
 * Loops through each item in the cart.
 * If an item has our custom data ('aakaari_custom_designs' and 'aakaari_print_type_id'),
 * it recalculates the price: Base Price + Total Print Cost.
 * The Total Print Cost is calculated using the selected Print Type's pricing model (from ACF)
 * and the dimensions of each design added by the user.
 * It uses a helper function `aakaari_php_calculate_design_cost` for the calculation.
 *
 * @param WC_Cart $cart The WooCommerce cart object.
 */
function aakaari_calculate_customizer_cart_price( $cart ) {
    // Don't run in admin backend unless it's an AJAX request (e.g., updating cart)
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

    // Prevent this function from running multiple times during the same calculation cycle
	if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
		return;
	}

	// Loop through each item currently in the cart
	foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {

		// Check if our custom data keys exist for this specific cart item
		if ( isset( $cart_item['aakaari_custom_designs'] ) && isset( $cart_item['aakaari_print_type_id'] ) ) {

			$product = $cart_item['data']; // Get the WC_Product object for this cart item
            // Get the *original* base price stored in WP, ignoring previous modifications
			$base_price = (float) $product->get_price('edit');
			$total_print_cost = 0;

            // Ensure ACF function exists before trying to use it
            if ( ! function_exists('get_field') ) {
                error_log('Aakaari Theme Error: ACF function get_field() not found during cart price calculation.');
                continue; // Skip price calculation for this item if ACF is missing
            }

			// --- Calculate Total Print Cost ---
			$print_type_id_str = $cart_item['aakaari_print_type_id']; // e.g., "pt_123"
			// Extract the numeric post ID from our prefixed string
			$print_type_post_id = (int) str_replace( 'pt_', '', $print_type_id_str );

			// Validate the extracted ID and check if it's the correct post type
			if ( $print_type_post_id > 0 && get_post_type($print_type_post_id) === 'print_type' ) { // IMPORTANT: Replace 'print_type' with your CPT slug

                // Get pricing model and price-per-unit from the Print Type post's ACF fields
				$pricing_model = get_field( 'pricing_model', $print_type_post_id );
				$price_per_unit = get_field( 'price', $print_type_post_id ); // ACF number field returns string or number

                // Ensure we got valid numeric price data
                if ( $pricing_model && is_numeric($price_per_unit) ) {
                    $price_per_unit = (float) $price_per_unit; // Convert to float
					$all_designs = $cart_item['aakaari_custom_designs'];

					// Loop through designs saved for this cart item
					if ( is_array( $all_designs ) ) {
						foreach ( $all_designs as $side_id => $side_designs ) { // Loop through sides (e.g., 'side_0', 'side_1')
							if ( is_array( $side_designs ) ) {
								foreach ( $side_designs as $design ) { // Loop through designs on that side
									// Validate design structure before passing to calculation function
									if ( is_array( $design ) && isset( $design['width'] ) && isset( $design['height'] ) ) {
										$total_print_cost += aakaari_php_calculate_design_cost( $design, $pricing_model, $price_per_unit );
									}
								}
							}
						}
					}
				} else {
                    error_log('Aakaari Theme Error: Invalid pricing data for Print Type ID: ' . $print_type_post_id);
                }
			} else {
                 error_log('Aakaari Theme Error: Invalid Print Type Post ID derived from: ' . $print_type_id_str);
            }
            // --- End Print Cost Calculation ---

			// --- Set the final calculated price for this cart item ---
			// This price will be used by WooCommerce for cart totals and checkout.
			$final_price = $base_price + $total_print_cost;
			$cart_item['data']->set_price( $final_price );
		}
	}
}
// Hook the function to run before cart totals are calculated (priority 20 runs after default calculations)
add_action( 'woocommerce_before_calculate_totals', 'aakaari_calculate_customizer_cart_price', 20, 1 );


/**
 * PHP Helper function to calculate cost for a single design.
 * The calculation logic MUST exactly match the JavaScript version (`calculatePrintCost`).
 *
 * @param array  $design         The design object (must contain 'width' and 'height').
 * @param string $pricing_model  The pricing model slug ('fixed', 'per-inch', 'per-px').
 * @param float  $price_per_unit The price associated with the model.
 * @return float Calculated cost for this single design. Returns 0 if input is invalid.
 */
function aakaari_php_calculate_design_cost( $design, $pricing_model, $price_per_unit ) {
    // Basic validation of input
	if ( ! is_array( $design ) || ! isset( $design['width'] ) || ! isset( $design['height'] ) ) {
		return 0;
	}

	$width = (float) $design['width'];
	$height = (float) $design['height'];

    // Ensure dimensions and price are valid positive numbers
	if ( $width <= 0 || $height <= 0 || $price_per_unit < 0 ) {
		return 0;
	}

	// Calculate cost based on the pricing model
	switch ( $pricing_model ) {
		case 'fixed':
			return $price_per_unit; // Return the fixed price directly
		case 'per-inch':
			// Assumption: 12 pixels = 1 inch (Must be consistent with JS calculation)
			$square_inches = ( $width * $height ) / 144.0;
			return $square_inches * $price_per_unit;
		case 'per-px':
			$pixels = $width * $height;
			return $pixels * $price_per_unit;
		default:
            error_log('Aakaari Theme Warning: Unknown pricing model encountered: ' . $pricing_model);
			return 0; // Return 0 for unknown models
	}
}


/**
 * 3. Display custom data in cart and checkout item details.
 *
 * Hooks into WooCommerce to add extra lines under the product name in the cart/checkout table.
 * Displays the selected Color, Print Method, and number of Custom Designs.
 *
 * @param array $item_data Existing item data lines.
 * @param array $cart_item Cart item data containing our custom keys.
 * @return array Modified item data lines.
 */
function aakaari_display_customizer_data_cart_checkout( $item_data, $cart_item ) {

    // Display selected Color
    if ( isset( $cart_item['aakaari_product_color'] ) ) {
        $item_data[] = array(
            'key'     => __( 'Color', 'aakaari' ), // Translatable label
            'value'   => sanitize_text_field( $cart_item['aakaari_product_color'] ), // The value stored
            'display' => sanitize_text_field( $cart_item['aakaari_product_color'] ), // How it appears in the cart table
        );
    }

    // Display selected Print Method
	if ( isset( $cart_item['aakaari_print_type_id'] ) ) {
		$print_type_id_str = $cart_item['aakaari_print_type_id'];
		$print_type_post_id = (int) str_replace( 'pt_', '', $print_type_id_str );

        // Verify the post ID corresponds to a valid 'print_type' post
		if ( $print_type_post_id > 0 && get_post_type($print_type_post_id) === 'print_type' ) { // Replace 'print_type' with your CPT slug
			$item_data[] = array(
				'key'     => __( 'Print Method', 'aakaari' ),
				'value'   => get_the_title( $print_type_post_id ), // Get the Print Type post title
                'display' => get_the_title( $print_type_post_id ),
			);
		}
	}

    // Display the number of Custom Designs added
	if ( isset( $cart_item['aakaari_custom_designs'] ) && is_array( $cart_item['aakaari_custom_designs'] ) ) {
		$total_designs = 0;
		// Sum up designs across all sides
		foreach ( $cart_item['aakaari_custom_designs'] as $side_designs ) {
			if ( is_array( $side_designs ) ) {
				$total_designs += count( $side_designs );
			}
		}
		if ( $total_designs > 0 ) {
			$item_data[] = array(
				'key'     => __( 'Custom Designs', 'aakaari' ),
				'value'   => $total_designs . ( $total_designs > 1 ? ' designs' : ' design' ), // Pluralize if needed
                'display' => $total_designs . ( $total_designs > 1 ? ' designs' : ' design' ),
                // Future Enhancement: Maybe add a link here to view a proof image if generated?
			);
		}
	}

	return $item_data; // Return the (potentially) modified array of display lines
}
// Hook the function to the WooCommerce filter for item data
add_filter( 'woocommerce_get_item_data', 'aakaari_display_customizer_data_cart_checkout', 10, 2 );


/**
 * 4. Save custom data as order item meta when an order is created.
 *
 * Hooks into WooCommerce during checkout processing.
 * Takes the custom data stored in the cart item and saves it as meta data
 * attached to the corresponding line item in the new order.
 * This ensures the customization details are permanently stored with the order.
 *
 * @param WC_Order_Item_Product $item          Order item object being created.
 * @param string                $cart_item_key Key of the item in the cart array.
 * @param array                 $values        Data associated with the cart item (includes our custom data).
 * @param WC_Order              $order         The order object being created.
 */
function aakaari_save_customizer_data_to_order_item( $item, $cart_item_key, $values, $order ) {

    // Save selected Color as order item meta
    if ( isset( $values['aakaari_product_color'] ) ) {
        // add_meta_data( $key, $value, $unique = false )
        // $unique = true means it won't add if the key already exists (useful here)
        $item->add_meta_data( __( 'Color', 'aakaari' ), sanitize_text_field( $values['aakaari_product_color'] ), true );
    }

    // Save selected Print Method as order item meta
	if ( isset( $values['aakaari_print_type_id'] ) ) {
		$print_type_id_str = $values['aakaari_print_type_id'];
		$print_type_post_id = (int) str_replace( 'pt_', '', $print_type_id_str );

		if ( $print_type_post_id > 0 && get_post_type($print_type_post_id) === 'print_type' ) { // Replace 'print_type' with CPT slug
            // Save the human-readable name
			$item->add_meta_data( __( 'Print Method', 'aakaari' ), get_the_title( $print_type_post_id ), true );
            // Also save the ID as hidden meta (prefixed with _) for potential backend use
            $item->add_meta_data( '_print_method_id', $print_type_id_str, true );
		}
	}

    // Save Custom Design details as order item meta
	if ( isset( $values['aakaari_custom_designs'] ) && is_array( $values['aakaari_custom_designs'] ) ) {
		$total_designs = 0;
        $design_summary = []; // Array to store a brief summary for display

		// Calculate total and create summary string
		foreach ( $values['aakaari_custom_designs'] as $side_id => $side_designs ) {
			if ( is_array( $side_designs ) ) {
				$total_designs += count( $side_designs );
                foreach ($side_designs as $design) {
                    if (is_array($design)) {
                        // Create a short description (e.g., "Logo.png (Image)", "Hello (Text)")
                        $name = $design['fileName'] ?? $design['content'] ?? 'Custom';
                        $type = $design['type'] ?? 'item';
                        // Keep summary relatively brief
                        $summary_part = substr($name, 0, 20) . ($type === 'text' ? ' (Text)' : '');
                        $design_summary[] = $summary_part;
                    }
                }
			}
		}

        // Save the total count (visible)
		if ( $total_designs > 0 ) {
			$item->add_meta_data( __( 'Custom Designs', 'aakaari' ), $total_designs . ( $total_designs > 1 ? ' designs' : ' design' ), true );
            // Save the summary string (hidden meta)
            $item->add_meta_data( '_custom_designs_summary', implode('; ', $design_summary), true );

            // --- IMPORTANT CONSIDERATION ---
            // Storing the full JSON of design data (`$values['aakaari_custom_designs']`) in order meta
            // can make the database large and slow if designs are complex or numerous.
            // Consider if you *really* need the exact position/size data saved with the order itself.
            // Alternatives:
            // 1. Generate a unique ID for the customization and store *that* ID.
            // 2. Save the customization details to a separate custom database table linked to the order item ID.
            // 3. Generate a preview image (proof) and save its URL as meta.
            // If you DO need the full JSON temporarily, use hidden meta:
            // $item->add_meta_data( '_custom_designs_data_json', wp_json_encode($values['aakaari_custom_designs']), true );
		}
	}
}
// Hook the function to the WooCommerce action during checkout
add_action( 'woocommerce_checkout_create_order_line_item', 'aakaari_save_customizer_data_to_order_item', 10, 4 );


/**
 * 5. Optional: Control visibility of custom meta keys in the admin order view.
 * By default, meta keys starting with '_' are hidden. This function can make them visible if needed.
 *
 * @param string $display_key The meta key being considered for display.
 * @param object $meta        The meta data object.
 * @param object $item        The order item object.
 * @return string The potentially modified display key (or empty string to hide).
 */
function aakaari_filter_order_item_display_meta_key( $display_key, $meta, $item ) {
    // Example: Show the '_print_method_id' if you want admins to see it easily
    // if ( $meta->key === '_print_method_id' ) {
    //     $display_key = __( 'Print Method ID (Internal)', 'aakaari' );
    // }

    // Example: Show the design summary
     if ( $meta->key === '_custom_designs_summary' ) {
         $display_key = __( 'Design Details', 'aakaari' ); // Change the label shown in admin
     }

     // Example: Show the full JSON data (Use with caution!)
     // if ( $meta->key === '_custom_designs_data_json' ) {
     //    $display_key = __( 'Full Design Data (JSON)', 'aakaari' );
     // }


    // Keep default behavior: Hide keys starting with '_' unless explicitly handled above
    if ( substr( $meta->key, 0, 1 ) === '_' && $display_key === $meta->key ) {
        // If we didn't specifically choose to show this hidden key above, hide it.
        return ''; // Return empty string to hide
    }

    return $display_key; // Return original key for standard meta or explicitly shown hidden meta
}
// Hook the function to the WooCommerce filter for admin meta display
add_filter( 'woocommerce_order_item_display_meta_key', 'aakaari_filter_order_item_display_meta_key', 10, 3 );

?>