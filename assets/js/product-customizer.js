/**
 * Product Customizer JavaScript - Fixed
 * Handles the interactive product customization experience
 */

(function($) {
    'use strict';
    
    // Make sure we have jQuery and required data
    if (typeof $ === 'undefined' || typeof AAKAARI_PRODUCTS === 'undefined') {
        console.error('Required dependencies or data missing for product customizer');
        return;
    }
    
    // Global state
    const state = {
        // Product data
        product: AAKAARI_PRODUCTS[0] || null,
        printTypes: AAKAARI_PRINT_TYPES || [],
        selectedSide: 0,
        selectedColor: null,
        selectedPrintType: null,
        designs: [],
        
        // Canvas
        canvas: null,
        ctx: null,
        canvasScale: 1.0,
        canvasOffset: { x: 0, y: 0 },
        
        // Interaction state
        draggingDesign: null,
        draggingOffset: { x: 0, y: 0 },
        resizingDesign: null,
        resizeStartSize: { width: 0, height: 0 },
        resizeStartPoint: { x: 0, y: 0 },
        
        // Pricing
        basePrice: 0,
        totalPrice: 0,
        printCost: 0
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        console.log('Product customizer initializing...');
        initCustomizer();
    });
    
    function initCustomizer() {
        // Exit if we don't have product data
        if (!state.product) {
            console.error('No product data found');
            $('#customizer-product-name').text('Product Not Found');
            $('#customizer-product-desc').text('This product is not configured for customization.');
            return;
        }
        
        // Initialize UI
        setupProductInfo();
        setupCanvas();
        setupSideSelector();
        setupColorSelector();
        setupPrintTypeSelector();
        setupEventListeners();
        
        // Set initial product state
        updateProductState();
        
        // Initialize Lucide icons if available
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
        
        console.log('Product customizer initialized');
    }
    
    function setupProductInfo() {
        // Set product name and description
        $('#customizer-product-name').text(state.product.name);
        $('#customizer-product-desc').text(state.product.description);
        
        // Set initial pricing
        state.basePrice = state.product.salePrice !== null ? state.product.salePrice : state.product.basePrice;
        $('#product-base-price').text(`$${state.basePrice.toFixed(2)}`);
        
        // Show strikethrough original price if on sale
        if (state.product.salePrice !== null) {
            $('#product-base-price-strikethrough').text(`$${state.product.basePrice.toFixed(2)}`).removeClass('hidden');
        }
        
        // Set initial total price
        $('#total-price').text(`$${state.basePrice.toFixed(2)}`);
    }
    
    function setupCanvas() {
        // Get canvas elements
        state.canvas = document.getElementById('interactive-canvas');
        if (!state.canvas) {
            console.error('Canvas element not found!');
            return;
        }
        
        state.ctx = state.canvas.getContext('2d');
        
        // Initial canvas render
        renderCanvas();
    }
    
    function setupSideSelector() {
        // Get the current side data
        const sides = state.product.sides || [];
        const sideContainer = $('#side-selector-container');
        
        // Hide selector if only one side
        if (sides.length <= 1) {
            $('#side-selector-card').addClass('hidden');
            return;
        }
        
        // Clear side container
        sideContainer.empty();
        
        // Add side buttons
        sides.forEach((side, index) => {
            const isActive = index === state.selectedSide;
            const button = $(`<button class="side-btn ${isActive ? 'active' : ''}" data-side="${index}">${side.name}</button>`);
            sideContainer.append(button);
        });
        
        // Update side info text
        updateSideInfo();
    }
    
    function setupColorSelector() {
        // Get colors for this product
        const colors = state.product.colors || [];
        const colorContainer = $('#color-selector-container');
        
        console.log('Setting up color selector with colors:', colors);
        
        // Hide selector if only one color or no colors
        if (colors.length <= 1) {
            $('#color-selector-card').addClass('hidden');
            console.log('Color selector hidden - only', colors.length, 'color(s)');
            return;
        }
        
        // Show the color selector card
        $('#color-selector-card').removeClass('hidden');
        
        // Clear color container
        colorContainer.empty();
        
        // Add color swatches
        colors.forEach((color, index) => {
            const isSelected = index === 0; // Select first color by default
            const hexColor = color.color || '#FFFFFF';
            const colorName = color.name || hexColor;
            console.log(`Adding color swatch: ${colorName} (${hexColor})`);
            const swatch = $(`<div class="color-swatch ${isSelected ? 'selected' : ''}" data-color="${index}" style="background-color: ${hexColor};" title="${colorName}"></div>`);
            colorContainer.append(swatch);
        });
        
        // Set initial selected color
        state.selectedColor = 0;
        console.log('Color selector setup complete with', colors.length, 'colors');
    }
    
    function setupPrintTypeSelector() {
        // Get print types for this product
        const printTypes = state.printTypes || [];
        const printTypeContainer = $('#print-type-container');
        
        // Clear container
        printTypeContainer.empty();
        
        // Add print type options
        printTypes.forEach((type, index) => {
            const isSelected = index === 0; // Select first type by default
            
            const printTypeHtml = `
                <div class="design-item ${isSelected ? 'selected' : ''}" data-print-type="${type.id}">
                    <div>
                        <div style="font-weight:600;">${type.name}</div>
                        <div style="color:#6B7280; font-size:12px;">${type.description}</div>
                    </div>
                    <div style="font-weight:600;">$${type.price.toFixed(2)}</div>
                </div>
            `;
            
            printTypeContainer.append(printTypeHtml);
        });
        
        // Set initial selected print type
        if (printTypes.length > 0) {
            state.selectedPrintType = printTypes[0].id;
        }
    }
    
    function setupEventListeners() {
        // Side selector
        $('#side-selector-container').on('click', '.side-btn', function() {
            const sideIndex = parseInt($(this).data('side'));
            selectSide(sideIndex);
        });
        
        // Color selector
        $('#color-selector-container').on('click', '.color-swatch', function() {
            const colorIndex = parseInt($(this).data('color'));
            selectColor(colorIndex);
        });
        
        // Print type selector
        $('#print-type-container').on('click', '.design-item', function() {
            const printTypeId = $(this).data('print-type');
            selectPrintType(printTypeId);
        });
        
        // Canvas interactions
        $('#interactive-canvas').on('mousedown', handleCanvasMouseDown);
        $('#interactive-canvas').on('mousemove', handleCanvasMouseMove);
        $('#interactive-canvas').on('mouseup', handleCanvasMouseUp);
        $('#interactive-canvas').on('mouseleave', handleCanvasMouseUp);
        
        // Zoom controls
        $('#zoom-in-btn').on('click', function() {
            updateCanvasZoom(0.1);
        });
        
        $('#zoom-out-btn').on('click', function() {
            updateCanvasZoom(-0.1);
        });
        
        $('#zoom-reset-btn').on('click', function() {
            resetCanvasZoom();
        });
        
        // Tab controls
        $('.tab-trigger').on('click', function() {
            const tab = $(this).data('tab');
            switchTab(tab);
        });
        
        // Add design controls
        $('#add-image-btn').on('click', function() {
            $('#file-upload-input').click();
        });
        
        $('#file-upload-input').on('change', function() {
            handleImageUpload(this.files[0]);
        });
        
        $('#add-text-input').on('input', function() {
            $('#add-text-btn').prop('disabled', $(this).val().trim() === '');
        });
        
        $('#add-text-btn').on('click', function() {
            addTextDesign($('#add-text-input').val().trim());
            $('#add-text-input').val('');
            $(this).prop('disabled', true);
        });
        
        // Back to shop button
        $('#back-to-shop-btn').on('click', function() {
            window.location.href = '/shop/';
        });
        
        // Add to cart
        $('#add-to-cart-btn').on('click', addToCart);
    }
    
    // Helper function for canvas rendering
    function renderCanvas() {
        if (!state.canvas || !state.ctx) return;
        
        const ctx = state.ctx;
        const canvas = state.canvas;
        const sides = state.product.sides || [];
        
        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Get current side
        if (sides.length === 0 || state.selectedSide >= sides.length) {
            // Draw placeholder if no sides
            drawCanvasPlaceholder(ctx, canvas);
            return;
        }
        
        const currentSide = sides[state.selectedSide];
        
        // Draw side background image if available
        if (currentSide.imageUrl) {
            drawSideImage(ctx, currentSide.imageUrl);
        } else {
            // Draw placeholder background
            ctx.fillStyle = '#F3F4F6';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            // Draw product name as placeholder
            ctx.fillStyle = '#9CA3AF';
            ctx.font = '20px Arial';
            ctx.textAlign = 'center';
            ctx.fillText(state.product.name, canvas.width / 2, canvas.height / 2);
        }
        
        // Draw print areas
        if (currentSide.printAreas && currentSide.printAreas.length > 0) {
            currentSide.printAreas.forEach(area => {
                drawPrintArea(ctx, area);
            });
        }
        
        // Draw restriction areas
        if (currentSide.restrictionAreas && currentSide.restrictionAreas.length > 0) {
            currentSide.restrictionAreas.forEach(area => {
                drawRestrictionArea(ctx, area);
            });
        }
        
        // Draw designs for this side
        const sideDesigns = state.designs.filter(design => design.sideIndex === state.selectedSide);
        sideDesigns.forEach(design => {
            drawDesign(ctx, design);
        });
    }
    
    // Helper function to draw a design on canvas
    function drawDesign(ctx, design) {
        // Apply canvas transforms
        ctx.save();
        ctx.translate(design.x, design.y);
        
        if (design.type === 'text') {
            // Draw text design
            ctx.font = `${design.fontSize}px ${design.fontFamily}`;
            ctx.fillStyle = design.color;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(design.text, 0, 0);
            
            // Draw selection box if selected
            if (design.isSelected) {
                const metrics = ctx.measureText(design.text);
                const width = metrics.width;
                const height = design.fontSize;
                
                ctx.strokeStyle = '#3B82F6';
                ctx.lineWidth = 2;
                ctx.strokeRect(-width/2 - 5, -height/2 - 5, width + 10, height + 10);
                
                // Draw resize handles
                drawResizeHandles(ctx, -width/2 - 5, -height/2 - 5, width + 10, height + 10);
            }
        } else if (design.type === 'image') {
            // Draw image design if image is loaded
            if (design.image) {
                const width = design.width;
                const height = design.height;
                
                // Draw the image centered at the design position
                ctx.drawImage(design.image, -width/2, -height/2, width, height);
                
                // Draw selection box if selected
                if (design.isSelected) {
                    ctx.strokeStyle = '#3B82F6';
                    ctx.lineWidth = 2;
                    ctx.strokeRect(-width/2 - 5, -height/2 - 5, width + 10, height + 10);
                    
                    // Draw resize handles
                    drawResizeHandles(ctx, -width/2 - 5, -height/2 - 5, width + 10, height + 10);
                }
            }
        }
        
        ctx.restore();
    }
    
    // Helper function to draw print area
    function drawPrintArea(ctx, area) {
        ctx.save();
        
        // Draw print area rectangle
        ctx.strokeStyle = '#3B82F6';
        ctx.lineWidth = 2;
        ctx.strokeRect(area.x, area.y, area.width, area.height);
        
        // Add label if name exists
        if (area.name) {
            ctx.font = '12px Arial';
            ctx.fillStyle = '#3B82F6';
            ctx.fillText(area.name, area.x + 5, area.y - 5);
        }
        
        ctx.restore();
    }
    
    // Helper function to draw restriction area
    function drawRestrictionArea(ctx, area) {
        ctx.save();
        
        // Draw restriction area rectangle with red color
        ctx.strokeStyle = '#EF4444';
        ctx.lineWidth = 2;
        ctx.setLineDash([5, 5]); // Dashed line
        ctx.strokeRect(area.x, area.y, area.width, area.height);
        
        // Add label if name exists
        if (area.name) {
            ctx.font = '12px Arial';
            ctx.fillStyle = '#EF4444';
            ctx.fillText('No Print: ' + area.name, area.x + 5, area.y - 5);
        }
        
        ctx.restore();
    }
    
    // Helper function to draw resize handles
    function drawResizeHandles(ctx, x, y, width, height) {
        const handleSize = 8;
        const halfHandle = handleSize / 2;
        
        ctx.fillStyle = '#3B82F6';
        
        // Draw 8 resize handles (corners and sides)
        // Top left
        ctx.fillRect(x - halfHandle, y - halfHandle, handleSize, handleSize);
        // Top middle
        ctx.fillRect(x + width/2 - halfHandle, y - halfHandle, handleSize, handleSize);
        // Top right
        ctx.fillRect(x + width - halfHandle, y - halfHandle, handleSize, handleSize);
        // Middle left
        ctx.fillRect(x - halfHandle, y + height/2 - halfHandle, handleSize, handleSize);
        // Middle right
        ctx.fillRect(x + width - halfHandle, y + height/2 - halfHandle, handleSize, handleSize);
        // Bottom left
        ctx.fillRect(x - halfHandle, y + height - halfHandle, handleSize, handleSize);
        // Bottom middle
        ctx.fillRect(x + width/2 - halfHandle, y + height - halfHandle, handleSize, handleSize);
        // Bottom right
        ctx.fillRect(x + width - halfHandle, y + height - halfHandle, handleSize, handleSize);
    }
    
    // Helper function to draw a placeholder on canvas
    function drawCanvasPlaceholder(ctx, canvas) {
        // Fill canvas with light gray
        ctx.fillStyle = '#F3F4F6';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // Draw text
        ctx.fillStyle = '#9CA3AF';
        ctx.font = '20px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('No product sides available', canvas.width / 2, canvas.height / 2);
    }
    
    // Helper function to draw side image
    function drawSideImage(ctx, imageUrl) {
        // Create new image object
        const img = new Image();
        
        // Set crossorigin if needed
        if (imageUrl.indexOf('http') === 0) {
            img.crossOrigin = 'Anonymous';
        }
        
        // Draw image when loaded
        img.onload = function() {
            // Clear canvas
            ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
            
            // Calculate aspect ratio to fit in canvas
            const canvasRatio = ctx.canvas.width / ctx.canvas.height;
            const imgRatio = img.width / img.height;
            
            let drawWidth, drawHeight, offsetX = 0, offsetY = 0;
            
            if (imgRatio > canvasRatio) {
                // Image is wider than canvas
                drawWidth = ctx.canvas.width;
                drawHeight = drawWidth / imgRatio;
                offsetY = (ctx.canvas.height - drawHeight) / 2;
            } else {
                // Image is taller than canvas
                drawHeight = ctx.canvas.height;
                drawWidth = drawHeight * imgRatio;
                offsetX = (ctx.canvas.width - drawWidth) / 2;
            }
            
            // Draw the image centered
            ctx.drawImage(img, offsetX, offsetY, drawWidth, drawHeight);
            
            // Redraw other elements
            const currentSide = state.product.sides[state.selectedSide];
            
            // Draw print areas
            if (currentSide.printAreas && currentSide.printAreas.length > 0) {
                currentSide.printAreas.forEach(area => {
                    drawPrintArea(ctx, area);
                });
            }
            
            // Draw restriction areas
            if (currentSide.restrictionAreas && currentSide.restrictionAreas.length > 0) {
                currentSide.restrictionAreas.forEach(area => {
                    drawRestrictionArea(ctx, area);
                });
            }
            
            // Draw designs for this side
            const sideDesigns = state.designs.filter(design => design.sideIndex === state.selectedSide);
            sideDesigns.forEach(design => {
                drawDesign(ctx, design);
            });
        };
        
        // Set image source to start loading
        img.src = imageUrl;
    }
    
    // Function to select a side
    function selectSide(index) {
        // Validate side index
        if (!state.product.sides || index >= state.product.sides.length || index < 0) {
            console.error('Invalid side index:', index);
            return;
        }
        
        // Update selected side
        state.selectedSide = index;
        
        // Update UI
        $('.side-btn').removeClass('active');
        $(`.side-btn[data-side="${index}"]`).addClass('active');
        
        // Update side info
        updateSideInfo();
        
        // Re-render canvas
        renderCanvas();
    }
    
    // Function to select a color
    function selectColor(index) {
        // Validate color index
        if (!state.product.colors || index >= state.product.colors.length || index < 0) {
            console.error('Invalid color index:', index);
            return;
        }
        
        // Update selected color
        state.selectedColor = index;
        
        // Update UI
        $('.color-swatch').removeClass('selected');
        $(`.color-swatch[data-color="${index}"]`).addClass('selected');
        
        // Get color data
        const color = state.product.colors[index];
        
        // Update side image if color has an image
        if (color.image) {
            const currentSide = state.product.sides[state.selectedSide];
            if (currentSide) {
                // Store original image URL
                if (!currentSide.originalImageUrl) {
                    currentSide.originalImageUrl = currentSide.imageUrl;
                }
                
                // Set color-specific image
                currentSide.imageUrl = color.image;
                
                // Re-render canvas
                renderCanvas();
            }
        } else if (state.product.sides[state.selectedSide]?.originalImageUrl) {
            // Restore original image
            state.product.sides[state.selectedSide].imageUrl = state.product.sides[state.selectedSide].originalImageUrl;
            renderCanvas();
        }
    }
    
    // Function to select a print type
    function selectPrintType(typeId) {
        // Find print type in available types
        const printType = state.printTypes.find(type => type.id === typeId);
        if (!printType) {
            console.error('Invalid print type:', typeId);
            return;
        }
        
        // Update selected print type
        state.selectedPrintType = typeId;
        
        // Update UI
        $('#print-type-container .design-item').removeClass('selected');
        $(`#print-type-container .design-item[data-print-type="${typeId}"]`).addClass('selected');
        
        // Show pricing calculation
        $('#pricing-calculation-card').removeClass('hidden');
        updatePricing();
        
        // Enable add to cart if we have designs
        updateAddToCartState();
    }
    
    // Function to update side info text
    function updateSideInfo() {
        const sides = state.product.sides || [];
        if (sides.length === 0 || state.selectedSide >= sides.length) {
            $('#customizer-side-info').text('No sides available');
            return;
        }
        
        const currentSide = sides[state.selectedSide];
        $('#customizer-side-info').text(`${currentSide.name} (${state.selectedSide + 1} of ${sides.length})`);
    }
    
    // Function to update product state
    function updateProductState() {
        // Count total designs
        const totalDesigns = state.designs.length;
        $('#customizer-total-designs').text(`${totalDesigns} total`);
        
        // Count designs per side
        const sides = state.product.sides || [];
        sides.forEach((side, index) => {
            const sideDesigns = state.designs.filter(design => design.sideIndex === index);
            $(`.side-btn[data-side="${index}"]`).attr('data-designs', sideDesigns.length);
        });
        
        // Update pricing
        updatePricing();
        
        // Update add to cart button state
        updateAddToCartState();
        
        // Show design list placeholder if no designs
        if (totalDesigns === 0) {
            $('#design-list-placeholder').removeClass('hidden');
        } else {
            $('#design-list-placeholder').addClass('hidden');
        }
    }
    
    // Function to update pricing
    function updatePricing() {
        // Calculate print cost based on designs and print type
        let printCost = 0;
        
        // Get selected print type
        const printType = state.printTypes.find(type => type.id === state.selectedPrintType);
        
        if (printType && state.designs.length > 0) {
            if (printType.pricingModel === 'per-inch') {
                // Calculate area of all designs
                let totalArea = 0;
                state.designs.forEach(design => {
                    const area = design.width * design.height / 100; // Convert to square inches
                    totalArea += area;
                });
                
                // Calculate cost based on area
                printCost = totalArea * printType.price;
            } else if (printType.pricingModel === 'fixed') {
                // Fixed price per design
                printCost = state.designs.length * printType.price;
            }
        }
        
        // Update state
        state.printCost = printCost;
        
        // Calculate total price
        state.totalPrice = state.basePrice + printCost;
        
        // Update UI
        $('#print-cost-value').text(`+$${printCost.toFixed(2)}`);
        $('#print-cost-label').text(`Print Cost (${state.designs.length} designs):`);
        $('#print-cost-summary').removeClass('hidden');
        $('#total-price').text(`$${state.totalPrice.toFixed(2)}`);
        
        // Update pricing calculation
        let calculationHtml = '';
        
        if (printType && state.designs.length > 0) {
            calculationHtml += `<div>Base Price: $${state.basePrice.toFixed(2)}</div>`;
            
            if (printType.pricingModel === 'per-inch') {
                // Show area calculation
                let totalArea = 0;
                state.designs.forEach(design => {
                    const area = design.width * design.height / 100;
                    totalArea += area;
                });
                
                calculationHtml += `<div>Total Design Area: ${totalArea.toFixed(2)} sq in</div>`;
                calculationHtml += `<div>Rate: $${printType.price.toFixed(2)} per sq in</div>`;
            } else if (printType.pricingModel === 'fixed') {
                // Show per-design calculation
                calculationHtml += `<div>${state.designs.length} designs × $${printType.price.toFixed(2)} each</div>`;
            }
            
            calculationHtml += `<div>Print Cost: $${printCost.toFixed(2)}</div>`;
            calculationHtml += `<div style="font-weight:600;">Total: $${state.totalPrice.toFixed(2)}</div>`;
        }
        
        $('#pricing-calculation-content').html(calculationHtml);
    }
    
    // Function to update add to cart button state
    function updateAddToCartState() {
        const hasDesigns = state.designs.length > 0;
        const hasPrintType = !!state.selectedPrintType;
        
        if (hasDesigns && hasPrintType) {
            $('#add-to-cart-btn').prop('disabled', false);
            $('#add-to-cart-placeholder').addClass('hidden');
        } else {
            $('#add-to-cart-btn').prop('disabled', true);
            $('#add-to-cart-placeholder').removeClass('hidden');
            
            if (!hasDesigns) {
                $('#add-to-cart-placeholder').text('Add at least one design to continue');
            } else if (!hasPrintType) {
                $('#add-to-cart-placeholder').text('Select a print type to continue');
            }
        }
    }
    
    // Function to switch tabs
    function switchTab(tab) {
        // Hide all tab content
        $('.tab-content').addClass('hidden');
        
        // Show selected tab
        $(`#${tab}-tab-content`).removeClass('hidden');
        
        // Update tab triggers
        $('.tab-trigger').removeClass('active');
        $(`.tab-trigger[data-tab="${tab}"]`).addClass('active');
    }
    
    // Function to update canvas zoom
    function updateCanvasZoom(delta) {
        // Limit zoom range
        const newScale = Math.max(0.5, Math.min(3.0, state.canvasScale + delta));
        
        // Update scale
        state.canvasScale = newScale;
        
        // Update zoom percentage display
        $('#zoom-percentage').text(Math.round(state.canvasScale * 100));
        
        // TODO: Implement actual canvas zooming with CSS transform
    }
    
    // Function to reset canvas zoom
    function resetCanvasZoom() {
        state.canvasScale = 1.0;
        $('#zoom-percentage').text('100');
        
        // TODO: Reset canvas transform
    }
    
    // Function to handle image upload
    function handleImageUpload(file) {
        if (!file) return;
        
        // Create reader to load image
        const reader = new FileReader();
        
        reader.onload = function(event) {
            const img = new Image();
            
            img.onload = function() {
                // Calculate size based on image dimensions
                // Scale to reasonable dimensions for the canvas
                const maxDimension = 200;
                let width = img.width;
                let height = img.height;
                
                if (width > height && width > maxDimension) {
                    height = height * (maxDimension / width);
                    width = maxDimension;
                } else if (height > maxDimension) {
                    width = width * (maxDimension / height);
                    height = maxDimension;
                }
                
                // Create new design
                const design = {
                    id: Date.now().toString(),
                    type: 'image',
                    image: img,
                    src: event.target.result,
                    sideIndex: state.selectedSide,
                    x: state.canvas.width / 2,
                    y: state.canvas.height / 2,
                    width: width,
                    height: height,
                    originalWidth: img.width,
                    originalHeight: img.height,
                    isSelected: true,
                    printType: state.selectedPrintType
                };
                
                // Deselect all other designs
                state.designs.forEach(d => d.isSelected = false);
                
                // Add design to state
                state.designs.push(design);
                
                // Update product state
                updateProductState();
                
                // Render canvas
                renderCanvas();
                
                // Add design to design list
                updateDesignList();
            };
            
            img.src = event.target.result;
        };
        
        reader.readAsDataURL(file);
    }
    
    // Function to add text design
    function addTextDesign(text) {
        if (!text) return;
        
        // Create new design
        const design = {
            id: Date.now().toString(),
            type: 'text',
            text: text,
            sideIndex: state.selectedSide,
            x: state.canvas.width / 2,
            y: state.canvas.height / 2,
            fontSize: 36,
            fontFamily: 'Arial',
            color: '#000000',
            width: text.length * 20, // Rough estimate
            height: 36,
            isSelected: true,
            printType: state.selectedPrintType
        };
        
        // Deselect all other designs
        state.designs.forEach(d => d.isSelected = false);
        
        // Add design to state
        state.designs.push(design);
        
        // Update product state
        updateProductState();
        
        // Render canvas
        renderCanvas();
        
        // Add design to design list
        updateDesignList();
    }
    
    // Function to update design list
    function updateDesignList() {
        const designList = $('#design-list-container');
        designList.empty();
        
        // Filter designs for current side
        const sideDesigns = state.designs.filter(design => design.sideIndex === state.selectedSide);
        
        // Add designs to list
        sideDesigns.forEach(design => {
            let designHtml = `
                <div class="design-item ${design.isSelected ? 'selected' : ''}" data-design-id="${design.id}">
                    <div>
            `;
            
            if (design.type === 'text') {
                designHtml += `<div style="font-weight:600;">${design.text}</div>`;
                designHtml += `<div style="color:#6B7280; font-size:12px;">Text (${design.fontSize}px)</div>`;
            } else if (design.type === 'image') {
                designHtml += `<div style="font-weight:600;">Image</div>`;
                designHtml += `<div style="color:#6B7280; font-size:12px;">${Math.round(design.width)}×${Math.round(design.height)}</div>`;
            }
            
            designHtml += `
                    </div>
                    <div>
                        <button class="side-btn" data-action="delete-design" data-design-id="${design.id}">×</button>
                    </div>
                </div>
            `;
            
            designList.append(designHtml);
        });
        
        // Add event listeners
        $('.design-item').on('click', function(event) {
            // Ignore if delete button was clicked
            if ($(event.target).data('action') === 'delete-design') return;
            
            const designId = $(this).data('design-id');
            selectDesign(designId);
        });
        
        $('[data-action="delete-design"]').on('click', function() {
            const designId = $(this).data('design-id');
            deleteDesign(designId);
        });
    }
    
    // Function to select a design
    function selectDesign(designId) {
        // Deselect all designs
        state.designs.forEach(design => design.isSelected = false);
        
        // Select the design
        const design = state.designs.find(design => design.id === designId);
        if (design) {
            design.isSelected = true;
        }
        
        // Update design list
        $('.design-item').removeClass('selected');
        $(`.design-item[data-design-id="${designId}"]`).addClass('selected');
        
        // Re-render canvas
        renderCanvas();
    }
    
    // Function to delete a design
    function deleteDesign(designId) {
        // Remove design from state
        state.designs = state.designs.filter(design => design.id !== designId);
        
        // Update product state
        updateProductState();
        
        // Re-render canvas
        renderCanvas();
        
        // Update design list
        updateDesignList();
    }
    
    // Canvas mouse event handlers
    function handleCanvasMouseDown(event) {
        const canvas = state.canvas;
        if (!canvas) return;
        
        // Calculate canvas coordinates
        const rect = canvas.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        
        // Check if clicking on a design
        let clickedDesign = null;
        let clickedOffset = { x: 0, y: 0 };
        
        // Reverse loop to check top-most designs first
        for (let i = state.designs.length - 1; i >= 0; i--) {
            const design = state.designs[i];
            if (design.sideIndex !== state.selectedSide) continue;
            
            // Calculate design bounds
            const left = design.x - design.width / 2;
            const top = design.y - design.height / 2;
            const right = design.x + design.width / 2;
            const bottom = design.y + design.height / 2;
            
            if (x >= left && x <= right && y >= top && y <= bottom) {
                clickedDesign = design;
                clickedOffset = { x: x - design.x, y: y - design.y };
                break;
            }
        }
        
        if (clickedDesign) {
            // Start dragging this design
            state.draggingDesign = clickedDesign;
            state.draggingOffset = clickedOffset;
            
            // Select this design
            state.designs.forEach(d => d.isSelected = false);
            clickedDesign.isSelected = true;
            
            // Update design list
            updateDesignList();
            
            // Re-render canvas
            renderCanvas();
        } else {
            // Deselect all designs
            state.designs.forEach(d => d.isSelected = false);
            
            // Update design list
            updateDesignList();
            
            // Re-render canvas
            renderCanvas();
        }
    }
    
    function handleCanvasMouseMove(event) {
        if (!state.draggingDesign) return;
        
        const canvas = state.canvas;
        if (!canvas) return;
        
        // Calculate canvas coordinates
        const rect = canvas.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        
        // Update design position
        state.draggingDesign.x = x - state.draggingOffset.x;
        state.draggingDesign.y = y - state.draggingOffset.y;
        
        // Re-render canvas
        renderCanvas();
    }
    
    function handleCanvasMouseUp() {
        // Stop dragging
        state.draggingDesign = null;
    }
    
    // Function to add to cart
    function addToCart() {
        // Check if we have designs and print type
        if (state.designs.length === 0 || !state.selectedPrintType) {
            alert('Please add at least one design and select a print type before adding to cart.');
            return;
        }
        
        // Show loading state
        const addToCartBtn = $('#add-to-cart-btn');
        const originalText = addToCartBtn.text();
        addToCartBtn.text('Adding...');
        addToCartBtn.prop('disabled', true);
        
        // Prepare designs data (including print type)
        const designsData = state.designs.map(design => {
            const designData = {
                id: design.id,
                type: design.type,
                sideIndex: design.sideIndex,
                x: design.x,
                y: design.y,
                width: design.width,
                height: design.height,
                printType: state.selectedPrintType
            };
            
            if (design.type === 'text') {
                designData.text = design.text;
                designData.fontSize = design.fontSize;
                designData.fontFamily = design.fontFamily;
                designData.color = design.color;
            } else if (design.type === 'image') {
                designData.src = design.src;
            }
            
            return designData;
        });
        
        // Create form data
        const formData = new FormData();
        formData.append('action', 'aakaari_add_to_cart');
        formData.append('security', AAKAARI_SETTINGS.nonce);
        formData.append('product_id', state.product.id);
        formData.append('designs', JSON.stringify(designsData));
        
        // Add any image files from designs
        state.designs.forEach(design => {
            if (design.type === 'image' && design.file) {
                formData.append('files[]', design.file);
            }
        });
        
        // Send AJAX request
        $.ajax({
            url: AAKAARI_SETTINGS.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Redirect to cart
                    if (response.data && response.data.redirect) {
                        window.location.href = response.data.redirect;
                    } else {
                        window.location.href = '/cart/';
                    }
                } else {
                    // Show error
                    alert('Error adding to cart: ' + (response.data ? response.data.message : 'Unknown error'));
                    
                    // Reset button
                    addToCartBtn.text(originalText);
                    addToCartBtn.prop('disabled', false);
                }
            },
            error: function() {
                // Show error
                alert('Error adding to cart. Please try again.');
                
                // Reset button
                addToCartBtn.text(originalText);
                addToCartBtn.prop('disabled', false);
            }
        });
    }
    
    // Debug helper - expose state to window for debugging
    window.customizer = {
        state: state,
        selectSide: selectSide,
        selectColor: selectColor,
        selectPrintType: selectPrintType,
        addTextDesign: addTextDesign,
        renderCanvas: renderCanvas
    };
    
})(jQuery);