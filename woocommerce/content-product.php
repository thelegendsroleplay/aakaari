<?php
/**
 * The template for displaying product content within loops
 * Structure based on the standalone index.html product card.
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Ensure visibility.
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

// Get necessary product data
$product_id = $product->get_id();
$product_link = get_permalink($product_id);
$thumbnail_id = $product->get_image_id();
$image_url = wp_get_attachment_image_url($thumbnail_id, 'woocommerce_thumbnail'); // Or use a custom size
$product_name = $product->get_name();
$description = $product->get_short_description() ?: wp_trim_words($product->get_description(), 15, '...');
$is_on_sale = $product->is_on_sale();
$price_html = $product->get_price_html(); // Gets formatted price including sale price
$base_price_raw = $product->get_regular_price();
$sale_price_raw = $product->get_sale_price();

// Get category (get primary or first category)
$categories = wc_get_product_terms($product_id, 'product_cat', array('orderby' => 'parent', 'order' => 'DESC'));
$category_name = !empty($categories) ? $categories[0]->name : 'Uncategorized';

// Get sides count (using ACF field 'product_sides')
$sides_count = 0;
if (function_exists('get_field')) {
    $sides = get_field('product_sides', $product_id);
    if (is_array($sides)) {
        $sides_count = count($sides);
    }
}

// Check if customizable (using ACF field 'is_customizable')
$is_customizable = function_exists('get_field') ? get_field('is_customizable', $product_id) : false;
$button_text = $is_customizable ? __('Customize Now', 'aakaari') : __('View Product', 'aakaari');

// Add WooCommerce product classes + Tailwind classes from index.html card structure
?>
<li <?php wc_product_class( 'overflow-hidden hover:shadow-lg transition-shadow rounded-lg border bg-white text-gray-900 shadow-sm', $product ); ?>>
    <div class="aspect-square bg-muted flex items-center justify-center relative bg-gray-100">
        <a href="<?php echo esc_url( $product_link ); ?>">
            <?php if ( $image_url ) : ?>
                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $product_name ); ?>" class="w-full h-full object-cover" loading="lazy">
            <?php else : ?>
                <?php // Fallback placeholder ?>
                <i data-lucide="package" class="h-16 w-16 text-gray-400"></i>
                <?php // echo wc_placeholder_img('woocommerce_thumbnail'); ?>
            <?php endif; ?>
        </a>
        <?php if ( $is_on_sale ) : ?>
            <div class="absolute top-2 right-2 inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-accent text-accent-foreground bg-yellow-100 text-yellow-800"> <?php // Match index.html styling ?>
                Sale
            </div>
        <?php endif; ?>
    </div>
    <div class="p-6"> <?php // Card Header in index.html structure ?>
        <div class="flex justify-between items-start gap-2">
            <h3 class="text-base font-semibold line-clamp-2"> <?php // Card Title styling ?>
                <a href="<?php echo esc_url( $product_link ); ?>"><?php echo esc_html( $product_name ); ?></a>
            </h3>
        </div>
        <?php if ( $description ) : ?>
            <p class="text-sm text-gray-500 line-clamp-2 mt-1"> <?php // Card Description styling ?>
                <?php echo wp_kses_post( $description ); ?>
            </p>
        <?php endif; ?>
    </div>
    <div class="p-6 pt-0"> <?php // Card Content in index.html structure ?>
         <div class="flex items-center flex-wrap gap-2 mb-3"> <?php // Badges for category/sides ?>
             <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-gray-100 border-gray-200"> <?php // Badge styling ?>
                 <?php echo esc_html($category_name); ?>
             </span>
             <?php if ($sides_count > 0) : ?>
                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-gray-100 border-gray-200"> <?php // Badge styling ?>
                    <?php echo esc_html($sides_count); ?> sides
                </span>
             <?php endif; ?>
        </div>
        <div class="flex items-baseline gap-2"> <?php // Price display ?>
            <?php // Use WooCommerce's formatted price - it handles sale prices correctly ?>
            <span class="text-xl font-semibold text-gray-900"><?php echo $price_html; ?></span>
            <?php /* // Manual price display if needed:
            if ( $is_on_sale && $sale_price_raw ) : ?>
                <span class="text-xl text-primary font-semibold" style="color: #3B82F6;">$<?php echo wc_price($sale_price_raw); ?></span>
                <span class="text-sm line-through text-gray-500">$<?php echo wc_price($base_price_raw); ?></span>
            <?php elseif ($base_price_raw): ?>
                <span class="text-xl text-gray-900 font-semibold">$<?php echo wc_price($base_price_raw); ?></span>
            <?php endif; */?>
        </div>
    </div>
    <div class="p-6 pt-0"> <?php // Card Footer in index.html structure ?>
        <?php
            // Button links to the single product page
            echo sprintf(
                '<a href="%s" class="%s">%s</a>',
                esc_url( $product_link ),
                esc_attr( 'w-full inline-flex items-center justify-center rounded-md text-sm font-medium h-10 px-4 py-2 bg-primary text-white hover:bg-primary/90' ), // Tailwind classes for button
                esc_html( $button_text ) // Dynamic button text
            );
        ?>
    </div>
</li>