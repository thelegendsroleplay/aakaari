<?php
/**
 * Multi-step Checkout (Aakaari)
 * Path: yourtheme/woocommerce/checkout/form-checkout.php
 *
 * CUSTOM HTML STRUCTURE VERSION (with Country/State Fix)
 * - Manually builds a custom HTML layout for each field using a helper function.
 * - Uses custom CSS classes (aak-form-row, aak-label, etc.).
 * - Styles are applied via CSS to these custom classes.
 * - **Exception:** Country & State fields use `woocommerce_form_field()` for JS compatibility,
 * styled via specific CSS rules targeting WC wrappers inside our structure.
 * - This avoids conflicts with theme/WooCommerce default field structure.
 */

defined('ABSPATH') || exit;

$checkout = WC()->checkout();

if (!$checkout) return;

do_action('woocommerce_before_checkout_form', $checkout);

// Require login if needed
if (!is_user_logged_in() && $checkout->is_registration_required()) {
    wc_print_notice(esc_html__('You must be logged in to checkout.', 'woocommerce'));
    return;
}

// Get all checkout fields
$fields = $checkout->get_checkout_fields();

/**
 * Helper function to render a single field with custom structure.
 *
 * @param string $key The field key (e.g., 'billing_first_name').
 * @param array $field The field properties array from WooCommerce.
 * @param mixed $value Optional. The current value of the field. Fetches from checkout if null.
 */
