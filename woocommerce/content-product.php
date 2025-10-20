<?php
defined( 'ABSPATH' ) || exit;
global $product;
?>
<div <?php wc_product_class( 'product' ); ?>>
    <a href="<?php the_permalink(); ?>">
        <?php
        if ( has_post_thumbnail() ) {
            echo get_the_post_thumbnail( get_the_ID(), 'medium' );
        } else {
            echo '<img src="' . esc_url( get_template_directory_uri() . '/assets/img/placeholder.png' ) . '" alt="placeholder" />';
        }
        ?>
        <h2 class="product-title"><?php the_title(); ?></h2>
        <div class="product-price"><?php echo $product->get_price_html(); ?></div>
    </a>
</div>
