<?php
/**
 * Template Name: Cart (Aakaari)
 * Description: Shell for WooCommerce Cart. Renders Cart Block if present, else falls back to [woocommerce_cart].
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

            // Page content (this will render the Cart Block or any content you added).
            $content = get_the_content();

            $has_cart_block     = function_exists('has_block') && has_block('woocommerce/cart', get_the_ID());
            $has_cart_shortcode = function_exists('has_shortcode') && has_shortcode($content, 'woocommerce_cart');

            echo apply_filters('the_content', $content);

            // Fallback to classic cart if neither block nor shortcode exists in the page content.
            if ( ! $has_cart_block && ! $has_cart_shortcode ) {
                echo do_shortcode('[woocommerce_cart]');
            }

        endwhile;
    else :
        // Absolute fallback if page has no content.
        echo do_shortcode('[woocommerce_cart]');
    endif;
    ?>
</main>
<?php
// Theme wrappers (optional).
do_action('aakaari_wrapper_end');

get_footer();
