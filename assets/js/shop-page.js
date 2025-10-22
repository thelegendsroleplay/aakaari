/**
 * Aakaari Theme: Shop Page JavaScript
 * Handles filtering logic based on the standalone script.js
 */
jQuery(document).ready(function($) {
    const shopContent = $('#shop-page-content'); // Main container for shop page elements
    if (!shopContent.length) return; // Only run on pages with this container

    // --- DOM Cache ---
    const dom = {
        searchInput: shopContent.find('#woocommerce-product-search-field-shop'), // Use WC search field ID
        categorySelect: shopContent.find('#category-select'),
        productGrid: shopContent.find('.products'), // WooCommerce uses ul.products
        noProductsMessage: shopContent.find('#no-products-message')
    };

    // --- State ---
    let state = {
        searchTerm: dom.searchInput.val() || '',
        selectedCategory: dom.categorySelect.val() || 'all'
    };

    // --- Functions ---

    // Function to populate categories (mimics original JS logic)
    function populateCategories() {
        // In WordPress, categories are usually part of the page load.
        // This function is kept for structural similarity but might not be
        // strictly needed if using wp_dropdown_categories in PHP fully.
        // However, we'll use it to ensure the 'change' event works as expected.

        // Let's assume categories might be passed via shopPageData if needed,
        // or we could fetch them via AJAX. For now, rely on PHP render.
        // If PHP renders the <select>, we just need the change handler.
        console.log("Categories assumed to be rendered by PHP.");
    }

    // --- Event Listeners ---

    // Search Input (using standard WC search form now)
    // No JS needed if using the standard form submission.
    // If you want live filtering (AJAX), add JS here. Example commented out:
    /*
    dom.searchInput.on('input', debounce(function() {
        state.searchTerm = $(this).val();
        // TODO: Implement AJAX product filtering based on state.searchTerm and state.selectedCategory
        console.log("Filtering for:", state.searchTerm, state.selectedCategory);
        // Example: triggerFilterUpdate();
    }, 300));
    */

    // Category Select (redirects on change via inline script in PHP)
    // No extra JS needed here as the inline script handles it.
    // If you wanted AJAX filtering:
    /*
    dom.categorySelect.on('change', function() {
        state.selectedCategory = $(this).val();
        // TODO: Implement AJAX product filtering
        console.log("Filtering for:", state.searchTerm, state.selectedCategory);
        // Example: triggerFilterUpdate();
    });
    */

    // --- Initialization ---
    populateCategories(); // Call even if it does little, maintains structure
    if (window.lucide) { // Ensure icons render
         lucide.createIcons();
     }

    // --- Helper: Debounce --- (Optional, useful for AJAX filtering)
    function debounce(func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    }

});