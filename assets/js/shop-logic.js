/**
 * Shop Logic JS - Fixed version
 * Handles product loading, filtering, and display for the shop page
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        initShopPage();
    });
    
    function initShopPage() {
        // Exit if AakaariShopData isn't defined
        if (typeof AakaariShopData === 'undefined') {
            console.error('AakaariShopData not found. Check product-customizer-functions.php');
            return;
        }
        
        const products = AakaariShopData.products || [];
        const categories = AakaariShopData.categories || [];
        
        // Setup category filter
        populateCategoryFilter(categories);
        
        // Render initial products
        renderProducts(products);
        
        // Setup event listeners
        setupEventListeners(products);
        
        // Initialize Lucide icons if available
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
        
        console.log('Shop page initialized with', products.length, 'products');
    }
    
    function populateCategoryFilter(categories) {
        const categorySelect = $('#category-select');
        
        // Keep the "All Categories" option and add the rest
        categories.forEach(function(category) {
            categorySelect.append(`<option value="${category.slug}">${category.name}</option>`);
        });
    }
    
    function renderProducts(products, filterCategory = 'all', searchTerm = '') {
        const productGrid = $('#product-grid');
        const noProductsMessage = $('#no-products-message');
        
        // Apply filters
        let filteredProducts = products;
        
        // Filter by category if not 'all'
        if (filterCategory !== 'all') {
            filteredProducts = filteredProducts.filter(product => product.category === filterCategory);
        }
        
        // Filter by search term if provided
        if (searchTerm) {
            const search = searchTerm.toLowerCase();
            filteredProducts = filteredProducts.filter(product => 
                product.name.toLowerCase().includes(search) || 
                (product.description && product.description.toLowerCase().includes(search))
            );
        }
        
        // Clear the loading message and grid
        productGrid.empty();
        
        // Check if we have products to display
        if (filteredProducts.length === 0) {
            productGrid.html('');
            noProductsMessage.removeClass('hidden');
            return;
        }
        
        // Hide the no products message if we have products
        noProductsMessage.addClass('hidden');
        
        // Create and append product cards
        filteredProducts.forEach(function(product) {
            productGrid.append(createProductCard(product));
        });
    }
    
    function createProductCard(product) {
        // Format prices
        const hasDiscount = product.salePrice !== null;
        const formattedRegularPrice = formatPrice(hasDiscount ? product.basePrice : product.displayPrice);
        const formattedSalePrice = hasDiscount ? formatPrice(product.salePrice) : '';
        
        // Prepare tag HTML
        let tagsHtml = '';
        if (product.category) {
            tagsHtml += `<span class="product-card-tag">${product.category}</span>`;
        }
        
        // Determine button text
        const buttonText = product.isCustomizable ? 'Customize Now' : 'View Product';
        
        // Create the card HTML
        return `
            <div class="product-card">
                <a href="${product.permalink}" class="product-card-image-link">
                    <img src="${product.thumbnail}" alt="${product.name}" class="product-card-image">
                    ${hasDiscount ? '<div class="product-card-badge">Sale</div>' : ''}
                </a>
                <div class="product-card-content">
                    <h3 class="product-card-title">
                        <a href="${product.permalink}">${product.name}</a>
                    </h3>
                    ${product.description ? `<p class="product-card-description">${truncateText(product.description, 100)}</p>` : ''}
                    <div class="product-card-tags">
                        ${tagsHtml}
                    </div>
                    <div class="product-card-price">
                        ${hasDiscount ? 
                            `<span class="product-card-sale-price">₹${formattedSalePrice}</span>
                             <span class="product-card-regular-price">₹${formattedRegularPrice}</span>` : 
                            `<span class="product-card-regular-price-no-sale">₹${formattedRegularPrice}</span>`}
                    </div>
                </div>
                <div class="product-card-button-section">
                    <a href="${product.permalink}" class="aakaari-button-primary">${buttonText}</a>
                </div>
            </div>
        `;
    }
    
    function setupEventListeners(products) {
        // Category filter change
        $('#category-select').on('change', function() {
            const category = $(this).val();
            const searchTerm = $('#search-input').val();
            renderProducts(products, category, searchTerm);
        });
        
        // Search input
        $('#search-input').on('input', function() {
            const searchTerm = $(this).val();
            const category = $('#category-select').val();
            renderProducts(products, category, searchTerm);
        });
    }
    
    // Helper Functions
    function formatPrice(price) {
        return parseFloat(price).toFixed(2);
    }
    
    function truncateText(text, maxLength) {
        if (!text || text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }
    
})(jQuery);