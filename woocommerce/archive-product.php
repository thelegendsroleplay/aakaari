<?php
/**
 * The template for displaying product archives, including the main shop page
 *
 * Structure based on the standalone index.html shop page section.
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' ); // Use the shop header

?>
<div id="shop-page-content" class="min-h-screen bg-background"> <?php // Match outer div from index.html ?>

    <div class="bg-gradient-to-br from-primary to-secondary text-primary-foreground py-16" style="--tw-gradient-from: #3B82F6; --tw-gradient-to: #6366F1; color: white;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl mb-4 font-bold">Custom Print Studio</h1>
            <p class="text-xl mb-8 opacity-90">
                Create unique, personalized products with our easy-to-use design tools
            </p>
            <div class="flex justify-center gap-4">
                <div class="inline-flex items-center rounded-full border border-transparent bg-secondary px-4 py-2 text-lg font-semibold text-secondary-foreground" style="background-color: rgba(255,255,255, 0.2);">
                    <i data-lucide="paintbrush" class="mr-2 h-5 w-5"></i>
                    Multiple Print Methods
                </div>
                <div class="inline-flex items-center rounded-full border border-transparent bg-secondary px-4 py-2 text-lg font-semibold text-secondary-foreground" style="background-color: rgba(255,255,255, 0.2);">
                    <i data-lucide="package" class="mr-2 h-5 w-5"></i>
                    High Quality Products
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="mb-8">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500"></i>
                        <?php // We can use the standard search form but style it like the input ?>
                        <form role="search" method="get" class="woocommerce-product-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                            <label class="screen-reader-text" for="woocommerce-product-search-field-shop"><?php esc_html_e( 'Search for:', 'woocommerce' ); ?></label>
                            <input
                                type="search"
                                id="woocommerce-product-search-field-shop" <?php // Changed ID slightly ?>
                                class="search-field pl-10 w-full h-10 px-3 py-2 border border-gray-300 rounded-md text-sm" <?php // Tailwind classes ?>
                                placeholder="<?php echo esc_attr__( 'Search products&hellip;', 'woocommerce' ); ?>"
                                value="<?php echo get_search_query(); ?>" name="s"
                            />
                            <input type="hidden" name="post_type" value="product" />
                        </form>
                        <?php /* Original input if preferred, but form is better:
                        <input
                            id="search-input"
                            placeholder="Search products..."
                            class="pl-10 w-full h-10 px-3 py-2 border border-gray-300 rounded-md text-sm"
                        /> */ ?>
                    </div>
                </div>
                <?php
                    // WooCommerce Product Categories Dropdown, styled like index.html
                    $cat_args = array(
                        'taxonomy'     => 'product_cat',
                        'orderby'      => 'name',
                        'show_count'   => 0,
                        'hierarchical' => 1,
                        'title_li'     => '',
                        'hide_empty'   => 1,
                        'value_field'  => 'slug',
                        'show_option_all' => __('All Categories', 'aakaari'),
                        'id'           => 'category-select', // Match ID from index.html
                        'class'        => 'w-full sm:w-[200px] h-10 px-3 py-2 border border-gray-300 rounded-md text-sm bg-white', // Tailwind classes
                        'name'         => 'product_cat_filter_dd', // Needs unique name
                        'selected'     => get_query_var( 'product_cat' )
                    );
                ?>
                <select id="<?php echo esc_attr($cat_args['id']); ?>" name="<?php echo esc_attr($cat_args['name']); ?>" class="<?php echo esc_attr($cat_args['class']); ?>">
                     <?php /* Intentionally empty - JS will populate and handle change */ ?>
                     <option value="all">All Categories</option> <?php // Keep default option ?>
                     <?php // Let JS populate categories dynamically for consistency with standalone ?>
                </select>
            </div>
        </div>

        <?php
        /**
         * Hook: woocommerce_before_main_content.
         * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content) - REMOVED LATER
         * @hooked woocommerce_breadcrumb - 20 - Keep breadcrumbs? Maybe add inside max-w-7xl
         */
        do_action( 'woocommerce_before_main_content' );
        ?>

        <?php // Optional: Add sorting/result count inside this container ?>
         <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
             <?php do_action( 'woocommerce_before_shop_loop' ); ?>
         </div>

        <?php
        if ( woocommerce_product_loop() ) {

            woocommerce_product_loop_start(); // Outputs <ul class="products ...">

            if ( wc_get_loop_prop( 'total' ) ) {
                while ( have_posts() ) {
                    the_post();
                    do_action( 'woocommerce_shop_loop' );
                    wc_get_template_part( 'content', 'product' ); // <-- Loads the styled card
                }
            }

            woocommerce_product_loop_end(); // Outputs </ul>

            /**
             * Hook: woocommerce_after_shop_loop. Output pagination
             */
             ?>
             <div class="mt-8">
                <?php do_action( 'woocommerce_after_shop_loop' ); ?>
             </div>
             <?php

        } else {
            /**
             * Hook: woocommerce_no_products_found. Output "No products" message
             */
            ?>
             <div id="no-products-message" class="text-center py-16"> <?php // Match ID from index.html ?>
                 <i data-lucide="package" class="mx-auto h-16 w-16 text-gray-500 mb-4"></i>
                 <h3 class="text-xl mb-2 font-semibold">No products found</h3>
                 <p class="text-gray-500">
                     <?php
                     if ( is_search() ) {
                         esc_html_e( 'Sorry, no products matched your search terms.', 'woocommerce' );
                     } else {
                         esc_html_e( 'Try adjusting your filters or check back later!', 'aakaari' );
                     }
                     ?>
                 </p>
             </div>
            <?php
             // do_action( 'woocommerce_no_products_found' ); // Default message is less styled
        }
        ?>

        <?php
        /**
         * Hook: woocommerce_after_main_content.
         * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content) - REMOVED LATER
         */
        do_action( 'woocommerce_after_main_content' );
        ?>

    </div> </div> <?php // End #shop-page-content ?>
<?php

// Ensure Lucide icons render
echo '<script> if(window.lucide && typeof window.lucide.createIcons === \'function\') { lucide.createIcons(); } </script>';

get_footer( 'shop' ); // Use the shop footer