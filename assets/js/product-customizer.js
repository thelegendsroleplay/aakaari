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
        selectedFabric: null,
        selectedSize: null,
        selectedPrintType: null,
        designs: [],

        // Canvas
        canvas: null,
        ctx: null,
        canvasScale: 1.0,
        canvasOffset: { x: 0, y: 0 },
        canvasWidth: 0,
        canvasHeight: 0,

        // Print Studio coordinate system (fixed size)
        PRINT_STUDIO_WIDTH: 500,
        PRINT_STUDIO_HEIGHT: 500,

        // Scaling factors for coordinate transformation
        scaleX: 1.0,
        scaleY: 1.0,

        // Interaction state
        draggingDesign: null,
        draggingOffset: { x: 0, y: 0 },
        resizingDesign: null,
        activeHandle: null,
        resizeStartSize: { width: 0, height: 0 },
        resizeStartPoint: { x: 0, y: 0 },
        resizeStartCenter: { x: 0, y: 0 },

        // Pricing
        basePrice: 0,
        totalPrice: 0,
        printCost: 0
    };

    // Touch capability
    const isTouchCapable = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
    state.isTouchCapable = isTouchCapable;
    state.pinch = { active: false, startDistance: 0, baseWidth: 0, baseHeight: 0 };
    
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
        setupFabricSelector();
        setupSizeSelector();
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
        $('#product-base-price').text(`₹${state.basePrice.toFixed(2)}`);
        
        // Show strikethrough original price if on sale
        if (state.product.salePrice !== null) {
            $('#product-base-price-strikethrough').text(`₹${state.product.basePrice.toFixed(2)}`).removeClass('hidden');
        }
        
        // Set initial total price
        $('#total-price').text(`₹${state.basePrice.toFixed(2)}`);
    }
    
    function setupCanvas() {
        // Get canvas elements
        state.canvas = document.getElementById('interactive-canvas');
        if (!state.canvas) {
            console.error('Canvas element not found!');
            return;
        }

        state.ctx = state.canvas.getContext('2d');

        // Store canvas dimensions
        state.canvasWidth = state.canvas.width;
        state.canvasHeight = state.canvas.height;

        // Calculate scaling factors for coordinate transformation
        // Print Studio uses 500x500, we need to scale to actual canvas size
        state.scaleX = state.canvasWidth / state.PRINT_STUDIO_WIDTH;
        state.scaleY = state.canvasHeight / state.PRINT_STUDIO_HEIGHT;

        console.log('Canvas setup complete:');
        console.log('- Canvas size:', state.canvasWidth, 'x', state.canvasHeight);
        console.log('- Print Studio size:', state.PRINT_STUDIO_WIDTH, 'x', state.PRINT_STUDIO_HEIGHT);
        console.log('- Scale factors:', state.scaleX.toFixed(3), 'x', state.scaleY.toFixed(3));

        // Initial canvas render
        renderCanvas();
    }

    /**
     * Transform print area coordinates from Print Studio to Canvas coordinates
     */
    function transformPrintArea(area) {
        if (!area) return null;

        return {
            x: area.x * state.scaleX,
            y: area.y * state.scaleY,
            width: area.width * state.scaleX,
            height: area.height * state.scaleY,
            name: area.name
        };
    }

    /**
     * Get the primary (largest) print area, transformed to canvas coordinates
     */
    function getPrimaryPrintArea() {
        const currentSide = state.product.sides[state.selectedSide];
        if (!currentSide || !currentSide.printAreas || currentSide.printAreas.length === 0) {
            return null;
        }

        // Find largest print area
        let primaryArea = currentSide.printAreas[0];
        let maxSize = primaryArea.width * primaryArea.height;

        currentSide.printAreas.forEach(area => {
            const size = area.width * area.height;
            if (size > maxSize) {
                maxSize = size;
                primaryArea = area;
            }
        });

        // Return transformed coordinates
        return transformPrintArea(primaryArea);
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
    
    function setupFabricSelector() {
        // Get fabrics for this product
        const fabrics = state.product.fabrics || [];
        const fabricContainer = $('#fabric-selector-container');
        
        console.log('Setting up fabric selector with fabrics:', fabrics);
        
        // Hide selector if no fabrics
        if (fabrics.length === 0) {
            $('#fabric-selector-card').addClass('hidden');
            console.log('Fabric selector hidden - no fabrics available');
            return;
        }
        
        // Show the fabric selector card
        $('#fabric-selector-card').removeClass('hidden');
        
        // Clear fabric container
        fabricContainer.empty();
        
        // Add fabric options
        fabrics.forEach((fabric, index) => {
            const isSelected = index === 0; // Select first fabric by default
            const fabricPrice = fabric.price ? ` (+₹${parseFloat(fabric.price).toFixed(2)})` : '';
            const fabricDesc = fabric.description ? `<div style="color:#6B7280; font-size:12px; margin-top:2px;">${fabric.description}</div>` : '';
            
            console.log(`Adding fabric option: ${fabric.name} - Price: ${fabric.price}`);
            
            const fabricHtml = `
                <div class="design-item ${isSelected ? 'selected' : ''}" data-fabric="${index}" style="cursor:pointer; margin-bottom:8px;">
                    <div>
                        <div style="font-weight:600;">${fabric.name}${fabricPrice}</div>
                        ${fabricDesc}
                    </div>
                    <div style="width:20px; height:20px; border:2px solid ${isSelected ? 'var(--primary)' : '#E5E7EB'}; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                        ${isSelected ? '<div style="width:10px; height:10px; background:var(--primary); border-radius:50%;"></div>' : ''}
                    </div>
                </div>
            `;
            
            fabricContainer.append(fabricHtml);
        });
        
        // Set initial selected fabric
        state.selectedFabric = 0;
        console.log('Fabric selector setup complete with', fabrics.length, 'fabrics');
    }
    
    function setupSizeSelector() {
        // Get sizes for this product
        const sizes = state.product.sizes || [];
        const sizeContainer = $('#size-selector-container');
        
        console.log('Setting up size selector with sizes:', sizes);
        
        // Hide selector if no sizes
        if (sizes.length === 0) {
            $('#size-selector-card').addClass('hidden');
            console.log('Size selector hidden - no sizes available');
            return;
        }
        
        // Show the size selector card
        $('#size-selector-card').removeClass('hidden');
        
        // Clear size container
        sizeContainer.empty();
        
        // Add size options
        sizes.forEach((size, index) => {
            const isSelected = index === 0; // Select first size by default
            
            console.log(`Adding size option: ${size.name}`);
            
            const sizeHtml = `
                <div class="design-item ${isSelected ? 'selected' : ''}" data-size="${index}" style="cursor:pointer; margin-bottom:8px;">
                    <div>
                        <div style="font-weight:600;">${size.name}</div>
                    </div>
                    <div style="width:20px; height:20px; border:2px solid ${isSelected ? 'var(--primary)' : '#E5E7EB'}; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                        ${isSelected ? '<div style="width:10px; height:10px; background:var(--primary); border-radius:50%;"></div>' : ''}
                    </div>
                </div>
            `;
            
            sizeContainer.append(sizeHtml);
        });
        
        // Set initial selected size
        state.selectedSize = 0;
        console.log('Size selector setup complete with', sizes.length, 'sizes');
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
                    <div style="font-weight:600;">₹${type.price.toFixed(2)}</div>
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
        
        // Fabric selector
        $('#fabric-selector-container').on('click', '.design-item', function() {
            const fabricIndex = parseInt($(this).data('fabric'));
            selectFabric(fabricIndex);
        });
        
        // Size selector
        $('#size-selector-container').on('click', '.design-item', function() {
            const sizeIndex = parseInt($(this).data('size'));
            selectSize(sizeIndex);
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
        // Desktop wheel-scale (hold Ctrl or Shift)
        $('#interactive-canvas').on('wheel', function(e) {
            const selected = state.designs.find(d => d.isSelected && d.type === 'image' && d.sideIndex === state.selectedSide);
            if (!selected) return;
            if (!e.ctrlKey && !e.shiftKey) return; // require modifier to avoid page scroll conflicts
            e.preventDefault();
            const delta = e.originalEvent ? e.originalEvent.deltaY : e.deltaY;
            const factor = delta > 0 ? 0.9 : 1.1; // zoom out/in
            let newW = Math.max(10, Math.round(selected.width * factor));
            let newH = Math.max(10, Math.round(selected.height * factor));
            const constrained = constrainToPrintArea(selected.x, selected.y, newW, newH);
            selected.x = constrained.x;
            selected.y = constrained.y;
            selected.width = newW;
            selected.height = newH;
            renderCanvas();
        });

        // Touch support
        $('#interactive-canvas').on('touchstart', handleCanvasTouchStart);
        $('#interactive-canvas').on('touchmove', handleCanvasTouchMove);
        $('#interactive-canvas').on('touchend touchcancel', handleCanvasTouchEnd);
        
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
        // Print areas are drawn later in the pipeline to avoid duplicate overlays
        
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
                // Apply rotation and flips
                if (design.rotation) {
                    ctx.rotate((design.rotation * Math.PI) / 180);
                }
                const scaleX = design.flipH ? -1 : 1;
                const scaleY = design.flipV ? -1 : 1;
                if (scaleX !== 1 || scaleY !== 1) {
                    ctx.scale(scaleX, scaleY);
                }
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
        // Transform area to canvas coordinates
        const transformed = transformPrintArea(area);
        if (!transformed) return;

        ctx.save();

        // Draw semi-transparent fill to highlight the print area
        ctx.fillStyle = 'rgba(59, 130, 246, 0.1)';
        ctx.fillRect(transformed.x, transformed.y, transformed.width, transformed.height);

        // Draw print area rectangle with dashed border
        ctx.strokeStyle = '#3B82F6';
        ctx.lineWidth = 2;
        ctx.setLineDash([8, 4]);
        ctx.strokeRect(transformed.x, transformed.y, transformed.width, transformed.height);
        ctx.setLineDash([]);

        // Add label
        const labelText = transformed.name || 'Design Area';
        ctx.font = 'bold 14px Arial';
        ctx.fillStyle = '#3B82F6';
        ctx.textAlign = 'left';
        ctx.fillText('✓ ' + labelText, transformed.x + 10, transformed.y + 25);

        ctx.restore();
    }

    // Helper function to draw restriction area
    function drawRestrictionArea(ctx, area) {
        // Transform area to canvas coordinates
        const transformed = transformPrintArea(area);
        if (!transformed) return;

        ctx.save();

        // Draw semi-transparent red overlay to show restricted area
        ctx.fillStyle = 'rgba(239, 68, 68, 0.15)'; // Light red tint
        ctx.fillRect(transformed.x, transformed.y, transformed.width, transformed.height);

        // Draw restriction area rectangle with red dashed border
        ctx.strokeStyle = '#EF4444';
        ctx.lineWidth = 3;
        ctx.setLineDash([5, 5]); // Dashed line
        ctx.strokeRect(transformed.x, transformed.y, transformed.width, transformed.height);
        ctx.setLineDash([]); // Reset dash

        // Add warning label
        const labelText = transformed.name || 'Restricted Zone';
        ctx.font = 'bold 14px Arial';
        ctx.fillStyle = '#EF4444';
        ctx.textAlign = 'left';
        ctx.fillText('⚠ No Print: ' + labelText, transformed.x + 10, transformed.y + 25);

        // Draw diagonal lines to indicate restriction
        ctx.strokeStyle = 'rgba(239, 68, 68, 0.3)';
        ctx.lineWidth = 1;
        const spacing = 20;
        for (let i = transformed.x; i < transformed.x + transformed.width + transformed.height; i += spacing) {
            ctx.beginPath();
            ctx.moveTo(i, transformed.y);
            ctx.lineTo(i - transformed.height, transformed.y + transformed.height);
            ctx.stroke();
        }

        ctx.restore();
    }
    
    // Helper function to draw resize handles with icons
    function drawResizeHandles(ctx, x, y, width, height) {
        const handleSize = state.isTouchCapable ? 32 : 24; // Much larger for easier interaction
        const padding = 8;
        const boxX = x - padding;
        const boxY = y - padding;
        const boxW = width + padding * 2;
        const boxH = height + padding * 2;
        
        // Draw 8 handles: 4 corners + 4 edges (midpoints)
        const handles = [
            { x: boxX, y: boxY, corner: 'nw', type: 'corner' },
            { x: boxX + boxW / 2 - handleSize / 2, y: boxY, corner: 'n', type: 'edge' },
            { x: boxX + boxW - handleSize, y: boxY, corner: 'ne', type: 'corner' },
            { x: boxX + boxW - handleSize, y: boxY + boxH / 2 - handleSize / 2, corner: 'e', type: 'edge' },
            { x: boxX + boxW - handleSize, y: boxY + boxH - handleSize, corner: 'se', type: 'corner' },
            { x: boxX + boxW / 2 - handleSize / 2, y: boxY + boxH - handleSize, corner: 's', type: 'edge' },
            { x: boxX, y: boxY + boxH - handleSize, corner: 'sw', type: 'corner' },
            { x: boxX, y: boxY + boxH / 2 - handleSize / 2, corner: 'w', type: 'edge' }
        ];
        
        handles.forEach((handle) => {
            ctx.save();
            // Larger, more visible handles with shadow effect
            // Shadow
            ctx.fillStyle = 'rgba(0, 0, 0, 0.2)';
            ctx.fillRect(handle.x + 1, handle.y + 1, handleSize, handleSize);
            // Blue background
            ctx.fillStyle = '#3B82F6';
            ctx.fillRect(handle.x, handle.y, handleSize, handleSize);
            // White border (thicker for visibility)
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 3;
            ctx.strokeRect(handle.x, handle.y, handleSize, handleSize);
            // White icon or dots for edge handles
            ctx.fillStyle = '#ffffff';
            if (handle.type === 'edge') {
                // Draw dots for edge handles
                const dotSize = 3;
                const centerX = handle.x + handleSize / 2;
                const centerY = handle.y + handleSize / 2;
                ctx.beginPath();
                ctx.arc(centerX - dotSize, centerY, dotSize, 0, Math.PI * 2);
                ctx.arc(centerX + dotSize, centerY, dotSize, 0, Math.PI * 2);
                ctx.fill();
            } else {
                // Corner handles with diagonal lines
                ctx.lineWidth = 2;
                ctx.strokeStyle = '#ffffff';
                const margin = handleSize * 0.25;
                if (handle.corner === 'nw') {
                    ctx.beginPath();
                    ctx.moveTo(handle.x + margin, handle.y + margin);
                    ctx.lineTo(handle.x + margin, handle.y + margin + handleSize * 0.3);
                    ctx.moveTo(handle.x + margin, handle.y + margin);
                    ctx.lineTo(handle.x + margin + handleSize * 0.3, handle.y + margin);
                    ctx.stroke();
                } else if (handle.corner === 'ne') {
                    ctx.beginPath();
                    ctx.moveTo(handle.x + handleSize - margin, handle.y + margin);
                    ctx.lineTo(handle.x + handleSize - margin, handle.y + margin + handleSize * 0.3);
                    ctx.moveTo(handle.x + handleSize - margin, handle.y + margin);
                    ctx.lineTo(handle.x + handleSize - margin - handleSize * 0.3, handle.y + margin);
                    ctx.stroke();
                } else if (handle.corner === 'se') {
                    ctx.beginPath();
                    ctx.moveTo(handle.x + handleSize - margin, handle.y + handleSize - margin);
                    ctx.lineTo(handle.x + handleSize - margin, handle.y + handleSize - margin - handleSize * 0.3);
                    ctx.moveTo(handle.x + handleSize - margin, handle.y + handleSize - margin);
                    ctx.lineTo(handle.x + handleSize - margin - handleSize * 0.3, handle.y + handleSize - margin);
                    ctx.stroke();
                } else if (handle.corner === 'sw') {
                    ctx.beginPath();
                    ctx.moveTo(handle.x + margin, handle.y + handleSize - margin);
                    ctx.lineTo(handle.x + margin, handle.y + handleSize - margin - handleSize * 0.3);
                    ctx.moveTo(handle.x + margin, handle.y + handleSize - margin);
                    ctx.lineTo(handle.x + margin + handleSize * 0.3, handle.y + handleSize - margin);
                    ctx.stroke();
                }
            }
            ctx.restore();
        });
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
            
            // Draw print areas (single source of truth)
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
    
    // Function to select a fabric
    function selectFabric(index) {
        // Validate fabric index
        if (!state.product.fabrics || index >= state.product.fabrics.length || index < 0) {
            console.error('Invalid fabric index:', index);
            return;
        }
        
        // Update selected fabric
        state.selectedFabric = index;
        
        // Update UI
        $('#fabric-selector-container .design-item').removeClass('selected');
        const selectedItem = $(`#fabric-selector-container .design-item[data-fabric="${index}"]`);
        selectedItem.addClass('selected');
        
        // Update radio button style
        $('#fabric-selector-container .design-item').each(function() {
            const isSelected = $(this).hasClass('selected');
            $(this).find('> div:last-child').css('border-color', isSelected ? 'var(--primary)' : '#E5E7EB');
            if (isSelected) {
                $(this).find('> div:last-child').html('<div style="width:10px; height:10px; background:var(--primary); border-radius:50%;"></div>');
            } else {
                $(this).find('> div:last-child').html('');
            }
        });
        
        // Update pricing
        updatePricing();
        
        console.log('Selected fabric:', state.product.fabrics[index].name);
    }
    
    // Function to select a size
    function selectSize(index) {
        // Validate size index
        if (!state.product.sizes || index >= state.product.sizes.length || index < 0) {
            console.error('Invalid size index:', index);
            return;
        }
        
        // Update selected size
        state.selectedSize = index;
        
        // Update UI
        $('#size-selector-container .design-item').removeClass('selected');
        const selectedItem = $(`#size-selector-container .design-item[data-size="${index}"]`);
        selectedItem.addClass('selected');
        
        // Update radio button style
        $('#size-selector-container .design-item').each(function() {
            const isSelected = $(this).hasClass('selected');
            $(this).find('> div:last-child').css('border-color', isSelected ? 'var(--primary)' : '#E5E7EB');
            if (isSelected) {
                $(this).find('> div:last-child').html('<div style="width:10px; height:10px; background:var(--primary); border-radius:50%;"></div>');
            } else {
                $(this).find('> div:last-child').html('');
            }
        });
        
        // Update product state to trigger price recalculation
        updateProductState();
        
        console.log('Selected size:', state.product.sizes[index].name);
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
        
        // Get fabric price
        let fabricPrice = 0;
        if (state.product.fabrics && state.selectedFabric !== undefined && state.selectedFabric >= 0) {
            const selectedFabric = state.product.fabrics[state.selectedFabric];
            if (selectedFabric && selectedFabric.price) {
                fabricPrice = parseFloat(selectedFabric.price);
            }
        }
        
        // Update state
        state.printCost = printCost;
        state.fabricPrice = fabricPrice;
        
        // Calculate total price
        state.totalPrice = state.basePrice + printCost + fabricPrice;
        
        // Update UI
        $('#print-cost-value').text(`+₹${printCost.toFixed(2)}`);
        $('#print-cost-label').text(`Print Cost (${state.designs.length} designs):`);
        $('#print-cost-summary').removeClass('hidden');
        $('#total-price').text(`₹${state.totalPrice.toFixed(2)}`);
        
        // Update pricing calculation
        let calculationHtml = '';
        
        if (printType && state.designs.length > 0) {
            calculationHtml += `<div>Base Price: ₹${state.basePrice.toFixed(2)}</div>`;
            
            // Add fabric price if selected
            if (fabricPrice > 0) {
                const selectedFabric = state.product.fabrics[state.selectedFabric];
                calculationHtml += `<div>Fabric (${selectedFabric.name}): +₹${fabricPrice.toFixed(2)}</div>`;
            }
            
            if (printType.pricingModel === 'per-inch') {
                // Show area calculation
                let totalArea = 0;
                state.designs.forEach(design => {
                    const area = design.width * design.height / 100;
                    totalArea += area;
                });
                
                calculationHtml += `<div>Total Design Area: ${totalArea.toFixed(2)} sq in</div>`;
                calculationHtml += `<div>Rate: ₹${printType.price.toFixed(2)} per sq in</div>`;
            } else if (printType.pricingModel === 'fixed') {
                // Show per-design calculation
                calculationHtml += `<div>${state.designs.length} designs × ₹${printType.price.toFixed(2)} each</div>`;
            }
            
            calculationHtml += `<div>Print Cost: ₹${printCost.toFixed(2)}</div>`;
            calculationHtml += `<div style="font-weight:600; margin-top:8px; padding-top:8px; border-top:1px solid #E5E7EB;">Total: ₹${state.totalPrice.toFixed(2)}</div>`;
        }
        
        $('#pricing-calculation-content').html(calculationHtml);
    }
    
    // Function to update add to cart button state
    function updateAddToCartState() {
        const hasDesigns = state.designs.length > 0; // Does the user want customization?
        const hasPrintType = !!state.selectedPrintType; // Has the user selected a print type?

        // The button should be enabled if:
        // 1. The user has NOT added any designs (they want the plain product).
        // OR
        // 2. The user HAS added designs AND has selected a print type.
        if (!hasDesigns || (hasDesigns && hasPrintType)) {
            $('#add-to-cart-btn').prop('disabled', false);
            $('#add-to-cart-placeholder').addClass('hidden');
        } else {
            // This block now only runs if designs are present but no print type is selected.
            $('#add-to-cart-btn').prop('disabled', true);
            $('#add-to-cart-placeholder').removeClass('hidden');
            if (hasDesigns && !hasPrintType) {
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
    // Uploads image IMMEDIATELY to server and stores the attachment ID/URL
    // This avoids sending large files when adding to cart
    function handleImageUpload(file) {
        if (!file) return;

        // Log original file info
        console.log('Uploading original file to server:', {
            name: file.name,
            size: (file.size / 1024 / 1024).toFixed(2) + 'MB',
            type: file.type
        });

        // Show uploading indicator
        const originalText = $('#add-image-btn').text();
        $('#add-image-btn').text('Uploading...').prop('disabled', true);

        // Upload file immediately to WordPress media library
        const formData = new FormData();
        formData.append('action', 'aakaari_upload_design_image');
        formData.append('security', AAKAARI_SETTINGS.nonce || '');
        formData.append('file', file);

        $.ajax({
            url: AAKAARI_SETTINGS.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 120000, // 2 minutes for large uploads
            success: function(response) {
                if (response.success && response.data) {
                    console.log('Image uploaded successfully:', response.data);

                    // Now process the uploaded image
                    processUploadedImage(file, response.data.attachment_id, response.data.url);

                    // Reset button
                    $('#add-image-btn').text(originalText).prop('disabled', false);
                } else {
                    alert('Failed to upload image: ' + (response.data ? response.data.message : 'Unknown error'));
                    $('#add-image-btn').text(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                let errorMsg = 'Failed to upload image. ';

                if (status === 'timeout') {
                    errorMsg += 'Upload timed out. Please try a smaller image or check your connection.';
                } else if (xhr.status === 413) {
                    errorMsg += 'Image file is too large for server.';
                } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMsg += xhr.responseJSON.data.message;
                } else {
                    errorMsg += 'Please try again.';
                }

                console.error('Upload failed:', xhr, status, error);
                alert(errorMsg);

                // Reset button
                $('#add-image-btn').text(originalText).prop('disabled', false);
            }
        });
    }

    // Process uploaded image after server upload completes
    function processUploadedImage(file, attachmentId, attachmentUrl) {
        // Create reader to load image for canvas display
        const reader = new FileReader();

        reader.onload = function(event) {
            const img = new Image();

            img.onload = function() {
                // Log actual image dimensions
                console.log('Image dimensions:', img.width + 'x' + img.height + 'px');
                console.log('Attachment ID:', attachmentId);
                console.log('Attachment URL:', attachmentUrl);

                // Calculate size for CANVAS DISPLAY ONLY
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

                console.log('Canvas preview dimensions:', Math.round(width) + 'x' + Math.round(height) + 'px');

                // Create new design with attachment reference
                const design = {
                    id: Date.now().toString(),
                    type: 'image',
                    image: img,
                    src: event.target.result, // Data URL for canvas display
                    attachmentId: attachmentId, // WordPress attachment ID (original file)
                    attachmentUrl: attachmentUrl, // URL to original file
                    sideIndex: state.selectedSide,
                    x: state.canvas.width / 2,
                    y: state.canvas.height / 2,
                    width: width, // Canvas display width (scaled for UI)
                    height: height, // Canvas display height (scaled for UI)
                    originalWidth: img.width, // Original image width (preserved)
                    originalHeight: img.height, // Original image height (preserved)
                    rotation: 0,
                    flipH: false,
                    flipV: false,
                    lockAspect: true,
                    isSelected: true,
                    printType: state.selectedPrintType
                };

                // Apply print area constraints to initial position (using center coordinates)
                const constrainedPosition = constrainToPrintArea(
                    design.x,
                    design.y,
                    design.width,
                    design.height
                );

                design.x = constrainedPosition.x;
                design.y = constrainedPosition.y;

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

        // Create new design with initial position
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

        // Apply print area constraints to initial position (using center coordinates)
        const constrainedPosition = constrainToPrintArea(
            design.x,
            design.y,
            design.width,
            design.height
        );

        design.x = constrainedPosition.x;
        design.y = constrainedPosition.y;

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

        // Image tools panel for selected image
        const selected = state.designs.find(d => d.isSelected && d.type === 'image' && d.sideIndex === state.selectedSide);
        if (selected) {
            const aspect = selected.height > 0 ? (selected.width / selected.height).toFixed(3) : '1.000';
            const panelHtml = `
                <div id="image-tools-panel" style="margin-top:12px; padding:10px; border:1px solid #E5E7EB; border-radius:6px;">
                    <div style="font-weight:600; margin-bottom:8px;">Image size</div>
                    <div style="display:flex; gap:8px; align-items:center; margin-bottom:8px;">
                        <label style=\"font-size:12px; color:#6B7280;\">W</label>
                        <input id=\"design-width\" type=\"number\" min=\"10\" value=\"${Math.round(selected.width)}\" style=\"width:80px; padding:6px; border:1px solid #E5E7EB; border-radius:4px;\" />
                        <label style=\"font-size:12px; color:#6B7280;\">H</label>
                        <input id=\"design-height\" type=\"number\" min=\"10\" value=\"${Math.round(selected.height)}\" style=\"width:80px; padding:6px; border:1px solid #E5E7EB; border-radius:4px;\" />
                        <label style=\"display:flex; align-items:center; gap:6px; font-size:12px; color:#374151; cursor:pointer;\">
                            <input id=\"lock-aspect\" type=\"checkbox\" checked style=\"cursor:pointer;\" /> Lock aspect (${aspect})
                        </label>
                    </div>
                    <div style=\"display:flex; gap:8px;\">
                        <button type=\"button\" id=\"size-minus\" class=\"button\" style=\"padding:6px 10px;\">-</button>
                        <button type=\"button\" id=\"size-plus\" class=\"button\" style=\"padding:6px 10px;\">+</button>
                    </div>
                    <div style=\"font-weight:600; margin:12px 0 6px;\">Rotate</div>
                    <input id=\"rotate-range\" type=\"range\" min=\"0\" max=\"360\" value=\"${Math.round(selected.rotation || 0)}\" style=\"width:100%; cursor:pointer; accent-color:#3B82F6;\" />
                    <div style=\"display:flex; gap:8px; margin-top:8px;\">
                        <button type=\"button\" id=\"rotate-left\" class=\"button\" style=\"padding:6px 10px;\">⟲ 15°</button>
                        <button type=\"button\" id=\"rotate-right\" class=\"button\" style=\"padding:6px 10px;\">⟳ 15°</button>
                        <button type=\"button\" id=\"rotate-reset\" class=\"button\" style=\"padding:6px 10px;\">Reset</button>
                    </div>
                    <div style=\"font-weight:600; margin:12px 0 6px;\">Flip</div>
                    <div style=\"display:flex; gap:8px;\">
                        <button type=\"button\" id=\"flip-h\" class=\"button\" style=\"padding:6px 10px;\">Flip H</button>
                        <button type=\"button\" id=\"flip-v\" class=\"button\" style=\"padding:6px 10px;\">Flip V</button>
                    </div>
                </div>`;
            designList.append(panelHtml);

            $('#design-width').on('input change', function() {
                const val = Math.max(10, parseInt(this.value, 10) || 10);
                const keepCenter = { x: selected.x, y: selected.y };
                let newW = val;
                let newH = selected.height;
                if ($('#lock-aspect').is(':checked') && selected.height > 0) {
                    const ar = selected.width / selected.height;
                    newH = Math.max(10, Math.round(newW / ar));
                    $('#design-height').val(newH);
                }
                const constrained = constrainToPrintArea(keepCenter.x, keepCenter.y, newW, newH);
                selected.x = constrained.x;
                selected.y = constrained.y;
                selected.width = newW;
                selected.height = newH;
                renderCanvas();
            });

            $('#design-height').on('input change', function() {
                const val = Math.max(10, parseInt(this.value, 10) || 10);
                const keepCenter = { x: selected.x, y: selected.y };
                let newH = val;
                let newW = selected.width;
                if ($('#lock-aspect').is(':checked') && selected.height > 0) {
                    const ar = selected.width / selected.height;
                    newW = Math.max(10, Math.round(newH * ar));
                    $('#design-width').val(newW);
                }
                const constrained = constrainToPrintArea(keepCenter.x, keepCenter.y, newW, newH);
                selected.x = constrained.x;
                selected.y = constrained.y;
                selected.width = newW;
                selected.height = newH;
                renderCanvas();
            });

            $('#size-plus').on('click', function() {
                const inc = Math.round(Math.max(10, selected.width) * 0.1);
                $('#design-width').val(Math.round(selected.width + inc)).trigger('change');
            });
            $('#size-minus').on('click', function() {
                const dec = Math.round(Math.max(10, selected.width) * 0.1);
                $('#design-width').val(Math.max(10, Math.round(selected.width - dec))).trigger('change');
            });

            // Rotation / Flip handlers
            $('#rotate-range').on('input change', function() {
                selected.rotation = parseInt(this.value, 10) || 0;
                renderCanvas();
            });
            $('#rotate-left').on('click', function() {
                selected.rotation = (selected.rotation || 0) - 15;
                if (selected.rotation < 0) selected.rotation += 360;
                $('#rotate-range').val(Math.round(selected.rotation)).trigger('input');
            });
            $('#rotate-right').on('click', function() {
                selected.rotation = (selected.rotation || 0) + 15;
                if (selected.rotation >= 360) selected.rotation -= 360;
                $('#rotate-range').val(Math.round(selected.rotation)).trigger('input');
            });
            $('#rotate-reset').on('click', function() {
                selected.rotation = 0;
                $('#rotate-range').val(0).trigger('input');
            });
            $('#flip-h').on('click', function() {
                selected.flipH = !selected.flipH;
                renderCanvas();
            });
            $('#flip-v').on('click', function() {
                selected.flipV = !selected.flipV;
                renderCanvas();
            });
        }
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
        
        // FIRST: Check for resize handles on ANY design (even if not selected)
        // This makes it much easier to resize without needing to click the design first
        let clickedDesign = null;
        let clickedHandle = null;
        
        // Reverse loop to check top-most designs first
        for (let i = state.designs.length - 1; i >= 0; i--) {
            const design = state.designs[i];
            if (design.sideIndex !== state.selectedSide) continue;
            if (design.type !== 'image') continue; // Only images have resize handles
            
            const handle = getResizeHandleAtPoint(design, x, y);
            if (handle) {
                clickedDesign = design;
                clickedHandle = handle;
                break; // Found a handle, stop searching
            }
        }
        
        // SECOND: If no handle clicked, check if clicking on design body
        if (!clickedDesign) {
            for (let i = state.designs.length - 1; i >= 0; i--) {
                const design = state.designs[i];
                if (design.sideIndex !== state.selectedSide) continue;
                
                // Calculate design bounds
                const left = design.x - design.width / 2;
                const top = design.y - design.height / 2;
                const right = design.x + design.width / 2;
                const bottom = design.y + design.height / 2;
                
                // Check if click is in design bounds but NOT in handle area
                if (x >= left && x <= right && y >= top && y <= bottom) {
                    // Make sure it's not in a handle area (double check)
                    const handleCheck = getResizeHandleAtPoint(design, x, y);
                    if (!handleCheck) {
                        clickedDesign = design;
                        break;
                    }
                }
            }
        }
        
        if (clickedDesign) {
            // Select this design first
            state.designs.forEach(d => d.isSelected = false);
            clickedDesign.isSelected = true;
            
            if (clickedHandle) {
                // Start resizing
                state.resizingDesign = clickedDesign;
                state.activeHandle = clickedHandle;
                state.resizeStartSize = { width: clickedDesign.width, height: clickedDesign.height };
                state.resizeStartPoint = { x, y };
                state.resizeStartCenter = { x: clickedDesign.x, y: clickedDesign.y };
            } else {
                // Start dragging this design
                const clickedOffset = { x: x - clickedDesign.x, y: y - clickedDesign.y };
                state.draggingDesign = clickedDesign;
                state.draggingOffset = clickedOffset;
                state.activeHandle = null;
            }
            
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
    
    /**
     * Constrain design position to stay within print areas
     * This ensures designs cannot be placed outside designated print zones
     * Uses transformed coordinates to match canvas size
     *
     * IMPORTANT: This function expects CENTER-based coordinates (design.x, design.y = center)
     * since that's how designs are positioned throughout the customizer
     */
    function constrainToPrintArea(centerX, centerY, width, height) {
        // Get transformed print area (already scaled to canvas size)
        const printArea = getPrimaryPrintArea();

        if (!printArea) {
            console.warn('No print areas defined for this side. Designs can be placed anywhere.');
            return { x: centerX, y: centerY };
        }

        // Convert center-based coordinates to top-left for boundary checking
        let left = centerX - width / 2;
        let top = centerY - height / 2;

        // Constrain the design to stay within the print area (using top-left coordinates)
        let constrainedLeft = left;
        let constrainedTop = top;

        // Left boundary
        if (constrainedLeft < printArea.x) {
            constrainedLeft = printArea.x;
        }

        // Right boundary
        if (constrainedLeft + width > printArea.x + printArea.width) {
            constrainedLeft = printArea.x + printArea.width - width;
        }

        // Top boundary
        if (constrainedTop < printArea.y) {
            constrainedTop = printArea.y;
        }

        // Bottom boundary
        if (constrainedTop + height > printArea.y + printArea.height) {
            constrainedTop = printArea.y + printArea.height - height;
        }

        // Check if design is too large for print area
        if (width > printArea.width) {
            console.warn('Design is wider than print area. Constraining to left edge.');
            constrainedLeft = printArea.x;
        }

        if (height > printArea.height) {
            console.warn('Design is taller than print area. Constraining to top edge.');
            constrainedTop = printArea.y;
        }

        // Check restriction areas - transform and check each one
        const currentSide = state.product.sides[state.selectedSide];
        const restrictionAreas = currentSide?.restrictionAreas || [];

        restrictionAreas.forEach(area => {
            // Transform restriction area to canvas coordinates
            const restrictionArea = transformPrintArea(area);
            if (!restrictionArea) return;

            // Check if design would overlap with restriction area (using top-left coordinates)
            const designRight = constrainedLeft + width;
            const designBottom = constrainedTop + height;
            const restrictionRight = restrictionArea.x + restrictionArea.width;
            const restrictionBottom = restrictionArea.y + restrictionArea.height;

            // Check for overlap
            const overlapsX = constrainedLeft < restrictionRight && designRight > restrictionArea.x;
            const overlapsY = constrainedTop < restrictionBottom && designBottom > restrictionArea.y;

            if (overlapsX && overlapsY) {
                // Design overlaps with restriction area - push it outside
                console.log('Design overlaps restriction area, adjusting position...');

                // Determine which direction to push the design
                const pushLeft = designRight - restrictionArea.x;
                const pushRight = restrictionRight - constrainedLeft;
                const pushUp = designBottom - restrictionArea.y;
                const pushDown = restrictionBottom - constrainedTop;

                // Find the minimum push distance
                const minPush = Math.min(pushLeft, pushRight, pushUp, pushDown);

                if (minPush === pushLeft && constrainedLeft - pushLeft >= printArea.x) {
                    constrainedLeft -= pushLeft;
                } else if (minPush === pushRight && constrainedLeft + pushRight + width <= printArea.x + printArea.width) {
                    constrainedLeft += pushRight;
                } else if (minPush === pushUp && constrainedTop - pushUp >= printArea.y) {
                    constrainedTop -= pushUp;
                } else if (minPush === pushDown && constrainedTop + pushDown + height <= printArea.y + printArea.height) {
                    constrainedTop += pushDown;
                }
            }
        });

        // Convert back to center-based coordinates for return
        const constrainedCenterX = constrainedLeft + width / 2;
        const constrainedCenterY = constrainedTop + height / 2;

        return { x: constrainedCenterX, y: constrainedCenterY };
    }

    function handleCanvasMouseMove(event) {
        if (!state.draggingDesign && !state.resizingDesign) return;

        const canvas = state.canvas;
        if (!canvas) return;

        // Calculate canvas coordinates
        const rect = canvas.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;

        if (state.resizingDesign && state.activeHandle) {
            const design = state.resizingDesign;
            const handle = state.activeHandle;
            const corner = handle.corner;
            
            // Calculate distance from start point to current mouse position
            const dx = x - state.resizeStartPoint.x;
            const dy = y - state.resizeStartPoint.y;
            
            // Calculate new width/height based on which handle is being dragged
            let deltaW = 0, deltaH = 0;
            
            // Handle edge cases (only resize one dimension)
            if (corner === 'n' || corner === 's') {
                // Top/bottom edge: only change height
                deltaH = corner === 's' ? dy * 2 : -dy * 2;
                deltaW = 0;
            } else if (corner === 'e' || corner === 'w') {
                // Left/right edge: only change width
                deltaW = corner === 'e' ? dx * 2 : -dx * 2;
                deltaH = 0;
            } else {
                // Corner handles: change both dimensions
                if (corner === 'ne' || corner === 'se') {
                    deltaW = dx * 2;
                } else {
                    deltaW = -dx * 2;
                }
                if (corner === 'sw' || corner === 'se') {
                    deltaH = dy * 2;
                } else {
                    deltaH = -dy * 2;
                }
            }
            
            let newWidth = Math.max(10, state.resizeStartSize.width + deltaW);
            let newHeight = Math.max(10, state.resizeStartSize.height + deltaH);
            
            // Keep aspect ratio for images if locked (only for corner handles, not edge handles)
            if (design.type === 'image' && design.lockAspect !== false && handle.type === 'corner') {
                const aspect = state.resizeStartSize.height > 0 ? (state.resizeStartSize.width / state.resizeStartSize.height) : 1;
                // Use the larger change to determine scale
                const scaleW = Math.abs(deltaW) / state.resizeStartSize.width;
                const scaleH = Math.abs(deltaH) / state.resizeStartSize.height;
                const scale = Math.max(scaleW, scaleH);
                const direction = (Math.abs(deltaW) > Math.abs(deltaH)) ? (deltaW > 0 ? 1 : -1) : (deltaH > 0 ? 1 : -1);
                newWidth = Math.max(10, state.resizeStartSize.width * (1 + scale * direction));
                newHeight = Math.max(10, newWidth / aspect);
            }
            
            // Adjust center position based on handle type and position
            let newCenterX = state.resizeStartCenter.x;
            let newCenterY = state.resizeStartCenter.y;
            
            if (corner === 'nw' || corner === 'sw' || corner === 'w') {
                // Left side handles: move center right as width decreases
                newCenterX = state.resizeStartCenter.x + (state.resizeStartSize.width - newWidth) / 2;
            } else if (corner === 'ne' || corner === 'se' || corner === 'e') {
                // Right side handles: move center right as width increases
                newCenterX = state.resizeStartCenter.x + (newWidth - state.resizeStartSize.width) / 2;
            }
            
            if (corner === 'nw' || corner === 'ne' || corner === 'n') {
                // Top handles: move center down as height decreases
                newCenterY = state.resizeStartCenter.y + (state.resizeStartSize.height - newHeight) / 2;
            } else if (corner === 'sw' || corner === 'se' || corner === 's') {
                // Bottom handles: move center down as height increases
                newCenterY = state.resizeStartCenter.y + (newHeight - state.resizeStartSize.height) / 2;
            }

            // Constrain size to print area
            const constrainedCenter = constrainToPrintArea(newCenterX, newCenterY, newWidth, newHeight);
            design.x = constrainedCenter.x;
            design.y = constrainedCenter.y;
            design.width = newWidth;
            design.height = newHeight;
        } else if (state.draggingDesign) {
            // Calculate new position
            let newX = x - state.draggingOffset.x;
            let newY = y - state.draggingOffset.y;

            // Apply print area boundary constraints
            const constrainedPosition = constrainToPrintArea(
                newX,
                newY,
                state.draggingDesign.width,
                state.draggingDesign.height
            );

            // Update design position with constraints applied
            state.draggingDesign.x = constrainedPosition.x;
            state.draggingDesign.y = constrainedPosition.y;
            state.canvas.style.cursor = 'nwse-resize';
        } else if (state.draggingDesign) {
            state.canvas.style.cursor = 'grabbing';
        } else {
            // Hover feedback for handles - show resize cursor when hovering over handles
            let cursor = 'default';
            // Check all designs, not just selected (handles work on all images)
            for (let i = state.designs.length - 1; i >= 0; i--) {
                const design = state.designs[i];
                if (design.sideIndex !== state.selectedSide) continue;
                if (design.type !== 'image') continue;
                
                const handle = getResizeHandleAtPoint(design, x, y);
                if (handle) {
                    // Map handle types to appropriate cursors
                    const cursorMap = {
                        'nw': 'nwse-resize', 'ne': 'nesw-resize', 
                        'sw': 'nesw-resize', 'se': 'nwse-resize',
                        'n': 'ns-resize', 's': 'ns-resize',
                        'e': 'ew-resize', 'w': 'ew-resize'
                    };
                    cursor = cursorMap[handle.corner] || 'nwse-resize';
                    break;
                } else if (design.isSelected) {
                    // Check if hovering over selected design body
                    const left = design.x - design.width / 2;
                    const top = design.y - design.height / 2;
                    const right = design.x + design.width / 2;
                    const bottom = design.y + design.height / 2;
                    if (x >= left && x <= right && y >= top && y <= bottom) {
                        cursor = 'move';
                        break;
                    }
                }
            }
            if (state.canvas) state.canvas.style.cursor = cursor;
        }

        // Re-render canvas
        renderCanvas();
    }
    
    function handleCanvasMouseUp() {
        // Stop interactions
        state.draggingDesign = null;
        state.resizingDesign = null;
        state.activeHandle = null;
        if (state.canvas) state.canvas.style.cursor = 'default';
    }

    // Touch helpers
    function getCanvasPointFromTouch(touch) {
        const rect = state.canvas.getBoundingClientRect();
        return { x: touch.clientX - rect.left, y: touch.clientY - rect.top };
    }

    function distanceBetweenTouches(t1, t2) {
        const dx = t2.clientX - t1.clientX;
        const dy = t2.clientY - t1.clientY;
        return Math.sqrt(dx*dx + dy*dy);
    }

    function handleCanvasTouchStart(e) {
        if (!state.canvas) return;
        if (e.touches.length === 1) {
            const p = getCanvasPointFromTouch(e.touches[0]);
            // Simulate mouse down
            const fakeEvent = { clientX: e.touches[0].clientX, clientY: e.touches[0].clientY };
            handleCanvasMouseDown(fakeEvent);
        } else if (e.touches.length === 2) {
            // Pinch gesture for resizing selected image
            const selected = state.designs.find(d => d.isSelected && d.type === 'image' && d.sideIndex === state.selectedSide);
            if (selected) {
                state.pinch.active = true;
                state.pinch.startDistance = distanceBetweenTouches(e.touches[0], e.touches[1]);
                state.pinch.baseWidth = selected.width;
                state.pinch.baseHeight = selected.height;
            }
        }
        e.preventDefault();
    }

    function handleCanvasTouchMove(e) {
        if (!state.canvas) return;
        if (state.pinch.active && e.touches.length === 2) {
            const selected = state.designs.find(d => d.isSelected && d.type === 'image' && d.sideIndex === state.selectedSide);
            if (selected) {
                const dist = distanceBetweenTouches(e.touches[0], e.touches[1]);
                const scale = Math.max(0.1, dist / (state.pinch.startDistance || dist));
                let newW = Math.max(10, state.pinch.baseWidth * scale);
                let newH = Math.max(10, state.pinch.baseHeight * scale);
                // keep center fixed while constraining
                const constrained = constrainToPrintArea(selected.x, selected.y, newW, newH);
                selected.x = constrained.x;
                selected.y = constrained.y;
                selected.width = newW;
                selected.height = newH;
                renderCanvas();
            }
        } else if (e.touches.length === 1) {
            const fakeEvent = { clientX: e.touches[0].clientX, clientY: e.touches[0].clientY };
            handleCanvasMouseMove(fakeEvent);
        }
        e.preventDefault();
    }

    function handleCanvasTouchEnd(e) {
        if (e.touches.length < 2) {
            state.pinch.active = false;
        }
        handleCanvasMouseUp();
        e.preventDefault();
    }

    // Hit-test resize handles around a design (works even if not selected)
    // Returns handle info or false
    function getResizeHandleAtPoint(design, x, y) {
        const padding = 8;
        const handleSize = state.isTouchCapable ? 32 : 24;
        const hitAreaPadding = 6; // Extra invisible padding for easier clicking
        const left = design.x - design.width / 2 - padding;
        const top = design.y - design.height / 2 - padding;
        const w = design.width + padding * 2;
        const h = design.height + padding * 2;
        
        // 8 handles: 4 corners + 4 edges (matching drawResizeHandles)
        const handles = [
            { x: left, y: top, corner: 'nw', type: 'corner' },
            { x: left + w / 2 - handleSize / 2, y: top, corner: 'n', type: 'edge' },
            { x: left + w - handleSize, y: top, corner: 'ne', type: 'corner' },
            { x: left + w - handleSize, y: top + h / 2 - handleSize / 2, corner: 'e', type: 'edge' },
            { x: left + w - handleSize, y: top + h - handleSize, corner: 'se', type: 'corner' },
            { x: left + w / 2 - handleSize / 2, y: top + h - handleSize, corner: 's', type: 'edge' },
            { x: left, y: top + h - handleSize, corner: 'sw', type: 'corner' },
            { x: left, y: top + h / 2 - handleSize / 2, corner: 'w', type: 'edge' }
        ];
        
        for (let i = 0; i < handles.length; i++) {
            const hd = handles[i];
            // Check with extra hit area padding for easier clicking
            const hitX = hd.x - hitAreaPadding;
            const hitY = hd.y - hitAreaPadding;
            const hitW = handleSize + hitAreaPadding * 2;
            const hitH = handleSize + hitAreaPadding * 2;
            
            if (x >= hitX && x <= hitX + hitW && y >= hitY && y <= hitY + hitH) {
                return { index: i, corner: hd.corner, type: hd.type };
            }
        }
        return false;
    }
    
    // Function to add to cart
    function addToCart() {
        // Customization is now OPTIONAL
        // Only validate print type if user has added designs
        if (state.designs.length > 0 && !state.selectedPrintType) {
            alert('Please select a print type for your custom design.');
            return;
        }

        // Show loading state
        const addToCartBtn = $('#add-to-cart-btn');
        const originalText = addToCartBtn.text();
        addToCartBtn.text('Adding...');
        addToCartBtn.prop('disabled', true);

        // DEBUG: Check state.designs first
        console.log('=== ADD TO CART DEBUG START ===');
        console.log('Total designs in state:', state.designs.length);
        state.designs.forEach((design, index) => {
            console.log(`Design ${index}:`, {
                type: design.type,
                attachmentId: design.attachmentId,
                attachmentUrl: design.attachmentUrl,
                hasSrc: !!design.src,
                hasFile: !!design.file
            });
        });

        // Prepare designs data (empty array if no designs)
        const designsData = state.designs.map(design => {
            const designData = {
                id: design.id,
                type: design.type,
                sideIndex: design.sideIndex,
                x: design.x,
                y: design.y,
                width: design.width,
                height: design.height,
                rotation: design.rotation || 0,
                flipH: !!design.flipH,
                flipV: !!design.flipV,
                printType: state.selectedPrintType
            };

            if (design.type === 'text') {
                designData.text = design.text;
                designData.fontSize = design.fontSize;
                designData.fontFamily = design.fontFamily;
                designData.color = design.color;
            } else if (design.type === 'image') {
                // CRITICAL: Do NOT include src (base64) as it's too large for database storage
                // The image is already uploaded to server with attachmentId - we can retrieve it later
                // Only include attachment ID and URL for original uploaded image
                designData.attachmentId = design.attachmentId;
                designData.attachmentUrl = design.attachmentUrl;

                console.log('Image design data prepared (src excluded for size):', {
                    id: designData.id,
                    attachmentId: designData.attachmentId,
                    attachmentUrl: designData.attachmentUrl
                });
            }

            return designData;
        });

        console.log('Designs data prepared for server:', JSON.stringify(designsData, null, 2));
        
        // Get product ID - try multiple sources
        let productId = null;
        if (state.product && state.product.id) {
            productId = state.product.id;
        } else if (state.product && state.product.woocommerceId) {
            productId = state.product.woocommerceId;
        } else {
            // Fallback: try to get from page URL or data attribute
            const urlMatch = window.location.pathname.match(/product[\/-]([^\/]+)/);
            if (urlMatch) {
                // Try to extract ID from slug (would need AJAX call to resolve)
                console.warn('Could not determine product ID from state');
            }
        }

        // Validate product ID before proceeding
        if (!productId) {
            console.error('Product ID missing. State:', state.product);
            alert('Error: Product ID is missing. Please refresh the page and try again.');
            addToCartBtn.text(originalText);
            addToCartBtn.prop('disabled', false);
            return;
        }

        // Create form data
        const formData = new FormData();
        formData.append('action', 'aakaari_add_to_cart');
        formData.append('security', AAKAARI_SETTINGS.nonce || '');
        formData.append('product_id', productId);
        formData.append('designs', JSON.stringify(designsData));
        
        // Add selected fabric, size, and color
        if (state.selectedFabric !== null && state.product.fabrics && state.product.fabrics[state.selectedFabric]) {
            formData.append('selected_fabric', state.product.fabrics[state.selectedFabric].id);
        }
        if (state.selectedSize !== null && state.product.sizes && state.product.sizes[state.selectedSize]) {
            formData.append('selected_size', state.product.sizes[state.selectedSize].id);
        }
        if (state.selectedColor !== null && state.product.colors && state.product.colors[state.selectedColor]) {
            formData.append('selected_color', state.product.colors[state.selectedColor].hex || state.product.colors[state.selectedColor].color);
        }
        
        // Debug logging
        console.log('Add to cart - Product ID:', productId);
        console.log('Add to cart - Nonce:', AAKAARI_SETTINGS.nonce ? 'Present' : 'Missing');
        console.log('Add to cart - Designs count:', designsData.length);
        
        // Capture and append preview image (data URL)
        try {
            const previewCanvas = document.getElementById('interactive-canvas');
            if (previewCanvas && typeof previewCanvas.toDataURL === 'function') {
                const dataUrl = previewCanvas.toDataURL('image/png');
                // Only append if not empty and looks like a data URL
                if (dataUrl && dataUrl.indexOf('data:image/png;base64,') === 0) {
                    formData.append('preview_image', dataUrl);
                }

                // Also generate a cropped PNG of just the primary print area
                if (typeof getPrimaryPrintArea === 'function') {
                    const area = getPrimaryPrintArea();
                    if (area) {
                        const cropCanvas = document.createElement('canvas');
                        cropCanvas.width = Math.max(1, Math.round(area.width));
                        cropCanvas.height = Math.max(1, Math.round(area.height));
                        const cropCtx = cropCanvas.getContext('2d');
                        // Draw the selected region from the main canvas onto the crop canvas
                        cropCtx.drawImage(
                            previewCanvas,
                            Math.round(area.x),
                            Math.round(area.y),
                            Math.round(area.width),
                            Math.round(area.height),
                            0,
                            0,
                            Math.round(area.width),
                            Math.round(area.height)
                        );
                        const cropDataUrl = cropCanvas.toDataURL('image/png');
                        if (cropDataUrl && cropDataUrl.indexOf('data:image/png;base64,') === 0) {
                            formData.append('print_area_image', cropDataUrl);
                        }
                    }
                }
            }
        } catch (err) {
            console.warn('Preview capture failed:', err);
        }

        // NOTE: Images are already uploaded to server when user selects them
        // We only send attachment IDs in the designs JSON (not files again)
        console.log('Image designs with attachment IDs:',
            state.designs.filter(d => d.type === 'image').map(d => ({
                id: d.id,
                attachmentId: d.attachmentId,
                attachmentUrl: d.attachmentUrl
            }))
        );

        // Send AJAX request
        $.ajax({
            url: AAKAARI_SETTINGS.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 30000, // 30 seconds timeout (no large files now)
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
            error: function(xhr, status, error) {
                // Show detailed error for debugging
                let errorMsg = 'Error adding to cart. Please try again.';

                // Check for timeout
                if (status === 'timeout') {
                    errorMsg = 'Upload timed out. Your image file may be too large. Please try with a smaller image or check your internet connection.';
                }
                // Check for file size errors
                else if (xhr.status === 413) {
                    errorMsg = 'Image file is too large. Please use a smaller image (recommended: under 5MB).';
                }
                // Check for server errors
                else if (xhr.status === 500) {
                    errorMsg = 'Server error occurred. This may be due to image size limits. Please try with a smaller image.';
                }
                // Parse other error messages
                else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMsg = xhr.responseJSON.data.message;
                } else if (xhr.responseText) {
                    // Try to parse response text
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.message) {
                            errorMsg = response.data.message;
                        }
                    } catch (e) {
                        console.error('AJAX Error Response:', xhr.responseText);
                    }
                }

                console.error('Add to cart failed:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    response: xhr.responseJSON || xhr.responseText,
                    error: error,
                    ajaxStatus: status
                });

                alert(errorMsg);
                
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