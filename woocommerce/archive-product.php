<?php
defined( 'ABSPATH' ) || exit;
get_header( 'shop' ); ?>
<div class="container">
<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
    <h1><?php woocommerce_page_title(); ?></h1>
<?php endif; ?>

<?php
do_action( 'woocommerce_archive_description' );
if ( woocommerce_product_loop() ) {
    woocommerce_product_loop_start();
    if ( wc_get_loop_prop( 'total' ) ) {
        while ( have_posts() ) {
            the_post();
            wc_get_template_part( 'content', 'product' );
        }
    }
    woocommerce_product_loop_end();
    do_action( 'woocommerce_after_shop_loop' );
} else {
    do_action( 'woocommerce_no_products_found' );
}
?>
</div>
<?php get_footer( 'shop' ); ?>
