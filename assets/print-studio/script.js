// Wait for the DOM to be fully loaded before running the script
document.addEventListener('DOMContentLoaded', () => {

    // ========================================================================
    // A. MOCK DATA & APP STATE
    // ========================================================================
    
    // This object holds the entire state of the application, similar to React state.
    let appState = {
        editingProduct: null, // If not null, shows the editor. Otherwise, shows the dashboard.
        
        products: [
            {
                id: 'prod-1',
                name: 'Classic Unisex T-Shirt',
                description: 'A comfortable and stylish t-shirt for everyday wear.',
                basePrice: 15.00,
                salePrice: 12.50,
                category: 'T-Shirts',
                woocommerceId: 'wc-101',
                isActive: true,
                colors: ['#000000', '#FFFFFF', '#FF0000'],
                availablePrintTypes: ['print-1'],
                sides: [
                    {
                        id: 'side-1',
                        name: 'Front',
                        imageUrl: 'https://i.imgur.com/2s4P2c5.png', // Example image URL
                        printAreas: [
                            { id: 'area-1', name: 'Center Chest', x: 150, y: 100, width: 200, height: 300, isRestricted: false }
                        ],
                        restrictionAreas: [
                            { id: 'rest-1', name: 'Collar', x: 180, y: 20, width: 140, height: 50, isRestricted: true }
                        ],
                    },
                    {
                        id: 'side-2',
                        name: 'Back',
                        imageUrl: 'https://i.imgur.com/Ph6p2tq.png', // Example image URL
                        printAreas: [
                            { id: 'area-2', name: 'Full Back', x: 100, y: 100, width: 300, height: 400, isRestricted: false }
                        ],
                        restrictionAreas: [],
                    }
                ]
            },
            {
                id: 'prod-2',
                name: 'Premium Hoodie',
                description: 'Heavyweight hoodie for maximum comfort.',
                basePrice: 35.00,
                salePrice: null,
                category: 'Hoodies',
                woocommerceId: 'wc-102',
                isActive: false,
                colors: ['#000000', '#3B82F6'],
                availablePrintTypes: ['print-1', 'print-2'],
                sides: [
                    {
                        id: 'side-3',
                        name: 'Front',
                        imageUrl: '', // No image set
                        printAreas: [
                            { id: 'area-3', name: 'Left Chest', x: 80, y: 120, width: 100, height: 100, isRestricted: false }
                        ],
                        restrictionAreas: [],
                    }
                ]
            }
        ],
        fabrics: [
            {
                id: 'fab-1',
                name: '100% Cotton',
                description: 'Standard soft cotton, 180 GSM.',
                price: 0,
            },
            {
                id: 'fab-2',
                name: 'Premium Tri-Blend',
                description: 'A soft, durable blend of three fabrics.',
                price: 2.50,
            }
        ],
        printTypes: [
            {
                id: 'print-1',
                name: 'DTF (Direct to Film)',
                description: 'Vibrant colors, durable finish. Great for complex graphics.',
                pricingModel: 'per-inch',
                price: 0.15
            },
            {
                id: 'print-2',
                name: 'Embroidery',
                description: 'Stitched design for a premium, textured look.',
                pricingModel: 'fixed',
                price: 8.00
            }
        ],
        categories: [
            { id: 'cat-1', name: 'T-Shirts' },
            { id: 'cat-2', name: 'Hoodies' },
            { id: 'cat-3', name: 'Mugs' }
        ],
        wooCommerceColors: [
            { name: 'Black', hex: '#000000' },
            { name: 'White', hex: '#FFFFFF' },
            { name: 'Red', hex: '#FF0000' },
            { name: 'Blue', hex: '#3B82F6' },
            { name: 'Green', hex: '#22C55E' },
            { name: 'Heather Grey', hex: '#E5E7EB' }
        ]
    };

    // This object will hold temporary state for dialog forms, etc.
    let tempState = {
        fabricForm: { name: '', description: '', price: 0 },
        printTypeForm: { name: '', description: '', pricingModel: 'per-inch', price: 0 },
        editingFabricId: null,
        editingPrintTypeId: null,
    };
    
    // Canvas-specific state
    let canvasState = {
        ctx: null,
        canvas: null,
        loadedImage: null, // To hold the loaded image object
        selectedSideIndex: 0,
        toolMode: 'select', // 'select', 'draw-print', 'draw-restriction'
        interactionMode: 'none', // 'none', 'drawing', 'moving', 'resizing'
        selectedType: null, // 'print' | 'restriction'
        selectedIndex: null, // index in the array
        dragStart: null, // { x, y }
        tempArea: null, // PrintArea
        resizeHandle: null, // 'tl', 'tr', 'bl', 'br', ...
        hoveredHandle: null,
        HANDLE_SIZE: 8,
        CANVAS_WIDTH: 500,
        CANVAS_HEIGHT: 500,
    };
// ========================================================================
    // B. MAIN RENDER FUNCTION & EVENT DISPATCHER
    // ========================================================================

    /**
     * The main entry point. Renders the entire application based on the current appState.
     */
    function renderApp() {
        const appContainer = document.getElementById('app-container');
        if (!appContainer) return;

        if (appState.editingProduct) {
            // Render the Product Editor view
            appContainer.innerHTML = renderEditorView(appState.editingProduct);
            // After rendering, we must initialize the canvas and tab listeners
            initEditorTabs();
            initCanvasEditor();
        } else {
            // Render the main Dashboard view
            appContainer.innerHTML = renderDashboardView();
            // After rendering, initialize the dashboard tab listeners
            initDashboardTabs();
        }

        // After every render, tell Lucide to create icons from `data-lucide` tags
        lucide.createIcons();
    }

    /**
     * Sets up a single, global event listener on the body to handle all clicks.
     * This avoids adding/removing listeners on every render.
     */
    function setupGlobalListeners() {
        document.body.addEventListener('click', (e) => {
            const actionTarget = e.target.closest('[data-action]');
            if (!actionTarget) return;

            const { action, ...data } = actionTarget.dataset;
            
            // Stop form submissions from bubbling
            if (actionTarget.tagName === 'BUTTON' || actionTarget.tagName === 'FORM') {
                e.preventDefault();
            }

            // --- Dashboard & Product List Actions ---
            if (action === 'edit-product') {
                handleEditProduct(data.productId);
            }
            if (action === 'toggle-product-active') {
                // The 'click' event fires before the switch 'checked' state updates,
                // so we read the state from the event target directly.
                handleToggleActive(data.productId, e.target.checked);
            }
            if (action === 'add-product') {
                handleAddNewProduct();
            }

            // --- Fabric Management Actions ---
            if (action === 'open-fabric-dialog') {
                handleOpenFabricDialog(data.fabricId);
            }
            if (action === 'save-fabric') {
                handleSaveFabric();
            }
            if (action === 'delete-fabric') {
                handleDeleteFabric(data.fabricId);
            }
            
            // --- Print Type Management Actions ---
            if (action === 'open-print-dialog') {
                handleOpenPrintDialog(data.printTypeId);
            }
            if (action === 'save-print-type') {
                handleSavePrintType();
            }
            if (action === 'delete-print-type') {
                handleDeletePrintType(data.printTypeId);
            }

            // --- Product Editor Actions ---
            if (action === 'cancel-editor') {
                handleCancelEditor();
            }
            if (action === 'save-product') {
                handleSaveProduct();
            }
            if (action === 'toggle-color') {
                handleToggleColor(data.colorHex);
            }
            if (action === 'toggle-print-type') {
                handleToggleEditorPrintType(data.printTypeId, e.target.checked);
            }
            if (action === 'add-side') {
                handleAddSide();
            }
            if (action === 'remove-side') {
                handleRemoveSide(data.sideIndex);
            }
            if (action === 'select-editor-side') {
                handleSelectEditorSide(parseInt(data.sideIndex, 10));
            }
            
            // --- Canvas Editor Actions ---
            if (action === 'set-canvas-tool') {
                canvasState.toolMode = data.tool;
                // Re-render only the editor's side panel
                document.getElementById('editor-side-panel').innerHTML = renderEditorSidePanel();
                lucide.createIcons();
            }
            if (action === 'duplicate-canvas-area') {
                handleCanvasDuplicate();
            }
            if (action === 'delete-canvas-area') {
                handleCanvasDelete();
            }
            
            // --- Generic Actions ---
            if (action === 'close-dialog') {
                closeDialog();
            }
        });
        
        // Handle input changes for forms (delegated)
        document.body.addEventListener('input', (e) => {
            const { id, value, type, checked } = e.target;
            
            // --- Fabric Dialog Form ---
            if (id === 'fabric-name') tempState.fabricForm.name = value;
            if (id === 'fabric-description') tempState.fabricForm.description = value;
            if (id === 'fabric-price') tempState.fabricForm.price = parseFloat(value) || 0;
            
            // --- Print Type Dialog Form ---
            if (id === 'print-name') tempState.printTypeForm.name = value;
            if (id === 'print-description') tempState.printTypeForm.description = value;
            if (id === 'print-price') tempState.printTypeForm.price = parseFloat(value) || 0;

            // --- Product Editor Basic Info ---
            if (appState.editingProduct) {
                if (id === 'product-name') appState.editingProduct.name = value;
                if (id === 'product-description') appState.editingProduct.description = value;
                if (id === 'product-basePrice') appState.editingProduct.basePrice = parseFloat(value) || 0;
                if (id === 'product-salePrice') appState.editingProduct.salePrice = value ? parseFloat(value) : null;
                if (id === 'product-wooId') appState.editingProduct.woocommerceId = value;
                if (id === 'product-isActive') appState.editingProduct.isActive = checked;
            }
            
            // --- Canvas Editor Inputs ---
            if (appState.editingProduct && canvasState.selectedType && canvasState.selectedIndex !== null) {
                const isPrint = canvasState.selectedType === 'print';
                const side = appState.editingProduct.sides[canvasState.selectedSideIndex];
                const area = isPrint 
                    ? side.printAreas[canvasState.selectedIndex] 
                    : side.restrictionAreas[canvasState.selectedIndex];
                
                if (!area) return;

                if (id === 'canvas-area-name') area.name = value;
                if (id === 'canvas-area-x') area.x = parseInt(value, 10) || 0;
                if (id === 'canvas-area-y') area.y = parseInt(value, 10) || 0;
                if (id === 'canvas-area-width') area.width = Math.max(20, parseInt(value, 10) || 20);
                if (id === 'canvas-area-height') area.height = Math.max(20, parseInt(value, 10) || 20);
                
                // After changing inputs, redraw the canvas
                drawCanvas();
            }
            
            // --- Editor Side Name ---
             if (appState.editingProduct && id === 'editor-side-name') {
                 const side = appState.editingProduct.sides[canvasState.selectedSideIndex];
                 if(side) side.name = value;
             }
        });
        
        // Handle 'change' events for selects and file inputs
        document.body.addEventListener('change', (e) => {
             const { id, value, type, files } = e.target;
             
             // --- Product Editor Category Select ---
             if (appState.editingProduct && id === 'product-category') {
                 appState.editingProduct.category = value;
             }
             
             // --- Print Type Pricing Model ---
             if (id.startsWith('print-pricing-')) {
                 tempState.printTypeForm.pricingModel = value;
                 // Re-render dialog to update labels
                 const dialogContent = document.querySelector('.dialog-content');
                 if(dialogContent) dialogContent.innerHTML = renderPrintTypeDialog(tempState.editingPrintTypeId);
             }

             // --- ** NEW: Handle Side Image Upload ** ---
             if (id === 'editor-side-image' && files && files[0]) {
                 const side = getCurrentSide();
                 if (side) {
                     // Create a local URL for the selected file
                     side.imageUrl = URL.createObjectURL(files[0]);
                     
                     // Invalidate the cached image
                     canvasState.loadedImage = null; 
                     
                     // Redraw the canvas to show the new image
                     drawCanvas();
                     
                     // Update the helper text
                     const helperText = document.getElementById('editor-side-image-helper');
                     if (helperText) {
                         helperText.innerHTML = `New image loaded. Upload another to replace it.`;
                     }
                 }
             }
             // --- ** END NEW BLOCK ** ---
        });
    }
// ========================================================================
    // C. ACTION HANDLERS
    // ========================================================================
    
    // --- Product List Handlers ---
    function handleEditProduct(productId) {
        const product = appState.products.find(p => p.id === productId);
        if (product) {
            // Create a deep copy to edit, so we can cancel
            appState.editingProduct = JSON.parse(JSON.stringify(product));
            canvasState.selectedSideIndex = 0; // Reset side selection
            resetCanvasState();
            renderApp();
        }
    }
    
    function handleAddNewProduct() {
        const newProduct = {
            id: `prod-${Date.now()}`,
            name: 'New Product',
            description: '',
            basePrice: 0,
            salePrice: null,
            category: appState.categories[0]?.name || '',
            woocommerceId: '',
            isActive: false,
            colors: [],
            availablePrintTypes: [],
            sides: []
        };
        // Add to state and immediately open in editor
        appState.products.push(newProduct);
        appState.editingProduct = JSON.parse(JSON.stringify(newProduct)); // Edit copy
        canvasState.selectedSideIndex = 0;
        resetCanvasState();
        renderApp();
    }

    function handleToggleActive(productId, isActive) {
        const product = appState.products.find(p => p.id === productId);
        if (product) {
            product.isActive = isActive;
            // No full re-render needed, but we do it for simplicity.
            // In a more optimized app, you'd just update the DOM for this row.
            renderApp(); 
        }
    }

    // --- Product Editor Handlers ---
    function handleCancelEditor() {
        // If the product was new and isn't saved, remove it
        const originalProduct = appState.products.find(p => p.id === appState.editingProduct.id);
        if (originalProduct && originalProduct.name === 'New Product' && originalProduct.description === '') {
            appState.products = appState.products.filter(p => p.id !== appState.editingProduct.id);
        }
        
        appState.editingProduct = null;
        renderApp();
    }

    function handleSaveProduct() {
        const product = appState.editingProduct;
        if (!product.name || !product.category || product.sides.length === 0 || product.colors.length === 0) {
            alert('Please fill in required fields, add at least one product side, and select at least one color');
            return;
        }

        // Find the product in the main state and update it
        const productIndex = appState.products.findIndex(p => p.id === product.id);
        if (productIndex !== -1) {
            // Save the deep copy back to the main state
            appState.products[productIndex] = JSON.parse(JSON.stringify(product));
        }
        
        appState.editingProduct = null;
        alert('Product saved successfully');
        renderApp();
    }
    
    function handleToggleColor(hex) {
        if (!appState.editingProduct) return;
        
        const colors = appState.editingProduct.colors;
        const index = colors.indexOf(hex);
        
        if (index > -1) {
            colors.splice(index, 1); // Remove color
        } else {
            colors.push(hex); // Add color
        }
        
        // Re-render just the editor view
        const appContainer = document.getElementById('app-container');
        appContainer.innerHTML = renderEditorView(appState.editingProduct);
        initEditorTabs('colors'); // Go back to the colors tab
        initCanvasEditor();
        lucide.createIcons();
    }
    
    function handleToggleEditorPrintType(printTypeId, isChecked) {
        if (!appState.editingProduct) return;
        
        const types = appState.editingProduct.availablePrintTypes;
        const index = types.indexOf(printTypeId);
        
        if (isChecked && index === -1) {
            types.push(printTypeId);
        } else if (!isChecked && index > -1) {
            types.splice(index, 1);
        }
        // No re-render needed, checkbox handles its own state
    }
    
    function handleAddSide() {
        if (!appState.editingProduct) return;
        
        const newSide = {
            id: `side-${Date.now()}`,
            name: 'New Side',
            printAreas: [],
            restrictionAreas: [],
            imageUrl: '', // ** NEW: Added this property **
        };
        appState.editingProduct.sides.push(newSide);
        canvasState.selectedSideIndex = appState.editingProduct.sides.length - 1; // Select new side
        
        // Re-render editor and go to 'sides' tab
        const appContainer = document.getElementById('app-container');
        appContainer.innerHTML = renderEditorView(appState.editingProduct);
        initEditorTabs('sides');
        initCanvasEditor();
        lucide.createIcons();
    }
    
    function handleRemoveSide(sideIndex) {
         if (!appState.editingProduct) return;
         const index = parseInt(sideIndex, 10);
         
         if (confirm(`Are you sure you want to remove side "${appState.editingProduct.sides[index].name}"?`)) {
             appState.editingProduct.sides.splice(index, 1);
             
             if (canvasState.selectedSideIndex >= appState.editingProduct.sides.length) {
                 canvasState.selectedSideIndex = Math.max(0, appState.editingProduct.sides.length - 1);
             }
             
             // Re-render editor and go to 'sides' tab
            const appContainer = document.getElementById('app-container');
            appContainer.innerHTML = renderEditorView(appState.editingProduct);
            initEditorTabs('sides');
            initCanvasEditor();
            lucide.createIcons();
         }
    }
    
    function handleSelectEditorSide(index) {
        canvasState.selectedSideIndex = index;
        resetCanvasState();
        
        // Just re-render the sides tab content
        document.getElementById('sides-tab-content').innerHTML = renderEditorSidesTab(appState.editingProduct);
        initCanvasEditor();
        lucide.createIcons();
    }
    
    // --- Fabric Dialog Handlers ---
    function handleOpenFabricDialog(fabricId = null) {
        if (fabricId) {
            const fabric = appState.fabrics.find(f => f.id === fabricId);
            tempState.fabricForm = { ...fabric };
            tempState.editingFabricId = fabricId;
        } else {
            tempState.fabricForm = { name: '', description: '', price: 0 };
            tempState.editingFabricId = null;
        }
        openDialog(renderFabricDialog(fabricId));
    }

    function handleSaveFabric() {
        const form = tempState.fabricForm;
        if (!form.name || !form.description) {
            alert('Please fill in all required fields');
            return;
        }

        if (tempState.editingFabricId) {
            // Update existing fabric
            const index = appState.fabrics.findIndex(f => f.id === tempState.editingFabricId);
            appState.fabrics[index] = { ...appState.fabrics[index], ...form };
            alert('Fabric updated successfully');
        } else {
            // Create new fabric
            const newFabric = {
                id: `fabric-${Date.now()}`,
                ...form,
            };
            appState.fabrics.push(newFabric);
            alert('Fabric created successfully');
        }

        closeDialog();
        renderApp(); // Re-render dashboard
    }
    
    function handleDeleteFabric(fabricId) {
        if (confirm('Are you sure you want to delete this fabric?')) {
            appState.fabrics = appState.fabrics.filter(f => f.id !== fabricId);
            alert('Fabric deleted successfully');
            renderApp(); // Re-render dashboard
        }
    }
    
    // --- Print Type Dialog Handlers ---
    function handleOpenPrintDialog(printTypeId = null) {
        if (printTypeId) {
            const printType = appState.printTypes.find(p => p.id === printTypeId);
            tempState.printTypeForm = { ...printType };
            tempState.editingPrintTypeId = printTypeId;
        } else {
            tempState.printTypeForm = { name: '', description: '', pricingModel: 'per-inch', price: 0 };
            tempState.editingPrintTypeId = null;
        }
        openDialog(renderPrintTypeDialog(printTypeId));
    }

    function handleSavePrintType() {
        const form = tempState.printTypeForm;
        if (!form.name || !form.description || form.price === null) {
            alert('Please fill in all required fields');
            return;
        }

        if (tempState.editingPrintTypeId) {
            // Update
            const index = appState.printTypes.findIndex(p => p.id === tempState.editingPrintTypeId);
            appState.printTypes[index] = { ...appState.printTypes[index], ...form };
            alert('Print type updated successfully');
        } else {
            // Create
            const newPrintType = {
                id: `print-${Date.now()}`,
                ...form,
            };
            appState.printTypes.push(newPrintType);
            alert('Print type created successfully');
        }

        closeDialog();
        renderApp();
    }
    
    function handleDeletePrintType(printTypeId) {
        if (confirm('Are you sure you want to delete this print type?')) {
            appState.printTypes = appState.printTypes.filter(p => p.id !== printTypeId);
            alert('Print type deleted successfully');
            renderApp();
        }
    }
// ========================================================================
    // D. VIEW RENDERERS (Return HTML strings)
    // ========================================================================

    /**
     * Renders the main dashboard view with stats and tabs.
     */
    function renderDashboardView() {
        const activeProducts = appState.products.filter(p => p.isActive).length;
        const totalSides = appState.products.reduce((sum, p) => sum + p.sides.length, 0);

        return `
            <div>
                <h1>Admin Dashboard</h1>
                <p class="text-muted-foreground mt-1">Manage your product customization platform</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                ${renderStatCard('Total Products', appState.products.length, `${activeProducts} active`, 'package')}
                ${renderStatCard('Total Sides', totalSides, 'Across all products', 'bar-chart-3')}
                ${renderStatCard('Fabric Options', appState.fabrics.length, 'Available materials', 'palette')}
                ${renderStatCard('Print Types', appState.printTypes.length, 'Printing methods', 'printer')}
            </div>

            <div class="w-full" data-tabs-container>
                <div class="grid w-full grid-cols-3 lg:w-auto border-b">
                    ${renderTabTrigger('products', 'Products', true)}
                    ${renderTabTrigger('fabrics', 'Fabrics')}
                    ${renderTabTrigger('prints', 'Print Types')}
                </div>

                <div class="mt-6">
                    ${renderTabContent('products', renderProductList(), true)}
                    ${renderTabContent('fabrics', renderFabricManagement())}
                    ${renderTabContent('prints', renderPrintTypeManagement())}
                </div>
            </div>
        `;
    }
    
    /**
     * Renders the Product Editor view.
     */
    function renderEditorView(product) {
        return `
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2>Edit Product</h2>
                        <p class="text-muted-foreground mt-1">Configure product settings and customization options</p>
                    </div>
                    <div class="flex gap-2">
                        ${renderButton('Cancel', { variant: 'outline', icon: 'x', extraClass: 'mr-2', data: 'data-action="cancel-editor"' })}
                        ${renderButton('Save Changes', { icon: 'save', extraClass: 'mr-2', data: 'data-action="save-product"' })}
                    </div>
                </div>

                <div class="w-full" data-tabs-container>
                    <div class="grid w-full grid-cols-4 border-b">
                        ${renderTabTrigger('basic', 'Basic Info', true)}
                        ${renderTabTrigger('colors', 'Colors')}
                        ${renderTabTrigger('sides', 'Sides & Print Areas')}
                        ${renderTabTrigger('prints', 'Print Types')}
                    </div>

                    <div class="mt-6 space-y-4">
                        ${renderTabContent('basic', renderEditorBasicInfo(product), true)}
                        ${renderTabContent('colors', renderEditorColors(product))}
                        ${renderTabContent('sides', renderEditorSidesTab(product), false, 'sides-tab-content')}
                        ${renderTabContent('prints', renderEditorPrintTypes(product))}
                    </div>
                </div>
            </div>
        `;
    }

    // ========================================================================
    // E. COMPONENT RENDERERS (Return HTML strings)
    // ========================================================================
    
    // --- Dashboard Components ---

    function renderStatCard(title, value, description, icon) {
        return `
            <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
                <div class="flex flex-row items-center justify-between space-y-0 p-6 pb-2">
                    <h3 class="text-sm font-medium tracking-tight">${title}</h3>
                    <span data-lucide="${icon}" class="h-4 w-4 text-muted-foreground"></span>
                </div>
                <div class="p-6 pt-0">
                    <div class="text-2xl font-bold">${value}</div>
                    <p class="text-xs text-muted-foreground mt-1">${description}</p>
                </div>
            </div>
        `;
    }

    function renderProductList() {
        return `
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2>Products</h2>
                        <p class="text-muted-foreground mt-1">Manage your product catalog</p>
                    </div>
                    ${renderButton('Add Product', { icon: 'plus', extraClass: 'mr-2', data: 'data-action="add-product"' })}
                </div>

                <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
                    <div class="p-6 pt-6">
                        <div class="overflow-x-auto">
                            <table class="w-full caption-bottom text-sm">
                                <thead class="border-b">
                                    <tr class="border-b transition-colors hover:bg-muted/50">
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Product</th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Category</th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Base Price</th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Sides</th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Status</th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="border-0">
                                    ${appState.products.map(product => `
                                        <tr class="border-b transition-colors hover:bg-muted/50">
                                            <td class="p-4 align-middle font-medium">
                                                <div>${product.name}</div>
                                                <div class="text-sm text-muted-foreground">
                                                    ${product.description.substring(0, 50)}...
                                                </div>
                                            </td>
                                            <td class="p-4 align-middle">
                                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-secondary text-secondary-foreground">
                                                    ${product.category}
                                                </span>
                                            </td>
                                            <td class="p-4 align-middle">$${product.basePrice.toFixed(2)}</td>
                                            <td class="p-4 align-middle">
                                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-primary text-primary-foreground">
                                                    ${product.sides.length}
                                                </span>
                                            </td>
                                            <td class="p-4 align-middle">
                                                <div class="flex items-center gap-2">
                                                    <input 
                                                        type="checkbox" 
                                                        class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                                        ${product.isActive ? 'checked' : ''}
                                                        data-action="toggle-product-active"
                                                        data-product-id="${product.id}"
                                                    >
                                                    <span class="text-sm">${product.isActive ? 'Active' : 'Inactive'}</span>
                                                </div>
                                            </td>
                                            <td class="p-4 align-middle">
                                                ${renderButton('Edit', { variant: 'outline', size: 'sm', icon: 'edit', extraClass: 'mr-2', data: `data-action="edit-product" data-product-id="${product.id}"` })}
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                        ${appState.products.length === 0 ? `
                            <div class="text-center py-12">
                                <span data-lucide="package" class="mx-auto h-12 w-12 text-muted-foreground"></span>
                                <h3 class="mt-4 font-semibold">No products found</h3>
                                <p class="mt-2 text-muted-foreground">Get started by adding a new product</p>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    function renderFabricManagement() {
        return `
            <div class="space-y-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h2>Fabric Management</h2>
                        <p class="text-muted-foreground mt-1">Manage fabric types</p>
                    </div>
                    ${renderButton('Add Fabric', { icon: 'plus', extraClass: 'mr-2', data: 'data-action="open-fabric-dialog"' })}
                </div>

                <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
                    <div class="p-6 pt-6">
                        ${appState.fabrics.length > 0 ? `
                            <div class="space-y-4">
                                ${appState.fabrics.map(fabric => `
                                    <div class="flex flex-col sm:flex-row justify-between p-4 border rounded-lg gap-4">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <h3 class="font-semibold">${fabric.name}</h3>
                                                ${fabric.price > 0 ? `
                                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-secondary text-secondary-foreground">
                                                        +$${fabric.price.toFixed(2)}
                                                    </span>
                                                ` : ''}
                                            </div>
                                            <p class="text-sm text-muted-foreground mb-3">${fabric.description}</p>
                                        </div>
                                        <div class="flex sm:flex-col gap-2">
                                            ${renderButton('Edit', { variant: 'outline', size: 'sm', icon: 'edit', extraClass: 'mr-2 flex-1 sm:flex-none', data: `data-action="open-fabric-dialog" data-fabric-id="${fabric.id}"` })}
                                            ${renderButton('Delete', { variant: 'destructive', size: 'sm', icon: 'trash-2', extraClass: 'mr-2 flex-1 sm:flex-none', data: `data-action="delete-fabric" data-fabric-id="${fabric.id}"` })}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        ` : `
                            <div class="text-center py-12">
                                <span data-lucide="palette" class="mx-auto h-12 w-12 text-muted-foreground"></span>
                                <h3 class="mt-4 font-semibold">No fabrics configured</h3>
                                <p class="mt-2 text-muted-foreground">Create your first fabric type to get started</p>
                            </div>
                        `}
                    </div>
                </div>
            </div>
        `;
    }

    function renderPrintTypeManagement() {
         const getPriceDisplay = (printType) => {
            switch (printType.pricingModel) {
              case 'fixed': return `$${printType.price.toFixed(2)}`;
              case 'per-inch': return `$${printType.price.toFixed(2)}/sq in`;
              case 'per-px': return `$${printType.price.toFixed(4)}/px²`;
              default: return `$${printType.price.toFixed(2)}`;
            }
        };
        
        return `
            <div class="space-y-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h2>Print Type Management</h2>
                        <p class="text-muted-foreground mt-1">Manage available printing methods and pricing</p>
                    </div>
                    ${renderButton('Add Print Type', { icon: 'plus', extraClass: 'mr-2', data: 'data-action="open-print-dialog"' })}
                </div>

                <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
                    <div class="p-6 pt-6">
                        ${appState.printTypes.length > 0 ? `
                            <div class="space-y-4">
                                ${appState.printTypes.map(printType => `
                                    <div class="flex flex-col sm:flex-row justify-between p-4 border rounded-lg gap-4">
                                        <div class="flex-1">
                                            <div class="flex items-center flex-wrap gap-2 mb-1">
                                                <h3 class="font-semibold">${printType.name}</h3>
                                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-secondary text-secondary-foreground">
                                                    ${getPriceDisplay(printType)}
                                                </span>
                                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold border-input bg-background">
                                                    ${printType.pricingModel === 'fixed' ? 'Fixed' : printType.pricingModel === 'per-inch' ? 'Per Inch' : 'Per Pixel'}
                                                </span>
                                            </div>
                                            <p class="text-sm text-muted-foreground">${printType.description}</p>
                                        </div>
                                        <div class="flex sm:flex-col gap-2">
                                            ${renderButton('Edit', { variant: 'outline', size: 'sm', icon: 'edit', extraClass: 'mr-2 flex-1 sm:flex-none', data: `data-action="open-print-dialog" data-print-type-id="${printType.id}"` })}
                                            ${renderButton('Delete', { variant: 'destructive', size: 'sm', icon: 'trash-2', extraClass: 'mr-2 flex-1 sm:flex-none', data: `data-action="delete-print-type" data-print-type-id="${printType.id}"` })}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        ` : `
                            <div class="text-center py-12">
                                <span data-lucide="printer" class="mx-auto h-12 w-12 text-muted-foreground"></span>
                                <h3 class="mt-4 font-semibold">No print types configured</h3>
                                <p class="mt-2 text-muted-foreground">Create your first print type to get started</p>
                            </div>
                        `}
                    </div>
                </div>
            </div>
        `;
    }
    
    // --- Editor Tab Components ---

    function renderEditorBasicInfo(product) {
        return `
            <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
                <div class="flex flex-col space-y-1.5 p-6">
                    <h3 class="text-2xl font-semibold leading-none tracking-tight">Product Information</h3>
                    <p class="text-sm text-muted-foreground">Basic details about the product</p>
                </div>
                <div class="p-6 pt-0 space-y-4">
                    ${renderInput('product-name', 'Product Name *', product.name)}
                    ${renderTextarea('product-description', 'Description *', product.description)}
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        ${renderInput('product-basePrice', 'Base Price ($) *', product.basePrice, { type: 'number', step: '0.01' })}
                        ${renderInput('product-salePrice', 'Sale Price ($)', product.salePrice, { type: 'number', step: '0.01', placeholder: 'Optional' })}
                    </div>
                    
                    ${renderSelect('product-category', 'Category (WooCommerce) *', product.category, appState.categories.map(c => ({ value: c.name, label: c.name })))}
                    ${renderInput('product-wooId', 'WooCommerce Product ID', product.woocommerceId, { placeholder: 'e.g., wc-101' })}
                    
                    <div class="flex items-center space-x-2">
                        <input type="checkbox" id="product-isActive" class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                            ${product.isActive ? 'checked' : ''}>
                        <label for="product-isActive" class="text-sm font-medium leading-none cursor-pointer">
                            Active (visible in shop)
                        </label>
                    </div>
                </div>
            </div>
        `;
    }
    
    function renderEditorColors(product) {
        return `
            <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
                <div class="flex flex-col space-y-1.5 p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-2xl font-semibold leading-none tracking-tight">Available Colors</h3>
                            <p class="text-sm text-muted-foreground">Select colors available for this product</p>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-secondary text-secondary-foreground">
                            <span data-lucide="palette" class="h-3 w-3"></span>
                            ${product.colors.length} selected
                        </span>
                    </div>
                </div>
                <div class="p-6 pt-0">
                    ${product.colors.length === 0 ? `
                        <div class="mb-4 p-3 bg-destructive/10 border border-destructive/20 rounded-lg text-sm text-destructive">
                            Please select at least one color for this product
                        </div>
                    ` : ''}
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        ${appState.wooCommerceColors.map(color => {
                            const isChecked = product.colors.includes(color.hex);
                            return `
                                <div 
                                    class="p-3 border-2 rounded-lg cursor-pointer transition-all hover:shadow-md ${isChecked ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/50'}"
                                    data-action="toggle-color"
                                    data-color-hex="${color.hex}"
                                >
                                    <div class="flex items-center gap-3 pointer-events-none">
                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary" ${isChecked ? 'checked' : ''} readonly>
                                        <div class="w-10 h-10 rounded border-2 border-border shadow-sm" style="background-color: ${color.hex}"></div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium truncate">${color.name}</div>
                                            <div class="text-xs text-muted-foreground">${color.hex}</div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            </div>
        `;
    }
    
    function renderEditorSidesTab(product) {
        const currentSide = product.sides[canvasState.selectedSideIndex];
        
        return `
             <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
                <div class="flex flex-col space-y-1.5 p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-2xl font-semibold leading-none tracking-tight">Product Sides & Print Areas</h3>
                            <p class="text-sm text-muted-foreground">Configure printable areas with advanced editing tools</p>
                        </div>
                        ${renderButton('Add Side', { size: 'sm', icon: 'plus', extraClass: 'mr-2', data: 'data-action="add-side"' })}
                    </div>
                </div>
                <div class="p-6 pt-0">
                    ${product.sides.length === 0 ? `
                        <div class="text-center py-8 text-muted-foreground">
                            No sides configured. Add a side to get started.
                        </div>
                    ` : `
                        <div class="space-y-4">
                            <div class="flex flex-wrap gap-2">
                                ${product.sides.map((side, index) => `
                                    <button 
                                        class="shadcn-button h-9 px-3 ${canvasState.selectedSideIndex === index ? 'bg-primary text-primary-foreground' : 'border-input'}"
                                        data-action="select-editor-side"
                                        data-side-index="${index}"
                                    >
                                        ${side.name}
                                        ${side.printAreas.length > 0 ? `
                                            <span class="ml-2 inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-secondary text-secondary-foreground">
                                                ${side.printAreas.length}
                                            </span>
                                        ` : ''}
                                    </button>
                                `).join('')}
                            </div>
                            
                            ${currentSide ? `
                                <div class="space-y-4 pt-4 border-t">
                                    <div class="flex justify-between items-center gap-4">
                                        <div class="space-y-2 flex-1 max-w-md">
                                            <label for="editor-side-name" class="text-sm font-medium">Side Name</label>
                                            <input 
                                                id="editor-side-name"
                                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                value="${currentSide.name}"
                                            >
                                        </div>
                                        ${renderButton('Remove Side', { variant: 'destructive', size: 'sm', icon: 'trash-2', extraClass: 'mr-2', data: `data-action="remove-side" data-side-index="${canvasState.selectedSideIndex}"` })}
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <label for="editor-side-image" class="text-sm font-medium">Side Template Image</label>
                                        <input 
                                            type="file" 
                                            id="editor-side-image"
                                            accept="image/png, image/jpeg, image/webp"
                                            class="flex w-full rounded-md border border-input bg-background text-sm file:mr-4 file:py-2 file:px-4 file:rounded-l-md file:border-0 file:bg-muted file:font-medium"
                                        >
                                        <p class="text-xs text-muted-foreground" id="editor-side-image-helper">
                                            ${currentSide.imageUrl ? `Current image loaded. Upload a new one to replace it.` : `No image. Upload a template to draw on.`}
                                        </p>
                                    </div>
                                    <hr class="border-border" />
                                    
                                    ${renderEnhancedPrintAreaEditor(currentSide)}
                                </div>
                            ` : ''}
                        </div>
                    `}
                </div>
            </div>
        `;
    }
    
    function renderEditorPrintTypes(product) {
         const getPriceDisplay = (printType) => {
            switch (printType.pricingModel) {
              case 'fixed': return `$${printType.price.toFixed(2)}`;
              case 'per-inch': return `$${printType.price.toFixed(2)}/sq in`;
              case 'per-px': return `$${printType.price.toFixed(4)}/px²`;
              default: return `$${printType.price.toFixed(2)}`;
            }
        };
        
        return `
            <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
                <div class="flex flex-col space-y-1.5 p-6">
                    <h3 class="text-2xl font-semibold leading-none tracking-tight">Available Print Types</h3>
                    <p class="text-sm text-muted-foreground">Select which print methods are available for this product</p>
                </div>
                <div class="p-6 pt-0">
                    <div class="space-y-3">
                        ${appState.printTypes.map(printType => {
                            const isChecked = product.availablePrintTypes.includes(printType.id);
                            return `
                                <div class="flex items-start space-x-3 p-4 border rounded-lg">
                                    <input 
                                        type="checkbox" 
                                        id="print-${printType.id}" 
                                        class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary mt-1"
                                        ${isChecked ? 'checked' : ''}
                                        data-action="toggle-print-type"
                                        data-print-type-id="${printType.id}"
                                    >
                                    <div class="flex-1">
                                        <label for="print-${printType.id}" class="cursor-pointer">
                                            <div class="flex flex-wrap items-center justify-between mb-1 gap-2">
                                                <span class="font-medium">${printType.name}</span>
                                                <div class="flex gap-2">
                                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-secondary text-secondary-foreground">
                                                        ${getPriceDisplay(printType)}
                                                    </span>
                                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold border-input bg-background">
                                                        ${printType.pricingModel === 'fixed' ? 'Fixed' : printType.pricingModel === 'per-inch' ? 'Per Inch' : 'Per Pixel'}
                                                    </span>
                                                </div>
                                            </div>
                                            <p class="text-sm text-muted-foreground">${printType.description}</p>
                                        </label>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            </div>
        `;
    }
// --- Canvas Editor Components ---
    
    function renderEnhancedPrintAreaEditor(side) {
        // This component is complex. We render the layout, and the `initCanvasEditor`
        // function will attach all the listeners and drawing logic.
        // The side panel is rendered by `renderEditorSidePanel`.
        return `
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div id="editor-side-panel">
                        ${renderEditorSidePanel()}
                    </div>
                
                    <div class="border rounded-lg overflow-hidden bg-gray-50">
                        <canvas
                            id="print-area-canvas"
                            width="${canvasState.CANVAS_WIDTH}"
                            height="${canvasState.CANVAS_HEIGHT}"
                            class="w-full"
                        ></canvas>
                    </div>
                    
                    <div class="flex items-center gap-4 text-xs text-muted-foreground">
                        <div class="flex items-center gap-1">
                            <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-semibold bg-blue-50 border-blue-500">Print</span>
                            <span>${(side.printAreas || []).length} areas</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-semibold bg-red-50 border-red-500">Restriction</span>
                            <span>${(side.restrictionAreas || []).length} zones</span>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    ${renderCanvasAreaList('print', side.printAreas || [])}
                    
                    ${renderCanvasAreaList('restriction', side.restrictionAreas || [])}
                </div>
            </div>
        `;
    }
    
    function renderEditorSidePanel() {
        // This panel shows the selected area's details
        let selectedArea = null;
        if (appState.editingProduct && canvasState.selectedType && canvasState.selectedIndex !== null) {
            const side = appState.editingProduct.sides[canvasState.selectedSideIndex];
            if (side) {
                selectedArea = canvasState.selectedType === 'print'
                    ? (side.printAreas || [])[canvasState.selectedIndex]
                    : (side.restrictionAreas || [])[canvasState.selectedIndex];
            }
        }
        
        const toolButtonClasses = (tool) => 
            `h-9 px-3 ${canvasState.toolMode === tool ? 'bg-primary text-primary-foreground' : 'border-input'}`;
        const destructiveButtonClasses = (tool) =>
             `h-9 px-3 ${canvasState.toolMode === tool ? 'bg-destructive text-destructive-foreground' : 'border-input'}`;

        return `
            <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
                <div class="flex flex-col space-y-1.5 p-6">
                    <h3 class="text-sm font-semibold leading-none tracking-tight">Drawing Tools</h3>
                </div>
                <div class="p-6 pt-0 space-y-3">
                    <div class="flex flex-wrap gap-2">
                        <button class="shadcn-button ${toolButtonClasses('select')}" data-action="set-canvas-tool" data-tool="select">
                            <span data-lucide="mouse-pointer" class="mr-2 h-4 w-4"></span> Select & Move
                        </button>
                        <button class="shadcn-button ${toolButtonClasses('draw-print')}" data-action="set-canvas-tool" data-tool="draw-print">
                            <span data-lucide="square" class="mr-2 h-4 w-4"></span> Draw Print Area
                        </button>
                        <button class="shadcn-button ${destructiveButtonClasses('draw-restriction')}" data-action="set-canvas-tool" data-tool="draw-restriction">
                            <span data-lucide="square" class="mr-2 h-4 w-4"></span> Draw Restriction
                        </button>
                    </div>

                    ${selectedArea ? `
                        <div class="pt-3 border-t space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium">Selected:</span>
                                <div class="flex gap-1">
                                    ${renderButton('', { variant: 'outline', size: 'sm', icon: 'copy', data: 'data-action="duplicate-canvas-area"' })}
                                    ${renderButton('', { variant: 'destructive', size: 'sm', icon: 'trash-2', data: 'data-action="delete-canvas-area"' })}
                                </div>
                            </div>
                            
                            <div>
                                <label class="text-xs font-medium" for="canvas-area-name">Name</label>
                                <input id="canvas-area-name" class="flex h-8 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value="${selectedArea.name}">
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                ${renderInput('canvas-area-x', 'X', selectedArea.x, { type: 'number', extraClass: 'h-8 text-xs' })}
                                ${renderInput('canvas-area-y', 'Y', selectedArea.y, { type: 'number', extraClass: 'h-8 text-xs' })}
                                ${renderInput('canvas-area-width', 'Width', selectedArea.width, { type: 'number', extraClass: 'h-8 text-xs' })}
                                ${renderInput('canvas-area-height', 'Height', selectedArea.height, { type: 'number', extraClass: 'h-8 text-xs' })}
                            </div>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    function renderCanvasAreaList(type, areas) {
        const isPrint = type === 'print';
        const title = isPrint ? 'Print Areas' : 'Restriction Areas';
        
        return `
            <div class="space-y-2">
                <label class="text-sm font-medium">${title} (${areas.length})</label>
                ${areas.length > 0 ? `
                    <div class="space-y-2 max-h-60 overflow-y-auto pr-2">
                        ${areas.map((area, index) => {
                            const isSelected = canvasState.selectedType === type && canvasState.selectedIndex === index;
                            return `
                                <div
                                    key="${area.id}"
                                    class="p-3 border rounded-lg cursor-pointer transition-colors ${isSelected
                                        ? (isPrint ? 'border-primary bg-primary/10' : 'border-destructive bg-destructive/10')
                                        : (isPrint ? 'hover:bg-accent/50' : 'hover:bg-destructive/5')
                                    }"
                                    data-action="select-canvas-area"
                                    data-type="${type}"
                                    data-index="${index}"
                                >
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium">${area.name}</span>
                                        ${renderButton('', { variant: 'ghost', size: 'sm', icon: 'trash-2', extraClass: 'h-8 w-8 text-muted-foreground hover:text-destructive', data: `data-action="delete-canvas-area" data-type="${type}" data-index="${index}"` })}
                                    </div>
                                    <div class="text-xs text-muted-foreground mt-1">
                                        ${area.width} × ${area.height} px at (${area.x}, ${area.y})
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                ` : `
                    <p class="text-sm text-muted-foreground p-4 border rounded-lg border-dashed">No ${title.toLowerCase()} defined.</p>
                `}
            </div>
        `;
    }

    // --- Dialog Components ---

    function renderFabricDialog(fabricId) {
        const isEditing = !!fabricId;
        const form = tempState.fabricForm;
        
        return `
            <div class="space-y-4 py-4 p-6">
                ${renderInput('fabric-name', 'Fabric Name *', form.name, { placeholder: 'e.g., 100% Cotton' })}
                ${renderTextarea('fabric-description', 'Description *', form.description, { placeholder: 'Describe the fabric material', rows: 3 })}
                ${renderInput('fabric-price', 'Additional Price ($)', form.price, { type: 'number', step: '0.01', placeholder: '0.00', description: 'Extra cost added to base product price (0 for no additional cost)' })}
            </div>

            <div class="flex items-center justify-end gap-2 p-6 pt-0 border-t">
                ${renderButton('Cancel', { variant: 'outline', data: 'data-action="close-dialog"' })}
                ${renderButton(isEditing ? 'Update Fabric' : 'Create Fabric', { data: 'data-action="save-fabric"' })}
            </div>
        `;
    }
    
    function renderPrintTypeDialog(printTypeId) {
        const isEditing = !!printTypeId;
        const form = tempState.printTypeForm;
        
        const getPriceLabel = (model) => {
            switch (model) {
              case 'fixed': return 'Fixed Price ($)';
              case 'per-inch': return 'Price per Square Inch ($)';
              case 'per-px': return 'Price per Square Pixel ($)';
              default: return 'Price ($)';
            }
        };
        
        const getPriceDescription = (model) => {
             switch (model) {
                case 'fixed': return 'Total price for this print method';
                case 'per-inch': return 'Price multiplied by design area in square inches';
                case 'per-px': return 'Price multiplied by design area in square pixels';
                default: return '';
            }
        }

        return `
            <div class="space-y-4 py-4 p-6">
                ${renderInput('print-name', 'Print Method Name *', form.name, { placeholder: 'e.g., DTF (Direct to Film)' })}
                ${renderTextarea('print-description', 'Description *', form.description, { placeholder: 'Describe the print method', rows: 3 })}
                
                <div class="space-y-3">
                    <label class="text-sm font-medium">Pricing Model *</label>
                    <div class="space-y-2">
                        ${renderRadioOption('print-pricing-fixed', 'fixed', 'Fixed Price', 'Same price regardless of design size', form.pricingModel)}
                        ${renderRadioOption('print-pricing-inch', 'per-inch', 'Per Square Inch', 'Price based on design area in square inches', form.pricingModel)}
                        ${renderRadioOption('print-pricing-px', 'per-px', 'Per Square Pixel', 'Price based on design area in pixels', form.pricingModel)}
                    </div>
                </div>
                
                ${renderInput('print-price', getPriceLabel(form.pricingModel), form.price, { type: 'number', step: '0.01', placeholder: '0.00', description: getPriceDescription(form.pricingModel) })}
            </div>

            <div class="flex items-center justify-end gap-2 p-6 pt-0 border-t">
                ${renderButton('Cancel', { variant: 'outline', data: 'data-action="close-dialog"' })}
                ${renderButton(isEditing ? 'Update Print Type' : 'Create Print Type', { data: 'data-action="save-print-type"' })}
            </div>
        `;
    }


    // ========================================================================
    // F. UI & HTML HELPERS
    // ========================================================================

    // --- Tab Helpers ---
    
    function initDashboardTabs() {
        initTabs(document.querySelector('#app-container [data-tabs-container]'));
    }
    
    function initEditorTabs(defaultTab = 'basic') {
        const container = document.querySelector('#app-container [data-tabs-container]');
        initTabs(container);
        
        // Activate the default tab
        const trigger = container.querySelector(`[data-tab-trigger="${defaultTab}"]`);
        const content = container.querySelector(`[data-tab-content="${defaultTab}"]`);
        if (trigger && content) {
            // Deactivate all others
            container.querySelectorAll('[data-tab-trigger]').forEach(t => t.dataset.state = 'inactive');
            container.querySelectorAll('[data-tab-content]').forEach(c => c.dataset.state = 'inactive');
            // Activate target
            trigger.dataset.state = 'active';
            content.dataset.state = 'active';
        }
    }
    
    function initTabs(container) {
        if (!container) return;
        
        const triggers = container.querySelectorAll('[data-tab-trigger]');
        const contents = container.querySelectorAll('[data-tab-content]');
        
        triggers.forEach(trigger => {
            trigger.addEventListener('click', () => {
                const targetId = trigger.dataset.tabTrigger;
                
                triggers.forEach(t => t.dataset.state = 'inactive');
                contents.forEach(c => c.dataset.state = 'inactive');
                
                trigger.dataset.state = 'active';
                container.querySelector(`[data-tab-content="${targetId}"]`).dataset.state = 'active';
            });
        });
    }

    function renderTabTrigger(id, text, isActive = false) {
        return `
            <button
                class="inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm data-[state=inactive]:text-muted-foreground"
                data-tab-trigger="${id}"
                data-state="${isActive ? 'active' : 'inactive'}"
            >
                ${text}
            </button>
        `;
    }
    
    function renderTabContent(id, html, isActive = false, elementId = null) {
        return `
            <div
                class="ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                data-tab-content="${id}"
                data-state="${isActive ? 'active' : 'inactive'}"
                ${elementId ? `id="${elementId}"` : ''}
            >
                ${html}
            </div>
        `;
    }
    
    // --- Dialog Helpers ---
    
    function openDialog(contentHtml) {
        const dialogContainer = document.getElementById('dialog-container');
        const title = tempState.editingFabricId || tempState.editingPrintTypeId ? 'Edit' : 'Create';
        
        dialogContainer.innerHTML = `
            <div class="dialog-overlay" data-action="close-dialog">
                <div class="dialog-content max-w-2xl" onclick="event.stopPropagation()">
                    <div class="flex flex-col space-y-1.5 p-6">
                        <h3 class="text-2xl font-semibold leading-none tracking-tight">${title}</h3>
                    </div>
                    ${contentHtml}
                </div>
            </div>
        `;
        lucide.createIcons();
    }
    
    function closeDialog() {
        const dialogContainer = document.getElementById('dialog-container');
        dialogContainer.innerHTML = '';
        tempState.editingFabricId = null;
        tempState.editingPrintTypeId = null;
    }

    // --- Form Element Helpers ---
    
    function renderButton(text, { variant = 'default', size = 'default', icon = null, extraClass = '', data = '' } = {}) {
        const sizeClasses = {
            default: 'h-10 px-4 py-2',
            sm: 'h-9 rounded-md px-3',
            lg: 'h-11 rounded-md px-8',
        };
        const variantClasses = {
            default: 'bg-primary text-primary-foreground hover:bg-primary/90',
            destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
            outline: 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
            ghost: 'hover:bg-accent hover:text-accent-foreground',
        };
        
        return `
            <button 
                class="shadcn-button ${sizeClasses[size]} ${variantClasses[variant]} ${extraClass}"
                ${data}
            >
                ${icon ? `<span data-lucide="${icon}" class="h-4 w-4"></span>` : ''}
                ${text}
            </button>
        `;
    }
    
    function renderInput(id, label, value, { type = 'text', placeholder = '', step = null, description = null, extraClass = '' } = {}) {
        return `
            <div class="space-y-2">
                <label for="${id}" class="text-sm font-medium leading-none">${label}</label>
                <input
                    type="${type}"
                    id="${id}"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background ${extraClass}"
                    value="${value || ''}"
                    placeholder="${placeholder}"
                    ${type === 'number' && step ? `step="${step}"` : ''}
                >
                ${description ? `<p class="text-xs text-muted-foreground">${description}</p>` : ''}
            </div>
        `;
    }
    
    function renderTextarea(id, label, value, { placeholder = '', rows = 3, description = null } = {}) {
         return `
            <div class="space-y-2">
                <label for="${id}" class="text-sm font-medium leading-none">${label}</label>
                <textarea
                    id="${id}"
                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
                    placeholder="${placeholder}"
                    rows="${rows}"
                >${value || ''}</textarea>
                ${description ? `<p class="text-xs text-muted-foreground">${description}</p>` : ''}
            </div>
        `;
    }
    
    function renderSelect(id, label, value, options) {
        return `
            <div class="space-y-2">
                <label for="${id}" class="text-sm font-medium leading-none">${label}</label>
                <select
                    id="${id}"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
                >
                    <option value="" disabled ${!value ? 'selected' : ''}>Select category</option>
                    ${options.map(opt => `
                        <option value="${opt.value}" ${value === opt.value ? 'selected' : ''}>${opt.label}</option>
                    `).join('')}
                </select>
            </div>
        `;
    }
    
    function renderRadioOption(id, value, label, description, currentValue) {
        return `
            <div class="flex items-start space-x-2 p-3 border rounded-lg">
                <input 
                    type="radio" 
                    id="${id}" 
                    name="pricingModel" 
                    value="${value}"
                    class="h-4 w-4 text-primary border-gray-300 focus:ring-primary mt-1"
                    ${currentValue === value ? 'checked' : ''}
                >
                <div class="flex-1">
                    <label for="${id}" class="cursor-pointer font-medium">
                        <span>${label}</span>
                        <p class="text-sm text-muted-foreground">${description}</p>
                    </label>
                </div>
            </div>
        `;
    }
    

    // ========================================================================
    // G. CANVAS EDITOR LOGIC
    // ========================================================================
    
    function initCanvasEditor() {
        const canvas = document.getElementById('print-area-canvas');
        if (!canvas) return;
        
        canvasState.canvas = canvas;
        canvasState.ctx = canvas.getContext('2d');
        
        // Attach listeners
        canvas.addEventListener('mousedown', handleCanvasMouseDown);
        canvas.addEventListener('mousemove', handleCanvasMouseMove);
        canvas.addEventListener('mouseup', handleCanvasMouseUp);
        canvas.addEventListener('mouseleave', handleCanvasMouseUp);
        
        // Initial draw
        drawCanvas();
    }
    
    function resetCanvasState() {
        canvasState.toolMode = 'select';
        canvasState.interactionMode = 'none';
        canvasState.selectedType = null;
        canvasState.selectedIndex = null;
        canvasState.dragStart = null;
        canvasState.tempArea = null;
        canvasState.resizeHandle = null;
        canvasState.hoveredHandle = null;
        canvasState.loadedImage = null; // Clear cached image
    }
    
    function getCanvasCoordinates(clientX, clientY) {
        if (!canvasState.canvas) return { x: 0, y: 0 };
        
        const rect = canvasState.canvas.getBoundingClientRect();
        const scaleX = canvasState.CANVAS_WIDTH / rect.width;
        const scaleY = canvasState.CANVAS_HEIGHT / rect.height;

        return {
            x: Math.round((clientX - rect.left) * scaleX),
            y: Math.round((clientY - rect.top) * scaleY),
        };
    }
    
    function getCurrentSide() {
        if (!appState.editingProduct) return null;
        return appState.editingProduct.sides[canvasState.selectedSideIndex];
    }
    
    function getResizeHandle(x, y, area) {
        const { HANDLE_SIZE } = canvasState;
        const handles = [
          { name: 'tl', x: area.x, y: area.y },
          { name: 'tr', x: area.x + area.width, y: area.y },
          { name: 'bl', x: area.x, y: area.y + area.height },
          { name: 'br', x: area.x + area.width, y: area.y + area.height },
          { name: 'top', x: area.x + area.width / 2, y: area.y },
          { name: 'right', x: area.x + area.width, y: area.y + area.height / 2 },
          { name: 'bottom', x: area.x + area.width / 2, y: area.y + area.height },
          { name: 'left', x: area.x, y: area.y + area.height / 2 },
        ];

        for (const handle of handles) {
          if (
            x >= handle.x - HANDLE_SIZE && x <= handle.x + HANDLE_SIZE &&
            y >= handle.y - HANDLE_SIZE && y <= handle.y + HANDLE_SIZE
          ) {
            return handle.name;
          }
        }
        return null;
    }
    
    function isInsideArea(x, y, area) {
        return x >= area.x && x <= area.x + area.width && y >= area.y && y <= area.y + area.height;
    }
    
    function setCanvasCursor() {
        if (!canvasState.canvas) return;
        
        let cursor = 'default';
        if (canvasState.toolMode !== 'select') {
            cursor = 'crosshair';
        } else if (canvasState.hoveredHandle) {
             const cursors = {
                tl: 'nwse-resize', tr: 'nesw-resize', bl: 'nesw-resize', br: 'nwse-resize',
                top: 'ns-resize', right: 'ew-resize', bottom: 'ns-resize', left: 'ew-resize',
            };
            cursor = cursors[canvasState.hoveredHandle] || 'default';
        } else if (canvasState.interactionMode === 'moving') {
            cursor = 'move';
        }
        
        canvasState.canvas.style.cursor = cursor;
    }
    
    function handleCanvasMouseDown(e) {
        const coords = getCanvasCoordinates(e.clientX, e.clientY);
        const side = getCurrentSide();
        if (!side) return;

        if (canvasState.toolMode === 'select') {
            // Check for resize handle on selected area
            if (canvasState.selectedType && canvasState.selectedIndex !== null) {
                const areas = canvasState.selectedType === 'print' ? (side.printAreas || []) : (side.restrictionAreas || []);
                const area = areas[canvasState.selectedIndex];
                const handle = getResizeHandle(coords.x, coords.y, area);
                
                if (handle) {
                  canvasState.interactionMode = 'resizing';
                  canvasState.resizeHandle = handle;
                  canvasState.dragStart = coords;
                  return;
                }
            }

            // Check if clicking inside any area
            let found = false;
            // Check print areas first (top layer)
            for (let i = (side.printAreas || []).length - 1; i >= 0; i--) {
                if (isInsideArea(coords.x, coords.y, side.printAreas[i])) {
                    canvasState.selectedType = 'print';
                    canvasState.selectedIndex = i;
                    canvasState.interactionMode = 'moving';
                    canvasState.dragStart = { x: coords.x - side.printAreas[i].x, y: coords.y - side.printAreas[i].y };
                    found = true;
                    break;
                }
            }
            // Check restriction areas
            if (!found) {
                for (let i = (side.restrictionAreas || []).length - 1; i >= 0; i--) {
                    if (isInsideArea(coords.x, coords.y, side.restrictionAreas[i])) {
                        canvasState.selectedType = 'restriction';
                        canvasState.selectedIndex = i;
                        canvasState.interactionMode = 'moving';
                        canvasState.dragStart = { x: coords.x - side.restrictionAreas[i].x, y: coords.y - side.restrictionAreas[i].y };
                        found = true;
                        break;
                    }
                }
            }
            
            if (!found) {
                canvasState.selectedType = null;
                canvasState.selectedIndex = null;
            }
            
            // Re-render side panel to show selection
            document.getElementById('editor-side-panel').innerHTML = renderEditorSidePanel();
            lucide.createIcons();
            
        } else {
            // Drawing mode
            canvasState.interactionMode = 'drawing';
            canvasState.dragStart = coords;
            canvasState.tempArea = {
                id: '', name: '', x: coords.x, y: coords.y, width: 0, height: 0,
                isRestricted: canvasState.toolMode === 'draw-restriction',
            };
        }
        
        drawCanvas();
    }
    
    function handleCanvasMouseMove(e) {
        const coords = getCanvasCoordinates(e.clientX, e.clientY);
        const side = getCurrentSide();
        if (!side) return;

        // Update hovered handle and cursor
        if (canvasState.toolMode === 'select' && canvasState.interactionMode === 'none' && canvasState.selectedType && canvasState.selectedIndex !== null) {
            const areas = canvasState.selectedType === 'print' ? (side.printAreas || []) : (side.restrictionAreas || []);
            const area = areas[canvasState.selectedIndex];
            if (area) {
                canvasState.hoveredHandle = getResizeHandle(coords.x, coords.y, area);
                setCanvasCursor();
            }
        }

        if (!canvasState.dragStart) return;

        if (canvasState.interactionMode === 'drawing' && canvasState.tempArea) {
            const width = coords.x - canvasState.dragStart.x;
            const height = coords.y - canvasState.dragStart.y;
            canvasState.tempArea = {
                ...canvasState.tempArea,
                x: width < 0 ? coords.x : canvasState.dragStart.x,
                y: height < 0 ? coords.y : canvasState.dragStart.y,
                width: Math.abs(width),
                height: Math.abs(height),
            };
        } else if (canvasState.interactionMode === 'moving' && canvasState.selectedType && canvasState.selectedIndex !== null) {
            const areas = canvasState.selectedType === 'print' ? (side.printAreas || []) : (side.restrictionAreas || []);
            const area = areas[canvasState.selectedIndex];
            
            area.x = Math.max(0, Math.min(canvasState.CANVAS_WIDTH - area.width, coords.x - canvasState.dragStart.x));
            area.y = Math.max(0, Math.min(canvasState.CANVAS_HEIGHT - area.height, coords.y - canvasState.dragStart.y));
            
            // Update input fields
            document.getElementById('canvas-area-x').value = area.x;
            document.getElementById('canvas-area-y').value = area.y;

        } else if (canvasState.interactionMode === 'resizing' && canvasState.selectedType && canvasState.selectedIndex !== null && canvasState.resizeHandle) {
            const areas = canvasState.selectedType === 'print' ? (side.printAreas || []) : (side.restrictionAreas || []);
            const area = areas[canvasState.selectedIndex];
            let newArea = { ...area };
            
            const dx = coords.x - canvasState.dragStart.x;
            const dy = coords.y - canvasState.dragStart.y;
            
            // Resize logic from React component
            switch (canvasState.resizeHandle) {
                case 'br':
                  newArea.width = Math.max(20, area.width + dx);
                  newArea.height = Math.max(20, area.height + dy);
                  break;
                case 'bl':
                  newArea.width = Math.max(20, area.width - dx);
                  newArea.height = Math.max(20, area.height + dy);
                  newArea.x = Math.min(area.x + dx, area.x + area.width - 20);
                  break;
                case 'tr':
                  newArea.width = Math.max(20, area.width + dx);
                  newArea.height = Math.max(20, area.height - dy);
                  newArea.y = Math.min(area.y + dy, area.y + area.height - 20);
                  break;
                case 'tl':
                  newArea.width = Math.max(20, area.width - dx);
                  newArea.height = Math.max(20, area.height - dy);
                  newArea.x = Math.min(area.x + dx, area.x + area.width - 20);
                  newArea.y = Math.min(area.y + dy, area.y + area.height - 20);
                  break;
                case 'top':
                  newArea.height = Math.max(20, area.height - dy);
                  newArea.y = Math.min(area.y + dy, area.y + area.height - 20);
                  break;
                case 'bottom':
                  newArea.height = Math.max(20, area.height + dy);
                  break;
                case 'left':
                  newArea.width = Math.max(20, area.width - dx);
                  newArea.x = Math.min(area.x + dx, area.x + area.width - 20);
                  break;
                case 'right':
                  newArea.width = Math.max(20, area.width + dx);
                  break;
            }
            
            // Bounds checking
            newArea.x = Math.max(0, Math.min(canvasState.CANVAS_WIDTH - newArea.width, newArea.x));
            newArea.y = Math.max(0, Math.min(canvasState.CANVAS_HEIGHT - newArea.height, newArea.y));
            
            // Update state
            areas[canvasState.selectedIndex] = newArea;
            canvasState.dragStart = coords; // Reset drag start for continuous resize
            
            // Update input fields
            document.getElementById('canvas-area-x').value = newArea.x;
            document.getElementById('canvas-area-y').value = newArea.y;
            document.getElementById('canvas-area-width').value = newArea.width;
            document.getElementById('canvas-area-height').value = newArea.height;
        }
        
        drawCanvas();
    }
    
    function handleCanvasMouseUp() {
        const side = getCurrentSide();
        if (!side) return;
        
        if (!side.printAreas) side.printAreas = [];
        if (!side.restrictionAreas) side.restrictionAreas = [];
        
        if (canvasState.interactionMode === 'drawing' && canvasState.tempArea && canvasState.tempArea.width >= 20 && canvasState.tempArea.height >= 20) {
            if (canvasState.toolMode === 'draw-print') {
                const newArea = {
                    ...canvasState.tempArea,
                    id: `area-${Date.now()}`,
                    name: `Print Area ${side.printAreas.length + 1}`,
                };
                side.printAreas.push(newArea);
                canvasState.selectedType = 'print';
                canvasState.selectedIndex = side.printAreas.length - 1;
            } else if (canvasState.toolMode === 'draw-restriction') {
                const newArea = {
                    ...canvasState.tempArea,
                    id: `restriction-${Date.now()}`,
                    name: `Restriction ${side.restrictionAreas.length + 1}`,
                    isRestricted: true,
                };
                side.restrictionAreas.push(newArea);
                canvasState.selectedType = 'restriction';
                canvasState.selectedIndex = side.restrictionAreas.length - 1;
            }
            canvasState.toolMode = 'select'; // Switch back to select tool
            
            // Full re-render of sides tab to update lists and panel
            document.getElementById('sides-tab-content').innerHTML = renderEditorSidesTab(appState.editingProduct);
            initCanvasEditor();
            lucide.createIcons();
        }

        canvasState.interactionMode = 'none';
        canvasState.dragStart = null;
        canvasState.tempArea = null;
        canvasState.resizeHandle = null;
        
        drawCanvas();
    }
    
    function handleCanvasDuplicate() {
        const side = getCurrentSide();
        if (!side || canvasState.selectedType === null || canvasState.selectedIndex === null) return;
        
        if (!side.printAreas) side.printAreas = [];
        if (!side.restrictionAreas) side.restrictionAreas = [];
        
        if (canvasState.selectedType === 'print') {
            const area = side.printAreas[canvasState.selectedIndex];
            const newArea = {
                ...area,
                id: `area-${Date.now()}`,
                name: `${area.name} (Copy)`,
                x: area.x + 20,
                y: area.y + 20,
            };
            side.printAreas.push(newArea);
            canvasState.selectedIndex = side.printAreas.length - 1; // Select new
        } else {
            const area = side.restrictionAreas[canvasState.selectedIndex];
             const newArea = {
                ...area,
                id: `restriction-${Date.now()}`,
                name: `${area.name} (Copy)`,
                x: area.x + 20,
                y: area.y + 20,
            };
            side.restrictionAreas.push(newArea);
            canvasState.selectedIndex = side.restrictionAreas.length - 1; // Select new
        }
        
        // Full re-render of sides tab
        document.getElementById('sides-tab-content').innerHTML = renderEditorSidesTab(appState.editingProduct);
        initCanvasEditor();
        lucide.createIcons();
    }
    
    function handleCanvasDelete() {
         const side = getCurrentSide();
         if (!side || canvasState.selectedType === null || canvasState.selectedIndex === null) return;
         
         if (canvasState.selectedType === 'print') {
             (side.printAreas || []).splice(canvasState.selectedIndex, 1);
         } else {
             (side.restrictionAreas || []).splice(canvasState.selectedIndex, 1);
         }
         
         resetCanvasState();
         
        // Full re-render of sides tab
        document.getElementById('sides-tab-content').innerHTML = renderEditorSidesTab(appState.editingProduct);
        initCanvasEditor();
        lucide.createIcons();
    }

    /**
     * The main drawing function for the canvas.
     */
    function drawCanvas() {
        const { ctx, CANVAS_WIDTH, CANVAS_HEIGHT } = canvasState;
        if (!ctx) return;
        
        const side = getCurrentSide();

        // Clear canvas
        ctx.fillStyle = '#F8FAFC'; // slate-50
        ctx.fillRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
        
        // This inner function contains all the drawing logic for overlays (grid, areas, etc.)
        const performDrawing = () => {
            // Draw grid (now semi-transparent)
            ctx.strokeStyle = 'rgba(226, 232, 240, 0.5)'; // slate-200 with 50% opacity
            ctx.lineWidth = 1;
            for (let i = 0; i <= CANVAS_WIDTH; i += 50) { ctx.beginPath(); ctx.moveTo(i, 0); ctx.lineTo(i, CANVAS_HEIGHT); ctx.stroke(); }
            for (let i = 0; i <= CANVAS_HEIGHT; i += 50) { ctx.beginPath(); ctx.moveTo(0, i); ctx.lineTo(CANVAS_WIDTH, i); ctx.stroke(); }

            // Draw restriction areas
            (side.restrictionAreas || []).forEach((area, index) => {
                const isSelected = canvasState.selectedType === 'restriction' && canvasState.selectedIndex === index;
                ctx.fillStyle = isSelected ? 'rgba(239, 68, 68, 0.25)' : 'rgba(239, 68, 68, 0.15)';
                ctx.fillRect(area.x, area.y, area.width, area.height);
                ctx.strokeStyle = isSelected ? '#DC2626' : '#EF4444'; // red-600 / red-500
                ctx.lineWidth = isSelected ? 3 : 2;
                ctx.setLineDash([4, 4]);
                ctx.strokeRect(area.x, area.y, area.width, area.height);
                ctx.setLineDash([]);
                ctx.fillStyle = '#DC2626';
                ctx.font = 'bold 12px Arial';
                ctx.fillText(area.name || `Restriction ${index + 1}`, area.x + 5, area.y + 15);
                if (isSelected) drawResizeHandles(ctx, area);
            });

            // Draw print areas
            (side.printAreas || []).forEach((area, index) => {
                const isSelected = canvasState.selectedType === 'print' && canvasState.selectedIndex === index;
                ctx.fillStyle = isSelected ? 'rgba(37, 99, 235, 0.1)' : 'rgba(37, 99, 235, 0.05)';
                ctx.fillRect(area.x, area.y, area.width, area.height);
                ctx.strokeStyle = isSelected ? '#F97316' : '#2563EB'; // orange-500 / blue-600
                ctx.lineWidth = isSelected ? 3 : 2;
                ctx.setLineDash([8, 4]);
                ctx.strokeRect(area.x, area.y, area.width, area.height);
                ctx.setLineDash([]);
                ctx.fillStyle = isSelected ? '#F97316' : '#2563EB';
                ctx.font = 'bold 12px Arial';
                ctx.fillText(area.name || `Area ${index + 1}`, area.x + 5, area.y + 15);
                if (isSelected) drawResizeHandles(ctx, area);
            });

            // Draw temp area while drawing
            if (canvasState.tempArea) {
                const color = canvasState.toolMode === 'draw-restriction' ? '#EF4444' : '#22C55E';
                ctx.fillStyle = canvasState.toolMode === 'draw-restriction' ? 'rgba(239, 68, 68, 0.2)' : 'rgba(34, 197, 94, 0.2)';
                ctx.fillRect(canvasState.tempArea.x, canvasState.tempArea.y, canvasState.tempArea.width, canvasState.tempArea.height);
                ctx.strokeStyle = color;
                ctx.lineWidth = 2;
                ctx.setLineDash([4, 4]);
                ctx.strokeRect(canvasState.tempArea.x, canvasState.tempArea.y, canvasState.tempArea.width, canvasState.tempArea.height);
                ctx.setLineDash([]);
                ctx.fillStyle = color;
                ctx.font = '12px Arial';
                ctx.fillText(`${canvasState.tempArea.width}×${canvasState.tempArea.height}`, canvasState.tempArea.x + 5, canvasState.tempArea.y - 5);
            }
        };

        // ** NEW: Image Loading Logic **
        // Check if there's a side and if it has an image URL
        if (side && side.imageUrl) {
            
            // Use cached image if available
            if (canvasState.loadedImage && canvasState.loadedImage.src === side.imageUrl) {
                ctx.drawImage(canvasState.loadedImage, 0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
                performDrawing();
            } else {
                // Otherwise, load the new image
                const img = new Image();
                img.onload = () => {
                    canvasState.loadedImage = img; // Cache the image
                    ctx.drawImage(img, 0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
                    performDrawing();
                };
                img.onerror = () => {
                    // If image fails, just draw the grid
                    console.error("Failed to load image:", side.imageUrl);
                    canvasState.loadedImage = null; // Clear cache on error
                    performDrawing();
                };
                img.src = side.imageUrl;
            }
        } else {
            // No image, just draw on the blank bg as before
            performDrawing();
        }
    }
    
    function drawResizeHandles(ctx, area) {
        const { HANDLE_SIZE, hoveredHandle } = canvasState;
        const handles = [
          { name: 'tl', x: area.x, y: area.y },
          { name: 'tr', x: area.x + area.width, y: area.y },
          { name: 'bl', x: area.x, y: area.y + area.height },
          { name: 'br', x: area.x + area.width, y: area.y + area.height },
          { name: 'top', x: area.x + area.width / 2, y: area.y },
          { name: 'right', x: area.x + area.width, y: area.y + area.height / 2 },
          { name: 'bottom', x: area.x + area.width / 2, y: area.y + area.height },
          { name: 'left', x: area.x, y: area.y + area.height / 2 },
        ];

        handles.forEach((handle) => {
          const isHovered = hoveredHandle === handle.name;
          ctx.fillStyle = isHovered ? '#F97316' : '#FFFFFF';
          ctx.strokeStyle = '#2563EB';
          ctx.lineWidth = 2;
          ctx.fillRect(handle.x - HANDLE_SIZE / 2, handle.y - HANDLE_SIZE / 2, HANDLE_SIZE, HANDLE_SIZE);
          ctx.strokeRect(handle.x - HANDLE_SIZE / 2, handle.y - HANDLE_SIZE / 2, HANDLE_SIZE, HANDLE_SIZE);
        });
    }


    // ========================================================================
    // H. INITIALIZE APP
    // ========================================================================
    
    setupGlobalListeners();
    renderApp();

});