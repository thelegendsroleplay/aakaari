<?php
/**
 * Template Name: Checkout (Aakaari)
 * Description: Shell for WooCommerce Checkout. Renders Checkout Block if present, else falls back to [woocommerce_checkout].
 */

defined('ABSPATH') || exit;

get_header();

// Theme wrappers (optional).
do_action('aakaari_wrapper_start');

// WooCommerce notices.
if ( function_exists('wc_print_notices') ) {
    wc_print_notices();
}
?>
<main id="primary" class="site-main">
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) : the_post();

            // Detect special checkout endpoints.
            $is_pay_page      = function_exists('is_checkout_pay_page') && is_checkout_pay_page();
            $is_received_page = function_exists('is_order_received_page') && is_order_received_page();

            // Page content (this will render the Checkout Block or any content you added).
            $content = get_the_content();

            $has_checkout_block     = function_exists('has_block') && has_block('woocommerce/checkout', get_the_ID());
            $has_checkout_shortcode = function_exists('has_shortcode') && has_shortcode($content, 'woocommerce_checkout');

            // For "Pay for order" endpoint, force classic checkout (Block doesn't support it).
            if ( $is_pay_page ) {
                echo do_shortcode('[woocommerce_checkout]');
            } else {
                // Always render content first to allow Block/shortcode placed in editor to run.
                echo apply_filters('the_content', $content);

                // If it's NOT the "Order received" endpoint and neither Block nor shortcode is present, inject classic checkout.
                if ( ! $is_received_page && ! $has_checkout_block && ! $has_checkout_shortcode ) {
                    echo do_shortcode('[woocommerce_checkout]');
                }
            }

        endwhile;
    else :
        // No page content: still render checkout (classic).
        echo do_shortcode('[woocommerce_checkout]');
    endif;
    ?>
</main>
<?php
// Theme wrappers (optional).
do_action('aakaari_wrapper_end');

get_footer();
