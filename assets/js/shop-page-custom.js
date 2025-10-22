/**
 * Aakaari Theme: Custom Shop Page JavaScript
 * Handles interactions for the standalone shop page template.
 * Product rendering is now handled by PHP.
 */
jQuery(document).ready(function($) {
    const shopPageContainer = $('#shop-page'); // Target the main container
    if (!shopPageContainer.length) return; // Only run on this page template

    // ========================================================================
    // DOM Cache (Keep relevant elements if needed for interactions)
    // ========================================================================
    const dom = {
        searchInput: shopPageContainer.find('#woocommerce-product-search-field-custom-shop'),
        categorySelect: shopPageContainer.find('#category-select'),
        productGrid: shopPageContainer.find('#product-grid'), // Direct reference to grid
        noProductsMessage: shopPageContainer.find('#no-products-message')
    };

    // ========================================================================
    // Event Listeners (Keep if needed, adjust if using standard WP filtering)
    // ========================================================================

    // Search: Standard form submission is handled by PHP template.
    // If you add AJAX search later, uncomment and modify.
    /*
    dom.searchInput.on('input', debounce(function() {
        console.log("AJAX Search for:", $(this).val());
        // Add AJAX filtering logic here
    }, 300));
    */

    // Category Select: Redirect is handled by inline script in PHP template.
    // If you add AJAX category filtering later, uncomment and modify.
    /*
    dom.categorySelect.on('change', function() {
        console.log("AJAX Filter by Category:", $(this).val());
        // Add AJAX filtering logic here
    });
    */

    // Example: Click handler for product buttons (can remain if needed)
    dom.productGrid.on('click', '[data-product-id]', function(e) {
        // This button now links directly via PHP, so JS might not be needed
        // unless you want to intercept the click for something else.
        // e.preventDefault(); // Uncomment if you want JS to handle navigation
        const productId = $(this).data('product-id');
        console.log("Clicked product button for ID (handled by PHP link):", productId);
        // Maybe track click? Or open quick view?
    });

    // ========================================================================
    // Initialization
    // ========================================================================

    // Ensure Lucide icons render after page load (PHP also adds a script tag)
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        lucide.createIcons();
    }
    console.log("Custom Shop Page JS Initialized.");

    // --- Helper: Debounce --- (Optional)
    function debounce(func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => { clearTimeout(timeout); func.apply(this, args); };
        clearTimeout(timeout); timeout = setTimeout(later, wait);
      };
    }

});