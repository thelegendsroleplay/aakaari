<?php
/**
 * Template Name: Custom Products
 *
 * This template displays the filterable "Design Your Products" catalog.
 */

get_header();

// Check if the current user is a reseller
// (You might have a different role name, like 'customer')
$is_reseller = current_user_can('reseller') || current_user_can('customer');

?>

<div class="custom-products-page">
    <div class="hero-section">
        <div class="container">
            <div class="hero-content">
                <span class="hero-badge">Design Your Products</span>
                <h1 class="hero-title">Create Custom Products</h1>
                <p class="hero-description">
                    Choose from our curated collection of customizable products. Add your designs, logos, or text to create unique items for your brand.
                </p>
                <div class="hero-features">
                    <div class="feature-item">
                        <div class="feature-dot"></div>
                        <span>Premium Quality Materials</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-dot"></div>
                        <span>Easy Design Tools</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-dot"></div>
                        <span>Fast Turnaround</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container page-content">
        <div class="filters-card">
            <div class="filters-bar-content">
                <div class="filter-search">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" id="product-search" placeholder="Search products by name or SKU..." />
                </div>

                <select id="product-category" class="filter-select">
                    <option value="all">All Categories</option>
                    <?php
                        $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]);
                        foreach ($categories as $cat) {
                            echo '<option value="' . esc_attr($cat->slug) . '">' . esc_html($cat->name) . '</option>';
                        }
                    ?>
                </select>

                <select id="product-sort" class="filter-select">
                    <option value="popular">Most Popular</option>
                    <option value="rating">Highest Rated</option>
                    <option value="price-low">Price: Low to High</option>
                    <option value="price-high">Price: High to Low</option>
                    <option value="name">Name: A to Z</option>
                </select>

                <div class="view-mode-toggle">
                    <button id="view-grid-btn" class="view-btn active" aria-label="Grid View">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 12h18"/><path d="M12 3v18"/></svg>
                    </button>
                    <button id="view-list-btn" class="view-btn" aria-label="List View">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18"y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="results-count-container">
            <p>Showing <span id="product-count">0</span> customizable products</p>
        </div>

        <div id="products-list-container" class="view-grid">
            <?php
                $args = array(
                    'post_type' => 'product',
                    'posts_per_page' => -1, // Get all products
                    'status' => 'publish',
                );
                $products_query = new WP_Query($args);

                if ($products_query->have_posts()) :
                    while ($products_query->have_posts()) : $products_query->the_post();
                        global $product;
                        
                        // Get all data for JS filtering
                        $product_id = $product->get_id();
                        $name = $product->get_name();
                        $sku = $product->get_sku() ? $product->get_sku() : 'N/A';
                        $price = $product->get_price() ? (float)$product->get_price() : 0;
                        $retail_price = $product->get_regular_price() ? (float)$product->get_regular_price() : $price;
                        $rating = (float)$product->get_average_rating();
                        $reviews = (int)$product->get_review_count();
                        
                        // Get categories for filtering
                        $cat_slugs = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'slugs']);
                        $cat_slug_str = implode(',', $cat_slugs); // Join slugs with comma
                        $first_cat = wp_get_post_terms($product_id, 'product_cat', ['number' => 1]);
                        $category_name = !empty($first_cat) ? $first_cat[0]->name : 'Uncategorized';
                        
                        // Check for Bestseller tag
                        $is_bestseller = has_term('bestseller', 'product_tag', $product_id);
                        
                        // Get Custom Meta (Placeholders)
                        $discount = get_post_meta($product_id, '_product_discount_percent', true);
                        $moq = get_post_meta($product_id, '_product_moq', true) ? : 10; // Default 10
                        $print_areas = get_product_print_areas($product_id); // Using our helper
                        
                        // Customizer URL
                        $customizer_url = home_url('/product-customizer/?product_id=' . $product_id);
            ?>

            <div class="product-item" 
                 data-name="<?php echo esc_attr($name); ?>"
                 data-sku="<?php echo esc_attr($sku); ?>"
                 data-category="<?php echo esc_attr($cat_slug_str); ?>"
                 data-price="<?php echo esc_attr($price); ?>"
                 data-rating="<?php echo esc_attr($rating); ?>"
                 data-reviews="<?php echo esc_attr($reviews); ?>"
            >
                <div class="product-card">
                    
                    <div class="product-image-col">
                        <a href="<?php echo esc_url($customizer_url); ?>">
                            <?php echo woocommerce_get_product_thumbnail('woocommerce_thumbnail'); ?>
                        </a>
                        <?php if ($is_bestseller) : ?>
                            <span class="badge bestseller-badge">Bestseller</span>
                        <?php endif; ?>
                        <?php if (!empty($discount)) : ?>
                            <span class="badge discount-badge"><?php echo esc_html($discount); ?>% OFF</span>
                        <?php endif; ?>
                        
                        <a href="<?php echo esc_url($customizer_url); ?>" class="hover-customize-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M12 15v-1a2 2 0 0 0-4 0v1"/><path d="M14.5 11a.5.5 0 0 0-1 0v3a.5.5 0 0 0 1 0v-3z"/><path d="m17 12-1-1-1 1"/><path d="m17 14 1 1 1-1"/><path d="m7 12 1-1 1 1"/><path d="m7 14-1 1-1-1"/></svg>
                            Customize Now
                        </a>
                    </div>
                    
                    <div class="product-content-col">
                        <div class="product-content-main">
                            <div class="product-meta-top">
                                <span class="badge category-badge"><?php echo esc_html($category_name); ?></span>
                                <span class="product-sku">SKU: <?php echo esc_html($sku); ?></span>
                            </div>
                            
                            <h3 class="product-title"><?php echo esc_html($name); ?></h3>
                            
                            <div class="product-rating">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                <span><?php echo esc_html($rating); ?></span>
                                <span class="divider">•</span>
                                <span class="reviews"><?php echo esc_html($reviews); ?> reviews</span>
                            </div>

                            <div class="product-list-details">
                                <div class_="detail-grid">
                                    <div>
                                        <div class="detail-label">Available Colors</div>
                                        <div class="detail-value"><?php echo count($product->get_attribute('pa_color') ? $product->get_attribute('pa_color') : []); ?> colors</div>
                                    </div>
                                    <div>
                                        <div class="detail-label">Available Sizes</div>
                                        <div class="detail-value"><?php echo esc_html(implode(', ', $product->get_attribute('pa_size') ? $product->get_attribute('pa_size') : ['N/A'])); ?></div>
                                    </div>
                                    <div>
                                        <div class="detail-label">Print Areas</div>
                                        <div class="detail-value"><?php echo count($print_areas); ?> areas</div>
                                    </div>
                                     <div>
                                        <div class="detail-label">Min. Order</div>
                                        <div class="detail-value"><?php echo esc_html($moq); ?> units</div>
                                    </div>
                                </div>
                                <div class="print-areas-list">
                                    <?php foreach ($print_areas as $area) : ?>
                                        <span class="badge secondary-badge"><?php echo esc_html($area['name'] . ' (' . $area['size'] . ')'); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="product-price-info">
                                <div class="price-wrap">
                                    <span class="price"><?php echo wc_price($price); ?></span>
                                    <span class="unit-label">/unit</span>
                                </div>
                                <?php if ($is_reseller && $retail_price > $price) : ?>
                                    <span class="profit-label">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                                        <?php echo wc_price($retail_price - $price); ?> profit
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-grid-details">
                                <div class="detail-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M12 15v-1a2 2 0 0 0-4 0v1"/><path d="M14.5 11a.5.5 0 0 0-1 0v3a.5.5 0 0 0 1 0v-3z"/><path d="m17 12-1-1-1 1"/><path d="m17 14 1 1 1-1"/><path d="m7 12 1-1 1 1"/><path d="m7 14-1 1-1-1"/></svg>
                                    <span><?php echo count($print_areas); ?> Print Areas</span>
                                </div>
                                <div class="detail-item-desc">
                                    <?php echo esc_html(implode(', ', array_column($print_areas, 'name'))); ?>
                                </div>
                            </div>
                            </div>
                        
                        <div class="product-actions">
                            <a href="<?php echo esc_url($customizer_url); ?>" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M12 15v-1a2 2 0 0 0-4 0v1"/><path d="M14.5 11a.5.5 0 0 0-1 0v3a.5.5 0 0 0 1 0v-3z"/><path d="m17 12-1-1-1 1"/><path d="m17 14 1 1 1-1"/><path d="m7 12 1-1 1 1"/><path d="m7 14-1 1-1-1"/></svg>
                                Design
                            </a>
                            <a href="<?php the_permalink(); ?>" class="btn btn-outline">
                                Details
                            </a>
                        </div>
                        
                        <div class="product-footer">
                            Min. order: <?php echo esc_html($moq); ?> units
                        </div>
                    </div>

                </div>
            </div>

            <?php
                    endwhile;
                endif;
                wp_reset_postdata();
            ?>
        </div>

        <div id="products-empty-state" class="empty-state-card" style="display: none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            <h3>No products found</h3>
            <p>Try adjusting your search or filter criteria</p>
            <button id="clear-filters-btn" class="btn btn-primary">Clear Filters</button>
        </div>
        
    </div>
</div>

<?php
get_footer();
?>