function aakaari_render_checkout_field($key, $field, $value = null) {
    if ( is_string( $field ) ) { // Handle cases where field might be passed as string? Unlikely but safe.
        $field = array( 'type' => $field );
    }

    $defaults = array(
        'type'              => 'text',
        'label'             => '',
        'description'       => '',
        'placeholder'       => '',
        'maxlength'         => false,
        'required'          => false,
        'autocomplete'      => false,
        'id'                => $key,
        'class'             => array(), // Original WC classes
        'label_class'       => array(),
        'input_class'       => array(), // Classes intended for the <input>
        'return'            => false, // We will echo directly
        'options'           => array(),
        'custom_attributes' => array(),
        'validate'          => array(),
        'default'           => '',
        'autofocus'         => '',
        'priority'          => '',
    );

    $field = wp_parse_args($field, $defaults);

    // Ensure value is fetched correctly
    $value = is_null( $value ) ? $field['default'] : $value;
    // For good measure, try getting value from checkout if still null or empty, common for non-posted fields
    if ( is_null( $value ) || $value === '') {
        $value = WC()->checkout()->get_value( $key );
    }


    // Custom data attributes
    $custom_attributes_html = '';
    if (!empty($field['custom_attributes']) && is_array($field['custom_attributes'])) {
        foreach ($field['custom_attributes'] as $attribute => $attribute_value) {
            $custom_attributes_html .= ' ' . esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
        }
    }

    $field_id = esc_attr($field['id']);
    $field_label = $field['label'];
    $field_required = $field['required'];

    // --- Build Custom Row Classes ---
    $row_classes = ['aak-form-row'];
    // Layout classes (first, last, wide)
    if (!empty($field['class']) && in_array('form-row-first', $field['class'])) $row_classes[] = 'aak-form-row-first';
    if (!empty($field['class']) && in_array('form-row-last', $field['class'])) $row_classes[] = 'aak-form-row-last';
    if (!empty($field['class']) && in_array('form-row-wide', $field['class'])) $row_classes[] = 'aak-form-row-wide';

    // Specific field type classes for targeting if needed
    $row_classes[] = 'aak-field-type-' . esc_attr($field['type']);
    $row_classes[] = 'aak-field-key-' . esc_attr($key); // e.g., aak-field-key-billing_first_name

    // 3-col layout classes based on key
    if ($key === 'billing_city' || $key === 'shipping_city') $row_classes[] = 'aak-city-field';
    if ($key === 'billing_state' || $key === 'shipping_state') $row_classes[] = 'aak-state-field';
    if ($key === 'billing_postcode' || $key === 'shipping_postcode') $row_classes[] = 'aak-postcode-field';

    // Validation classes for JS/CSS
    if (!empty($field['validate'])) {
        foreach ($field['validate'] as $validate) {
            $row_classes[] = 'validate-' . $validate;
        }
    }
    if ($field_required) {
        $row_classes[] = 'validate-required';
    }

    // --- Start Outputting Custom Structure ---
    ?>
    <div class="<?php echo esc_attr(implode(' ', $row_classes)); ?>" id="<?php echo esc_attr($key); ?>_field" data-priority="<?php echo esc_attr($field['priority']); ?>">
        <?php // Label for non-checkbox fields ?>
        <?php if ($field_label && 'checkbox' !== $field['type']) : ?>
            <label for="<?php echo esc_attr($field_id); ?>" class="aak-label <?php echo esc_attr( implode( ' ', $field['label_class'] ) ); ?>">
                <?php echo wp_kses_post($field_label); ?>
                <?php if ($field_required) : ?>
                    <span class="required" aria-hidden="true">*</span>
                <?php endif; ?>
            </label>
        <?php endif; ?>

        <?php // Input container ?>
        <div class="aak-input-container">
        <?php
            // --- Manually build the input element ---
            $input_classes = array_merge( ['input-text'], $field['input_class'] ); // Add default WC class + custom ones
            $attrs = '';
            $attrs .= ' name="' . esc_attr($key) . '"';
            $attrs .= ' id="' . esc_attr($field_id) . '"';
            $attrs .= ' placeholder="' . esc_attr($field['placeholder']) . '"';
            $attrs .= ' class="' . esc_attr(implode(' ', $input_classes)) . '"';
            $attrs .= ' autocomplete="' . esc_attr($field['autocomplete']) . '"';
            $attrs .= $field['autofocus'] ? ' autofocus="autofocus"' : '';
            $attrs .= $field_required ? ' aria-required="true"' : '';
            $attrs .= $field['maxlength'] ? ' maxlength="' . esc_attr($field['maxlength']) . '"' : '';
            $attrs .= $custom_attributes_html; // Add custom attributes

            // --- RENDER SPECIFIC FIELD TYPES ---
            switch ($field['type']) {
                case 'textarea':
                    echo '<textarea ' . $attrs . '>' . esc_textarea($value) . '</textarea>';
                    break;

                case 'checkbox':
                    // Checkbox needs label wrapping the input
                    // Remove input-text class, add input-checkbox
                    $checkbox_attrs = str_replace('input-text', 'input-checkbox', $attrs);
                    // Add .checkbox class to label_class array if not present
                    if (!in_array('checkbox', $field['label_class'])) {
                        $field['label_class'][] = 'checkbox';
                    }
                    echo '<label class="checkbox-wrapper aak-label ' . esc_attr( implode( ' ', $field['label_class'] ) ) . '">'; // Add checkbox class here
                    echo '<input type="checkbox" ' . $checkbox_attrs . ' value="1" ' . checked($value, 1, false) . ' />';
                    echo '<span>' . wp_kses_post($field_label) . ($field_required ? '&nbsp;<span class="required">*</span>' : '') . '</span>'; // Checkbox label text
                    echo '</label>';
                    break;

                 // **** START COUNTRY/STATE FIX ****
                 case 'country': // Let WC handle complex Country field
                 case 'state':   // Let WC handle complex State field
                     // Output WC's default structure directly for these complex fields
                     // Our CSS will target the '.form-row' inside '.aak-input-container'
                     $field['label'] = ''; // Prevent WC from rendering its label inside our container
                     $field['return'] = true; // Get the HTML instead of echoing
                     echo woocommerce_form_field( $key, $field, $value );
                     break; // IMPORTANT: End case here
                 // **** END COUNTRY/STATE FIX ****

                 case 'select': // Handle REGULAR select fields manually
                    $options_html = '';
                    if (!empty($field['options'])) {
                         // Add placeholder option if set
                         if ( ! empty( $field['placeholder'] ) ) {
                             $options_html .= '<option value="">' . esc_html( $field['placeholder'] ) . '</option>';
                         }
                        foreach ($field['options'] as $option_key => $option_text) {
                            $options_html .= '<option value="' . esc_attr($option_key) . '" '. selected($value, $option_key, false) . '>' . esc_html($option_text) .'</option>';
                        }
                    }
                    echo '<select ' . $attrs . '>' . $options_html . '</select>';
                     // Note: Requires Select2 JS initialization if used for non-country/state fields
                    break;

                case 'radio': // Example if needed
                     if ( ! empty( $field['options'] ) ) {
                         echo '<ul class="aak-radio-list">';
                         foreach ( $field['options'] as $option_key => $option_text ) {
                             echo '<li>';
                             echo '<label class="aak-radio-label">';
                             echo '<input type="radio" ' . str_replace('input-text', 'input-radio', $attrs) . ' value="' . esc_attr( $option_key ) . '"' . checked( $value, $option_key, false ) . ' />';
                             echo esc_html( $option_text );
                             echo '</label>';
                             echo '</li>';
                         }
                         echo '</ul>';
                     }
                    break;

                // Add cases for 'password', 'number', etc. if needed, adjusting $attrs slightly
                case 'tel':
                case 'email':
                case 'password':
                case 'number':
                default: // 'text'
                    echo '<input type="' . esc_attr($field['type']) . '" ' . $attrs . ' value="' . esc_attr($value) . '" />';
                    break;
            } // End switch
         ?>
        </div> <?php // End aak-input-container ?>

        <?php // Description (if any) ?>
        <?php if (!empty($field['description'])) : ?>
            <span class="description aak-field-description" id="<?php echo esc_attr($field_id); ?>-description" aria-hidden="true"><?php echo wp_kses_post($field['description']); ?></span>
        <?php endif; ?>
    </div> <?php // End aak-form-row ?>
    <?php
} // End aakaari_render_checkout_field()
?>

