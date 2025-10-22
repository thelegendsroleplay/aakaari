<?php
/**
 * The Template for displaying product archives (Shop Page).
 * Uses custom HTML structure and CSS classes (shop-style.css).
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
do_action( 'woocommerce_before_main_content' );
?>

<?php // 1. Custom Shop Header Section ?>
<div class="shop-header">
    <div class="shop-container">
        <h1 class="shop-header-title">Custom Print Studio</h1>
        <p class="shop-header-subtitle">
            Create unique, personalized products with our easy-to-use design tools
        </p>
        <div class="shop-header-features">
            <div class="feature-item">
                <i data-lucide="paintbrush" class="feature-icon"></i>
                Multiple Print Methods
            </div>
            <div class="feature-item">
                <i data-lucide="package" class="feature-icon"></i>
                High Quality Products
            </div>
        </div>
    </div>
</div>

<?php // 2. Main Shop Content Area ?>
<div class="shop-main">
    <div class="shop-container">

        <?php // 3. Filter Bar ?>
        <div class="shop-filters">
            <div class="search-wrapper">
                <i data-lucide="search" class="search-icon"></i>
                <input
                    id="search-input"
                    placeholder="Search products..."
                    class="search-input"
                    type="search"
                />
            </div>
            <select id="category-select" class="category-filter">
                <option value="all">All Categories</option>
                <?php // Options added by shop-logic.js ?>
            </select>
        </div>

        <?php // 4. Product Grid (Populated by JS) ?>
        <div id="product-grid" class="product-grid">
            <p class="loading-message">Loading products...</p> <?php // Loading message ?>
        </div>

        <?php // 5. No Products Message (Hidden initially) ?>
        <div id="no-products-message" class="no-products-message hidden">
            <i data-lucide="package-x" class="no-products-icon"></i>
            <h3 class="no-products-title">No products found</h3>
            <p class="no-products-text">Try adjusting your search or filters.</p>
        </div>

    </div> <?php // End .shop-container ?>
</div> <?php // End .shop-main ?>

<?php
do_action( 'woocommerce_after_main_content' );
get_footer( 'shop' );
?>