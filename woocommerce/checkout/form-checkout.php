<?php
/**
 * Multi-step Checkout (Theme override)
 * Place in: your-theme/woocommerce/checkout/form-checkout.php
 */
defined('ABSPATH') || exit;

do_action('woocommerce_before_checkout_form', WC()->checkout());

// If registration is disabled and not logged in, show login first
if (!$checkout = WC()->checkout()) return;
if (!is_user_logged_in() && $checkout->is_registration_required()) {
  echo '<p>' . esc_html__('You must be logged in to checkout.', 'woocommerce') . '</p>';
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
    <button class="back-button hidden-lg" id="back-to-cart-top" type="button">
      <i data-lucide="arrow-left" width="16" height="16"></i> <?php esc_html_e('Back to cart','woocommerce'); ?>
    </button>

    <form name="checkout" method="post" class="checkout woocommerce-checkout" id="checkout-form" action="<?php echo esc_url(wc_get_checkout_url()); ?>" novalidate>
      <div class="checkout-grid">
        <!-- LEFT: Steps -->
        <div class="form-column">
          <button class="back-button lg-hidden" id="back-to-cart" type="button">
            <i data-lucide="arrow-left" width="16" height="16"></i> <?php esc_html_e('Back','woocommerce'); ?>
          </button>

          <!-- STEP 1: Contact + Address -->
          <div id="step-1-content" class="">
            <div class="card">
              <div class="card-header">
                <div class="icon-bg"><i data-lucide="mail"></i></div>
                <div>
                  <h2><?php esc_html_e('Contact Information','woocommerce'); ?></h2>
                  <p><?php esc_html_e('How can we reach you?','woocommerce'); ?></p>
                </div>
              </div>
              <?php
                // Billing contains email + phone + address fields
                do_action('woocommerce_checkout_billing');
              ?>
            </div>

            <div class="card">
              <div class="card-header">
                <div class="icon-bg"><i data-lucide="map-pin"></i></div>
                <div>
                  <h2><?php esc_html_e('Shipping Address','woocommerce'); ?></h2>
                  <p><?php esc_html_e('Where should we deliver?','woocommerce'); ?></p>
                </div>
              </div>
              <?php
                // Optional: if you want separate shipping address toggle/fields
                do_action('woocommerce_before_checkout_shipping_form', $checkout);
                do_action('woocommerce_checkout_shipping');
                do_action('woocommerce_after_checkout_shipping_form', $checkout);
              ?>
            </div>
          </div>

          <!-- STEP 2: Shipping methods -->
          <div id="step-2-content" class="hidden">
            <div class="card">
              <div class="card-header">
                <div class="icon-bg"><i data-lucide="truck"></i></div>
                <div>
                  <h2><?php esc_html_e('Shipping Method','woocommerce'); ?></h2>
                  <p><?php esc_html_e('Choose your delivery option','woocommerce'); ?></p>
                </div>
              </div>

              <?php
              /**
               * Shipping methods live inside the order review totals normally.
               * We render the package rates list here as Woo would, using its hooks.
               */
              if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
                <div class="radio-group" id="aakaari-shipping-methods">
                  <?php wc_cart_totals_shipping_html(); ?>
                </div>
              <?php elseif (WC()->cart->needs_shipping()) : ?>
                <?php woocommerce_shipping_calculator(); ?>
              <?php endif; ?>
            </div>
          </div>

          <!-- STEP 3: Payment -->
          <div id="step-3-content" class="hidden">
            <div class="card">
              <div class="card-header">
                <div class="icon-bg"><i data-lucide="lock"></i></div>
                <div>
                  <h2><?php esc_html_e('Payment Method','woocommerce'); ?></h2>
                  <p><?php esc_html_e('All transactions are secure and encrypted','woocommerce'); ?></p>
                </div>
              </div>

              <?php
              /**
               * Payment + Place order button come from the order review template.
               * We just output the payment section hook here so step 3 contains it.
               */
              do_action('woocommerce_checkout_before_order_review_heading');
              ?>

              <div id="aakaari-payment">
                <?php do_action('woocommerce_checkout_payment'); ?>
              </div>

              <div class="secure-payment-badge">
                <i data-lucide="shield" class="icon" width="20" height="20"></i>
                <div>
                  <h3><?php esc_html_e('Secure Payment','woocommerce'); ?></h3>
                  <p><?php esc_html_e("Your payment information is encrypted and secure. We never store your card details.",'woocommerce'); ?></p>
                </div>
              </div>
            </div>
          </div>

          <!-- Mobile nav -->
          <div class="lg-hidden" style="display:flex;gap:.75rem;margin-top:1rem;">
            <button type="button" class="btn btn-outline btn-full" id="mobile-back-btn"><?php esc_html_e('Back','woocommerce'); ?></button>
            <button type="submit" class="btn btn-primary btn-full" id="mobile-next-btn"><?php esc_html_e('Continue','woocommerce'); ?></button>
          </div>
        </div>
        
        <!-- RIGHT: Order Summary -->
        <div class="summary-column">
          <div class="card sticky-card summary-card">
            <h2><?php esc_html_e('Order Summary','woocommerce'); ?></h2>

            <?php
            // Items (simple compact list)
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
              $_product = $cart_item['data'];
              if ($_product && $_product->exists() && $cart_item['quantity'] > 0) :
                $thumb = $_product->get_image('woocommerce_thumbnail');
                ?>
                <div class="summary-item">
                  <?php echo $thumb; ?>
                  <div class="summary-item-details">
                    <p class="name"><?php echo esc_html($_product->get_name()); ?></p>
                    <p class="qty"><?php echo sprintf(esc_html__('Qty: %d','woocommerce'), $cart_item['quantity']); ?></p>
                    <p class="price"><?php echo wc_price($_product->get_price() * $cart_item['quantity']); ?></p>
                  </div>
                </div>
              <?php endif;
            endforeach; ?>

            <hr class="separator">

            <!-- Put Woo totals + place order button here so it matches your mock -->
            <div id="order_review" class="woocommerce-checkout-review-order">
              <?php do_action('woocommerce_checkout_order_review'); ?>
            </div>

            <!-- Desktop nav -->
            <div class="hidden-lg" style="display:flex;flex-direction:column;gap:.75rem;margin-top:1.5rem;">
              <button type="submit" class="btn btn-primary btn-full" id="desktop-next-btn"><?php esc_html_e('Continue','woocommerce'); ?></button>
              <button type="button" class="btn btn-outline btn-full" id="desktop-back-btn"><?php esc_html_e('Back','woocommerce'); ?></button>
            </div>

            <p class="summary-footer-text">
              <?php esc_html_e('By placing this order, you agree to our Terms and Privacy Policy', 'woocommerce'); ?>
            </p>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<?php do_action('woocommerce_after_checkout_form', $checkout); ?>