<?php
/**
 * Template Name: Custom Shop Page
 *
 * This template replicates the standalone shop page design
 * AND displays actual WooCommerce products.
 * Requires Tailwind CSS to be loaded by the theme.
 */

get_header(); // Includes your theme's header

// --- Get current query variables for filtering ---
$paged = ( get_query_var( 'paged' ) ) ? absint( get_query_var( 'paged' ) ) : 1;
$search_query = get_search_query(); // Get search term if submitted via form
$product_cat_slug = get_query_var( 'product_cat' ); // Get category if navigating via category links/dropdown redirect

// --- Setup WooCommerce Query Arguments ---
$args = array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => apply_filters( 'loop_shop_per_page', wc_get_loop_prop( 'posts_per_page' ) ), // Use WC setting for products per page
    'paged'          => $paged,
    'orderby'        => isset($_GET['orderby']) ? wc_clean(wp_unslash($_GET['orderby'])) : 'date', // Allow sorting
    'order'          => isset($_GET['order']) ? wc_clean(wp_unslash($_GET['order'])) : 'DESC',
);

// Add search query if present
if ( ! empty( $search_query ) ) {
    $args['s'] = $search_query;
}

// Add category query if present
if ( ! empty( $product_cat_slug ) ) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $product_cat_slug,
        ),
    );
}

// --- Execute the Product Query ---
$products_query = new WP_Query( $args );

