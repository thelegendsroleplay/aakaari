/**
 * Aakaari Theme: Product Customizer Script
 * Handles UI interactions for the customizer on single product pages.
 * Reads data from the 'wpCustomizerData' object (passed from PHP).
 */
jQuery(document).ready(function($) {
    // Check if we are on the customizer page by looking for the main container and data
    const customizerContainer = $('#customizer-page');
    if (!customizerContainer.length || typeof wpCustomizerData === 'undefined' || wpCustomizerData.error) {
        // console.log('Customizer data not found or error present.');
        if (wpCustomizerData && wpCustomizerData.error) {
             console.error('Customizer Error:', wpCustomizerData.error);
             // Optionally display an error message on the page
        }
        return; // Exit if not on customizer page or data failed
    }

	// --- 1. STATE & DATA INITIALIZATION ---
	const PRODUCT_DATA = wpCustomizerData;
	const PRINT_TYPES = wpCustomizerData.printTypes || []; // Get print types from WP data

	let state = {
        // Initialize state from wpCustomizerData
		selectedSideId: PRODUCT_DATA.sides[0]?.id || '',
		selectedPrintTypeId: PRINT_TYPES[0]?.id || '',
		selectedColor: PRODUCT_DATA.colors[0] || { name: 'Default', color: '#FFFFFF', image: null }, // Ensure default color exists
		designs: {}, // { side_id: [design1, design2], ... } - Starts empty
		selectedDesignId: null,

		// Canvas state
		zoom: 1,
		isDragging: false,
		isResizing: false,
		dragStart: { x: 0, y: 0 },
		resizeHandle: null,
		loadedProductImage: null, // HTMLImageElement for canvas background
	};

	const MIN_ZOOM = 0.5;
	const MAX_ZOOM = 3;

	// --- 2. DOM ELEMENT CACHE (Using jQuery selectors) ---
	const dom = {
		// Customizer Page Elements
        productName: $('#customizer-product-name'), // Already set by PHP
        productDesc: $('#customizer-product-desc'), // Already set by PHP
		customizerSideInfo: $('#customizer-side-info'),
		customizerTotalDesigns: $('#customizer-total-designs'),
		sideSelectorContainer: $('#side-selector-container'),
		colorSelectorCard: $('#color-selector-card'),
		colorSelectorContainer: $('#color-selector-container'),

		// Canvas Elements
		canvasContainer: $('#canvas-container'),
		canvas: $('#interactive-canvas')[0], // Get the raw canvas element
		ctx: $('#interactive-canvas')[0]?.getContext('2d'),
		zoomInBtn: $('#zoom-in-btn'),
		zoomOutBtn: $('#zoom-out-btn'),
		zoomResetBtn: $('#zoom-reset-btn'),
		zoomPercentage: $('#zoom-percentage'),

		// Tab Elements
		tabTriggers: customizerContainer.find('.tab-trigger'), // Scope to customizer
		tabContents: customizerContainer.find('.tab-content'), // Scope to customizer

		// Design Tab Elements
		addImageBtn: $('#add-image-btn'),
		fileUploadInput: $('#file-upload-input'),
		addTextInput: $('#add-text-input'),
		addTextBtn: $('#add-text-btn'),
		designListContainer: $('#design-list-container'),
		designListPlaceholder: $('#design-list-placeholder'),

		// Print Tab Elements
		printTypeContainer: $('#print-type-container'),
		pricingCalculationCard: $('#pricing-calculation-card'),
		pricingCalculationContent: $('#pricing-calculation-content'),

		// Price Summary & Form Elements
		cartForm: customizerContainer.find('form.customizer-cart'), // Target the specific form
		productBasePriceStrikethrough: $('#product-base-price-strikethrough'),
		productBasePrice: $('#product-base-price'),
		printCostSummary: $('#print-cost-summary'),
		printCostLabel: $('#print-cost-label'),
		printCostValue: $('#print-cost-value'),
		totalPrice: $('#total-price'),
		addToCartBtn: $('#add-to-cart-btn'),
		addToCartPlaceholder: $('#add-to-cart-placeholder'),

        // Hidden Form Inputs
        hiddenDesignsInput: $('#custom_designs_data'),
        hiddenPrintTypeInput: $('#custom_print_type_id'),
        hiddenColorInput: $('#custom_product_color'),
	};

    // Exit if canvas context couldn't be obtained
    if (!dom.ctx) {
        console.error("Could not get canvas context.");
        dom.canvasContainer.html('<p class="text-red-600 p-4 text-center">Error initializing canvas.</p>');
        return;
    }

	// --- 3. RENDER FUNCTIONS (Update the DOM based on state) ---

    // Main function to update the entire customizer UI
	function renderCustomizerUI() {
		if (!PRODUCT_DATA) return;

		const currentSide = PRODUCT_DATA.sides.find(s => s.id === state.selectedSideId);
		const currentDesigns = state.designs[state.selectedSideId] || [];

		// Preview Card Header
		dom.customizerSideInfo.text(`${currentSide?.name || 'Side'} • ${currentDesigns.length} design(s)`);
		const totalDesigns = getTotalDesignCount();
		dom.customizerTotalDesigns.html(`<i data-lucide="layers" class="mr-1 h-3 w-3"></i> ${totalDesigns} total`);

		// Side Selector Buttons
		renderSideSelector();
		// Color Selector Swatches
		renderColorSelector();
		// Design List Items
		renderDesignList();
		// Print Type Radio Buttons
		renderPrintTypeTab();
		// Price Summary Calculation
		updatePriceSummary();

		// Canvas (Load image if needed, then draw)
        // Ensure selectedColor is initialized
        if (!state.selectedColor && PRODUCT_DATA.colors.length > 0) {
            state.selectedColor = PRODUCT_DATA.colors[0];
        }
		loadProductImageAndDraw(state.selectedColor?.image || null); // Pass image URL

		// Re-initialize Lucide icons
        if (typeof lucide !== 'undefined' && lucide.createIcons) {
		    lucide.createIcons();
        }
	}

    // Function to render side selector buttons
    function renderSideSelector() {
        dom.sideSelectorContainer.empty();
		PRODUCT_DATA.sides.forEach(side => {
			const sideDesigns = state.designs[side.id] || [];
			const isSelected = side.id === state.selectedSideId;
			const button = $('<button>', {
                type: 'button',
				class: `w-full inline-flex items-center justify-center rounded-md text-sm font-medium h-10 px-4 py-2 ${
					isSelected ? 'bg-primary text-white' : 'border border-gray-300 bg-white text-gray-900 hover:bg-gray-100' // Adjusted classes
				}`
			});
            if(isSelected) button.css('background-color', '#3B82F6'); // Keep primary style

			let buttonHtml = escapeHtml(side.name);
			if (sideDesigns.length > 0) {
				buttonHtml += `
					<span class="ml-2 inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold ${
						isSelected ? 'bg-white text-primary' : 'bg-gray-100 text-gray-600' // Adjusted classes
					}">${sideDesigns.length}</span>`;
			}
            button.html(buttonHtml);

			button.on('click', () => {
				state.selectedSideId = side.id;
				state.selectedDesignId = null; // Deselect design when changing side
				renderCustomizerUI(); // Re-render everything
			});
			dom.sideSelectorContainer.append(button);
		});
    }

    // Function to render color swatches
	function renderColorSelector() {
		if (!PRODUCT_DATA.colors || PRODUCT_DATA.colors.length <= 1) {
			dom.colorSelectorCard.addClass('hidden');
			return;
		}

		dom.colorSelectorCard.removeClass('hidden');
		dom.colorSelectorContainer.empty();

		PRODUCT_DATA.colors.forEach(color => {
            // Ensure state.selectedColor is set before comparing
            const isSelected = state.selectedColor && color.name === state.selectedColor.name;
			const swatch = $('<button>', {
                type: 'button',
				class: `h-8 w-8 rounded-full border-2 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 ${
					isSelected ? 'border-blue-600 ring-2 ring-blue-600 ring-offset-2' : 'border-gray-300 hover:border-blue-400'
				}`,
				title: color.name,
			}).css('background-color', color.color);

			swatch.on('click', () => {
				state.selectedColor = color;
                dom.hiddenColorInput.val(color.name); // Update hidden input immediately
				loadProductImageAndDraw(color.image); // Load new image & redraw canvas
				renderColorSelector(); // Re-render swatches to show selection
			});
			dom.colorSelectorContainer.append(swatch);
		});
	}

    // Function to render the list of added designs
	function renderDesignList() {
        const currentSide = PRODUCT_DATA.sides.find(s => s.id === state.selectedSideId);
		const currentDesigns = state.designs[state.selectedSideId] || [];
		dom.designListContainer.empty(); // Clear previous list

		if (currentDesigns.length > 0) {
			dom.designListPlaceholder.addClass('hidden'); // Hide placeholder

			// Add label for the current side
			const label = $('<label>', {
				class: 'text-sm font-medium block mb-2', // Added block and margin
				text: `Current Designs on ${currentSide?.name || 'side'}`
			});
			dom.designListContainer.append(label);

			const listWrapper = $('<div>', { class: 'space-y-2' });

			// Create list item for each design
			currentDesigns.forEach(design => {
				const isSelected = design.id === state.selectedDesignId;
				const designItem = $('<div>', {
					class: `flex items-center justify-between p-3 rounded-lg border transition-colors cursor-pointer ${
						isSelected ? 'border-blue-600 bg-blue-50/50' : 'border-gray-200 bg-white hover:bg-gray-100' // Adjusted classes
					}`
				});

				const cost = calculatePrintCost(design);
				const costHtml = state.selectedPrintTypeId
					? `• Cost: ₹${cost.toFixed(2)}` // Using ₹ symbol
					: '';

                const contentName = design.type === 'text'
					? design.content
					: (design.fileName || 'Uploaded Image');
                const safeContentName = escapeHtml(contentName);

				designItem.html(`
					<div class="flex-1 overflow-hidden mr-2">
						<div class="flex items-center gap-2">
							<span class="text-sm font-medium truncate" title="${safeContentName}">
								${safeContentName}
							</span>
						</div>
						<div class="text-xs text-gray-500 mt-1">
							Size: ${Math.round(design.width)} × ${Math.round(design.height)} px ${costHtml}
						</div>
					</div>
					<button type="button" class="delete-design-btn inline-flex items-center justify-center rounded-md text-sm font-medium h-8 w-8 p-0 flex-shrink-0" data-design-id="${design.id}" aria-label="Delete design">
						<i data-lucide="trash-2" class="h-4 w-4 text-red-500"></i>
					</button>
				`);

                // Click handler for selection/deletion
				designItem.on('click', (e) => {
                    const deleteButton = $(e.target).closest('.delete-design-btn');
					if (deleteButton.length) { // Check if delete button was clicked
						e.stopPropagation();
						handleRemoveDesign(design.id);
					} else {
						state.selectedDesignId = design.id;
						renderDesignList(); // Re-render list to show selection change
						drawInteractiveCanvas(); // Re-draw canvas for selection handles
					}
				});

				listWrapper.append(designItem);
			});
			dom.designListContainer.append(listWrapper);
		} else {
            // Show placeholder if no designs
			dom.designListPlaceholder.removeClass('hidden');
            // Ensure placeholder content is set (from index.html)
             dom.designListPlaceholder.html(`
                 <div class="flex">
                     <i data-lucide="info" class="h-4 w-4 text-gray-600 mr-2 mt-0.5 flex-shrink-0"></i>
                     <div class="flex-1">
                         <p class="text-sm text-gray-600">
                             Add images or text to start customizing. Drag and resize designs within the print area.
                         </p>
                     </div>
                 </div>
            `);
		}
        // Ensure icons are rendered
         if (typeof lucide !== 'undefined' && lucide.createIcons) {
		    lucide.createIcons();
        }
	}

    // Function to render print type options and pricing card
	function renderPrintTypeTab() {
		dom.printTypeContainer.empty(); // Clear previous options
		PRINT_TYPES.forEach(printType => {
			let priceLabel = '';
			switch (printType.pricingModel) {
				case 'fixed': priceLabel = `₹${printType.price.toFixed(2)}`; break;
				case 'per-inch': priceLabel = `₹${printType.price.toFixed(2)}/sq in`; break;
				case 'per-px': priceLabel = `₹${printType.price.toFixed(4)}/px²`; break;
                default: priceLabel = `₹${printType.price.toFixed(2)}`;
			}

			const printTypeItem = $('<div>', {
				class: 'flex items-start space-x-3 p-4 border rounded-lg hover:border-blue-600 transition-colors cursor-pointer' // Added cursor pointer
			});

            // Use unique ID for input and label 'for' attribute
            const inputId = `print-type-${printType.id}`;

			printTypeItem.html(`
				<input
					type="radio"
					name="printTypeRadio" <?php // Use a common name ?>
					value="${printType.id}"
					id="${inputId}"
					class="mt-1"
					${printType.id === state.selectedPrintTypeId ? 'checked' : ''}
				/>
				<div class="flex-1">
					<label for="${inputId}" class="cursor-pointer block"> <?php // Label wraps content ?>
						<div class="flex items-center justify-between mb-1">
							<span class="font-medium">${escapeHtml(printType.name)}</span>
							<span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-600">
								${priceLabel}
							</span>
						</div>
						<p class="text-sm text-gray-500">${escapeHtml(printType.description)}</p>
					</label>
				</div>
			`);

            // Change event for the radio button
			printTypeItem.find('input[type="radio"]').on('change', (e) => {
				state.selectedPrintTypeId = e.target.value;
                dom.hiddenPrintTypeInput.val(state.selectedPrintTypeId); // Update hidden input
				renderCustomizerUI(); // Re-render to update prices and potentially the pricing card
			});

            // Click handler for the whole div to select the radio
            printTypeItem.on('click', function(e) {
                // Don't trigger if the click was directly on the input or label
                if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'LABEL') {
                    $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
                }
            });

			dom.printTypeContainer.append(printTypeItem);
		});

		// --- Update Pricing Calculation Card ---
		const totalDesigns = getTotalDesignCount();
		if (totalDesigns > 0 && state.selectedPrintTypeId) {
			dom.pricingCalculationCard.removeClass('hidden');
			dom.pricingCalculationContent.empty(); // Clear previous breakdown

			Object.entries(state.designs).forEach(([sideId, sideDesigns]) => {
				if (!Array.isArray(sideDesigns) || sideDesigns.length === 0) return;

				const side = PRODUCT_DATA.sides.find(s => s.id === sideId);
				const sideCost = sideDesigns.reduce((sum, d) => sum + calculatePrintCost(d), 0);

				const row = $('<div>', { class: 'flex justify-between' });
				row.html(`
					<span class="text-gray-500">
						${escapeHtml(side?.name || sideId)} (${sideDesigns.length} design${sideDesigns.length > 1 ? 's' : ''}):
					</span>
					<span>₹${sideCost.toFixed(2)}</span>
				`);
				dom.pricingCalculationContent.append(row);
			});
		} else {
			dom.pricingCalculationCard.addClass('hidden'); // Hide if no designs or no print type selected
		}
	}

    // Function to update the final price summary
	function updatePriceSummary() {
		const basePrice = PRODUCT_DATA.sale_price || PRODUCT_DATA.base_price;

        // Update base price display (handle sale price strikethrough)
		if (PRODUCT_DATA.sale_price) {
			dom.productBasePriceStrikethrough.text(`₹${PRODUCT_DATA.base_price.toFixed(2)}`).removeClass('hidden');
		} else {
			dom.productBasePriceStrikethrough.addClass('hidden');
		}
		dom.productBasePrice.text(`₹${basePrice.toFixed(2)}`);

		const totalDesigns = getTotalDesignCount();
		const currentPrintType = PRINT_TYPES.find(p => p.id === state.selectedPrintTypeId);

		let totalPrintCost = 0;
        let finalTotalPrice = basePrice;

		// Calculate and display print cost if applicable
		if (totalDesigns > 0 && currentPrintType) {
			totalPrintCost = Object.values(state.designs).flat().reduce((sum, d) => sum + calculatePrintCost(d), 0);
            finalTotalPrice += totalPrintCost;

			dom.printCostSummary.removeClass('hidden');
			dom.printCostLabel.text(`${escapeHtml(currentPrintType.name)} (${totalDesigns} design${totalDesigns > 1 ? 's' : ''}):`);
			dom.printCostValue.text(`+₹${totalPrintCost.toFixed(2)}`);

		} else {
			dom.printCostSummary.addClass('hidden'); // Hide print cost row
		}

        // Update total price display
		dom.totalPrice.text(`₹${finalTotalPrice.toFixed(2)}`);

        // Enable/disable Add to Cart button and show/hide placeholder text
		dom.addToCartBtn.prop('disabled', totalDesigns === 0);
        dom.addToCartPlaceholder.toggleClass('hidden', totalDesigns > 0);

        // Update hidden form inputs needed for cart submission
        dom.hiddenPrintTypeInput.val(state.selectedPrintTypeId || '');
        dom.hiddenColorInput.val(state.selectedColor?.name || 'Default');
        // Designs input is updated just before form submit in handleFormSubmit
	}

    // --- Helper to escape HTML ---
    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
     }

	// --- 4. ACTION HANDLERS (Add Text, Add Image, Remove Design, etc.) ---
    // (Keep handleAddText, handleAddImageClick, handleFileUpload, handleRemoveDesign functions
    // exactly as they were in the standalone script.js)

    function handleAddText() {
		const textInput = dom.addTextInput.val();
		if (!textInput.trim() || !state.selectedSideId) return;

		const currentSide = PRODUCT_DATA.sides.find(s => s.id === state.selectedSideId);
		const printArea = currentSide?.printAreas[0]; // Assuming first print area
		if (!printArea) {
            alert('No print area defined for this side.');
            return;
        }

		const newDesign = {
			id: `text-${Date.now()}`,
			type: 'text',
			content: textInput,
			element: null, // No HTML element needed for text rendering on canvas
			fileName: null,
			x: printArea.x + 20, // Default position within print area
			y: printArea.y + 40,
			width: 150, // Default width for text
			height: 30, // Default height for text (adjust based on font size?)
			rotation: 0,
		};

        // Add to state
		const currentDesigns = state.designs[state.selectedSideId] || [];
		state.designs[state.selectedSideId] = [...currentDesigns, newDesign];
		state.selectedDesignId = newDesign.id; // Select the new text design

        // Update UI
		dom.addTextInput.val(''); // Clear input
		dom.addTextBtn.prop('disabled', true); // Disable button until new input
		renderCustomizerUI(); // Re-render everything
	}

	function handleAddImageClick() {
		dom.fileUploadInput.trigger('click'); // Trigger hidden file input
	}

	function handleFileUpload(e) {
		const file = e.target.files[0];
		if (!file || !state.selectedSideId) return;

		const currentSide = PRODUCT_DATA.sides.find(s => s.id === state.selectedSideId);
		const printArea = currentSide?.printAreas[0];
		if (!printArea) {
             alert('No print area defined for this side.');
             return;
        }

        // Optional: File type/size check
        const allowedTypes = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'];
        const maxSize = 10 * 1024 * 1024; // 10MB
        if (!allowedTypes.includes(file.type)) {
            alert('Invalid file type. Please upload PNG, JPG, WEBP or SVG.');
            e.target.value = null; // Reset file input
            return;
        }
        if (file.size > maxSize) {
             alert('File is too large. Maximum size is 10MB.');
             e.target.value = null;
             return;
        }

		const reader = new FileReader();
		reader.onload = (event) => {
			const img = new Image();
			img.onload = () => {
				// Calculate initial size (e.g., fit within 50% of print area)
				const maxWidth = printArea.width * 0.5;
				const maxHeight = printArea.height * 0.5;
				let width = img.width;
				let height = img.height;
				const ratio = width / height;

				if (width > maxWidth) { width = maxWidth; height = width / ratio; }
				if (height > maxHeight) { height = maxHeight; width = height * ratio; }
                width = Math.max(20, width); // Ensure min size
                height = Math.max(20, height);

				const newDesign = {
					id: `image-${Date.now()}`,
					type: 'image',
					content: file.name, // Store original name
					element: img, // Store the loaded Image object for canvas drawing
					fileName: file.name,
					x: printArea.x + (printArea.width - width) / 2, // Center
					y: printArea.y + (printArea.height - height) / 2,
					width: Math.round(width),
					height: Math.round(height),
					rotation: 0,
				};

                // Add to state
				const currentDesigns = state.designs[state.selectedSideId] || [];
				state.designs[state.selectedSideId] = [...currentDesigns, newDesign];
				state.selectedDesignId = newDesign.id; // Select new image

                // Update UI
				renderCustomizerUI(); // Re-render everything
			};
            img.onerror = () => {
                 alert('Error loading image file. Please try a different image.');
             };
			img.src = event.target.result; // Set src AFTER onload is defined
		};
        reader.onerror = () => {
             alert('Error reading file. Please try again.');
         };
		reader.readAsDataURL(file);

		// Reset file input to allow uploading the same file again if needed
		e.target.value = null;
	}

	function handleRemoveDesign(designId) {
		if (!state.selectedSideId) return;

        // Filter out the design
		state.designs[state.selectedSideId] = (state.designs[state.selectedSideId] || []).filter(d => d.id !== designId);

        // Deselect if the removed one was selected
		if (state.selectedDesignId === designId) {
			state.selectedDesignId = null;
		}

		renderCustomizerUI(); // Re-render everything
	}

    // Handler for tab switching
	function handleTabClick(e) {
		const clickedTab = $(e.target).closest('.tab-trigger');
		if (!clickedTab.length) return;

		const tabName = clickedTab.data('tab');

		// Update tab trigger visual state
		dom.tabTriggers.each(function() {
            const $trigger = $(this);
            const isTarget = $trigger.data('tab') === tabName;
			$trigger
                .toggleClass('border-primary text-primary', isTarget)
                .toggleClass('text-gray-500 hover:text-gray-700', !isTarget)
                .css('border-color', isTarget ? '#3B82F6' : 'transparent');
		});

		// Show/Hide tab content
		dom.tabContents.each(function() {
            const $content = $(this);
			$content.toggleClass('hidden', $content.attr('id') !== `${tabName}-tab-content`);
		});
	}

    // Handler for Add to Cart form submission
    function handleFormSubmit(e) {
        // Prepare the design data (strip out non-serializable parts like 'element')
        const designsToSave = {};
        Object.entries(state.designs).forEach(([sideId, sideDesigns]) => {
            if (Array.isArray(sideDesigns)) {
                designsToSave[sideId] = sideDesigns.map(d => ({
                    id: d.id, type: d.type, content: d.content, fileName: d.fileName,
                    x: d.x, y: d.y, width: d.width, height: d.height, rotation: d.rotation,
                }));
            }
        });

        // Update hidden inputs just before submission
        dom.hiddenDesignsInput.val(JSON.stringify(designsToSave));
        // Other hidden inputs (color, print type) are updated on change

        // Allow the form to submit normally (no e.preventDefault())
        return true;
    }


	// --- 5. CANVAS LOGIC & EVENT HANDLERS ---
    // (Keep loadProductImageAndDraw, drawInteractiveCanvas, getCanvasCoordinates,
    // isInPrintArea, isInRestrictionArea, handleCanvasMouseDown, handleCanvasMouseMove,
    // handleCanvasMouseUp, handleZoom functions exactly as they were in the standalone script.js)

    function loadProductImageAndDraw(imageUrl) {
		if (!dom.ctx) return; // Ensure context exists

        // If no image URL, clear background and draw overlays
		if (!imageUrl) {
			state.loadedProductImage = null;
            dom.canvas.style.backgroundColor = '#FFFFFF'; // Ensure white bg
			drawInteractiveCanvas(); // Draw overlays on default white background
			return;
		}
         // Optional: Clear bg while loading
         dom.canvas.style.backgroundColor = '#E5E7EB'; // Gray placeholder

		const img = new Image();
		img.crossOrigin = "Anonymous"; // Needed for external images if using placeholders
		img.onload = () => {
			state.loadedProductImage = img;
            dom.canvas.style.backgroundColor = 'transparent'; // Remove placeholder bg
			drawInteractiveCanvas(); // Draw image + overlays
		};
		img.onerror = () => {
			console.error("Failed to load product image:", imageUrl);
			state.loadedProductImage = null; // Failed, draw without bg
            dom.canvas.style.backgroundColor = '#FFFFFF'; // Ensure white bg on error
			drawInteractiveCanvas(); // Draw overlays on default white background
		};
		img.src = imageUrl;
	}

    function drawInteractiveCanvas() {
		if (!dom.ctx || !PRODUCT_DATA) return;

		const canvas = dom.canvas;
		const ctx = dom.ctx;
		const currentSide = PRODUCT_DATA.sides.find(s => s.id === state.selectedSideId);
		if (!currentSide) return;

		const printArea = currentSide.printAreas[0]; // Assuming first print area
        // CRITICAL: Check if printArea exists
         if (!printArea) {
             // Clear canvas and show error
             ctx.fillStyle = '#f8f9fa'; // Light grey background
             ctx.fillRect(0, 0, canvas.width, canvas.height);
             ctx.fillStyle = '#dc2626'; // Red text
             ctx.font = '16px Arial';
             ctx.textAlign = 'center';
             ctx.fillText('No print area configured for this side.', canvas.width / 2, canvas.height / 2);
             console.warn("No print area defined for side:", currentSide.name);
             return; // Stop drawing
         }

		const restrictionAreas = currentSide.restrictionAreas || [];
		const designs = state.designs[state.selectedSideId] || [];

		// 1. Clear canvas (background image is handled by loadProductImageAndDraw)
        ctx.clearRect(0, 0, canvas.width, canvas.height);

		// 2. Draw background product image if loaded
		if (state.loadedProductImage) {
            try {
			    ctx.drawImage(state.loadedProductImage, 0, 0, canvas.width, canvas.height);
            } catch (e) {
                console.error("Error drawing background image:", e);
                // Fallback clear if drawing fails
                 ctx.fillStyle = '#FFFFFF';
                 ctx.fillRect(0, 0, canvas.width, canvas.height);
            }
		} else {
             // If no bg image is loaded (or failed), ensure a white background
             ctx.fillStyle = '#FFFFFF';
             ctx.fillRect(0, 0, canvas.width, canvas.height);
        }

		// 3. Draw print area (Blue dashed)
		ctx.strokeStyle = '#2563EB'; // Blue
		ctx.lineWidth = 2;
		ctx.setLineDash([8, 4]);
		ctx.strokeRect(printArea.x, printArea.y, printArea.width, printArea.height);
		ctx.setLineDash([]);

		// 4. Draw restriction areas (Red dashed with light fill)
		restrictionAreas.forEach((area) => {
			ctx.fillStyle = 'rgba(239, 68, 68, 0.1)'; // Light Red fill
			ctx.fillRect(area.x, area.y, area.width, area.height);
			ctx.strokeStyle = '#EF4444'; // Red border
			ctx.lineWidth = 1;
			ctx.setLineDash([4, 4]);
			ctx.strokeRect(area.x, area.y, area.width, area.height);
			ctx.setLineDash([]);
		});

		// 5. Draw added designs (text or images)
		designs.forEach((design) => {
			ctx.save();

			// Apply rotation if needed (logic exists but no UI to set it yet)
			if (design.rotation && design.rotation !== 0) {
				const centerX = design.x + design.width / 2;
				const centerY = design.y + design.height / 2;
				ctx.translate(centerX, centerY);
				ctx.rotate((design.rotation * Math.PI) / 180);
				ctx.translate(-centerX, -centerY);
			}

			if (design.type === 'text') {
				ctx.fillStyle = '#000000'; // Black text
				ctx.font = '24px Arial'; // Consider making font configurable
				// Adjust y-coordinate for fillText baseline
				ctx.fillText(design.content, design.x, design.y + 24);
			} else if (design.type === 'image' && design.element instanceof HTMLImageElement) {
				// Draw the actual uploaded image element stored in the design object
                try {
				    ctx.drawImage(design.element, design.x, design.y, design.width, design.height);
                } catch (e) {
                     console.error("Error drawing design image:", e);
                     // Optionally draw an error placeholder for this specific design
                     ctx.fillStyle = '#fef2f2'; ctx.fillRect(design.x, design.y, design.width, design.height);
                     ctx.strokeStyle = '#fecaca'; ctx.lineWidth = 1; ctx.strokeRect(design.x, design.y, design.width, design.height);
                     ctx.fillStyle = '#dc2626'; ctx.font = '12px Arial'; ctx.textAlign = 'center';
                     ctx.fillText('Error', design.x + design.width / 2, design.y + design.height / 2 + 5); ctx.textAlign = 'left';
                 }
			}

			// Draw selection box and handles if this design is selected
			if (design.id === state.selectedDesignId) {
				ctx.strokeStyle = '#2563EB'; // Blue selection border
				ctx.lineWidth = 2;
				ctx.strokeRect(design.x - 2, design.y - 2, design.width + 4, design.height + 4);

				// Draw resize handles (blue squares)
				const handleSize = 8;
				ctx.fillStyle = '#2563EB'; // Blue handles
				const handles = [
					{ x: design.x, y: design.y }, // Top-left
					{ x: design.x + design.width, y: design.y }, // Top-right
					{ x: design.x, y: design.y + design.height }, // Bottom-left
					{ x: design.x + design.width, y: design.y + design.height } // Bottom-right
				];
				handles.forEach(handle => {
					ctx.fillRect(handle.x - handleSize / 2, handle.y - handleSize / 2, handleSize, handleSize);
				});
			}

			ctx.restore(); // Restore context state (like rotation)
		});
	}

    // --- Canvas Interaction Helpers ---
    function getCanvasCoordinates(clientX, clientY) {
		const canvas = dom.canvas;
        if (!canvas) return { x: 0, y: 0 };
		const rect = canvas.getBoundingClientRect();
        // Calculate scale factors in case the canvas CSS size differs from its render size
		const scaleX = canvas.width / rect.width;
		const scaleY = canvas.height / rect.height;

		return {
			x: Math.round((clientX - rect.left) * scaleX),
			y: Math.round((clientY - rect.top) * scaleY),
		};
	}

    // Checks if a box is entirely within the first print area of the current side
	function isInPrintArea(x, y, w, h) {
        const currentSide = PRODUCT_DATA.sides.find(s => s.id === state.selectedSideId);
		const printArea = currentSide?.printAreas[0];
		if (!printArea) return false; // Can't be inside if no print area exists
		return (
			x >= printArea.x &&
			y >= printArea.y &&
			(x + w) <= (printArea.x + printArea.width) &&
			(y + h) <= (printArea.y + printArea.height)
		);
	}

    // Checks if a box overlaps with ANY restriction area of the current side
	function isInRestrictionArea(x, y, w, h) {
        const currentSide = PRODUCT_DATA.sides.find(s => s.id === state.selectedSideId);
		const restrictionAreas = currentSide?.restrictionAreas || [];
		return restrictionAreas.some((area) => {
            // Check for NO overlap, then negate the result
            const noOverlap = (
                (x + w) <= area.x || // Box is entirely to the left of area
                x >= (area.x + area.width) || // Box is entirely to the right of area
                (y + h) <= area.y || // Box is entirely above area
                y >= (area.y + area.height) // Box is entirely below area
            );
            return !noOverlap; // If there's no "noOverlap", it means there IS overlap
		});
	}

    // --- Canvas Mouse Event Handlers ---
	function handleCanvasMouseDown(e) {
        // Prevent default browser actions like text selection or image dragging
        e.preventDefault();
		const coords = getCanvasCoordinates(e.clientX, e.clientY);
		const designs = state.designs[state.selectedSideId] || [];

		// Check if clicking on resize handle of the currently selected design
		const selectedDesign = designs.find((d) => d.id === state.selectedDesignId);
		if (selectedDesign) {
			const handleSize = 8;
            const handleMargin = handleSize; // Larger clickable area around handle
			const handles = [ // Coordinates of the corners
				{ name: 'tl', x: selectedDesign.x, y: selectedDesign.y },
				{ name: 'tr', x: selectedDesign.x + selectedDesign.width, y: selectedDesign.y },
				{ name: 'bl', x: selectedDesign.x, y: selectedDesign.y + selectedDesign.height },
				{ name: 'br', x: selectedDesign.x + selectedDesign.width, y: selectedDesign.y + selectedDesign.height },
			];

			for (const handle of handles) {
                // Check if click is within the handle's clickable margin
				if (
					coords.x >= handle.x - handleMargin && coords.x <= handle.x + handleMargin &&
					coords.y >= handle.y - handleMargin && coords.y <= handle.y + handleMargin
				) {
					state.isResizing = true;
					state.resizeHandle = handle.name; // Store which handle is being dragged ('tl', 'br', etc.)
					state.dragStart = { // Store starting mouse coords and original dimensions
                        x: coords.x,
                        y: coords.y,
                        origX: selectedDesign.x,
                        origY: selectedDesign.y,
                        origW: selectedDesign.width,
                        origH: selectedDesign.height
                    };
					drawInteractiveCanvas(); // Redraw immediately (optional, shows active handle)
					return; // Stop further checks if handle is clicked
				}
			}
		}

		// If not resizing, check if clicking on any design (check top-most first)
		const clickedDesign = [...designs].reverse().find((design) => {
            // Simple bounding box check
			return (
				coords.x >= design.x && coords.x <= design.x + design.width &&
				coords.y >= design.y && coords.y <= design.y + design.height
			);
		});

		if (clickedDesign) {
            // Select the clicked design
            const newlySelected = state.selectedDesignId !== clickedDesign.id;
			state.selectedDesignId = clickedDesign.id;
			state.isDragging = true;
            // Store the offset of the click relative to the design's top-left corner
			state.dragStart = { x: coords.x - clickedDesign.x, y: coords.y - clickedDesign.y };
            if (newlySelected) {
                 renderDesignList(); // Update list UI only if the selection *changed*
            }
		} else {
            // Clicked on empty space, deselect
             if (state.selectedDesignId !== null) { // Only update UI if something *was* selected
                 renderDesignList();
             }
			state.selectedDesignId = null;
            state.isDragging = false; // Ensure dragging stops
		}

		drawInteractiveCanvas(); // Redraw canvas to show/hide selection boxes/handles
	}

	function handleCanvasMouseMove(e) {
        // Only run if dragging or resizing is active
		if (!state.isDragging && !state.isResizing) return;

		const coords = getCanvasCoordinates(e.clientX, e.clientY);
		const designs = state.designs[state.selectedSideId] || [];
		const selectedDesign = designs.find((d) => d.id === state.selectedDesignId);

        // Safety check - should have a selected design if interacting
		if (!selectedDesign) {
             state.isDragging = false;
             state.isResizing = false;
             return;
         }

		if (state.isResizing && state.resizeHandle && state.dragStart) {
            // --- Resizing Logic ---
			let newX = state.dragStart.origX;
			let newY = state.dragStart.origY;
			let newWidth = state.dragStart.origW;
			let newHeight = state.dragStart.origH;

            // Calculate new dimensions based on handle and mouse delta
            const dx = coords.x - state.dragStart.x; // How far mouse moved horizontally
            const dy = coords.y - state.dragStart.y; // How far mouse moved vertically

			switch (state.resizeHandle) {
				case 'br': // Bottom-right: change width & height
					newWidth = state.dragStart.origW + dx;
					newHeight = state.dragStart.origH + dy;
					break;
				case 'bl': // Bottom-left: change width, height, and x
					newWidth = state.dragStart.origW - dx;
					newHeight = state.dragStart.origH + dy;
                    newX = state.dragStart.origX + dx;
					break;
				case 'tr': // Top-right: change width, height, and y
					newWidth = state.dragStart.origW + dx;
					newHeight = state.dragStart.origH - dy;
                    newY = state.dragStart.origY + dy;
					break;
				case 'tl': // Top-left: change width, height, x, and y
					newWidth = state.dragStart.origW - dx;
					newHeight = state.dragStart.origH - dy;
                    newX = state.dragStart.origX + dx;
                    newY = state.dragStart.origY + dy;
					break;
			}

            // Enforce minimum size
            if (newWidth < 20) {
                 if (state.resizeHandle.includes('l')) { // If dragging left handle, adjust x instead of width
                     newX = state.dragStart.origX + state.dragStart.origW - 20;
                 }
                 newWidth = 20;
             }
             if (newHeight < 20) {
                 if (state.resizeHandle.includes('t')) { // If dragging top handle, adjust y instead of height
                     newY = state.dragStart.origY + state.dragStart.origH - 20;
                 }
                 newHeight = 20;
             }

            // Check boundaries *before* updating state
			if (isInPrintArea(newX, newY, newWidth, newHeight) && !isInRestrictionArea(newX, newY, newWidth, newHeight)) {
                // Update the actual design object in the state array
                selectedDesign.x = Math.round(newX);
                selectedDesign.y = Math.round(newY);
                selectedDesign.width = Math.round(newWidth);
                selectedDesign.height = Math.round(newHeight);

				drawInteractiveCanvas(); // Redraw immediately
                // Throttle UI updates if performance becomes an issue
                renderDesignList(); // Update size/cost info in the side list
                updatePriceSummary();
			}
            // If boundaries fail, the design object isn't updated, preventing invalid state

		} else if (state.isDragging && state.dragStart) {
            // --- Dragging Logic ---
            // Calculate new top-left based on mouse position and initial click offset
			const newX = coords.x - state.dragStart.x;
			const newY = coords.y - state.dragStart.y;

            // Check boundaries *before* updating state
			if (isInPrintArea(newX, newY, selectedDesign.width, selectedDesign.height) &&
				!isInRestrictionArea(newX, newY, selectedDesign.width, selectedDesign.height)) {

                // Update the actual design object in the state array
                selectedDesign.x = Math.round(newX);
				selectedDesign.y = Math.round(newY);

				drawInteractiveCanvas(); // Redraw immediately
			}
             // If boundaries fail, the design object isn't updated
		}
	}

	function handleCanvasMouseUp(e) {
        // If resizing was in progress, recalculate costs based on final size
         if (state.isResizing) {
             renderDesignList(); // Update list display
             updatePriceSummary(); // Update total price
         }

        // Reset interaction states
		state.isDragging = false;
		state.isResizing = false;
		state.resizeHandle = null;
        state.dragStart = null; // Clear drag start info
        // Keep selectedDesignId as is, it's only cleared on clicking away
	}

    // --- Canvas Zoom Handlers ---
    function handleZoom(delta) {
        const newZoom = Math.max(MIN_ZOOM, Math.min(MAX_ZOOM, state.zoom + delta));
        if (newZoom !== state.zoom) { // Only update if zoom actually changed
            state.zoom = newZoom;
            dom.canvas.style.transform = `scale(${state.zoom})`;
            dom.zoomPercentage.text(Math.round(state.zoom * 100));
            dom.zoomInBtn.prop('disabled', state.zoom >= MAX_ZOOM);
            dom.zoomOutBtn.prop('disabled', state.zoom <= MIN_ZOOM);
        }
	}

	// --- 6. HELPER FUNCTIONS ---
    // (Keep calculatePrintCost and getTotalDesignCount exactly as before)

    function calculatePrintCost(design) {
		const currentPrintType = PRINT_TYPES.find(p => p.id === state.selectedPrintTypeId);
		if (!currentPrintType || !design || typeof design.width !== 'number' || typeof design.height !== 'number') return 0;

		switch (currentPrintType.pricingModel) {
			case 'fixed': return currentPrintType.price;
			case 'per-inch': return ( (design.width * design.height) / 144.0 ) * currentPrintType.price; // Assuming 12px = 1 inch
			case 'per-px': return ( design.width * design.height ) * currentPrintType.price;
			default: return 0;
		}
	}

	function getTotalDesignCount() {
		return Object.values(state.designs).reduce((sum, sideDesigns) => sum + (Array.isArray(sideDesigns) ? sideDesigns.length : 0), 0);
	}

	// --- 7. INITIALIZATION & EVENT LISTENERS ---

	function init() {
        // Set initial values for hidden fields based on default state
        dom.hiddenPrintTypeInput.val(state.selectedPrintTypeId || '');
        dom.hiddenColorInput.val(state.selectedColor?.name || 'Default');

		// Attach Event Listeners using jQuery
		dom.tabTriggers.parent().on('click', '.tab-trigger', handleTabClick);

		// Design Tab
		dom.addTextInput.on('input', (e) => { dom.addTextBtn.prop('disabled', !$(e.target).val().trim()); });
		dom.addTextInput.on('keypress', (e) => { if (e.key === 'Enter') handleAddText(); });
		dom.addTextBtn.on('click', handleAddText);
		dom.addImageBtn.on('click', handleAddImageClick);
		dom.fileUploadInput.on('change', handleFileUpload);

		// Canvas Listeners (using raw JS for finer control if needed, or jQuery)
        if (dom.canvas) {
		    dom.canvas.addEventListener('mousedown', handleCanvasMouseDown);
		    dom.canvas.addEventListener('mousemove', handleCanvasMouseMove);
            // Use window/document for mouseup/leave to catch events outside canvas
		    window.addEventListener('mouseup', handleCanvasMouseUp);
            // dom.canvas.addEventListener('mouseleave', handleCanvasMouseUp); // Mouseup on window is safer
        }

		// Zoom Buttons
		dom.zoomInBtn.on('click', () => handleZoom(0.25));
		dom.zoomOutBtn.on('click', () => handleZoom(-0.25));
		dom.zoomResetBtn.on('click', () => handleZoom(1 - state.zoom)); // Calculate delta needed to reach 1

        // Cart Form Submission
        dom.cartForm.on('submit', handleFormSubmit);

		// Initial Render
		renderCustomizerUI();
	}

	init(); // Run initialization

}); // End jQuery ready