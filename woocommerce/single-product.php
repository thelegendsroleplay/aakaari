<?php
/**
 * The Template for displaying all single products
 *
 * Overrides the default WooCommerce single-product.php template.
 * Conditionally displays the customizer UI based on ACF field 'is_customizable'.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

get_header( 'shop' ); // Use the shop header

// Get current product ID and object
$product_id = get_the_ID();
$product    = wc_get_product( $product_id );

// Check if this product is customizable (Requires ACF plugin)
$is_customizable = function_exists('get_field') ? get_field('is_customizable', $product_id) : false;

// --- Load CUSTOMIZER UI if customizable ---
if ( $is_customizable && $product ) :

    // Fetch the data needed for the JavaScript customizer interface
    // This function should exist in 'inc/product-customizer-functions.php'
    $customizer_data = function_exists('aakaari_get_customizer_data_for_js')
        ? aakaari_get_customizer_data_for_js($product_id)
        : array('error' => 'Customizer data function not found.');

    // Check for errors fetching data (e.g., ACF not active)
    if (isset($customizer_data['error'])) :
        // Display error message instead of customizer
        echo '<div class="woocommerce-error container mx-auto px-4 py-8">Error loading customizer data: ' . esc_html($customizer_data['error']) . '. Please ensure ACF plugin is active and required fields are configured correctly.</div>';

    else:
        // --- START CUSTOMIZER HTML INTERFACE (Copied from index.html #customizer-page) ---
        ?>
        <div id="customizer-page" class="product-customizer" data-product-id="<?php echo esc_attr($product_id); ?>"> <?php // Added data-product-id ?>
            <div class="min-h-screen bg-background"> <?php // Match outer div ?>
                <div class="border-b border-gray-200 bg-white">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <h1 id="customizer-product-name" class="text-2xl font-semibold text-gray-900"><?php echo esc_html( $customizer_data['product_name'] ); ?></h1>
                                <p id="customizer-product-desc" class="text-gray-500 mt-1"><?php echo wp_kses_post( $customizer_data['description'] ); ?></p>
                            </div>
                            <?php // Use WooCommerce shop URL function ?>
                            <a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="inline-flex items-center justify-center rounded-md text-sm font-medium h-10 px-4 py-2 border border-gray-300">
                                Back to Shop
                            </a>
                        </div>
                    </div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                        <div class="space-y-4">
                            <div class="rounded-lg border bg-white text-gray-900 shadow-sm">
                                <div class="flex flex-col space-y-1.5 p-6">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <h3 class="text-lg font-semibold">Product Preview</h3>
                                            <p id="customizer-side-info" class="text-sm text-gray-500"></p> <?php // Populated by JS ?>
                                        </div>
                                        <div id="customizer-total-designs" class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-600">
                                            <i data-lucide="layers" class="mr-1 h-3 w-3"></i>
                                            0 total <?php // Updated by JS ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6 pt-0">
                                    <div class="relative">
                                        <div class="absolute top-2 right-2 flex gap-2 z-10">
                                            <button type="button" id="zoom-out-btn" class="inline-flex items-center justify-center rounded-md text-sm font-medium h-9 w-9 px-0 py-0 bg-gray-100 text-gray-700 hover:bg-gray-200 disabled:opacity-50" aria-label="Zoom Out">
                                                <i data-lucide="zoom-out" class="h-4 w-4"></i>
                                            </button>
                                            <button type="button" id="zoom-in-btn" class="inline-flex items-center justify-center rounded-md text-sm font-medium h-9 w-9 px-0 py-0 bg-gray-100 text-gray-700 hover:bg-gray-200 disabled:opacity-50" aria-label="Zoom In">
                                                <i data-lucide="zoom-in" class="h-4 w-4"></i>
                                            </button>
                                            <button type="button" id="zoom-reset-btn" class="inline-flex items-center justify-center rounded-md text-sm font-medium h-9 w-9 px-0 py-0 bg-gray-100 text-gray-700 hover:bg-gray-200" aria-label="Reset Zoom">
                                                <i data-lucide="maximize-2" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                        <div id="canvas-container" class="overflow-auto border border-gray-300 rounded-lg bg-gray-100 flex items-center justify-center" style="max-height: 600px;">
                                            <canvas
                                                id="interactive-canvas"
                                                width="500" <?php // Base canvas dimensions ?>
                                                height="500"
                                                class="cursor-move shadow-sm bg-white" <?php // Added bg-white for no-image case ?>
                                                style="transform: scale(1); transform-origin: center;"
                                                aria-label="Product customization area"
                                            ></canvas>
                                        </div>
                                        <div class="mt-2 text-sm text-gray-500 text-center">
                                            Zoom: <span id="zoom-percentage">100</span>% • Drag designs within the blue print area
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-lg border bg-white text-gray-900 shadow-sm">
                                <div class="flex flex-col space-y-1.5 p-6">
                                    <h3 class="text-lg font-semibold">Select Side to Customize</h3>
                                </div>
                                <div class="p-6 pt-0">
                                    <div id="side-selector-container" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        <?php // Side buttons populated by JS using wpCustomizerData ?>
                                    </div>
                                </div>
                            </div>

                            <div id="color-selector-card" class="rounded-lg border bg-white text-gray-900 shadow-sm hidden">
                                <div class="flex flex-col space-y-1.5 p-6">
                                    <h3 class="text-lg font-semibold">Select Color</h3>
                                </div>
                                <div class="p-6 pt-0">
                                    <div id="color-selector-container" class="flex flex-wrap gap-3">
                                        <?php // Color swatches populated by JS using wpCustomizerData ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <form class="cart customizer-cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>

                                <div class="mb-4">
                                    <div class="flex border-b border-gray-200">
                                        <button type="button" data-tab="design" class="tab-trigger flex-1 inline-flex items-center justify-center whitespace-nowrap rounded-t-sm px-3 py-1.5 text-sm font-medium border-b-2 border-primary text-primary" style="border-color: #3B82F6;">
                                            Design
                                        </button>
                                        <button type="button" data-tab="print" class="tab-trigger flex-1 inline-flex items-center justify-center whitespace-nowrap rounded-t-sm px-3 py-1.5 text-sm font-medium text-gray-500 hover:text-gray-700">
                                            Print Type
                                        </button>
                                    </div>
                                    <div class="mt-4">
                                        <div id="design-tab-content" class="tab-content space-y-4">
                                            <div class="rounded-lg border bg-white text-gray-900 shadow-sm">
                                                <div class="flex flex-col space-y-1.5 p-6">
                                                    <h3 class="text-lg font-semibold">Add Your Design</h3>
                                                    <p class="text-sm text-gray-500">
                                                        Upload images or add text
                                                    </p>
                                                </div>
                                                <div class="p-6 pt-0 space-y-4">
                                                    <div class="space-y-3">
                                                        <label class="text-sm font-medium">Upload Image</label>
                                                        <button type="button" id="add-image-btn" class="inline-flex items-center justify-center rounded-md text-sm font-medium h-10 px-4 py-2 border border-gray-300 w-full">
                                                            <i data-lucide="upload" class="mr-2 h-4 w-4"></i> Upload Design
                                                        </button>
                                                        <input type="file" id="file-upload-input" class="hidden" accept="image/png, image/jpeg, image/svg+xml, image/webp" /> <?php // Added webp ?>
                                                        <p class="text-xs text-gray-500">
                                                            Supports PNG, JPG, SVG, WEBP • Max 10MB
                                                        </p>
                                                    </div>
                                                    <div class="space-y-3">
                                                        <label class="text-sm font-medium" for="add-text-input">Add Text</label>
                                                        <div class="flex gap-2">
                                                            <input id="add-text-input" placeholder="Enter your text" class="flex-1 w-full h-10 px-3 py-2 border border-gray-300 rounded-md text-sm"/>
                                                            <button type="button" id="add-text-btn" class="inline-flex items-center justify-center rounded-md text-sm font-medium h-10 w-10 px-0 py-0 bg-primary text-white hover:bg-primary/90 disabled:opacity-50" style="background-color: #3B82F6;" aria-label="Add Text" disabled> <?php // Start disabled ?>
                                                                <i data-lucide="type" class="h-4 w-4"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div id="design-list-container" class="space-y-2 pt-4 border-t"> <?php // JS Populates ?></div>
                                                    <div id="design-list-placeholder" class="rounded-md border border-gray-200 p-4 hidden"> <?php // JS Populates ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="print-tab-content" class="tab-content space-y-4 hidden">
                                            <div class="rounded-lg border bg-white text-gray-900 shadow-sm">
                                                <div class="flex flex-col space-y-1.5 p-6">
                                                    <h3 class="text-lg font-semibold">Select Print Method</h3>
                                                    <p class="text-sm text-gray-500">
                                                        Choose how your design will be printed
                                                    </p>
                                                </div>
                                                <div class="p-6 pt-0">
                                                    <div id="print-type-container" class="space-y-3"> <?php // JS Populates ?></div>
                                                </div>
                                            </div>
                                            <div id="pricing-calculation-card" class="rounded-lg border border-blue-300/50 bg-white text-gray-900 shadow-sm hidden">
                                                <div class="flex flex-col space-y-1.5 p-6">
                                                    <h3 class="text-sm font-semibold">Pricing Calculation</h3>
                                                </div>
                                                <div id="pricing-calculation-content" class="p-6 pt-0 space-y-2 text-sm"> <?php // JS Populates ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-lg border border-blue-300/20 bg-blue-50/50 text-gray-900 shadow-sm sticky top-4">
                                    <div class="p-6">
                                        <div class="space-y-3">
                                            <div class="flex justify-between items-center">
                                                <span class="text-gray-500">Product Base Price:</span>
                                                <div class="flex items-center gap-2">
                                                    <span id="product-base-price-strikethrough" class="line-through text-gray-500 text-sm hidden"></span>
                                                    <span id="product-base-price" class="font-medium">₹0.00</span> <?php // JS Updates, using ₹ ?>
                                                </div>
                                            </div>
                                            <div id="print-cost-summary" class="flex justify-between items-center hidden">
                                                <span id="print-cost-label" class="text-gray-500">Print Cost:</span>
                                                <span id="print-cost-value" class="font-medium">+₹0.00</span> <?php // JS Updates ?>
                                            </div>
                                            <div class="border-t border-blue-300/20 pt-3 flex justify-between items-center">
                                                <span class="text-lg font-semibold">Total Price:</span>
                                                <span id="total-price" class="text-lg font-semibold text-blue-600">₹0.00</span> <?php // JS Updates ?>
                                            </div>

                                            <?php
                                            // Ensure quantity input is displayed
                                            if ( $product->is_sold_individually() ) {
                                                woocommerce_quantity_input( array(
                                                    'min_value' => 1,
                                                    'max_value' => 1,
                                                ), $product, false ); // Pass false to return HTML
                                            } else {
                                                 woocommerce_quantity_input( array(
                                                     'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
                                                     'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
                                                     'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(),
                                                  ), $product, true ); // Pass true to echo
                                            }
                                             ?>

                                            <input type="hidden" name="custom_designs_data" id="custom_designs_data" value="" />
                                            <input type="hidden" name="custom_print_type_id" id="custom_print_type_id" value="" />
                                            <input type="hidden" name="custom_product_color" id="custom_product_color" value="" />

                                            <button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>"
                                                    id="add-to-cart-btn" <?php // Keep ID for JS ?>
                                                    class="single_add_to_cart_button button alt w-full inline-flex items-center justify-center rounded-md text-sm font-medium h-11 px-4 py-2 bg-primary text-white hover:bg-primary/90 disabled:opacity-50"
                                                    style="background-color: #3B82F6;" disabled> <?php // Match index.html styles ?>
                                                <i data-lucide="shopping-cart" class="mr-2 h-5 w-5"></i>
                                                Add to Cart
                                            </button>
                                            <?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>


                                            <p id="add-to-cart-placeholder" class="text-xs text-center text-gray-500">
                                                Add at least one design to continue
                                            </p>
                                        </div>
                                    </div>
                                </div>

                            </form> </div> </div>
                </div>
            </div>
        </div>
        <?php
        // --- END CUSTOMIZER HTML INTERFACE ---
    endif; // End check for customizer data errors

// --- Load DEFAULT WooCommerce content if NOT customizable ---
else :

	/**
	 * Hook: woocommerce_before_main_content.
	 */
	do_action( 'woocommerce_before_main_content' );

	while ( have_posts() ) :
		the_post();
        // This function loads the default WooCommerce product page layout
        // (e.g., product image gallery, summary, tabs, related products)
		wc_get_template_part( 'content', 'single-product' );
	endwhile; // end of the loop.

	/**
	 * Hook: woocommerce_after_main_content.
	 */
	do_action( 'woocommerce_after_main_content' );

endif; // --- End $is_customizable check ---

// Ensure Lucide icons render
echo '<script> if(window.lucide && typeof window.lucide.createIcons === \'function\') { lucide.createIcons(); } </script>';

get_footer( 'shop' ); // Use the shop footer
?>