?>

    <?php // --- Start: Structure based on index.html --- ?>
    <div id="shop-page">
        <div class="min-h-screen bg-background">
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
                                <?php // Use standard search form, styled ?>
                                <form role="search" method="get" class="woocommerce-product-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                                    <label class="screen-reader-text" for="woocommerce-product-search-field-custom-shop"><?php esc_html_e( 'Search for:', 'woocommerce' ); ?></label>
                                    <input
                                        type="search"
                                        id="woocommerce-product-search-field-custom-shop"
                                        class="search-field pl-10 w-full h-10 px-3 py-2 border border-gray-300 rounded-md text-sm" <?php // Tailwind classes ?>
                                        placeholder="<?php echo esc_attr__( 'Search products&hellip;', 'woocommerce' ); ?>"
                                        value="<?php echo get_search_query(); ?>" name="s"
                                    />
                                    <input type="hidden" name="post_type" value="product" />
                                     <?php // Keep current page template when searching ?>
                                    <input type="hidden" name="page_id" value="<?php echo get_the_ID(); ?>" />
                                </form>
                            </div>
                        </div>
                        <?php
                        // Categories Dropdown - Use wp_dropdown_categories for actual categories
                        $cat_args = array(
                            'taxonomy'     => 'product_cat',
                            'orderby'      => 'name',
                            'show_count'   => 0,
                            'hierarchical' => 1,
                            'title_li'     => '',
                            'hide_empty'   => 1,
                            'value_field'  => 'slug',
                            'show_option_all' => __('All Categories', 'aakaari'),
                            'id'           => 'category-select', // Keep ID for consistency & potential JS
                            'class'        => 'w-full sm:w-[200px] h-10 px-3 py-2 border border-gray-300 rounded-md text-sm bg-white', // Tailwind classes
                            'name'         => 'product_cat_filter_dd',
                            'selected'     => $product_cat_slug // Pre-select current category
                        );
                        ?>
                        <select id="<?php echo esc_attr($cat_args['id']); ?>" name="<?php echo esc_attr($cat_args['name']); ?>" class="<?php echo esc_attr($cat_args['class']); ?>" style="background-image: url('data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 20 20\'%3e%3cpath stroke=\'%236b7280\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M6 8l4 4 4-4\'/%3e%3c/svg%3e'); background-position: right 0.5rem center; background-size: 1.5em 1.5em; appearance: none; padding-right: 2.5rem;">
                             <?php wp_dropdown_categories( $cat_args ); ?>
                        </select>
                         <script type="text/javascript">
                            // JS to handle dropdown change redirect TO THIS PAGE with query var
                            jQuery(document).ready(function($) {
                                $('#category-select').on('change', function() {
                                    var cat_slug = this.value;
                                    var current_url = new URL(window.location.href);
                                    if (cat_slug && cat_slug !== 'all') {
                                        current_url.searchParams.set('product_cat', cat_slug);
                                    } else {
                                        current_url.searchParams.delete('product_cat');
                                    }
                                    // Remove paged param when changing category/search
                                    current_url.searchParams.delete('paged');
                                    window.location.href = current_url.toString();
                                });

                                // Ensure search form submits to this page template
                                $('.woocommerce-product-search').on('submit', function() {
                                    $(this).find('input[name="page_id"]').val(<?php echo get_the_ID(); ?>);
                                });
                            });
                        </script>
                    </div>
                </div>

                <?php // Optional: Add sorting dropdown (WooCommerce default) ?>
                <div class="flex justify-end mb-4">
                    <?php woocommerce_catalog_ordering(); ?>
                </div>

                <?php if ( $products_query->have_posts() ) : ?>
                    <div id="product-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <?php
                        // --- START PRODUCT LOOP ---
                        while ( $products_query->have_posts() ) : $products_query->the_post();
                            global $product; // Make product object available

                            // Get data for the card
                            $product_id = $product->get_id();
                            $product_link = get_permalink($product_id);
                            $thumbnail_id = $product->get_image_id();
                            $image_url = wp_get_attachment_image_url($thumbnail_id, 'woocommerce_thumbnail');
                            $product_name = $product->get_name();
                            $description = $product->get_short_description() ?: wp_trim_words($product->get_description(), 15, '...');
                            $is_on_sale = $product->is_on_sale();
                            $price_html = $product->get_price_html();

                            // Category
                            $categories = wc_get_product_terms($product_id, 'product_cat', array('orderby' => 'parent', 'order' => 'DESC'));
                            $category_name = !empty($categories) ? $categories[0]->name : __('Uncategorized', 'woocommerce');

                            // Sides count (using ACF)
                            $sides_count = 0;
                            if (function_exists('get_field')) {
                                $sides = get_field('product_sides', $product_id);
                                if (is_array($sides)) $sides_count = count($sides);
                            }

                            // Customizable check (using ACF)
                            $is_customizable = function_exists('get_field') ? get_field('is_customizable', $product_id) : false;
                            $button_text = $is_customizable ? __('Customize Now', 'aakaari') : __('View Product', 'aakaari');
                        ?>
                        <?php // --- Start: Card HTML from index.html --- ?>
                        <div class="overflow-hidden hover:shadow-lg transition-shadow rounded-lg border bg-white text-gray-900 shadow-sm flex flex-col h-full"> <?php // Added flex flex-col h-full ?>
                            <div class="aspect-square bg-muted flex items-center justify-center relative bg-gray-100">
                                <a href="<?php echo esc_url( $product_link ); ?>">
                                    <?php if ( $image_url ) : ?>
                                        <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $product_name ); ?>" class="w-full h-full object-cover" loading="lazy"/>
                                    <?php else : ?>
                                        <i data-lucide="package" class="h-16 w-16 text-gray-400"></i>
                                    <?php endif; ?>
                                </a>
                                <?php if ( $is_on_sale ) : ?>
                                    <div class="absolute top-2 right-2 inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-accent text-accent-foreground bg-yellow-100 text-yellow-800">
                                        Sale
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-6 flex-grow"> <?php // Added flex-grow ?>
                                <div class="flex justify-between items-start gap-2">
                                    <h3 class="text-base font-semibold line-clamp-2">
                                        <a href="<?php echo esc_url( $product_link ); ?>"><?php echo esc_html( $product_name ); ?></a>
                                    </h3>
                                </div>
                                <?php if ( $description ) : ?>
                                    <p class="text-sm text-gray-500 line-clamp-2 mt-1">
                                        <?php echo wp_kses_post( $description ); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="p-6 pt-0 mt-auto"> <?php // Added mt-auto to push content down ?>
                                 <div class="flex items-center flex-wrap gap-2 mb-3">
                                     <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-gray-100 border-gray-200">
                                         <?php echo esc_html($category_name); ?>
                                     </span>
                                     <?php if ($sides_count > 0) : ?>
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-gray-100 border-gray-200">
                                            <?php echo esc_html($sides_count); ?> sides
                                        </span>
                                     <?php endif; ?>
                                </div>
                                <div class="flex items-baseline gap-2 mb-4"> <?php // Added margin-bottom ?>
                                    <span class="text-xl font-semibold text-gray-900"><?php echo $price_html; ?></span>
                                </div>
                                <a href="<?php echo esc_url( $product_link ); ?>" class="w-full inline-flex items-center justify-center rounded-md text-sm font-medium h-10 px-4 py-2 bg-primary text-white hover:bg-primary/90" style="background-color: #3B82F6;">
                                    <?php echo esc_html( $button_text ); ?>
                                </a>
                            </div>
                        </div>
                        <?php // --- End: Card HTML --- ?>
                        <?php
                        endwhile; // --- END PRODUCT LOOP ---
                        ?>
                    </div> <?php // End #product-grid ?>

                    <?php
                        // --- PAGINATION ---
                        $total_pages = $products_query->max_num_pages;
                        if ($total_pages > 1){
                            echo '<div class="mt-8 woocommerce-pagination">'; // Add WC class for potential styling
                            $big = 999999999; // need an unlikely integer
                            $args = array(
                                'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                                'format'    => '?paged=%#%',
                                'current'   => max( 1, $paged ),
                                'total'     => $total_pages,
                                'prev_text' => __('&laquo; Previous'),
                                'next_text' => __('Next &raquo;'),
                                'type'      => 'list', // Use list for easier styling
                                // 'add_args'  => false // May need to add search/cat query vars back if links break
                                'add_args' => array( // Keep existing query vars
                                     's' => $search_query ?: false,
                                     'product_cat' => $product_cat_slug ?: false,
                                     'orderby' => isset($_GET['orderby']) ? wc_clean(wp_unslash($_GET['orderby'])) : false,
                                     'order' => isset($_GET['order']) ? wc_clean(wp_unslash($_GET['order'])) : false,
                                     'page_id' => get_the_ID(), // Keep using this page template
                                ),
                            );
                            echo paginate_links($args);
                            echo '</div>';
                        }
                    ?>

                <?php else : ?>
                    <?php // --- No Products Found --- ?>
                    <div id="no-products-message" class="text-center py-16">
                         <i data-lucide="package" class="mx-auto h-16 w-16 text-gray-500 mb-4"></i>
                         <h3 class="text-xl mb-2 font-semibold">No products found</h3>
                         <p class="text-gray-500">
                             <?php esc_html_e( 'No products were found matching your selection.', 'woocommerce' ); ?>
                         </p>
                    </div>
                <?php endif; ?>
                <?php wp_reset_postdata(); // Important after custom query ?>

                <?php // Hooks removed as we control the structure directly ?>

            </div> </div>
    </div>
     <?php // --- End: Structure based on index.html --- ?>

<?php
// Ensure Lucide icons render
echo '<script> if(window.lucide && typeof window.lucide.createIcons === \'function\') { lucide.createIcons(); } </script>';

get_footer(); // Includes your theme's footer
?>