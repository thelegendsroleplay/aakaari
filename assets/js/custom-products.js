document.addEventListener('DOMContentLoaded', () => {
    // --- Get DOM Elements ---
    const container = document.getElementById('products-list-container');
    if (!container) return; // Exit if we're not on the right page

    const searchInput = document.getElementById('product-search');
    const categorySelect = document.getElementById('product-category');
    const sortSelect = document.getElementById('product-sort');
    const gridBtn = document.getElementById('view-grid-btn');
    const listBtn = document.getElementById('view-list-btn');
    const productCountEl = document.getElementById('product-count');
    const emptyState = document.getElementById('products-empty-state');
    const clearFiltersBtn = document.getElementById('clear-filters-btn');

    // Get all product items from the DOM
    const allProducts = Array.from(container.querySelectorAll('.product-item'));

    // --- View Switching ---
    gridBtn.addEventListener('click', () => {
        container.classList.add('view-grid');
        container.classList.remove('view-list');
        gridBtn.classList.add('active');
        listBtn.classList.remove('active');
    });

    listBtn.addEventListener('click', () => {
        container.classList.remove('view-grid');
        container.classList.add('view-list');
        listBtn.classList.add('active');
        gridBtn.classList.remove('active');
    });

    // --- Filtering and Sorting Function ---
    function runFiltersAndSort() {
        const searchQuery = searchInput.value.toLowerCase();
        const categoryFilter = categorySelect.value;
        const sortBy = sortSelect.value;

        // 1. Filter Products
        const filteredProducts = allProducts.filter(product => {
            const name = product.dataset.name.toLowerCase();
            const sku = product.dataset.sku.toLowerCase();
            const categories = product.dataset.category.split(','); // 'tshirt,apparel'

            const matchesSearch = name.includes(searchQuery) || sku.includes(searchQuery);
            const matchesCategory = categoryFilter === 'all' || categories.includes(categoryFilter);
            
            // Toggle visibility
            const isMatch = matchesSearch && matchesCategory;
            product.style.display = isMatch ? 'block' : 'none';
            return isMatch;
        });

        // 2. Sort Products
        const sortedProducts = [...filteredProducts].sort((a, b) => {
            const aData = a.dataset;
            const bData = b.dataset;

            switch (sortBy) {
                case 'price-low':
                    return parseFloat(aData.price) - parseFloat(bData.price);
                case 'price-high':
                    return parseFloat(bData.price) - parseFloat(aData.price);
                case 'rating':
                    return parseFloat(bData.rating) - parseFloat(aData.rating);
                case 'name':
                    return aData.name.localeCompare(bData.name);
                default: // 'popular'
                    return parseInt(bData.reviews) - parseInt(aData.reviews);
            }
        });

        // 3. Re-append sorted products to the container
        sortedProducts.forEach(product => {
            container.appendChild(product);
        });
        
        // 4. Update Count and Empty State
        const visibleCount = filteredProducts.length;
        productCountEl.textContent = visibleCount;
        
        if (visibleCount === 0) {
            emptyState.style.display = 'block';
        } else {
            emptyState.style.display = 'none';
        }
    }

    // --- Event Listeners ---
    searchInput.addEventListener('input', runFiltersAndSort);
    categorySelect.addEventListener('change', runFiltersAndSort);
    sortSelect.addEventListener('change', runFiltersAndSort);
    
    clearFiltersBtn.addEventListener('click', () => {
        searchInput.value = '';
        categorySelect.value = 'all';
        sortSelect.value = 'popular';
        runFiltersAndSort();
    });

    // --- Initial Run ---
    runFiltersAndSort();
});