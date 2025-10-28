<?php

/**

 * Multi-step Checkout (Aakaari)

 * Path: yourtheme/woocommerce/checkout/form-checkout.php

 */

defined('ABSPATH') || exit;

$checkout = WC()->checkout();

if ( ! $checkout ) return;

do_action('woocommerce_before_checkout_form', $checkout);

// Require login if needed

if ( ! is_user_logged_in() && $checkout->is_registration_required() ) {

    wc_print_notice( esc_html__('You must be logged in to checkout.', 'woocommerce') );

    return;

}

?>

<div id="checkout-container" class="aak-checkout">

  <!-- Progress -->

  <div class="progress-bar-container">

    <div class="progress-bar-steps">

      <div class="progress-step active" id="progress-step-1">

        <div class="progress-step-icon">1</div>

        <div class="progress-step-text"><strong><?php esc_html_e('Information','woocommerce'); ?></strong></div>

      </div>

      <div class="progress-step" id="progress-step-2">

        <div class="progress-step-icon">2</div>

        <div class="progress-step-text"><strong><?php esc_html_e('Shipping','woocommerce'); ?></strong></div>

      </div>

      <div class="progress-step" id="progress-step-3">

        <div class="progress-step-icon">3</div>

        <div class="progress-step-text"><strong><?php esc_html_e('Payment','woocommerce'); ?></strong></div>

      </div>

    </div>

  </div>

  <div class="page-container">

    <form name="checkout" method="post" class="checkout woocommerce-checkout" id="checkout-form"

          action="<?php echo esc_url( wc_get_checkout_url() ); ?>" novalidate>

      <div class="checkout-grid">

        <!-- LEFT -->

        <div class="form-column">

          <!-- Step 1 -->

          <div id="step-1-content">

            <div class="card">

              <div class="card-header">

                <h2><?php esc_html_e('Contact Information','woocommerce'); ?></h2>

                <p><?php esc_html_e('How can we reach you?','woocommerce'); ?></p>

              </div>

              <?php do_action('woocommerce_checkout_billing'); ?>

            </div>

            <div class="card">

              <div class="card-header">

                <h2><?php esc_html_e('Shipping Address','woocommerce'); ?></h2>

                <p><?php esc_html_e('Where should we deliver?','woocommerce'); ?></p>

              </div>

              <?php do_action('woocommerce_checkout_shipping'); ?>

            </div>

          </div>

          <!-- Step 2 -->

          <div id="step-2-content" class="hidden">

            <div class="card">

              <div class="card-header"><h2><?php esc_html_e('Shipping Method','woocommerce'); ?></h2></div>

              <div class="radio-group" id="aakaari-shipping-methods">

                <?php

                if ( WC()->cart->needs_shipping() ) {

                  if ( WC()->cart->show_shipping() ) {

                    wc_cart_totals_shipping_html();

                  } else {

                    woocommerce_shipping_calculator();

                  }

                }

                ?>

              </div>

            </div>

          </div>

          <!-- Step 3 -->

          <div id="step-3-content" class="hidden">

            <div class="card">

              <div class="card-header">

                <h2><?php esc_html_e('Payment Method','woocommerce'); ?></h2>

                <p><?php esc_html_e('All transactions are secure and encrypted','woocommerce'); ?></p>

              </div>

              <div id="aakaari-payment">

                <?php do_action('woocommerce_checkout_payment'); ?>

              </div>

            </div>

          </div>

          <!-- Mobile nav -->

          <div class="lg-hidden" style="display:flex;gap:.75rem;margin-top:1rem;">

            <button type="button" class="btn btn-outline btn-full" id="mobile-back-btn"><?php esc_html_e('Back','woocommerce'); ?></button>

            <button type="submit" class="btn btn-primary btn-full" id="mobile-next-btn"><?php esc_html_e('Continue','woocommerce'); ?></button>

          </div>

        </div>

        <!-- RIGHT: Summary -->

        <div class="summary-column">

          <div class="card summary-card">

            <h2><?php esc_html_e('Order Summary','woocommerce'); ?></h2>

            <?php foreach ( WC()->cart->get_cart() as $cart_item ) :

              $_product = $cart_item['data'];

              if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 ) : ?>

                <div class="summary-item">

                  <?php echo $_product->get_image('woocommerce_thumbnail'); ?>

                  <div class="summary-item-details">

                    <p class="name"><?php echo esc_html( $_product->get_name() ); ?></p>

                    <p class="qty"><?php printf( esc_html__('Qty: %d','woocommerce'), $cart_item['quantity'] ); ?></p>

                    <p class="price"><?php echo wc_price( $_product->get_price() * $cart_item['quantity'] ); ?></p>

                  </div>

                </div>

            <?php endif; endforeach; ?>

            <hr class="separator">

            <div id="order_review" class="woocommerce-checkout-review-order">

              <?php do_action('woocommerce_checkout_order_review'); ?>

            </div>

            <!-- Desktop nav -->

            <div class="hidden-lg" style="display:flex;flex-direction:column;gap:.75rem;margin-top:1.5rem;">

              <button type="submit" class="btn btn-primary btn-full" id="desktop-next-btn"><?php esc_html_e('Continue','woocommerce'); ?></button>

              <button type="button" class="btn btn-outline btn-full" id="desktop-back-btn"><?php esc_html_e('Back','woocommerce'); ?></button>

            </div>

          </div>

        </div>

      </div>

    </form>

  </div>

</div>

<?php do_action('woocommerce_after_checkout_form', $checkout); ?>