<div id="checkout-container" class="aak-checkout">
    <div class="progress-bar-container">
         <div class="progress-bar-steps">
             <div class="progress-step active" id="progress-step-1">
                 <div class="progress-step-icon">1</div>
                 <div class="progress-step-text">
                     <?php esc_html_e('Information', 'woocommerce'); ?>
                     <span class="progress-step-subtitle"><?php esc_html_e('Contact & delivery', 'woocommerce'); ?></span>
                 </div>
             </div>
             <div class="progress-step" id="progress-step-2">
                 <div class="progress-step-icon">2</div>
                 <div class="progress-step-text">
                     <?php esc_html_e('Shipping', 'woocommerce'); ?>
                     <span class="progress-step-subtitle"><?php esc_html_e('Choose method', 'woocommerce'); ?></span>
                 </div>
             </div>
             <div class="progress-step" id="progress-step-3">
                 <div class="progress-step-icon">3</div>
                 <div class="progress-step-text">
                     <?php esc_html_e('Payment', 'woocommerce'); ?>
                     <span class="progress-step-subtitle"><?php esc_html_e('Secure checkout', 'woocommerce'); ?></span>
                 </div>
             </div>
         </div>
     </div>

    <div class="page-container">
        <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="back-to-cart">
             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
             </svg>
             <?php esc_html_e('Back to Cart', 'woocommerce'); ?>
         </a>

        <form name="checkout" method="post" class="checkout woocommerce-checkout" id="checkout-form"
              action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" novalidate> <?php // Added enctype ?>

            <?php // Output hidden WC fields needed for processing ?>
            <?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
            <input type="hidden" name="woocommerce_checkout_update_totals" value="false"> <?php // Prevent unnecessary Ajax on field changes initially ?>

            <div class="checkout-grid">
                <div class="form-column">
                    <div id="step-1-content">
                        <div class="card" id="aak-contact-card">
                            <div class="card-header">
                                <div class="card-icon">
                                     <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                 </div>
                                <div class="card-text">
                                    <h2><?php esc_html_e('Contact Information', 'woocommerce'); ?></h2>
                                    <p><?php esc_html_e('How can we reach you?', 'woocommerce'); ?></p>
                                </div>
                            </div>

                            <?php
                            // Render Email and Phone using our custom function
                            if (isset($fields['billing']['billing_email'])) {
                                // Add classes for layout
                                $fields['billing']['billing_email']['class'][] = 'form-row-first';
                                aakaari_render_checkout_field('billing_email', $fields['billing']['billing_email']);
                                unset($fields['billing']['billing_email']); // Remove from main loop later
                            }
                            if (isset($fields['billing']['billing_phone'])) {
                                // Add classes for layout
                                $fields['billing']['billing_phone']['class'][] = 'form-row-last';
                                aakaari_render_checkout_field('billing_phone', $fields['billing']['billing_phone']);
                                unset($fields['billing']['billing_phone']); // Remove from main loop later
                            }

                             // Render optional 'Email me' checkbox (Manually creating for simplicity)
                             ?>
                             <div class="aak-form-row aak-form-row-wide">
                                <?php
                                $marketing_field = array(
                                    'type' => 'checkbox',
                                    'label' => __( 'Email me with news and exclusive offers', 'aakaari' ), // Use your theme text domain
                                    'required' => false,
                                    'id' => 'marketing_opt_in',
                                );
                                aakaari_render_checkout_field('marketing_opt_in', $marketing_field, checked( WC()->checkout()->get_value('marketing_opt_in'), 1, false ) );
                                ?>
                             </div>
                        </div>

                        <div class="card" id="aak-address-card">
                             <div class="card-header">
                                 <div class="card-icon">
                                     <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                 </div>
                                 <div class="card-text">
                                     <h2><?php esc_html_e('Address Details', 'woocommerce'); ?></h2>
                                     <p><?php esc_html_e('Where should we deliver?', 'woocommerce'); ?></p>
                                 </div>
                             </div>

                            <?php
                            // Render Billing Address fields using our custom function
                            echo '<div class="woocommerce-billing-fields">'; // Optional: Keep WC wrapper if hooks rely on it
                            echo '<h3 class="billing-details-header">' . esc_html__('Billing details', 'woocommerce') . '</h3>';
                            foreach ($fields['billing'] as $key => $field) {
                                aakaari_render_checkout_field($key, $field);
                            }
                            echo '</div>'; // end .woocommerce-billing-fields


                            // Render Shipping Address fields (conditionally)
                            if (WC()->cart->needs_shipping_address()) : ?>
                                <div class="woocommerce-shipping-fields"> <?php // Optional: Keep WC wrapper ?>
                                    <?php if (true === WC()->cart->needs_shipping_address()) : ?>
                                        <?php // "Ship to different address" Checkbox using our function ?>
                                        <?php
                                        $ship_checkbox_field = array(
                                            'type' => 'checkbox',
                                            'class' => array('form-row-wide', 'update_totals_on_change'), // Added WC class for JS
                                            'label_class' => array('checkbox-wrapper'), // Apply wrapper style
                                            'label' => __('Ship to a different address?', 'woocommerce'),
                                            'required' => false,
                                            'id' => 'ship-to-different-address-checkbox', // Ensure ID matches WC JS
                                        );
                                        // Render checkbox within our structure
                                        echo '<div class="aak-form-row aak-form-row-wide" id="ship-to-different-address">';
                                        aakaari_render_checkout_field('ship_to_different_address', $ship_checkbox_field, $checkout->get_value( 'ship_to_different_address' ));
                                        echo '</div>';
                                        ?>

                                        <div class="shipping_address" style="<?php echo ($checkout->get_value('ship_to_different_address') ? '' : 'display: none;');?>">
                                            <?php
                                            // Render shipping fields using our function
                                            foreach ($fields['shipping'] as $key => $field) {
                                                 aakaari_render_checkout_field($key, $field);
                                             }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="clear"></div>
                            <?php endif; // needs_shipping_address

                            // Render Order Notes using our custom function
                            if (isset($fields['order']['order_comments'])) {
                                echo '<div class="woocommerce-additional-fields">'; // Optional: Keep WC wrapper
                                aakaari_render_checkout_field('order_comments', $fields['order']['order_comments']);
                                echo '</div>';
                            }
                            ?>
                             <?php // Render Save Info Checkbox (Manually for simplicity) ?>
                             <div class="aak-form-row aak-form-row-wide save-info"> <?php // Add class for styling ?>
                                 <?php
                                    $save_info_field = array(
                                        'type' => 'checkbox',
                                        'label' => __( 'Save this information for next time', 'aakaari' ),
                                        'required' => false,
                                        'id' => 'save-info' // Assuming 'save-info' is the correct ID if used by JS
                                    );
                                     aakaari_render_checkout_field('save_info', $save_info_field, checked( WC()->checkout()->get_value('save_info'), 1, false ) );
                                 ?>
                             </div>
                        </div>
                    </div>

                    <div id="step-2-content" class="hidden">
                         <div class="card">
                             <div class="card-header">
                                 <div class="card-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg></div>
                                 <div class="card-text">
                                     <h2><?php esc_html_e('Shipping Method', 'woocommerce'); ?></h2>
                                     <p><?php esc_html_e('Choose your delivery option', 'woocommerce'); ?></p>
                                 </div>
                             </div>
                             <div class="radio-group" id="aakaari-shipping-methods">
                                 <?php
                                 // This section relies on WC default output which JS formats
                                 if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) {
                                     do_action( 'woocommerce_review_order_before_shipping' );
                                     wc_cart_totals_shipping_html();
                                     do_action( 'woocommerce_review_order_after_shipping' );
                                 }
                                 ?>
                             </div>
                         </div>
                     </div>

                    <div id="step-3-content" class="hidden">
                         <div class="card">
                             <div class="card-header">
                                 <div class="card-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg></div>
                                 <div class="card-text">
                                     <h2><?php esc_html_e('Payment Method', 'woocommerce'); ?></h2>
                                     <p><?php esc_html_e('All transactions are secure and encrypted', 'woocommerce'); ?></p>
                                 </div>
                             </div>
                             <div id="aakaari-payment" class="payment-methods">
                                 <?php
                                  if ( WC()->cart->needs_payment() ) {
                                      $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
                                      wc_get_template( 'checkout/payment.php', array(
                                          'checkout'           => WC()->checkout(),
                                          'available_gateways' => $available_gateways,
                                          'order_button_text'  => apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'woocommerce' ) ),
                                      ) );
                                  }
                                 ?>
                             </div>
                             <div class="secure-badge">
                                 <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                 <p><?php esc_html_e('Your payment information is encrypted and secure. We never store your card details.', 'woocommerce'); ?></p>
                             </div>
                         </div>
                     </div>

                    <div class="button-group lg-hidden">
                         <button type="button" class="btn btn-outline" id="mobile-back-btn"><?php esc_html_e('Back', 'woocommerce'); ?></button>
                         <?php // This submit button is crucial for final step ?>
                         <button type="submit" class="btn btn-primary button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" id="mobile-next-btn" name="woocommerce_checkout_place_order" value="<?php esc_attr_e( 'Place order', 'woocommerce' ); ?>" data-value="<?php esc_attr_e( 'Place order', 'woocommerce' ); ?>"><?php esc_html_e('Continue', 'woocommerce'); ?></button>
                     </div>
                </div>

                <div class="summary-column">
                     <div class="card summary-card">
                         <h2><?php esc_html_e('Order Summary', 'woocommerce'); ?></h2>
                         <div class="summary-items">
                             <?php
                             do_action( 'woocommerce_checkout_order_review_start' ); // Allow plugins to add content
                             foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
                                 $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                                 if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key)) :
                             ?>
                                     <div class="summary-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
                                        <?php
                                        $thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image('woocommerce_thumbnail'), $cart_item, $cart_item_key );
                                        echo $thumbnail; // PHPCS: XSS ok.
                                        ?>
                                         <div class="summary-item-details">
                                             <p class="name"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ) . '&nbsp;'; ?></p>
                                             <?php echo apply_filters( 'woocommerce_checkout_cart_item_quantity', ' <p class="qty">' . sprintf( __('Qty: %s', 'woocommerce'), $cart_item['quantity'] ) . '</p>', $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                             <?php // echo wc_get_formatted_cart_item_data( $cart_item ); // uncomment if you need variation data ?>
                                             <p class="price"><?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
                                         </div>
                                     </div>
                             <?php
                                 endif;
                             endforeach;
                             do_action( 'woocommerce_checkout_order_review_list_end' ); // Allow plugins to add content
                              ?>
                         </div>
                         <div class="summary-totals">
                             <?php // Using standard WC hooks for totals ensures compatibility ?>
                             <div class="summary-row cart-subtotal">
                                 <span class="summary-label"><?php esc_html_e('Subtotal', 'woocommerce'); ?></span>
                                 <span class="summary-value"><?php wc_cart_totals_subtotal_html(); ?></span>
                             </div>

                             <?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
                                 <div class="summary-row cart-discount coupon-<?php echo esc_attr(sanitize_title($code)); ?>">
                                     <span class="summary-label"><?php wc_cart_totals_coupon_label($coupon); ?></span>
                                     <span class="summary-value"><?php wc_cart_totals_coupon_html($coupon); ?></span>
                                 </div>
                             <?php endforeach; ?>

                             <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
                                 <?php do_action('woocommerce_review_order_before_shipping'); ?>
                                 <div class="summary-row shipping">
                                     <span class="summary-label"><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></span>
                                     <span class="summary-value"><?php echo wp_kses_post( WC()->cart->get_cart_shipping_total() ); ?></span> <?php // Display calculated total ?>
                                 </div>
                                 <?php do_action('woocommerce_review_order_after_shipping'); ?>
                             <?php endif; ?>


                            <?php foreach (WC()->cart->get_fees() as $fee) : ?>
                                <div class="summary-row fee">
                                    <span class="summary-label"><?php echo esc_html($fee->name); ?></span>
                                    <span class="summary-value"><?php wc_cart_totals_fee_html($fee); ?></span>
                                </div>
                            <?php endforeach; ?>

                             <?php if (wc_tax_enabled() && !WC()->cart->display_prices_including_tax()) : ?>
                                <?php if ('itemized' === get_option('woocommerce_tax_total_display')) : ?>
                                     <?php foreach (WC()->cart->get_tax_totals() as $code => $tax) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
                                         <div class="summary-row tax-rate tax-rate-<?php echo esc_attr(sanitize_title($code)); ?>">
                                             <span class="summary-label"><?php echo esc_html($tax->label); ?></span>
                                             <span class="summary-value"><?php echo wp_kses_post($tax->formatted_amount); ?></span>
                                         </div>
                                     <?php endforeach; ?>
                                <?php else : ?>
                                     <div class="summary-row tax-total">
                                         <span class="summary-label"><?php echo esc_html(WC()->countries->tax_or_vat()); ?></span>
                                         <span class="summary-value"><?php wc_cart_totals_taxes_total_html(); ?></span>
                                     </div>
                                <?php endif; ?>
                             <?php endif; ?>

                             <?php do_action('woocommerce_review_order_before_order_total'); ?>
                             <div class="summary-row total order-total">
                                 <span class="summary-label"><?php esc_html_e('Total', 'woocommerce'); ?></span>
                                 <span class="summary-value"><?php wc_cart_totals_order_total_html(); ?></span>
                             </div>
                             <?php do_action('woocommerce_review_order_after_order_total'); ?>
                         </div>

                         <?php // Keep original WC review order actions for compatibility (e.g., Terms checkbox) ?>
                         <div class="wc-checkout-review-order-actions">
                            <?php do_action( 'woocommerce_review_order_before_submit' ); ?>

                            <?php // The Place Order button is handled by JS/Step logic now, but keep WC hook ?>
                            <noscript>
                                <?php esc_html_e( 'Since your browser does not support JavaScript, or it is disabled, please ensure you click the Place Order button when ready.', 'woocommerce' ); ?>
                                <br/><button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="<?php esc_attr_e( 'Place order', 'woocommerce' ); ?>" data-value="<?php esc_attr_e( 'Place order', 'woocommerce' ); ?>"><?php esc_html_e( 'Place order', 'woocommerce' ); ?></button>
                            </noscript>

                             <?php do_action( 'woocommerce_review_order_after_submit' ); ?>
                         </div>


                         <div class="button-group hidden-lg">
                             <?php // Desktop 'Place Order' button - hidden until step 3 ?>
                             <button type="submit" class="btn btn-primary button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="woocommerce_checkout_place_order" id="desktop-next-btn" value="<?php esc_attr_e( 'Place order', 'woocommerce' ); ?>" data-value="<?php esc_attr_e( 'Place order', 'woocommerce' ); ?>">
                                 <span id="desktop-btn-text"><?php esc_html_e('Continue', 'woocommerce'); ?></span>
                             </button>
                             <button type="button" class="btn btn-outline" id="desktop-back-btn"><?php esc_html_e('Back', 'woocommerce'); ?></button>
                         </div>
                         <div class="terms-policy">
                             <?php // Use standard WC terms output for compatibility ?>
                            <?php wc_checkout_privacy_policy_text(); ?>
                         </div>
                     </div>
                 </div>
            </div>
        </form>
    </div>
</div>

<?php do_action('woocommerce_after_checkout_form', $checkout); ?>