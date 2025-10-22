/**
 * Logic for the Custom Aakaari Shop Page (archive-product.php)
 * Uses data from window.AakaariShopData (localized via PHP)
 */
(function ($) { // Use jQuery if needed, or plain JS
    'use strict';

    // ========================================================================
    // UTILITY FUNCTIONS << --- ADD THIS SECTION --- >>
    // ========================================================================
    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
    }
    // ========================================================================

    $(function () { // Document Ready

        // --- Check if data exists ---
        if (typeof AakaariShopData === 'undefined' || !AakaariShopData.products) {
            console.error('Aakaari Shop Error: Localized data (AakaariShopData) not found or incomplete.');
            const grid = document.getElementById('product-grid');
            if (grid) grid.innerHTML = '<p class="woocommerce-error sm:col-span-2 lg:col-span-3 xl:col-span-4 text-center py-8">Error loading product data.</p>';
            return; // Stop execution
        }

        // --- State ---
        let state = {
            searchTerm: '',
            selectedCategory: 'all',
            // Get data from PHP
            allProducts: AakaariShopData.products || [],
            allCategories: AakaariShopData.categories || []
        };

        // --- DOM Cache ---
        const dom = {
            searchInput: document.getElementById('search-input'),
            categorySelect: document.getElementById('category-select'),
            productGrid: document.getElementById('product-grid'),
            noProductsMessage: document.getElementById('no-products-message')
        };

        // --- Render Functions ---
function renderShopPage() {
            // 1. Populate Categories (only needs to be done once)
            // ... (category population logic remains the same) ...
             if (dom.categorySelect.options.length <= 1) { // Avoid repopulating
                // Add "All Categories" option first
                 dom.categorySelect.innerHTML = '<option value="all">All Categories</option>';
                 state.allCategories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.slug; // Use slug for value
                    option.textContent = category.name; // Use name for display
                    dom.categorySelect.appendChild(option);
                });
            }
            dom.categorySelect.value = state.selectedCategory;


            // 2. Filter Products
            // ... (filtering logic remains the same) ...
             const filteredProducts = state.allProducts.filter(product => {
                const searchLower = state.searchTerm.toLowerCase();
                const matchesSearch = (product.name && product.name.toLowerCase().includes(searchLower)) ||
                                      (product.description && product.description.toLowerCase().includes(searchLower));
                // Use category SLUG for filtering now
                const matchesCategory = state.selectedCategory === 'all' || product.category === state.selectedCategory;
                return matchesSearch && matchesCategory;
            });


            // 3. Render Product Grid
            dom.productGrid.innerHTML = ''; // Clear previous grid
            if (filteredProducts.length > 0) {
                dom.noProductsMessage.classList.add('hidden');
                filteredProducts.forEach(product => {
                    const displayPrice = product.displayPrice || 0;
                    const isOnSale = product.salePrice && product.basePrice > displayPrice;

                    const salePriceHtml = isOnSale
                        ? `<span class="text-xl text-primary font-semibold" style="color: #3B82F6;">$${displayPrice.toFixed(2)}</span><span class="text-sm line-through text-gray-500">$${product.basePrice.toFixed(2)}</span>`
                        : `<span class="text-xl text-gray-900 font-semibold">$${displayPrice.toFixed(2)}</span>`;

                    // --- Find Category Name for Display ---
                    const categoryObj = state.allCategories.find(cat => cat.slug === product.category);
                    const categoryName = categoryObj ? categoryObj.name : product.category; // Fallback to slug if name not found

                    // --- Get Sides Count ---
                    // We need PHP to pass the sides count if it's not already in AakaariShopData.products
                    // Let's assume PHP adds a 'sidesCount' property for now.
                    // If not, we'll need to adjust the PHP `aakaari_enqueue_shop_assets` function.
                    const sidesCount = product.sidesCount || 0; // Assuming PHP provides this count


                    const productCard = document.createElement('div');
                    productCard.className = "rounded-lg border bg-white text-gray-900 shadow-sm overflow-hidden hover:shadow-lg transition-shadow flex flex-col";

                    // --- UPDATED INNER HTML ---
                    productCard.innerHTML = `
                        <a href="${product.permalink}" class="aspect-square bg-gray-200 flex items-center justify-center relative group">
                            <img src="${product.thumbnail}" alt="${escapeHtml(product.name)}" class="w-full h-full object-cover group-hover:opacity-90 transition-opacity" />
                            ${isOnSale ? '<div class="absolute top-2 right-2 inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-yellow-100 text-yellow-800 z-10">Sale</div>' : ''}
                        </a>
                        <div class="p-6 flex-grow flex flex-col"> ${/* Adjusted padding */''}
                            <h3 class="text-base font-semibold line-clamp-2 mb-1 flex-grow">
                                <a href="${product.permalink}" class="hover:text-primary transition-colors">${escapeHtml(product.name)}</a>
                            </h3>
                            
                            ${/* Added Description from index.html card structure */''}
                            <p class="text-sm text-gray-500 line-clamp-2 mt-1">${escapeHtml(product.description)}</p> 
                            
                            ${/* Added Category & Sides Badges from index.html card structure */''}
                            <div class="flex items-center flex-wrap gap-2 mt-4 mb-3"> 
                                ${categoryName ? `<span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-800">${escapeHtml(categoryName)}</span>` : ''}
                                ${sidesCount > 0 ? `<span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-800">${sidesCount} side${sidesCount > 1 ? 's' : ''}</span>` : ''}
                            </div>

                            <div class="flex items-baseline gap-2"> ${/* Price - same as before */''}
                                ${salePriceHtml}
                            </div>
                        </div>
                        <div class="p-6 pt-0"> ${/* Button Section - Adjusted padding */''}
                            <a href="${product.permalink}"
                               class="shop-product-button w-full mt-auto inline-flex items-center justify-center rounded-md text-sm font-medium h-10 px-4 py-2 bg-primary text-white hover:bg-primary/90"
                               style="background-color: #3B82F6;">
                               Customize Now ${/* Changed Button Text */''}
                           </a>
                        </div>
                    `;
                    dom.productGrid.appendChild(productCard);
                });
            } else {
                dom.noProductsMessage.classList.remove('hidden');
            }

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
        // ... (rest of the file remains the same - event handlers, init etc.) ...
         // --- Event Handlers ---
        function handleSearchInput(e) {
            state.searchTerm = e.target.value;
            renderShopPage(); // Re-render grid on search
        }

        function handleCategoryChange(e) {
            state.selectedCategory = e.target.value;
            renderShopPage(); // Re-render grid on category change
        }

        // --- Attach Listeners ---
        if(dom.searchInput) dom.searchInput.addEventListener('input', handleSearchInput);
        if(dom.categorySelect) dom.categorySelect.addEventListener('change', handleCategoryChange);

        // --- Initial Render ---
        renderShopPage();

        // --- Activate Lucide Icons ---
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        } else {
            console.warn("Lucide icons library not found.");
        }

    }); // End Document Ready
})(jQuery); // End IIFE