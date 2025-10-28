<?php

/**

 * Template Name: Cart (Aakaari)

 */

defined('ABSPATH') || exit;

get_header();

do_action('aakaari_wrapper_start');

?>

<main id="primary" class="site-main aakaari-cart-page">

    <?php

    // Print notices

    if ( function_exists('wc_print_notices') ) {

        wc_print_notices();

    }

    // Force loading custom WooCommerce cart template from your theme

    if ( class_exists('WooCommerce') ) {

        wc_get_template('cart/cart.php');

    } else {

        echo '<p>WooCommerce plugin not active.</p>';

    }

    ?>

</main>

<?php

do_action('aakaari_wrapper_end');

get_footer();