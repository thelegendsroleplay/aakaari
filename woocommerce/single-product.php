<?php
/**
 * single-product.php - Aakaari custom single product template (fixed)
 * Place in yourtheme/woocommerce/single-product.php
 */

defined( 'ABSPATH' ) || exit;

// If WooCommerce isn't active, bail gracefully
if ( ! function_exists( 'is_woocommerce' ) ) {
    get_header();
    echo '<div class="aakaari-container"><p>WooCommerce is not active.</p></div>';
    get_footer();
    return;
}

global $post, $product;

// Ensure $product is a WC_Product object. If not, try to get it.
if ( empty( $product ) || ! is_object( $product ) || ! method_exists( $product, 'get_name' ) ) {
    $product = wc_get_product( isset( $post ) ? $post->ID : get_the_ID() );
}

// If still no product, show a friendly message and avoid fatal errors.
if ( ! $product || ! is_object( $product ) ) {
    get_header();
    ?>
    <div class="aakaari-container">
      <div class="card" style="padding:20px;">
        <h2>Product not found</h2>
        <p>Sorry — we couldn't locate this product. Please check that you're viewing a valid product page.</p>
      </div>
    </div>
    <?php
    get_footer();
    return;
}

get_header();
?>

<div class="aakaari-container">

  <!-- Hero (optional) -->
  <div class="aakaari-hero">
    <h1>Custom Print Studio</h1>
    <p>Create unique, personalized products with our easy-to-use design tools</p>
  </div>

  <div id="customizer-page" class="">
    <div class="card" style="margin-bottom:12px; padding:16px;">
      <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
          <h2 id="customizer-product-name"><?php echo esc_html( $product->get_name() ); ?></h2>
          <p id="customizer-product-desc" style="color:#6B7280; margin-top:6px;"><?php echo wp_strip_all_tags( $product->get_description() ); ?></p>
        </div>
        <button id="back-to-shop-btn" class="side-btn">Back to Shop</button>
      </div>
    </div>

    <div class="customizer-wrap">
      <div class="customizer-left">
        <!-- Product preview card -->
        <div class="card">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <div>
              <h3 style="margin:0;">Product Preview</h3>
              <p id="customizer-side-info" style="color:#6B7280; margin:0; font-size:13px;">&nbsp;</p>
            </div>
            <div id="customizer-total-designs" style="font-size:12px; padding:6px 8px; border-radius:999px; background:#F3F4F6;">0 total</div>
          </div>

          <div style="position:relative;">
            <div class="canvas-controls" style="position:absolute; right:8px; top:8px; z-index:10;">
              <button id="zoom-out-btn" class="side-btn" title="Zoom out">-</button>
              <button id="zoom-in-btn" class="side-btn" title="Zoom in">+</button>
              <button id="zoom-reset-btn" class="side-btn" title="Reset">1x</button>
            </div>

            <div id="canvas-container">
              <canvas id="interactive-canvas" width="500" height="500"></canvas>
            </div>

            <div style="margin-top:8px; text-align:center; color:#6B7280; font-size:13px;">
              Zoom: <span id="zoom-percentage">100</span>% • Drag designs within the blue print area
            </div>
          </div>
        </div>

        <!-- Side selector -->
        <div id="side-selector-card" class="card">
          <h4 style="margin:0 0 8px 0;">Select Side to Customize</h4>
          <div id="side-selector-container" style="margin-top:8px;"></div>
        </div>

        <!-- Color selector (hidden if single color) -->
        <div id="color-selector-card" class="card hidden">
          <h4 style="margin:0 0 8px 0;">Select Color</h4>
          <div id="color-selector-container"></div>
        </div>

        <!-- Fabric selector (hidden if no fabrics) -->
        <div id="fabric-selector-card" class="card hidden">
          <h4 style="margin:0 0 8px 0;">Select Fabric</h4>
          <div id="fabric-selector-container"></div>
        </div>
      </div>

      <div class="customizer-right">
        <!-- Tabs -->
        <div class="card">
          <div class="tab-triggers" style="margin-bottom:10px;">
            <button data-tab="design" class="tab-trigger active">Design</button>
            <button data-tab="print" class="tab-trigger">Print Type</button>
          </div>

          <div id="design-tab-content" class="tab-content">
            <h4 style="margin-top:0;">Add Your Design</h4>
            <p style="color:#6B7280; margin-top:6px;">Upload images or add text to customize your product</p>
            <div style="margin-top:12px;">
              <label>Upload Image</label>
              <button id="add-image-btn" class="side-btn" style="display:block; width:100%; margin-top:8px;">Upload Design</button>
              <input type="file" id="file-upload-input" class="hidden" accept="image/png, image/jpeg, image/svg+xml" />
              <p style="font-size:12px; color:#6B7280; margin-top:6px;">Supports PNG, JPG, SVG • Max 10MB</p>
            </div>

            <div style="margin-top:16px;">
              <label>Add Text</label>
              <div style="display:flex; gap:8px; margin-top:8px;">
                <input id="add-text-input" placeholder="Enter your text" style="flex:1; padding:8px; border:1px solid #E5E7EB; border-radius:6px;" />
                <button id="add-text-btn" class="side-btn" title="Add text" disabled>OK</button>
              </div>
            </div>

            <div id="design-list-container" style="margin-top:12px;"></div>
            <div id="design-list-placeholder" class="card hidden" style="margin-top:8px;">
              <p style="margin:0; color:#6B7280;">Add images or text to start customizing. Drag and resize designs within the print area.</p>
            </div>
          </div>

          <div id="print-tab-content" class="tab-content hidden">
            <h4 style="margin-top:0;">Select Print Method</h4>
            <p style="color:#6B7280;">Choose how your design will be printed</p>
            <div id="print-type-container" style="margin-top:12px;"></div>
            <div id="pricing-calculation-card" class="hidden card" style="margin-top:12px;">
              <h5 style="margin:0 0 6px 0;">Pricing Calculation</h5>
              <div id="pricing-calculation-content"></div>
            </div>
          </div>
        </div>

        <!-- Price summary -->
        <div class="card">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <div style="color:#6B7280;">Product Base Price:</div>
            <div>
              <span id="product-base-price-strikethrough" class="hidden" style="text-decoration:line-through; color:#6B7280; margin-right:6px;"></span>
              <span id="product-base-price">$0.00</span>
            </div>
          </div>

          <div id="print-cost-summary" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;" class="hidden">
            <span id="print-cost-label" style="color:#6B7280;">Print Cost (0 designs):</span>
            <span id="print-cost-value">+$0.00</span>
          </div>

          <div style="border-top:1px solid #E5E7EB; padding-top:8px; display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <div style="font-weight:700;">Total Price:</div>
            <div id="total-price" style="color:var(--primary); font-weight:700;">$0.00</div>
          </div>

          <button id="add-to-cart-btn" disabled>Add to Cart</button>
          <p id="add-to-cart-placeholder" style="font-size:12px; color:#6B7280; text-align:center; margin-top:8px;">Add at least one design to continue</p>
        </div>
      </div>
    </div>
  </div>

</div>

<?php get_footer(); ?>
